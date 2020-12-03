<?php

namespace App\Http\Controllers;

use App\Book;
use App\BookSku;
use App\CartItem;
use App\Coupon;
use App\Events\BookRecoverPriceRisen;
use App\Order;
use App\ReminderItem;
use App\Shudan;
use App\User;
use App\UserAddress;
use Carbon\Carbon;
use EasyWeChat\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use zgldh\QiniuStorage\QiniuStorage;

class MiniController extends Controller
{
    protected $app;

    public function __construct()
    {
        $this->app = Factory::miniProgram([
            'app_id' => 'wx21fe0075cee9bbd5',
            'secret' => 'eee07f0d442e5a2f5ee699c5656b737e',

            'log' => [
                'level' => 'debug',
                'file' => '/var/www/ershou-platform/storage/logs/hly-miniprogram.log',
            ],
        ]);
    }

    public function login()
    {
        $code = request('code');
        $data = $this->app->auth->session($code);
        if (isset($data['unionid'])) {
            $user = User::where('union_id', $data['unionid'])->first();
            if ($user) {
                $user->update([
                    'xcx_open_id' => $data['openid'],
                    'xcx_session' => $data['session_key']
                ]);
                $data['has_user'] = true;
            }
        }
        return $data;
    }

    public function user()
    {
        $session = request('session');
        $openid = request('openid');
        $unionid = request('unionid');
        $user = User::where('union_id', $unionid)->first();
        if ($session && $openid && $user) {
            $user->xcx_session=$session;
            $user->xcx_open_id=$openid;
            $user->save();
        }
        return $user;
    }

    public function decryptedData()
    {
        $session = request('session');
        $iv = request('iv');
        $encryptedData = request('encryptedData');
        $dataArray = $this->app->encryptor->decryptData($session, $iv, $encryptedData);
        // 新建或者更新用户信息
        $unionid = $dataArray['unionId'];
        if (is_null($unionid) || empty($unionid)) {
            return null;
        }
        $openid = $dataArray['openId'];
        $nickName = $dataArray['nickName'];
        $gender = $dataArray['gender'];
        $city = $dataArray['city'];
        $province = $dataArray['province'];
        $avatarUrl = $dataArray['avatarUrl'];
        Log::info('decryptedData unionid='.$unionid);
        $user = User::where('union_id', $unionid)->first();
        if ($user) {
            // 更新
            $user->avatar = $avatarUrl;
            $user->xcx_open_id = $openid;
            $user->xcx_session = $session;
            $user->save();
        }else{
            $user = User::create([
                'xcx_open_id' => $openid,
                'xcx_session' => $session,
                'union_id' => $unionid,
                'nickname' => $nickName,
                'sex' => $gender,
                'province' => $province,
                'city' => $city,
                'avatar' => $avatarUrl
            ]);
        }
        return $user;
    }

    public function giveShareCoupons()
    {
        $fromUserId = request('from');
        $toUserId = request('to');
        $fromUser = User::find($fromUserId);
        $toUser = User::find($toUserId);
        // from得一张20元买书券，可以得多张
        $coupon20 = Coupon::where('user_id', $fromUserId)->where('from_user', $toUserId)
            ->where('from', Coupon::FROM_USER_SHARE)
            ->where('order_type', 'sale')->where('value', 20)->first();
        // to得一张10元买书券，一张5元卖书券，这种类型的只有这一次
        $coupon10 = Coupon::where('user_id', $toUserId)->where('from', Coupon::FROM_USER_SHARE)
            ->where('order_type', 'sale')->where('value', 10)->first();
        $coupon5 = Coupon::where('user_id', $toUserId)->where('from', Coupon::FROM_USER_SHARE)
            ->where('order_type', 'recover')->where('value', 5)->first();
        if ($coupon20 && $coupon10 && $coupon5) {
            return response()->json([
                'msg' => 'success',
                'code' => 200
            ]);
        }
        if (!$coupon20 && !$coupon10 && !$coupon5) {
            Coupon::create([
                'user_id' => $fromUserId,
                'from' => Coupon::FROM_USER_SHARE,
                'from_user' => $toUserId,
                'name' => '20元买书现金券',
                'type' => Coupon::TYPE_FIXED,
                'order_type' => Coupon::ORDER_TYPE_SALE,
                'value' => 20,
                'used' => 0,
                'min_amount' => 40,
                'not_after' => now()->addMonth(6)->toDateTimeString(),
                'enabled' => 0
            ]);
            // TODO 发送通知(公众号或小程序)
        }
        if (!$coupon10 && $toUser->subscribe == 1 && $toUser->created_at->isToday()) {
            Coupon::create([
                'user_id' => $toUserId,
                'from' => Coupon::FROM_USER_SHARE,
                'from_user' => $fromUserId,
                'name' => '新人10元买书券',
                'type' => Coupon::TYPE_FIXED,
                'order_type' => Coupon::ORDER_TYPE_SALE,
                'value' => 10,
                'used' => 0,
                'min_amount' => 30,
                'not_after' => now()->addMonth(6)->toDateTimeString(),
                'enabled' => 1
            ]);
            // TODO 发送通知(公众号或小程序)
        }
        if (!$coupon5 && $toUser->subscribe == 1 && $toUser->created_at->isToday()) {
            Coupon::create([
                'user_id' => $toUserId,
                'from' => Coupon::FROM_USER_SHARE,
                'from_user' => $fromUserId,
                'name' => '新人5元卖书券',
                'type' => Coupon::TYPE_FIXED,
                'order_type' => Coupon::ORDER_TYPE_RECOVER,
                'value' => 5,
                'used' => 0,
                'min_amount' => 0,
                'not_after' => now()->addMonth(6)->toDateTimeString(),
                'enabled' => 1
            ]);
            // TODO 发送通知(公众号或小程序)
        }
        return response()->json([
            'msg' => 'created',
            'code' => 200
        ]);
    }

