<?php

namespace App\Zto;

class ZopRequest
{
    private $url;
    private $params = Array();

    public function addParam($k, $v)
    {
        $this->params += [$k => $v];
    }

    /**
     * @param mixed $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    public function setData($data)
    {
        $this->params = json_decode($data);
    }


    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }


}