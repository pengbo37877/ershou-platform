<?php

namespace App\Utils;


use App\Book;
use Illuminate\Support\Facades\Log;

class Tools
{
    public static function isDate($dateString)
    {
        return strtotime(date('Y-m-d', strtotime($dateString))) === strtotime($dateString);
        /*date函数会给月和日补零，所以最终用unix时间戳来校验*/
    }

    public static function isUrl($C_url)
    {
        $str = "/^http(s?):\/\/(?:[A-za-z0-9-]+\.)+[A-za-z]{2,4}(?:[\/\?#][\/=\?%\-&~`@[\]\':+!\.#\w]*)?$/";
        if (!preg_match($str, $C_url)) {
            return false;
        } else {
            return true;
        }
    }

    public static function findNum($str = '')
    {
        $str = trim($str);
        if (empty($str)) {
            return '';
        }
        $temp = array('1', '2', '3', '4', '5', '6', '7', '8', '9', '0');
        $result = '';
        for ($i = 0; $i < strlen($str); $i++) {
            if (in_array($str[$i], $temp)) {
                $result .= $str[$i];
            }
        }
        return $result;
    }

    public static function convertPrice(Book $book)
    {
        if (is_numeric($book->price) && floatval($book->price) < 100) {
            return;
        }
        $price = "";
        if (empty($book->original_price) && !is_numeric($book->price)) {
            $price = $book->price;
        }else{
            $price = $book->original_price;
        }
        if (strpos($book->isbn, '9780') === 0 || strpos($book->isbn, '9781') === 0 ||
            strpos($book->isbn, '9782') === 0 || strpos($book->isbn, '9783') === 0) {
            $price = explode('/', $price)[0];
            $price = str_replace(['：', ',', '，', ' '], '', $price);
            $prefixes = ['US$','美','USD','$'];
            $price = str_replace($prefixes, "", $price);
            Log::info('convertPrice after replace='.$price);
            if (is_numeric($price) && !is_numeric($book->original_price)) {
                $book->price = number_format(floatval($price) * 6.5, 1, '.', '');
                Log::info('convertPrice after format='.$book->price);
            }else if (strpos($price, 'GBP') === 0 || strpos($price, '£') === 0) {
                $price = str_replace(['GBP', '£'], "", $price);
                if (is_numeric($price) && !is_numeric($book->original_price)) {
                    $book->price = number_format(floatval($price) * 8.5, 1, '.', '');
                }
            }else if (strpos($price, 'EUR') === 0) {
                $price = str_replace(['EUR', ' '], "", $price);
                if (is_numeric($price) && !is_numeric($book->original_price)) {
                    $book->price = number_format(floatval($price) * 7.2, 1, '.', '');
                }
            }else if (strpos($price, 'JPY') === 0) {
                $price = str_replace(['JPY', '円','日', '（税込）', '本体', '：', ',', ' '], "", $price);
                if (is_numeric($price) && !is_numeric($book->original_price)) {
                    $book->price = number_format(floatval($price) / 17, 1, '.', '');
                }
            }
        }else if(strpos($book->isbn, '9784') === 0) { // 日语，日元
            $price = explode('/', $price)[0];
            $price = str_replace(['（税込）', '本体', '：', ',', ' '], '', $price);
            $prefixes = ['JPY','円','日'];
            $price = str_replace($prefixes, "", $price);
            if (is_numeric($price) && !is_numeric($book->original_price)) {
                $book->price = number_format(floatval($price) / 17, 1, '.', '');
            }
        }else if(strpos($book->isbn, '978957') === 0 || strpos($book->isbn, '978986') === 0) { // 台湾，新台币
            $price = explode('/', $price)[0];
            $price = str_replace(['：',',','，', ' '], '', $price);
            $prefixes = ['NT$','NTD','TWD', '新台幣', '（新台币）', '新台币', '台币', 'NT'];
            $price = str_replace($prefixes, "", $price);
            if (is_numeric($price) && !is_numeric($book->original_price)) {
                $book->price = number_format(floatval($price) / 5, 1, '.', '');
            }
        }else if(strpos($book->isbn, '978962') === 0 || strpos($book->isbn, '978988') === 0) {
            $price = explode('/', $price)[0];
            $price = str_replace(['：',',','，', ' '], '', $price);
            $prefixes = ['HKD','HK$', 'HK＄', 'HK', '港币', '港'];
            $price = str_replace($prefixes, "", $price);
            if (is_numeric($price) && !is_numeric($book->original_price)) {
                $book->price = number_format(floatval($price) / 1.1, 1, '.', '');
            }
        }
        $book->save();
    }
}