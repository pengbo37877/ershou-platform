<?php

namespace App\Jobs;

use App\Book;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;

class CopyXscBooks implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // 9787 中国大陆
        // 978962 中国香港
        // 978957 978986 台湾
        $xsc_books = DB::connection('xsc')->select("select * from books where user_add=1 and isbn like '978986%' order by id limit 500");
        foreach ($xsc_books as $xsc_book) {
            $book = Book::where('isbn', $xsc_book->isbn)->first();
            if (!$book) {
                $this->createBook($xsc_book);
            }
            DB::connection('xsc')->update("update books set user_add=2 where id=?",[$xsc_book->id]);
        }
    }

    function createBook($xsc_book) {
        $b = Book::create([
            'isbn' => $xsc_book->isbn,
            'name' => $xsc_book->name,
            'author' => $xsc_book->author,
            'press' => $xsc_book->press,
            'publish_year' => $xsc_book->publish_year,
            'original_name' => $xsc_book->original_name,
            'subtitle' => $xsc_book->subtitle,
            'price' => $this->buildPrice($xsc_book->price),
            'binding' => $xsc_book->binding,
            'series' => $xsc_book->series,
            'cover_image' => $xsc_book->cover_image,
            'rating_num' => floatval($xsc_book->rating_num),
            'category' => $xsc_book->category,
            'subjectid' => $xsc_book->subjectid,
            'cover_replace' => $xsc_book->cover_replace,
            'num_raters' => intval($xsc_book->num_raters),
            'publisher' => $xsc_book->publisher,
            'user_add' => 100,
        ]);
        if ($b) {
            $this->bookFilter($b);
        }
        return $b;
    }

    public function bookFilter(Book $book)
    {
        // 书的价格不是纯数字
        if (floatval($book->price) == 0.0) {
            $book->update([
                'can_recover' => false,
                'discount' => 0
            ]);
        }else{
            $book->update([
                'can_recover' => true,
                'discount' => 10
            ]);
        }
        $category = $book->category;
        $name = $book->name;
        $author = $book->author;
        if (empty($category) || empty($author)) {
            $book->update([
                'can_recover' => false,
                'discount' => 0
            ]);
            return;
        }
        if (strstr($name, 'CD') ||
            strstr($name, 'VCD') ||
            strstr($name, 'vcd') ||
            strstr($name, 'DVD') ||
            strstr($name, 'dvd') ||
            strstr($name, 'MP3') ||
            strstr($name, 'mp3') ||
            strstr($name, 'MP4') ||
            strstr($name, 'mp4') ||
            strstr($name, '21世纪') ||
            strstr($name, '教材') ||
            strstr($name, '教育') ||
            strstr($name, 'java') ||
            strstr($name, 'cs') ||
            strstr($name, '编程') ||
            strstr($name, '开发') ||
            strstr($name, '大全') ||
            strstr($name, '项目') ||
            strstr($category, '教材') ||
            strstr($category, '教育') ||
            strstr($category, '教辅') ||
            strstr($category, '课本') ||
            strstr($category, '玄幻') ||
            strstr($category, '修仙') ||
            strstr($category, '穿越') ||
            strstr($category, '网络小说') ||
            strstr($category, '考研') ||
            strstr($category, '论文') ||
            strstr($category, '工具') ||
            strstr($category, '计算') ||
            strstr($category, '算法') ||
            strstr($category, 'web') ||
            strstr($category, 'ucd') ||
            strstr($category, '育儿') ||
            strstr($category, '亲子') ||
            strstr($category, '养生') ||
            strstr($category, '家居') ||
            strstr($category, '自助游') ||
            strstr($category, '绘本') ||
            strstr($category, '漫画') ||
            strstr($category, '摄影') ||
            strstr($category, '股票') ||
            strstr($category, '股市') ||
            strstr($category, '校园') ||
            strstr($category, '数学') ||
            strstr($category, '物理') ||
            strstr($category, '化学') ||
            strstr($category, '医') ||
            strstr($category, '学习') ||
            strstr($category, '考古') ||
            strstr($category, '耽美') ||
            strstr($category, '佛') ||
            strstr($category, '穿越') ||
            strstr($category, '灵修') ||
            strstr($category, '晋江')) {
            $book->update([
                'can_recover' => false,
                'discount' => 0
            ]);
        }
    }

    public function buildPrice($price)
    {
        $price = preg_replace('/cny/i', '', $price);
        $price = preg_replace('/元/i', '', $price);
        $price = preg_replace('/rmb/i', '', $price);
        $price = preg_replace('/￥/i', '', $price);
        $price = preg_replace('/,/i', '', $price);
        $price = preg_replace('# #', '', $price);
        return $price;
    }
}
