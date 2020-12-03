<?php

namespace App\Jobs;

use App\BookSku;
use App\CartItem;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SkuAotoDropPrice implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $sku;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(BookSku $sku)
    {
        $this->sku = $sku;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // 没上架的，价格非法的不调价
        if (floatval($this->sku->price)==0 || floatval($this->sku->original_price)==0 ||
        $this->sku->status == BookSku::STATUS_SOLD){
            return;
        }
        // 是否有人放入购物车
        $cc = CartItem::where('book_sku_id', $this->sku->id)->count();
        if ($cc>0) {
            SkuAotoDropPrice::dispatch($this->sku)->delay(now()->addDay());
            return;
        }
        $interval = strtotime(now()) - strtotime($this->sku->sale_at);
        if ($interval/86400>=1){ // 上架1天没卖掉，在现价基础下降1折
            $discount = $this->sku->price/$this->sku->original_price;
            if ($this->sku->level==BookSku::LEVEL_60 && $discount>0.3) { // 中等：回调到2.9折停止
                $this->sku->price = $this->sku->price * 0.95;
                $this->sku->sale_at = now();
                $this->sku->save();
                SkuAotoDropPrice::dispatch($this->sku)->delay(now()->addDay());
            }else if($this->sku->level==BookSku::LEVEL_80 && $discount>0.38) { // 上好：回调到3.5折停止
                $this->sku->price = $this->sku->price * 0.95;
                $this->sku->sale_at = now();
                $this->sku->save();
                SkuAotoDropPrice::dispatch($this->sku)->delay(now()->addDay());
            }else if($this->sku->level==BookSku::LEVEL_100 && $discount>0.45){ // 全新：回调到3.8折停止
                $this->sku->price = $this->sku->price * 0.95;
                $this->sku->sale_at = now();
                $this->sku->save();
                SkuAotoDropPrice::dispatch($this->sku)->delay(now()->addDay());
            }
        }
    }
}
