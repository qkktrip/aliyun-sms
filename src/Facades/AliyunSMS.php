<?php


namespace Qkktrip\AliyunSMS\Facades;

use Illuminate\Support\Facades\Facade;

class AliyunSMS extends Facade
{

    public static function getFacadeAccessor()
    {
        return 'aliyunsms';
    }

}