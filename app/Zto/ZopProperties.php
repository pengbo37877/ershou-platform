<?php

namespace App\Zto;

class ZopProperties
{
    private $companyid;
    private $key;

    /**
     * ZopProperties constructor.
     * @param $companyid
     * @param $key
     */
    public function __construct($companyid, $key)
    {
        $this->companyid = $companyid;
        $this->key = $key;
    }

    /**
     * @return mixed
     */
    public function getCompanyid()
    {
        return $this->companyid;
    }

    /**
     * @return mixed
     */
    public function getKey()
    {
        return $this->key;
    }




}