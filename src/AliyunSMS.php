<?php

namespace Qkktrip\AliyunSMS;

use Qkktrip\AliyunSMS\Api\Sms\Request\V20170525\QuerySendDetailsRequest;
use Qkktrip\AliyunSMS\Api\Sms\Request\V20170525\SendSmsRequest;
use Qkktrip\AliyunSMS\Core\Config;
use Qkktrip\AliyunSMS\Core\DefaultAcsClient;
use Qkktrip\AliyunSMS\Core\Profile\DefaultProfile;

class AliyunSMS
{
    protected $acsClient;

    protected $phoneNumbers;

    protected $request;

    public function __construct($config)
    {
        //产品名称:云通信流量服务API产品,开发者无需替换
        $product = "Dysmsapi";

        //产品域名,开发者无需替换
        $domain = "dysmsapi.aliyuncs.com";

        // AccessKeyId
        $accessKeyId = config('aliyunsms.access_id');

        // AccessKeySecret
        $accessKeySecret = config('aliyunsms.access_key');

        // 暂时不支持多Region
        $region = "cn-hangzhou";

        // 服务结点
        $endPointName = "cn-hangzhou";

        // 加载区域结点配置
        Config::load();

        //初始化acsClient,暂不支持region化
        $profile = DefaultProfile::getProfile($region, $accessKeyId, $accessKeySecret);

        // 增加服务结点
        DefaultProfile::addEndpoint($endPointName, $region, $product, $domain);

        // 初始化AcsClient用于发起请求
        $this->acsClient = new DefaultAcsClient($profile);
    }

    /**
     *发送短信
     *
     * @return $this
     */
    public function sms()
    {
        // 初始化SendSmsRequest实例用于设置发送短信的参数
        $this->request = new SendSmsRequest();

        return $this;
    }

    /**
     * 获取SendSmsRequest实例
     *
     * @return SendSmsRequest
     * @throws \Exception
     */
    public function getSmsRequest()
    {
        if ($this->request instanceof SendSmsRequest) {
            return $this->request;
        }

        throw new \Exception('Request is not instanceof SendSmsRequest');
    }

    /**
     * 初始化QuerySendDetailsRequest实例用于设置短信查询的参数
     *
     * @return $this
     */
    public function query()
    {
        $this->request = new QuerySendDetailsRequest();

        // 短信发送日期，格式Ymd，支持近30天记录查询
        $this->request->setSendDate(date('Ymd'));

        // 分页大小
        $this->request->setPageSize(10);

        // 当前页码
        $this->request->setCurrentPage(1);


        return $this;
    }

    /**
     * 获取QuerySendDetailsRequest实例
     *
     * @return QuerySendDetailsRequest
     * @throws \Exception
     */
    public function getQueryRequest()
    {
        if ($this->request instanceof QuerySendDetailsRequest) {
            return $this->request;
        }

        throw new \Exception('Request is not instanceof QuerySendDetailsRequest');
    }

    /**
     * 短信发送日期，格式Ymd，支持近30天记录查询
     *
     * @param $date
     * @return $this
     * @throws \Exception
     */
    public function date($date)
    {

        if (empty($date) || !preg_match("/^\d{8}$/", $date)) {
            throw new \Exception('Illegal send date');
        }

        $this->getQueryRequest()->setSendDate($date);

        return $this;
    }


    /**
     * 当前页码
     *
     * @param int $offset
     * @return $this
     */
    public function offset($offset = 1)
    {
        $this->getQueryRequest()->setCurrentPage($offset);
        return $this;
    }

    /**
     * 分页大小
     *
     * @param int $limit
     * @return $this
     */
    public function limit($limit = 10)
    {
        $this->getQueryRequest()->setPageSize($limit);
        return $this;
    }

    /**
     * 发起访问请求
     *
     * @return stdClass
     */
    public function send()
    {
        return $this->acsClient->getAcsResponse($this->request);
    }

    /**
     * 选填，上行短信扩展码（扩展码字段控制在7位或以下，无特殊需求用户请忽略此字段）
     *
     * @param $extendCode
     * @return $this
     * @throws \Exception
     */
    public function code($extendCode)
    {
        if (strlen($extendCode) > 7) {
            throw new \Exception('Extend code length must be equal or less than 7');
        }

        $this->request->setSmsUpExtendCode($extendCode);

        return $this;
    }

    /**
     * 可选，设置流水号
     *
     * @return $this
     */
    public function outid($outId)
    {
        $this->getSmsRequest()->setOutId($outId);

        return $this;
    }

    public function bizid($bizId)
    {
        $this->getQueryRequest()->setBizId($bizId);

        return $this;
    }

    /**
     * 可选，设置模板参数, 假如模板中存在变量需要替换则为必填项
     *
     * @param array $message 短信模板中字段的值
     * @return $this
     */
    public function message(array $message)
    {
        $this->getSmsRequest()->setTemplateParam(json_encode($message, JSON_UNESCAPED_UNICODE));

        return $this;
    }


    /**
     * 设置短信模版
     *
     * @param $template
     * @return $this
     * @throws \Exception
     */
    public function template($template)
    {
        $smsTemplate = config('aliyunsms.sms_template.' . $template);

        if (empty($smsTemplate)) {
            throw new \Exception('SMS template not found.');
        }

        // 设置签名名称
        $this->getSmsRequest()->setSignName($smsTemplate['sign_name']);

        // 设置模板CODE
        $this->getSmsRequest()->setTemplateCode($smsTemplate['template_code']);

        return $this;
    }

    /**
     * 设置短信接收号码（单条）
     *
     * @param $phoneNumber
     * @return $this
     */
    public function to($phoneNumber)
    {
        $this->checkPhoneNumber($phoneNumber);

        if ($this->request instanceof SendSmsRequest) {
            $this->request->setPhoneNumbers($phoneNumber);
        } elseif ($this->request instanceof QuerySendDetailsRequest) {
            $this->request->setPhoneNumber($phoneNumber);
        }

        return $this;
    }

    /**
     * 设置短信接收号码（群发）,支持以逗号分隔的形式进行批量调用，批量上限为1000个手机号码
     *
     * @param array|string $phoneNumbers
     * @return $this
     * @throws \Exception
     */
    public function toList(array $phoneNumbers)
    {
        $phoneList = [];
        if (is_array($phoneNumbers)) {
            $phoneList = $phoneNumbers;
        } elseif (is_string($phoneNumbers)) {
            $phoneList = explode(',', $phoneNumbers);
        }

        $count = count($phoneList);
        if ($count <= 0 || $count > 1000) {
            throw new \Exception('Illegal mobile phone number');
        }

        foreach ($phoneList as $phoneNumber) {
            $this->checkPhoneNumber($phoneNumber);
        }

        $this->getSmsRequest()->setPhoneNumbers(implode(',', $phoneList));

        return $this;
    }

    /**
     * 校验手机号
     *
     * @param $phoneNumber
     * @return bool
     * @throws \Exception
     */
    protected function checkPhoneNumber($phoneNumber)
    {
        if (empty($phoneNumber) || !preg_match("/^1[34578]{1}\d{9}$/", $phoneNumber)) {
            throw new \Exception('Illegal mobile phone number');
        }
        return true;
    }

}