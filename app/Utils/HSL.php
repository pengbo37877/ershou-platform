<?php

namespace App\Utils;

class HSL
{
    /**
     * 色相 0-360
     * @var float
     */
    protected $_hue;

    /**
     * 饱和度 0-1
     * @var float
     */
    protected $_saturation;

    /**
     * 亮度 0-1
     * @var float
     */
    protected $_lightness;

    /**
     * 构造HSL色彩空间描述
     * @param float $hue
     * @param float $saturation
     * @param float $lightness
     */
    public function __construct($hue=0, $saturation=0, $lightness=0) {
        $this->_hue = $hue;
        $this->_saturation = $saturation;
        $this->_lightness = $lightness;
    }

    /**
     * 获取色相
     * @return float
     */
    public function getHue() {
        return $this->_hue;
    }

    /**
     * 获取饱和度
     * @return float
     */
    public function getSaturation() {
        return $this->_saturation;
    }

    /**
     * 获取亮度
     * @return float
     */
    public function getLightness() {
        return $this->_lightness;
    }

    /**
     * 获取RGB形式色彩空间描述
     * @return RGB
     */
    public function toRGB() {
        $h = $this->getHue();
        $s = $this->getSaturation();
        $l = $this->getLightness();

        if ($s == 0) {
            return new RGB($l, $l, $l);
        }

        $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - ($l * $s);
        $p = 2 * $l - $q;
        $hk = $h / 360;
        $tR = $hk + (1 / 3);
        $tG = $hk;
        $tB = $hk - (1 / 3);

        $tR = $this->getTC($tR);
        $tG = $this->getTC($tG);
        $tB = $this->getTC($tB);
        $tR = $this->getColorC($tR, $p, $q);
        $tG = $this->getColorC($tG, $p, $q);
        $tB = $this->getColorC($tB, $p, $q);
        return new RGB($tR, $tG, $tB);
    }

    private function getColorC($tc, $p, $q) {
        if ($tc < (1 / 6)) {
            return $p + (($q - $p) * 6 * $tc );
        } else if ((1 / 6) <= $tc && $tc < 0.5) {
            return $q;
        } else if (0.5 <= $tc && $tc < (2 / 3)) {
            return $p + (($q - $p) * 6 * (2 / 3 - $tc) );
        } else {
            return $p;
        }
    }

    private function getTC($c) {
        if ($c < 0)
            $c++;
        if ($c > 1)
            $c--;
        return $c;
    }

    /**
     * 获取 array形式HSL色彩描述
     * @return array
     */
    public function toArray() {
        return array(
            'hue' => $this->getHue(),
            'saturation' => $this->getSaturation(),
            'lightness' => $this->getLightness()
        );
    }
}