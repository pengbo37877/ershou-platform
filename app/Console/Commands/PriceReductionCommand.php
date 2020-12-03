<?php

namespace App\Console\Commands;

use App\Book;
use App\BookSku;
use App\CartItem;
use App\Events\BookOnSale;
use Carbon\Carbon;
use Illuminate\Console\Command;

class PriceReductionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'price:reduction';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'SKU price reduction';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $start = time();
        $bs = BookSku::with('book')
            ->where('status', BookSku::STATUS_FOR_SALE)
            ->get();

        $bs->each(function ($sku) {

            // 豆瓣评分
            $rating_num = $sku->book->rating_num;

            // 分类
            $groups = $sku->groups;

            $group1 = '至少读两遍';
            $group2 = '哪儿都有TA';
            $group3 = '逢人便推荐';
            // 超级畅销   全新调价后不低于 5 折，上好调价后不低于 4 折
            $pos = strpos($groups, $group1) || strpos($groups, $group2) || strpos($groups, $group3);

            $new_price = floatval($sku->price) * 0.92;
            $new_original_price = floatval($sku->original_price) > 0
                ? $sku->original_price
                : $sku->book->price;
            $discount = $new_price * 10 / $new_original_price;


            if (empty($sku->sale_at)) {
                $sku->sale_at = now();
                $sku->save();
            } else if ($sku->price_reduction_count > 3) {
                return;
            } else if (now()->gt(Carbon::createFromTimeString($sku->sale_at)->addWeek($sku->price_reduction_count + 2))) {
                // 新书一次下调5%，最低不低于4折
                if ($sku->level == 100) {
                    $new_price = floatval($sku->price) * 0.95;
                    $new_original_price = floatval($sku->original_price) > 0 ? $sku->original_price : $sku->book->price;
                    $discount = $new_price * 10 / $new_original_price;

                    // 超级畅销全新，不低于 5 折
                    if ($pos !== false) {
                        if ($discount > 5) {
                            $sku->price = $new_price;
                        }
                    } else {
                        if ($discount > 4) {
                            $sku->price = $new_price;
                        }
                    }

                } else if ($sku->level == 80) {
                    // 上好一次下调8%，最低不低于3折
                    $new_price = floatval($sku->price) * 0.92;
                    $new_original_price = floatval($sku->original_price) > 0 ? $sku->original_price : $sku->book->price;
                    $discount = $new_price * 10 / $new_original_price;

                    // 超级畅销上好，不低于 4 折
                    if ($pos !== false) {
                        if ($discount > 4) {
                            $sku->price = $new_price;
                        }
                    } else {
                        if ($discount > 3) {
                            $sku->price = $new_price;
                        }
                    }

                } else {
                    $sku->price = floatval($sku->price) * 0.9;
                }

                $sku->price_reduction_count = $sku->price_reduction_count + 1;


                // 豆瓣评分   8.5分>3折，9分>4折，9.5分>5折
                $douban_95  = floatval($sku->original_price) * 0.5;
                $douban_9   = floatval($sku->original_price) * 0.4;
                $douban_85  = floatval($sku->original_price) * 0.3;
                if ($rating_num >= 9.5) {
                    if ($sku->price < $douban_95) {
                        $sku->price = $douban_95;
                    }
                } else if ($rating_num >= 9) {
                    if ($sku->price < $douban_9) {
                        $sku->price = $douban_9;
                    }
                } else if ($rating_num >= 8.5) {
                    if ($sku->price < $douban_85) {
                        $sku->price = $douban_85;
                    }
                }


                $sku->save();


                // 调价时更新图书最低折扣
                // 调价后在调整一次折扣
                $book_id = $sku->book_id;
                $book = Book::find($book_id);
                $book_skus = BookSku::where('book_id', $book_id)
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

                // 图书在售数量
                // $sale_sku_count = count($book_skus);
                //$book->sale_sku_count = $sale_sku_count;

                // 更新图书 最低折扣和 折扣价格
                $book->sale_discount        = $sale_discount;
                $book->sale_discount_price  = $min_price;
                $book->save();

                // 发送降价通知给购物车中的用户
                $cartItems = CartItem::where('book_id', $sku->book_id)->get();
                foreach ($cartItems as $cartItem) {
                    event(new BookOnSale($cartItem->user_id, $cartItem->book_id));
                }
            }
        });

        $end = time();
        $timespan = $end - $start;

        echo $timespan;
    }
}
