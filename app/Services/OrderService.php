<?php

namespace App\Services;


use App\BookSku;
use App\CartItem;
use App\Coupon;
use App\Events\BookRecoverPriceRisen;
use App\Events\BookShipper;
use App\Events\OrderCreated;
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

class OrderService
{
    // 回收单先不绑定sku，收到货审核的时候再绑定sku
    public function recoverOrderStore(User $user, $address_id, $time, $items)
    {

        if (count($items) == 0) return null;
        $is_evil = Order::where('user_id', $user->id)
            ->where('is_evil', 1)
            ->count();

        if ($is_evil > 0) {
            throw new InvalidRequestException('回流鱼暂时不收你的书', 500);
        }

        $address = UserAddress::find($address_id);
        // $count = Evil::where('district',$address->district)->orWhere('phone',$address->contact_phone)->count();
        if (
            $address->district == '沭阳县' || $address->district == '广阳区' || $address->district == '隆化县' ||
            $address->district == '常熟市' || $address->district == '越城区' || $address->contact_phone == "15811112206" ||
            $address->contact_phone == "18268887606" || $address->contact_phone == "18613520246"
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
                'address_id'        => $address->id,
                'total_amount'      => 0,
                'type'              => Order::ORDER_TYPE_RECOVER,
                'recover_status'    => Order::RECOVER_STATUS_PENDING,
                'recover_time'      => Carbon::createFromTimestamp(strtotime($time)),
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
                // 创建一个 OrderItem 并直接与当前订单关联
                $remind_count = ReminderItem::where('book_id', $data->book_id)->count();
                $cart_item_count = CartItem::where('book_id', $data->book_id)->count();
                $sale_sku_count = BookSku::where('book_id', $data->book_id)
                    ->where('status', BookSku::STATUS_FOR_SALE)
                    ->count();

                $item = $order->items()->make([
                    'amount'    => 1,
                    'price'     => $book->price * $book->discount / 100,
                    'book_id'   => $data->book_id,
                    'book_sku_id'       => $data->book_sku_id,
                    'remind_count'      => $remind_count,
                    'cart_item_count'   => $cart_item_count,
                    'sale_sku_count'    => $sale_sku_count
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

    public function saleOrderStore(User $user, $address_id, $coupon_id)
    {
        Log::info('address_id=' . $address_id);
        $cartItems = CartItem::where('user_id', $user->id)->where('selected', true)->get();
        if (count($cartItems) == 0) return null;
            $today_orders_count = Order::where('type', Order::ORDER_TYPE_SALE)
                ->where('user_id', $user->id)
                ->whereBetween('created_at', [
                    Carbon::createFromTimestamp(strtotime(date("Y-m-d")))->toDateTimeString(),
                    Carbon::createFromTimestamp(strtotime(date("Y-m-d", strtotime('+1 day'))))
                ])->count();
        if ($today_orders_count >= 5 && $user->id != 1) {
            throw new InvalidRequestException('你今天已经下了 5 个订单，请明天再来', 500);
        }

        // 开启一个数据库事务
        $order = DB::transaction(function () use ($user, $address_id, $cartItems, $coupon_id) {
            $userAddress = UserAddress::find($address_id);
            $userAddress->last_used_at = Carbon::now();
            $userAddress->save();

            // 创建一个订单
            $order = new Order([
                'total_amount'  => 0,
                'type'          => Order::ORDER_TYPE_SALE,
                'address_id'    => $address_id,
                'coupon_id'     => $coupon_id,
                'sale_status'   => Order::SALE_STATUS_PENDING,
            ]);

            // 订单关联到当前用户
            $order->user()->associate($user);

            // 写入数据库
            $order->save();

            $totalAmount = 0;

            // 遍历用户提交的 sale item
            foreach ($cartItems as $data) {
                $book_sku = $data->book_sku;
                // 查看sku是否出现在其他买单
                $other_order_item = OrderItem::whereHas('order', function ($q) {
                    $q->where('type', Order::ORDER_TYPE_SALE)
                        ->where('sale_status', '<>', Order::SALE_STATUS_CANCEL)
                        ->where('closed', 0);
                })->where('book_sku_id', $book_sku->id)
                    ->first();

                if ($other_order_item) {
                    throw new InvalidRequestException('《' . $data->book->name . '》已卖光');
                }

                // 创建一个 OrderItem 并直接与当前订单关联
                $remind_count = ReminderItem::where('book_id', $data->book_id)->count();
                $cart_item_count = CartItem::where('book_id', $data->book_id)->count();

                $sale_sku_count = BookSku::where('book_id', $data->book_id)
                    ->whereIn('status', [BookSku::STATUS_FOR_SALE, BookSku::STATUS_READY_TO_GO])
                    ->count();

                event(new BookRecoverPriceRisen($data->book));

                $item = $order->items()->make([
                    'amount'        => 1,
                    'price'         => $book_sku->price,
                    'book_id'       => $data->book_id,
                    'book_sku_id'   => $book_sku->id,
                    'remind_count'  => $remind_count,
                    'cart_item_count'   => $cart_item_count,
                    'sale_sku_count'    => $sale_sku_count,
                    'source'            => $data->source ?? 'no',
                    'up_id' => $order->id
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
                $book_sku->soldtime = time()-strtotime($book_sku->sale_at);
                $book_sku->save();

                // 上架一个同品相的sku
                $sku = BookSku::where('book_id', $book_sku->book_id)
                    ->where('status', BookSku::STATUS_READY_TO_GO)
                    ->where('level', $book_sku->level)
                    ->first();
                if ($sku) {
                    $sku->status = BookSku::STATUS_FOR_SALE;
                    $sku->save();
                }

                $totalAmount += $book_sku->price;
            }

            // 更新订单总金额，减去现金券费用
            $coupon = Coupon::find($coupon_id);
            Log::info('coupon=' . $coupon);
            if ($coupon) {

                // 总金额已经计算出来了，检查是否符合现金券规则
                $coupon->checkAvailable($totalAmount);
                $totalAmount = $coupon->getSaleAdjustedPrice($totalAmount);

                // 标记为已用
                $coupon->used = 1;
                $coupon->save();
            }

            // 更新订单总金额, 增加快递费用
            $expressFee = $this->expressFee($userAddress, $order);
            $totalAmount = $totalAmount + $expressFee;
            $order->update([
                'ship_price' => $expressFee,
                'total_amount' => $totalAmount
            ]);

            // 将下单的商品从购物车中移除
            $cartItems->each->delete();

            return $order;
        });

        // 125分钟不支付自动关闭订单
        CancelSaleOrderIn15Minutes::dispatch($order)
            ->delay(now()->addMinutes(125));

        // 给运营发消息
        event(new OrderCreated($order));

        return $order;
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
}
