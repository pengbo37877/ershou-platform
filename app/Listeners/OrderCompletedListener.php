<?php

namespace App\Listeners;

use App\Book;
use App\BookSale;
use App\BookShelf;
use App\BookSku;
use App\BookVersion;
use App\Console\Commands\NotifyUserBookOnSale;
use App\Coupon;
use App\Events\OrderCompleted;
use App\Events\SendCouponEnableMsg;
use App\Jobs\GenerateSkuFromOrderItemJob;
use App\Jobs\NotifyUserBookOnSaleJob;
use App\Order;
use App\OrderItem;
use App\ReminderItem;
use App\SkuPath;
use App\User;
use App\Wallet;
use Carbon\Carbon;
use EasyWeChat\Kernel\Exceptions\InvalidArgumentException;
use EasyWeChat\OfficialAccount\Application;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderCompletedListener
{
    public $app;

    /**
     * Create the event listener.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Handle the event.
     *
     * @param  OrderCompleted  $event
     * @return void
     */
    public function handle(OrderCompleted $event)
    {
        $order = $event->order;
        Log::info('订单完成' . $order->no);

        // 订单事务
        DB::transaction(function () use ($order, $event) {

            // 回收书订单
            if ($order->type == Order::ORDER_TYPE_RECOVER) {
                // 检查快递费是否填写
                if ($order->ship_price <= 0) {
                    Log::info("order " . $order->id . " 快递费没填写");
                    throw new InvalidArgumentException("order " . $order->id . " 快递费没填写");
                }
                // 回收类订单完成后打款给用户，只计算审核通过的书的费用
                $review_ok_items = $order->items->filter(function ($item) {
                    return $item->review_result == OrderItem::REVIEW_OK;
                });

                // 检查review_ok_items各项是否都填写完成了
                $review_ok_items->each(function ($item) use ($order) {
                    if (empty($item->hly_code) || strlen($item->hly_code) != 13) {
                        Log::info("order item " . $item->id . ' hly_code不规范');
                        throw new InvalidArgumentException("order item " . $item->id . ' hly_code不规范');
                    }
                    if (empty($item->level)) {
                        Log::info("order item " . $item->id . ' level不能为空');
                        throw new InvalidArgumentException("order item " . $item->id . ' level不能为空');
                    }
                    if (empty($item->title) && $item->level == BookSku::LEVEL_60) {
                        Log::info("order item " . $item->id . ' title不能为空');
                        throw new InvalidArgumentException("order item " . $item->id . ' title不能为空');
                    }
                    $has_sku = BookSku::where('hly_code', $item->hly_code)->first();
                    if ($has_sku && $has_sku->from_order != $order->id) {
                        Log::info("order item " . $item->id . ' hly_code 重复');
                        throw new InvalidArgumentException("order item " . $item->id . ' hly_code 重复');
                    }
                });
                // 修改收购价
                $review_ok_items->each(function ($item) {
                    $this->buildRecoverPrice($item);
                });
                $total_amount = $review_ok_items->sum->reviewed_price;

                // 现金券逻辑
                if ($order->coupon) {
                    $total_amount += $order->coupon->value;
                }
                $wallet = Wallet::where([
                    'user_id' => $order->user_id,
                    'order_id' => $order->id,
                    'type' => Wallet::TYPE_SALE_BOOK,
                    'status' => Wallet::STATUS_SUCCESS
                ])->first();

                if (!$wallet) {
                    Wallet::create([
                        'user_id'   => $order->user_id,
                        'order_id'  => $order->id,
                        'type'      => Wallet::TYPE_SALE_BOOK,
                        'status'    => Wallet::STATUS_SUCCESS,
                        'amount'    => $total_amount
                    ]);
                }

                $order->paid_at = now();
                $order->recover_status = Order::RECOVER_STATUS_COMPLETE;
                $order->ship_status = Order::SHIP_STATUS_RECEIVED;
                $order->save();

                collect($review_ok_items)->each(function ($item) use ($order) {
                    $prev_sku = BookSku::where('from_order', $order->id)
                        ->where('book_id', $item->book_id)
                        ->first();
                    if (empty($prev_sku)) {
                        // 批量创建sku
                        $title = $this->buildTitle($item);
                        $sale_price = $this->buildPrice($item);
                        $data = [
                            'user_id'           => $order->user_id,
                            'book_id'           => $item->book_id,
                            'book_version_id'   => $item->book_version_id ? $item->book_version_id : 0,
                            'isbn'              => $item->book->isbn,
                            'recover_price'     => $item->reviewed_price,
                            'original_price'    => $this->buildOriginalPrice($item),
                            'level'         => $item->level,
                            'title'         => $title,
                            'status'        => BookSku::STATUS_RETREADING,
                            'hly_code'      => $item->hly_code,
                            'groups'        => $this->buildGroups($item),
                            'price'         => $sale_price,
                            'from_order'    => $order->id,
                            'description'   => "",
                            'mark'          => ""
                        ];
                        $sku = BookSku::create($data);
                        if (!$sku) {
                            $sku = BookSku::create($data);
                        }

                        // 为order item 更新sku id
                        if ($sku) {
                            $item->book_sku_id = $sku->id;
                        }
                        $item->sale_price = $sale_price;
                        $item->save();

                        // 为用户增加卖书动态
                        $bookSale = BookSale::where([
                            'user_id' => $order->user_id,
                            'order_id' => $order->id,
                            'book_id' => $item->book_id,
                            'book_sku_id' => $sku->id
                        ])->first();
                        if (!$bookSale) {
                            BookSale::create([
                                'user_id'   => $order->user_id,
                                'order_id'  => $order->id,
                                'book_id'   => $item->book_id,
                                'book_sku_id' => $sku->id,
                                'isbn'      => $item->book->isbn,
                            ]);
                        }
                    }
                });

                // 用户卖书完成激活A用户的券
                $user = $event->order->user;
                if ($user->qr_scene && intval($user->qr_scene) > 0) {
                    $coupon = Coupon::where('user_id', $user->qr_scene)
                        ->where('from_user', $user->id)
                        ->where('enabled', 0)
                        ->first();

                    // 20 元满减现金券有效期 30 天
                    if ($coupon) {
                        $coupon->enabled = 1;
                        $coupon->not_after = Carbon::now()->addDays(30);
                        $coupon->not_before = Carbon::now();
                        $coupon->save();

                        event(new SendCouponEnableMsg($coupon));
                    }
                }

                // 发送订单完成通知
                $review_reject_count = count($order->items) - count($review_ok_items);
                try {
                    $this->app->template_message->send([
                        'touser'        => $order->user->mp_open_id,
                        'template_id'   => 'wLuu-n96ze_sy4gvTfigz2ujh0ILipbLefXHo11XX-c',
                        'url'   => env('APP_URL') . '/wechat/recover_order/' . $order->no,
                        'data'  => [
                            'first' => '回流鱼已经验收通过你卖的' . count($review_ok_items) . '本书，拒收了'
                                . $review_reject_count . '本',
                            'keyword1' => $order->no,
                            'keyword2' => '卖书所得的' . $total_amount . '元，已经存入你的回流鱼钱包',
                            'keyword3' => Carbon::now()->toDateTimeString()
                        ]
                    ]);
                } catch (InvalidArgumentException $e) { }

                // 发放包邮券
                if (count($review_ok_items) >= 8) {
                    $coupon = Coupon::create([
                        'user_id'   => $order->user_id,
                        'from'      => 'order_' . $order->id,
                        'from_user' => 0,
                        'name'      => '5元包邮券',
                        'type'      => Coupon::TYPE_FIXED,
                        'order_type' => Coupon::ORDER_TYPE_SALE,
                        'value'         => 5,
                        'min_amount'    => 20,
                        'not_after'     => now()->addMonth(),
                        'enabled'       => 1
                    ]);
                    if ($coupon && env('SEND_WECHAT_MSG')) {
                        $this->app->template_message->send([
                            'touser' => $order->user->mp_open_id,
                            'template_id' => 'wLuu-n96ze_sy4gvTfigz2ujh0ILipbLefXHo11XX-c',
                            'url' => env('APP_URL') . '/wechat/shop',
                            'data' => [
                                'first' => '你的' . $coupon->name . '已激活
快去回流鱼买书吧',
                                'keyword1' => '卖书满6本，你得了券',
                                'keyword2' => '有效期一个月，记得回来买书哦',
                                'keyword3' => Carbon::now()->toDateTimeString()
                            ]
                        ]);
                    }
                }
            } else if ($order->type == Order::ORDER_TYPE_SALE) {
                // 用户买书订单
                if($order->order_id){
                    $orders = Order::where('order_id',$order->order_id)->get();
                    if($orders->sum->sale_status == count($orders)*Order::SALE_STATUS_COMPLETE){
                        Order::where('id',$order->order_id)->update([
                            'sale_status' => Order::SALE_STATUS_COMPLETE
                        ]);
                    }
                }
                // 在用户书架增加书，为sku增加新传播路径
                collect($order->items)->each(function ($item) use ($order) {
                    if ($item->review_result == OrderItem::REVIEW_OK) {
                        // 用户的书架上增加书
                        $bookonshelf = BookShelf::where('user_id',$order->user_id)->where('book_id',$item->book_id)->first();
                        if(!$bookonshelf){
                            BookShelf::create([
                                'user_id' => $order->user_id,
                                'book_id' => $item->book_id,
                                'book_sku_id' => $item->book_sku_id,
                                'isbn' => $item->book->isbn
                            ]);
                        }
                        // 删除到货提醒
                        ReminderItem::where('user_id', $order->user_id)->where('book_id', $item->book_id)->delete();
                        // 增加sku的传播路径
                        $prevSkuPath = SkuPath::where([
                            'book_sku_id' => $item->book_sku_id,
                            'is_owner' => true
                        ])->first();

                        if ($prevSkuPath) {

                            SkuPath::create([
                                'book_sku_id' => $item->book_sku_id,
                                'prev_user_id' => $prevSkuPath->user_id,
                                'user_id' => $order->user_id,
                                'is_owner' => true
                            ]);
                            $prevSkuPath->is_owner = false;
                            $prevSkuPath->save();
                        } else {

                            SkuPath::create([
                                'book_sku_id' => $item->book_sku_id,
                                'user_id' => $order->user_id,
                                'is_owner' => true
                            ]);
                        }
                    }
                });

                // 激活邀请来源用户的券，这里是二次确认
                $user = $order->user;
                $coupon = Coupon::where('user_id', $user->qr_scene)
                    ->where('from_user', $order->user_id)
                    ->where('enabled', 0)
                    ->first();
                if ($coupon) {
                    $coupon->enabled = 1;
                    $coupon->save();
                    event(new SendCouponEnableMsg($coupon));
                }

                // 发送订单完成信息
                try {
                    if (env('SEND_WECHAT_MSG')) {
                        $this->app->template_message->send([
                            'touser'        => $order->user->mp_open_id,
                            'template_id'   => 'wLuu-n96ze_sy4gvTfigz2ujh0ILipbLefXHo11XX-c',
                            'url' => env('APP_URL') . '/wechat/sale_order/' . $order->no,
                            'data' => [
                                'first' => $order->user->nickname . ' 您的订单已签收。感谢你的惠顾，欢迎再次使用回流鱼，阅读不孤读！',
                                'keyword1' => $order->no,
                                'keyword2' => '已签收',
                                'keyword3' => Carbon::now()->toDateTimeString()
                            ]
                        ]);
                    }
                } catch (InvalidArgumentException $e) { }
            }
        });
        GenerateSkuFromOrderItemJob::dispatchNow($order);
    }

    function buildTitle($orderItem)
    {
        if ($orderItem->level == 100) {
            return '全新';
        } else if ($orderItem->level == 80) {
            return '上好';
        } else if ($orderItem->level == 60) {
            $r = '';
            if ($orderItem->title) {
                $arr = explode(',', $orderItem->title);
                $arr = array_diff($arr, ['一折收', '+0.5折']);
                $arr = array_map(function ($t) {
                    $jz = mb_substr_count($t, '较重');
                    if ($jz == 1) {
                        $t = mb_substr($t, 0, 2);
                    }
                    return $t;
                }, $arr);
                $r = $r . implode('、', $arr);
            }
            return $r;
        }
        return '';
    }

    function buildGroups($orderItem)
    {
        if (!empty($orderItem->groups)) {
            $book = Book::find($orderItem->book_id);
            $gs = explode(',', $orderItem->groups);
            for ($i = 0; $i < count($gs); $i++) {
                if ($i == 0) {
                    $book->group1 = $gs[$i];
                } else if ($i == 1) {
                    $book->group2 = $gs[$i];
                } else if ($i == 2) {
                    $book->group3 = $gs[$i];
                }
            }
            if (count($gs) == 1) {
                $book->group2 = '';
                $book->group3 = '';
            } else if (count($gs) == 2) {
                $book->group3 = '';
            }
            $book->save();
            return $orderItem->groups;
        }
        return '';
    }

    function buildRecoverPrice($orderItem)
    {
        $price = floatval($orderItem->price);
        $base_discount = 0.1;
        $base_price = floatval($orderItem->book->price) * .1;
        $yzs = mb_substr_count($orderItem->title, '一折收');
        if ($yzs > 0) {
            $price = $base_price;
        } else {
            $base_discount = $orderItem->price / $orderItem->book->price;
        }
        if ($orderItem->book_version_id) {
            $price = floatval(BookVersion::find($orderItem->book_version_id)->price) * $base_discount;
        }
        $jbz = mb_substr_count($orderItem->title, '+0.5折');
        if ($jbz > 0) {
            $price = $price + $base_price * .5;
        }
        if ($orderItem->level == 100) {
            $orderItem->reviewed_price = $price;
        } else if ($orderItem->level == 80) {
            $orderItem->reviewed_price = $price;
        } else if ($orderItem->level == 60) {
            $orderItem->reviewed_price = $price * .8;
        }
        $jz = mb_substr_count($orderItem->title, '较重');
        if ($jz == 1) {
            $orderItem->reviewed_price = $orderItem->price * (1 - $jz * .1);
        }
        if ($jz >= 2) {
            $orderItem->reviewed_price = $base_price * .5;
        }
        $orderItem->save();
        return number_format($orderItem->reviewed_price, 2);
    }

    function buildOriginalPrice($orderItem)
    {
        if ($orderItem->book_version) {
            return $orderItem->book_version->price;
        }
        return $orderItem->book->price;
    }

    function buildPrice($orderItem)
    {
        $book = Book::find($orderItem->book_id);
        $price = floatval($book->price);
        if ($orderItem->book_version_id) {
            $version = BookVersion::find($orderItem->book_version_id);
            if ($version) {
                $price = $version->price;
            } else {
                $price = $book->price;
            }
        }
        // 一折的价钱
        $base_price = $price * .1;
        // 已审核通过的价格为基准
        $base_discount = number_format($orderItem->price * 100 / $price, 1);
        // 其他平台的新书价格
        $prices = $book->prices;
        if ($prices) {
            $prices = $prices->first();
        }
        // 想要的人数
        $want_count = $orderItem->book->reminder_count;

        // 查看品相
        $level = $orderItem->level;
        $title = $orderItem->title;
        if ($level == 100) {
            // 收取折扣<=4折，定价6折，利润超过100，调整为100
            // 收取折扣>4折，<=5折，定价6.5折，利润超过90，调整为90
            // 收取折扣>5折，<=6折，定价7折，利润超过80，调整为80
            // 收取折扣>6折，<=7折，定价8折，利润超过60，调整为60
            // 收取折扣>7折，<=8折，定价9折，利润超过40，调整为40
            // 收取折扣>8折，定价9折，利润超过20，调整为20
            if ($base_discount <= 30) {
                $sale_price = $price * .59;
                $profit = $sale_price - $orderItem->price;
                if ($profit > 120) {
                    $sale_price = $orderItem->price + 120;
                }
                if ($want_count > 0) {
                    $sale_price = $sale_price + $base_price * $want_count / 200;
                }
            } else if ($base_discount > 30 && $base_discount <= 40) {
                $sale_price = $price * .63;
                $profit = $sale_price - $orderItem->price;
                if ($profit > 110) {
                    $sale_price = $orderItem->price + 110;
                }
                if ($want_count > 0) {
                    $sale_price = $sale_price + $base_price * $want_count / 300;
                }
            } else if ($base_discount > 40 && $base_discount <= 50) {
                $sale_price = $price * .66;
                $profit = $sale_price - $orderItem->price;
                if ($profit > 100) {
                    $sale_price = $orderItem->price + 100;
                }
                if ($want_count > 0) {
                    $sale_price = $sale_price + $base_price * $want_count / 400;
                }
            } else if ($base_discount > 50 && $base_discount <= 60) {
                $sale_price = $price * .7;
                $profit = $sale_price - $orderItem->price;
                if ($profit > 90) {
                    $sale_price = $orderItem->price + 90;
                }
                if ($want_count > 0) {
                    $sale_price = $sale_price + $base_price * $want_count / 500;
                }
            } else if ($base_discount > 60 && $base_discount <= 70) {
                $sale_price = $price * .75;
                $profit = $sale_price - $orderItem->price;
                if ($profit > 80) {
                    $sale_price = $orderItem->price + 80;
                }
                if ($want_count > 0) {
                    $sale_price = $sale_price + $base_price * $want_count / 600;
                }
            } else if ($base_discount > 70 && $base_discount <= 80) {
                $sale_price = $price * .8;
                $profit = $sale_price - $orderItem->price;
                if ($profit > 60) {
                    $sale_price = $orderItem->price + 60;
                }
                if ($want_count > 0) {
                    $sale_price = $sale_price + $base_price * $want_count / 700;
                }
            } else if ($base_discount > 80 && $base_discount <= 90) {
                $sale_price = $price * .85;
                $profit = $sale_price - $orderItem->price;
                if ($profit > 40) {
                    $sale_price = $orderItem->price + 40;
                }
                if ($want_count > 0) {
                    $sale_price = $sale_price + $base_price * $want_count / 800;
                }
            } else {
                $sale_price = $price * .9;
                $profit = $sale_price - $orderItem->price;
                if ($profit > 20) {
                    $sale_price = $orderItem->price + 20;
                }
                if ($want_count > 0) {
                    $sale_price = $sale_price + $base_price * $want_count / 900;
                }
            }
            // 最近已售10条sku记录
            $sold_skus = BookSku::where('book_id', $orderItem->book_id)->where('book_version_id', $orderItem->book_version_id)->where('level', BookSku::LEVEL_100)
                ->where('status', BookSku::STATUS_SOLD)->orderByDesc('sold_at')->take(10)->get();
            // 评价售卖价格
            if (count($sold_skus) > 0) {
                $avg_price = $sold_skus->avg->price;
                if (floatval($avg_price) > 0) {
                    if (floatval($avg_price) > floatval($sale_price)) {
                        $sale_price = $avg_price * .8 + $sale_price * .2;
                    } else {
                        $sale_price = $avg_price * .2 + $sale_price * .8;
                    }
                }
            }
            if ($want_count > 0) {
                $highest_price = 0;
                if ($prices && $prices->dd_new_price > 0 && $prices->dd_new_price > $highest_price) {
                    $highest_price = $prices->dd_new_price;
                } else if ($prices && $prices->amz_new_price > 0 && $prices->amz_new_price > $highest_price) {
                    $highest_price = $prices->amz_new_price;
                } else if ($prices && $prices->jd_new_price > 0 && $prices->jd_new_price > $highest_price) {
                    $highest_price = $prices->jd_new_price;
                } else if ($prices && $prices->bc_new_price > 0 && $prices->bc_new_price > $highest_price) {
                    $highest_price = $prices->bc_new_price;
                }
                if ($highest_price > 0 && $sale_price > $highest_price) {
                    $sale_price = $highest_price;
                }
            }
        } else if ($level == 80) {
            // 收取折扣<=2折，定价4折，利润超过80，调整为80
            // 收取折扣>2折，<=3折，定价5折，利润超过70，调整为70
            // 收取折扣>3折，<=4折，定价6折，利润超过60，调整为60
            // 收取折扣>4折，<=5折，定价7折，利润超过50，调整为50
            // 收取折扣>5折，<=6折，定价7.5折，利润超过40，调整为40
            // 收取折扣>6折，<=7折，定价8折，利润超过30，调整为30
            // 收取折扣>7折，定价9折，利润超过20，调整为20
            if ($base_discount < 15) {
                $sale_price = $price * .45;
                $profit = $sale_price - $orderItem->price;
                if ($profit > 100) {
                    $sale_price = $orderItem->price + 100;
                }
                if ($want_count > 0) {
                    $sale_price = $sale_price + $base_price * $want_count / 200;
                }
            } else if ($base_discount >= 15 && $base_discount < 20) {
                $sale_price = $price * .48;
                $profit = $sale_price - $orderItem->price;
                if ($profit > 90) {
                    $sale_price = $orderItem->price + 90;
                }
                if ($want_count > 0) {
                    $sale_price = $sale_price + $base_price * $want_count / 300;
                }
            } else if ($base_discount >= 20 && $base_discount < 30) {
                $sale_price = $price * .52;
                $profit = $sale_price - $orderItem->price;
                if ($profit > 80) {
                    $sale_price = $orderItem->price + 80;
                }
                if ($want_count > 0) {
                    $sale_price = $sale_price + $base_price * $want_count / 400;
                }
            } else if ($base_discount > 30 && $base_discount <= 40) {
                $sale_price = $price * .55;
                $profit = $sale_price - $orderItem->price;
                if ($profit > 70) {
                    $sale_price = $orderItem->price + 70;
                }
                if ($want_count > 0) {
                    $sale_price = $sale_price + $base_price * $want_count / 500;
                }
            } else if ($base_discount > 40 && $base_discount <= 50) {
                $sale_price = $price * .6;
                $profit = $sale_price - $orderItem->price;
                if ($profit > 60) {
                    $sale_price = $orderItem->price + 60;
                }
                if ($want_count > 0) {
                    $sale_price = $sale_price + $base_price * $want_count / 600;
                }
            } else if ($base_discount > 50 && $base_discount <= 60) {
                $sale_price = $price * .7;
                $profit = $sale_price - $orderItem->price;
                if ($profit > 50) {
                    $sale_price = $orderItem->price + 50;
                }
                if ($want_count > 0) {
                    $sale_price = $sale_price + $base_price * $want_count / 700;
                }
            } else if ($base_discount > 60 && $base_discount <= 70) {
                $sale_price = $price * .75;
                $profit = $sale_price - $orderItem->price;
                if ($profit > 40) {
                    $sale_price = $orderItem->price + 40;
                }
                if ($want_count > 0) {
                    $sale_price = $sale_price + $base_price * $want_count / 800;
                }
            } else if ($base_discount > 70 && $base_discount <= 80) {
                $sale_price = $price * .8;
                $profit = $sale_price - $orderItem->price;
                if ($profit > 30) {
                    $sale_price = $orderItem->price + 30;
                }
                if ($want_count > 0) {
                    $sale_price = $sale_price + $base_price * $want_count / 900;
                }
            } else {
                $sale_price = $price * .9;
                $profit = $sale_price - $orderItem->price;
                if ($profit > 20) {
                    $sale_price = $orderItem->price + 20;
                }
                if ($want_count > 0) {
                    $sale_price = $sale_price + $base_price * $want_count / 1000;
                }
            }
            // 最近已售10条sku记录
            $sold_skus = BookSku::where('book_id', $orderItem->book_id)->where('book_version_id', $orderItem->book_version_id)->where('level', BookSku::LEVEL_80)
                ->where('status', BookSku::STATUS_SOLD)->orderByDesc('sold_at')->take(10)->get();
            // 评价售卖价格
            if (count($sold_skus) > 0) {
                $avg_price = $sold_skus->avg->price;
                if (floatval($avg_price) > 0) {
                    if (floatval($avg_price) > floatval($sale_price)) {
                        $sale_price = $avg_price * .8 + $sale_price * .2;
                    } else {
                        $sale_price = $avg_price * .2 + $sale_price * .8;
                    }
                }
            }
            if ($want_count > 0) {
                $highest_price = 0;
                if ($prices && $prices->dd_new_price > 0 && $prices->dd_new_price > $highest_price) {
                    $highest_price = $prices->dd_new_price;
                } else if ($prices && $prices->amz_new_price > 0 && $prices->amz_new_price > $highest_price) {
                    $highest_price = $prices->amz_new_price;
                } else if ($prices && $prices->jd_new_price > 0 && $prices->jd_new_price > $highest_price) {
                    $highest_price = $prices->jd_new_price;
                } else if ($prices && $prices->bc_new_price > 0 && $prices->bc_new_price > $highest_price) {
                    $highest_price = $prices->bc_new_price;
                }
                if ($highest_price > 0 && $sale_price > $highest_price) {
                    $sale_price = $highest_price * .95;
                }
            }
        } else if ($level == 60) {
            if ($base_discount < 12) {
                $sale_price = $price * .3;
                $profit = $sale_price - $orderItem->price;
                if ($profit > 70) {
                    $sale_price = $orderItem->price + 70;
                }
                if ($want_count > 0) {
                    $sale_price = $sale_price + $base_price * $want_count / 400;
                }
            } else if ($base_discount >= 12 && $base_discount < 18) {
                $sale_price = $price * .33;
                $profit = $sale_price - $orderItem->price;
                if ($profit > 70) {
                    $sale_price = $orderItem->price + 70;
                }
                if ($want_count > 0) {
                    $sale_price = $sale_price + $base_price * $want_count / 400;
                }
            } else if ($base_discount >= 18 && $base_discount < 25) {
                $sale_price = $price * .35;
                $profit = $sale_price - $orderItem->price;
                if ($profit > 65) {
                    $sale_price = $orderItem->price + 65;
                }
                if ($want_count > 0) {
                    $sale_price = $sale_price + $base_price * $want_count / 500;
                }
            } else if ($base_discount >= 25 && $base_discount < 30) {
                $sale_price = $price * .4;
                $profit = $sale_price - $orderItem->price;
                if ($profit > 60) {
                    $sale_price = $orderItem->price + 60;
                }
                if ($want_count > 0) {
                    $sale_price = $sale_price + $base_price * $want_count / 600;
                }
            } else if ($base_discount >= 30 && $base_discount < 35) {
                $sale_price = $price * .45;
                $profit = $sale_price - $orderItem->price;
                if ($profit > 55) {
                    $sale_price = $orderItem->price + 55;
                }
                if ($want_count > 0) {
                    $sale_price = $sale_price + $base_price * $want_count / 700;
                }
            } else if ($base_discount >= 35 && $base_discount < 40) {
                $sale_price = $price * .5;
                $profit = $sale_price - $orderItem->price;
                if ($profit > 50) {
                    $sale_price = $orderItem->price + 50;
                }
                if ($want_count > 0) {
                    $sale_price = $sale_price + $base_price * $want_count / 800;
                }
            } else if ($base_discount >= 40 && $base_discount < 45) {
                $sale_price = $price * .55;
                $profit = $sale_price - $orderItem->price;
                if ($profit > 45) {
                    $sale_price = $orderItem->price + 45;
                }
                if ($want_count > 0) {
                    $sale_price = $sale_price + $base_price * $want_count / 900;
                }
            } else if ($base_discount >= 45 && $base_discount < 50) {
                $sale_price = $price * .6;
                $profit = $sale_price - $orderItem->price;
                if ($profit > 40) {
                    $sale_price = $orderItem->price + 40;
                }
                if ($want_count > 0) {
                    $sale_price = $sale_price + $base_price * $want_count / 1000;
                }
            } else if ($base_discount >= 50 && $base_discount < 60) {
                $sale_price = $price * .7;
                $profit = $sale_price - $orderItem->price;
                if ($profit > 30) {
                    $sale_price = $orderItem->price + 30;
                }
                if ($want_count > 0) {
                    $sale_price = $sale_price + $base_price * $want_count / 1100;
                }
            } else if ($base_discount >= 60 && $base_discount < 70) {
                $sale_price = $price * .8;
                $profit = $sale_price - $orderItem->price;
                if ($profit > 20) {
                    $sale_price = $orderItem->price + 20;
                }
                if ($want_count > 0) {
                    $sale_price = $sale_price + $base_price * $want_count / 1200;
                }
            } else {
                $sale_price = $price * .9;
                $profit = $sale_price - $orderItem->price;
                if ($profit > 10) {
                    $sale_price = $orderItem->price + 10;
                }
                if ($want_count > 0) {
                    $sale_price = $sale_price + $base_price * $want_count / 1300;
                }
            }
            if ($title == '轻微污渍') {
                $sale_price = $sale_price * 1.16;
            }
            if ($title == '轻微划线') {
                $sale_price = $sale_price * 1.16;
            }
            if ($title == '轻微笔记') {
                $sale_price = $sale_price * 1.16;
            }
            if ($title == '轻微泛黄') {
                $sale_price = $sale_price * 1.16;
            }
            if ($title == '封套丢失') {
                $sale_price = $sale_price * 1.1;
            }
            // 轻微
            $qw = mb_substr_count($title, '轻微');
            $tybq = mb_substr_count($title, '贴有标签');
            $gyyz = mb_substr_count($title, '盖有印章');
            $ftds = mb_substr_count($title, '封套丢失');
            $qw = $qw + $tybq + $gyyz + $ftds;
            if ($qw > 1) {
                $sale_price = $sale_price * (1 - $qw * 0.05);
            }
            // 较重 降价
            $jz = mb_substr_count($title, '较重');
            if ($jz > 0) {
                $sale_price = $sale_price * (1 - $jz * 0.15);
            }
            // 最近已售10条sku记录
            $sold_skus = BookSku::where('book_id', $orderItem->book_id)->where('book_version_id', $orderItem->book_version_id)->where('level', BookSku::LEVEL_60)
                ->where('status', BookSku::STATUS_SOLD)->orderByDesc('sold_at')->take(10)->get();
            // 评价售卖价格
            if (count($sold_skus) > 0) {
                $avg_price = $sold_skus->avg->price;
                if (floatval($avg_price) > 0) {
                    if (floatval($avg_price) > floatval($sale_price)) {
                        $sale_price = $avg_price * .8 + $sale_price * .2;
                    } else {
                        $sale_price = $avg_price * .2 + $sale_price * .8;
                    }
                }
            }
            if ($want_count > 0) {
                $highest_price = 0;
                if ($prices && $prices->dd_new_price > 0 && $prices->dd_new_price > $highest_price) {
                    $highest_price = $prices->dd_new_price;
                } else if ($prices && $prices->amz_new_price > 0 && $prices->amz_new_price > $highest_price) {
                    $highest_price = $prices->amz_new_price;
                } else if ($prices && $prices->jd_new_price > 0 && $prices->jd_new_price > $highest_price) {
                    $highest_price = $prices->jd_new_price;
                } else if ($prices && $prices->bc_new_price > 0 && $prices->bc_new_price > $highest_price) {
                    $highest_price = $prices->bc_new_price;
                }
                if ($highest_price > 0 && $sale_price > $highest_price) {
                    $sale_price = $highest_price * .95;
                }
            }
        } else {
            $sale_price = 10;
        }
        return number_format($sale_price, 2);
    }
}
