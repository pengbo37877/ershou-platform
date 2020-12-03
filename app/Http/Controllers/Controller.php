<?php

namespace App\Http\Controllers;

use App\Book;
use App\Utils\HSL;
use App\Utils\RGB;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected function getBookLightColor(Book $book)
    {
        if (!$book) {
            return '#fff5f5';
        }
        if(is_null($book->color)) {
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

        $info = getimagesize($image_url);
        if (!$info) {
            return '#fff0f0';
        }
        if ($info['mime'] != 'image/jpeg') {
            return '#fff0f0';
        }
        $im  =  imagecreatefromjpeg($image_url);
        $rgb  =  imagecolorat( $im ,  10 ,  15 );

        if (!$rgb) {
            // 使用七牛的接口获取图片的主色调
            $rgb_json = json_decode(file_get_contents($image_url.'?imageAve'), true);
            $rgb = $rgb_json['RGB'];
        }

        $r  = ( $rgb  >>  16 ) &  0xFF;
        $g  = ( $rgb  >>  8 ) &  0xFF;
        $b  =  $rgb  &  0xFF ;

        $rgb = new RGB($r, $g, $b);
        $hsl = $rgb->toHSL();
        $new_hsl = new HSL($hsl->getHue(), 1, 0.97);
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
