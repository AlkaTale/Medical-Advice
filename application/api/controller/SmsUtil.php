<?php

namespace app\api\controller;

ini_set("display_errors", "on");

require_once VENDOR_PATH.'aliyunsms/vendor/autoload.php';

use Aliyun\Core\Config;
use Aliyun\Core\Profile\DefaultProfile;
use Aliyun\Core\DefaultAcsClient;
use Aliyun\Api\Sms\Request\V20170525\SendSmsRequest;
use Aliyun\Api\Sms\Request\V20170525\SendBatchSmsRequest;
use Aliyun\Api\Sms\Request\V20170525\QuerySendDetailsRequest;

// 加载区域结点配置
Config::load();

/**
 * Class SmsDemo
 *
 * 这是短信服务API产品的DEMO程序，直接执行此文件即可体验短信服务产品API功能
 * (只需要将AK替换成开通了云通信-短信服务产品功能的AK即可)
 * 备注:Demo工程编码采用UTF-8
 */
class SmsUtil
{

    static $acsClient = null;

    /**
     * 取得AcsClient
     *
     * @return DefaultAcsClient
     */
    public static function getAcsClient() {
        //产品名称:云通信流量服务API产品,开发者无需替换
        $product = "Dysmsapi";

        //产品域名,开发者无需替换
        $domain = "dysmsapi.aliyuncs.com";

        // TODO 此处需要替换成开发者自己的AK
        $accessKeyId = "LTAIa3LakEbYEh7t"; // AccessKeyId

        $accessKeySecret = "tCaXh43RYiz0eD2qrHYFG1fw9ZqfAC"; // AccessKeySecret

        // 暂时不支持多Region
        $region = "cn-hangzhou";

        // 服务结点
        $endPointName = "cn-hangzhou";


        if(static::$acsClient == null) {

            //初始化acsClient,暂不支持region化
            $profile = DefaultProfile::getProfile($region, $accessKeyId, $accessKeySecret);

            // 增加服务结点
            DefaultProfile::addEndpoint($endPointName, $region, $product, $domain);

            // 初始化AcsClient用于发起请求
            static::$acsClient = new DefaultAcsClient($profile);
        }
        return static::$acsClient;
    }

    /**
     * 发送短信
     * @return stdClass
     */
    public static function sendSms($phone, $code) {

        // 初始化SendSmsRequest实例用于设置发送短信的参数
        $request = new SendSmsRequest();

        //可选-启用https协议
        //$request->setProtocol("https");

        // 必填，设置短信接收号码
        $request->setPhoneNumbers($phone);

        // 必填，设置签名名称，应严格按"签名名称"填写，请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/sign
        $request->setSignName("OMS系统");

        // 必填，设置模板CODE，应严格按"模板CODE"填写, 请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/template
        $request->setTemplateCode("SMS_134318343");

        // 可选，设置模板参数, 假如模板中存在变量需要替换则为必填项
        $request->setTemplateParam(json_encode(array(  // 短信模板中字段的值
            "code"=>$code
        ), JSON_UNESCAPED_UNICODE));

        // 可选，设置流水号
        //$request->setOutId("yourOutId");

        // 选填，上行短信扩展码（扩展码字段控制在7位或以下，无特殊需求用户请忽略此字段）
        //$request->setSmsUpExtendCode("1234567");

        // 发起访问请求
        $acsResponse = static::getAcsClient()->getAcsResponse($request);

        return $acsResponse;
    }

    /**
     * 短信发送记录查询
     * @return stdClass
     */
//    public static function querySendDetails() {
//
//        // 初始化QuerySendDetailsRequest实例用于设置短信查询的参数
//        $request = new QuerySendDetailsRequest();
//
//        //可选-启用https协议
//        //$request->setProtocol("https");
//
//        // 必填，短信接收号码
//        $request->setPhoneNumber("111");
//
//        // 必填，短信发送日期，格式Ymd，支持近30天记录查询
//        $request->setSendDate("20180514");
//
//        // 必填，分页大小
//        $request->setPageSize(10);
//
//        // 必填，当前页码
//        $request->setCurrentPage(1);
//
//        // 选填，短信发送流水号
//        //$request->setBizId("yourBizId");
//
//        // 发起访问请求
//        $acsResponse = static::getAcsClient()->getAcsResponse($request);
//
//        return $acsResponse;
//    }

}

// 调用示例：
//set_time_limit(0);
//header('Content-Type: text/plain; charset=utf-8');
//
//$response = SmsDemo::sendSms();
//echo "发送短信(sendSms)接口返回的结果:\n";
//print_r($response);
//
//sleep(2);
//
////$response = SmsDemo::sendBatchSms();
////echo "批量发送短信(sendBatchSms)接口返回的结果:\n";
////print_r($response);
////
////sleep(2);
//
//$response = SmsDemo::querySendDetails();
//echo "查询短信发送情况(querySendDetails)接口返回的结果:\n";
//print_r($response);
