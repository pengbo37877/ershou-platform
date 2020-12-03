<?php

namespace App\Http\Controllers;

use App\Book;
use App\BookSku;
use App\BookVersion;
use App\Events\OrderCompleted;
use App\EvilPhone;
use App\Jobs\FetchBookFromDouban;
use App\Order;
use App\OrderItem;
use App\PendingBook;
use App\UserAddress;
use App\Wallet;
use App\Tag;
use Carbon\Carbon;
use EasyWeChat\OfficialAccount\Application;
use http\Env\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InboundController extends Controller
{
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function inbound()
    {
        $url = env('APP_URL');
        return view('inbound.index', compact('url'));
    }

    // 顺丰单号
    public function getOrderByExpressNo()
    {
        $express_no = request('no');
        Log::info('express_no=' . $express_no);
        $order = Order::with('user', 'address')
            ->withCount('items', 'reviewed_items', 'rejected_items')
            ->where('express_no', $express_no)
            ->first();
        if (!$order) {

            return response()->json([
                'msg' => '顺丰' . $express_no . ' 订单不存在',
                'code' => 500
            ]);
        }

        return $order;
    }

    public function nextOrder()
    {
        $prev_order = Order::find(request('order'));
        if (!$prev_order) {
            return response()->json(['msg' => '订单不存在', 'code' => 500]);
        }

        $order = Order::with('user', 'address')
            ->withCount('items', 'reviewed_items', 'rejected_items')
            ->where('user_id', $prev_order->user_id)
            ->where('type', Order::ORDER_TYPE_RECOVER)
            ->where('recover_status', '<>', Order::RECOVER_STATUS_COMPLETE)
            ->where('id', '<>', request('order'))
            ->first();
        if (!$order) {

            return response()->json(['msg' => '该用户没有其他卖书订单', 'code' => 500]);
        }

        return $order;
    }

    // isbn 查找图书
    public function getBookByIsbn()
    {
        $isbn       = request('isbn');
        $order_id   = request('order');
        $user_id    = request('user');
        $book = Book::with('all_skus')
            ->with('versions')
            ->where('isbn', $isbn)
            ->first();
        if (!$book) {
            return response()->json([
                'msg' => '没有isbn为 ' . $isbn . ' 的书',
                'code' => 500
            ]);
        } else if (!empty($user_id) && intval($user_id) > 0) {
            $orderItem = OrderItem::with('book.versions')
                ->whereHas('order', function ($q) use ($user_id) {
                    $q->where('user_id', $user_id)
                        ->where('recover_status', '<>', Order::RECOVER_STATUS_COMPLETE);
                })
                ->where('book_id', $book->id)
                ->first();

            if (!$orderItem) {

                return response()->json([
                    'msg'   => '该用户没有 ' . $book->name,
                    'code'  => 500
                ]);
            }

            return $orderItem;
        } else {
            $orderItem = OrderItem::with('book.versions')
                ->where('order_id', $order_id)
                ->where('book_id', $book->id)
                ->first();
            if (!$orderItem) {
                return response()->json([
                    'msg'   => '订单中没有 ' . $book->name,
                    'code' => 500
                ]);
            }

            return $orderItem;
        }
    }

    // 下一本
    public function nextItem()
    {
        $order_id   = request('order');
        $user_id    = request('user');
        if (!empty($user_id) && intval($user_id) > 0) {

            $orderItem = OrderItem::with('book.versions')
                ->withCount(['sold_skus','storage_skus'])
                ->whereHas('order', function ($q) use ($user_id) {
                    $q->where('user_id', $user_id)
                        ->where('recover_status', '<>', Order::RECOVER_STATUS_COMPLETE);
                    })
                ->whereNull('hly_code')
                ->where('review_result', 1)
                ->first();
            if (!$orderItem) {
                return response()->json([
                    'msg' => '没有了',
                    'code' => 500
                ]);
            }

            return $orderItem;
        } else {
            $orderItem = OrderItem::with('book.versions')
                ->withCount(['sold_skus','storage_skus'])
                ->where('order_id', $order_id)
                ->whereNull('hly_code')
                ->where('review_result', 1)
                ->first();
            if (!$orderItem) {

                return response()->json([
                    'msg'   => '没有了',
                    'code'  => 500
                ]);
            }

            return $orderItem;
        }
    }

    public function addItem()
    {
        $isbn       = request('isbn');
        $order_id   = request('order');
        $book       = Book::where('isbn', $isbn)->first();
        if (!$book) {
            return response()->json(['msg' => '书籍不存在', 'code' => 500]);
        }

        $orderItem = OrderItem::create([
            'order_id' => $order_id,
            'book_id' => $book->id,
            'price' => $book->price * .1,
            'is_add' => 1
        ]);

        return OrderItem::with('book.versions')->find($orderItem->id);
    }

    public function deleteItem()
    {
        $orderItem = OrderItem::find(request('order_item'));
        if (!$orderItem) {
            return response()->json(['msg' => '不存在', 'code' => 500]);
        } else if ($orderItem->is_add == 0) {
            return response()->json(['msg' => '不可删除', 'code' => 500]);
        }
        $orderItem->delete();

        return $orderItem;
    }

    // 回收订单的图书详情
    public function allItems()
    {
        $order_id = request('order');
        $items = OrderItem::with('book')
            ->where('order_id', $order_id)
            ->get();

        return $items;
    }

    public function getSkuByHlyCode()
    {
        $code = request('code');
        Log::info('hly_code=' . $code);
        $sku = BookSku::where('hly_code', $code)->first();
        if (!$sku) {
            return response()->json([
                'msg' => '没有找到绑定了 ' . $code . ' 的书, 你可以放心用这个',
                'code' => 500
            ]);
        }

        return response()->json([
            'msg' => $code . '已经被使用了',
            'code' => 500
        ]);
    }

    public function getSkuByIsbn()
    {
        $isbn = request('isbn');
        Log::info('isbn=' . $isbn);
        $skus = BookSku::with('user', 'book')
            ->where('isbn', $isbn)
            ->whereNull('hly_code')
            ->get();
        if (!$skus) {

            return response()->json([
                'msg' => '没有找到绑定了 ' . $isbn . ' 的Sku, 是不是搞错了',
                'code' => 500
            ]);
        }

        return $skus;
    }

    public function config()
    {
        $url = \request('url');
        if ($url) {
            $config = $this->app->jssdk->setUrl(env('APP_URL') . '/inbound/' . $url)->buildConfig([
                'checkJsApi', 'scanQRCode'
            ], env('WX_DEBUG', false));
        } else {
            $config = $this->app->jssdk->setUrl(env('APP_URL') . '/inbound')->buildConfig([
                'checkJsApi', 'scanQRCode'
            ], env('WX_DEBUG', false));
        }

        return $config;
    }

    public function tags()
    {
        return Tag::all();
    }

    // 入库
    public function sku()
    {
        $isbn   = request('isbn');
        $code   = request('code');
        $price  = request('price');
        $level  = request('level');
        $title  = request('title');
        $groups = request('groups');
        $discount       = request('discount');
        $original_price = request('original_price');
        $recover_price  = request('recover_price');
        $version_id     = empty(request('version')) ? 0 : request('version');

        if (
            empty($isbn) || empty($code) || empty($price) || empty($level) || empty($title) ||
            empty($groups) || empty($discount) || empty($original_price) || empty($recover_price)
        ) {
            return reponse()->json([
                'msg' => '数据非法，提交失败',
                'code' => 500
            ]);
        }

        $sku = BookSku::where([
            'isbn'      => $isbn,
            'hly_code'  => $code
        ])->first();
        if ($sku) {
            return response()->json([
                'msg' => '已经有这个sku了',
                'code' => 500
            ]);
        }

        $book = Book::select('id', 'price')
            ->where('isbn', $isbn)
            ->first();
        $book->discount = $discount;
        $book->save();

        $sku = BookSku::create([
            'title'             => $title,
            'isbn'              => $isbn,
            'price'             => $price,
            'original_price'    => $original_price,
            'recover_price'     => $recover_price,
            'book_id'   => $book->id,
            'rating_num' => $book->rating_num,
            'level'     => $level,
            'status'    => BookSku::STATUS_NOT_FOR_SALE,
            'hly_code'  => $code,
            'groups'    => implode(',', $groups),
            'book_version_id' => $version_id
        ]);
        return $sku;
    }

    public function bandHlyCode()
    {
        return view('inbound.bandHlyCode');
    }

    // 回流鱼码
    public function band()
    {
        $sku_id     = request('sku_id');
        $hly_code   = request('hly_code');
        $sku = BookSku::find($sku_id);

        if ($sku) {
            $sku->hly_code = $hly_code;
            $sku->save();

            return $sku;
        } else {
            return \response()->json([
                'msg' => '绑定失败',
                'code' => 500
            ]);
        }
    }

    // 入库
    public function orderItem()
    {
        //        id: this.orderItem.id,
        //        code: this.hlyCode,
        //        level: this.level,
        //        title: this.title,
        //        groups: this.groups,
        //        version: this.version,
        $order_item_id  = request('id');
        $hly_code       = request('code');
        $level          = intval(request('level'));
        $title          = request('title');
        $groups         = request('groups');
        $book_version_id = request('version');
        $volume_count   = request('volume');
        $orderItem      = OrderItem::with('book')
            ->find($order_item_id);

        if (!$orderItem) {
            return response()->json(['msg' => '没找到', 'code' => 500]);
        }

        if (empty($hly_code)) {
            return response()->json(['msg' => '回流鱼码为空', 'code' => 500]);
        }

        $hly_item = OrderItem::where('hly_code', $hly_code)->first();
        if ($hly_item && $hly_item->id != $orderItem->id) {
            return response()->json(['msg' => '回流鱼码重复，请重新贴码', 'code' => 500]);
        }

        $hly_sku_count = BookSku::where('hly_code', $hly_code)->count();
        if ($hly_sku_count > 0) {
            return response()->json(['msg' => '回流鱼码重复，请重新贴码', 'code' => 500]);
        }

        if (empty($level)) {
            return response()->json(['msg' => '品相没有选', 'code' => 500]);
        }

        if ($level == 60 && count($title) == 0) {
            return response()->json(['msg' => '品相描述没有', 'code' => 500]);
        }

        // 图书分类
        if (count($groups) < 1) {
            return response()->json(['msg' => '分类为空', 'code' => 500]);
        }

        if ($volume_count > 1) {
            $book = $orderItem->book;
            $book->update([
                'volume_count' => $volume_count
            ]);
        }

        $orderItem->update([
            'hly_code'  => $hly_code,
            'level'     => $level,
            'title'     => join($title, ','),
            'groups'    => join($groups, ','),
            'book_version_id'   => $book_version_id,
            'reviewed_at'       => now()
        ]);

        return $orderItem;
    }

    // 拒收理由
    public function deny()
    {
        $order_item_id = request('id');
        $reason = request('reason');
        $volume_count = request('volume');
        if (empty($reason)) {
            return response()->json(['msg' => '拒收原因必填', 'code' => 500]);
        }

        $orderItem = OrderItem::with('book')->find($order_item_id);
        if (!$orderItem) {
            return response()->json(['msg' => '没找到', 'code' => 500]);
        }

        if ($volume_count > 1) {
            $book = $orderItem->book;
            $book->update([
                'volume_count' => $volume_count
            ]);
        }

        $orderItem->update([
            'review_result' => OrderItem::REVIEW_REJECT,
            'review'        => $reason,
            'review_at'     => Carbon::now(),
        ]);

        return $orderItem;
    }

    // 审核完
    public function completeOrder()
    {
        $order_id = request('order');
        $order = Order::withCount(['items', 'reviewed_items', 'rejected_items'])
            ->find($order_id);
        $review_count = $order->reviewed_items_count + $order->rejected_items_count;

        if ($review_count < $order->items_count) {
            return response()->json(['msg' => '还没审核完', 'code' => 500]);
        }
        if ($order->type == Order::ORDER_TYPE_SALE) {
            return response()->json(['msg' => '这是用户买书的订单', 'code' => 500]);
        }

        event(new OrderCompleted($order));

        return $order;
    }

    // 更新运费
    public function updateShipPrice()
    {
        $order_id = request('order');
        $price = request('price');
        $order = Order::with('user', 'address')
            ->withCount('items', 'reviewed_items', 'rejected_items')
            ->find($order_id);
        if (!$order) {
            return response()->json(['msg' => '没找到订单', 'code' => 500]);
        }

        $order->update([
            'ship_price' => $price
        ]);

        return $order;
    }

    public function userSituation()
    {
        $user_id = request('user');
        $complete_orders = Order::with('items')
            ->where('user_id', $user_id)
            ->where('closed', 0)
            ->where('recover_status', Order::RECOVER_STATUS_COMPLETE)
            ->get();

        $evil_count = Order::where('user_id', $user_id)
            ->where('is_evil', 1)
            ->count();

        return response()->json([
	        'complete_count'    => count($complete_orders),
	        'orders'            => $complete_orders,
            'evil_count'        => $evil_count
        ]);
    }

    // 加版本
    public function addVersion()
    {
        $book_id = request('book');
        $price = request('price');
        $book = Book::find($book_id);
        if (!$book) {
            return response()->json(['msg' => '书不存在', 'code' => 500]);
        }

        $version = BookVersion::create([
            'book_id' => $book_id,
            'title' => $price,
            'price' => $price,
            'cover' => $book->cover_replace,
            'press' => $book->press,
            'publish_year' => $book->publish_year,
            'name' => $book->name
        ]);

        return $version;
    }

    // 标恶意
    public function markOrderAsEvil()
    {
        $order = Order::find(request('order'));
        if ($order && $order->type === Order::ORDER_TYPE_SALE) {
            return response()->json(['msg' => '非回收类订单不能标记', 'code' => 500]);
        }
        $order->is_evil = !$order->is_evil;
        $order->save();
        $evil = EvilPhone::where('phone',$order->address->contact_phone)
            ->where('order_id',$order->id)->first();
        if($order->is_evil){
            if(!$evil) {
                EvilPhone::create([
                    'user_id' => $order->user_id,
                    'order_id' => $order->id,
                    'username' => $order->address->contact_name,
                    'phone' => $order->address->contact_phone
                ]);
            }
        }else{
            if($evil){
                $evil->delete();
            }
        }
        return Order::with('user', 'address')->withCount('items', 'reviewed_items', 'rejected_items')
            ->find(request('order'));
    }

    // 拒收
    public function banBook()
    {
        $ban = request('ban');
        $orderItem = OrderItem::with('book.versions')
            ->find(request('order_item'));
        if (empty($orderItem)) {
            return response()->json(['msg' => '失败', 'code' => 500]);
        }

        $book = $orderItem->book;
        $book->can_recover = !boolval($ban);
        $book->admin_user_id = 89;
        $book->save();

        return $orderItem;
    }

    // 审核完
    public function reviewOk()
    {
        $orderItem = OrderItem::with('book.versions')->find(request('id'));
        $wallet = Wallet::where('order_id', $orderItem->order_id)->first();
        if ($wallet) {
            return response()->json([
                'msg' => '订单已打款，请独立处理本书',
                'code' => 500
            ]);
        }
        if (!$orderItem) {
            return response()->json([
                'msg' => '不存在',
                'code' => 500
            ]);
        }
        $orderItem->review_result = 1;
        $orderItem->save();

        return $orderItem;
    }
}
