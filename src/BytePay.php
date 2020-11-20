<?php


namespace Kukudeliu\BytePay;

use GuzzleHttp\Client;
use Kukudeliu\BytePay\Exceptions\HttpException;
use Kukudeliu\BytePay\Exceptions\InvalidArgumentException;

class BytePay
{
    protected $config;

    protected $guzzleOptions = [];

    public function __construct(array $config)
    {
        /***** 检查参数 *****/
        $base_params = ['merchant_id' => '商户ID', 'merchant_secret' => '商户密钥', 'byte_pay_domain' => '请求地址'];

        foreach ($base_params as $key => $item) {

            if (!array_key_exists($key, $config)) {

                throw new InvalidArgumentException("参数 $item 未填写");

            }

        }

        $this->config = $config;
    }

    public function payment(array $params)
    {
        $method = $params['paytool'];

        if (method_exists($this, $method)) {

            return $this->$method($params);

        } else {

            throw new InvalidArgumentException('支付方式：' . $params['paytool'] . " 暂不支持");

        }
    }

    /**
     * 查询订单
     * @param array $params
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function orderquery(array $params = [])
    {
        [$params, $requestApiUrl] = $this->getParams($params, 'payments/' . $params['ordercode']);

        $this->setGuzzleOptions(['headers' => ['Content-Type' => 'application/json;charset=UTF-8']]);

        $response = $this->getHttpClient()->get($requestApiUrl, ['json' => $params])->getBody();

        return json_decode($response, true);
    }

    /**
     * 微信条形码支付
     * @param array $params
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function wechatswipepay(array $params = [])
    {
        [$params, $requestApiUrl] = $this->getParams($params, 'payments');

        $response = $this->getHttpClient()->post($requestApiUrl, ['json' => $params])->getBody();

        return json_decode($response, true);
    }

    /**
     * 支付宝条形码支付
     * @param array $params
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function alipayswipepay(array $params = [])
    {
        [$params, $requestApiUrl] = $this->getParams($params, 'payments');

        $response = $this->getHttpClient()->post($requestApiUrl, ['form_params' => $params])->getBody();

        return json_decode($response, true);
    }

    /**
     * 退款发起 - 目前一笔订单只能退一次
     * @param array $params
     * $params['refund_code'] => 退款订单编号
     * $params['ordercode']   => 要退款的订单编号
     * $params['amount']      => 退款金额
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function refund(array $params = [])
    {
        [$params, $requestApiUrl] = $this->getParams($params, 'payment_refunds');

        $this->setGuzzleOptions(['headers' => ['Content-Type' => 'application/json;charset=UTF-8']]);

        $response = $this->getHttpClient()->post($requestApiUrl, ['json' => $params])->getBody();

        return json_decode($response, true);
    }

    /**
     * 退款查询
     * @param array $params
     * $params['refund_code'] => 退款订单编号
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function refundquery(array $params = [])
    {
        [$params, $requestApiUrl] = $this->getParams($params, 'payment_refunds/' . $params['refund_code']);

        $this->setGuzzleOptions(['headers' => ['Content-Type' => 'application/json;charset=UTF-8']]);

        $response = $this->getHttpClient()->get($requestApiUrl, ['json' => $params])->getBody();

        return json_decode($response, true);
    }

    /**
     * 支付宝扫码支付
     * @param array $params
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function alipayqrcode(array $params = [])
    {
        [$params, $request_api_url] = $this->getParams($params, 'payments');

        $response = $this->getHttpClient()->post($request_api_url, ['json' => $params])->getBody();

        return json_decode($response, true);
    }

    /**
     * 签名验证
     * @param array $params
     * @return bool
     */
    public function verifySignature(array $params)
    {
        $config = $this->config;

        $sign = $params['sign'];

        unset($params['sign']);

        $ASCII = $this->ASCII($params) . "&secret=" . $config['merchant_secret'];

        if ($sign == strtoupper(md5($ASCII))) {

            return true;

        } else {

            return false;

        }
    }

    /**
     * 格式化参数
     * @param array $params
     * @param string $api_url
     * @return array
     */
    private function getParams(array $params, string $api_url)
    {
        $config = $this->config;

        $params['merchant_id'] = $config['merchant_id'];

        $params['timestamp'] = time();

        $params['sign'] = $this->signature($params);

        $request_api_url = $config['byte_pay_domain'] . '/' . $api_url;

        return [$params, $request_api_url];
    }


    private function signature(array $params)
    {
        $config = $this->config;

        $ASCII = $this->ASCII($params) . "&secret=" . $config['merchant_secret'];

        return strtoupper(md5($ASCII));
    }

    private function ASCII(array $params)
    {
        ksort($params);

        $str = '';

        foreach ($params as $k => $val) {

            $str .= $k . '=' . $val . '&';

        }

        $strs = rtrim($str, '&');

        return $strs;
    }

    public function getHttpClient()
    {
        return new Client($this->guzzleOptions);
    }

    public function setGuzzleOptions(array $options)
    {
        $this->guzzleOptions = $options;
    }
}