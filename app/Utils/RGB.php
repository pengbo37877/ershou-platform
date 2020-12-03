<?php

namespace App\Utils;

use ErrorException;

class RGB
{
    /**
     * 红色 0-1
     * @var float
     */
    protected $_red;

    /**
     * 绿色 0-1
     * @var float
     */
    protected $_green;

    /**
     * 蓝色 0-1
     * @var float
     */
    protected $_blue;

    /**
     * 以初始值构造
     * @param float $red 红色0-1
     * @param float $green 绿色0-1
     * @param float $blue 蓝色0-1
     */
    public function __construct($red = 0, $green = 0, $blue = 0) {
        $this->_red = $red;
        $this->_green = $green;
        $this->_blue = $blue;
    }

    /**
     * 获取红色分量
     * @return float
     */
    public function getRed() {
        return $this->_red;
    }

    /**
     * 获取绿色分量
     * @return float
     */
    public function getGreen() {
        return $this->_green;
    }

    /**
     * 获取蓝色分量
     * @return float
     */
    public function getBlue() {
        return $this->_blue;
    }

    /**
     * 返回该色彩的HSL空间描述
     * @return HSL
     */
    public function toHSL() {
        $r = $this->getRed();
        $g = $this->getGreen();
        $b = $this->getBlue();
        $rgb = array($r, $g, $b);
        $max = max($rgb);
        $min = min($rgb);
        $diff = $max - $min;
        if ($max == $min) {
            $h = 0;
        } else if ($max == $r && $g >= $b) {
            $h = 60 * (($g - $b) / $diff);
        } else if ($max == $r && $g < $b) {
            $h = 60 * (($g - $b) / $diff) + 360;
        } else if ($max == $g) {
            $h = 60 * (($b - $r) / $diff) + 120;
        } else if ($max == $b) {
            $h = 60 * (($r - $g) / $diff) + 240;
        } else {
            throw new ErrorException("RGB conversion HSL failure!");
        }
        $l = ($max + $min) / 2;
        if ($l == 0 || $max == $min) {
            $s = 0;
        } else if (0 < $l && $l <= 0.5) {
            $s = $diff / (2 * $l);
        } else if ($l > 0.5) {
//            $s = $diff / (2 - 2 * $l);
            $s = $diff / (2 - 1.99 * $l);
        } else {
            throw new ErrorException("RGB conversion HSL failure!");
        }
        return new HSL($h, $s, $l);
    }

    /**
     * 返回此色彩的HSV空间描述
     * @return HSV
     */
    public function toHSV() {
        $red = $this->getRed();
        $green = $this->getGreen();
        $blue = $this->getBlue();

        $rgb = array($red, $green, $blue);
        $max = max($rgb);
        $min = min($rgb);
        $diff = $max - $min;

        /* 计算色相 */
        if ($max == $min) {
            $hue = 0;
        } else if ($max == $red && $green >= $blue) {
            $hue = 60 * (($green - $blue) / $diff);
        } else if ($max == $red && $green < $blue) {
            $hue = 60 * (($green - $blue) / $diff) + 360;
        } else if ($max == $green) {
            $hue = 60 * (($blue - $red) / $diff) + 120;
        } else if ($max == $blue) {
            $hue = 60 * (($red - $green) / $diff) + 240;
        } else {
            throw new ErrorException("compute hue failure!");
        }

        /* 计算饱和度 */
        if ($max == 0) {
            $saturation = 0;
        } else {
            $saturation = 1 - $min / $max;
        }

        /* 计算色调 */
        $value = $max;

        return new HSV($hue, $saturation, $value);
    }

    /**
     * 返回该色彩的数组表现形式
     */
    public function toArray() {
        return array(
            'red' => $this->getRed(),
            'green' => $this->getGreen(),
            'blue' => $this->getBlue()
        );
    }
}