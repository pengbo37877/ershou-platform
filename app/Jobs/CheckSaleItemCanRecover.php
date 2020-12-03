<?php

namespace App\Jobs;

use App\Book;
use App\SaleItem;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CheckSaleItemCanRecover implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {

    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $book_ids = SaleItem::select("book_id")->where('can_recover', true)->groupBy("book_id")->get()->pluck("book_id")->toArray();
        $books = Book::select("name","author","press","category","can_recover","discount")->whereIn('id', $book_ids)->get();
        $books->each(function ($book){
            $category = $book->category;
            $name = $book->name;
            // 标签过滤
            $notRecoverTags = ["灵修", "郭敬明", "沧月", "张小娴", "席娟", "亦舒", "言情", "教材", "教辅", "穿越", "佛教", "佛学", "耽美", "考古",
                "养生", "手工", "摄影", "工具书", "计算机", "神经网络", "算法", "程序", "web", "Web", "WEB", "UCD", "ucd", "通信", "校园", "落落",
                "几米", "漫画", "绘本", "新概念英语", "少儿","生命之水","词典","辞典","猫小乐","曹正凤","马云","马化腾","李彦宏","奢侈品","幼稚园",
                "王先霈","航空","航天","民航","Mp3","mp3","vcd","VCD","CD","cd","DVD","dvd","考研","公务员","画册","电商","电子商务","剑桥",
                "自助游", "健康", "家居", "股票", "股市", "炒股", "策划", "职场", "教育", "情感", "人际关系", "投资", "广告", "营销", "管理", "企业史",
                "创业", "数学", "安妮宝贝", "庆山", "网络小说", "学习", "育儿", "亲子", "物理", "化学", "医", "photoshop", "Photoshop", "ps", "PS", "字典",
                "意林", "读者", "儿童", "儿童文学", "java", "JAVA", "Java", "c++", "C++", "html", "HTML", "编程", "严凌君", "翻译",
                "首饰制作","首饰","烹饪","红点奖","轻小说","瑜伽","大学语文","考试","主编","PPT","ppt","郑仰霖","郑仰森","练习册","教程",
                "软件工程","习近平","共产党","外贸","能力测试","新东方","GRE","TOEFL","365天"];
            $tagBan = false;
            foreach ($notRecoverTags as $tag) {
                $c_arr = explode(',', $category);
                foreach ($c_arr as $c) {
                    if ($c == $tag) {
                        $tagBan = true;
                    }
                }
                if (strstr($name, $tag)) {
                    $tagBan = true;
                }
                if (!empty($book->press) && strstr($book->press, $tag)) {
                    $tagBan = true;
                }
                if (!empty($book->author) && strstr($book->author, $tag)) {
                    $tagBan = true;
                }
            }
            if ($tagBan) {
                $book->update([
                    'can_recover' => false,
                    'discount' => 0
                ]);
            }
        });
    }
}
