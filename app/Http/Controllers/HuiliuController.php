<?php

namespace App\Http\Controllers;

use App\Book;
use App\BookShelf;
use App\BookSku;
use App\BookVersion;
use App\CartItem;
use App\ClientError;
use App\Comment;
use App\Coupon;
use App\Events\BookRecoverPriceRisen;
use App\Events\OrderCanceled;
use App\Events\OrderCompleted;
use App\Events\OrderDelivered;
use App\Events\OrderPaid;
use App\Events\OrderSigned;
use App\Jobs\CancelOrderJob;
use App\Jobs\CancelSaleOrderIn15Minutes;
use App\Jobs\CrawlingByWebPageISBN;
use App\Jobs\CrawlingByWebPageSubjectId;
use App\Jobs\DispatchGetUserWechatInfoJob;
use App\Jobs\DownloadCoverImageBySubjectId;
use App\Jobs\FetchBookFromDouban;
use App\Jobs\GetDbSecondDataJob;
use App\Jobs\GiveCouponsJob;
use App\Jobs\GetUserWechatInfoJob;
use App\Jobs\UpdateUserRecommend;
use App\Juzi;
use App\Lunar;
use App\RecoverReport;
use App\ShudanComment;
use App\ShudanDianzan;
use App\UserAddress;
use App\Order;
use App\OrderItem;
use App\PendingBook;
use App\ReminderItem;
use App\SaleItem;
use App\Services\OrderService;
use App\Shudan;
use App\Tag;
use App\Taggable;
use App\User;
use App\UserBanBook;
use App\UserSearchHistory;
use App\Utils\Tools;
use App\ViewBook;
use App\Wallet;
use App\WxMsg;
use Barryvdh\Snappy\Facades\SnappyImage;
use Carbon\Carbon;
use EasyWeChat\Kernel\Exceptions\InvalidArgumentException;
use EasyWeChat\Kernel\Messages\Transfer;
use EasyWeChat\Kernel\Messages\Image;
use EasyWeChat\OfficialAccount\Application;
use EasyWeChat\Payment\Application as WxPayment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class HuiliuController extends Controller
{
    protected $app, $payment, $orderService;

    public function __construct(Application $app, WxPayment $payment, OrderService $orderService)
    {
        $this->app = $app;
        $this->payment = $payment;
        $this->orderService = $orderService;
    }


    protected function fetchUser($wx_user)
    {
        $original = $wx_user->getOriginal();
        $unionid = $original['unionid'];
        if (is_null($unionid) || empty($unionid)) {
            $unionid = $wx_user->original->unionid;
        }
        if (is_null($unionid) || empty($unionid)) {
            Log::info('fetchUser fail openid=' . $wx_user->id);
            return null;
        }
        $user = User::where('union_id', $unionid)->first();
        $fu = $this->app->user->get($wx_user->id);
        if ($user) {

            // 更新头像和nickname
            $user->avatar = $original['headimgurl'];
            $user->nickname = $original['nickname'];
            $user->province = $original['province'];
            $user->city = $original['city'];
            $user->sex = $original['sex'];

            // 更新用户关注状态
            $subscribe = isset($fu['subscribe']) ? $fu['subscribe'] : 0;
            $user->subscribe = isset($fu['subscribe']) ? $fu['subscribe'] : 0;
            if ($subscribe != 0) {
                $user->subscribe_scene  = isset($fu['subscribe_scene']) ? $fu['subscribe_scene'] : '';
                $user->subscribe_time   = isset($fu['subscribe_time']) ? $fu['subscribe_time'] : '';
                $user->union_id         = isset($fu['unionid']) ? $fu['unionid'] : '';
                $user->qr_scene         = isset($fu['qr_scene']) ? $fu['qr_scene'] : '';
                $user->qr_scene_str     = isset($fu['qr_scene_str']) ? $fu['qr_scene_str'] : '';
                $user->save();
            } else {
                $user = '';
            }
        } else {
            $user = new User();
            $user->mp_open_id   = $wx_user->id;
            $user->nickname     = isset($fu['nickname']) ? $fu['nickname'] : '';
            $user->sex          = isset($fu['sex']) ? $fu['sex'] : '';
            $user->avatar       = isset($fu['headimgurl']) ? $fu['headimgurl'] : '';
            $user->subscribe    = isset($fu['subscribe']) ? $fu['subscribe'] : '';
            $user->subscribe_scene  = isset($fu['subscribe_scene']) ? $fu['subscribe_scene'] : '';
            $user->subscribe_time   = isset($fu['subscribe_time']) ? $fu['subscribe_time'] : '';
            $user->union_id         = isset($fu['unionid']) ? $fu['unionid'] : '';
            $user->province         = isset($fu['province']) ? $fu['province'] : '';
            $user->city             = isset($fu['city']) ? $fu['city'] : '';
            $user->qr_scene         = isset($fu['qr_scene']) ? $fu['qr_scene'] : '';
            $user->qr_scene_str     = isset($fu['qr_scene_str']) ? $fu['qr_scene_str'] : '';
            //            if (empty(!isset($fu['unionid']))) {
            //                return null;
            //            }
            if ($user->subscribe) {
                $user->save();
            } else {
                $user = '';
            }
        }

        return $user;
    }




    // 搜索
    public function searchBookByStr()
    {
        $q = request('q');
        $page = request('page') ?: 1;
        if (is_null($q)) return [];

        $wx_user = session('wechat.oauth_user.default');        // 拿到授权用户资料
        Log::info("search book by str user: " . json_encode($wx_user));
        $user = $this->fetchUser($wx_user);
        UpdateUserRecommend::dispatch($user->id)->delay(now()->addSecond());

        // 折扣  1升序  2降序
        $discount = intval( request('discount') );
        $args_discount = [1, 2];
        if ($discount && !in_array($discount, $args_discount)) {
            return response()->json([
                'code' => 500,
                'msg' => '折扣参数错误'
            ]);
        }


        // 评分  1升序   2降序
        $rating = intval( request('rating') );
        $args_rating = [1, 2];
        if ($rating && !in_array($rating, $args_rating)) {
            return response()->json([
                'code' => 500,
                'msg' => '评分参数错误'
            ]);
        }

        // 价格
        $low = 0;
        $high = 0;
        $price = trim( request('price') );
        $prices = [];
        if ($price && strpos($price, '-')!==false) {
            $prices = explode('-', $price);
            if (count($prices) == 2) {
                if ($prices[0]==0 && $prices[1]==0) {
                    return response()->json([
                       'code' => 500,
                       'msg' => '价格参数错误'
                    ]);
                } else if ($prices[0]==0) {

                    $high = $prices[1];
                } else if ($prices[1]==0) {

                    $high = 1000;
                } else {

                    $low = $prices[0];
                    $high = $prices[1];
                }

                if ($low < $high) {
                    $low = floatval( $low );
                    $high = floatval( $high );
                } else {
                    return response()->json([
                       'code' => 500,
                       'msg' => '价格区间错误'
                    ]);
                }
            }

        }

        // 品相  全新100  上好80  中等60
        $level = trim(request('level'));
        $args_level = [100, 80, 60];
        if ($level && !in_array($level, $args_level)) {
            return response()->json([
                'code' => 500,
                'msg' => '品相参数错误'
            ]);
        }

        // 搜索历史
        $user_search = UserSearchHistory::where('user_id', $user->id)
            ->where('q', $q)
            ->first();

        $top = UserSearchHistory::where('q', $q)
            ->orderBy('start', 'desc')
            ->first();

        $ids = Book::select('id', 'subjectid')
            ->where('name', 'like', $q . '%')
            ->orWhere('author', 'like', $q . '%')
            ->orWhere('category', 'like', $q . '%')
            ->orWhere('group1', 'like', $q . '%')
            ->orWhere('group2', 'like', $q . '%')
            ->orWhere('group3', 'like', $q . '%')
            ->where('sale_sku_count', '>', 0)
            ->take(50)
            ->get();

        if (!$user_search) {
            if ($top) {
                UserSearchHistory::create([
                    'q' => $q,
                    'book_ids' => join(',', $ids->pluck('id')->toArray()),
                    'subjectids' => join(',', $ids->pluck('subjectid')->toArray()),
                    'user_id' => $user->id,
                    'start' => $top->start,
                    'total' => $top->total
                ]);
            } else {
                UserSearchHistory::create([
                    'q' => $q,
                    'book_ids' => join(',', $ids->pluck('id')->toArray()),
                    'subjectids' => join(',', $ids->pluck('subjectid')->toArray()),
                    'user_id' => $user->id,
                ]);
            }
        } else {
            if ($top) {
                $user_search->update([
                    'search_count' => $user_search->search_count + 1,
                    'book_ids' => join(',', $ids->pluck('id')->toArray()),
                    'subjectids' => join(',', $ids->pluck('subjectid')->toArray()),
                    'start' => $top->start,
                    'total' => $top->total
                ]);
            } else {
                $user_search->update([
                    'book_ids' => join(',', $ids->pluck('id')->toArray()),
                    'subjectids' => join(',', $ids->pluck('subjectid')->toArray()),
                    'search_count' => $user_search->search_count + 1,
                ]);
            }
        }

        // 搜索
        // $discount
        // $rating rating_num


        // $price
        // $level
        if ($price && $level) {
            /**
             *
             *
             *
             * 价格 品相
             *
             *
             *
             */
            // 价格

            // 品相
            $skus_level = 'for_sale_skus_level_100';
            if ($level == 80) {
                $skus_level = 'for_sale_skus_level_80';
            }

            if ($level == 60) {
                $skus_level = 'for_sale_skus_level_60';
            }

            // 无排序
            if ($rating==0 && $discount==0) {

                    $booksTmp = Book::select('id', 'isbn', 'name', 'author', 'rating_num', 'cover_replace', 'category', 'price', 'sale_discount', 'sale_sku_count')
                        ->with($skus_level)
                        ->where('sale_sku_count', '>', 0)
                        ->whereBetween('sale_discount_price', [$low, $high])
                        ->where(function ($query) use ($q) {
                            $query->where('name', 'like', $q . '%')
                                ->orWhere('author', 'like', $q . '%')
                                ->orWhere('translator', 'like', $q . '%')
                                ->orWhere('isbn', 'like', $q . '%')
                                ->orWhere('category', 'like', $q . '%')
                                ->orWhere('subtitle', 'like', $q . '%')
                                ->orWhere('original_name', 'like', $q . '%')
                                ->orWhere('press', 'like', $q . '%')
                                ->orWhere('group1', 'like', $q . '%')
                                ->orWhere('group2', 'like', $q . '%')
                                ->orWhere('group3', 'like', $q . '%');

                        })
                        ->orderBy('sale_sku_count', 'desc')
                        ->orderBy('all_sku_count', 'desc')
                        ->paginate(40);

            } else {
                if ($rating == 1) {
                    $order_column = 'rating_num';
                    $orderby = 'asc';
                } else if ($rating == 2) {
                    $order_column = 'rating_num';
                    $orderby = 'desc';
                } else if ($discount == 1) {
                    $order_column = 'sale_discount';
                    $orderby = 'asc';
                } else if ($discount == 2) {
                    $order_column = 'sale_discount';
                    $orderby = 'desc';
                }

                $booksTmp = Book::select('id', 'isbn', 'name', 'author', 'rating_num', 'cover_replace', 'category', 'price', 'sale_discount', 'sale_sku_count')
                    ->with($skus_level)
                    ->where('sale_sku_count', '>', 0)
                    ->whereBetween('sale_discount_price', [$low, $high])
                    ->where(function ($query) use ($q) {
                        $query->where('name', 'like', $q . '%')
                            ->orWhere('author', 'like', $q . '%')
                            ->orWhere('translator', 'like', $q . '%')
                            ->orWhere('isbn', 'like', $q . '%')
                            ->orWhere('category', 'like', $q . '%')
                            ->orWhere('subtitle', 'like', $q . '%')
                            ->orWhere('original_name', 'like', $q . '%')
                            ->orWhere('press', 'like', $q . '%')
                            ->orWhere('group1', 'like', $q . '%')
                            ->orWhere('group2', 'like', $q . '%')
                            ->orWhere('group3', 'like', $q . '%');

                    })
                    ->orderBy($order_column, $orderby)
                    ->orderBy('sale_sku_count', 'desc')
                    ->orderBy('all_sku_count', 'desc')
                    ->paginate(40);

            }



            $books = $data = [];
            if (count($booksTmp->items()) > 0) {
                foreach ($booksTmp->items() as $b) {
                    if ($level == 100) {
                        $b->for_sale_skus = $b->for_sale_skus_level_100;
                    } else if ($level == 80){
                        $b->for_sale_skus = $b->for_sale_skus_level_80;
                    } else if ($level == 60){
                        $b->for_sale_skus = $b->for_sale_skus_level_60;
                    }

                    if (count($b->for_sale_skus) > 0) {
                        $data[] = $b;
                    }


                }

                $books['data'] = $data;
                $books['total'] = $booksTmp->total();
                $books['next_page_url'] = $booksTmp->nextPageUrl();
                $books['last_page'] = $booksTmp->lastPage();


                $bookSkus = $books;
            } else {

                $bookSkus = $booksTmp;
            }


        } else if ($price) {
            /**
             *
             *
             *
             * 价格
             *
             *
             *
             */

            // 不排序
            $skus_level = 'for_sale_skus';
            if ($rating==0 && $discount==0) {
                $booksTmp = Book::select('id', 'isbn', 'name', 'author', 'rating_num', 'cover_replace', 'category', 'price', 'sale_discount', 'sale_sku_count')
                    ->with($skus_level)
                    ->where('sale_sku_count', '>', 0)
                    ->whereBetween('sale_discount_price', [$low, $high])
                    ->where(function ($query) use ($q) {
                        $query->where('name', 'like', $q . '%')
                            ->orWhere('author', 'like', $q . '%')
                            ->orWhere('translator', 'like', $q . '%')
                            ->orWhere('isbn', 'like', $q . '%')
                            ->orWhere('category', 'like', $q . '%')
                            ->orWhere('subtitle', 'like', $q . '%')
                            ->orWhere('original_name', 'like', $q . '%')
                            ->orWhere('press', 'like', $q . '%')
                            ->orWhere('group1', 'like', $q . '%')
                            ->orWhere('group2', 'like', $q . '%')
                            ->orWhere('group3', 'like', $q . '%');

                    })
                    ->orderBy('sale_sku_count', 'desc')
                    ->orderBy('all_sku_count', 'desc')
                    ->paginate(40);
            } else {
                // 排序
                if ($rating == 1) {
                    $order_column = 'rating_num';
                    $orderby = 'asc';
                } else if ($rating == 2) {
                    $order_column = 'rating_num';
                    $orderby = 'desc';
                } else if ($discount == 1) {
                    $order_column = 'sale_discount';
                    $orderby = 'asc';
                } else if ($discount == 2) {
                    $order_column = 'sale_discount';
                    $orderby = 'desc';
                }


                $booksTmp = Book::select('id', 'isbn', 'name', 'author', 'rating_num', 'cover_replace', 'category', 'price', 'sale_discount', 'sale_sku_count')
                    ->with($skus_level)
                    ->where('sale_sku_count', '>', 0)
                    ->whereBetween('sale_discount_price', [$low, $high])
                    ->where(function ($query) use ($q) {
                        $query->where('name', 'like', $q . '%')
                            ->orWhere('author', 'like', $q . '%')
                            ->orWhere('translator', 'like', $q . '%')
                            ->orWhere('isbn', 'like', $q . '%')
                            ->orWhere('category', 'like', $q . '%')
                            ->orWhere('subtitle', 'like', $q . '%')
                            ->orWhere('original_name', 'like', $q . '%')
                            ->orWhere('press', 'like', $q . '%')
                            ->orWhere('group1', 'like', $q . '%')
                            ->orWhere('group2', 'like', $q . '%')
                            ->orWhere('group3', 'like', $q . '%');

                    })
                    ->orderBy($order_column, $orderby)
                    ->orderBy('sale_sku_count', 'desc')
                    ->orderBy('all_sku_count', 'desc')
                    ->paginate(40);

            }


            if (count($booksTmp->items()) > 0) {
                foreach ($booksTmp->items() as $b) {
                    // 只显示有库存的数据
                    if (count($b->for_sale_skus) > 0) {
                        $data[] = $b;
                    }

                }

                $books['prices'] = $prices;
                $books['data'] = $data;
                $books['total'] = $booksTmp->total();
                $books['next_page_url'] = $booksTmp->nextPageUrl();
                $books['last_page'] = $booksTmp->lastPage();

                $bookSkus = $books;
            } else {
                $bookSkus = $booksTmp;
            }

        } else if ($level) {
            /**
             *
             *
             *
             * 品相
             *
             *
             *
             */
            $skus_level = 'for_sale_skus_level_100';
            if ($level == 80) {
                $skus_level = 'for_sale_skus_level_80';
            }

            if ($level == 60) {
                $skus_level = 'for_sale_skus_level_60';
            }


            // 品相 无排序
            if ($rating==0 && $discount==0) {

                $booksTmp = Book::select('id', 'isbn', 'name', 'author', 'rating_num', 'cover_replace', 'category', 'price', 'sale_discount', 'sale_sku_count')
                    ->with($skus_level)
                    ->where('sale_sku_count', '>', 0)
                    ->where(function ($query) use ($q) {
                        $query->where('name', 'like', $q . '%')
                            ->orWhere('author', 'like', $q . '%')
                            ->orWhere('translator', 'like', $q . '%')
                            ->orWhere('isbn', 'like', $q . '%')
                            ->orWhere('category', 'like', $q . '%')
                            ->orWhere('subtitle', 'like', $q . '%')
                            ->orWhere('original_name', 'like', $q . '%')
                            ->orWhere('press', 'like', $q . '%')
                            ->orWhere('group1', 'like', $q . '%')
                            ->orWhere('group2', 'like', $q . '%')
                            ->orWhere('group3', 'like', $q . '%');

                    })
                    ->orderBy('sale_sku_count', 'desc')
                    ->orderBy('all_sku_count', 'desc')
                    ->paginate(40);


            } else {
                if ($rating == 1) {
                    // 品相   豆瓣评分
                    $order_column = 'rating_num';
                    $orderby = 'asc';
                } else if ($rating == 2) {
                    $order_column = 'rating_num';
                    $orderby = 'desc';
                } else if ($discount == 1) {
                    // 品相   折扣
                    $order_column = 'sale_discount';
                    $orderby = 'asc';
                } else {
                    $order_column = 'sale_discount';
                    $orderby = 'desc';
                }


                $booksTmp = Book::select('id', 'isbn', 'name', 'author', 'rating_num', 'cover_replace', 'category', 'price', 'sale_discount', 'sale_sku_count')
                    ->with($skus_level)
                    ->where('sale_sku_count', '>', 0)
                    ->where(function ($query) use ($q) {
                        $query->where('name', 'like', $q . '%')
                            ->orWhere('author', 'like', $q . '%')
                            ->orWhere('translator', 'like', $q . '%')
                            ->orWhere('isbn', 'like', $q . '%')
                            ->orWhere('category', 'like', $q . '%')
                            ->orWhere('subtitle', 'like', $q . '%')
                            ->orWhere('original_name', 'like', $q . '%')
                            ->orWhere('press', 'like', $q . '%')
                            ->orWhere('group1', 'like', $q . '%')
                            ->orWhere('group2', 'like', $q . '%')
                            ->orWhere('group3', 'like', $q . '%');

                    })
                    ->orderBy($order_column, $orderby)
                    ->orderBy('sale_sku_count', 'desc')
                    ->orderBy('all_sku_count', 'desc')
                    ->paginate(40);
            }


            $books = $data = [];
            if (count($booksTmp->items()) > 0) {
                foreach ($booksTmp->items() as $b) {
                    if ($level == 100) {
                        $b->for_sale_skus = $b->for_sale_skus_level_100;
                    } else if ($level == 80){
                        $b->for_sale_skus = $b->for_sale_skus_level_80;
                    } else if ($level == 60){
                        $b->for_sale_skus = $b->for_sale_skus_level_60;
                    }

                    // 只显示有库存的数据
                    if(count($b->for_sale_skus) > 0) {
                        $data[] = $b;
                    }

                }

                $books['data'] = $data;
                $books['total'] = $booksTmp->total();
                $books['next_page_url'] = $booksTmp->nextPageUrl();
                $books['last_page'] = $booksTmp->lastPage();

            }

            $bookSkus = $books;

        } else {


            /**
             *
             *
             *
             * 无筛选      折扣 从低到高1  1-9折
             *
             *
             *
             */

            // 默认搜索
            if ($rating==0 && $discount==0) {
                $books = Book::select('id', 'isbn', 'name', 'author', 'rating_num', 'cover_replace', 'category', 'price', 'sale_discount', 'sale_sku_count')
                    ->with('for_sale_skus')
                    ->where(function ($query) use ($q) {
                        $query->where('name', 'like', $q . '%')
                            ->orWhere('author', 'like', $q . '%')
                            ->orWhere('translator', 'like', $q . '%')
                            ->orWhere('isbn', 'like', $q . '%')
                            ->orWhere('category', 'like', $q . '%')
                            ->orWhere('subtitle', 'like', $q . '%')
                            ->orWhere('original_name', 'like', $q . '%')
                            ->orWhere('press', 'like', $q . '%')
                            ->orWhere('group1', 'like', $q . '%')
                            ->orWhere('group2', 'like', $q . '%')
                            ->orWhere('group3', 'like', $q . '%');
                    })
                    ->orderByDesc('sale_sku_count')
                    ->orderByDesc('all_sku_count')
                    ->paginate(40);

                $bookSkus = $books;
            } else {


                if ($discount == 1) {
                    // 折扣
                    $order_column = 'sale_discount';
                    $orderby = 'asc';
                } else if ($discount == 2) {
                    $order_column = 'sale_discount';
                    $orderby = 'desc';
                } else if ($rating == 1) {
                    // 豆瓣评分
                    $order_column = 'rating_num';
                    $orderby = 'asc';
                } else {
                    $order_column = 'rating_num';
                    $orderby = 'desc';
                }


                $booksTmp = Book::select('id', 'isbn', 'name', 'author', 'rating_num', 'cover_replace', 'category', 'price', 'sale_discount', 'sale_sku_count')
                    ->with('for_sale_skus')
                    ->where('sale_sku_count', '>', 0)
                    ->where(function ($query) use ($q) {
                        $query->where('name', 'like', $q . '%')
                            ->orWhere('author', 'like', $q . '%')
                            ->orWhere('translator', 'like', $q . '%')
                            ->orWhere('isbn', 'like', $q . '%')
                            ->orWhere('category', 'like', $q . '%')
                            ->orWhere('subtitle', 'like', $q . '%')
                            ->orWhere('original_name', 'like', $q . '%')
                            ->orWhere('press', 'like', $q . '%')
                            ->orWhere('group1', 'like', $q . '%')
                            ->orWhere('group2', 'like', $q . '%')
                            ->orWhere('group3', 'like', $q . '%');
                    })
                    ->orderBy($order_column, $orderby)
                    ->orderBy('sale_sku_count', 'desc')
                    ->orderBy('all_sku_count', 'desc')
                    ->paginate(40);



                $books = $data = [];
                if (count($booksTmp->items()) > 0) {
                    foreach ($booksTmp->items() as $b) {
                        // 只显示有库存的数据
                        if(count($b->for_sale_skus) > 0) {
                            $data[] = $b;
                        }

                    }

                    $books['data'] = $data;
                    $books['total'] = $booksTmp->total();
                    $books['next_page_url'] = $booksTmp->nextPageUrl();
                    $books['last_page'] = $booksTmp->lastPage();

                }

                $bookSkus = $books;
            }



        }

        return $bookSkus;
    }

    // 热搜关键词
    public function getTopQ()
    {
        $q = Cache::remember('top_q', 1, function() {
            return DB::table('user_search_histories')
                ->select('q', DB::raw('count(*) as count'))
                ->groupBy('q')
                ->orderBy('count', 'desc')
                ->take('15')
                ->get();
        });


        $ret = collect($q)->pluck('q');

        return $ret;
    }

    // 已有未使用的优惠券
    // user 表的 email 字段   0无新优惠券    1有新优惠券
    public function UpdateCouponTip() {
        $wx_user = session('wechat.oauth_user.default');        // 拿到授权用户资料
        $user = $this->fetchUser($wx_user);

        $user->email = 0;
        $user->save();

        $user = $user = $this->fetchUser($wx_user);
        return response()->json([
            'code'  => 0,
            'msg'   => '新优惠券',
            'user' => $user
        ]);
    }



    // 8 大分类列表页
    public function getCategoryBooks()
    {
        $page = request('page') ?: 1;
        $cate = request('cate') ?: '';
        $tag = request('tag') ?: '';
        $tags_arr = [
            '文学酒'  => ["中国文学", "古典文学", "外国文学", "日本文学", "青春文学",
                "诗词世界", "散文·随笔", "纪实文学", "传记文学", "悬疑·推理", "科幻·奇幻"],
            '艺术盐'  => ["电影·摄影", "艺术·设计", "书法·绘画", "音乐·戏剧", "建筑·居住"],
            '生活家'  => ["时尚·化妆", "旅游·地理", "美食·健康", "运动·健身", "家居·宠物", "手工·工艺"],
            '知识面'  => ["读点历史", "懂点政治", "了解经济", "管理学", "军事·战争",
                "社会·人类学", "哲学·宗教", "科普·涨知识", "国学典籍"],
            '成长树'  => ["母婴育儿", "绘本故事", "儿童文学"],
            '必杀技'  => ["心理学", "学会沟通", "技能提升", "职业进阶", "自我管理",
                "理财知识", "外语学习", "语言·工具", "爱情婚姻"],
            '工作狂'  => ["财务会计", "新闻传播", "市场营销", "投资管理", "法律法规", "广告文案"],
            '互联网'  => ["科技·互联网", "产品·运营", "开发·编程", "交互设计"],
            '创业营'  => ["创业·商业", "科技·未来", "企业家", "管理学"],
            '其他'    => ["豆瓣8.5"]
        ];

        $tag_keys = array_keys($tags_arr);
        if (!in_array($cate, $tag_keys)) {
            return response()->json([
                'status'    => false,
                'message'   => '分类不存在',
                'tag_keys'  => $tag_keys,
                'cate'      => $cate
            ]);
        }

        $tags = $tags_arr[$cate];




        // 折扣  1升序  2降序
        $discount = intval( request('discount') );
        $args_discount = [1, 2];
        if ($discount && !in_array($discount, $args_discount)) {
            return response()->json([
                'code' => 500,
                'msg' => '折扣参数错误'
            ]);
        }


        // 评分  1升序   2降序
        $rating = intval( request('rating') );
        $args_rating = [1, 2];
        if ($rating && !in_array($rating, $args_rating)) {
            return response()->json([
                'code' => 500,
                'msg' => '评分参数错误'
            ]);
        }

        // 价格
        $low = 0;
        $high = 0;
        $price = trim( request('price') );
        $prices = [];
        if ($price && strpos($price, '-')!==false) {
            $prices = explode('-', $price);
            if (count($prices) == 2) {
                if ($prices[0]==0 && $prices[1]==0) {
                    return response()->json([
                        'code' => 500,
                        'msg' => '价格参数错误'
                    ]);
                } else if ($prices[0]==0) {

                    $high = $prices[1];
                } else if ($prices[1]==0) {

                    $high = 1000;
                } else {

                    $low = $prices[0];
                    $high = $prices[1];
                }

                if ($low < $high) {
                    $low = floatval( $low );
                    $high = floatval( $high );
                } else {
                    return response()->json([
                        'code' => 500,
                        'msg' => '价格区间错误'
                    ]);
                }
            }

        }

        // 品相  全新100  上好80  中等60
        $level = trim(request('level'));
        $args_level = [100, 80, 60];
        if ($level && !in_array($level, $args_level)) {
            return response()->json([
                'code' => 500,
                'msg' => '品相参数错误'
            ]);
        }


        // 有排序
        if ($discount == 1) {
            // 折扣
            $order_column = 'sale_discount';
            $orderby = 'asc';
        } else if ($discount == 2) {
            $order_column = 'sale_discount';
            $orderby = 'desc';
        } else if ($rating == 1) {
            // 豆瓣评分
            $order_column = 'rating_num';
            $orderby = 'asc';
        } else {
            $order_column = 'rating_num';
            $orderby = 'desc';
        }


        $skus_level = 'for_sale_skus_level_100';
        if ($level == 80) {
            $skus_level = 'for_sale_skus_level_80';
        }

        if ($level == 60) {
            $skus_level = 'for_sale_skus_level_60';
        }


        /**
         *
         * 新上架
         *
         */
        if ($tag == '新上架') {

            if ($price && $level) {
                /**
                 *
                 * 价格
                 * 品相
                 *
                 */
                if ($rating==0 && $discount==0) {
                    // 无排序
                    //$bookSkus = Cache::remember($cate . '_' . $tag . '_page_' . $page, 5, function () use ($tags) {
                    $booksTmp = Book::select('id', 'isbn', 'name', 'subtitle', 'author', 'rating_num', 'cover_replace',
                        'press', 'publish_year', 'binding', 'category', 'price', 'sale_discount',
                        'type', 'group1', 'group2', 'group3')
                        ->with($skus_level)
                        //->with('for_sale_skus.user')
                        //->with('for_sale_skus.book_version')
                        ->with($skus_level . '.user')
                        ->with($skus_level . '.book_version')
                        ->withCount('all_sold_sku')
                        ->where('sale_sku_count', '>', 0)
                        ->where(function($q) use ($tags, $level, $low, $high) {
                            // 品相
                            //$q->where('price', '>=', $low)
                            //  ->where('price', '<=', $high)
                            //  ->where(function($qTags) use ($tags) {
                                  // 标签
                                  $q->whereIn('group1', $tags)
                                      ->orWhereIn('group2', $tags)
                                      ->orWhereIn('group3', $tags);
                            //  });

                        })
                        ->orderBy('updated_at', 'desc')
                        ->paginate(100);
                    //});

                } else {

                    //$bookSkus = Cache::remember($cate . '_' . $tag . '_page_' . $page, 5, function () use ($tags) {
                    $booksTmp = Book::select('id', 'isbn', 'name', 'subtitle', 'author', 'rating_num', 'cover_replace',
                        'press', 'publish_year', 'binding', 'category', 'price', 'sale_discount',
                        'type', 'group1', 'group2', 'group3')
                        //->with('for_sale_skus.user')
                        //->with('for_sale_skus.book_version')
                        ->with($skus_level . '.user')
                        ->with($skus_level . '.book_version')
                        ->withCount('all_sold_sku')
                        ->where('sale_sku_count', '>', 0)
                        ->where(function($q) use ($tags, $level, $low, $high) {
                            //$q->where('price', '>=', $low)
                            //    ->where('price', '<=', $high)
                            //    ->where(function($qTags) use ($tags) {
                                    $q->whereIn('group1', $tags)
                                        ->orWhereIn('group2', $tags)
                                        ->orWhereIn('group3', $tags);
                             //   });
                        })
                        ->orderBy($order_column, $orderby)
                        ->orderBy('updated_at', 'desc')
                        ->paginate(100);
                    //});


                }


                $books = $data = [];
                if (count($booksTmp->items()) > 0) {
                    foreach ($booksTmp->items() as $b) {
                        // 只显示有库存的数据
                        if ($level == 100) {
                            $b->for_sale_skus = $b->for_sale_skus_level_100;
                        } else if ($level == 80) {
                            $b->for_sale_skus = $b->for_sale_skus_level_80;
                        } else if ($level == 60) {
                            $b->for_sale_skus = $b->for_sale_skus_level_60;
                        }

                        $bk = $b->toArray();
                        // 只显示有库存的数据
                        if (count($b['for_sale_skus']) > 0) {
                            $for_sale_skus = [];
                            // 只存符合价格的 sku
                            foreach ($b['for_sale_skus'] as $sku) {

                                if ($sku['price'] >= $low && $sku['price'] <= $high) {
                                    $for_sale_skus[] = $sku;
                                }
                            }

                            if (count($for_sale_skus) > 0) {
                                $bk['for_sale_skus'] = $for_sale_skus;
                                $data[] = $bk;
                            }

                        }

                    }

                    $books['data']      = $data;
                    $books['total']     = $booksTmp->total();
                    $books['next_page_url'] = $booksTmp->nextPageUrl();
                    $books['last_page']     = $booksTmp->lastPage();

                }

                $bookSkus = $books;

            } else if ($price) {

                /**
                 * 价格
                 */
                $skus_level = 'for_sale_skus';


                if ($rating==0 && $discount==0) {
                    // 无排序
                    //$bookSkus = Cache::remember($cate . '_' . $tag . '_page_' . $page, 5, function () use ($tags) {
                    $booksTmp = Book::select('id', 'isbn', 'name', 'subtitle', 'author', 'rating_num', 'cover_replace',
                        'press', 'publish_year', 'binding', 'category', 'price', 'sale_discount',
                        'type', 'group1', 'group2', 'group3')
                        ->with($skus_level)
                        //->with('for_sale_skus.user')
                        //->with('for_sale_skus.book_version')
                        ->with($skus_level . '.user')
                        ->with($skus_level . '.book_version')
                        ->withCount('all_sold_sku')
                        ->where('sale_sku_count', '>', 0)

                        ->where(function($q) use ($tags, $low, $high) {
                            // 价格
                            //$q->where('price', '>=', $low)
                            //    ->where('price', '<=', $high)
                            //    ->where(function($qTags) use ($tags) {
                                    // 标签
                                    $q->whereIn('group1', $tags)
                                        ->orWhereIn('group2', $tags)
                                        ->orWhereIn('group3', $tags);
                            //    });
                        })
                        ->orderBy('updated_at', 'desc')
                        ->paginate(100);
                    //});

                } else {

                    //$bookSkus = Cache::remember($cate . '_' . $tag . '_page_' . $page, 5, function () use ($tags) {
                    $booksTmp = Book::select('id', 'isbn', 'name', 'subtitle', 'author', 'rating_num', 'cover_replace',
                        'press', 'publish_year', 'binding', 'category', 'price', 'sale_discount',
                        'type', 'group1', 'group2', 'group3')
                        //->with('for_sale_skus.user')
                        //->with('for_sale_skus.book_version')
                        ->with($skus_level . '.user')
                        ->with($skus_level . '.book_version')
                        ->withCount('all_sold_sku')
                        ->where('sale_sku_count', '>', 0)
                        ->where(function($q) use ($tags, $low, $high) {
                            // 价格
                            //$q->where('price', '>=', $low)
                            //    ->where('price', '<=', $high)
                            //    ->where(function($qTags) use ($tags) {
                                    // 标签
                                    $q->whereIn('group1', $tags)
                                        ->orWhereIn('group2', $tags)
                                        ->orWhereIn('group3', $tags);
                            //    });
                        })
                        ->orderBy($order_column, $orderby)
                        ->orderBy('updated_at', 'desc')
                        ->paginate(100);
                    //});


                }

                $books = $data = [];
                if (count($booksTmp->items()) > 0) {
                    foreach ($booksTmp->items() as $b) {
                        $bk = $b->toArray();
                        // 只显示有库存的数据
                        if (count($b['for_sale_skus']) > 0) {
                            $for_sale_skus = [];
                            // 只存符合价格的 sku
                            foreach ($b['for_sale_skus'] as $sku) {

                                if ($sku['price'] >= $low && $sku['price'] <= $high) {
                                    $for_sale_skus[] = $sku;
                                }
                            }

                            if (count($for_sale_skus) > 0) {
                                $bk['for_sale_skus'] = $for_sale_skus;
                                $data[] = $bk;
                            }

                        }

                    }

                    $books['data']      = $data;
                    $books['total']     = $booksTmp->total();
                    $books['next_page_url'] = $booksTmp->nextPageUrl();
                    $books['last_page']     = $booksTmp->lastPage();

                }

                $bookSkus = $books;



            } else if ($level) {
                /**
                 * 品相
                 */
                if ($rating==0 && $discount==0) {
                    // 无排序
                    //$bookSkus = Cache::remember($cate . '_' . $tag . '_page_' . $page, 5, function () use ($tags) {
                    $booksTmp = Book::select('id', 'isbn', 'name', 'subtitle', 'author', 'rating_num', 'cover_replace',
                        'press', 'publish_year', 'binding', 'category', 'price', 'sale_discount',
                        'type', 'group1', 'group2', 'group3')
                        ->with($skus_level)
                        //->with('for_sale_skus.user')
                        //->with('for_sale_skus.book_version')
                        ->with($skus_level . '.user')
                        ->with($skus_level . '.book_version')
                        ->withCount('all_sold_sku')
                        ->where('sale_sku_count', '>', 0)
                        ->where(function($q) use ($tags) {
                            $q->whereIn('group1', $tags)
                                ->orWhereIn('group2', $tags)
                                ->orWhereIn('group3', $tags);
                        })
                        ->orderBy('updated_at', 'desc')
                        ->paginate(100);
                    //});

                } else {

                    //$bookSkus = Cache::remember($cate . '_' . $tag . '_page_' . $page, 5, function () use ($tags) {
                    $booksTmp = Book::select('id', 'isbn', 'name', 'subtitle', 'author', 'rating_num', 'cover_replace',
                        'press', 'publish_year', 'binding', 'category', 'price', 'sale_discount',
                        'type', 'group1', 'group2', 'group3')
                        //->with('for_sale_skus.user')
                        //->with('for_sale_skus.book_version')
                        ->with($skus_level . '.user')
                        ->with($skus_level . '.book_version')
                        ->withCount('all_sold_sku')
                        ->where('sale_sku_count', '>', 0)
                        ->where(function($q) use ($tags) {
                            $q->whereIn('group1', $tags)
                                ->orWhereIn('group2', $tags)
                                ->orWhereIn('group3', $tags);
                        })
                        ->orderBy($order_column, $orderby)
                        ->orderBy('updated_at', 'desc')
                        ->paginate(100);
                    //});


                }

                $books = $data = [];
                if (count($booksTmp->items()) > 0) {
                    foreach ($booksTmp->items() as $b) {
                        // 只显示有库存的数据
                        if ($level == 100) {
                            $b->for_sale_skus = $b->for_sale_skus_level_100;
                        } else if ($level == 80) {
                            $b->for_sale_skus = $b->for_sale_skus_level_80;
                        } else if ($level == 60) {
                            $b->for_sale_skus = $b->for_sale_skus_level_60;
                        }

                        if (count($b->for_sale_skus) > 0) {
                            $data[] = $b;
                        }

                    }

                    $books['data']      = $data;
                    $books['total']     = $booksTmp->total();
                    $books['next_page_url'] = $booksTmp->nextPageUrl();
                    $books['last_page']     = $booksTmp->lastPage();
                    $books['order'] = [$order_column, $orderby];

                }

                $bookSkus = $books;


            } else {

                if ($rating==0 && $discount==0) {
                    // 无排序
                    //$bookSkus = Cache::remember($cate . '_' . $tag . '_page_' . $page, 5, function () use ($tags) {
                    $bookSkus = Book::select('id', 'isbn', 'name', 'subtitle', 'author', 'rating_num', 'cover_replace',
                        'press', 'publish_year', 'binding', 'category', 'price', 'sale_discount',
                        'type', 'group1', 'group2', 'group3')
                        ->with('for_sale_skus.user')
                        ->with('for_sale_skus.book_version')
                        ->withCount('all_sold_sku')
                        ->where('sale_sku_count', '>', 0)
                        ->where(function($q) use ($tags) {
                            $q->whereIn('group1', $tags)
                                ->orWhereIn('group2', $tags)
                                ->orWhereIn('group3', $tags);
                        })
                        ->orderBy('updated_at', 'desc')
                        ->paginate(30);
                    //});

                } else {

                    //$bookSkus = Cache::remember($cate . '_' . $tag . '_page_' . $page, 5, function () use ($tags) {
                    $booksTmp = Book::select('id', 'isbn', 'name', 'subtitle', 'author', 'rating_num', 'cover_replace',
                        'press', 'publish_year', 'binding', 'category', 'price', 'sale_discount',
                        'type', 'group1', 'group2', 'group3')
                        ->with('for_sale_skus.user')
                        ->with('for_sale_skus.book_version')
                        ->withCount('all_sold_sku')
                        ->where('sale_sku_count', '>', 0)
                        ->where(function($q) use ($tags) {
                            $q->whereIn('group1', $tags)
                                ->orWhereIn('group2', $tags)
                                ->orWhereIn('group3', $tags);
                        })
                        ->orderBy($order_column, $orderby)
                        ->orderBy('updated_at', 'desc')
                        ->paginate(30);
                    //});

                    $books = $data = [];
                    if (count($booksTmp->items()) > 0) {
                        foreach ($booksTmp->items() as $b) {
                            // 只显示有库存的数据
                            if(count($b->for_sale_skus) > 0) {
                                $data[] = $b;
                            }

                        }

                        $books['data']      = $data;
                        $books['total']     = $booksTmp->total();
                        $books['next_page_url'] = $booksTmp->nextPageUrl();
                        $books['last_page']     = $booksTmp->lastPage();
                        $books['order'] = [$order_column, $orderby];

                    }

                    $bookSkus = $books;

                }

            }

            // 返回接口数据
            return $bookSkus;

        } else if ($tag == '特价市集' or $tag == '豆瓣8.5') {
            /**
             *
             * 特价市集  价格<6
             * 豆瓣8.5+ 评分>8.5
             *
             */
            if ($tag == '特价市集') {
                $price = 'price';
                if ($high>6) {
                    $high = 6;
                }
                if ($high == 0) {
                    $high = 6;
                }

                //构造一个恒成立条件
                $colum = 'id';
                $operator = '>';
                $value = 0;
            } else {
                $colum = 'rating_num';
                $operator = '>';
                $value = 8.5;
            }
            
            if ($price && $level) {
                /**
                 *
                 * 价格
                 * 品相
                 *
                 */
                if ($rating==0 && $discount==0) {
                    // 无排序
                    //$bookSkus = Cache::remember($cate . '_' . $tag . '_page_' . $page, 5, function () use ($tags) {
                    $booksTmp = Book::select('id', 'isbn', 'name', 'subtitle', 'author', 'rating_num', 'cover_replace',
                        'press', 'publish_year', 'binding', 'category', 'price', 'sale_discount',
                        'type', 'group1', 'group2', 'group3')
                        ->with($skus_level)
                        //->with('for_sale_skus.user')
                        //->with('for_sale_skus.book_version')
                        ->with($skus_level . '.user')
                        ->with($skus_level . '.book_version')
                        ->withCount('all_sold_sku')
                        ->where('sale_sku_count', '>', 0)
                        ->where($colum, $operator, $value)
                        ->orderBy('updated_at', 'desc')
                        ->paginate(100);
                    //});

                } else {

                    //$bookSkus = Cache::remember($cate . '_' . $tag . '_page_' . $page, 5, function () use ($tags) {
                    $booksTmp = Book::select('id', 'isbn', 'name', 'subtitle', 'author', 'rating_num', 'cover_replace',
                        'press', 'publish_year', 'binding', 'category', 'price', 'sale_discount',
                        'type', 'group1', 'group2', 'group3')
                        //->with('for_sale_skus.user')
                        //->with('for_sale_skus.book_version')
                        ->with($skus_level . '.user')
                        ->with($skus_level . '.book_version')
                        ->withCount('all_sold_sku')
                        ->where('sale_sku_count', '>', 0)
                        ->where($colum, $operator, $value)
                        ->orderBy($order_column, $orderby)
                        ->orderBy('updated_at', 'desc')
                        ->paginate(100);
                    //});


                }


                $books = $data = [];
                if (count($booksTmp->items()) > 0) {
                    foreach ($booksTmp->items() as $b) {
                        // 只显示有库存的数据
                        if ($level == 100) {
                            $b->for_sale_skus = $b->for_sale_skus_level_100;
                        } else if ($level == 80) {
                            $b->for_sale_skus = $b->for_sale_skus_level_80;
                        } else if ($level == 60) {
                            $b->for_sale_skus = $b->for_sale_skus_level_60;
                        }

                        $bk = $b->toArray();
                        // 只显示有库存的数据
                        if (count($b['for_sale_skus']) > 0) {
                            $for_sale_skus = [];
                            // 只存符合价格的 sku
                            foreach ($b['for_sale_skus'] as $sku) {

                                if ($sku['price'] >= $low && $sku['price'] <= $high) {
                                    $for_sale_skus[] = $sku;
                                }
                            }

                            if (count($for_sale_skus) > 0) {
                                $bk['for_sale_skus'] = $for_sale_skus;
                                $data[] = $bk;
                            }

                        }

                    }

                    $books['data']      = $data;
                    $books['total']     = $booksTmp->total();
                    $books['next_page_url'] = $booksTmp->nextPageUrl();
                    $books['last_page']     = $booksTmp->lastPage();

                }

                $bookSkus = $books;

            } else if ($price) {

                /**
                 * 价格
                 */
                $skus_level = 'for_sale_skus';


                if ($rating==0 && $discount==0) {
                    // 无排序
                    //$bookSkus = Cache::remember($cate . '_' . $tag . '_page_' . $page, 5, function () use ($tags) {
                    $booksTmp = Book::select('id', 'isbn', 'name', 'subtitle', 'author', 'rating_num', 'cover_replace',
                        'press', 'publish_year', 'binding', 'category', 'price', 'sale_discount',
                        'type', 'group1', 'group2', 'group3')
                        ->with($skus_level)
                        //->with('for_sale_skus.user')
                        //->with('for_sale_skus.book_version')
                        ->with($skus_level . '.user')
                        ->with($skus_level . '.book_version')
                        ->withCount('all_sold_sku')
                        ->where('sale_sku_count', '>', 0)
                        ->where($colum, $operator, $value)
                        ->orderBy('updated_at', 'desc')
                        ->paginate(100);
                    //});

                } else {

                    //$bookSkus = Cache::remember($cate . '_' . $tag . '_page_' . $page, 5, function () use ($tags) {
                    $booksTmp = Book::select('id', 'isbn', 'name', 'subtitle', 'author', 'rating_num', 'cover_replace',
                        'press', 'publish_year', 'binding', 'category', 'price', 'sale_discount',
                        'type', 'group1', 'group2', 'group3')
                        //->with('for_sale_skus.user')
                        //->with('for_sale_skus.book_version')
                        ->with($skus_level . '.user')
                        ->with($skus_level . '.book_version')
                        ->withCount('all_sold_sku')
                        ->where('sale_sku_count', '>', 0)
                        ->where($colum, $operator, $value)
                        ->orderBy($order_column, $orderby)
                        ->orderBy('updated_at', 'desc')
                        ->paginate(100);
                    //});


                }

                $books = $data = [];
                if (count($booksTmp->items()) > 0) {
                    foreach ($booksTmp->items() as $b) {
                        $bk = $b->toArray();
                        // 只显示有库存的数据
                        if (count($b['for_sale_skus']) > 0) {
                            $for_sale_skus = [];
                            // 只存符合价格的 sku
                            foreach ($b['for_sale_skus'] as $sku) {

                                if ($sku['price'] >= $low && $sku['price'] <= $high) {
                                    $for_sale_skus[] = $sku;
                                }
                            }

                            if (count($for_sale_skus) > 0) {
                                $bk['for_sale_skus'] = $for_sale_skus;
                                $data[] = $bk;
                            }

                        }

                    }

                    $books['data']      = $data;
                    $books['total']     = $booksTmp->total();
                    $books['next_page_url'] = $booksTmp->nextPageUrl();
                    $books['last_page']     = $booksTmp->lastPage();

                }

                $bookSkus = $books;



            } else if ($level) {
                /**
                 * 品相
                 */
                if ($rating==0 && $discount==0) {
                    // 无排序
                    //$bookSkus = Cache::remember($cate . '_' . $tag . '_page_' . $page, 5, function () use ($tags) {
                    $booksTmp = Book::select('id', 'isbn', 'name', 'subtitle', 'author', 'rating_num', 'cover_replace',
                        'press', 'publish_year', 'binding', 'category', 'price', 'sale_discount',
                        'type', 'group1', 'group2', 'group3')
                        ->with($skus_level)
                        //->with('for_sale_skus.user')
                        //->with('for_sale_skus.book_version')
                        ->with($skus_level . '.user')
                        ->with($skus_level . '.book_version')
                        ->withCount('all_sold_sku')
                        ->where('sale_sku_count', '>', 0)
                        ->where($colum, $operator, $value)
                        ->orderBy('updated_at', 'desc')
                        ->paginate(100);
                    //});

                } else {

                    //$bookSkus = Cache::remember($cate . '_' . $tag . '_page_' . $page, 5, function () use ($tags) {
                    $booksTmp = Book::select('id', 'isbn', 'name', 'subtitle', 'author', 'rating_num', 'cover_replace',
                        'press', 'publish_year', 'binding', 'category', 'price', 'sale_discount',
                        'type', 'group1', 'group2', 'group3')
                        //->with('for_sale_skus.user')
                        //->with('for_sale_skus.book_version')
                        ->with($skus_level . '.user')
                        ->with($skus_level . '.book_version')
                        ->withCount('all_sold_sku')
                        ->where('sale_sku_count', '>', 0)
                        ->where($colum, $operator, $value)
                        ->orderBy($order_column, $orderby)
                        ->orderBy('updated_at', 'desc')
                        ->paginate(100);
                    //});


                }

                $books = $data = [];
                if (count($booksTmp->items()) > 0) {
                    foreach ($booksTmp->items() as $b) {
                        // 只显示有库存的数据
                        if ($level == 100) {
                            $b->for_sale_skus = $b->for_sale_skus_level_100;
                        } else if ($level == 80) {
                            $b->for_sale_skus = $b->for_sale_skus_level_80;
                        } else if ($level == 60) {
                            $b->for_sale_skus = $b->for_sale_skus_level_60;
                        }

                        if (count($b->for_sale_skus) > 0) {
                            $data[] = $b;
                        }

                    }

                    $books['data']      = $data;
                    $books['total']     = $booksTmp->total();
                    $books['next_page_url'] = $booksTmp->nextPageUrl();
                    $books['last_page']     = $booksTmp->lastPage();
                    $books['order'] = [$order_column, $orderby];

                }

                $bookSkus = $books;


            } else {

                if ($rating==0 && $discount==0) {
                    // 无排序
                    //$bookSkus = Cache::remember($cate . '_' . $tag . '_page_' . $page, 5, function () use ($tags) {
                    $bookSkus = Book::select('id', 'isbn', 'name', 'subtitle', 'author', 'rating_num', 'cover_replace',
                        'press', 'publish_year', 'binding', 'category', 'price', 'sale_discount',
                        'type', 'group1', 'group2', 'group3')
                        ->with('for_sale_skus.user')
                        ->with('for_sale_skus.book_version')
                        ->withCount('all_sold_sku')
                        ->where('sale_sku_count', '>', 0)
                        ->where($colum, $operator, $value)
                        ->orderBy('updated_at', 'desc')
                        ->paginate(30);
                    //});

                } else {

                    //$bookSkus = Cache::remember($cate . '_' . $tag . '_page_' . $page, 5, function () use ($tags) {
                    $booksTmp = Book::select('id', 'isbn', 'name', 'subtitle', 'author', 'rating_num', 'cover_replace',
                        'press', 'publish_year', 'binding', 'category', 'price', 'sale_discount',
                        'type', 'group1', 'group2', 'group3')
                        ->with('for_sale_skus.user')
                        ->with('for_sale_skus.book_version')
                        ->withCount('all_sold_sku')
                        ->where('sale_sku_count', '>', 0)
                        ->where($colum, $operator, $value)
                        ->orderBy($order_column, $orderby)
                        ->orderBy('updated_at', 'desc')
                        ->paginate(30);
                    //});

                    $books = $data = [];
                    if (count($booksTmp->items()) > 0) {
                        foreach ($booksTmp->items() as $b) {
                            // 只显示有库存的数据
                            if(count($b->for_sale_skus) > 0) {
                                $data[] = $b;
                            }

                        }

                        $books['data']      = $data;
                        $books['total']     = $booksTmp->total();
                        $books['next_page_url'] = $booksTmp->nextPageUrl();
                        $books['last_page']     = $booksTmp->lastPage();
                        $books['order'] = [$order_column, $orderby];

                    }

                    $bookSkus = $books;

                }

            }

            // 返回接口数据
            return $bookSkus;



        } else if (in_array($tag, $tags)) {
            /**
             *
             * 某个分类
             *
             */
            if ($price && $level) {
                /**
                 *
                 * 价格
                 * 品相
                 *
                 */
                if ($rating==0 && $discount==0) {
                    // 无排序
                    //$bookSkus = Cache::remember($cate . '_' . $tag . '_page_' . $page, 5, function () use ($tags) {
                    $booksTmp = Book::select('id', 'isbn', 'name', 'subtitle', 'author', 'rating_num', 'cover_replace',
                        'press', 'publish_year', 'binding', 'category', 'price', 'sale_discount',
                        'type', 'group1', 'group2', 'group3')
                        ->with($skus_level)
                        //->with('for_sale_skus.user')
                        //->with('for_sale_skus.book_version')
                        ->with($skus_level . '.user')
                        ->with($skus_level . '.book_version')
                        ->withCount('all_sold_sku')
                        ->where('sale_sku_count', '>', 0)
                        ->where(function($q) use ($tag, $level, $low, $high) {
                            // 价格
                            //$q->where('price', '>=', $low)
                            //   ->where('price', '<=', $high)
                            //   ->where(function($qTag) use($tag) {
                                    // 标签
                                    $q->where('group1', $tag)
                                        ->orWhere('group2', $tag)
                                        ->orWhere('group3', $tag);
                            //    });

                        })
                        ->orderBy('all_sold_sku_count', 'desc')
                        ->paginate(30);
                    //});

                } else {

                    //$bookSkus = Cache::remember($cate . '_' . $tag . '_page_' . $page, 5, function () use ($tags) {
                    $booksTmp = Book::select('id', 'isbn', 'name', 'subtitle', 'author', 'rating_num', 'cover_replace',
                        'press', 'publish_year', 'binding', 'category', 'price', 'sale_discount',
                        'type', 'group1', 'group2', 'group3')
                        //->with('for_sale_skus.user')
                        //->with('for_sale_skus.book_version')
                        ->with($skus_level . '.user')
                        ->with($skus_level . '.book_version')
                        ->withCount('all_sold_sku')
                        ->where('sale_sku_count', '>', 0)
                        ->where(function($q) use ($tag, $level, $low, $high) {
                            // 价格
                            //$q->where('price', '>=', $low)
                            //   ->where('price', '<=', $high)
                            //   ->where(function ($qTag) use ($tag) {
                                   // 标签
                                    $q->where('group1', $tag)
                                       ->orWhere('group2', $tag)
                                       ->orWhere('group3', $tag);
                              // });

                        })
                        ->orderBy($order_column, $orderby)
                        ->orderBy('all_sold_sku_count', 'desc')
                        ->paginate(100);
                    //});


                }

                $books = $data = [];
                if (count($booksTmp->items()) > 0) {
                    foreach ($booksTmp->items() as $b) {
                        if ($level == 100) {
                            $b->for_sale_skus = $b->for_sale_skus_level_100;
                        } else if ($level == 80) {
                            $b->for_sale_skus = $b->for_sale_skus_level_80;
                        } else if ($level == 60) {
                            $b->for_sale_skus = $b->for_sale_skus_level_60;
                        }

                        $bk = $b->toArray();
                        // 只显示有库存的数据
                        if (count($b['for_sale_skus']) > 0) {
                            $for_sale_skus = [];
                            // 只存符合价格的 sku
                            foreach ($b['for_sale_skus'] as $sku) {

                                if ($sku['price'] >= $low && $sku['price'] <= $high) {
                                    $for_sale_skus[] = $sku;
                                }
                            }

                            if (count($for_sale_skus) > 0) {
                                $bk['for_sale_skus'] = $for_sale_skus;
                                $data[] = $bk;
                            }

                        }

                    }

                    $books['data']      = $data;
                    $books['total']     = $booksTmp->total();
                    $books['next_page_url'] = $booksTmp->nextPageUrl();
                    $books['last_page']     = $booksTmp->lastPage();

                }

                $bookSkus = $books;

            } else if ($price) {
                /**
                 * 价格
                 */
                $skus_level = 'for_sale_skus';


                if ($rating==0 && $discount==0) {
                    // 无排序
                    //$bookSkus = Cache::remember($cate . '_' . $tag . '_page_' . $page, 5, function () use ($tags) {
                    $booksTmp = Book::select('id', 'isbn', 'name', 'subtitle', 'author', 'rating_num', 'cover_replace',
                        'press', 'publish_year', 'binding', 'category', 'price', 'sale_discount',
                        'type', 'group1', 'group2', 'group3')
                        ->with($skus_level)
                        //->with('for_sale_skus.user')
                        //->with('for_sale_skus.book_version')
                        ->with($skus_level . '.user')
                        ->with($skus_level . '.book_version')
                        ->withCount('all_sold_sku')
                        ->where('sale_sku_count', '>', 0)
                        ->where(function($q) use ($tag, $low, $high) {
                            // 价格
                            //$q->where('price', '>=', $low)
                            //   ->where('price', '<=', $high)
                            //   ->where(function($qTag) use ($tag) {
                                    // 标签
                                $q->where('group1', $tag)
                                        ->orWhere('group2', $tag)
                                        ->orWhere('group3', $tag);
                            //   });


                        })
                        ->orderBy('all_sold_sku_count', 'desc')
                        ->paginate(100);
                    //});

                } else {

                    //$bookSkus = Cache::remember($cate . '_' . $tag . '_page_' . $page, 5, function () use ($tags) {
                    $booksTmp = Book::select('id', 'isbn', 'name', 'subtitle', 'author', 'rating_num', 'cover_replace',
                        'press', 'publish_year', 'binding', 'category', 'price', 'sale_discount',
                        'type', 'group1', 'group2', 'group3')
                        //->with('for_sale_skus.user')
                        //->with('for_sale_skus.book_version')
                        ->with($skus_level . '.user')
                        ->with($skus_level . '.book_version')
                        ->withCount('all_sold_sku')
                        ->where('sale_sku_count', '>', 0)
                        ->where(function($q) use ($tag, $low, $high) {
                            //$q->where('price', '>=', $low)
                            //   ->where('price', '<=', $high)
                            //   ->where(function($qTag) use ($tag) {
                                    $q->where('group1', $tag)
                                        ->orWhere('group2', $tag)
                                        ->orWhere('group3', $tag);
                            //   });


                        })
                        ->orderBy($order_column, $orderby)
                        ->orderBy('all_sold_sku_count', 'desc')
                        ->paginate(100);
                    //});


                }

                $books = $data = [];
                if (count($booksTmp->items()) > 0) {
                    foreach ($booksTmp->items() as $b) {

                        $bk = $b->toArray();
                        // 只显示有库存的数据
                        if (count($b['for_sale_skus']) > 0) {
                            $for_sale_skus = [];
                            // 只存符合价格的 sku
                            foreach ($b['for_sale_skus'] as $sku) {

                                if ($sku['price'] >= $low && $sku['price'] <= $high) {
                                    $for_sale_skus[] = $sku;
                                }
                            }

                            if (count($for_sale_skus) > 0) {
                                $bk['for_sale_skus'] = $for_sale_skus;
                                $data[] = $bk;
                            }

                        }

                    }

                    $books['data']      = $data;
                    $books['total']     = $booksTmp->total();
                    $books['next_page_url'] = $booksTmp->nextPageUrl();
                    $books['last_page']     = $booksTmp->lastPage();

                }

                $bookSkus = $books;


            } else if ($level) {

                /**
                 * 品相
                 */
                if ($rating==0 && $discount==0) {
                    // 无排序
                    //$bookSkus = Cache::remember($cate . '_' . $tag . '_page_' . $page, 5, function () use ($tags) {
                    $booksTmp = Book::select('id', 'isbn', 'name', 'subtitle', 'author', 'rating_num', 'cover_replace',
                        'press', 'publish_year', 'binding', 'category', 'price', 'sale_discount',
                        'type', 'group1', 'group2', 'group3')
                        ->with($skus_level)
                        //->with('for_sale_skus.user')
                        //->with('for_sale_skus.book_version')
                        ->with($skus_level . '.user')
                        ->with($skus_level . '.book_version')
                        ->withCount('all_sold_sku')
                        ->where('sale_sku_count', '>', 0)
                        ->where(function($q) use ($tag) {
                            $q->where('group1', $tag)
                                ->orWhere('group2', $tag)
                                ->orWhere('group3', $tag);
                        })
                        ->orderBy('all_sold_sku_count', 'desc')
                        ->paginate(100);
                    //});

                } else {

                    //$bookSkus = Cache::remember($cate . '_' . $tag . '_page_' . $page, 5, function () use ($tags) {
                    $booksTmp = Book::select('id', 'isbn', 'name', 'subtitle', 'author', 'rating_num', 'cover_replace',
                        'press', 'publish_year', 'binding', 'category', 'price', 'sale_discount',
                        'type', 'group1', 'group2', 'group3')
                        //->with('for_sale_skus.user')
                        //->with('for_sale_skus.book_version')
                        ->with($skus_level . '.user')
                        ->with($skus_level . '.book_version')
                        ->withCount('all_sold_sku')
                        ->where('sale_sku_count', '>', 0)
                        ->where(function($q) use ($tag) {
                            $q->where('group1', $tag)
                                ->orWhere('group2', $tag)
                                ->orWhere('group3', $tag);
                        })
                        ->orderBy($order_column, $orderby)
                        ->orderBy('all_sold_sku_count', 'desc')
                        ->paginate(100);
                    //});

                }

                $books = $data = [];
                if (count($booksTmp->items()) > 0) {
                    foreach ($booksTmp->items() as $b) {
                        // 只显示有库存的数据
                        if ($level == 100) {
                            $b->for_sale_skus = $b->for_sale_skus_level_100;
                        } else if ($level == 80) {
                            $b->for_sale_skus = $b->for_sale_skus_level_80;
                        } else if ($level == 60) {
                            $b->for_sale_skus = $b->for_sale_skus_level_60;
                        }

                        if (count($b->for_sale_skus) > 0) {
                            $data[] = $b;
                        }

                    }

                    $books['data']      = $data;
                    $books['total']     = $booksTmp->total();
                    $books['next_page_url'] = $booksTmp->nextPageUrl();
                    $books['last_page']     = $booksTmp->lastPage();

                }

                $bookSkus = $books;

            } else {

                /**
                 * 默认
                 */
                if ($rating==0 && $discount==0) {
                    // 无排序
                    //$bookSkus = Cache::remember($cate . '_' . $tag . '_page_' . $page, 5, function () use ($tags) {
                    $bookSkus = Book::select('id', 'isbn', 'name', 'subtitle', 'author', 'rating_num', 'cover_replace',
                        'press', 'publish_year', 'binding', 'category', 'price', 'sale_discount',
                        'type', 'group1', 'group2', 'group3')
                        ->with('for_sale_skus.user')
                        ->with('for_sale_skus.book_version')
                        ->withCount('all_sold_sku')
                        ->where('sale_sku_count', '>', 0)
                        ->where(function($q) use ($tag) {
                            $q->where('group1', $tag)
                                ->orWhere('group2', $tag)
                                ->orWhere('group3', $tag);
                        })
                        ->orderBy('all_sold_sku_count', 'desc')
                        ->paginate(30);
                    //});

                } else {

                    //$bookSkus = Cache::remember($cate . '_' . $tag . '_page_' . $page, 5, function () use ($tags) {
                    $booksTmp = Book::select('id', 'isbn', 'name', 'subtitle', 'author', 'rating_num', 'cover_replace',
                        'press', 'publish_year', 'binding', 'category', 'price', 'sale_discount',
                        'type', 'group1', 'group2', 'group3')
                        ->with('for_sale_skus.user')
                        ->with('for_sale_skus.book_version')
                        ->withCount('all_sold_sku')
                        ->where('sale_sku_count', '>', 0)
                        ->where(function($q) use ($tag) {
                            $q->where('group1', $tag)
                                ->orWhere('group2', $tag)
                                ->orWhere('group3', $tag);
                        })
                        ->orderBy($order_column, $orderby)
                        ->orderBy('updated_at', 'desc')
                        ->paginate(30);
                    //});

                    $books = $data = [];
                    if (count($booksTmp->items()) > 0) {
                        foreach ($booksTmp->items() as $b) {
                            // 只显示有库存的数据
                            if(count($b->for_sale_skus) > 0) {
                                $data[] = $b;
                            }

                        }

                        $books['data']      = $data;
                        $books['total']     = $booksTmp->total();
                        $books['next_page_url'] = $booksTmp->nextPageUrl();
                        $books['last_page']     = $booksTmp->lastPage();

                    }

                    $bookSkus = $books;

                }

            }

            return $bookSkus;

        }

        return response()->json([
            'status' => false,
            'message' => '标签不存在'
        ]);
    }


    /**
     *
     * 超级畅销
     *
     */
    public function getBestseller()
    {
        $page = request('page') ?: 1;
        $tag = request('tag') ?: '新上架';

        $tags = ['新上架', '至少读两遍', '哪儿都有TA', '逢人便推荐'];
        if (!in_array($tag, $tags)) {
            return response()->json([
                'status'    => false,
                'message'   => '分类不存在'
            ]);
        }

        // 折扣  1升序  2降序
        $discount = intval( request('discount') );
        $args_discount = [1, 2];
        if ($discount && !in_array($discount, $args_discount)) {
            return response()->json([
                'code' => 500,
                'msg' => '折扣参数错误'
            ]);
        }


        // 评分  1升序   2降序
        $rating = intval( request('rating') );
        $args_rating = [1, 2];
        if ($rating && !in_array($rating, $args_rating)) {
            return response()->json([
                'code' => 500,
                'msg' => '评分参数错误'
            ]);
        }

        // 价格
        $low = 0;
        $high = 0;
        $price = trim( request('price') );
        $prices = [];
        if ($price && strpos($price, '-')!==false) {
            $prices = explode('-', $price);
            if (count($prices) == 2) {
                if ($prices[0]==0 && $prices[1]==0) {
                    return response()->json([
                        'code' => 500,
                        'msg' => '价格参数错误'
                    ]);
                } else if ($prices[0]==0) {

                    $high = $prices[1];
                } else if ($prices[1]==0) {

                    $high = 1000;
                } else {

                    $low = $prices[0];
                    $high = $prices[1];
                }

                if ($low < $high) {
                    $low = floatval( $low );
                    $high = floatval( $high );
                } else {
                    return response()->json([
                        'code' => 500,
                        'msg' => '价格区间错误'
                    ]);
                }
            }

        }

        // 品相  全新100  上好80  中等60
        $level = trim(request('level'));
        $args_level = [100, 80, 60];
        if ($level && !in_array($level, $args_level)) {
            return response()->json([
                'code' => 500,
                'msg' => '品相参数错误'
            ]);
        }


        // 有排序
        if ($discount == 1) {
            // 折扣
            $order_column = 'sale_discount';
            $orderby = 'asc';
        } else if ($discount == 2) {
            $order_column = 'sale_discount';
            $orderby = 'desc';
        } else if ($rating == 1) {
            // 豆瓣评分
            $order_column = 'rating_num';
            $orderby = 'asc';
        } else {
            $order_column = 'rating_num';
            $orderby = 'desc';
        }


        $skus_level = 'for_sale_skus_level_100';
        if ($level == 80) {
            $skus_level = 'for_sale_skus_level_80';
        }

        if ($level == 60) {
            $skus_level = 'for_sale_skus_level_60';
        }


        /**
         *
         * 新上架
         *
         */
        if ($tag == '新上架') {

            if ($price && $level) {
                /**
                 *
                 * 价格
                 * 品相
                 *
                 */
                if ($rating==0 && $discount==0) {
                    // 无排序
                    //$bookSkus = Cache::remember($cate . '_' . $tag . '_page_' . $page, 5, function () use ($tags) {
                    $booksTmp = Book::select('id', 'isbn', 'name', 'subtitle', 'author', 'rating_num', 'cover_replace',
                        'press', 'publish_year', 'binding', 'category', 'price', 'sale_discount',
                        'type', 'group1', 'group2', 'group3')
                        ->with($skus_level)
                        //->with('for_sale_skus.user')
                        //->with('for_sale_skus.book_version')
                        ->with($skus_level . '.user')
                        ->with($skus_level . '.book_version')
                        ->withCount('all_sold_sku')
                        ->where('sale_sku_count', '>', 0)
                        ->where(function($q) use ($tags, $level, $low, $high) {
                            // 品相
                            //$q->where('price', '>=', $low)
                            //  ->where('price', '<=', $high)
                            //  ->where(function($qTags) use ($tags) {
                            // 标签
                            $q->whereIn('group1', $tags)
                                ->orWhereIn('group2', $tags)
                                ->orWhereIn('group3', $tags);
                            //  });

                        })
                        ->orderBy('updated_at', 'desc')
                        ->paginate(30);
                    //});

                } else {

                    //$bookSkus = Cache::remember($cate . '_' . $tag . '_page_' . $page, 5, function () use ($tags) {
                    $booksTmp = Book::select('id', 'isbn', 'name', 'subtitle', 'author', 'rating_num', 'cover_replace',
                        'press', 'publish_year', 'binding', 'category', 'price', 'sale_discount',
                        'type', 'group1', 'group2', 'group3')
                        //->with('for_sale_skus.user')
                        //->with('for_sale_skus.book_version')
                        ->with($skus_level . '.user')
                        ->with($skus_level . '.book_version')
                        ->withCount('all_sold_sku')
                        ->where('sale_sku_count', '>', 0)
                        ->where(function($q) use ($tags, $level, $low, $high) {
                            //$q->where('price', '>=', $low)
                            //    ->where('price', '<=', $high)
                            //    ->where(function($qTags) use ($tags) {
                            $q->whereIn('group1', $tags)
                                ->orWhereIn('group2', $tags)
                                ->orWhereIn('group3', $tags);
                            //   });
                        })
                        ->orderBy($order_column, $orderby)
                        ->orderBy('updated_at', 'desc')
                        ->paginate(30);
                    //});


                }


                $books = $data = [];
                if (count($booksTmp->items()) > 0) {
                    foreach ($booksTmp->items() as $b) {
                        // 只显示有库存的数据
                        if ($level == 100) {
                            $b->for_sale_skus = $b->for_sale_skus_level_100;
                        } else if ($level == 80) {
                            $b->for_sale_skus = $b->for_sale_skus_level_80;
                        } else if ($level == 60) {
                            $b->for_sale_skus = $b->for_sale_skus_level_60;
                        }

                        $bk = $b->toArray();
                        // 只显示有库存的数据
                        if (count($b['for_sale_skus']) > 0) {
                            $for_sale_skus = [];
                            // 只存符合价格的 sku
                            foreach ($b['for_sale_skus'] as $sku) {

                                if ($sku['price'] >= $low && $sku['price'] <= $high) {
                                    $for_sale_skus[] = $sku;
                                }
                            }

                            if (count($for_sale_skus) > 0) {
                                $bk['for_sale_skus'] = $for_sale_skus;
                                $data[] = $bk;
                            }

                        }

                    }

                    $books['data']      = $data;
                    $books['total']     = $booksTmp->total();
                    $books['next_page_url'] = $booksTmp->nextPageUrl();
                    $books['last_page']     = $booksTmp->lastPage();

                }

                $bookSkus = $books;

            } else if ($price) {

                /**
                 * 价格
                 */
                $skus_level = 'for_sale_skus';


                if ($rating==0 && $discount==0) {
                    // 无排序
                    //$bookSkus = Cache::remember($cate . '_' . $tag . '_page_' . $page, 5, function () use ($tags) {
                    $booksTmp = Book::select('id', 'isbn', 'name', 'subtitle', 'author', 'rating_num', 'cover_replace',
                        'press', 'publish_year', 'binding', 'category', 'price', 'sale_discount',
                        'type', 'group1', 'group2', 'group3')
                        ->with($skus_level)
                        //->with('for_sale_skus.user')
                        //->with('for_sale_skus.book_version')
                        ->with($skus_level . '.user')
                        ->with($skus_level . '.book_version')
                        ->withCount('all_sold_sku')
                        ->where('sale_sku_count', '>', 0)

                        ->where(function($q) use ($tags, $low, $high) {
                            // 价格
                            //$q->where('price', '>=', $low)
                            //    ->where('price', '<=', $high)
                            //    ->where(function($qTags) use ($tags) {
                            // 标签
                            $q->whereIn('group1', $tags)
                                ->orWhereIn('group2', $tags)
                                ->orWhereIn('group3', $tags);
                            //    });
                        })
                        ->orderBy('updated_at', 'desc')
                        ->paginate(30);
                    //});

                } else {

                    //$bookSkus = Cache::remember($cate . '_' . $tag . '_page_' . $page, 5, function () use ($tags) {
                    $booksTmp = Book::select('id', 'isbn', 'name', 'subtitle', 'author', 'rating_num', 'cover_replace',
                        'press', 'publish_year', 'binding', 'category', 'price', 'sale_discount',
                        'type', 'group1', 'group2', 'group3')
                        //->with('for_sale_skus.user')
                        //->with('for_sale_skus.book_version')
                        ->with($skus_level . '.user')
                        ->with($skus_level . '.book_version')
                        ->withCount('all_sold_sku')
                        ->where('sale_sku_count', '>', 0)
                        ->where(function($q) use ($tags, $low, $high) {
                            // 价格
                            //$q->where('price', '>=', $low)
                            //    ->where('price', '<=', $high)
                            //    ->where(function($qTags) use ($tags) {
                            // 标签
                            $q->whereIn('group1', $tags)
                                ->orWhereIn('group2', $tags)
                                ->orWhereIn('group3', $tags);
                            //    });
                        })
                        ->orderBy($order_column, $orderby)
                        ->orderBy('updated_at', 'desc')
                        ->paginate(30);
                    //});


                }

                $books = $data = [];
                if (count($booksTmp->items()) > 0) {
                    foreach ($booksTmp->items() as $b) {
                        $bk = $b->toArray();
                        // 只显示有库存的数据
                        if (count($b['for_sale_skus']) > 0) {
                            $for_sale_skus = [];
                            // 只存符合价格的 sku
                            foreach ($b['for_sale_skus'] as $sku) {

                                if ($sku['price'] >= $low && $sku['price'] <= $high) {
                                    $for_sale_skus[] = $sku;
                                }
                            }

                            if (count($for_sale_skus) > 0) {
                                $bk['for_sale_skus'] = $for_sale_skus;
                                $data[] = $bk;
                            }

                        }

                    }

                    $books['data']      = $data;
                    $books['total']     = $booksTmp->total();
                    $books['next_page_url'] = $booksTmp->nextPageUrl();
                    $books['last_page']     = $booksTmp->lastPage();

                }

                $bookSkus = $books;



            } else if ($level) {
                /**
                 * 品相
                 */
                if ($rating==0 && $discount==0) {
                    // 无排序
                    //$bookSkus = Cache::remember($cate . '_' . $tag . '_page_' . $page, 5, function () use ($tags) {
                    $booksTmp = Book::select('id', 'isbn', 'name', 'subtitle', 'author', 'rating_num', 'cover_replace',
                        'press', 'publish_year', 'binding', 'category', 'price', 'sale_discount',
                        'type', 'group1', 'group2', 'group3')
                        ->with($skus_level)
                        //->with('for_sale_skus.user')
                        //->with('for_sale_skus.book_version')
                        ->with($skus_level . '.user')
                        ->with($skus_level . '.book_version')
                        ->withCount('all_sold_sku')
                        ->where('sale_sku_count', '>', 0)
                        ->where(function($q) use ($tags) {
                            $q->whereIn('group1', $tags)
                                ->orWhereIn('group2', $tags)
                                ->orWhereIn('group3', $tags);
                        })
                        ->orderBy('updated_at', 'desc')
                        ->paginate(30);
                    //});

                } else {

                    //$bookSkus = Cache::remember($cate . '_' . $tag . '_page_' . $page, 5, function () use ($tags) {
                    $booksTmp = Book::select('id', 'isbn', 'name', 'subtitle', 'author', 'rating_num', 'cover_replace',
                        'press', 'publish_year', 'binding', 'category', 'price', 'sale_discount',
                        'type', 'group1', 'group2', 'group3')
                        //->with('for_sale_skus.user')
                        //->with('for_sale_skus.book_version')
                        ->with($skus_level . '.user')
                        ->with($skus_level . '.book_version')
                        ->withCount('all_sold_sku')
                        ->where('sale_sku_count', '>', 0)
                        ->where(function($q) use ($tags) {
                            $q->whereIn('group1', $tags)
                                ->orWhereIn('group2', $tags)
                                ->orWhereIn('group3', $tags);
                        })
                        ->orderBy($order_column, $orderby)
                        ->orderBy('updated_at', 'desc')
                        ->paginate(30);
                    //});


                }

                $books = $data = [];
                if (count($booksTmp->items()) > 0) {
                    foreach ($booksTmp->items() as $b) {
                        // 只显示有库存的数据
                        if ($level == 100) {
                            $b->for_sale_skus = $b->for_sale_skus_level_100;
                        } else if ($level == 80) {
                            $b->for_sale_skus = $b->for_sale_skus_level_80;
                        } else if ($level == 60) {
                            $b->for_sale_skus = $b->for_sale_skus_level_60;
                        }

                        if (count($b->for_sale_skus) > 0) {
                            $data[] = $b;
                        }

                    }

                    $books['data']      = $data;
                    $books['total']     = $booksTmp->total();
                    $books['next_page_url'] = $booksTmp->nextPageUrl();
                    $books['last_page']     = $booksTmp->lastPage();
                    $books['order'] = [$order_column, $orderby];

                }

                $bookSkus = $books;


            } else {

                if ($rating==0 && $discount==0) {
                    // 无排序
                    //$bookSkus = Cache::remember($cate . '_' . $tag . '_page_' . $page, 5, function () use ($tags) {
                    $bookSkus = Book::select('id', 'isbn', 'name', 'subtitle', 'author', 'rating_num', 'cover_replace',
                        'press', 'publish_year', 'binding', 'category', 'price', 'sale_discount',
                        'type', 'group1', 'group2', 'group3')
                        ->with('for_sale_skus.user')
                        ->with('for_sale_skus.book_version')
                        ->withCount('all_sold_sku')
                        ->where('sale_sku_count', '>', 0)
                        ->where(function($q) use ($tags) {
                            $q->whereIn('group1', $tags)
                                ->orWhereIn('group2', $tags)
                                ->orWhereIn('group3', $tags);
                        })
                        ->orderBy('updated_at', 'desc')
                        ->paginate(30);
                    //});

                } else {

                    //$bookSkus = Cache::remember($cate . '_' . $tag . '_page_' . $page, 5, function () use ($tags) {
                    $booksTmp = Book::select('id', 'isbn', 'name', 'subtitle', 'author', 'rating_num', 'cover_replace',
                        'press', 'publish_year', 'binding', 'category', 'price', 'sale_discount',
                        'type', 'group1', 'group2', 'group3')
                        ->with('for_sale_skus.user')
                        ->with('for_sale_skus.book_version')
                        ->withCount('all_sold_sku')
                        ->where('sale_sku_count', '>', 0)
                        ->where(function($q) use ($tags) {
                            $q->whereIn('group1', $tags)
                                ->orWhereIn('group2', $tags)
                                ->orWhereIn('group3', $tags);
                        })
                        ->orderBy($order_column, $orderby)
                        ->orderBy('updated_at', 'desc')
                        ->paginate(30);
                    //});

                    $books = $data = [];
                    if (count($booksTmp->items()) > 0) {
                        foreach ($booksTmp->items() as $b) {
                            // 只显示有库存的数据
                            if(count($b->for_sale_skus) > 0) {
                                $data[] = $b;
                            }

                        }

                        $books['data']      = $data;
                        $books['total']     = $booksTmp->total();
                        $books['next_page_url'] = $booksTmp->nextPageUrl();
                        $books['last_page']     = $booksTmp->lastPage();
                        $books['order'] = [$order_column, $orderby];

                    }

                    $bookSkus = $books;

                }

            }

            // 返回接口数据
            return $bookSkus;

        } else {
            /**
             *
             * 某个分类
             *
             */

            if ($price && $level) {
                /**
                 *
                 * 价格
                 * 品相
                 *
                 */
                if ($rating==0 && $discount==0) {
                    // 无排序
                    //$bookSkus = Cache::remember($cate . '_' . $tag . '_page_' . $page, 5, function () use ($tags) {
                    $booksTmp = Book::select('id', 'isbn', 'name', 'subtitle', 'author', 'rating_num', 'cover_replace',
                        'press', 'publish_year', 'binding', 'category', 'price', 'sale_discount',
                        'type', 'group1', 'group2', 'group3')
                        ->with($skus_level)
                        //->with('for_sale_skus.user')
                        //->with('for_sale_skus.book_version')
                        ->with($skus_level . '.user')
                        ->with($skus_level . '.book_version')
                        ->withCount('all_sold_sku')
                        ->where('sale_sku_count', '>', 0)
                        ->where(function($q) use ($tag, $level, $low, $high) {
                            // 价格
                            //$q->where('price', '>=', $low)
                            //   ->where('price', '<=', $high)
                            //   ->where(function($qTag) use($tag) {
                            // 标签
                            $q->where('group1', $tag)
                                ->orWhere('group2', $tag)
                                ->orWhere('group3', $tag);
                            //    });

                        })
                        ->orderBy('all_sold_sku_count', 'desc')
                        ->paginate(30);
                    //});

                } else {

                    //$bookSkus = Cache::remember($cate . '_' . $tag . '_page_' . $page, 5, function () use ($tags) {
                    $booksTmp = Book::select('id', 'isbn', 'name', 'subtitle', 'author', 'rating_num', 'cover_replace',
                        'press', 'publish_year', 'binding', 'category', 'price', 'sale_discount',
                        'type', 'group1', 'group2', 'group3')
                        //->with('for_sale_skus.user')
                        //->with('for_sale_skus.book_version')
                        ->with($skus_level . '.user')
                        ->with($skus_level . '.book_version')
                        ->withCount('all_sold_sku')
                        ->where('sale_sku_count', '>', 0)
                        ->where(function($q) use ($tag, $level, $low, $high) {
                            // 价格
                            //$q->where('price', '>=', $low)
                            //   ->where('price', '<=', $high)
                            //   ->where(function ($qTag) use ($tag) {
                            // 标签
                            $q->where('group1', $tag)
                                ->orWhere('group2', $tag)
                                ->orWhere('group3', $tag);
                            // });

                        })
                        ->orderBy($order_column, $orderby)
                        ->orderBy('all_sold_sku_count', 'desc')
                        ->paginate(30);
                    //});


                }

                $books = $data = [];
                if (count($booksTmp->items()) > 0) {
                    foreach ($booksTmp->items() as $b) {
                        if ($level == 100) {
                            $b->for_sale_skus = $b->for_sale_skus_level_100;
                        } else if ($level == 80) {
                            $b->for_sale_skus = $b->for_sale_skus_level_80;
                        } else if ($level == 60) {
                            $b->for_sale_skus = $b->for_sale_skus_level_60;
                        }

                        $bk = $b->toArray();
                        // 只显示有库存的数据
                        if (count($b['for_sale_skus']) > 0) {
                            $for_sale_skus = [];
                            // 只存符合价格的 sku
                            foreach ($b['for_sale_skus'] as $sku) {

                                if ($sku['price'] >= $low && $sku['price'] <= $high) {
                                    $for_sale_skus[] = $sku;
                                }
                            }

                            if (count($for_sale_skus) > 0) {
                                $bk['for_sale_skus'] = $for_sale_skus;
                                $data[] = $bk;
                            }

                        }

                    }

                    $books['data']      = $data;
                    $books['total']     = $booksTmp->total();
                    $books['next_page_url'] = $booksTmp->nextPageUrl();
                    $books['last_page']     = $booksTmp->lastPage();

                }

                $bookSkus = $books;

            } else if ($price) {
                /**
                 * 价格
                 */
                $skus_level = 'for_sale_skus';


                if ($rating==0 && $discount==0) {
                    // 无排序
                    //$bookSkus = Cache::remember($cate . '_' . $tag . '_page_' . $page, 5, function () use ($tags) {
                    $booksTmp = Book::select('id', 'isbn', 'name', 'subtitle', 'author', 'rating_num', 'cover_replace',
                        'press', 'publish_year', 'binding', 'category', 'price', 'sale_discount',
                        'type', 'group1', 'group2', 'group3')
                        ->with($skus_level)
                        //->with('for_sale_skus.user')
                        //->with('for_sale_skus.book_version')
                        ->with($skus_level . '.user')
                        ->with($skus_level . '.book_version')
                        ->withCount('all_sold_sku')
                        ->where('sale_sku_count', '>', 0)
                        ->where(function($q) use ($tag, $low, $high) {
                            // 价格
                            //$q->where('price', '>=', $low)
                            //   ->where('price', '<=', $high)
                            //   ->where(function($qTag) use ($tag) {
                            // 标签
                            $q->where('group1', $tag)
                                ->orWhere('group2', $tag)
                                ->orWhere('group3', $tag);
                            //   });


                        })
                        ->orderBy('all_sold_sku_count', 'desc')
                        ->paginate(30);
                    //});

                } else {

                    //$bookSkus = Cache::remember($cate . '_' . $tag . '_page_' . $page, 5, function () use ($tags) {
                    $booksTmp = Book::select('id', 'isbn', 'name', 'subtitle', 'author', 'rating_num', 'cover_replace',
                        'press', 'publish_year', 'binding', 'category', 'price', 'sale_discount',
                        'type', 'group1', 'group2', 'group3')
                        //->with('for_sale_skus.user')
                        //->with('for_sale_skus.book_version')
                        ->with($skus_level . '.user')
                        ->with($skus_level . '.book_version')
                        ->withCount('all_sold_sku')
                        ->where('sale_sku_count', '>', 0)
                        ->where(function($q) use ($tag, $low, $high) {
                            //$q->where('price', '>=', $low)
                            //   ->where('price', '<=', $high)
                            //   ->where(function($qTag) use ($tag) {
                            $q->where('group1', $tag)
                                ->orWhere('group2', $tag)
                                ->orWhere('group3', $tag);
                            //   });


                        })
                        ->orderBy($order_column, $orderby)
                        ->orderBy('all_sold_sku_count', 'desc')
                        ->paginate(30);
                    //});


                }

                $books = $data = [];
                if (count($booksTmp->items()) > 0) {
                    foreach ($booksTmp->items() as $b) {

                        $bk = $b->toArray();
                        // 只显示有库存的数据
                        if (count($b['for_sale_skus']) > 0) {
                            $for_sale_skus = [];
                            // 只存符合价格的 sku
                            foreach ($b['for_sale_skus'] as $sku) {

                                if ($sku['price'] >= $low && $sku['price'] <= $high) {
                                    $for_sale_skus[] = $sku;
                                }
                            }

                            if (count($for_sale_skus) > 0) {
                                $bk['for_sale_skus'] = $for_sale_skus;
                                $data[] = $bk;
                            }

                        }

                    }

                    $books['data']      = $data;
                    $books['total']     = $booksTmp->total();
                    $books['next_page_url'] = $booksTmp->nextPageUrl();
                    $books['last_page']     = $booksTmp->lastPage();

                }

                $bookSkus = $books;


            } else if ($level) {

                /**
                 * 品相
                 */
                if ($rating==0 && $discount==0) {
                    // 无排序
                    //$bookSkus = Cache::remember($cate . '_' . $tag . '_page_' . $page, 5, function () use ($tags) {
                    $booksTmp = Book::select('id', 'isbn', 'name', 'subtitle', 'author', 'rating_num', 'cover_replace',
                        'press', 'publish_year', 'binding', 'category', 'price', 'sale_discount',
                        'type', 'group1', 'group2', 'group3')
                        ->with($skus_level)
                        //->with('for_sale_skus.user')
                        //->with('for_sale_skus.book_version')
                        ->with($skus_level . '.user')
                        ->with($skus_level . '.book_version')
                        ->withCount('all_sold_sku')
                        ->where('sale_sku_count', '>', 0)
                        ->where(function($q) use ($tag) {
                            $q->where('group1', $tag)
                                ->orWhere('group2', $tag)
                                ->orWhere('group3', $tag);
                        })
                        ->orderBy('all_sold_sku_count', 'desc')
                        ->paginate(30);
                    //});

                } else {

                    //$bookSkus = Cache::remember($cate . '_' . $tag . '_page_' . $page, 5, function () use ($tags) {
                    $booksTmp = Book::select('id', 'isbn', 'name', 'subtitle', 'author', 'rating_num', 'cover_replace',
                        'press', 'publish_year', 'binding', 'category', 'price', 'sale_discount',
                        'type', 'group1', 'group2', 'group3')
                        //->with('for_sale_skus.user')
                        //->with('for_sale_skus.book_version')
                        ->with($skus_level . '.user')
                        ->with($skus_level . '.book_version')
                        ->withCount('all_sold_sku')
                        ->where('sale_sku_count', '>', 0)
                        ->where(function($q) use ($tag) {
                            $q->where('group1', $tag)
                                ->orWhere('group2', $tag)
                                ->orWhere('group3', $tag);
                        })
                        ->orderBy($order_column, $orderby)
                        ->orderBy('all_sold_sku_count', 'desc')
                        ->paginate(30);
                    //});

                }

                $books = $data = [];
                if (count($booksTmp->items()) > 0) {
                    foreach ($booksTmp->items() as $b) {
                        // 只显示有库存的数据
                        if ($level == 100) {
                            $b->for_sale_skus = $b->for_sale_skus_level_100;
                        } else if ($level == 80) {
                            $b->for_sale_skus = $b->for_sale_skus_level_80;
                        } else if ($level == 60) {
                            $b->for_sale_skus = $b->for_sale_skus_level_60;
                        }

                        if (count($b->for_sale_skus) > 0) {
                            $data[] = $b;
                        }

                    }

                    $books['data']      = $data;
                    $books['total']     = $booksTmp->total();
                    $books['next_page_url'] = $booksTmp->nextPageUrl();
                    $books['last_page']     = $booksTmp->lastPage();

                }

                $bookSkus = $books;

            } else {

                /**
                 * 默认
                 */
                if ($rating==0 && $discount==0) {
                    // 无排序
                    //$bookSkus = Cache::remember($cate . '_' . $tag . '_page_' . $page, 5, function () use ($tags) {
                    $bookSkus = Book::select('id', 'isbn', 'name', 'subtitle', 'author', 'rating_num', 'cover_replace',
                        'press', 'publish_year', 'binding', 'category', 'price', 'sale_discount',
                        'type', 'group1', 'group2', 'group3')
                        ->with('for_sale_skus.user')
                        ->with('for_sale_skus.book_version')
                        ->withCount('all_sold_sku')
                        ->where('sale_sku_count', '>', 0)
                        ->where(function($q) use ($tag) {
                            $q->where('group1', $tag)
                                ->orWhere('group2', $tag)
                                ->orWhere('group3', $tag);
                        })
                        ->orderBy('all_sold_sku_count', 'desc')
                        ->paginate(30);
                    //});

                } else {

                    //$bookSkus = Cache::remember($cate . '_' . $tag . '_page_' . $page, 5, function () use ($tags) {
                    $booksTmp = Book::select('id', 'isbn', 'name', 'subtitle', 'author', 'rating_num', 'cover_replace',
                        'press', 'publish_year', 'binding', 'category', 'price', 'sale_discount',
                        'type', 'group1', 'group2', 'group3')
                        ->with('for_sale_skus.user')
                        ->with('for_sale_skus.book_version')
                        ->withCount('all_sold_sku')
                        ->where('sale_sku_count', '>', 0)
                        ->where(function($q) use ($tag) {
                            $q->where('group1', $tag)
                                ->orWhere('group2', $tag)
                                ->orWhere('group3', $tag);
                        })
                        ->orderBy($order_column, $orderby)
                        ->orderBy('updated_at', 'desc')
                        ->paginate(30);
                    //});

                    $books = $data = [];
                    if (count($booksTmp->items()) > 0) {
                        foreach ($booksTmp->items() as $b) {
                            // 只显示有库存的数据
                            if(count($b->for_sale_skus) > 0) {
                                $data[] = $b;
                            }

                        }

                        $books['data']      = $data;
                        $books['total']     = $booksTmp->total();
                        $books['next_page_url'] = $booksTmp->nextPageUrl();
                        $books['last_page']     = $booksTmp->lastPage();

                    }

                    $bookSkus = $books;

                }

            }

            return $bookSkus;

        }

        return response()->json([
            'status' => false,
            'message' => '标签不存在'
        ]);

    }


    /**
     * 获取新人礼优惠券
     */
    public function getNewUserCoupons() {
        $wx_user = session('wechat.oauth_user.default');
        $user = $this->fetchUser($wx_user);

        // 是否成功下过单
        $order_count = Order::where('user_id', $user->id)
            ->where(function($query) {
                $query->where('recover_status', 70)
                    ->orWhere('sale_status', 70);
            })
            ->count();

        // 是否有激活的新人优惠券
        $coupon_count = Coupon::where('user_id', $user->id)
            ->where('value', '<', 15)
            ->where('enabled', 1)
            ->where('from', 'like', '%share%')
            ->count();

        // 没有成功下单 and 没有已激活的新人券
        if ($order_count==0 && $coupon_count==0) {
            $new_user = true;
        } else {
            $new_user = false;
        }

        return response()->json([
           'code'       => 0,
           'new_user'   => $new_user,
           'msg'        => '新用户'
        ]);

    }


    /**
     * 购物车数量
     */
    public function getUserCartBooks()
    {
        $wx_user = session('wechat.oauth_user.default');
        $user = $this->fetchUser($wx_user);

        $book_ids = CartItem::select('book_id')
            ->where('user_id', $user->id)
            ->get();
        $ids = $book_ids->pluck('book_id');

        return response()->json([
            'code'  => 0,
            'msg'   => '购物车数量',
            'book_ids' => $ids

        ]);
    }

}
