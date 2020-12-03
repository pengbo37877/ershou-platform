<?php

namespace App\Console\Commands;

use App\BookSku;
use App\ShudanComment;
use Illuminate\Console\Command;

class PriceReductionShudanCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'price:shudan';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create price reduction shudan';

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
        // 把无货的删除
        $items = ShudanComment::where('shudan_id', 1)->with('for_sale_skus')->get();
        $items->each(function($item) {
            if (count($item->for_sale_skus)==0) {
                $item->delete();
            }
        });
        // 1、降价超过3次的书
        // 2、定价低于6块的书
        // 3、按更新时间倒排
        $ids1 = BookSku::select('book_id')->where('status', BookSku::STATUS_FOR_SALE)->where('price_reduction_count', '>=', 3)
            ->get()->pluck('book_id')->toArray();
        $ids2 = BookSku::select('book_id')->where('status', BookSku::STATUS_FOR_SALE)->where('price', '<', 6)
            ->get()->pluck('book_id')->toArray();
        $ids = array_filter(array_merge($ids1, $ids2));
        for ($i=0;$i<count($ids);$i++) {
            $exist_item = ShudanComment::where('book_id', $ids[$i])->first();
            if (!$exist_item) {
                ShudanComment::create([
                    'shudan_id' => 1,
                    'comment_id' => 0,
                    'book_id' => $ids[$i]
                ]);
            }
        }
    }
}
