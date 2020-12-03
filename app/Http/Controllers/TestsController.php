<?php

namespace App\Http\Controllers;

use App\Book;
use App\BookSku;
use App\Jobs\UpdateUserRecommend;
use Carbon\Carbon;
use App\CartItem;
use App\Order;
use App\OrderItem;
use App\ReminderItem;
use App\SaleItem;
use App\Series;
use App\User;
use App\UserBanBook;
use App\UserRecommend;
use App\UserSearchHistory;
use App\ViewBook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use EasyWeChat\OfficialAccount\Application;
use EasyWeChat\Payment\Application as WxPayment;
use App\Services\OrderService2;
use App\Zto\ZopClient;
use App\Zto\ZopProperties;
use App\Zto\ZopRequest;

class TestsController extends Controller
{

    protected $app, $payment, $orderService;

    public function __construct(Application $app, WxPayment $payment, OrderService2 $orderService)
    {
        $this->app = $app;
        $this->payment = $payment;
        $this->orderService = $orderService;
    }

    public function pushOrder($no){
        $properties = new ZopProperties("dae66ce5e08b445098f1be408a232834", "aa29ef5a1246");
        $client = new ZopClient($properties);
        $request = new ZopRequest();
        $request->setUrl("http://japi.zto.cn/exposeServicePushOrderService");

        $order = Order::where('no',$no)->first();
        if(!$order){return;}
        $address = $order->address;
        //build data;
        $data = [
            "shopKey" => "NTUzRjREMDZGMDlFNzYyRkE5MTU4RTM2MkNGOENEN0Q=",
            'orderId' => $order->no,
            "orderType" => "0",
            "receiveAddress"=> $address->address,
            "receiveCity"=> $address->city,
            "receiveCounty"=> $address->district,
            "receiveMan"=> $address->contact_name,
            "receiveMobile"=> $address->contact_phone,
            "receiveProvince"=> $address->province,
            //"sendAddress"=>"光谷步行街老尚都1栋903",
            "sendAddress"=>"光谷大道58号红桃开集团电商仓库",
            "sendCity"=>"武汉市",
            "sendCompany"=>"回流鱼",
            "sendCounty"=>"洪山区",
            "sendMan"=>"回流鱼",
            "sendMobile"=>"18310951930",
            "sendProvince"=>"湖北省",
            "orderDate" => now()->toDateTimeString(),

        ];

        $dataStr = json_encode(['data' => $data]);
        Log::info("ZtoExposeServicePushOrderServiceJob data=".$dataStr);
        $request->setData($dataStr);
        $result = $client->execute($request);
        Log::info('ZtoExposeServicePushOrderServiceJob '.$result);
        $resultArray = json_decode($result, true);
        if (isset($resultArray['statusCode']) && $resultArray['statusCode'] == 'A200') {
            $order->sale_status = Order::SALE_STATUS_ORDERED_EXPRESS;
            $order->save();
        }
        return $resultArray;
    }

