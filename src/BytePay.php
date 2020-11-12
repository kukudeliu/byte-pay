<?php


namespace Kukudeliu\BytePay;

use GuzzleHttp\Client;
use Kukudeliu\BytePay\Exceptions\HttpException;
use Kukudeliu\BytePay\Exceptions\InvalidArgumentException;

class BytePay
{
    protected $merchant_id;

    protected $merchant_secret;

    protected $byte_pay_domain;

    protected $guzzleOptions = [];

    public function __construct(array $config = [])
    {
        if (!$config['merchant_id']) {

            throw new InvalidArgumentException('商户编号 未填写');

        }

        if (!$config['merchant_secret']) {

            throw new InvalidArgumentException('商户密钥 未填写');

        }

        if (!$config['byte_pay_domain']) {

            throw new InvalidArgumentException('请求URL 未填写');

        }

        $this->merchant_id = $config['merchant_id'];

        $this->merchant_secret = $config['merchant_secret'];

        $this->byte_pay_domain = $config['byte_pay_domain'];
    }

    public function payment(array $params = [])
    {
        if (!$params['paytool']) {

            throw new InvalidArgumentException('支付方式 未填写');

        }

        switch ($params['paytool']) {

            case 'alipayswipepay': // 支付宝被扫

                $result = $this->alipayswipepay($params);

                break;

            case 'wechatswipepay': // 微信被扫

                $result = $this->wechatswipepay($params);

                break;

            case 'wechatqrcode': // 微信主扫

                $result = $this->wechatqrcode($params);

                break;

            default:

                throw new InvalidArgumentException('支付方式：' . $params['paytool'] . " 暂不支持");

        }

        return $result;

    }

    public function wechatqrcode(array $params = [])
    {
        $this->setGuzzleOptions(['headers' => ['Content-Type' => 'application/json;charset=UTF-8']]);

        $params['merchant_id'] = $this->merchant_id;

        $params['timestamp'] = time();

        $params['sign'] = $this->signature($params);

        $requestApiUrl = $this->byte_pay_domain . '/payments';

        var_dump($params);

        $response = $this->getHttpClient()->post($requestApiUrl, ['json' => $params])->getBody();

        return json_decode($response, true);
    }

    /**
     * 退款发起
     * @param array $params
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function refund(array $params = [])
    {
        $this->setGuzzleOptions(['headers' => ['Content-Type' => 'application/json;charset=UTF-8']]);

        $params['merchant_id'] = $this->merchant_id;

        $params['timestamp'] = time();

        $params['sign'] = $this->signature($params);

        $requestApiUrl = $this->byte_pay_domain . '/payment_refunds';

        $response = $this->getHttpClient()->post($requestApiUrl, ['json' => $params])->getBody();

        return json_decode($response, true);
    }

    /**
     * 查询订单
     * @param array $params
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function orderquery(array $params = [])
    {
        $params['merchant_id'] = $this->merchant_id;

        $params['timestamp'] = time();

        $params['sign'] = $this->signature($params);

        $requestApiUrl = $this->byte_pay_domain . '/payments/' . $params['ordercode'];

        $response = $this->getHttpClient()->get($requestApiUrl, ['json' => $params])->getBody();

        return json_decode($response, true);
    }


    /**
     * 微信被扫
     * @param array $params
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function wechatswipepay(array $params = [])
    {
        $params['merchant_id'] = $this->merchant_id;

        $params['timestamp'] = time();

        $params['sign'] = $this->signature($params);

        $requestApiUrl = $this->byte_pay_domain . '/payments';

        $response = $this->getHttpClient()->post($requestApiUrl, ['json' => $params])->getBody();

        return json_decode($response, true);
    }

    /**
     * 支付宝被扫
     * @param array $params
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function alipayswipepay(array $params = [])
    {
        $params['merchant_id'] = $this->merchant_id;

        $params['timestamp'] = time();

        $params['sign'] = $this->signature($params);

        $requestApiUrl = $this->byte_pay_domain . '/payments';

        $response = $this->getHttpClient()->post($requestApiUrl, ['form_params' => $params])->getBody();

        return json_decode($response, true);
    }


    private function signature(array $params)
    {
        $ASCII = $this->ASCII($params) . "&secret=" . $this->merchant_secret;

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