    public function getUserTags()
    {
        $userId = request('user');
        $user = User::find($userId);
        if ($user) {
            return $user->tags->sortByDesc('pivot.created_at')->pluck('name');
        }
        return [];
    }

    public function getBooksByTag()
    {
        $tag = request('tag');
        $page = request('page')??1;
        $books = Cache::remember('xcx_tag_new_books_page_' . $page, 10, function () {
            return Book::select('id', 'isbn', 'name', 'subtitle', 'author', 'rating_num', 'cover_replace', 'press', 'publish_year', 'binding', 'category', 'price', 'sale_discount', 'type', 'group1', 'group2', 'group3')
                ->with('for_sale_skus.user')->with('for_sale_skus.book_version')->where('sale_sku_count', '>', 0)
                ->orderByDesc('updated_at')->paginate(20);
        });
        return $books;
    }

    public function searchBooks()
    {
        $q = request('q');
        $books = Book::select('id', 'isbn', 'name', 'author', 'rating_num', 'cover_replace', 'category', 'price', 'sale_discount', 'sale_sku_count')
            ->with('for_sale_skus.book_version', 'latest_sold_sku.book_version')->where(function ($query) use ($q) {
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
            })->orderByDesc('sale_sku_count')->orderByDesc('all_sku_count')->paginate(40);
        return $books;
    }

    public function getUserCartItems()
    {
        $user = User::find(request('user'));
        if (!$user) {
            return [];
        }
        $cartItems = CartItem::where('user_id', $user->id)->with('book_sku', 'book.for_sale_skus')->latest()->get();
        // 用户购物袋里的书已经卖出去了，但是这本书还有别的品相，自动帮用户选一本
        $cartItems->each(function ($item) {
            $sku = $item->book_sku;
            // 检查是否存在于某个卖单中
            if ($sku) {
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
                if (!$ciE && (!$ci || ($ci && now()->subHours(8)->gt($ci->created_at)))) {
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
        CartItem::insert($data);
        return CartItem::where('user_id', $user->id)->with('book_sku', 'book.for_sale_skus')->latest()->get();
    }

    public function getUserReminderItems()
    {
        $user = User::find(request('user'));
        if (!$user) {
            return [];
        }
        $reminders = ReminderItem::where('user_id', $user->id)->with('book.for_sale_skus')->latest()->get();
        return $reminders;
    }

    public function addSkuToCart()
    {
        $user = User::find(request('user'));
        if (!$user) {
            return response()->json(['msg'=> '用户不存在', 'code'=>500]);
        }
        $sku = BookSku::find(request('sku'));
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

    public function removeCartItem()
    {
        CartItem::destroy(request('item'));
        return response()->json(['msg' => 'success']);
    }

    public function addBookToReminder()
    {
        $user = User::find(request('user'));
        if (!$user) {
            return response()->json([
                'msg' => '用户不存在',
                'code' => 500
            ]);
        }
        $book = Book::find(request('book'));
        if (!$book) {
            return response()->json([
                'msg' => '图书不存在',
                'code' => 500
            ]);
        }
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
        return ReminderItem::with('book.for_sale_skus')->find($reminder->id);
    }

    public function removeReminderItem()
    {
        ReminderItem::destroy(request('item'));
        return response()->json(['msg' => 'success']);
    }

    public function getOpenedShudans()
    {
        return Shudan::with('coverItems.book')->where('open', true)->orderByDesc('updated_at')->get();
    }

    public function send()
    {
        $lottery = Lottery::find(1);
        $lotteryUser = LotteryUser::where('user_id', 1)->first();
        $this->app->template_message->send([
            'touser' => 'op8uO4kVUG-kTJMgAA4oQCeAaFDA',
            'template_id' => 'RieZ5veYMn0BYH5fFx3HhpRxQt4gNt4ECtHppN-2Y3U',
            'page' => 'pages/result/result?id=1',
            'form_id' => $lotteryUser->form_id,
            'data' => [
                'keyword1' => $lottery->title,
                'keyword2' => $lottery->title,
                'keyword3' => '回流鱼抽奖机 参与的抽奖正在开奖，点击查看中奖名单'
            ],
        ]);
        return response()->json(['msg' => 'ok']);
    }

    public function upload(Request $request)
    {
        $disk = QiniuStorage::disk('qiniu');
        $file = $request->file('image');
        if(!$file->isValid()){
            Log::error('上传图片有问题：'.ini_get('upload_tmp_dir'));
            Log::error('上传图片有问题：'.$file->getErrorMessage());
            return response()->json([
                'msg' => '上传图片失败',
                'code' => 500
            ]);
        }
        $filename = $disk->put('lottery', $file);
        Log::info("转存到七牛的图片名称：".$filename);
//        return 'http://pic.ovoooo.com/'.$filename;
        return response()->json([
            'url' => 'http://pic.ovoooo.com/'.$filename
        ]);
    }
}
