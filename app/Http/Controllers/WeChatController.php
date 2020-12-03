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
use App\Services\OrderService3;
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
use ErrorException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class WeChatController extends Controller
{
    protected $app, $payment, $orderService;

    public function __construct(Application $app, WxPayment $payment, OrderService3 $orderService)
    {
        $this->app = $app;
        $this->payment = $payment;
        $this->orderService = $orderService;
    }

    // 微信支付回调函数
    public function paymentNotify()
    {
        Log::info('wechatNotify get');
        $response = $this->payment->handlePaidNotify(function ($message, $fail) {
            Log::info('wechatNotify: ' . json_encode($message));
            // 使用通知里的 "微信支付订单号" 或者 "商户订单号" 去自己的数据库找到订单
            $order = Order::with('user')->with('books')->where('no', $message['out_trade_no'])->first();

            if (!$order || $order->paid_at) { // 如果订单不存在 或者 订单已经支付过了
                return true; // 告诉微信，我已经处理完了，订单没找到，别再通知我了
            }

            if ($message['return_code'] === 'SUCCESS') { // return_code 表示通信状态，不代表支付状态
                // 用户是否支付成功
                if (array_get($message, 'result_code') === 'SUCCESS') {
                    $order->paid_at = now();
                    $order->payment_method = Order::PAYMENT_WECHAT;
                    $order->sale_status = Order::SALE_STATUS_PAID;
                    $order->payment_no = $message['transaction_id'];
                    $order->save();
                    event(new OrderPaid($order));
                } elseif (array_get($message, 'result_code') === 'FAIL') {
                    $order->paid_at = '';
                    $order->save();
                }
            } else {
                return $fail('通信失败，请稍后再通知我');
            }

            return true; // 返回处理完成
        });
        return $response;
    }

    public function oauthCallback()
    {
        Log::info('oauthCallback = ' . request()->all());
    }

    // 用户登录
    function createOrUpdateUser($openId)
    {
        $fu = $this->app->user->get($openId);

        Log::info('createOrUpdateUser ' . json_encode($fu, JSON_UNESCAPED_UNICODE));

        $unionId = isset($fu['unionid']) ? $fu['unionid'] : '';
        if (is_null($unionId) || empty($unionId)) {
            return;
        }
        $user = User::where('union_id', $unionId)->first();
        if ($user) {
            // 更新用户关注状态
            $subscribe = isset($fu['subscribe']) ? $fu['subscribe'] : 0;
            $user->subscribe = isset($fu['subscribe']) ? $fu['subscribe'] : 0;
            if ($subscribe != 0) {
                $user->mp_open_id       = $openId;
                $user->subscribe_scene  = isset($fu['subscribe_scene']) ? $fu['subscribe_scene'] : '';
                $user->subscribe_time   = isset($fu['subscribe_time']) ? $fu['subscribe_time'] : '';
                $user->union_id         = isset($fu['unionid']) ? $fu['unionid'] : '';
                $user->province     = isset($fu['province']) ? $fu['province'] : '';
                $user->city         = isset($fu['city']) ? $fu['city'] : '';
            }
            $user->save();
        } else {
            Log::info('createOrUpdateUser create new user');
            $user = new User();
            $user->mp_open_id       = $openId;
            $user->nickname         = isset($fu['nickname']) ? $fu['nickname'] : '';
            $user->sex          = isset($fu['sex']) ? $fu['sex'] : '';
            $user->avatar       = isset($fu['headimgurl']) ? $fu['headimgurl'] : '';
            $user->subscribe    = isset($fu['subscribe']) ? $fu['subscribe'] : '';
            $user->subscribe_scene  = isset($fu['subscribe_scene']) ? $fu['subscribe_scene'] : '';
            $user->subscribe_time   = isset($fu['subscribe_time']) ? $fu['subscribe_time'] : '';
            $user->union_id     = isset($fu['unionid']) ? $fu['unionid'] : '';
            $user->province     = isset($fu['province']) ? $fu['province'] : '';
            $user->city         = isset($fu['city']) ? $fu['city'] : '';
            $user->qr_scene     = isset($fu['qr_scene']) ? $fu['qr_scene'] : '';
            $user->qr_scene_str = isset($fu['qr_scene_str']) ? $fu['qr_scene_str'] : '';
            $user->save();

            Log::info('createOrUpdateUser new user=' . $user->id . ' qr_scene=' . $user->qr_scene);

            // 新用户发现金券
            GiveCouponsJob::dispatch($user);
        }
        file_get_contents('https://huiliuyu.com/wx-api/send_share_image/' . $user->id);
    }

    public function serve()
    {
        try {
            $this->app->server->push(function ($message) {
                WxMsg::create([
                    'body' => $message
                ]);
                $user_openid = $message['FromUserName'];
                if ($message['MsgType'] == 'text' && $message['Content'] == "巴拉巴拉小魔仙") {
                    $url = env('APP_URL');
                    return "{$url}/inbound";
                } else if ($message['MsgType'] == 'text' && $message['Content'] == "band-hly-code !@") {
                    $url = env('APP_URL');
                    return "{$url}/inbound/band_hly_code";
                } else if ($message['MsgType'] == 'text' && 0 === strpos($message['Content'], '收取')) {
                    $book_id = mb_substr($message['Content'], 2, strlen($message['Content']));
                    if (is_numeric($book_id)) {
                        $book = Book::find($book_id);
                        if (floatval($book->price) == 0) {
                            return '《' . $book->name . '》的价格未确定，不能开放收取';
                        }
                        if ($book) {
                            $user = User::where('mp_open_id', $user_openid)->first();
                            if ($user->id == 1 || $user->id == 3) {
                                $book->can_recover = 1;
                                $book->admin_user_id = $user->id;
                                $book->save();
                                return '已开始收取《' . $book->name . '》';
                            } else {
                                return '用户不存在';
                            }
                        } else {
                            return '书不存在';
                        }
                    }
                    return '收取失败';
                } else if ($message['MsgType'] == 'text' && 0 === strpos($message['Content'], '不收')) {
                    $book_id = mb_substr($message['Content'], 2, strlen($message['Content']));
                    if (is_numeric($book_id)) {
                        $book = Book::find($book_id);
                        if ($book) {
                            $book->can_recover = 0;
                            $book->save();
                            $sale_items = SaleItem::where('book_id', $book_id)->get();
                            $sale_items->each(function ($item) {
                                $item->can_recover = 0;
                                $item->save();
                            });
                            return '不收《' . $book->name . '》这本书了';
                        }
                    }
                    return '不收失败';
                } else if ($message['MsgType'] == 'text' && is_numeric($message['Content'])) {
                    $getReminder = Cache::get('reminder_' . $user_openid . '_' . $message['Content']);
                    $getCart = Cache::get('cart_' . $user_openid . '_' . $message['Content']);
                    if ($getReminder) {
                        Cache::forget('reminder_' . $user_openid . '_' . $message['Content']);
                        $user = User::where('mp_open_id', $user_openid)->first();
                        $book = Book::select('name')->find($message['Content']);
                        ReminderItem::where('user_id', $user->id)->where('book_id', $message['Content'])->delete();
                        CartItem::where('user_id', $user->id)->where('book_id', $message['Content'])->delete();
                        return '你已取消《' . $book->name . '》的到货提醒';
                    } else if ($getCart) {
                        Cache::forget('cart_' . $user_openid . '_' . $message['Content']);
                        $user = User::where('mp_open_id', $user_openid)->first();
                        $book = Book::select('name')->find($message['Content']);
                        CartItem::where('user_id', $user->id)->where('book_id', $message['Content'])->delete();
                        return '你已取消《' . $book->name . '》的到货提醒';
                    } else {
                        return new Transfer();
                    }
                } else if ($message['MsgType'] == 'text' && ($message['Content'] == '取消' || $message['Content'] == '取消订单' || $message['Content'] == '订单')) {
                    return '取消卖书订单：
卖书下单成功后，顺丰会在上门之前联系你，如需取消，请和顺丰说“下错订单，不用上门取件”，该订单则自动作废取消。如果顺丰已把书取走，则无法取消。

更改上门取件时间：
订单生效后，卖书预约的上门取件时间无法更改，你可以按上诉方式先取消订单，再重新扫码下个新订单。


取消买书订单：
买书订单在出库之前均可在“商店-我的-订单”中取消。

查看订单信息：
可在“商店-我的-订单”中查看买书/卖书订单详情。';
                } else if ($message['MsgType'] == 'text' && ($message['Content'] == '发货' || $message['Content'] == '快递')) {
                    return '每天下午5点中通来揽件发货，发货后非偏远地区一般隔一天，第三天能到哈。';
                } else if ($message['MsgType'] == 'text' && ($message['Content'] == '抽奖')) {
                    return '请点击菜单栏「答疑-抽奖机」，进入抽奖~';
                } else if ($message['MsgType'] == "event" && $message['EventKey'] == 'ERSHOU_KEFU') {
                    $url = env('APP_URL');
                    return "关于卖书：

1.为什么有些书显示不收？
严重破损、残缺、污渍、影响阅读的书我们不收。
<a href=\"{$url}/wechat/review_standard\">详情</a>

2.卖书时需要注意？
自己如有纸箱可自己包好，没有的话，快递小哥联系你时，可以提醒对方带纸箱。

3.卖书时需要填回流鱼收件地址吗？
我们预约快递上门时，已填好我们的收件地址，你只需填好自己的信息即可。

4.收书服务覆盖哪些地区？
全国。


关于买书：

1.买书有哪些品相可选？
品相有：全新、上好、中等。
<a href=\"{$url}/wechat/level_desc\">详情</a>

2.二手书的卫生问题？
所有书籍都必须经过清理翻新，以及图书臭氧消毒机一小时以上的消毒处理，才能上架出售。

3.发货时间？
每天下午3点前的订单，会在当天发货，下午3点后的订单会次日发货。";
                } else if ($message['MsgType'] == 'event' && $message['Event'] == 'subscribe') {
                    GetUserWechatInfoJob::dispatch($user_openid)->onQueue('high');
                    //$this->createOrUpdateUser($user_openid);

                    $user = User::where('mp_open_id', $user_openid)->first();

                    $url = env('APP_URL');
                    if($user){
                        // GetUserWechatInfoJob::dispatch($user_openid)->onQueue('high');
                        $this->createOrUpdateUser($user_openid);
                        return "hello，你来啦！

我们是二手循环书店「回流鱼」

你可以在这买到经我们翻新消毒的二手好书。

也可以把手头闲置的二手书卖给我们。

转发你的卡，邀请好友一起领现金券，买书更划算。
<a href=\"{$url}/wechat/myCoupons\">点击去邀请</a>

希望每一本好书都能被再次阅读！

卖书/买书，请到 <a href=\"{$url}/wechat/shop\">商店</a>";
                    }else{
                        // GetUserWechatInfoJob::dispatch($user_openid)->onQueue('high');
                        $this->createOrUpdateUser($user_openid);
                        return "hello，你来啦！

我们是二手循环书店「回流鱼」，你可以在这买到经我们翻新、消毒、塑封的二手好书，也可以把手头闲置旧书卖给我们。

初次相见，希望你能先了解我。
<a href=\"https://dwz.cn/c4BbPXRT\">点这里了解「回流鱼」</a>

我们给所有朋友都准备了现金券，转发你的卡邀请好友一起领券，买书更划算哦。
<a href=\"https://huiliuyu.com/wechat/myCoupons\">领取现金券</a>

希望每一本好书都能被再次阅读！
卖书/买书，请到 <a href=\"{$url}/wechat/shop\">商店</a>";
                    }
                } else if ($message['MsgType'] == 'event' && $message['Event'] == 'unsubscribe') {
                    $user = User::where('mp_open_id', $user_openid)->first();
                    Log::info('==unsubscribe');
                    if ($user) {
                        Log::info('用户 ' . $user->id . ' 取关了');
                        $user->subscribe = 0;
                        $user->save();
                    }
                    return "";
                } else {
                    return "";
                }
            });
        } catch (InvalidArgumentException $e) { }

        return $this->app->server->serve();
    }

    public function ui()
    {
        $wx_user = session('wechat.oauth_user.default'); // 拿到授权用户资料
        $user = $this->fetchUser($wx_user);
        if (empty($user)) {
            //            return view('layouts.scan');
            $user_id = 0;
            $tags = "";
            $url = env('APP_URL');
            $coupon = "";
            return view('layouts.app', compact('user_id', 'tags', 'url', 'coupon'));
        }
        $user_id = $user->id;
        $tags = $user->tags->sortByDesc('pivot.created_at')->pluck('name')->toArray();
        $tags = join(',', $tags);
        $coupon = Coupon::where('user_id', $user_id)->where('name', '七夕节包邮券')->first();
        $url = env('APP_URL');
        return view('layouts.app', compact('user_id', 'tags', 'coupon', 'url'));
    }

    public function users()
    {
        $openid = request('openid');
        if (!$openid) {
            $users_arr = $this->app->user->list();
        } else {
            $users_arr = $this->app->user->list($openid);
        }
        $users = $users_arr['data']['openid'];
        $next_openid = isset($users_arr['next_openid']) ? $users_arr['next_openid'] : 'no-result';
        DispatchGetUserWechatInfoJob::dispatch($users);
        return response()->json([
            'next_openid' => $next_openid
        ]);
        //        $openid = request('openid');
        //        return $this->app->user->list($openid);
    }

    public function images()
    {
        $offset = request('offset');
        if (is_null($offset) || empty($offset)) {
            return $this->app->material->list('image', 0, 10);
        } else {
            return $this->app->material->list('image', $offset, 10);
        }
    }

    public function getUserWechatInfo($users)
    {
        collect($users)->each(function ($openId) {
            GetUserWechatInfoJob::dispatch($openId)->delay(now()->addSecond(rand(0, 600)));
        });
    }

    public function material()
    {
        return $this->app->material->list('news');
    }

    public function user($openId)
    {
        return $this->app->user->get($openId);
    }

    public function buttons()
    {
        $buttons = [
            [
                "type" => "view",
                "name" => "买书",
                "url" => env('APP_URL') . "/wechat/shop"
            ],
            [
                "type" => "view",
                "name" => "卖书",
                "url" => env('APP_URL') . "/wechat/scan"
            ],
            [
                "name" => "领劵",
                "sub_button" => [
                    [
                        "type" => "view",
                        "name" => "了解我们",
                        "url" => "https://dwz.cn/c4BbPXRT"
                    ],
                    [
                        "type" => "view",
                        "name" => "领现金劵",
                        "url" => env('APP_URL') . "/wechat/myCoupons"
                    ],
                    [
                        "type" => "view",
                        "name" => "规则",
                        "url" => env('APP_URL') . "/wechat/qa"
                    ],
                    [
                        "type" => "click",
                        "name" => "客服",
                        "key" => "ERSHOU_KEFU"
                    ]
                ],
            ],
        ];
        $this->app->menu->create($buttons);
    }

    public function getIndustry()
    {
        return $this->app->template_message->getIndustry();
    }

    public function reviewStandard()
    {
        return view('wx.reviewStandard');
    }

    public function qa()
    {
        $index = is_null(request('index')) ? 0 : request('index');
        return view('wx.qa', compact('index'));
    }

    public function levelDesc()
    {
        return view('wx.levelDesc');
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

    public function config()
    {
        $url = request('url');
        $config = $this->app->jssdk->setUrl(env('APP_URL') . '/wechat/' . $url)->buildConfig([
            'checkJsApi', 'scanQRCode', 'openCard', 'chooseWXPay', 'onMenuShareAppMessage', 'onMenuShareTimeline',
            'openAddress', 'getLatestAddress'
        ], false);
        Log::info($config);

        return $config;
    }

    public function shareAddressConfig()
    {
        $wx_user = session('wechat.oauth_user.default');
        if (!$wx_user) {
            return response()->json([
                'msg' => '',
                'code' => 500
            ]);
        }
        $accessToken = $wx_user->token->access_token;
        $config = $this->payment->jssdk->shareAddressConfig($accessToken);
        Log::info($config);

        return $config;
    }

    public function searchBookByIsbn()
    {
        $isbn = request('isbn');
        Log::info("search isbn=" . $isbn);
        $book = Book::where('isbn', $isbn)->first();
        return $book;
    }

    public function banBook()
    {
        $user_id = request('user');
        $book_id = request('book');

        if (!empty($user_id) && !empty($book_id)) {
            $book = Book::select('subjectid', 'isbn')->find($book_id);

            return UserBanBook::create([
                'user_id'   => $user_id,
                'book_id'   => $book_id,
                'isbn'      => $book->isbn,
                'subjectid' => $book->subjectid
            ]);
        }

        return response()->json(['msg' => '失败', 'code' => 500]);
    }


    public function getBooksFromShelf()
    {
        $wx_user = session('wechat.oauth_user.default'); // 拿到授权用户资料
        $user = $this->fetchUser($wx_user);

		$books = $user->on_shelf_books;

		$isbns = [];
		$uniques = [];

		// 书籍去重
		if ($books) {
            foreach($books as $b) {

                if (!in_array($b->isbn, $isbns)) {
                    $isbns[] = $b->isbn;
                    $uniques[] = $b;
                }

            }
        }

		return $uniques;

    }



    public function addBookToShelf()
    {
        $isbn = request('isbn');
        if (is_null($isbn)) {
            return response()->json([
                'msg' => "扫码有误，再来一次",
                'code' => 500
            ]);
        }
        $wx_user = session('wechat.oauth_user.default'); // 拿到授权用户资料
        $user = $this->fetchUser($wx_user);
        $record = BookShelf::where('user_id', $user->id)->where('isbn', $isbn)->first();
        if ($record) {
            return response()->json([
                'msg' => "书架上已经有这本书了",
                'code' => 500
            ]);
        }
        $book = Book::where('isbn', $isbn)->first();
        if (!$book) {
            $p = PendingBook::create([
                'user_id' => $user->id,
                'isbn' => $isbn,
                'reason' => PendingBook::REASON_NO_ISBN
            ]);
            FetchBookFromDouban::dispatch($p);

            return response()->json([
                'msg' => '鼓起勇气再扫一次',
                'code' => 500
            ]);
        }
        BookShelf::create([
            'isbn' => $isbn,
            'user_id' => $user->id,
            'book_id' => $book->id
        ]);

        return $book;
    }

    public function removeBookFromShelf()
    {
        $isbn = request('isbn');
        $wx_user = session('wechat.oauth_user.default'); // 拿到授权用户资料
        $user = $this->fetchUser($wx_user);
        BookShelf::where('user_id', $user->id)->where('isbn', $isbn)->delete();

        return response()->json(['msg' => 'success']);
    }

    public function getUserSaleBalance()
    {
        $wx_user = session('wechat.oauth_user.default'); // 拿到授权用户资料
        $user = $this->fetchUser($wx_user);
        $ws = Wallet::where([
            'user_id' => $user->id,
            'status' => Wallet::STATUS_SUCCESS,
            'type' => Wallet::TYPE_SALE_BOOK
        ])->get();

        return $ws->sum->amount;
    }

    // 卖书袋添加图书
    public function addBookForRecover()
    {
        $isbn = request('isbn');
        $isbn = preg_replace('/-/i', '', $isbn);
        if (is_null($isbn) || strlen($isbn) < 13) {
            return response()->json([
                'msg' => '扫码有问题，再试一次',
                'code' => 500
            ]);
        }
        $wx_user = session('wechat.oauth_user.default'); // 拿到授权用户资料
        $user = $this->fetchUser($wx_user);
        Log::info('user ' . $user->id . ' add book ' . $isbn . ' for recover.');

        // 不可收的书如果超过100本
        // 可收的书最高值150
        $count0 = SaleItem::where('user_id', $user->id)
            ->where('can_recover', 0)
            ->count();

        $count1 = SaleItem::where('user_id', $user->id)
            ->where('can_recover', 1)
            ->count();

        // 看看是不是hlycode
        if (strstr($isbn, 'hly')) {
            Log::info("addBookForRecover hly_code=" . $isbn);
            $sku = BookSku::where('hly_code', $isbn)->first();
            if ($sku) {
                $book = Book::withCount('for_sale_skus')
                    ->withCount('reminders')
                    ->where('id', $sku->book_id)
                    ->first();

                $record = SaleItem::where('user_id', $user->id)
                    ->where('book_sku_id', $sku->id)
                    ->first();

                if ($record && $record->show == 1) {
                    return response()->json([
                        'msg' => "同样的书一次只能卖一本哦！",
                        'code' => 500
                    ]);
                } else if ($record && $record->show == 0) {
                    return response()->json([
                        'msg' => "回流鱼暂时不收这本书",
                        'code' => 500
                    ]);
                }

                if ($this->canRecover2($book, $user)) {
                    // 卖书袋可回收上限 150 本
                    if ($count1 < 150) {
                        SaleItem::create([
                            'isbn'      => $book->isbn,
                            'user_id'   => $user->id,
                            'book_id'   => $sku->book_id,
                            'book_sku_id'       => $sku->id,
                            'remind_count'      => $book->reminders_count,
                            'sale_sku_count'    => $book->for_sale_skus_count,
                            'can_recover'       => 1,
                            'show'      => 1,
                            'level'     => BookSku::LEVEL_1
                        ]);
                    } else {
                        return response()->json([
                            'msg' => "图书装满了，请提交订单后再继续扫码",
                            'code' => 500
                        ]);
                    }

                } else {
                    if ($count0 < 100) {
                        SaleItem::create([
                            'isbn'      => $book->isbn,
                            'user_id'   => $user->id,
                            'book_id'   => $book->id,
                            'book_sku_id'   => $sku->id,
                            'can_recover'   => 0,
                            'remind_count'  => $book->reminders_count,
                            'sale_sku_count' => $book->for_sale_skus_count,
                            'show'      => $book->admin_user_id > 0 ? 0 : 1,
                            'level'     => BookSku::LEVEL_1
                        ]);
                    }

                }

                return SaleItem::where([
                    'user_id'       => $user->id,
                    'book_id'       => $sku->book_id,
                    'book_sku_id'   => $sku->id
                ])->with('book')
                    ->with(['recover_reports' => function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                }])->first();

            } else {

                return response()->json([
                    'msg' => "回流鱼还没有这样的编码",
                    'code' => 500
                ]);
            }
        } else {
            $book = Book::withCount('for_sale_skus')
                ->withCount('reminders')
                ->where('isbn', $isbn)
                ->first();
            if ($book && !is_numeric($book->price)) {
                Tools::convertPrice($book);
            }

            // 书的价格缺失
            if ($book && (!is_numeric($book->price) || floatval($book->price) == 0)) {

                return response()->json([
                    'msg' => '书的价格不确定，请更新！',
                    'book' => $book,
                    'code' => 501
                ]);
            }

            if (!$book) {
                CrawlingByWebPageISBN::dispatch($isbn)->delay(now()->addSecond());
                return response()->json([
                    'msg' => '该书数据更新中，请过5秒钟重试！',
                    'code' => 500
                ]);
            }

            if ($book && (is_null($book->updated_at) || $book->update_at < Carbon::now()->subDay(30))) {
                CrawlingByWebPageSubjectId::dispatch($book->subjectid)->delay(now()->addSecond());
            }

            $record = SaleItem::where('user_id', $user->id)->where('isbn', $isbn)->first();
            if ($record && $record->show == 1) {
                return response()->json([
                    'msg' => "同样的书一次只能卖一本哦！",
                    'code' => 500
                ]);
            } else if ($record && $record->show == 0) {
                return response()->json([
                    'msg' => "回流鱼暂时不收这本书",
                    'code' => 500
                ]);
            }

            // 查看该用户过去3天的售卖记录
            $orderItem = OrderItem::whereHas('order', function ($q) use ($user) {
                $q->where('type', Order::ORDER_TYPE_RECOVER)
                    ->where('user_id', $user->id)
                    ->where('recover_status', '<>', Order::RECOVER_STATUS_CANCEL);
            })->where('book_id', $book->id)->where('created_at', '>=', now()->subDays(3))->first();
            if ($orderItem) {
                Log::info('user ' . $user->id . ' 3天内卖过一本');
                return response()->json([
                    'msg' => "你近期卖过这本书，要不换一本试试？",
                    'code' => 500
                ]);
            }

            // 书找到了
            if ($this->canRecover2($book, $user)) {
                // 卖书袋可回收上限 150 本
                if ($count1 < 150) {

                    SaleItem::create([
                        'isbn' => $isbn,
                        'user_id' => $user->id,
                        'book_id' => $book->id,
                        'can_recover' => 1,
                        'remind_count' => $book->reminders_count,
                        'sale_sku_count' => $book->for_sale_skus_count,
                        'show' => 1,
                        'level' => BookSku::LEVEL_1
                    ]);
                } else {

                    return response()->json([
                        'msg' => "图书装满了，请提交订单后再继续扫码",
                        'code' => 500
                    ]);
                }

            } else {
                // 卖书袋不可回收上限 100 本
                if ($count0 < 100) {

                    SaleItem::create([
                        'isbn' => $isbn,
                        'user_id' => $user->id,
                        'book_id' => $book->id,
                        'can_recover' => 0,
                        'remind_count' => $book->reminders_count,
                        'sale_sku_count' => $book->for_sale_skus_count,
                        'show' => $book->admin_user_id > 0 ? 0 : 1,
                        'level' => BookSku::LEVEL_1
                    ]);
                }

            }

            return SaleItem::where([
                'user_id' => $user->id,
                'book_id' => $book->id,
            ])->with('book')
                ->with(['recover_reports' => function ($q) use ($user) {
                    $q->where('user_id', $user->id);
            }])->first();
        }
    }

    public function getRecoverBooksWithoutCounting()
    {
        $wx_user = session('wechat.oauth_user.default'); // 拿到授权用户资料
        $user = $this->fetchUser($wx_user);
        $items = SaleItem::where('user_id', $user->id)->with('book')->with(['recover_reports' => function ($query) use ($user) {
            $query->where('user_id', $user->id);
        }])->orderBy('created_at', 'desc')->get();

        return $items;
    }

    public function getBooksForRecover()
    {
        $wx_user = session('wechat.oauth_user.default'); // 拿到授权用户资料
        $user = $this->fetchUser($wx_user);
        if (empty($user)) {
            return reponse()->json([
                'msg' => '用户不存在',
                'code' => 500
            ]);
        }

        $items = SaleItem::where('user_id', $user->id)
            ->with('book')
            ->with(['recover_reports' => function ($query) use ($user) {
                $query->where('user_id', $user->id);
            }])->orderBy('created_at', 'desc')->get();

        foreach ($items as $item) {
            if (!$this->canRecover2($item->book, $user)) {

                $item->update([
                    'can_recover' => 0,
                    'show' => $item->book->admin_user_id > 0 ? 0 : 1
                ]);
            } else {
                $item->update([
                    'can_recover' => 1,
                    'show' => 1
                ]);
            }
        }

        return $items;
    }

    public function updateBookPrice()
    {
        $book_id = request('book');
        $price = request('price');
        $currency = request('currency');
        if (empty($book_id) || empty($price) || empty($currency)) {
            return response()->json([
                'msg' => '数据不完整',
                'code' => 500
            ]);
        }
        $book = Book::find($book_id);
        if (!$book) {
            return response()->json([
                'msg' => '图书数据不存在',
                'code' => 500
            ]);
        }
        if (is_numeric($book->price)) {
            return response()->json([
                'msg' => '价格不需要更新',
                'code' => 500
            ]);
        }
        $original_price = $price;
        if ($currency == '美元') {
            $original_price = 'USD' . $price;
            $price = $price * 6.5;
        } else if ($currency == '英镑') {
            $original_price = 'GBP' . $price;
            $price = $price * 8.5;
        } else if ($currency == '欧元') {
            $original_price = 'EUR' . $price;
            $price = $price * 7.2;
        } else if ($currency == '日元') {
            $original_price = 'JPY' . $price;
            $price = $price / 17;
        } else if ($currency == '新台币') {
            $original_price = 'NT' . $price;
            $price = $price / 5;
        } else if ($currency == '港币') {
            $original_price = 'HKD' . $price;
            $price = $price / 1.1;
        }
        $book->update([
            'price' => $price,
            'original_price' => $original_price
        ]);
        return $book;
    }

    public function addRecoverReport()
    {
        $type = request('type');
        $reason = request('reason');
        $book_id = request('book_id');
        $wx_user = session('wechat.oauth_user.default'); // 拿到授权用户资料
        $user = $this->fetchUser($wx_user);
        $report = RecoverReport::create([
            'user_id' => $user->id,
            'book_id' => $book_id,
            'type' => $type,
            'reason' => $reason
        ]);

        return $report;
    }

    public function getLastUsedAddress()
    {
        $wx_user = session('wechat.oauth_user.default'); // 拿到授权用户资料
        $user = $this->fetchUser($wx_user);

        return $user->addresses()->first();
    }

    public function removeBookFromRecover()
    {
        $isbn = request('isbn');
        $wx_user = session('wechat.oauth_user.default'); // 拿到授权用户资料
        $user = $this->fetchUser($wx_user);
        SaleItem::where('user_id', $user->id)->where('isbn', $isbn)->delete();

        return response()->json(['msg' => 'success']);
    }

    // 生成用户卖书订单
    public function createRecoverOrder()
    {
        $address_id = request('address');
        $add = UserAddress::find($address_id);
        if (
            strstr($add->province, '西藏') || strstr($add->province, '新疆') ||
            strstr($add->province, '黑龙江') || strstr($add->province, '吉林') || strstr($add->province, '辽宁')
        ) {
            return response()->json([
                'msg' => '你所在的地区暂不收书',
                'code' => 500
            ]);
        }
        $time = \request('time');
        $wx_user = session('wechat.oauth_user.default'); // 拿到授权用户资料
        $user = $this->fetchUser($wx_user);
        $saleItems = SaleItem::with('book')->where('user_id', $user->id)
            ->where('can_recover', 1)
            ->get();
        Log::info('time=' . $time);

        return $this->orderService->recoverOrderStore($user, $address_id, $time, $saleItems);

    }

    public function getRecoverOrderBooks($no)
    {
        $order = Order::with('address')->where('no', $no)->first();

        return $order->books;
    }

    public function editRecoverOrder($no)
    {
        $order = Order::with('address')->where('no', $no)->first();

        return view('wx.editRecoverOrder', compact('order'));
    }

    public function updateRecoverOrder()
    {
        return $this->orderService->recoverOrderUpdate(request('form'));
    }

    public function cancelRecoverOrder($no)
    {
        $order = Order::with('address')->where('no', $no)->first();
        if ($order) {
            $order->update([
                'recover_status' => -1
            ]);
        }

        return $order;
    }

    public function myOrders()
    {
        $wx_user = session('wechat.oauth_user.default'); // 拿到授权用户资料
        $user = $this->fetchUser($wx_user);
        $orders = $user->orders()->with('items.book')->orderBy('id', 'desc')->get();

        return view('wx.myOrders', compact('orders'));
    }

    public function shop()
    {
        $wx_user = session('wechat.oauth_user.default'); // 拿到授权用户资料
        $user = $this->fetchUser($wx_user);
        $cartItemsCount = CartItem::where('user_id', $user->id)->count();
        $tags = $user->tags->pluck('name');
        $shudans = Shudan::where('open', true)->get();

        return view('wx.shop', compact('cartItemsCount', 'tags', 'shudans'));
    }

    public function getUser()
    {
        $wx_user = session('wechat.oauth_user.default'); // 拿到授权用户资料
        if (!$wx_user) {
            return response()->json([]);
        }
        $user = $this->fetchUser($wx_user);
        if (!$user) {
            return '';
        }
        /** 不记录用户机型
        $user_agent = request()->userAgent();
        $user->update([
            'user_agent' => $user_agent
        ]);
         */

        return $user;
    }

    public function getUserByOpenId($openId)
    {
        return User::where('mp_open_id', $openId)->first();
    }

    public function getUserTags()
    {
        $wx_user = session('wechat.oauth_user.default'); // 拿到授权用户资料
        $user = $this->fetchUser($wx_user);
        if (empty($user)) {
            return [];
        }

        $user_tags = Cache::remember('cache_user_tags', 5, function() use ($user) {
            return $user->tags->sortByDesc('pivot.created_at')->pluck('name');
        });

        return $user_tags;
    }

    // 图书详情页
    public function getBook($isbn)
    {
        $book = Book::where('isbn', $isbn)
            ->with('latest_sold_sku')
            ->with('for_sale_skus.user')
            ->with('for_sale_skus.book_version')
            ->first();

        // 更新用户的到货提醒数
        $reminder_count = ReminderItem::where('isbn', $isbn)->count();
        $book->reminder_count = $reminder_count;
        $book->save();
        if ($this->canRecover2($book)) {
            event(new BookRecoverPriceRisen($book));
        }

        // 更新SKU状态
        $sale_sku_count = BookSku::where('book_id', $book->id)
            ->where('status', BookSku::STATUS_FOR_SALE)
            ->count();

        // 品相上好
        $good_skus = BookSku::where('book_id', $book->id)
            ->where('level', BookSku::LEVEL_80)
            ->whereIn('status', [BookSku::STATUS_FOR_SALE, BookSku::STATUS_READY_TO_GO, BookSku::STATUS_RETREADING])
            ->get();
        $good_sku_max_price = 0;
        foreach ($good_skus as $sku) {
            if ($sku->price > $good_sku_max_price){
                $good_sku_max_price = $sku->price;
            }
        }

        // 更新所有新书到最高价
        foreach ($good_skus as $sku) {
            $sku->price = $good_sku_max_price;
            $sku->save();
        }

        // 在售的上好
        $on_sale_good_sku_count = BookSku::where('book_id', $book->id)
            ->where('level', BookSku::LEVEL_80)
            ->where('status', BookSku::STATUS_FOR_SALE)
            ->count();
        if ($sale_sku_count<3 && $on_sale_good_sku_count==0){
            $ready_sku = BookSku::where('book_id', $book->id)
                ->where('level', BookSku::LEVEL_80)
                ->where('status', BookSku::STATUS_READY_TO_GO)
                ->first();
            if ($ready_sku) {
                $ready_sku->status = BookSku::STATUS_FOR_SALE;
                $ready_sku->save();
            }
        }else if($sale_sku_count>3 && $on_sale_good_sku_count>1){
            $on_sale_good_skus = BookSku::where('book_id', $book->id)
                ->where('level', BookSku::LEVEL_80)
                ->where('status', BookSku::STATUS_FOR_SALE)
                ->take($on_sale_good_sku_count-1)
                ->get();
            foreach ($on_sale_good_skus as $sku){
                $sku->status = BookSku::STATUS_READY_TO_GO;
                $sku->save();
            }
        }else if($sale_sku_count==0) {
            // 上架一个上好
            $ready_good_sku = BookSku::where('book_id', $book->id)
                ->where('level', BookSku::LEVEL_80)
                ->where('status', BookSku::STATUS_READY_TO_GO)
                ->first();
            if ($ready_good_sku){
                $ready_good_sku->status = BookSku::STATUS_FOR_SALE;
                $ready_good_sku->save();
                $sale_sku_count++;
            }
        }

        // 品相中等
        $normal_skus = BookSku::where('book_id', $book->id)
            ->where('level', BookSku::LEVEL_60)
            ->whereIn('status', [BookSku::STATUS_FOR_SALE, BookSku::STATUS_READY_TO_GO, BookSku::STATUS_RETREADING])
            ->get();
        $normal_sku_max_price = 0;
        foreach ($normal_skus as $sku) {
            if ($good_sku_max_price>0 && $sku->price > $good_sku_max_price){
                $sku->price = $sku->price * .9;
                $sku->save();
            }
            if ($sku->price>$normal_sku_max_price){
                $normal_sku_max_price = $sku->price;
            }
        }

        // 更新轻微污渍，轻微泛黄到最高价
        foreach ($normal_skus as $sku) {
            if ($sku->title == '轻微污渍' || $sku->title == '轻微泛黄') {
                $sku->price = $normal_sku_max_price;
                $sku->save();
            }
        }

        if($sale_sku_count==0) {
            // 上架一个中等
            $ready_normal_sku = BookSku::where('book_id', $book->id)
                ->where('level', BookSku::LEVEL_60)
                ->where('status', BookSku::STATUS_READY_TO_GO)->first();
            if ($ready_normal_sku){
                $ready_normal_sku->status = BookSku::STATUS_FOR_SALE;
                $ready_normal_sku->save();
                $sale_sku_count++;
            }
        }

        // 品相新
        $new_skus = BookSku::where('book_id', $book->id)
            ->where('level', BookSku::LEVEL_100)
            ->whereIn('status', [BookSku::STATUS_FOR_SALE, BookSku::STATUS_READY_TO_GO, BookSku::STATUS_RETREADING])
            ->get();
        $new_sku_max_price = 0;
        foreach ($new_skus as $sku) {
            if ($sku->price > $new_sku_max_price){
                $new_sku_max_price = $sku->price;
            }
        }

        if ($new_sku_max_price<$good_sku_max_price) {
            $new_sku_max_price = $good_sku_max_price + $book->price * .1;
        }

        // 更新所有新书到最高价
        foreach ($new_skus as $sku) {
            $sku->price = $new_sku_max_price;
            $sku->save();
        }
        // 在售的全新
        $on_sale_new_sku_count = BookSku::where('book_id', $book->id)
            ->where('level', BookSku::LEVEL_100)
            ->where('status', BookSku::STATUS_FOR_SALE)
            ->count();
        if ($sale_sku_count<3 && $on_sale_new_sku_count==0){
            $ready_sku = BookSku::where('book_id', $book->id)
                ->where('level', BookSku::LEVEL_100)
                ->where('status', BookSku::STATUS_READY_TO_GO)
                ->first();
            if ($ready_sku) {
                $ready_sku->status = BookSku::STATUS_FOR_SALE;
                $ready_sku->save();
            }
        }else if($sale_sku_count>3 && $on_sale_new_sku_count>1){
            $on_sale_new_skus = BookSku::where('book_id', $book->id)
                ->where('level', BookSku::LEVEL_100)
                ->where('status', BookSku::STATUS_FOR_SALE)
                ->take($on_sale_new_sku_count-1)
                ->get();
            foreach ($on_sale_new_skus as $sku){
                $sku->status = BookSku::STATUS_READY_TO_GO;
                $sku->save();
            }
        }else if($sale_sku_count==0) {
            // 上架一个全新
            $ready_new_sku = BookSku::where('book_id', $book->id)
                ->where('level', BookSku::LEVEL_100)
                ->where('status', BookSku::STATUS_READY_TO_GO)
                ->first();
            if ($ready_new_sku){
                $ready_new_sku->status = BookSku::STATUS_FOR_SALE;
                $ready_new_sku->save();
            }
        }

        $bookDetail = Book::where('isbn', $isbn)
            ->with('latest_sold_sku')
            ->with('for_sale_skus.user')
            ->with('for_sale_skus.book_version')
            ->first();

        return $bookDetail;
    }

    // 图书评论
    public function getBookComments($isbn) {
        $wx_user = session('wechat.oauth_user.default');
        $user = $this->fetchUser($wx_user);
        $user_id = $user->id;
        $page = request('page') ? intval(request('page')) : 1;
        $offset = ($page - 1) * 20;
        $book = Book::where('isbn', $isbn)
            ->with('latest_sold_sku')
            ->with('for_sale_skus.user')
            ->with('for_sale_skus.book_version')
            ->first();
        if (empty($book)) {
            return response()->json([
               'status' => false,
                'message' => '图书不存在'
            ]);
        }

        $book_id = $book->id;
        $comments = ShudanComment::with([
            'comment.user',
            'shudan',
            'shudan_zan_users',
            'shudan_zan_status' => function($query) use ($user_id) {
                $query->where('user_id', $user_id);
            }
            ])
            ->where('book_id', $book_id)
            ->get();
        $openComments = $comments->filter(function($c){
            return $c->comment && $c->comment->open;
        });
        // 分页显示
        $count = $openComments->count();
        $openComments = $openComments->splice($offset, 10);
        $dataComments = [];
        if ($openComments) {
            foreach ($openComments as $comment) {
                $dataComments[] = $comment;
            }
        }

        return response()->json([
            'status'    => true,
            'message'   => '图书评论、推荐',
            'data'      => $dataComments,
            'total'     => $count
        ]);


    }

    public function getBookById()
    {
        return Book::with('versions')->find(request('id'));
    }

    public function getBookVersions($bookId)
    {
        return BookVersion::where('book_id', $bookId)->orderByDesc('updated_at')->get();
    }

    public function getBookVersion()
    {
        return BookVersion::with('book')->find(request('id'));
    }

    public function viewBook()
    {
        $book = Book::find(request('book'));
        if (!$book) {
            return;
        }
        $book_skus = BookSku::where('book_id', $book->id)
            ->where('status', BookSku::STATUS_FOR_SALE)
            ->get();

        $price = intval($book->price);
        if (count($book_skus) > 0) {
            $min_price = $book_skus->min('price');
        } else {
            $min_price = $price;
        }

        $sale_discount = 0;
        if ($price > 0) {
            $sale_discount = intval($min_price * 100 / $book->price);
        }

        $sale_sku_count = count($book_skus);
        // 图书在售数量
        $book->sale_sku_count = $sale_sku_count;

        // 更新图书 最低折扣和 折扣价格
        $book->sale_discount        = $sale_discount;
        $book->sale_discount_price  = $min_price;

        $book->save();

        if(request('start') && request('end')){
            $cha = strtotime(request('end')) - strtotime(request('start'));
            $string = ['s'=>request('start'),'e'=>request('end')];
        }else{
            $cha = 1;
            $string = null;
        }
        // 当天用户查看书籍的记录
        $viewBook = ViewBook::where('user_id', request('user'))
            ->where('book_id', request('book'))
            ->where('source', request('source'))
            ->where('created_at', '>', date('Y-m-d') . ' 00:00:00')
            ->first();

        if ($viewBook) {
            $viewBook->increment('second', $cha);
            if($string){
                if($viewBook->content){
                    $content = json_decode($viewBook->content,true);
                }else{
                    $content = [];
                }
                array_push($content,$string);
                $viewBook->content = json_encode($content);
                $viewBook->save();
            }
        } else {
            $viewBook = ViewBook::create([
                "book_id" => request('book'),
                "user_id" => request('user'),
                "source" => request('source'),
                'second' => $cha
            ]);
        }

        return response()->json([$min_price, $sale_discount]);
    }

    public function getUserReminders()
    {
        $wx_user = session('wechat.oauth_user.default'); // 拿到授权用户资料
        $user = $this->fetchUser($wx_user);
        if (empty($user)) {
            return [];
        }
        $reminders = ReminderItem::where('user_id', $user->id)
            ->with('book.for_sale_skus')
            ->latest()
            ->get();

        return $reminders;
    }

    public function openTimes()
    {
        $isbn = request('isbn');
        $user_id = request('user');
        $reminder = ReminderItem::where('isbn', $isbn)->where('user_id', $user_id)->first();
        if (!$reminder) {
            return response()->json(['msg' => 'Not Found', 'code' => 500]);
        }
        $reminder->open_times = $reminder->open_times + 1;
        $reminder->save();

        return $reminder;
    }

    public function getCartRecommends()
    {
        $wx_user = session('wechat.oauth_user.default'); // 拿到授权用户资料
        $user = $this->fetchUser($wx_user);
        $bookSubjectid = DB::select("select books.subjectid as subjectid from books join cart_items on cart_items.book_id=books.id where cart_items.user_id=? and cart_items.deleted_at is null", [$user->id]);
        if (count($bookSubjectid) == 0) {
            return [];
        }
        Log::info('is_array $bookSubjectid ' . is_array($bookSubjectid));
        Log::info('$bookSubjectid = ' . json_encode($bookSubjectid));
        $arr = array_map(function ($b) {
            return $b->subjectid;
        }, $bookSubjectid);
        Log::info('$bookSubjectid = ' . json_encode($arr));
        $bookRelationSubjectids = DB::table('books_relation')->whereIn('subjectid', $arr)->get();
        if (count($bookRelationSubjectids) == 0) {
            return [];
        }
        Log::info('$bookRelationSubjectids = ' . json_encode($bookRelationSubjectids));
        $subjectids = "";
        foreach ($bookRelationSubjectids as $b) {
            $subjectids = $subjectids . $b->subjectids;
        }
        $resultArray = array_filter(explode(',', $subjectids));
        $resultArray = array_diff($resultArray, $arr);
        Log::info('$subjectids = ' . json_encode($resultArray));
        $books = Book::with('for_sale_skus')->where('sale_sku_count', '>', 0)
            ->whereIn('subjectid', $resultArray)->take(10)->get();
        if (count($arr) != 0 && count($books) == 0) {
            // 推荐同作者的
            $authors = Book::whereIn('subjectid', $arr)->get()->pluck('author')->toArray();
            Log::info('authors count=' . count($authors));
            if (count($authors) == 1) {
                $books = Book::with('for_sale_skus')->whereNotIn('subjectid', $arr)->where('sale_sku_count', '>', 0)
                    ->where('author', 'like', $authors[0] . '%')->take(10)->get();
            } else if (count($authors) >= 2) {
                $books = Book::with('for_sale_skus')->whereNotIn('subjectid', $arr)->where('sale_sku_count', '>', 0)
                    ->where(function ($q) use ($authors) {
                        $q->where('author', 'like', $authors[0] . '%')->orWhere('author', 'like', $authors[1] . '%');
                    })->take(10)->get();
            } else {
                $books = [];
            }
        }

        return $books;
    }

    public function getUserCartItems()
    {
        $wx_user = session('wechat.oauth_user.default'); // 拿到授权用户资料
        $user = $this->fetchUser($wx_user);
        if (empty($user)) {
            return [];
        }
        // 1. 再更新已有的CarItem的Sku，无货的Sku置为0
        // 2. 先从Reminder创建CartItem
        $cartItems = CartItem::where('user_id', $user->id)->with('book_sku', 'book.for_sale_skus')->latest()->get();
        // 用户购物袋里的书已经卖出去了，但是这本书还有别的品相，自动帮用户选一本
        $cartItems->each(function ($item) {
            $sku = $item->book_sku;
            // 检查是否存在于某个卖单中
            if ($sku && $sku->ifnew != 1) {
                $orders = $sku->orders()->where([
                    ['type', '=', Order::ORDER_TYPE_SALE],
                    ['sale_status', '<>', Order::SALE_STATUS_CANCEL],
                    ['closed', '=', false],
                    ['paid_at', '<>', null]
                ])->get();
                if ($orders->count() > 0) {
                    $sku->status = BookSku::STATUS_SOLD;
                    $sku->save();
                }
                if ($sku->status != BookSku::STATUS_FOR_SALE && count($item->book->for_sale_skus) > 0) {
                    $item->update([
                        'selected' => 1,
                        'book_sku_id' => $item->book->for_sale_skus[0]->id
                    ]);
                }
            }elseif ($sku && $sku->ifnew == 1){
                if($sku->status != BookSku::STATUS_FOR_SALE){
                    $item->update(['selected' => 0 ]);
                }elseif ($sku->stock == 0){
                    $item->update(['selected' => 0]);
                    $sku->update(['status' => BookSku::STATUS_SOLD]);
                }
            }else{
                $item->update(['selected' => 0]);
            }
        });
        $reminders = ReminderItem::with('book.for_sale_skus')->where('user_id', $user->id)->get();
        $data = collect([]);
        $reminders->each(function ($r) use ($cartItems, $user, $data) {
            if (count($r->book->for_sale_skus) > 0) {
                $ci = CartItem::withTrashed()->where('user_id', $user->id)
                    ->where('book_id', $r->book_id)->where('source', 'auto')->latest()->first();
                $ciE = CartItem::where('user_id', $user->id)
                    ->where('book_id', $r->book_id)->where('source', 'auto')->latest()->first();
                // 现在购物车中没有 且 购物车中没有已删除的 或 有3天前已删除的
                if (!$ciE && (!$ci || ($ci && now()->subDays(3)->gt($ci->created_at)))) {
                    $data->push([
                        'user_id' => $user->id,
                        'book_id' => $r->book_id,
                        'book_sku_id' => $r->book->for_sale_skus[0]->id,
                        'amount' => 1,
                        'selected' => 1,
                        'source' => 'auto',
                        'created_at' => now()->toDateTimeString(),
                        'updated_at' => now()->toDateTimeString()
                    ]);
                }
            }
        });
        $data = $data->toArray();
        Log::info('getUserCartItems:\n' . json_encode($data));
        CartItem::insert($data);

        return CartItem::where('user_id', $user->id)->with('book_sku', 'book.for_sale_skus')->latest()->get();
    }

    public function getBooksByTag($tag)
    {
        $page = request('page') ? request('page') : 1;
        $f_tag = Tag::where('name', $tag)->first();
        Log::info('getBooksByTag tag=' . $tag);
        Log::info('getBooksByTag user=' . request('user'));
        if ($tag == '猜你喜欢') {
            $user_id = request('user');
            if (!empty($user_id)) {
                //                $r = $this->recommendSubjectids($user_id);
                //                $r = array_filter($r);
                $r = [];
                if (count($r) != 0) {
                    $books = Book::select('id', 'isbn', 'name', 'subtitle', 'author', 'rating_num', 'cover_replace', 'press', 'publish_year', 'binding', 'category', 'price', 'sale_discount', 'type', 'group1', 'group2', 'group3')
                        ->with('for_sale_skus.user')->with('for_sale_skus.book_version')->where('sale_sku_count', '>', 0)
                        ->whereIn('subjectid', $r)
                        ->orderByRaw(DB::raw('field(subjectid, ' . implode(",", $r) . ")"))
                        ->paginate(20);
                } else {
                    $books = Cache::remember('books_page_' . $page, 10, function () {
                        return Book::select('id', 'isbn', 'name', 'subtitle', 'author', 'rating_num', 'cover_replace', 'press', 'publish_year', 'binding', 'category', 'price', 'sale_discount', 'type', 'group1', 'group2', 'group3')
                            ->with('for_sale_skus.user')->with('for_sale_skus.book_version')->where('sale_sku_count', '>', 0)
                            ->orderByDesc('reminder_count')->paginate(20);
                    });
                }
            } else {
                $books = Cache::remember('books_page_' . $page, 10, function () {
                    return Book::select('id', 'isbn', 'name', 'subtitle', 'author', 'rating_num', 'cover_replace', 'press', 'publish_year', 'binding', 'category', 'price', 'sale_discount', 'type', 'group1', 'group2', 'group3')
                        ->with('for_sale_skus.user')->with('for_sale_skus.book_version')->where('sale_sku_count', '>', 0)
                        ->orderByDesc('updated_at')->paginate(20);
                });
            }
        } else if ($tag == '新上架') {
            $books = Cache::remember('books_page_' . $page, 10, function () {
                return Book::select('id', 'isbn', 'name', 'subtitle', 'author', 'rating_num', 'cover_replace', 'press', 'publish_year', 'binding', 'category', 'price', 'sale_discount', 'type', 'group1', 'group2', 'group3')
                    ->with('for_sale_skus.user')->with('for_sale_skus.book_version')->where('sale_sku_count', '>', 0)
                    ->orderByDesc('updated_at')->paginate(20);
            });
        } else if ($tag == '豆瓣8.5+') {
            $books = Cache::remember('books_tag_' . $f_tag->id . '_page_' . $page, 10, function () {
                return Book::select('id', 'isbn', 'name', 'subtitle', 'author', 'rating_num', 'cover_replace', 'press', 'publish_year', 'binding', 'category', 'price', 'sale_discount', 'type', 'group1', 'group2', 'group3')
                    ->with('for_sale_skus.user')->with('for_sale_skus.book_version')
                    ->where('rating_num', '>=', 8.5)->where('sale_sku_count', '>', 0)
                    ->orderByDesc('reminder_count')->paginate(20);
            });
        } else if ($tag == '特价市集') {
            $books = Cache::remember('books_cheap_page_' . $page, 10, function () {
                $ids = BookSku::select('book_id')->where('price', '<', 6)->where('status', BookSku::STATUS_FOR_SALE)->get()->pluck('book_id')->toArray();
                $ids = array_filter($ids);
                return Book::select('id', 'isbn', 'name', 'subtitle', 'author', 'rating_num', 'cover_replace', 'press', 'publish_year', 'binding', 'category', 'price', 'sale_discount', 'type', 'group1', 'group2', 'group3')
                    ->with('for_sale_skus.user')->with('for_sale_skus.book_version')
                    ->whereIn('id', $ids)->orderByDesc('updated_at')->paginate(20);
            });
        } else {
            $books = Cache::remember('books_tag_' . $f_tag->id . '_page_' . $page, 5, function () use ($tag) {
                return Book::select('id', 'isbn', 'name', 'subtitle', 'author', 'rating_num', 'cover_replace', 'press', 'publish_year', 'binding', 'category', 'price', 'sale_discount', 'type', 'group1', 'group2', 'group3')
                    ->with('for_sale_skus.user')->with('for_sale_skus.book_version')->where('sale_sku_count', '>', 0)
                    ->where(function ($q) use ($tag) {
                        $q->orWhere('group1', $tag)->orWhere('group2', $tag)->orWhere('group3', $tag);
                    })
                    ->orderByDesc('reminder_count')->paginate(20);
            });
        }

        return $books;
    }

    function recommendSubjectids($user_id = 0)
    {
        Log::info('recommend user=' . $user_id);
        $cached_recommend_set = Cache::get('user_' . $user_id . '_recommend_set');
        if ($cached_recommend_set) {
            // 回流鱼在售集合 H，缓存一分钟
            $H = Cache::remember('hly_sale_books', 10, function () {
                return Book::select('subjectid')->where('sale_sku_count', '>', 0)->get()->pluck('subjectid')->toArray();
            });
            // 用户反馈集合 F
            $F = UserBanBook::select('subjectid')->where('user_id', $user_id)->get()->pluck('subjectid')->toArray();
            $H = array_diff($H, $F);
            $a = array_diff($cached_recommend_set, $F);
            $r = array_intersect($H, $a);
            $d = array_diff($H, $a);

            return array_merge($r, $d);
        }

        // 搜索记录 SS，时效性
        $search_ids = UserSearchHistory::select('book_ids', 'subjectids')->where('user_id', $user_id)->orderByDesc('updated_at')
            ->where('updated_at', '>', now()->subDays(7))
            ->take(2)->get();
        $search_book_ids = [];
        foreach ($search_ids as $us) {
            $search_book_ids = array_merge($search_book_ids, explode(',', $us->book_ids));
        }
        Log::info('$search_book_ids count=' . count($search_book_ids));
        // 已购书籍 G
        //        $buy_book_ids = Cache::remember('user_'.$user_id.'_buy_books', 60, function() use ($user_id){
        //            return OrderItem::select('book_id')->whereHas('order', function($q) use ($user_id){
        //                $q->where('user_id', $user_id)->where('sale_status', Order::SALE_STATUS_COMPLETE);
        //            })->orderByDesc('id')->take(20)->get()->pluck('book_id')->toArray();
        //        });
        $buy_book_ids = OrderItem::select('book_id')->whereHas('order', function ($q) use ($user_id) {
            $q->where('user_id', $user_id)->where('type', Order::ORDER_TYPE_SALE);
        })->orderByDesc('id')->take(100)->get()->pluck('book_id')->toArray();
        Log::info('$buy_book_ids count=' . count($buy_book_ids));
        // 购物车书籍 W
        //            $cart_book_ids = Cache::remember('user_'.$user_id.'_cart_books', 10, function() use ($user_id) {
        //                return CartItem::select('book_id')->where('user_id', $user_id)->get()->pluck('book_id')->toArray();
        //            });
        $cart_book_ids = CartItem::select('book_id')->where('user_id', $user_id)->get()->pluck('book_id')->toArray();
        Log::info('$cart_book_ids count=' . count($cart_book_ids));
        // 到货提醒 D
        //            $reminder_book_ids = Cache::remember('user_'.$user_id.'_reminder_books', 10, function() use ($user_id){
        //                return ReminderItem::select('book_id')->where('user_id', $user_id)
        //                    ->orderByDesc('id')->take(50)->get()->pluck('book_id')->toArray();
        //            });
        $reminder_book_ids = ReminderItem::select('book_id')->where('user_id', $user_id)
            ->orderByDesc('id')->take(200)->get()->pluck('book_id')->toArray();
        Log::info('$reminder_book_ids count=' . count($reminder_book_ids));
        // 浏览数据 L
        //            $view_book_ids = Cache::remember('user_'.$user_id.'_view_books', 10, function() use ($user_id){
        //                return ViewBook::select('book_id')->where('user_id', $user_id)
        //                    ->orderByDesc('id')->take(100)->get()->pluck('book_id')->toArray();
        //            });
        $view_book_ids = ViewBook::select('book_id')->where('user_id', $user_id)
            ->orderByDesc('id')->take(200)->get()->pluck('book_id')->toArray();
        Log::info('$view_book_ids count=' . count($view_book_ids));
        // 标签Tag
        $user = User::with('tags')->find($user_id);
        $tags_book_ids = [];
        if ($user) {
            $user_tags = $user->tags()->get()->reverse()->take(3)->pluck('name')->toArray();
            if (count($user_tags) > 0) {
                $tags_book_ids = Book::select('id')->whereIn('group1', $user_tags)
                    ->where('sale_sku_count', '>', 0)->orderByDesc('reminder_count')->take(100)->get()->pluck('id')->toArray();
            }
        }
        Log::info('$tags_book_ids count=' . count($tags_book_ids));
        // 用户兴趣集合 B=SS ∪ G ∪ W ∪ D ∪ L ∪ Tag
        $B = array_merge($search_book_ids, $view_book_ids, $cart_book_ids, $buy_book_ids, $reminder_book_ids, $tags_book_ids);
        $B = array_filter($B);
        Log::info('B count=' . count($B));

        if (count($B) == 0) {
            return [];
        }

        // 豆瓣的推荐集合 T
        $book_subjectids = Book::select('subjectid')->whereIn('id', $B)
            ->orderByRaw(DB::raw('field(subjectid, ' . implode(",", $B) . ")"))
            ->get()->pluck('subjectid')->toArray();
        $book_subjectids = array_filter($book_subjectids);
        $douban_subjectids = DB::table('books_relation')->whereIn('subjectid', $book_subjectids)
            ->orderByRaw(DB::raw('field(subjectid, ' . implode(",", $book_subjectids) . ")"))
            ->select('subjectids')->get()->pluck('subjectids')->toArray();

        $T = [];
        foreach ($douban_subjectids as $subjectids) {
            $ids = explode(',', $subjectids);
            foreach ($ids as $id) {
                if (!empty($id)) {
                    array_push($T, $id);
                }
            }
        }
        $T = array_filter($T);
        if (count($T) < 100) {
            $other_douban_subjectids = DB::table('books_relation')->whereIn('subjectid', $T)
                ->orderByRaw(DB::raw('field(subjectid, ' . implode(",", $T) . ")"))
                ->select('subjectids')->get()->pluck('subjectids')->toArray();
            foreach ($other_douban_subjectids as $subjectids) {
                $ids = explode(',', $subjectids);
                foreach ($ids as $id) {
                    if (!empty($id)) {
                        array_push($T, $id);
                    }
                }
            }
        }
        Log::info('T count=' . count($T));
        //TODO 系列集合 S
        $S = [];
        // 回流鱼在售集合 H，缓存一分钟
        $H = Cache::remember('hly_sale_books', 10, function () {
            return Book::select('subjectid')->where('sale_sku_count', '>', 0)->get()->pluck('subjectid')->toArray();
        });
        // 用户反馈集合 F
        $F = UserBanBook::select('subjectid')->where('user_id', $user_id)->get()->pluck('subjectid')->toArray();

        $s = [];
        foreach ($search_ids as $si) {
            $s = array_merge($s, explode(',', $si->subjectids));
        }
        $a = Cache::remember('user_' . $user_id . '_recommend_set', 10, function () use ($s, $T, $S) {
            return array_slice(array_merge($s, $T, $S), 0, 3000);
        });
        $a = array_diff($a, $F);
        $r = array_intersect($H, $a);
        $d = array_diff($H, $a);

        return array_slice(array_merge($r, $d), 0, 3000);
    }

    public function getShudanList()
    {
        return Shudan::orderByDesc('updated_at')->paginate();
    }

    public function getOpenedShudan()
    {
        // 今日特价
        $tejia = Cache::remember('cache_tejia', 5 , function() {
            return Shudan::with('coverItems.book')
                ->where('open', true)
                ->where('id', 1)
                ->get();
        });

        // 其他书单
        $shudans = Cache::remember('cache_other_shudans', 5, function() {
            return Shudan::with('coverItems.book')
                ->where('open', true)
                ->where('id', '<>', 1)
                ->orderByDesc('created_at')
                ->get();
        });


        $rets = collect($tejia)->concat($shudans);
        $shudans = [];

        // cover_items 字段没用,字段内容多
        foreach($rets as $ret) {
            $collect = collect($ret);
            $collect->forget('cover_items');
            $shudans[] = $collect;
        }

        return $shudans;
    }

    public function getShudan($shudan)
    {
        return Shudan::find($shudan);
    }

    // 20190821 书单添加多少人推荐
    // type: 1推荐
    public function getShudanBooks($shudan)
    {
        $page = request('page') ? request('page') : 1;
        $offset = ($page - 1) * 20;
        Log::info('getShudanBooks page=' . $page);
		$shudan_key = 'shudan_items_' . $shudan . '_page_' . $page;

		// 登录用户信息
		$wx_user = session('wechat.oauth_user.default');
		$user = $this->fetchUser($wx_user);
		$user_id = $user->id;
		
        //$items = Cache::remember($shudan_key, 1, function () use ($shudan, $user_id) {
            if ($shudan == 1) {
                $items = ShudanComment::with([
                    'book.for_sale_skus',
                    'comment.user',
                    'shudan_zan_users',
                    'shudan_zan_status' => function($query) use ($user_id) {
                        $query->where('user_id', $user_id);
                    },
                ])
                    ->where('shudan_id', $shudan)
                    ->where('type', ShudanComment::TYPE_SHUDAN)
                    ->paginate(20);

                return $items;
            } else {
                $items = ShudanComment::with([
                    'book.for_sale_skus',
                    'comment.user',
                    'shudan_zan_users',
                    'shudan_zan_status' => function($query) use ($user_id) {
                        $query->where('user_id', $user_id);
                    },
                ])
                    ->where('shudan_id', $shudan)
                    ->where('type', ShudanComment::TYPE_SHUDAN)
                    ->get();

                $total = count( $items );
                $next_page = $page + 1;
                $last_page = ceil($total/20);
                if ($next_page <= $last_page) {
                    $next_page_url = "https://huiliuyu.com/wx-api/get_shudan_books/" .$shudan. "?page=" . $next_page;
                } else {
                    $next_page_url = null;
                }
                $last_page_url = "https://huiliuyu.com/wx-api/get_shudan_books/" .$shudan. "?page=" . $last_page;
                
                $filtered = $items->filter(function($item){
                    return $item->book->sale_sku_count > 0;
                });

                $others = $items->filter(function($item){
                    return $item->book->sale_sku_count == 0;
                });

                $shudanBooks = $filtered->concat($others);

                $data = $shudanBooks->splice($offset, 20);

                $ret = [
                    'data'      => $data,
                    'total'     => $total,
                    'current_page'  => intval($page),
                    'last_page'     => $last_page,
                    'next_page_url' => $next_page_url,
                    'last_page_url' => $last_page_url
                ];

                return response()->json($ret);

            }


        //});

    }
	
	// 书单虚拟推荐用户
	public function shudanUsers()
	{	
		$expiresAt = Carbon::now()->addMinutes(10);
		$lastMonth = Carbon::now()->subDays(30);

		// 用户存到缓存
		$users = Cache::get('shudan_recommend_users');
		if (!$users) {

			$users = User::where('avatar', '<>', '')
                ->where('nickname', '<>', '')
                ->where('updated_at', '>', $lastMonth)
                ->offset(200)
                ->limit(1000)
                ->get();
			Cache::put('shudan_recommend_users', $users, $expiresAt);

		}

		$shudans = Cache::get('cache_shudans');
		if (!$shudans) {
            $sql = "SELECT shudan_id, count(*) count FROM `shudan_comments` where shudan_id in (select id from shudans where open=1) group by shudan_id";
            $shudans = DB::select($sql);
            Cache::put('cache_shudans', $shudans, $expiresAt);

        }


		$arr = [];
		foreach ($shudans as $dan) {
			$v = [];
			$id = $dan->shudan_id;
			
			// 书单标题
			$shudan = Shudan::find($id);
			$v['shudan_title'] = $shudan->title;
			
			// 书的数量
			$count = $dan->count;
			$v['count_book'] = $count;
			$count_user = 0;
			if ($count < 50) {
				$count_user = $count;
			} else if ($count<200) {
				$count_user = intval($count/2 + 12);
			} else {
				$count_user = intval($count/3 + 22);
			}
			$v['count_user'] = $count_user;
			$user = [];
			if ($count < 500) {
				$user = $users->pluck('avatar')->slice($count, 5);
			} else {
				$start = $count % 500;
				$user = $users->pluck('avatar')->slice($start, 5);
			}
			// 对象转数组
			$uu = [];
			foreach ($user as $u) {
				$uu[] = $u;
			}
			$v['user'] = $uu;
			$arr[$id] = $v;
		}

		return response()->json([
			'status' => true,
			'message' => 'shudanUsers',
			'data' => $arr
		]);
		
	}
	
	// 书单推荐详情页
	public function getShudanComment($sd_comment_id)
	{
		// 登录用户信息
		$wx_user = session('wechat.oauth_user.default');
		$user = $this->fetchUser($wx_user);
		$user_id = $user->id;
		
		$shudan_comment = DB::table('shudan_comments')
			->join('comments', 'shudan_comments.comment_id', '=', 'comments.id')
			->where('shudan_comments.id', $sd_comment_id)
			->where('comments.open', true)
			->first();
			
		// 书的信息
		$book_id = $shudan_comment->book_id;
		$book = Book::find($book_id);
		
		
		// 推荐人的信息
		$comment_user = User::find($shudan_comment->user_id);
		
		// 该推荐所有点赞的用户
		$comment_id = $shudan_comment->comment_id;
		$dianzan_users = ShudanDianzan::with('user')
			->where('comment_id', $comment_id)
			->where('status', 1)
			->get();
		
		// 自己是否点过赞
		$dianzan = ShudanDianzan::where('user_id', $user_id)
			->where('comment_id', $comment_id)
			->where('status', 1)
			->first();
			
		return response()->json([
			'status' 	=> true,
			'book' 		=> $book,
			'shudan_comment' 	=> $shudan_comment,
			'dianzan_users' 	=> $dianzan_users,
			'dianzan_status' 	=> $dianzan ? true : false,
			'comment_user' 		=> $comment_user,
		]);
	}
	
	// 书单留言点赞
	public function commitShudanDianzan($comment_id)
	{
		// 登录用户信息
        $wx_user = session('wechat.oauth_user.default');
        $user = $this->fetchUser($wx_user);
        $user_id = $user->id;

        // 1 推荐 评论
        // 2 订单
        $type = request('type') ? request('type') : 1;
        if (!in_array($type, [1, 2])) {
            return response()->json([
                'status'    => false,
                'message'   => '类型错误',
                'msg'       => '类型错误'
            ]);
        }
		
		// 用户该留言是否有过点赞记录
		$dianzan = ShudanDianzan::where('comment_id', $comment_id)
			->where('user_id', $user_id)
			->first();
		if ($dianzan) {
			$status = $dianzan->status;
			$dianzan->status = !$status;
			$dianzan->save();
			
			return response()->json([
				'status' 	=> true,
				'message' 	=> '操作成功',
				'msg' 		=> !$status,
				'dianzan' 	=> $dianzan
			]);
		} else {
			$dianzan = new ShudanDianzan();
			$dianzan->comment_id = $comment_id;
			$dianzan->user_id = $user_id;
			$dianzan->save();
			
			return response()->json([
				'status' 	=> true,
				'message' 	=> '操作成功',
				'msg' 		=> '点赞成功',
				'dianzan' 	=> $dianzan,
			]);
		}

	}


    // 向书单推荐一本书
	// 评论和推荐书单共用一张表 shucan_comments
    public function addBookToShudan($shudan_id)
    {
		// 登录用户信息
        $wx_user = session('wechat.oauth_user.default');
        $user = $this->fetchUser($wx_user);
        $user_id = $user->id;

        // 书籍
        $book_id = request('book_id');
        $book = Book::find($book_id);
        if (!$book) {
            Log::info('add book to shudan fail. book id=' . $book_id);
            return response()->json([
                'status' => false,
                'message' => '书籍不存在'
            ]);
        }
		// 类型和评分
		$type = request('type') ? request('type') : 1;
		if (! in_array($type, [1, 2])) {
			return response()->json([
				'status' => false,
				'message' => '参数错误'
			]);
		}
		$star = request('star') ? request('star') : 0;
		if (! in_array($star, [0, 1, 2, 3, 4, 5])) {
            return response()->json([
                'status' => false,
                'message' => '数值错误'
            ]);
		}

        // 推荐理由
        $body = trim( request('reason') );
        if (!$body) {
            return response()->json([
                'status' => false,
                'message' => '内容不能为空'
            ]);
        }

        // 评论
        $comment = new Comment();
        $comment->user_id = $user_id;
        $comment->book_id = $book_id;
        $comment->body = $body;
        $comment->open = 0;
        $comment->save();
        $cid = $comment->id;

        // 1书单推荐   2评论
        $shudan_comment = new ShudanComment();
        $shudan_comment->type       = $type;
        $shudan_comment->star       = $star;
        $shudan_comment->shudan_id  = $shudan_id;
        $shudan_comment->comment_id = $cid;
        $shudan_comment->book_id    = $book_id;
        $shudan_comment->use_cover  = 0;
        $shudan_comment->save();

        $message = '已提交推荐';
        if ($type == 2) {
            $message = '已提交评论';
        }

        return response()->json([
            'status' => true,
            'message' => $message
        ]);

    }

    public function cart()
    {
        $wx_user = session('wechat.oauth_user.default'); // 拿到授权用户资料
        $user = $this->fetchUser($wx_user);
        $cartItems = CartItem::where('user_id', $user->id)->with('book_sku.book')->get();
        $reminders = ReminderItem::where('user_id', $user->id)->with('book')->get();

        return view('wx.cart', compact('cartItems', 'reminders'));
    }

    public function orderInfo()
    {
        $wx_user = session('wechat.oauth_user.default'); // 拿到授权用户资料
        $user = $this->fetchUser($wx_user);
        $cartItems = CartItem::where('user_id', $user->id)->with('book_sku.book')->get();
        $reminders = ReminderItem::where('user_id', $user->id)->with('book')->get();

        return view('wx.orderInfo', compact('cartItems'));
    }

    public function updateCartItem()
    {
        $wx_user = session('wechat.oauth_user.default'); // 拿到授权用户资料
        $user = $this->fetchUser($wx_user);
        $item_id = request('item');
        $book_sku_id = request('sku_id');
        $ci = CartItem::where([
            'user_id' => $user->id,
            'book_sku_id' => $book_sku_id
        ])->first();
        if ($ci) {
            return response()->json([
                'msg' => '购物袋已经有这个品相的书了',
                'code' => 500
            ]);
        }
        $ci = CartItem::find($item_id);
        $ci->book_sku_id = $book_sku_id;
        $ci->save();

        return CartItem::with('book_sku', 'book.for_sale_skus')->find($item_id);
    }

    public function deleteCartItem()
    {
        $item_id = \request('item');
        CartItem::destroy($item_id);

        return response()->json(['msg' => 'success']);
    }

    public function addSkuToCart()
    {
        $sku = BookSku::find(request('sku'));
        $wx_user = session('wechat.oauth_user.default'); // 拿到授权用户资料
        $user = $this->fetchUser($wx_user);
        $cartItem = CartItem::where('user_id', $user->id)->where('book_id', $sku->book_id)
            ->where('book_sku_id', $sku->id)->first();
        if ($cartItem) {
            return response()->json([
                'msg' => '这本书已经在购物车了，请不要重复添加！',
                'code' => 500
            ]);
        }
        $cartItem = CartItem::create([
            'user_id' => $user->id,
            'book_id' => $sku->book_id,
            'book_sku_id' => $sku->id,
            'amount' => 1,
            'source' => request('source') ?? 'no'
        ]);

        return CartItem::with('book_sku', 'book.for_sale_skus')->find($cartItem->id);
    }

    public function getCartItemsCount()
    {
        $wx_user = session('wechat.oauth_user.default'); // 拿到授权用户资料
        $user = $this->fetchUser($wx_user);

        return $user->cart_items->count();
    }

    public function searchBook()
    {
        return view('wx.searchBook');
    }

    public function tags()
    {
        $wx_user = session('wechat.oauth_user.default'); // 拿到授权用户资料
        $user = $this->fetchUser($wx_user);
        $userTags = $user->tags->pluck('name');

        return view('wx.tags', compact('userTags'));
    }

    // 添加用户分类
    public function addUserTag($name)
    {
        $wx_user = session('wechat.oauth_user.default'); // 拿到授权用户资料
        $user = $this->fetchUser($wx_user);
        UpdateUserRecommend::dispatch($user->id)->delay(now()->addSecond());
        //$name = request('tag');
        $tag = Tag::where('name', $name)->first();
        if ($tag) {
            $taggable = Taggable::where('taggable_id', $user->id)
                ->where('taggable_type', User::class)
                ->where('tag_id', $tag->id)->first();
            if (!$taggable) {
                Taggable::create([
                    'taggable_id' => $user->id,
                    'taggable_type' => User::class,
                    'tag_id' => $tag->id
                ]);
            }
        } else {
            /**
            用户不能创建标签
            $tag = Tag::create(['name' => $name]);
            Taggable::create([
                'taggable_id' => $user->id,
                'taggable_type' => User::class,
                'tag_id' => $tag->id
            ]);
             */
        }

        return response()->json(['msg' => 'success']);
    }

    // 删除用户分类
    public function deleteUserTag($name)
    {
        $wx_user = session('wechat.oauth_user.default'); // 拿到授权用户资料
        $user = $this->fetchUser($wx_user);
        UpdateUserRecommend::dispatch($user->id)->delay(now()->addSecond());
        //$name = request('tag');
        $tag = Tag::where('name', $name)->first();
        if ($tag) {
            $taggables = Taggable::where('taggable_id', $user->id)
                ->where('taggable_type', User::class)
                ->where('tag_id', $tag->id)->get();
            if ($taggables) {
                $taggables->each->delete();
            }
        }

        return response()->json(['msg' => 'success']);
    }

    // 修改用户
    public function modifyUserTag()
    {
        $wx_user = session('wechat.oauth_user.default');
        $user = $this->fetchUser($wx_user);
        // 用户操作之前的分类
        $userTags = $user->tags->pluck('name')->toArray();

        // 用户提交的分类
        $modifyTags = request('tag');
        $modifyTags = explode(',', $modifyTags);

        $tags = array_merge($userTags, $modifyTags);
        $tags = array_unique($tags);

        foreach ($tags as $tag) {
            $tag = trim($tag);

            if (in_array($tag, $userTags) && in_array($tag, $modifyTags)) {
                // 这个标签没有修改
            } else if (in_array($tag, $userTags) && !in_array($tag, $modifyTags)) {
                // 删除这个标签
                $this->deleteUserTag($tag);

            } else if (!in_array($tag, $userTags) && in_array($tag, $modifyTags)) {
                // 添加这个标签
                $this->addUserTag($tag);
            }
        }

        return response()->json(['msg' => 'success']);
    }

    public function book($isbn)
    {
        //        $wx_user = session('wechat.oauth_user.default'); // 拿到授权用户资料
        //        $user = $this->fetchUser($wx_user);
        $book = Book::where('isbn', $isbn)->with('for_sale_skus')->first();
        //        $this->getBookLightColor($book);
        //        $cartItemsCount = CartItem::where('user_id', $user->id)->count();

        return $book;
    }

    public function getBookRelations($isbn)
    {
        $book = Book::where('isbn', $isbn)->first();
        $r_ids = [];
        if ($book) {
            $r_ids = Cache::get('book_' . $book->id . "_relations");
            if ($r_ids && count($r_ids) > 0) {
                return Book::with('for_sale_skus')->select('isbn', 'name', 'author', 'cover_replace', 'press', 'rating_num', 'num_raters', 'sale_sku_count', 'category', 'group1', 'group2', 'group3')
                    ->whereIn('subjectid', $r_ids)->where('sale_sku_count', '>', 0)
                    ->orderByRaw(DB::raw('FIND_IN_SET(subjectid, "' . implode(",", $r_ids) . '"' . ")"))
                    ->paginate(10);
            } else {
                $r_ids = [];
            }
            $relation = DB::select("select subjectids from books_relation where subjectid=?", [$book->subjectid]);
            if ($relation) {
                $subjectids = explode(',', $relation[0]->subjectids);
                $r_ids = array_merge($r_ids, $subjectids);
                foreach ($subjectids as $subjectid) {
                    $r = DB::table('books_relation')
                        ->select('subjectids')->where('subjectid', $subjectid)->first();
                    if ($r) {
                        $ids = explode(',', $r->subjectids);
                        $r_ids = array_merge($r_ids, $ids);
                    }
                }
            }
        }
        Log::info('$r_ids count=' . count($r_ids));
        $a_ids = Book::select('subjectid')->where('author', 'like', '%' . $book->author . '%')->where('sale_sku_count', '>', 0)->take(10)->get()
            ->pluck('subjectid')->toArray();
        Log::info('$a_ids count=' . count($a_ids));

        $c_ids = [];
        $c_array = explode(',', $book->category);
        if (count($c_array) > 0) {
            foreach ($c_array as $c) {
                $ids = Book::select('subjectid')->where('category', 'like', '%' . $c . '%')->where('sale_sku_count', '>', 0)->take(10)->get()
                    ->pluck('subjectid')->toArray();
                $c_ids = array_merge($c_ids, $ids);
            }
        }
        Log::info('$c_ids count=' . count($c_ids));
        $r_ids = array_merge($r_ids, $a_ids, $c_ids);
        Log::info('$r_ids count=' . count($r_ids));
        $r_ids = Cache::remember('book_' . $book->id . '_relations', 60, function () use ($r_ids, $book) {
            return array_diff($r_ids, [$book->subjectid]);
        });

        return Book::with('for_sale_skus')->select('isbn', 'name', 'author', 'cover_replace', 'press', 'rating_num', 'num_raters', 'sale_sku_count', 'category', 'group1', 'group2', 'group3')
            ->whereIn('subjectid', $r_ids)->where('sale_sku_count', '>', 0)
            ->orderByRaw(DB::raw('FIND_IN_SET(subjectid, "' . implode(",", $r_ids) . '"' . ")"))
            ->paginate(10);
    }

    public function addBookToReminder()
    {
        $book = Book::find(request('book'));
        if (!$book) {
            Log::info('add book to reminder fail. book id=' . request('book'));
            return;
        }
        // 创建reminder
        $wx_user = session('wechat.oauth_user.default'); // 拿到授权用户资料
        $user = $this->fetchUser($wx_user);
        UpdateUserRecommend::dispatch($user->id)->delay(now()->addSecond());
        $reminder = ReminderItem::where('book_id', request('book'))->where('user_id', $user->id)->first();
        if ($reminder) {
            return response()->json([
                'msg' => '你已经关注这本书了',
                'code' => 500
            ]);
        }
        $reminder = ReminderItem::create([
            'book_id' => $book->id,
            'user_id' => $user->id,
            'isbn' => $book->isbn
        ]);
        // 更新书籍的到货提醒数
        $book->reminder_count = $book->reminder_count + 1;
        $book->save();
        // 不收的策略
        if (!$this->canRecover2($book)) {
            // 告诉魏总，这本书有人想要，但是目前不收
            $this->app->template_message->send([
                'touser' => 'ojrK40dDSJ8bLfFlCkQD0GcV2DhE',
                'template_id' => 'rgch7KVIzLxC7yX0SwH9_HWK6e4VPufqKbbDBGsLww0',
                'url' => env('APP_URL') . '/wechat/book/' . $book->isbn,
                'data' => [
                    'first' => '《' . $book->name . '》有人想要，但是目前我们不收，要不要改为收取',
                    'keyword1' => '《' . $book->name . '》作者：' . $book->author,
                    'keyword2' => '书的ID:' . $book->id,
                    'keyword3' => $book->price . '元',
                    'keyword4' => Carbon::now()->toDateTimeString(),
                    'remark' => '公众号回复：收取' . $book->id
                ]
            ]);
        }
        event(new BookRecoverPriceRisen($book));

        return ReminderItem::with('book.for_sale_skus')->find($reminder->id);
    }

    public function removeBookFromReminder()
    {
        $wx_user = session('wechat.oauth_user.default'); // 拿到授权用户资料
        $user = $this->fetchUser($wx_user);
        UpdateUserRecommend::dispatch($user->id)->delay(now()->addSecond());
        $reminder = ReminderItem::with('book.for_sale_skus')->where('book_id', \request('book'))->where('user_id', $user->id)->first();
        if (!$reminder) {
            return response()->json([
                'msg' => '你没有关注这本书',
                'code' => 500
            ]);
        }
        $reminder->delete();

        return $reminder;
    }

    public function saleInvoice()
    {
        $wx_user = session('wechat.oauth_user.default'); // 拿到授权用户资料
        $user = $this->fetchUser($wx_user);
        $cartItems = CartItem::where('user_id', $user->id)->where('selected', true)->with('book_sku', 'book')->get();
        $address = UserAddress::where('user_id', $user->id)->orderBy('last_used_at', 'desc')->first();
        $walletBalance = Wallet::where([
            'user_id' => $user->id,
            'status' => Wallet::STATUS_SUCCESS
        ])->get()->sum->amount;

        return view('wx.saleInvoice', compact('user', 'cartItems', 'address', 'walletBalance'));
    }

    public function getUserAllAddress()
    {
        $wx_user = session('wechat.oauth_user.default'); // 拿到授权用户资料
        $user = $this->fetchUser($wx_user);

        return $user->addresses;
    }

    public function getUserLatestAddress()
    {
        $wx_user = session('wechat.oauth_user.default'); // 拿到授权用户资料
        $user = $this->fetchUser($wx_user);
        $default_add = UserAddress::where('user_id', $user->id)->where('is_default', 1)->first();
        if ($default_add) {
            return $default_add;
        }

        return UserAddress::where('user_id', $user->id)->orderBy('last_used_at', 'desc')->first();
    }

    public function createUserAddress()
    {
        $form = request('form');
        $wx_user = session('wechat.oauth_user.default'); // 拿到授权用户资料
        $user = $this->fetchUser($wx_user);
        $address_id = isset($form['id']) ? $form['id'] : 0;
        if (isset($form['default']) && intval($form['default']) == 1) {
            DB::update('update user_addresses set is_default=0 where user_id=?', [$user->id]);
        }
        $contact_phone = str_replace([' ', '-', ',', '+'], '', $form['contact_phone']);
        if (intval($address_id) > 0) {
            $userAddress = UserAddress::find($address_id);
            $userAddress->update([
                'province' => $form['province'],
                'city' => $form['city'],
                'district' => $form['district'],
                'address' => $form['address'],
                'contact_name' => $form['contact_name'],
                'contact_phone' => $contact_phone,
                'zip' => isset($form['zip']) ? $form['zip'] : 0,
                'is_default' => isset($form['default']) ? $form['default'] : 0
            ]);
        } else {
            $userAddress = UserAddress::create([
                'user_id' => $user->id,
                'province' => $form['province'],
                'city' => $form['city'],
                'district' => $form['district'],
                'address' => $form['address'],
                'contact_name' => $form['contact_name'],
                'contact_phone' => $contact_phone,
                'zip' => isset($form['zip']) ? $form['zip'] : 0,
                'is_default' => isset($form['default']) ? $form['default'] : 0
            ]);
        }

        return $userAddress;
    }

    public function deleteUserAddress()
    {
        $address = request('address');
        $userAddress = UserAddress::find($address);
        if ($userAddress) {
            $userAddress->delete();
        }

        return $userAddress;
    }

    public function setDefaultAddress()
    {
        $wx_user = session('wechat.oauth_user.default'); // 拿到授权用户资料
        $user = $this->fetchUser($wx_user);
        $address = request('address');
        $userAddress = UserAddress::find($address);
        if ($userAddress) {
            DB::update('update user_addresses set is_default=0 where user_id=?', [$user->id]);
            $userAddress->update([
                'is_default' => 1
            ]);
        }

        return $userAddress;
    }

    public function getUserWalletBalance()
    {
        $wx_user = session('wechat.oauth_user.default'); // 拿到授权用户资料
        $user = $this->fetchUser($wx_user);
        $walletBalance = Wallet::where([
            'user_id' => $user->id,
            'status' => Wallet::STATUS_SUCCESS
        ])->get()->sum->amount;

        return $walletBalance;
    }

    public function getWallets()
    {
        $user_id = request('user');
        if (empty($user_id)) {
            return response()->json([
                'msg' => '用户非法',
                'code' => 500
            ]);
        }
        $wallets = Wallet::where('user_id', $user_id)->orderByDesc('id')->get();

        return $wallets;
    }

    public function getBookSaleSku()
    {
        $isbn = request('isbn');
        if (!$isbn)  return [];
        $skus = BookSku::where('isbn', $isbn)->where('status', BookSku::STATUS_FOR_SALE)->get();

        return $skus;
    }

    public function selectCartItem()
    {
        $item = request('item');
        $selected = request('selected');
        $cartItem = CartItem::where('id', $item)->update([
            'selected' => $selected
        ]);

        return $cartItem;
    }

    public function createSaleOrder()
    {
        $wx_user = session('wechat.oauth_user.default'); // 拿到授权用户资料
        $user = $this->fetchUser($wx_user);
        UpdateUserRecommend::dispatch($user->id)->delay(now()->addSecond());
        $address_id = request('address');
        $coupon_id = request('coupon');
        try{
            $order = $this->orderService->saleOrderStore($user, $address_id, $coupon_id);
            if ($order) {
                Log::info('订单 ' . $order->id . ' 已创建, 费用=' . $order->total_amount);
            } else {
                Log::info('创建订单失败');
                return response()->json([
                    'msg' => '创建订单失败',
                    'code' => 500
                ]);
            }
        }catch (\Exception $e){
            return response()->json([
                'msg' => $e->getMessage(),
                'code' => 500
            ]);
        }
        $result = $this->payment->order->unify([
            'body' => $user->id . ' 买书订单',
            'out_trade_no' => $order->no,
            'total_fee' => $order->total_amount * 100,
            'spbill_create_ip' => '127.0.0.1',
            'notify_url' => env('APP_URL') . '/wechat/payment_notify',
            'trade_type' => 'JSAPI',
            'openid' => $user->mp_open_id,
        ]);
        $config = $this->payment->jssdk->sdkConfig($result['prepay_id']);
        $order->update([
            'prepay_id' => $result['prepay_id']
        ]);
        $config['order_id'] = $order->id;
        $order['config'] = $config;

        return $order;
    }

    public function getSaleOrderWxConfig()
    {
        $wx_user = session('wechat.oauth_user.default'); // 拿到授权用户资料
        $user = $this->fetchUser($wx_user);
        $order = Order::find(request('order'));
        if (is_null($order)) {
            return response()->json([
                'msg' => '微信支付失败，订单不存在',
                'code' => 500
            ]);
        }
        $result = $this->payment->order->unify([
            'body' => $user->id . ' 买书订单',
            'out_trade_no' => $order->no,
            'total_fee' => $order->total_amount * 100,
            'spbill_create_ip' => '127.0.0.1',
            'notify_url' => env('APP_URL') . '/wechat/payment_notify',
            'trade_type' => 'JSAPI',
            'openid' => $user->mp_open_id,
        ]);
        Log::info('getSaleOrderWxConfig order id=' . request('order'));
        Log::info($result);
        $config = $this->payment->jssdk->sdkConfig($result['prepay_id']);
        $order->update([
            'prepay_id' => $result['prepay_id']
        ]);
        $config['order_id'] = $order->id;

        return $config;
    }

    public function paySaleOrderWithWallet()
    {
        $wx_user = session('wechat.oauth_user.default'); // 拿到授权用户资料
        $user = $this->fetchUser($wx_user);
        $order = Order::with('items')->find(request('order'));
        if (is_null($order)) {
            return response()->json([
                'msg' => '支付失败，订单不存在',
                'code' => 500
            ]);
        }
        if ($order->order_id) {
            return response()->json([
                'msg' => '请在主订单中支付',
                'code' => 500
            ]);
        }
        // 是否扣款成功
        $wallet = Wallet::where('user_id', $user->id)->where('order_id', $order->id)->first();
        if ($wallet) {
            return $order;
        }
        $items = $order->allitems;
        $orderTotalAmount = $items->sum->price;
        $orderTotalAmount = $orderTotalAmount * 0.95 + $order->ship_price;
        Log::info("paySaleOrderWithWallet orderTotalAmount=" . $orderTotalAmount);
        // 现金券的逻辑
        $coupon = $order->coupon;
        if ($coupon) {
            $orderTotalAmount = $orderTotalAmount - $coupon->value;
        }
        Log::info("paySaleOrderWithWallet orderTotalAmount sub coupon=" . $orderTotalAmount);
        $userBalance = Wallet::where([
            'user_id' => $user->id,
            'status' => Wallet::STATUS_SUCCESS
        ])->get()->sum->amount;
        Log::info("paySaleOrderWithWallet userBalance=" . $userBalance);
        if ($userBalance < $orderTotalAmount) {
            return response()->json([
                'msg' => '你的余额不足',
                'code' => 500
            ]);
        }
        // 从钱包扣款
        Wallet::create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'type' => Wallet::TYPE_BUY_BOOK,
            'status' => Wallet::STATUS_SUCCESS,
            'amount' => -$orderTotalAmount
        ]);
        $order->total_amount = $orderTotalAmount;
        $order->paid_at = Carbon::now();
        $order->payment_method = Order::PAYMENT_WALLET;
        $order->sale_status = Order::SALE_STATUS_PAID;
        $order->save();
        event(new OrderPaid($order));

        return $order;
    }

    public function getSaleOrderPaymentConfig($id)
    {
        $order = Order::find($id);
        $config = $this->payment->jssdk->sdkConfig($order->prepay_id);
        $config['order_id'] = $order->id;
        return $config;
    }

    public function getSaleOrderPaymentStatus($id)
    {
        $order = Order::with('address', 'items.bookSku', 'items.book')->find($id);
        if (!is_null($order->paid_at)) return $order;
        $result = $this->payment->order->queryByOutTradeNumber($order->no);
        Log::info('getSaleOrderPaymentStatus order id=' . $id);
        Log::info($result);
        // 修改订单状态
        try{
            if ($result['trade_state'] == 'SUCCESS') {
                $order->paid_at = Carbon::now();
                $order->payment_method = Order::PAYMENT_WECHAT;
                $order->sale_status = Order::SALE_STATUS_PAID;
                $order->save();
                event(new OrderPaid($order));
            }
        }catch (\Exception $e){
            return response()->json([
                'code' => 500,
                'msg' => $e->getMessage()
            ]);
        }
        return $order;
    }

    public function getSaleOrderPaymentStatusByNo($no)
    {
        $order = Order::with('address', 'items.bookSku', 'items.book','suborders')->where('no', $no)->first();
        if (!is_null($order->paid_at)) return $order;
        $result = $this->payment->order->queryByOutTradeNumber($order->no);
        Log::info('getSaleOrderPaymentStatus order no=' . $no);
        Log::info($result);
        // 修改订单状态
        try{
            if ($result['trade_state'] == 'SUCCESS') {
                $order->paid_at = Carbon::now();
                $order->payment_method = Order::PAYMENT_WECHAT;
                $order->sale_status = Order::SALE_STATUS_PAID;
                $order->save();
                event(new OrderPaid($order));
            }
        }catch(\Exception $e){
            return response()->json([
                'code' => 500,
                'msg' => $e->getMessage()
            ]);
        }

        return $order;
    }

    public function getOrder($no)
    {
        return Order::with('address', 'items.bookSku', 'items.book', 'coupon','suborders.items')
            ->where('order_id',null)
            ->where('no', $no)->first();
    }

    public function editSaleOrder($no)
    {
        $order = Order::with('address')->where('no', $no)->first();

        return view('wx.editSaleOrder', compact('order'));
    }

    public function updateSaleOrder()
    {
        $order = Order::where('no', \request('no'))->first();
        $address = UserAddress::find(\request('address'));
        if ($order && $address) {
            $address->last_used_at = now();
            $address->save();
            $order->address_id = \request('address');
            $order->save();
        }

        return Order::with('address', 'items.bookSku', 'items.book','suborders.items')->where('no', \request('no'))->first();
    }

    public function cancelOrder($no)
    {
        $wx_user = session('wechat.oauth_user.default'); // 拿到授权用户资料
        $user = $this->fetchUser($wx_user);
        $user_id = $user->id;

        $order = Order::with('address', 'items.bookSku', 'items.book','suborders.items')
            ->whereNull('order_id')->where('no', $no)->first();

        if ($order) {
            if ($order->type == Order::ORDER_TYPE_SALE) {
                if ($order->sale_status >= Order::SALE_STATUS_STOCK_OUT) {
                    return response()->json([
                        'msg' => '订单已出库，不能取消',
                        'code' => 500
                    ]);
                }elseif ($order->sale_status == Order::SALE_STATUS_CANCEL){
                    return response()->json([
                        'msg' => '订单已取消，请勿重复操作',
                        'code' => 500
                    ]);
                }elseif($order->closed == Order::PAYING_STATUS_CLOSE){
                    return response()->json([
                        'msg' => '订单已关闭，不能取消',
                        'code' => 500
                    ]);
                } else {
                    $order->update([
                        'sale_status' => Order::SALE_STATUS_CANCEL
                    ]);
                    Order::where('order_id',$order->id)->update(['sale_status' => Order::SALE_STATUS_CANCEL]);
                    event(new OrderCanceled($order));
                }
            } else if ($order->type == Order::ORDER_TYPE_RECOVER) {
                if (
                    $order->recover_status > Order::RECOVER_STATUS_ARRANGE_EXPRESS
                ) {
                    return response()->json([
                        'msg' => '订单运送中，不能取消',
                        'code' => 500
                    ]);
                }elseif (
                    $order->recover_status == Order::RECOVER_STATUS_CANCEL
                ) {
                    return response()->json([
                        'msg' => '订单已取消，请勿重复操作',
                        'code' => 500
                    ]);
                }elseif ($order->closed == Order::PAYING_STATUS_CLOSE) {
                    return response()->json([
                        'msg' => '订单已关闭，不能取消',
                        'code' => 500
                    ]);
                } else {
                    $order_id = $order->id;
                    $user_id = $order->user_id;
                    $items = OrderItem::with('book')
                        ->where('order_id', $order_id)
                        ->get();

                    // 取消用户卖书订单，书籍进入卖书袋
                    $arr = [];
                    if ($items) {

                        foreach ($items as $item) {
                            // 该书是否已经存在
                            $si = SaleItem::where('book_id', $item->book_id)
                                ->where('user_id', $user_id)
                                ->first();

                            if (!$si) {
                                $arr = [
                                    'isbn'      => $item->book->isbn,
                                    'user_id'   => $user_id,
                                    'book_id'   => $item->book_id,
                                    'book_sku_id'       => $item->book_sku_id,
                                    'remind_count'      => $item->remind_count,
                                    'sale_sku_count'    => $item->sale_sku_count,
                                    'created_at'        => $item->created_at,
                                    'updated_at'        => $item->updated_at
                                ];

                                DB::table('sale_items')->insert($arr);
                            }

                        }
                    }

                    $order->update([
                        'recover_status' => Order::RECOVER_STATUS_CANCEL
                    ]);
                    event(new OrderCanceled($order));
                }
            }
        }
        return $order;
    }

    public function wallet()
    {
        $wx_user = session('wechat.oauth_user.default'); // 拿到授权用户资料
        $user = $this->fetchUser($wx_user);
        //        $items = Wallet::where([
        //            'user_id' => $user->id,
        //            'status' => Wallet::STATUS_SUCCESS
        //        ])->get();
        $items = Wallet::where('user_id', $user->id)
            ->whereIn('status', [Wallet::STATUS_PENDING, Wallet::STATUS_SUCCESS])
            ->get();
        $balance = number_format(abs($items->sum->amount), 2);

        return view('wx.wallet', compact('balance'));
    }

    public function getUserBalance()
    {
        $wx_user = session('wechat.oauth_user.default'); // 拿到授权用户资料
        $user = $this->fetchUser($wx_user);
        //        $items = Wallet::where([
        //            'user_id' => $user->id,
        //            'status' => Wallet::STATUS_SUCCESS
        //        ])->get();
        $items = Wallet::where('user_id', $user->id)
            ->whereIn('status', [Wallet::STATUS_PENDING, Wallet::STATUS_SUCCESS])
            ->get();

        return number_format($items->sum->amount, 2);
    }

    public function walletTransfer()
    {
        $wx_user = session('wechat.oauth_user.default'); // 拿到授权用户资料
        $user = $this->fetchUser($wx_user);
        $item = Wallet::where([
            'user_id' => $user->id,
            'status' => Wallet::STATUS_PENDING,
            'type' => Wallet::TYPE_TRANSFER_OUT
        ])->first();
        if ($item) {
            return response()->json([
                'msg' => '还有提现没有成功，请稍后再试',
                'code' => 500
            ]);
        }
        $items = Wallet::where([
            'user_id' => $user->id,
            'status' => Wallet::STATUS_SUCCESS
        ])->get();
        $balance = $items->sum->amount;
        if (intval($balance) < 1) {
            return response()->json([
                'msg' => '你钱包是空的你不知道吗？',
                'code' => 500
            ]);
        }
        $transfer_no = Wallet::getAvailableTransferNo();
        $item = Wallet::create([
            'user_id' => $user->id,
            'type' => Wallet::TYPE_TRANSFER_OUT,
            'status' => Wallet::STATUS_PENDING,
            'amount' => -$balance,
            'memo' => $transfer_no
        ]);

        // 调用微信支付转账到用户微信钱包
        $this->payment->transfer->toBalance([
            'partner_trade_no' => $transfer_no, // 商户订单号，需保持唯一性(只能是字母或者数字，不能包含有符号)
            'openid' => $user->mp_open_id,
            'check_name' => 'NO_CHECK', // NO_CHECK：不校验真实姓名, FORCE_CHECK：强校验真实姓名
            're_user_name' => '', // 如果 check_name 设置为FORCE_CHECK，则必填用户真实姓名
            'amount' => $balance * 100, // 企业付款金额，单位为分
            'desc' => '回流鱼提现', // 企业付款操作说明信息。必填
        ]);
        $result = $this->payment->transfer->queryBalanceOrder($transfer_no);
        $item->result = json_encode($result);
        if (isset($result['status']) && $result['status'] == 'SUCCESS') {
            $item->status = Wallet::STATUS_SUCCESS;
            $item->save();
        } else {
            $item->status = Wallet::STATUS_FAILED;
            $item->save();
        }
        Log::info('walletTransfer:\n' . json_encode($result));

        return $item;
    }

    public function queryBalanceOrder()
    {
        $no = request('no');
        return $this->payment->transfer->queryBalanceOrder($no);
    }

    public function shudan($id)
    {
        $shudan = Shudan::with('reviewed_items.book')->find($id);
        return view('wx.shudan', compact('shudan'));
    }

    public function buySaleBooks()
    {
        $wx_user = session('wechat.oauth_user.default'); // 拿到授权用户资料
        $user = $this->fetchUser($wx_user);
        $buy_items = OrderItem::where('review_result', 1)
            ->with('book', 'bookSku.prev_user')
            ->whereHas('order', function ($query) use ($user) {
                $query->where([
                    'user_id' => $user->id,
                    'type' => Order::ORDER_TYPE_SALE,
                    'sale_status' => Order::SALE_STATUS_COMPLETE
                ]);
            })
            ->get();
        $sale_items = OrderItem::where('review_result', 1)
            ->with('book', 'bookSku.curr_user')
            ->whereHas('order', function ($query) use ($user) {
                $query->where([
                    'user_id' => $user->id,
                    'type' => Order::ORDER_TYPE_RECOVER,
                    'recover_status' => Order::RECOVER_STATUS_COMPLETE
                ]);
            })
            ->get();
        $buyOriginalPrice = $buy_items->pluck('book')->sum->price;
        $buyPrice = $buy_items->sum->price;
        $saleOriginalPrice = $sale_items->pluck('book')->sum->price;
        $salePrice = $sale_items->sum->price;
        $buyCount = count($buy_items);
        $saleCount = count($sale_items);

        return view('wx.buySaleBooks',
            compact('buyOriginalPrice', 'buyPrice', 'saleOriginalPrice', 'salePrice', 'buyCount', 'saleCount'));
    }

    public function getMyOrders()
    {
        $wx_user = session('wechat.oauth_user.default'); // 拿到授权用户资料
        $user = $this->fetchUser($wx_user);
        $orders = Order::where('user_id', $user->id)->whereNull('order_id')
            ->with('suborders.items')->with('items.book')->latest()->take(20)->get();

        return $orders;
    }

    // 停用
    public function getMyBuyOrders()
    {
        $wx_user = session('wechat.oauth_user.default'); // 拿到授权用户资料
        $user = $this->fetchUser($wx_user);
//        $items = OrderItem::where('review_result', 1)
//            ->with('book', 'bookSku.prev_user')
//            ->whereHas('order', function ($query) use ($user) {
//                $query->where([
//                    'user_id' => $user->id,
//                    'type' => Order::ORDER_TYPE_SALE,
//                    'sale_status' => Order::SALE_STATUS_COMPLETE
//                ]);
//            })
//            ->latest()
//            ->paginate();
//
//        return $items;$arr = [];
        $cartItems = CartItem::where('user_id',$user->id)->get();
        $arr = [];
        foreach ($cartItems as $item){
            $shop_id = $item->book_sku->shop_id;
            if(array_key_exists($item->book_sku->shop_id,$arr)){
                array_push($arr[$shop_id],$item);
            }else{
                $arr[$shop_id] = [];
                array_push($arr[$shop_id],$item);
            }
        }
        return count(array_keys($arr));
    }

    // 停用
    public function getMySaleOrders()
    {
        $wx_user = session('wechat.oauth_user.default'); // 拿到授权用户资料
        $user = $this->fetchUser($wx_user);
        $items = OrderItem::where('review_result', 1)
            ->with('book', 'bookSku.curr_user')
            ->whereHas('order', function ($query) use ($user) {
                $query->where([
                    'user_id' => $user->id,
                    'type' => Order::ORDER_TYPE_RECOVER,
                    'recover_status' => Order::RECOVER_STATUS_COMPLETE
                ]);
            })
            ->latest()
            ->paginate();

        return $items;
    }

    public function getRecommendTags()
    {
        return Tag::all()->random(12)->pluck('name');
    }

    public function getUserSoldBooksIncome($openId)
    {
        $user = User::where('mp_open_id', $openId)->first();
        if ($user) {
            $items = Wallet::where('user_id', $user->id)
                ->where('type', Wallet::TYPE_SALE_BOOK)
                ->where('status', Wallet::STATUS_SUCCESS)
                ->get();
            return $items->sum->amount;
        }

        return 0;
    }

    public function getUserSoldBooks($openId)
    {
        $user = User::where('mp_open_id', $openId)->first();
        return $user->sold_books;
    }
    
    // 用户动态
    public function getUserFeeds($openId) {
        $wx_user = session('wechat.oauth_user.default'); // 拿到授权用户资料
        $user = $this->fetchUser($wx_user);
        $user_id = $user->id;
        
        $page = request('page') ? request('page') : 1;
        $offset = ($page - 1) * 10;
        $offset = $offset < 1 ? 0 : $offset;
        $home_user = User::where('mp_open_id', $openId)->first();
        if ($home_user) {
            $home_user_id = $home_user->id;
            // 订单
            $orders = Order::whereNull('order_id')->with('books')
                ->with('user')
                ->with('shudan_zan_users')
                ->with(['shudan_zan_status' => function($query) use ($user_id) {
                    $query->where('user_id', $user_id);
                }])
                ->whereRaw('(user_id=? and (recover_status=? or sale_status=?))',
                    [$home_user_id, Order::RECOVER_STATUS_COMPLETE, Order::SALE_STATUS_COMPLETE])

                ->get();

            $comment_ids = Comment::where('user_id', $home_user_id)
                ->where('open', 1)
                ->pluck('id');


            $comments = ShudanComment::with([
                'comment.user',
                'book',
                'shudan',
                'shudan_zan_users',
                'shudan_zan_status' => function($query) use ($user_id) {
                    $query->where('user_id', $user_id);
                }
            ])
                ->whereIn('comment_id', $comment_ids)
                ->get();


            $feeds = $orders->concat($comments);
            $sorted = $feeds->sortByDesc('created_at');
            $pageFeeds = $sorted->splice($offset, 10);

            return $pageFeeds;
        }


        return collect([]);
    }

    public function getUserShelfBooks($openId)
    {
        $user = User::where('mp_open_id', $openId)->first();

        return $user->on_shelf_books;
    }

    /**
     * 优惠券列表
     */
    public function getCoupons()
    {
        $wx_user = session('wechat.oauth_user.default'); // 拿到授权用户资料
        $user = $this->fetchUser($wx_user);
        $now = Carbon::now();

        $coupons = Coupon::where('user_id', $user->id)
            ->orderByDesc('not_after')
            ->get();

        // 有效的券放在前边
        $valid = $expired = [];
        if ($coupons) {
            foreach ($coupons as $c) {

                $cc = $c->toArray();
                $from_user_id = intval( $c->from_user );

                // 邀请人用户昵称
                $nickname = '';
                if ($from_user_id > 0) {
                    $from_user = User::find($from_user_id);
                    $nickname = $from_user->nickname;
                }
                $cc['nickname'] = $nickname;

                // 优惠券是否有效
                if ($c->not_after>=$now && $c->used==0 && $c->enabled==1) {
                    $valid[] = $cc;
                } else {
                    $expired[] = $cc;
                }
            }
        }

        $arr = array_merge($valid, $expired);

        return $arr;
    }

    public function readDayCoupon()
    {
        $user_id = request('user');
        $coupon = Coupon::where('user_id', $user_id)->where('name', '七夕节包邮券')->first();
        if (!$coupon) {
            $coupon = Coupon::create([
                'user_id' => $user_id,
                'from' => '2019年七夕节',
                'from_user' => 0,
                'name' => '七夕节包邮券',
                'type' => Coupon::TYPE_FIXED,
                'order_type' => Coupon::ORDER_TYPE_SALE,
                'value' => 5,
                'min_amount' => 20,
                'not_before' => '2019-08-06 00:00:00',
                'not_after' => '2019-08-08 23:59:59',
                'enabled' => 1
            ]);
        }
        return $coupon;
    }

    public function createClientError()
    {
        return "";
        $ce_exist = ClientError::where('user_id', request('user_id'))->where('url', request('url'))
            ->where('created_at', '>', now()->subSeconds(5))->first();
        if (!$ce_exist) {
            $ce = ClientError::create([
                'user_id' => request('user_id'),
                'error' => request('error'),
                'url' => request('url')
            ]);
            return $ce;
        }

        return $ce_exist;
    }

    public function getJzs()
    {
        return Juzi::with('picture')->orderByDesc('id')->paginate(10);
    }

    public function getJzImage($id)
    {
        // 删除原有的invoice.pdf;
        Storage::disk('public')->delete('jz_' . $id . '.jpg');
        $jz = Juzi::find($id);
        $xq = "星期" . mb_substr("日一二三四五六", date('w', strtotime(Carbon::now()->toDateString())), 1, "utf-8");
        $year = Carbon::now()->year;
        $month = Carbon::now()->month;
        $day = Carbon::now()->day;
        $lunar = (new Lunar())->convertSolarToLunar($year, $month, $day);
        $view = view('image.jz', compact('jz', 'xq', 'year', 'month', 'day', 'lunar'));
        $html = response($view)->getContent();
        $image = SnappyImage::setOptions([
            'crop-w' => '750',
            'quality' => '100',
            'width' => '750'
        ])->loadHTML($html)->save('storage/jz_' . $id . '.jpg');

        return '/storage/jz_' . $id . '.jpg';
    }

    public function getQrImage()
    {
        $wx_user = session('wechat.oauth_user.default'); // 拿到授权用户资料
        $user = $this->fetchUser($wx_user);
        // 删除原有的image
        Storage::disk('public')->delete('qr_' . $user->id . '.jpg');
        $result = $this->app->qrcode->temporary($user->id, 6 * 24 * 3600);
        $ticket = $result['ticket'];
        $url = $this->app->qrcode->url($ticket);
        $view = view('image.qr', compact('url', 'user'));
        $html = response($view)->getContent();
        $image = SnappyImage::setOptions([
            'crop-w' => '740',
            'quality' => '25',
            'width' => '740'
        ])->loadHTML($html)->save('storage/qr_' . $user->id . '.jpg');

        return env('APP_URL') . '/storage/qr_' . $user->id . '.jpg';
    }

    public function sendShareImage($id)
    {
        Log::info('sendShareImage user ' . $id);
        $user = User::find($id);
        if (empty($user)) {
            Log::info('sendShareImage can not find user ' . $id);
            return;
        }
        // 生成关注图片，发送图片消息
        // 删除原有的image
        Storage::disk('public')->delete('share_' . $user->id . '.jpg');
        $result = $this->app->qrcode->temporary($user->id, 6 * 24 * 3600);
        $ticket = $result['ticket'];
        $url = $this->app->qrcode->url($ticket);
        $view = view('image.qr', compact('url', 'user'));
        $html = response($view)->getContent();
        $image = SnappyImage::setOptions([
            'crop-w' => '740',
            'quality' => '60',
            'width' => '740'
        ])->loadHTML($html)->save('storage/share_' . $user->id . '.jpg');
        $mediaId = $this->app->media->uploadImage('storage/share_' . $user->id . '.jpg'); // $path 为本地文件路径
        Log::info('GetUserWechatInfoListener mediaId=' . $mediaId['media_id']);
        $this->app->customer_service->message(new Image($mediaId['media_id']))->to($user->mp_open_id)->send();

        return 'Done';
    }

    public function testJz($id)
    {
        $jz = Juzi::find($id);
        $xq = "星期" . mb_substr("日一二三四五六", date('w', strtotime(Carbon::now()->toDateString())), 1, "utf-8");
        $year = Carbon::now()->year;
        $month = Carbon::now()->month;
        $day = Carbon::now()->day;
        $lunar = (new Lunar())->convertSolarToLunar($year, $month, $day);

        return view('image.jz', compact('jz', 'xq', 'year', 'month', 'day', 'lunar'));
    }

    public function qrcode($code)
    {
        $result = $this->app->qrcode->forever($code);
        $ticket = $result['ticket'];
        $url = $this->app->qrcode->url($ticket);

        return $url;
    }

    public function qrcode2($code)
    {
        $result = $this->app->qrcode->temporary($code);
        $ticket = $result['ticket'];
        $url = $this->app->qrcode->url($ticket);

        return $url;
    }

    public function canRecover2(Book $book, User $user = null)
    {
        // 是否是禁书
        if ($book->type == Book::TYPE_BAN) {
            $book->admin_user_id = 1314;
            $book->can_recover = 0;
            $book->save();
            return false;
        }
        // 非9787开头的不收
        if (strpos($book->isbn, '9787') !== 0 && floatval($book->rating_num) < 8.5) {
            return false;
        }
        // 封面为gif的不收
        if (strpos($book->cover_image, '.gif')) {
            $book->admin_user_id = 1037;
            $book->can_recover = 0;
            $book->save();
            return false;
        }
        // category为空不收
        if ($book->admin_user_id == 0 && empty($book->category)) {
            return false;
        }
        $sales_count = BookSku::where('book_id', $book->id)->whereIn('level', [BookSku::LEVEL_60, BookSku::LEVEL_80])
            ->whereIn('status', [BookSku::STATUS_FOR_SALE, BookSku::STATUS_READY_TO_GO])->count();
        $order_items = OrderItem::where('book_id', $book->id)->whereHas('order', function ($q) {
            $q->where('type', Order::ORDER_TYPE_RECOVER)->where('recover_status', '<>', Order::RECOVER_STATUS_COMPLETE)
                ->where('recover_status', '<>', Order::RECOVER_STATUS_CANCEL);
        })->where('created_at', '>', now()->subDays(5))->take(5)->get();

        // 0、无SKU，看order_items
        if ($sales_count == 0 && count($order_items) > (intval($book->reminder_count) + 1)) {
            Log::info($book->isbn . ' ban 0 order_items=' + count($order_items));
            return false;
        }

        // 有SKU，库存和需求对等
        $stock_count = $book->all_sku_count / 3;
        $c = $stock_count > $book->reminder_count ? $stock_count : $book->reminder_count;
        if ($sales_count > $c) {
            Log::info($book->isbn . ' ban 1 $c=' . $c . ' sales_count=' . $sales_count);
            return false;
        }

        // 先看书的价格
        $price = $book->price;
        if (is_null($price) || empty($price)) {
            Log::info($book->isbn . ' ban 4');
            return false;
        }
        if (!is_numeric($price)) {
            Tools::convertPrice($book);
            if (!is_numeric($book->price)) {
                Log::info($book->isbn . ' ban 5');
                return false;
            }
        } else if ($price > 5000) {
            Log::info($book->isbn . ' ban 6');
            return false;
        }
        // 平均收取周期的页面打开次数（一个用户只计算一次）
        $avg_recover_interval_days = Carbon::now()->diffInDays(Carbon::createFromTimeString('2018-09-01 00:00:00'));
        $recover_count = BookSku::where('book_id', $book->id)->where('status', '<>', BookSku::STATUS_ISSUE)->count();
        if ($recover_count > 0) {
            $avg_recover_interval_days =  $avg_recover_interval_days / ($recover_count + count($order_items));
            $avg_recover_interval_days = intval($avg_recover_interval_days);
            if ($avg_recover_interval_days < 1) {
                $avg_recover_interval_days = 1;
            }
        }
        $views = ViewBook::select('id')->where('book_id', $book->id)->where('created_at', '>', now()->subDays($avg_recover_interval_days))
            ->groupBy('user_id')->get();
        $view_count = count($views);
        Log::info($book->isbn . ' avg_days=' . $avg_recover_interval_days);
        Log::info($book->isbn . ' view_count=' . $view_count);
        if ($book->reminder_count == 1 && $book->admin_user_id == 0 && $user) {
            $reminder = $book->reminders()->first();
            if (
                $reminder && $reminder->user_id != $user->id &&
                now()->subDays(2)->gt(Carbon::createFromTimeString($reminder->created_at->toDateTimeString()))
            ) {
                $book->can_recover = true;
                $book->admin_user_id = 9999;
                $book->save();
            }
        }
        if ($book->reminder_count >= 2 && $book->admin_user_id == 0) {
            $book->can_recover = true;
            $book->admin_user_id = 100000;
            $book->save();
        }

        // 根据需求确定初始折扣
        if ($book->can_recover == 0 && $book->admin_user_id > 0) {
            Log::info($book->isbn . ' ban 7');
            return false;
        }

        $book_price = $book->prices->first();
        // 强开启收取逻辑
        if ($book->admin_user_id == 0 && $book_price) {
            if (floatval($book->rating_num) >= 8.5 && !$this->tagBan85($book)) {
                $book->can_recover = true;
                $book->admin_user_id = 85;
                $book->save();
            } else if (
                $book_price && !$this->tagBan($book) && intval($book_price->douban_es_want_count) >= 100 &&
                Tools::isDate($book->publish_year) &&
                strtotime($book->publish_year) >= strtotime('2010-01-01')
            ) {
                $book->can_recover = true;
                $book->admin_user_id = 83;
                $book->save();
            } else if (
                $book_price && !$this->tagBan($book) && intval($book_price->douban_es_want_count) >= 80 &&
                Tools::isDate($book->publish_year) &&
                strtotime($book->publish_year) >= strtotime('2014-01-01')
            ) {
                $book->can_recover = true;
                $book->admin_user_id = 82;
                $book->save();
            } else if (
                $book_price && !$this->tagBan($book) && intval($book_price->douban_es_want_count) >= 60 &&
                Tools::isDate($book->publish_year) &&
                strtotime($book->publish_year) >= strtotime('2017-01-01')
            ) {
                $book->can_recover = true;
                $book->admin_user_id = 81;
                $book->save();
            } else if (
                $book_price && !$this->tagBan8($book) && intval($book_price->douban_es_want_count) >= 50 && floatval($book->rating_num) >= 8 &&
                Tools::isDate($book->publish_year) &&
                strtotime($book->publish_year) >= strtotime('2011-01-01') && !$this->tagBan8($book)
            ) {
                $book->can_recover = true;
                $book->admin_user_id = 80;
                $book->save();
            } else if (
                $book_price && !$this->tagBan($book) && intval($book_price->douban_es_want_count) >= 90 && floatval($book->rating_num) >= 7 &&
                Tools::isDate($book->publish_year) &&
                strtotime($book->publish_year) >= strtotime('2011-01-01') && !$this->tagBan($book)
            ) {
                $book->can_recover = true;
                $book->admin_user_id = 70;
                $book->save();
            } else if (
                $book_price && !$this->tagBan($book) && intval($book_price->douban_es_want_count) >= 100 && floatval($book->rating_num) >= 6 &&
                Tools::isDate($book->publish_year) &&
                strtotime($book->publish_year) >= strtotime('2011-01-01') && !$this->tagBan($book)
            ) {
                $book->can_recover = true;
                $book->admin_user_id = 60;
                $book->save();
            } else if (
                strstr($book->category, '外国文学') && Tools::isDate($book->publish_year) &&
                strtotime($book->publish_year) >= strtotime('2014-01-01')
            ) {
                $book->can_recover = true;
                $book->admin_user_id = 59;
                $book->save();
            } else if (
                floatval($book->rating_num) < 7 && Tools::isDate($book->publish_year) &&
                strtotime($book->publish_year) >= strtotime('2000-01-01') && intval($book_price->douban_es_want_count) <= 30
            ) {
                Log::info($book->isbn . ' ban 17');
                return false;
            }
        }
        if ($book->admin_user_id == 0) {
            if (strstr($book->category, '明星')) {
                Log::info($book->isbn . ' ban 8');
                return false;
            } else if (empty($book->author) || empty($book->publish_year)) {
                Log::info($book->isbn . ' ban 9');
                return false;
            } else if (floatval($book->rating_num) <= 5) {
                Log::info($book->isbn . ' ban 10');
                return false;
            } else if (floatval($book->rating_num) <= 7 && $this->tagBan($book)) {
                Log::info($book->isbn . ' ban 14');
                return false;
            } else if (floatval($book->rating_num) > 7 && floatval($book->rating_num) < 8.5 && $this->tagBan8($book)) {
                Log::info($book->isbn . ' ban 15');
                return false;
            } else if ($this->tagBan85($book)) {
                Log::info($book->isbn . ' ban 16');
                return false;
            }
            // 默认评分8.5的都收取
        }

        // 默认base_discount=1;
        $base_discount = 1;
        // 如果最近2周新增的reminder大于等于2，预设discount=1.5
        $latest_2_weeks_reminder_count = ReminderItem::where('book_id', $book->id)->where('created_at', '>=', now()->subWeeks(2))->count();
        if ($latest_2_weeks_reminder_count == 2) {
            $base_discount = 1.2;
        } else if ($latest_2_weeks_reminder_count == 3) {
            $base_discount = 1.3;
        } else if ($latest_2_weeks_reminder_count == 4) {
            $base_discount = 1.4;
        } else if ($latest_2_weeks_reminder_count == 5) {
            $base_discount = 1.5;
        } else if ($latest_2_weeks_reminder_count == 6) {
            $base_discount = 1.6;
        } else if ($latest_2_weeks_reminder_count >= 7) {
            $base_discount = 1.7;
        }

        // 看收取难度
        $discount = 1; //预设
        $view_count = $view_count + intval($book->reminder_count) / 1.5;
        if ($w0 = BookSku::where('isbn', $book->isbn)->where('created_at', '>=', now()->subDay(7))->count() > 0) {
            // 1周内收到
            $discount = $base_discount + 0.004 * ($view_count - $w0);
            Log::info($book->isbn . ' get w0');
        } else if ($w1 = BookSku::where('isbn', $book->isbn)->where('created_at', '>=', now()->subDay(14))->count() > 0) {
            // 2周内收到
            $discount = $base_discount + 0.008 * ($view_count - $w1);
            Log::info($book->isbn . ' get w1');
        } else if ($w2 = BookSku::where('isbn', $book->isbn)->where('created_at', '>=', now()->subDay(21))->count() > 0) {
            // 3周内收到
            $discount = $base_discount + 0.012 * ($view_count - $w2);
            Log::info($book->isbn . ' get w2');
        } else if ($w3 = BookSku::where('isbn', $book->isbn)->where('created_at', '>=', now()->subDay(28))->count() > 0) {
            // 4周内收到
            $discount = $base_discount + 0.016 * ($view_count - $w3);
            Log::info($book->isbn . ' get w3');
        } else if ($w4 = BookSku::where('isbn', $book->isbn)->where('created_at', '>=', now()->subDay(56))->count() > 0) {
            // 8周内收到
            $discount = $base_discount + 0.02 * ($view_count - $w4);
            Log::info($book->isbn . ' get w4');
        } else if ($w5 = BookSku::where('isbn', $book->isbn)->where('created_at', '>=', now()->subDay(112))->count() > 0) {
            // 16周内收到
            $discount = $base_discount + 0.024 * ($view_count - $w5);
            Log::info($book->isbn . ' get w5');
        } else if ($w6 = BookSku::where('isbn', $book->isbn)->where('created_at', '>=', now()->subDay(224))->count() > 0) {
            // 32周内收到
            $discount = $base_discount + 0.028 * ($view_count - $w6);
            Log::info($book->isbn . ' get w6');
        } else {
            $discount = $base_discount + 0.028 * $view_count;
            Log::info($book->isbn . ' get w7');
        }
        if ($discount < 1) {
            $discount = 1;
        }
        if ($discount > 2) {
            $discount = 2;
        }
        Log::info($book->name . " w discount=" . $discount);

        // 价格大于88，2折收
        if (
            floatval($book->price) >= 88 && floatval($book->rating_num) >= 7.5 &&
            Tools::isDate($book->publish_year) && strtotime($book->publish_year) >= strtotime('2017-01-01')
        ) {
            $discount = 2;
        }
        Log::info($book->name . " price discount=" . $discount);

        // 查看是否有sku记录
        $sku = BookSku::where('book_id', $book->id)->first();
        if (
            !$sku && floatval($book->rating_num) >= 7 && Tools::isDate($book->publish_year) &&
            strtotime($book->publish_year) >= strtotime('2018-01-01') && strtotime($book->publish_year) < strtotime('2019-01-01')
        ) {
            $discount = floatval($discount) < 1.5 ? 1.5 : floatval($discount);
        } else if (
            !$sku && (floatval($book->rating_num) == 0 || floatval($book->rating_num) >= 8) &&
            Tools::isDate($book->publish_year) && strtotime($book->publish_year) >= strtotime('2019-01-01')
        ) {
            $discount = floatval($discount) < 1.6 ? 1.6 : floatval($discount);
        }

        // 收购价超过100，调整为100
        $recover_price = floatval($book->price) * floatval($discount) / 10;
        if ($recover_price > 100 && floatval($book->price) > 0) {
            $discount = 1000 / floatval($book->price);
        }

        // 教材0.5折收
        if (
            strstr($book->category, '教材') || strstr($book->category, '教辅') || strstr($book->category, '课本')
            || strstr($book->category, '考试') || strstr($book->category, '解析') || strstr($book->category, '真题')
            || strstr($book->category, '教科书') || strstr($book->category, '考研') || strstr($book->category, '高中')
            || strstr($book->category, '初中') || strstr($book->category, '高考') || strstr($book->category, '司法考试')
            || strstr($book->category, '公务员')
        ) {
            $discount = 0.5;
        }

        // 计算机类的1折
        if (strstr($book->category, '计算机') || strstr($book->category, '编程') ||
            strstr($book->category, '编程')) {
            $discount = 0.5;
        }

        $book->discount = number_format($discount * 10, 0);
        $book->can_recover = 1;
        $book->save();

        return true;
    }


    public function canRecover3(Book $book, User $user = null)
    {
        // 是否是禁书
        if ($book->type == Book::TYPE_BAN) {
            $book->admin_user_id = 1314;
            $book->can_recover = 0;
            $book->save();
            return false;
        }
        // 非9787开头的不收
        if (strpos($book->isbn, '9787') !== 0 && floatval($book->rating_num) < 8.5) {
            return false;
        }
        // 封面为gif的不收
        if (strpos($book->cover_image, '.gif')) {
            $book->admin_user_id = 1037;
            $book->can_recover = 0;
            $book->save();
            return false;
        }
        // category为空不收
        if ($book->admin_user_id == 0 && empty($book->category)) {
            return false;
        }

        // 运营ban掉的不收
        if ($book->admin_user_id>0 && $book->can_recover == 0){
            return false;
        }

        // reminder_count小于5不收
        if ($book->reminder_count == 0) {
            return false;
        }

        // 有库存的不收
        $stock_count = BookSku::where('book_id', $book->id)
            ->whereIn('status', [BookSku::STATUS_FOR_SALE, BookSku::STATUS_READY_TO_GO])->count();
        if ($stock_count > 0) {
            return false;
        }

        $sales_count = BookSku::where('book_id', $book->id)->whereIn('level', [BookSku::LEVEL_60, BookSku::LEVEL_80])
            ->whereIn('status', [BookSku::STATUS_FOR_SALE, BookSku::STATUS_READY_TO_GO])->count();
        $order_items = OrderItem::where('book_id', $book->id)->whereHas('order', function ($q) {
            $q->where('type', Order::ORDER_TYPE_RECOVER)->where('recover_status', '<>', Order::RECOVER_STATUS_COMPLETE)
                ->where('recover_status', '<>', Order::RECOVER_STATUS_CANCEL);
        })->where('created_at', '>', now()->subDays(5))->take(5)->get();

        // 0、无SKU，看order_items
        if ($sales_count == 0 && count($order_items) > (intval($book->reminder_count) + 1)) {
            Log::info($book->isbn . ' ban 0 order_items=' + count($order_items));
            return false;
        }

        // 有SKU，库存和需求对等
        $stock_count = $book->all_sku_count / 3;
        $c = $stock_count > $book->reminder_count ? $stock_count : $book->reminder_count;
        if ($sales_count > $c) {
            Log::info($book->isbn . ' ban 1 $c=' . $c . ' sales_count=' . $sales_count);
            return false;
        }

        // 先看书的价格
        $price = $book->price;
        if (is_null($price) || empty($price)) {
            Log::info($book->isbn . ' ban 4');
            return false;
        }
        if (!is_numeric($price)) {
            Tools::convertPrice($book);
            if (!is_numeric($book->price)) {
                Log::info($book->isbn . ' ban 5');
                return false;
            }
        } else if ($price > 5000) {
            Log::info($book->isbn . ' ban 6');
            return false;
        }
        // 平均收取周期的页面打开次数（一个用户只计算一次）
        $avg_recover_interval_days = Carbon::now()->diffInDays(Carbon::createFromTimeString('2018-09-01 00:00:00'));
        $recover_count = BookSku::where('book_id', $book->id)->where('status', '<>', BookSku::STATUS_ISSUE)->count();
        if ($recover_count > 0) {
            $avg_recover_interval_days =  $avg_recover_interval_days / ($recover_count + count($order_items));
            $avg_recover_interval_days = intval($avg_recover_interval_days);
            if ($avg_recover_interval_days < 1) {
                $avg_recover_interval_days = 1;
            }
        }
        $views = ViewBook::select('id')->where('book_id', $book->id)->where('created_at', '>', now()->subDays($avg_recover_interval_days))
            ->groupBy('user_id')->get();
        $view_count = count($views);
        Log::info($book->isbn . ' avg_days=' . $avg_recover_interval_days);
        Log::info($book->isbn . ' view_count=' . $view_count);
        if ($book->reminder_count == 1 && $book->admin_user_id == 0 && $user) {
            $reminder = $book->reminders()->first();
            if (
                $reminder && $reminder->user_id != $user->id &&
                now()->subDays(2)->gt(Carbon::createFromTimeString($reminder->created_at->toDateTimeString()))
            ) {
                $book->can_recover = true;
                $book->admin_user_id = 9999;
                $book->save();
            }
        }
        if ($book->reminder_count >= 5 && $book->admin_user_id == 0) {
            $book->can_recover = true;
            $book->admin_user_id = 100000;
            $book->save();
        }

        // 根据需求确定初始折扣
        if ($book->can_recover == 0 && $book->admin_user_id > 0) {
            Log::info($book->isbn . ' ban 7');
            return false;
        }

        $book_price = $book->prices->first();
        // 强开启收取逻辑
        if ($book->admin_user_id == 0 && $book_price) {
            if (floatval($book->rating_num) >= 8.5 && !$this->tagBan85($book)) {
                $book->can_recover = true;
                $book->admin_user_id = 85;
                $book->save();
            } else if (
                $book_price && !$this->tagBan($book) && intval($book_price->douban_es_want_count) >= 100 &&
                Tools::isDate($book->publish_year) &&
                strtotime($book->publish_year) >= strtotime('2010-01-01')
            ) {
                $book->can_recover = true;
                $book->admin_user_id = 83;
                $book->save();
            } else if (
                $book_price && !$this->tagBan($book) && intval($book_price->douban_es_want_count) >= 80 &&
                Tools::isDate($book->publish_year) &&
                strtotime($book->publish_year) >= strtotime('2014-01-01')
            ) {
                $book->can_recover = true;
                $book->admin_user_id = 82;
                $book->save();
            } else if (
                $book_price && !$this->tagBan($book) && intval($book_price->douban_es_want_count) >= 60 &&
                Tools::isDate($book->publish_year) &&
                strtotime($book->publish_year) >= strtotime('2017-01-01')
            ) {
                $book->can_recover = true;
                $book->admin_user_id = 81;
                $book->save();
            } else if (
                $book_price && !$this->tagBan8($book) && intval($book_price->douban_es_want_count) >= 50 && floatval($book->rating_num) >= 8 &&
                Tools::isDate($book->publish_year) &&
                strtotime($book->publish_year) >= strtotime('2011-01-01') && !$this->tagBan8($book)
            ) {
                $book->can_recover = true;
                $book->admin_user_id = 80;
                $book->save();
            } else if (
                $book_price && !$this->tagBan($book) && intval($book_price->douban_es_want_count) >= 90 && floatval($book->rating_num) >= 7 &&
                Tools::isDate($book->publish_year) &&
                strtotime($book->publish_year) >= strtotime('2011-01-01') && !$this->tagBan($book)
            ) {
                $book->can_recover = true;
                $book->admin_user_id = 70;
                $book->save();
            } else if (
                $book_price && !$this->tagBan($book) && intval($book_price->douban_es_want_count) >= 100 && floatval($book->rating_num) >= 6 &&
                Tools::isDate($book->publish_year) &&
                strtotime($book->publish_year) >= strtotime('2011-01-01') && !$this->tagBan($book)
            ) {
                $book->can_recover = true;
                $book->admin_user_id = 60;
                $book->save();
            } else if (
                strstr($book->category, '外国文学') && Tools::isDate($book->publish_year) &&
                strtotime($book->publish_year) >= strtotime('2014-01-01')
            ) {
                $book->can_recover = true;
                $book->admin_user_id = 59;
                $book->save();
            } else if (
                floatval($book->rating_num) < 7 && Tools::isDate($book->publish_year) &&
                strtotime($book->publish_year) >= strtotime('2000-01-01') && intval($book_price->douban_es_want_count) <= 30
            ) {
                Log::info($book->isbn . ' ban 17');
                return false;
            }
        }
        if ($book->admin_user_id == 0) {
            if (strstr($book->category, '明星')) {
                Log::info($book->isbn . ' ban 8');
                return false;
            } else if (empty($book->author) || empty($book->publish_year)) {
                Log::info($book->isbn . ' ban 9');
                return false;
            } else if (floatval($book->rating_num) <= 5) {
                Log::info($book->isbn . ' ban 10');
                return false;
            } else if (floatval($book->rating_num) <= 7 && $this->tagBan($book)) {
                Log::info($book->isbn . ' ban 14');
                return false;
            } else if (floatval($book->rating_num) > 7 && floatval($book->rating_num) < 8.5 && $this->tagBan8($book)) {
                Log::info($book->isbn . ' ban 15');
                return false;
            } else if ($this->tagBan85($book)) {
                Log::info($book->isbn . ' ban 16');
                return false;
            }
            // 默认评分8.5的都收取
        }

        // 默认base_discount=1;
        $base_discount = 1;
        // 如果最近2周新增的reminder大于等于2，预设discount=1.5
        $latest_2_weeks_reminder_count = ReminderItem::where('book_id', $book->id)->where('created_at', '>=', now()->subWeeks(2))->count();
        if ($latest_2_weeks_reminder_count == 2) {
            $base_discount = 1.2;
        } else if ($latest_2_weeks_reminder_count == 3) {
            $base_discount = 1.3;
        } else if ($latest_2_weeks_reminder_count == 4) {
            $base_discount = 1.4;
        } else if ($latest_2_weeks_reminder_count == 5) {
            $base_discount = 1.5;
        } else if ($latest_2_weeks_reminder_count == 6) {
            $base_discount = 1.6;
        } else if ($latest_2_weeks_reminder_count >= 7) {
            $base_discount = 1.7;
        }

        // 看收取难度
        $discount = 1; //预设
        $view_count = $view_count + intval($book->reminder_count) / 1.5;
        if ($w0 = BookSku::where('isbn', $book->isbn)->where('created_at', '>=', now()->subDay(7))->count() > 0) {
            // 1周内收到
            $discount = $base_discount + 0.004 * ($view_count - $w0);
            Log::info($book->isbn . ' get w0');
        } else if ($w1 = BookSku::where('isbn', $book->isbn)->where('created_at', '>=', now()->subDay(14))->count() > 0) {
            // 2周内收到
            $discount = $base_discount + 0.008 * ($view_count - $w1);
            Log::info($book->isbn . ' get w1');
        } else if ($w2 = BookSku::where('isbn', $book->isbn)->where('created_at', '>=', now()->subDay(21))->count() > 0) {
            // 3周内收到
            $discount = $base_discount + 0.012 * ($view_count - $w2);
            Log::info($book->isbn . ' get w2');
        } else if ($w3 = BookSku::where('isbn', $book->isbn)->where('created_at', '>=', now()->subDay(28))->count() > 0) {
            // 4周内收到
            $discount = $base_discount + 0.016 * ($view_count - $w3);
            Log::info($book->isbn . ' get w3');
        } else if ($w4 = BookSku::where('isbn', $book->isbn)->where('created_at', '>=', now()->subDay(56))->count() > 0) {
            // 8周内收到
            $discount = $base_discount + 0.02 * ($view_count - $w4);
            Log::info($book->isbn . ' get w4');
        } else if ($w5 = BookSku::where('isbn', $book->isbn)->where('created_at', '>=', now()->subDay(112))->count() > 0) {
            // 16周内收到
            $discount = $base_discount + 0.024 * ($view_count - $w5);
            Log::info($book->isbn . ' get w5');
        } else if ($w6 = BookSku::where('isbn', $book->isbn)->where('created_at', '>=', now()->subDay(224))->count() > 0) {
            // 32周内收到
            $discount = $base_discount + 0.028 * ($view_count - $w6);
            Log::info($book->isbn . ' get w6');
        } else {
            $discount = $base_discount + 0.028 * $view_count;
            Log::info($book->isbn . ' get w7');
        }
        if ($discount < 1) {
            $discount = 1;
        }
        if ($discount > 2) {
            $discount = 2;
        }
        Log::info($book->name . " w discount=" . $discount);

        // 价格大于88，2折收
        if (
            floatval($book->price) >= 88 && floatval($book->rating_num) >= 7.5 &&
            Tools::isDate($book->publish_year) && strtotime($book->publish_year) >= strtotime('2017-01-01')
        ) {
            $discount = 2;
        }
        Log::info($book->name . " price discount=" . $discount);

        // 查看是否有sku记录
        $sku = BookSku::where('book_id', $book->id)->first();
        if (
            !$sku && floatval($book->rating_num) >= 7 && Tools::isDate($book->publish_year) &&
            strtotime($book->publish_year) >= strtotime('2018-01-01') && strtotime($book->publish_year) < strtotime('2019-01-01')
        ) {
            $discount = floatval($discount) < 1.5 ? 1.5 : floatval($discount);
        } else if (
            !$sku && (floatval($book->rating_num) == 0 || floatval($book->rating_num) >= 8) &&
            Tools::isDate($book->publish_year) && strtotime($book->publish_year) >= strtotime('2019-01-01')
        ) {
            $discount = floatval($discount) < 1.6 ? 1.6 : floatval($discount);
        }

        // 收购价超过100，调整为100
        $recover_price = floatval($book->price) * floatval($discount) / 10;
        if ($recover_price > 100 && floatval($book->price) > 0) {
            $discount = 1000 / floatval($book->price);
        }

        // 教材0.5折收
        if (
            strstr($book->category, '教材') || strstr($book->category, '教辅') || strstr($book->category, '课本')
            || strstr($book->category, '考试') || strstr($book->category, '解析') || strstr($book->category, '真题')
            || strstr($book->category, '教科书') || strstr($book->category, '考研') || strstr($book->category, '高中')
            || strstr($book->category, '初中') || strstr($book->category, '高考') || strstr($book->category, '司法考试')
            || strstr($book->category, '公务员')
        ) {
            $discount = 0.5;
        }

        // 计算机类的1折
        if (strstr($book->category, '计算机') || strstr($book->category, '编程') ||
            strstr($book->category, '编程')) {
            $discount = 0.5;
        }

        $book->discount = number_format($discount * 10, 0);
        $book->can_recover = 1;
        $book->save();

        return true;
    }

    // 用于测试
    public function recoverPrice($isbn)
    {
        $book = Book::where('isbn', $isbn)->first();
        if (!$book) {
            return response()->json([
                'isbn' => $isbn,
                'msg' => '没有找到 ISBN 为' . $isbn . '的书',
                'code' => 500
            ]);
        }
        if (is_null($book->author)) {
            return response()->json([
                'isbn' => $isbn,
                'msg' => '没有作者的书我们不收',
                'code' => 500
            ]);
        }
        if ($book->can_recover == 0 && $book->admin_user_id > 0) {
            return response()->json([
                'isbn' => $isbn,
                'msg' => '管理员不让收',
                'code' => 500
            ]);
        }
        if ($book->can_recover == 1 && $book->admin_user_id > 0) {
            if ($book->rating_num > 0) {
                $base_discount = log($book->num_raters, 100 - $book->rating_num);
            } else {
                $base_discount = 1;
            }
            if ($base_discount >= 1.5) {
                $base_discount = 1.5;
            }
        } else if (!$this->tagBan($book)) {
            // 关键词过滤
            $base_discount = 1;
        } else {
            return response()->json([
                'isbn' => $isbn,
                'msg' => '这本书我们暂时不收',
                'code' => 500
            ]);
        }

        Log::info('1 base_discount=' . $base_discount);
        // 查看book prices
        $book_price = $book->prices->first();
        if ($book_price) {
            $dd_new_price = floatval($book_price->dd_new_price);
            $jd_new_price = floatval($book_price->jd_new_price);
            $amz_new_price = floatval($book_price->amz_new_price);
            $bc_new_price = floatval($book_price->bc_new_price);
            $douban_es_count = intval($book_price->douban_es_count);
            $douban_es_want_count = intval($book_price->douban_es_want_count);
            if ($dd_new_price == 0.0 && $jd_new_price == 0.0 && $amz_new_price == 0.0 && $bc_new_price == 0.0) {
                $div = $douban_es_want_count / ($douban_es_count + 1);
                if ($div >= 90 && $div < 116) {
                    $base_discount += 1;
                } else if ($div >= 116 && $div < 131) {
                    $base_discount += 1.5;
                } else if ($div >= 131 && $div < 320) {
                    $base_discount += 2;
                } else if ($div >= 320) {
                    $base_discount += 2.5;
                }
            }
        }
        Log::info('2 base_discount=' . $base_discount);

        // 查看book type
        if ($book->type == Book::TYPE_ADD_PRICE) {
            $base_discount += 1;
        } else if ($book->type == Book::TYPE_ADD_PRICE2) {
            $base_discount += 2;
        } else if ($book->type == Book::TYPE_OUT_OF_PRINT) {
            $base_discount += 3;
        } else if ($book->type == Book::TYPE_SUB_PRICE) {
            $base_discount -= 1;
        }
        Log::info('3 base_discount=' . $base_discount);

        // 需求驱动
        $reminders_count = ReminderItem::where('isbn', $isbn)->count();
        // 看收取难度
        $w1 = BookSku::where('isbn', $isbn)->where('created_at', '>=', now()->subDay(7))->count();
        if ($w1 > 0) {
            // 一周内收到
            $discount = $base_discount + 0.02 * ($reminders_count - $w1);
            return response()->json([
                'isbn' => $isbn,
                'code' => 200,
                'base_discount' => number_format($base_discount, 1),
                'discount' => number_format($discount, 1),
                'msg' => 'w1'
            ]);
        }
        $w2 = BookSku::where('isbn', $isbn)->where('created_at', '>=', now()->subDay(14))->count();
        if ($w2 > 0) {
            $discount = $base_discount + 0.025 * ($reminders_count - $w2);
            return response()->json([
                'isbn' => $isbn,
                'code' => 200,
                'base_discount' => number_format($base_discount, 1),
                'discount' => number_format($discount, 1),
                'msg' => 'w2'
            ]);
        }
        $w3 = BookSku::where('isbn', $isbn)->where('created_at', '>=', now()->subDay(21))->count();
        if ($w3 > 0) {
            $discount = $base_discount + 0.03 * ($reminders_count - $w3);
            return response()->json([
                'isbn' => $isbn,
                'code' => 200,
                'base_discount' => number_format($base_discount, 1),
                'discount' => number_format($discount, 1),
                'msg' => 'w3'
            ]);
        }
        $w4 = BookSku::where('isbn', $isbn)->where('created_at', '>=', now()->subDay(30))->count();
        if ($w4 > 0) {
            $discount = $base_discount + 0.04 * ($reminders_count - $w4);
            return response()->json([
                'isbn' => $isbn,
                'code' => 200,
                'base_discount' => number_format($base_discount, 1),
                'discount' => number_format($discount, 1),
                'msg' => 'w4'
            ]);
        }
        $w5 = BookSku::where('isbn', $isbn)->where('created_at', '>=', now()->subDay(60))->count();
        if ($w5 > 0) {
            $discount = $base_discount + 0.05 * ($reminders_count - $w5);
            return response()->json([
                'isbn' => $isbn,
                'code' => 200,
                'base_discount' => number_format($base_discount, 1),
                'discount' => number_format($discount, 1),
                'msg' => 'w5'
            ]);
        }
        if ($base_discount < 1) {
            $base_discount = 1;
        }
        $discount = $base_discount + 0.05 * $reminders_count;

        return response()->json([
            'isbn' => $isbn,
            'code' => 200,
            'base_discount' => number_format($base_discount, 1),
            'discount' => number_format($discount, 1),
            'msg' => '最后'
        ]);
    }

    function tagBan($book)
    {
        // 标签过滤
        $notRecoverTags = [
            "灵修", "郭敬明", "教材", "教辅", "课本", "穿越", "佛教", "佛学", "耽美", "考古", "日历",
            "养生", "手工", "摄影", "工具书", "神经网络", "web", "Web", "WEB", "UCD", "ucd", "通信", "校园", "落落", "法师",
            "几米", "新概念英语", "生命之水", "词典", "辞典", "猫小乐", "曹正凤", "马云", "马化腾", "李彦宏", "奢侈品", "幼稚园",
            "王先霈", "航空", "航天", "民航", "Mp3", "mp3", "vcd", "VCD", "CD", "cd", "DVD", "dvd", "考研", "公务员", "画册", "电子商务", "剑桥",
            "物理", "化学", "医", "photoshop", "Photoshop", "ps", "PS", "字典", "偶像", "少女",
            "意林", "读者", "java", "JAVA", "Java", "c++", "C++", "html", "HTML", "严凌君", "翻译", "adobe", "Adobe",
            "首饰制作", "首饰", "红点奖", "瑜伽", "大学语文", "考试", "主编", "PPT", "ppt", "郑仰霖", "郑仰森", "练习册", "教程",
            "软件工程", "习近平", "共产党", "外贸", "能力测试", "新东方", "GRE", "TOEFL", "365天", "工业4.0", "伍美珍", "星座", "太极",
            "园艺", "葡萄酒", "姚松涛", "中医", "针灸", 'CAD', 'cad', "开发指南", "Maya", "maya", "课本", "专业教材", "专业书", "布线", "接线",
            "男性杂志", "商晓娜", "真题", '历年', '法务', '股权', '机械设计', '软件', '应试', '垃圾', '邓小平', '朱镕基', '江泽民', '胡锦涛', '刘少奇',
            '朱德', '周恩来', '中共', '减肥', '瘦身', '美容', '养颜', '护肤', '嫩肤', '按摩', '护理', '手册', '全书', '长寿', '中华人民共和国',
            '象棋', '五子棋', '围棋', '教学', '魔术', '饶雪漫', '期刊', '杂志', '吉林出版', '写真', '足球', '英超', '案例',
            '华中科技', '明星', '坑爹', '山寨书', '伪书', '垃圾', '游戏', '仙侠', '灵修', '留学', '重生', '命理', '八字', '算卦',
            '明晓溪', '催眠', '信托', '基金', '房地产', '风水', '玄学', '雅思', '听力', 'iphone', 'iPhone', 'iOS',
            'ios', 'jQuery', 'C#', 'c#', 'sql', 'SQL'
        ];
        $tagBan = false;
        if (empty($book->name)) {
            return true;
        }
        if (empty($book->author)) {
            return true;
        }
        if (empty($book->category)) {
            return true;
        }
        $category = $book->category;
        foreach ($notRecoverTags as $tag) {
            if (strstr($category, $tag)) {
                $tagBan = true;
            }
            if (!empty($book->name) && strstr($book->name, $tag)) {
                $tagBan = true;
            }
            if (!empty($book->press) && strstr($book->press, $tag)) {
                $tagBan = true;
            }
            if (!empty($book->author) && strstr($book->author, $tag)) {
                $tagBan = true;
            }
        }

        return $tagBan;
    }

    function tagBan8($book)
    {
        // 标签过滤
        $notRecoverTags = [
            "灵修", "郭敬明", "教材", "教辅", "课本", "穿越", "耽美", "考古", "偶像", '日历', "少女",
            "养生", "落落", "法师", "新概念英语", "生命之水", "猫小乐", "曹正凤", "马云", "马化腾", "李彦宏", "幼稚园",
            "王先霈", "航空", "航天", "民航", "Mp3", "mp3", "vcd", "VCD", "CD", "cd", "DVD", "dvd", "考研", "公务员",
            "医", "意林", "读者", "严凌君", "首饰制作", "首饰", "红点奖", "大学语文", "考试", "主编", "郑仰霖", "郑仰森", "练习册", "教程",
            "习近平", "共产党", "能力测试", "新东方", "365天", "工业4.0", "郎咸平", "伍美珍", "星座", "太极", "姚松涛", "中医", "针灸", "课本",
            "专业教材", "专业书", "布线", "接线", "男性杂志", "商晓娜", "真题", '历年', '机械设计', '应试', '垃圾', '邓小平', '江泽民', '胡锦涛', '刘少奇',
            '朱德', '周恩来', '中共', '瘦身', '美容', '养颜', '护肤', '嫩肤', '按摩', '护理', '手册', '全书', '长寿', '中华人民共和国', '明星',
            '象棋', '五子棋', '围棋', '教学', '女性', '魔术', '饶雪漫', '期刊', '杂志', '吉林出版', '写真', '足球', '英超', '案例',
            '华中科技', '信托', '股票', '基金', '房地产', '风水', '玄学', '听力', '写作', 'iOS', 'ios', 'jQuery', 'C#', 'c#',
            'sql', 'SQL'
        ];
        $tagBan = false;
        if (empty($book->name)) {
            return true;
        }
        if (empty($book->author)) {
            return true;
        }
        if (empty($book->category)) {
            return true;
        }
        $category = $book->category;
        foreach ($notRecoverTags as $tag) {
            if (strstr($category, $tag)) {
                $tagBan = true;
            }
            if (!empty($book->name) && strstr($book->name, $tag)) {
                $tagBan = true;
            }
            if (!empty($book->press) && strstr($book->press, $tag)) {
                $tagBan = true;
            }
            if (!empty($book->author) && strstr($book->author, $tag)) {
                $tagBan = true;
            }
        }

        return $tagBan;
    }

    function tagBan85($book)
    {
        // 标签过滤
        $notRecoverTags = [
            "教材", "课本", '考试', '应试', '中医', '养生', '股票', '基金', '信托', '健康', '房地产', '风水', '玄学',
            '听力', '真题', '历年', '考研', '公务员', 'iOS', 'ios', 'iPhone', 'iphone', 'jQuery', '偶像', 'C#', 'c#', '日历',
            'sql', 'SQL', "少女", '耽美'
        ];
        $tagBan = false;
        if (empty($book->name)) {
            return true;
        }
        if (empty($book->author)) {
            return true;
        }
        if (empty($book->category)) {
            return true;
        }
        $category = $book->category;
        foreach ($notRecoverTags as $tag) {
            if (strstr($category, $tag)) {
                $tagBan = true;
            }
            if (!empty($book->name) && strstr($book->name, $tag)) {
                $tagBan = true;
            }
            if (!empty($book->press) && strstr($book->press, $tag)) {
                $tagBan = true;
            }
            if (!empty($book->author) && strstr($book->author, $tag)) {
                $tagBan = true;
            }
        }

        return $tagBan;
    }

    public function shipCallBack()
    {
        Log::info("shipCallBack 物流回调信息：");
        // 更新订单物流信息
        $array = request()->all();
        $traceJsonStr = $array['RequestData'];
        $traceData = json_decode($traceJsonStr, true);
        $data = $traceData['Data'][0];
        Log::info($data['ShipperCode'] . '  ' . $data['LogisticCode']);
        Log::info($data);
        $order = Order::where('express', $data['ShipperCode'])
            ->where('express_no', $data['LogisticCode'])
            ->where('created_at', '>', now()->subDays(7)->toDateTimeString())
            ->first();
        if ($order) {
            // 更新order的物流信息
            $order->ship_data = json_encode($data);
            // 根据order的类型更新状态
            $prev_sale_status = $order->sale_status;
            $prev_recover_status = $order->recover_status;
            if ($order->type == Order::ORDER_TYPE_RECOVER) {
                if (
                    $data['State'] == 3 && $prev_recover_status != Order::RECOVER_STATUS_PAYING &&
                    $prev_recover_status != Order::RECOVER_STATUS_COMPLETE
                ) { // 已揽收
                    $order->ship_status = Order::SHIP_STATUS_RECEIVED;
                    $order->recover_status = Order::RECOVER_STATUS_PAYING;
                    // 提醒用户回流鱼已收货
                    event(new OrderSigned($order));
                }
            } else {
                if ($data['State'] == 1 || $data['State'] == 2) { // 已发货
                    $order->ship_status = Order::SHIP_STATUS_DELIVERED;
                    $order->sale_status = Order::SALE_STATUS_DELIVERED;
                    if($order->order_id){
                        Order::where('id',$order->order_id)->update([
                            'sale_status' => Order::SALE_STATUS_DELIVERED
                        ]);
                    }
                    // 提醒用户已发货
                    event(new OrderDelivered($order));
                } else if ($data['State'] == 3 && $prev_sale_status == Order::SALE_STATUS_DELIVERED) { // 已发货
                    $order->ship_status = Order::SHIP_STATUS_RECEIVED;
                    $order->sale_status = Order::SALE_STATUS_COMPLETE;
                    // 提醒用户已签收
                    event(new OrderCompleted($order));
                } else if ($data['State'] == 4 && $prev_sale_status == Order::SALE_STATUS_DELIVERED) {
                    $get = Cache::get('notify_order_ship_issue_' . $order->no, 0);
                    if (!$get && $order->created_at->gt(now()->subDays(7))) {
                        Cache::put('notify_order_ship_issue_' . $order->no, 1, 60 * 24);
                        // 提醒魏总问题件
                        $this->app->template_message->send([
                            'touser' => 'ojrK40dDSJ8bLfFlCkQD0GcV2DhE',
                            'template_id' => 'wLuu-n96ze_sy4gvTfigz2ujh0ILipbLefXHo11XX-c',
                            'url' => env('APP_URL') . '/wechat/sale_order/' . $order->no,
                            'data' => [
                                'first' => '问题件，请注意处理',
                                'keyword1' => $order->no,
                                'keyword2' => '已发货',
                                'keyword3' => Carbon::now()->toDateTimeString()
                            ]
                        ]);
                        // 提醒雪亮问题件
                        $this->app->template_message->send([
                            'touser' => 'ojrK40eBFLP_LWoqF6lCtyB7sIL0',
                            'template_id' => 'wLuu-n96ze_sy4gvTfigz2ujh0ILipbLefXHo11XX-c',
                            'url' => env('APP_URL') . '/wechat/sale_order/' . $order->no,
                            'data' => [
                                'first' => '问题件，请注意处理',
                                'keyword1' => $order->no,
                                'keyword2' => '已发货',
                                'keyword3' => Carbon::now()->toDateTimeString()
                            ]
                        ]);
                    }
                }
            }
            $order->save();
        }

        return response()->json([
            "EBusinessID" => "1583793",
            "UpdateTime" => now()->toDateTimeString(),
            "Success" => true,
            "Reason" => ""
        ]);
    }

    public function ztoShipCallBack()
    {
        $array = request()->all();
        Log::info('ztoShipCallBack ' . json_encode($array, JSON_UNESCAPED_UNICODE));

        return response()->json([
            "message" => "",
            "result" => "success",
            "status" => true,
            "statusCode" => "0"
        ]);
    }

    public function kdgjCallBack()
    {
        $array = request()->all();
        Log::info('kdgjCallBack ' . json_encode($array, JSON_UNESCAPED_UNICODE));
        Log::info('kdgjCallBack ' . $array);
        $orderNo = request('orderCode');
        $billNo = request('billNo');
        if ($orderNo && $billNo) {
            $order = Order::where('no', $orderNo)->first();
            if ($order && empty($order->express) && empty($order->express_no)) {
                $order->express = 'ZTO';
                $order->express_no = $billNo;
                $order->save();
            } else {
                Log::info('kdgjCallBack 订单 ' . $orderNo . ' 没找到');
            }
        }

        return response()->json([
            "status" => true,
            "message" => "成功"
        ]);
    }

    // 领取 5 元新用户现金券
    public function getNewUserCoupon()
    {
        $wx_user = session('wechat.oauth_user.default');
        $user = $this->fetchUser($wx_user);

        $user_id = $user->id;

        // 5 元新用户现金券
        $coupons = Coupon::where('user_id', $user_id)
            ->where('value', 5)
            ->where('enabled', 0)
            ->get();
        if ($coupons) {
            // 激活现金券，7 天有效期
            Coupon::where('user_id', $user_id)
                ->where('value', 5)
                ->where('enabled', 0)
                ->update([
                    'enabled' => 1,
                    'not_before' => Carbon::now(),
                    'not_after' => Carbon::now()->addDays(7)

                ]);
            return response()->json([
                'status' => true,
                'message' => '领取成功'
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => '没有可以领取的现金券'
        ]);
    }

    // 活动现金券
    public function getSpecialCoupon()
    {
        $wx_user = session('wechat.oauth_user.default');
        $user = $this->fetchUser($wx_user);

        $user_id = $user->id;


        // 活动结束时间 2019-09-27 23:59:59
        $time = time();
        $timestamp = Carbon::create(2019, 9, 28, 0, 0, 0)->timestamp;

        if ($time > $timestamp) {
            return response()->json([
               'status'     => false,
               'message'    => '活动已结束'
            ]);
        }
        $coupon = Coupon::where('user_id', $user_id)
            ->where('name', '庆融资包邮券')
            ->first();
        if (!$coupon) {

            $coupon = Coupon::create([
                'user_id'   => $user_id,
                'from'      => '庆融资包邮券',
                'from_user' => 0,
                'name'      => '庆融资包邮券',
                'type'      => Coupon::TYPE_FIXED,
                'order_type' => Coupon::ORDER_TYPE_SALE,
                'value'         => 5,
                'min_amount'    => 20,
                'not_before'    => Carbon::now(),
                'not_after'     => '2019-09-27 23:59:59',
                'enabled'       => 1
            ]);

            return response()->json([
                'status' => true,
                'data' => $coupon,
                'message' => '领取成功'
            ]);
        }

        return response()->json([
            'status' => true,
            'data' => $coupon,
            'message' => '已领取'
        ]);
    }

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

        $books = Cache::remember($tag . 'bestseller_page_' . $page, 30, function () use ($tag) {
            if ($tag == '新上架') {
                
                return Book::select('id', 'isbn', 'name', 'subtitle', 'author', 'rating_num', 'cover_replace',
                    'press', 'publish_year', 'binding', 'category', 'price', 'sale_discount',
                    'type', 'group1', 'group2', 'group3')
                    ->with('for_sale_skus.user')
                    ->with('for_sale_skus.book_version')
                    ->withCount('all_sold_sku')
                    ->where('sale_sku_count', '>', 0)
                    ->where(function($q) {
                        $tags = ['至少读两遍', '哪儿都有TA', '逢人便推荐'];
                        $q->whereIn('group1',  $tags)
                            ->orWhereIn('group2', $tags)
                            ->orWhereIn('group3', $tags);
                    })
                    ->orderByDesc('all_sold_sku_count')
                    ->paginate(28);
            } else {

                return Book::select('id', 'isbn', 'name', 'subtitle', 'author', 'rating_num', 'cover_replace',
                    'press', 'publish_year', 'binding', 'category', 'price', 'sale_discount',
                    'type', 'group1', 'group2', 'group3')
                    ->with('for_sale_skus.user')
                    ->with('for_sale_skus.book_version')
                    ->withCount('all_sold_sku')
                    ->where('sale_sku_count', '>', 0)
                    ->where(function($q) use ($tag) {

                        $q->where('group1',  $tag)
                            ->orWhere('group2', $tag)
                            ->orWhere('group3', $tag);
                    })
                    ->orderByDesc('all_sold_sku_count')
                    ->paginate(28);
            }

        });

        return $books;
    }

    public function deleteOrder()
    {
        $wx_user = session('wechat.oauth_user.default'); // 拿到授权用户资料
        $user = $this->fetchUser($wx_user);
        $order_id = request('order');
        $order = Order::find($order_id);
        if($order->user_id != $user->id || $order->order_id) {
            return response()->json([
                'code' => 500,
                'msg' => '删除失败'
            ]);
        }
        if($order->sale_status == Order::SALE_STATUS_CANCEL ||
            $order->closed == 1 ||
            $order->recover_status == Order::RECOVER_STATUS_CANCEL
        ){
            $orders = Order::where('id',$order_id)->orWhere('order_id',$order_id)->get();
            $orders->each->delete();
            return response()->json([
                'code' => 200,
                'msg' => '成功'
            ]);
        }else{
            return response()->json([
                'code' => 500,
                'msg' => '仅支持关闭、取消订单的删除'
            ]);
        }
    }

    public function getNewBooks(){
        // 折扣、评分排序 1升序 2降序
        $discount = intval(request('discount'));
        $rating = intval(request('rating'));
        // 价格筛选
        $price = trim(request('price'));
        if ($price && strpos($price,'-')){
            $low = explode('-',$price)[0];
            $high = explode('-',$price)[1];
        }else{
            $low = 0;
            $high= 999;
        }
        $page = request('page') ?: 1;
        $books = Cache::remember('newbooks_' .$discount.$rating.$price.'_'. $page, 5, function () use ($low,$high,$discount,$rating) {
            $builder = BookSku::where('level',100)->where('status',1)->with('book')->whereNotNull('original_price')
                ->whereBetween('price',[$low,$high]);
            if($discount == 1){
                $builder = $builder->orderByRaw('price/original_price');
            }elseif($discount == 2){
                $builder = $builder->orderByRaw('price/original_price desc');
            }
            if($rating == 1){$builder = $builder->orderBy('rating_num');}
            elseif ($rating == 2){$builder = $builder->orderBy('rating_num','desc');}
            return $builder->groupBy('book_id')->paginate(30);
        });
        return $books;
    }

    public function getCategoryBooks()
    {
        $page = request('page') ?: 1;
        $cate = request('cate') ?: '';
        $tag = request('tag') ?: '';
        // 旅行·美食 分拆为   旅游·地理 美食·健康
        $tags_arr = [
            '文学酒' => ["中国文学", "古典文学", "外国文学", "日本文学", "青春文学",
                "诗词世界", "散文·随笔", "纪实文学", "传记文学", "悬疑·推理", "科幻·奇幻"],
            '艺术盐' => ["电影·摄影", "艺术·设计", "书法·绘画", "音乐·戏剧", "建筑·居住"],
            '生活家' => ["时尚·化妆", "旅游·地理", "美食·健康", "运动·健身", "家居·宠物", "手工·工艺"],
            '知识面' => ["读点历史", "懂点政治", "了解经济", "管理学", "军事·战争",
                "社会·人类学", "哲学·宗教", "科普·涨知识", "国学典籍"],
            '成长树' => ["母婴育儿", "绘本故事", "儿童文学"],
            '必杀技' => ["心理学", "学会沟通", "技能提升", "职业进阶", "自我管理",
                "理财知识", "外语学习", "语言·工具", "爱情婚姻"],
            '工作狂' => ["财务会计", "新闻传播", "市场营销", "投资管理", "法律法规", "广告文案"],
            '互联网' => ["科技·互联网", "产品·运营", "开发·编程", "交互设计"],
            '创业营' => ["创业·商业", "科技·未来", "企业家", "管理学"]
        ];

        $tag_keys = array_keys($tags_arr);
        if (!in_array($cate, $tag_keys)) {
            return response()->json([
                'status' => false,
                'message' => '分类不存在'
            ]);
        }

        $tags = $tags_arr[$cate];

        if ($tag == '新上架') {
            $books = Cache::remember($cate . '_' . $tag . '_page_' . $page, 5, function () use ($tags) {
                return Book::select('id', 'isbn', 'name', 'subtitle', 'author', 'rating_num', 'cover_replace',
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
                    ->orderByDesc('updated_at')->paginate(30);
            });

            return $books;

        } else if (in_array($tag, $tags)) {
            $books = Cache::remember($cate . '_' . $tag . '_page_' . $page, 5, function () use ($tag) {
                return Book::select('id', 'isbn', 'name', 'subtitle', 'author', 'rating_num', 'cover_replace',
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
                    ->orderByDesc('all_sold_sku_count')->paginate(30);
            });

            return $books;
        }

        return response()->json([
            'status' => false,
            'message' => '标签不存在'
        ]);
    }


}
