<?php

namespace App\Services;


use App\BookSku;
use App\CartItem;
use App\Coupon;
use App\Events\BookRecoverPriceRisen;
use App\Events\BookShipper;
use App\Events\OrderCreated;
use App\EvilPhone;
use App\Exceptions\CouponCodeUnavailableException;
use App\Exceptions\InvalidRequestException;
use App\Jobs\CancelSaleOrderIn15Minutes;
use App\OrderItem;
use App\UserAddress;
use App\Order;
use App\ReminderItem;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService3
{
    // 回收单先不绑定sku，收到货审核的时候再绑定sku
    public function recoverOrderStore(User $user, $address_id, $time, $items)
    {
        if (count($items) == 0) return null;
        $is_evil = Order::where('user_id', $user->id)->where('is_evil', 1)->count();
        if ($is_evil > 0) {
            throw new InvalidRequestException('回流鱼暂时不收你的书', 500);
        }
        $address = UserAddress::find($address_id);
        if(!$address){
            throw new InvalidRequestException('地址不存在');
        }
        $is_evil = EvilPhone::where('phone',$address->contact_phone)->first();
        if($is_evil){
            throw new InvalidRequestException('回流鱼暂时不收你的书',500);
        }
        if (
            $address->district == '沭阳县' || $address->district == '广阳区' || $address->district == '隆化县' ||
            $address->district == '常熟市' || $address->district == '越城区'
        ) {
            throw new InvalidRequestException('回流鱼暂时不收你的书', 500);
        }
        $today_orders_count = Order::where('type', Order::ORDER_TYPE_RECOVER)->where('user_id', $user->id)
            ->whereBetween('created_at', [
                Carbon::createFromTimestamp(strtotime(date("Y-m-d")))->toDateTimeString(),
                Carbon::createFromTimestamp(strtotime(date("Y-m-d", strtotime('+1 day'))))->toDateTimeString()
            ])->count();
        if ($today_orders_count >= 5) {
            throw new InvalidRequestException('你下单太频繁，请明天再来', 500);
        }
        $last_2_orders_count = Order::where('type', Order::ORDER_TYPE_RECOVER)->where('user_id', $user->id)
            ->whereBetween('created_at', [
                Carbon::createFromTimestamp(strtotime(date("Y-m-d", strtotime('-1 day'))))->toDateTimeString(),
                Carbon::createFromTimestamp(strtotime(date("Y-m-d", strtotime('+1 day'))))->toDateTimeString()
            ])->count();
        if ($last_2_orders_count >= 7) {
            throw new InvalidRequestException('你下单太频繁，请明天再来', 500);
        }
        $last_3_orders_count = Order::where('type', Order::ORDER_TYPE_RECOVER)->where('user_id', $user->id)
            ->whereBetween('created_at', [
                Carbon::createFromTimestamp(strtotime(date("Y-m-d", strtotime('-2 day'))))->toDateTimeString(),
                Carbon::createFromTimestamp(strtotime(date("Y-m-d", strtotime('+1 day'))))->toDateTimeString()
            ])->count();
        if ($last_3_orders_count >= 9) {
            throw new InvalidRequestException('你下单太频繁，请明天再来', 500);
        }
        // 开启一个数据库事务
        $order = DB::transaction(function () use ($user, $address, $time, $items) {
            // 更新此地址的最后使用时间
            $address->update(['last_used_at' => Carbon::now()]);
            // 创建一个订单
            $order = new Order([
                'address_id' => $address->id,
                'total_amount' => 0,
                'type' => Order::ORDER_TYPE_RECOVER,
                'recover_status' => Order::RECOVER_STATUS_PENDING,
                'recover_time' => Carbon::createFromTimestamp(strtotime($time)),
            ]);
            // 订单关联到当前用户
            $order->user()->associate($user);
            // 写入数据库
            $order->save();

            $totalAmount = 0;
            // 遍历用户提交的 sale item

            foreach ($items as $data) {
                $book = $data->book;
                // 创建一个 OrderItem 并直接与当前订单关联
                $remind_count = ReminderItem::where('book_id', $data->book_id)->count();
                $cart_item_count = CartItem::where('book_id', $data->book_id)->count();
                $sale_sku_count = BookSku::where('book_id', $data->book_id)->where('status', BookSku::STATUS_FOR_SALE)->count();
                $item = $order->items()->make([
                    'amount' => 1,
                    'price'  => $book->price * $book->discount / 100,
                    'book_id' => $data->book_id,
                    'book_sku_id' => $data->book_sku_id,
                    'remind_count' => $remind_count,
                    'cart_item_count' => $cart_item_count,
                    'sale_sku_count' => $sale_sku_count
                ]);
                $item->save();
                $totalAmount += $item->price;
            }
            // 总价满40或8本才能下单
            if ($totalAmount < 40 && count($items) < 8) {
                throw new InvalidRequestException('总价满40或8本才能下单', 500);
            }
            $coupon = Coupon::where('user_id', $user->id)->where('order_type', 'recover')
                ->where('used', 0)
                ->where('enabled', true)->first();
            if ($coupon) {
                // 总金额已经计算出来了，检查是否符合现金券规则
                //                $coupon->checkAvailable($totalAmount);
                // 把订单金额修改为优惠后的金额
                $totalAmount = $coupon->getRecoverAdjustedPrice($totalAmount);
                // 将订单与现金券关联
                $order->coupon()->associate($coupon);
                // 标记为已使用
                $coupon->used = true;
                $coupon->save();
            }
            // 更新订单总金额
            $order->update(['total_amount' => $totalAmount]);

            // 删除用户的sale items
            DB::delete('delete from sale_items where (can_recover=1 or `show`=0) and deleted_at is null and user_id=?', [$user->id]);

            return $order;
        });
        // 预约顺丰上门
        if ($user->id != 1 && $user->id != 3) {
            event(new BookShipper($order));
        }
        // 给运营发消息
        event(new OrderCreated($order));
        return $order;
    }

    public function saleNewOrderStore(User $user, UserAddress $userAddress, $cartItems, $order_id=null){
        Log::info('address_id=' . $userAddress->id);
        if(count($cartItems) == 0){return null;}
//        $order = DB::transaction(function () use ($user, $userAddress, $cartItems, $order_id) {
            // 创建一个订单
            $order = new Order([
                'total_amount' => 0,
                'type' => Order::ORDER_TYPE_SALE,
                'address_id' => $userAddress->id,
                'sale_status' => Order::SALE_STATUS_PENDING,
                'order_id' => $order_id,
                'new_flag' => 1
            ]);
            // 订单关联到当前用户
            $order->user()->associate($user);
            // 写入数据库
            $order->save();

            $up_id = $order_id ? $order_id:$order->id;
            $max_ship_price = null;
            $totalAmount = 0;
            foreach ($cartItems as $data) {
                $book_sku = $data->book_sku;
                $remind_count = ReminderItem::where('book_id', $data->book_id)->count();
                $cart_item_count = CartItem::where('book_id', $data->book_id)->count();
                $sale_sku_count = BookSku::where('book_id', $data->book_id)->whereIn('status', [BookSku::STATUS_FOR_SALE, BookSku::STATUS_READY_TO_GO])->count();
                if($data->amount > $book_sku->stock){
                    throw new InvalidRequestException('《' . $data->book->name . '》已卖光');
                }
                $item = $order->items()->make([
                    'amount' => $data->amount, // 新书可一次购买多本
                    'price' => $book_sku->price,
                    'book_id' => $data->book_id,
                    'book_sku_id' => $book_sku->id,
                    'remind_count' => $remind_count,
                    'cart_item_count' => $cart_item_count,
                    'sale_sku_count' => $sale_sku_count,
                    'source' => $data->source ?? 'no',
                    'up_id' => $up_id
                ]);
                $item->save();
                // 更新库存
                if ($book_sku->stock == $data->amount) {
                    $book_sku->stock = $book_sku->stock - $data->amount;
                    $book_sku->status = BookSku::STATUS_SOLD;
                } elseif ($book_sku->stock > $data->amount) {
                    $book_sku->stock = $book_sku->stock - $data->amount;
                }
                $book_sku->save();
                $fee = $this->expressNewBookFee($userAddress,$book_sku);
                if(is_null($max_ship_price)){
                    $max_ship_price = $fee;
                }else{
                    $max_ship_price = min($max_ship_price,$fee);
                }
                $totalAmount += $book_sku->price;
            }
            $totalAmount = $totalAmount + $max_ship_price;
            $order->update([
                'ship_price' => $max_ship_price,
                'total_amount' => $totalAmount
            ]);
            return $order;
//        });
    }

    public function saleESOrderStore(User $user, UserAddress $userAddress, $coupon_id, $cartItems, $order_id=null){
        if (count($cartItems) == 0) return null;
//        $order = DB::transaction(function () use ($user, $userAddress, $cartItems, $coupon_id, $order_id) {
            // 创建一个订单
            $order = new Order([
                'total_amount' => 0,
                'type' => Order::ORDER_TYPE_SALE,
                'address_id' => $userAddress->id,
                'coupon_id' => $coupon_id,
                'sale_status' => Order::SALE_STATUS_PENDING,
                'order_id' => $order_id,
                'new_flag' => 0
            ]);
            // 订单关联到当前用户
            $order->user()->associate($user);
            // 写入数据库
            $order->save();

            $up_id = $order_id ? $order_id:$order->id;
            $totalAmount = 0;
            foreach ($cartItems as $data) {
                $book_sku = $data->book_sku;
                $other_order_item = OrderItem::whereHas('order', function ($q) {
                    $q->where('type', Order::ORDER_TYPE_SALE)
                        ->where('sale_status', '<>', Order::SALE_STATUS_CANCEL)
                        ->where('closed', 0);
                })->where('book_sku_id', $book_sku->id)
                    ->first();

                if ($other_order_item) {
                    throw new InvalidRequestException('《' . $data->book->name . '》 已卖光');
                }
                $remind_count = ReminderItem::where('book_id', $data->book_id)->count();
                $cart_item_count = CartItem::where('book_id', $data->book_id)->count();
                $sale_sku_count = BookSku::where('book_id', $data->book_id)->whereIn('status', [BookSku::STATUS_FOR_SALE, BookSku::STATUS_READY_TO_GO])->count();
                $item = $order->items()->make([
                    'amount' => 1,
                    'price' => $book_sku->price,
                    'book_id' => $data->book_id,
                    'book_sku_id' => $book_sku->id,
                    'remind_count' => $remind_count,
                    'cart_item_count' => $cart_item_count,
                    'sale_sku_count' => $sale_sku_count,
                    'source' => $data->source ?? 'no',
                    'up_id' => $up_id
                ]);
                $item->save();
                // 更新sku状态
                if ($book_sku->status == BookSku::STATUS_SOLD) {
                    // 这里取消选择，让用户不用返回就可以继续下单
                    $data->selected = false;
                    $data->save();
                    throw new InvalidRequestException('《' . $data->book->name . '》已卖光');
                }
                // 标记当前sku为已售
                $book_sku->status = BookSku::STATUS_SOLD;
                $book_sku->to_order = $order->id;
                $book_sku->sold_at = now();
                $book_sku->soldtime = time() - strtotime($book_sku->sale_at);
                $book_sku->save();
                // 上架一个同品相的sku
                $sku = BookSku::where('book_id', $book_sku->book_id)->where('status', BookSku::STATUS_READY_TO_GO)->where('level', $book_sku->level)->first();
                if ($sku) {
                    $sku->status = BookSku::STATUS_FOR_SALE;
                    $sku->save();
                }
                $totalAmount += $book_sku->price;
            }
            // 更新订单总金额, 增加快递费用
            $expressFee = $this->expressFee($userAddress, $order);
            $totalAmount = $totalAmount + $expressFee;
            $order->update([
                'ship_price' => $expressFee,
                'total_amount' => $totalAmount
            ]);
            return $order;
//        });
    }

    public function saleOrderStore(User $user, $address_id, $coupon_id)
    {
        Log::info('address_id=' . $address_id);
        $userAddress = UserAddress::find($address_id);
        $userAddress->last_used_at = Carbon::now();
        $userAddress->save();
        $cartItems = CartItem::where('user_id', $user->id)->where('selected', true)->get();
        if (count($cartItems) == 0) return null;
        $today_orders_count = Order::where('type', Order::ORDER_TYPE_SALE)->where('user_id', $user->id)
            ->where('order_id',null)->whereBetween('created_at', [
                Carbon::createFromTimestamp(strtotime(date("Y-m-d")))->toDateTimeString(),
                Carbon::createFromTimestamp(strtotime(date("Y-m-d", strtotime('+1 day'))))
            ])->count();
        if ($today_orders_count >= 5 && $user->id != 1) {
            throw new InvalidRequestException('你今天已经下了 5 个订单，请明天再来', 500);
        }
        // 判断是否拆分订单
        $splitCartItems = $this->ifSplitOrder2($cartItems);
        $fat_order = DB::transaction(function() use ($user,$userAddress,$coupon_id,$cartItems,$splitCartItems){
            if(count(array_keys($splitCartItems)) > 1){
//                throw new InvalidRequestException(json_encode(array_keys($splitCartItems)), 500);
                $fat_order = new Order([
                    'total_amount' => 0,
                    'type' => Order::ORDER_TYPE_SALE,
                    'address_id' => $userAddress->id,
                    'sale_status' => Order::SALE_STATUS_PENDING
                ]);
                // 订单关联到当前用户
                $fat_order->user()->associate($user);
                // 写入数据库
                $fat_order->save();
                // 分订单
                $ship_price = 0;
                $total_amount = 0;
                foreach ($splitCartItems as $shop_id => $cartItem){
                    if($shop_id == 1){
                        $suborder = $this->saleESOrderStore($user, $userAddress, $coupon_id, $cartItem, $fat_order->id);
                    }elseif($shop_id > 1){
                        $suborder = $this->saleNewOrderStore($user, $userAddress, $cartItem, $fat_order->id);
                    }
                    $ship_price += $suborder->ship_price;
                    $total_amount += $suborder->total_amount;
                }
                $fat_order->update([
                    'ship_price' => $ship_price,
                    'total_amount' => $total_amount
                ]);
            }elseif($cartItems[0]->book_sku->ifnew != 1){
                // 一号店铺默认是回流鱼
//                throw new InvalidRequestException(json_encode(array_keys($splitCartItems)), 500);
                $fat_order = $this->saleESOrderStore($user, $userAddress, $coupon_id, $cartItems);
            }elseif($cartItems[0]->book_sku->ifnew == 1){
//                throw new InvalidRequestException('新订单', 500);
                $fat_order = $this->saleNewOrderStore($user, $userAddress, $cartItems);
            }
            // 更新订单总金额，减去现金券费用
            if($coupon_id){
                $coupon = Coupon::where('id',$coupon_id)->where('enabled',1)->where('used',0)->first();
                Log::info('coupon=' . $coupon);
                $fatTotalAmount = $fat_order->total_amount - $fat_order->ship_price;
                $fatShipPrice = $fat_order->ship_price;
                if ($coupon) {
                    // 总金额已经计算出来了，检查是否符合优惠券规则
                    try{
                        $coupon->checkAvailable($fatTotalAmount, $fatShipPrice);
                    }catch (\Exception $e){
                        throw new InvalidRequestException($e->getMessage());
                    }
                    $fatTotalAmount = $coupon->getSaleAdjustedPrice($fatTotalAmount);
                    $fat_order->total_amount = $fatTotalAmount + $fatShipPrice;
                    $fat_order->save();
                    // 标记为已用
                    $coupon->used = 1;
                    $coupon->save();
                }else{
                    $fat_order->coupon_id = null;
                    $fat_order->save();
                }
            }
            $cartItems->each->delete();
            return $fat_order;
        });
        // 125分钟不支付自动关闭订单
        CancelSaleOrderIn15Minutes::dispatch($fat_order)->delay(now()->addMinutes(125));
        // 给运营发消息
        event(new OrderCreated($fat_order));
        return $fat_order;
    }


    public function expressFee(UserAddress $address, Order $order)
    {
        $fee = 5;
        $selectedPrice = $order->items->sum->price;
        if (
            $address->province == '西藏自治区' ||
            $address->province == '新疆维吾尔自治区'
        ) {
            $fee = 20;
            if (count($order->items) > 3) {
                return $fee + (count($order->items) - 3) * 10;
            }
        } else if (
            $address->province == '内蒙古自治区' ||
            $address->province == '海南省' ||
            $address->province == '甘肃省' ||
            $address->province == '青海省' ||
            $address->province == '宁夏回族自治区'
        ) {
            $fee = 15;
            if (count($order->items) > 3) {
                return $fee + (count($order->items) - 3) * 5;
            }
        } else if ($selectedPrice >= 99) {
            return 0;
        }
        return $fee;
    }

    public function expressNewBookFee(UserAddress $userAddress,BookSku $book_sku)
    {
        $rule = $book_sku->ship_rule;
        $fee = $book_sku->ship_price;
        if($userAddress->province == "海外" || $userAddress->province == "台湾省" ||
            $userAddress->province == "香港特别行政区" || $userAddress->province == "澳门特别行政区"){
            throw new InvalidRequestException('此地区不发货');
        }
        if($rule->reject){
            $reject_areas = explode(',',$rule->reject);
            if(in_array($userAddress->province,$reject_areas)){
                throw new InvalidRequestException('此地区不发货');
            }
        }
        if($rule->content){
            $content = json_decode($rule->content,true);
            foreach ($content as $item){
                if(in_array($userAddress->province, explode(',',$item["areas"]))){
                    $fee += $item["addition"];
                }
            }
        }
        return $fee;
    }

    public function ifSplitOrder2($cartItems){
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
        return $arr;
    }
}
