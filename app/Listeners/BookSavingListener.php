<?php

namespace App\Listeners;

use App\Book;
use App\BookSku;
use App\Events\BookSaving;
use App\ReminderItem;
use App\SaleItem;
use App\Utils\HSL;
use App\Utils\RGB;
use App\Utils\Tools;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;
use zgldh\QiniuStorage\QiniuStorage;

class BookSavingListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  BookSaving  $event
     * @return void
     */
    public function handle(BookSaving $event)
    {
        $book = $event->book;
        $book->author = ltrim($book->author);
        $sale_item_count = SaleItem::where('book_id', $book->id)->count();
        $reminder_count = ReminderItem::where('book_id', $book->id)->count();
        $sale_sku_count = BookSku::where('book_id', $book->id)->where('status', BookSku::STATUS_FOR_SALE)->count();
        $book->sale_item_count = $sale_item_count;
        $book->reminder_count = $reminder_count;
        $book->sale_sku_count = $sale_sku_count;
        if (!is_null($book->cover_replace) && !Tools::isUrl($book->cover_replace)) {
            $book->cover_replace = "http://pic.ovoooo.com/".$book->cover_replace;
        }
        if (Tools::isUrl($book->cover_replace)) {
            $pos = strpos($book->cover_replace, 'http://pbnsfovvw.bkt.clouddn.com');
            if ($pos === 0) {
                $book->cover_replace = str_replace('http://pbnsfovvw.bkt.clouddn.com', 'http://pic.ovoooo.com', $book->cover_replace);
            }
        }
        if ($book->cover_replace == 'http://pic.ovoooo.com/') {
            $disk = QiniuStorage::disk('qiniu');
            $file_name = Uuid::uuid4()->toString();
            while ($disk->exists($file_name)){
                $file_name = Uuid::uuid4()->toString();
            }
            $disk->put($file_name, $this->get_contents($book->cover_image));
            $book->cover_replace = "http://pic.ovoooo.com/".$file_name;
        }
        if (is_numeric($book->price) && floatval($book->price) < 100) {
            return;
        }
        if (strpos($book->isbn, '9780') === 0 || strpos($book->isbn, '9781') === 0 ||
            strpos($book->isbn, '9782') === 0 || strpos($book->isbn, '9783') === 0) {
            $price = explode('/', $book->original_price)[0];
            $price = str_replace(['：', ',', '，', ' '], '', $price);
            $prefixes = ['US$','美','USD','$'];
            $price = str_replace($prefixes, "", $price);
            if (is_numeric($price) && !is_numeric($book->original_price)) {
                $book->price = number_format(floatval($price) * 6.5, 1, '.', '');
            }else if (strpos($price, 'GBP') === 0 || strpos($price, '£') === 0) {
                $price = str_replace(['GBP', '£'], "", $price);
                if (is_numeric($price) && !is_numeric($book->original_price)) {
                    $book->price = number_format(floatval($price) * 8.5, 1, '.', '');
                }
            }else if (strpos($price, 'EUR') === 0) {
                $price = str_replace('EUR', "", $price);
                if (is_numeric($price) && !is_numeric($book->original_price)) {
                    $book->price = number_format(floatval($price) * 7.2, 1, '.', '');
                }
            }
        }else if(strpos($book->isbn, '9784') === 0) { // 日语，日元
            $price = explode('/', $book->original_price)[0];
            $price = str_replace(['（税込）', "(税込)",'本体', '：', ',', ' '], '', $price);
            $prefixes = ['JPY','円','日'];
            $price = str_replace($prefixes, "", $price);
            if (is_numeric($price) && !is_numeric($book->original_price)) {
                $book->price = number_format(floatval($price) / 17, 1, '.', '');
            }
        }else if(strpos($book->isbn, '978957') === 0 || strpos($book->isbn, '978986') === 0) { // 台湾，新台币
            $price = explode('/', $book->original_price)[0];
            $price = str_replace(['：',',','，', ' '], '', $price);
            $prefixes = ['NT$','NTD','TWD', '新台幣', '（新台币）', '新台币', '台币', 'NT'];
            $price = str_replace($prefixes, "", $price);
            if (is_numeric($price) && !is_numeric($book->original_price)) {
                $book->price = number_format(floatval($price) / 5, 1, '.', '');
            }
        }else if(strpos($book->isbn, '978962') === 0 || strpos($book->isbn, '978988') === 0) {
            $price = explode('/', $book->original_price)[0];
            $price = str_replace(['：',',','，', ' '], '', $price);
            $prefixes = ['HKD','HK$', 'HK＄', 'HK', '港币', '港'];
            $price = str_replace($prefixes, "", $price);
            if (is_numeric($price) && !is_numeric($book->original_price)) {
                $book->price = number_format(floatval($price) / 1.1, 1, '.', '');
            }
        }
    }

    public function get_contents($url)
    {
        $ch = curl_init();
        $timeout = 5;
        curl_setopt ($ch, CURLOPT_URL, $url);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $file_contents = curl_exec($ch);
        curl_close($ch);
        return $file_contents;
    }

    protected function getBookLightColor(Book $book)
    {
        if (!$book) {
            return '#fff5f5';
        }
        if(is_null($book->color) || $book->color=='') {
            $color = $this->getColorByImageUrl($book->cover_replace);
            $book->update([
                'color' => $color
            ]);
            return $color;
        }
        return $book->color;
    }

    private function getColorByImageUrl($image_url)
    {
        if (filter_var($image_url, FILTER_VALIDATE_URL) === FALSE) {
            return '#fff0f0';
        }

//        $info = getimagesize($image_url);
//        if (!$info) {
//            return '#fff0f0';
//        }
//        if ($info['mime'] != 'image/jpeg') {
//            return '#fff0f0';
//        }
//        $im  =  imagecreatefromjpeg($image_url);
//        $rgb  =  imagecolorat( $im ,  10 ,  15 );

//        if (!$rgb) {
//            // 使用七牛的接口获取图片的主色调
//            $rgb_json = json_decode(file_get_contents($image_url.'?imageAve'), true);
//            $rgb = hexdec($rgb_json['RGB']);
//        }
        $ch = curl_init();
        $timeout = 5;
        curl_setopt ($ch, CURLOPT_URL, $image_url.'?imageAve');
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $file_contents = curl_exec($ch);
        curl_close($ch);
        if ($file_contents) {
            $rgb_json = json_decode($file_contents, true);
            if (isset($rgb_json['RGB'])) {
                $rgb = hexdec($rgb_json['RGB']);
            }else{
                return '#fff0f0';
            }
        }else{
            return '#fff0f0';
        }

        $r  = ( $rgb  >>  16 ) &  0xFF;
        $g  = ( $rgb  >>  8 ) &  0xFF;
        $b  =  $rgb  &  0xFF ;

        $rgb = new RGB($r, $g, $b);
        $hsl = $rgb->toHSL();
        $new_hsl = new HSL($hsl->getHue(), 1, 0.99);
        $new_rgb = $new_hsl->toRGB();

        return $this->RGB2Hex($new_rgb->getRed()*256, $new_rgb->getGreen()*256, $new_rgb->getBlue()*256);
    }

    private function RGB2Hex($r, $g, $b)
    {
        if (is_array($r) && sizeof($r) == 3)
            list($r, $g, $b) = $r;
        $r = intval($r); $g = intval($g);
        $b = intval($b);
        $r = dechex($r<0?0:($r>255?255:$r));
        $g = dechex($g<0?0:($g>255?255:$g));
        $b = dechex($b<0?0:($b>255?255:$b));
        $color = (strlen($r) < 2?'0':'').$r;
        $color .= (strlen($g) < 2?'0':'').$g;
        $color .= (strlen($b) < 2?'0':'').$b;
        return '#'.$color;
    }
}