    public function createSaleOrder(){
        $wx_user = session('wechat.oauth_user.default'); // 拿到授权用户资料
        $user = $this->fetchUser($wx_user);
        UpdateUserRecommend::dispatch($user->id)->delay(now()->addSecond());
        $address_id = request('address');
        $coupon_id = request('coupon');
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

    public function testPriceConvert(Book $book)
    {
        // build price
        if (!empty($book->price)) {
            // 英语，美元
            if (strpos($book->isbn, '9780') === 0) {
                $price = explode('/', $book->price)[0];
                $price = str_replace(['：', ' '], '', $price);
                $prefixes = ['US$','美','USD','$'];
                $price = str_replace($prefixes, "", $price);
                if (is_numeric($price)) {
                    return $price * 6.5;
                }
            }else if(strpos($book->isbn, '9784') === 0) { // 日语，日元
                $price = explode('/', $book->price)[0];
                $price = str_replace(['（税込）', '本体', '：', ',', ' '], '', $price);
                $prefixes = ['JPY','円','日'];
                $price = str_replace($prefixes, "", $price);
                return $price / 17;
            }else if(strpos($book->isbn, '978957') === 0 || strpos($book->isbn, '978986') === 0) { // 台湾，新台币
                $price = explode('/', $book->price)[0];
                $price = str_replace(['：',',', ' '], '', $price);
                $prefixes = ['NT$','NTD','TWD', '新台幣', '（新台币）', '新台币', '台币', 'NT'];
                $price = str_replace($prefixes, "", $price);
                return $price / 5;
            }
        }
        return 'no convert';
    }

    public function recommend()
    {
        $page = request('page')?request('page'):1;
        Log::info('getBooksByTag user='.request('user'));
        $user_id = request('user');
        if (!empty($user_id)) {
            $r = $this->recommendSubjectids($user_id);
            $r = array_filter($r);
            if (count($r) != 0) {
                $books = Book::select('id', 'isbn', 'name', 'subtitle', 'author', 'rating_num', 'cover_replace', 'press', 'publish_year', 'binding', 'category', 'price', 'sale_discount', 'type', 'group1', 'group2', 'group3')
                    ->with('for_sale_skus.user')->with('for_sale_skus.book_version')->where('sale_sku_count', '>', 0)
                    ->whereIn('subjectid', $r)
                    ->orderByRaw(DB::raw('field(subjectid, ' . implode(",", $r) . ")"))
                    ->paginate(20);
            }else{
                $books = Cache::remember('books_page_'.$page, 2, function () {
                    return Book::select('id', 'isbn', 'name', 'subtitle', 'author', 'rating_num', 'cover_replace', 'press', 'publish_year', 'binding', 'category', 'price', 'sale_discount', 'type', 'group1', 'group2', 'group3')
                        ->with('for_sale_skus.user')->with('for_sale_skus.book_version')->where('sale_sku_count', '>', 0)
                        ->orderByDesc('reminder_count')->paginate(20);
                });
            }
        } else {
            $books = Cache::remember('books_page_'.$page, 2, function () {
                return Book::select('id', 'isbn', 'name', 'subtitle', 'author', 'rating_num', 'cover_replace', 'press', 'publish_year', 'binding', 'category', 'price', 'sale_discount', 'type', 'group1', 'group2', 'group3')
                    ->with('for_sale_skus.user')->with('for_sale_skus.book_version')->where('sale_sku_count', '>', 0)
                    ->orderByDesc('reminder_count')->paginate(20);
            });
        }
        return $books;
    }

    function recommendSubjectids($user_id = 0) {
        Log::info('recommend user='.$user_id);
        $cached_recommend_set = Cache::get('user_'.$user_id.'_recommend_set');
        if ($cached_recommend_set){
            // 回流鱼在售集合 H，缓存一分钟
            $H = Cache::remember('hly_sale_books', 1, function () {
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
        Log::info('$search_book_ids count='.count($search_book_ids));
        // 已购书籍 G
//        $buy_book_ids = Cache::remember('user_'.$user_id.'_buy_books', 60, function() use ($user_id){
//            return OrderItem::select('book_id')->whereHas('order', function($q) use ($user_id){
//                $q->where('user_id', $user_id)->where('sale_status', Order::SALE_STATUS_COMPLETE);
//            })->orderByDesc('id')->take(20)->get()->pluck('book_id')->toArray();
//        });
        $buy_book_ids = OrderItem::select('book_id')->whereHas('order', function($q) use ($user_id){
            $q->where('user_id', $user_id)->where('type', Order::ORDER_TYPE_SALE);
        })->orderByDesc('id')->take(100)->get()->pluck('book_id')->toArray();
        Log::info('$buy_book_ids count='.count($buy_book_ids));
        // 购物车书籍 W
//            $cart_book_ids = Cache::remember('user_'.$user_id.'_cart_books', 10, function() use ($user_id) {
//                return CartItem::select('book_id')->where('user_id', $user_id)->get()->pluck('book_id')->toArray();
//            });
        $cart_book_ids = CartItem::select('book_id')->where('user_id', $user_id)->get()->pluck('book_id')->toArray();
        Log::info('$cart_book_ids count='.count($cart_book_ids));
        // 到货提醒 D
//            $reminder_book_ids = Cache::remember('user_'.$user_id.'_reminder_books', 10, function() use ($user_id){
//                return ReminderItem::select('book_id')->where('user_id', $user_id)
//                    ->orderByDesc('id')->take(50)->get()->pluck('book_id')->toArray();
//            });
        $reminder_book_ids = ReminderItem::select('book_id')->where('user_id', $user_id)
            ->orderByDesc('id')->take(200)->get()->pluck('book_id')->toArray();
        Log::info('$reminder_book_ids count='.count($reminder_book_ids));
        // 浏览数据 L
//            $view_book_ids = Cache::remember('user_'.$user_id.'_view_books', 10, function() use ($user_id){
//                return ViewBook::select('book_id')->where('user_id', $user_id)
//                    ->orderByDesc('id')->take(100)->get()->pluck('book_id')->toArray();
//            });
        $view_book_ids = ViewBook::select('book_id')->where('user_id', $user_id)
            ->orderByDesc('id')->take(200)->get()->pluck('book_id')->toArray();
        Log::info('$view_book_ids count='.count($view_book_ids));
        // 标签Tag
        $user = User::with('tags')->find($user_id);
        $tags_book_ids = [];
        if ($user) {
            $user_tags = $user->tags()->get()->reverse()->take(3)->pluck('name')->toArray();
            if (count($user_tags)>0) {
                $tags_book_ids = Book::select('id')->whereIn('group1', $user_tags)
                    ->where('sale_sku_count', '>', 0)->orderByDesc('reminder_count')->take(100)->get()->pluck('id')->toArray();
            }
        }
        Log::info('$tags_book_ids count='.count($tags_book_ids));
        // 用户兴趣集合 B=SS ∪ G ∪ W ∪ D ∪ L ∪ Tag
        $B = array_merge($search_book_ids, $buy_book_ids, $cart_book_ids, $reminder_book_ids, $view_book_ids, $tags_book_ids);
        $B = array_filter($B);
        Log::info('B count='.count($B));

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

        $T=[];
        foreach ($douban_subjectids as $subjectids) {
            $ids = explode(',', $subjectids);
            foreach ($ids as $id) {
                if (!empty($id)) {
                    array_push($T, $id);
                }
            }
        }
        $T = array_filter($T);
        if (count($T)<100) {
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
        Log::info('T count='.count($T));
        //TODO 系列集合 S
        $S = [];
        // 回流鱼在售集合 H，缓存一分钟
        $H = Cache::remember('hly_sale_books', 1, function () {
            return Book::select('subjectid')->where('sale_sku_count', '>', 0)->get()->pluck('subjectid')->toArray();
        });
        // 用户反馈集合 F
        $F = UserBanBook::select('subjectid')->where('user_id', $user_id)->get()->pluck('subjectid')->toArray();

        $s = [];
        foreach ($search_ids as $si) {
            $s = array_merge($s , explode(',', $si->subjectids));
        }
        $a = Cache::remember('user_'.$user_id.'_recommend_set', 10, function() use ($s, $T, $S){
            return array_slice(array_merge($s, $T, $S), 0, 3000);
        });
        $a = array_diff($a, $F);
        $r = array_intersect($H, $a);
        $d = array_diff($H, $a);
        return array_slice(array_merge($r, $d), 0, 3000);
    }

    public function bookRecommend()
    {
        $book_id=request('book');
        $book = Book::find($book_id);
        $r_ids = [];
        if ($book) {
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
        Log::info('$r_ids count='.count($r_ids));
        $a_ids = Book::select('subjectid')->where('author', 'like', '%'.$book->author.'%')->where('sale_sku_count', '>', 0)->take(10)->get()
            ->pluck('subjectid')->toArray();
        Log::info('$a_ids count='.count($a_ids));

        $c_ids = [];
        $c_array = explode(',', $book->category);
        if (count($c_array)>0) {
            foreach ($c_array as $c) {
                $ids = Book::select('subjectid')->where('category', 'like', '%'.$c.'%')->where('sale_sku_count','>',0)->take(10)->get()
                    ->pluck('subjectid')->toArray();
                $c_ids = array_merge($c_ids, $ids);
            }
        }
        Log::info('$c_ids count='.count($c_ids));
        $r_ids = array_merge($r_ids, $a_ids, $c_ids);
        Log::info('$r_ids count='.count($r_ids));
        return Book::select('isbn', 'name', 'author', 'press', 'rating_num', 'num_raters', 'sale_sku_count', 'category', 'group1', 'group2', 'group3')
            ->whereIn('subjectid', $r_ids)->where('sale_sku_count', '>', 0)
            ->orderByRaw(DB::raw('FIND_IN_SET(subjectid, "' . implode(",", $r_ids) . '"' . ")"))
            ->paginate(10);
    }


    public function lenglei()
    {
        echo 'lenglei';
    }
}
