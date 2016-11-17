<?php

namespace IWankeji\Wechat;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\FilesystemCache;

use EasyWeChat\Foundation\Config;
use Pimple\Container;

/**
 * 授权给第三方平台
 */
class Component extends Container
{
    const COMPONENT_LOGIN_PAGE = 'https://mp.weixin.qq.com/cgi-bin/componentloginpage?component_appid=%s&pre_auth_code=%s&redirect_uri=%s';
    const API_CREATE_PREAUTHCODE = 'https://api.weixin.qq.com/cgi-bin/component/api_create_preauthcode';
    const API_QUERY_AUTH = 'https://api.weixin.qq.com/cgi-bin/component/api_query_auth';
    const API_GET_AUTHORIZER_INFO = 'https://api.weixin.qq.com/cgi-bin/component/api_get_authorizer_info';
    const API_GET_AUTHORIZER_OPTION = 'https://api.weixin.qq.com/cgi-bin/component/api_get_authorizer_option';
    const API_SET_AUTHORIZER_OPTION = 'https://api.weixin.qq.com/cgi-bin/component/api_set_authorizer_option';
    /**
     * 第三方平台appid
     *
     * @var string
     */
    protected $appid;
    protected $authorizer_appid;

    private $appsecret;
    private $component_verify_ticket;

    /**
     * Http对象
     *
     * @var ComponentHttp
     */
    protected $http;

    protected $preAuthCodeCacheKey = 'iwankeji.wechat.pre_auth_code.%s';

    public function __construct($config)
    {
        //var_dump($config);

        $this->appid = $config['appId'];

        $this['config'] = function () use ($config) {
            return new Config($config);
        };


        if (!empty($this['config']['cache']) && $this['config']['cache'] instanceof CacheInterface) {
            $this['cache'] = $this['config']['cache'];
        } else {
            $this['cache'] = function () {
                return new FilesystemCache(sys_get_temp_dir());
            };
        }

        $this['component_access_token'] = function () {
            return new ComponentAccessToken(
                $this['config']['appId'],
                $this['config']['appSecret'],
                $this['cache']
            );
        };


        //var_dump($this['config']);
        //var_dump($this['component_access_token']);


        //$access_token = new ComponentAccessToken();


        $this->http = new ComponentHttp($this['component_access_token']->getToken());
        //die();
        //$this->http = new ComponentHttp(new ComponentAccessToken());
        //$this->appid = Config::get('wechat.componentAppId');

    }

    /**
     * 第三方平台授权页
     *
     * @param $redirect
     * @param null $identification
     * @return string
     */
    public function loginPage($redirect, $identification = null)
    {
        $preAuthCode = $this->createPreAuthCode($identification);
        //echo $preAuthCode;
        // 拼接出微信公众号登录授权页面url
        return sprintf(self::COMPONENT_LOGIN_PAGE, $this->appid, $preAuthCode, urlencode($redirect));
    }

    /**
     * 该API用于获取预授权码。
     * 预授权码用于公众号授权时的第三方平台方安全验证。
     *
     * @param $identification
     * @return mixed
     */
    public function createPreAuthCode($identification)
    {
        $cacheKey = sprintf($this->preAuthCodeCacheKey, $identification);
        $cached = $this['cache']->fetch($cacheKey);

        if(!empty($cached)) {
            return $cached;
        } else {
            $response = $this->http->parseJSON($this->http->json(self::API_CREATE_PREAUTHCODE, ['component_appid' => $this->appid]));
            if(isset($response['pre_auth_code']) && isset($response['expires_in'])) {
                $pre_auth_code = $response['pre_auth_code'];
                $expires_in = $response['expires_in'];
                $this['cache']->save($cacheKey, $pre_auth_code, $expires_in - 300);

                return $pre_auth_code;
            } else {
                echo 'error';
            }

        }

    }

    /**
     * 删除已经使用的预授权码.
     *
     * @param $identification
     * @return mixed
     */
    public function forgetPreAuthCode($identification)
    {
        $cacheKey = sprintf($this->preAuthCodeCacheKey, $identification);

        return Cache::forget($cacheKey);
    }

    /**
     * 使用授权码换取公众号的授权信息
     *
     * @param $authorization_code
     * @return mixed
     */
    public function queryAuth($authorization_code)
    {
        $params = array(
            'component_appid'    => $this->appid,
            'authorization_code' => $authorization_code,
        );
        return $this->http->parseJSON($this->http->json(self::API_QUERY_AUTH, $params));
    }

    /**
     * 获取授权方的账户信息
     *
     * @param $authorizer_appid
     * @return mixed
     */
    public function getAuthorizerInfo($authorizer_appid)
    {
        $params = array(
            'component_appid'  => $this->appid,
            'authorizer_appid' => $authorizer_appid,
        );
        return $this->http->parseJSON($this->http->json(self::API_GET_AUTHORIZER_INFO, $params));
    }

    /**
     * 获取授权方的选项设置信息
     *
     * @param $authorizer_appid
     * @param $option_name
     * @return mixed
     */
    public function getAuthorizerOption($authorizer_appid, $option_name)
    {
        $params = array(
            'component_appid'  => $this->appid,
            'authorizer_appid' => $authorizer_appid,
            'option_name'      => $option_name,
        );

        return $this->http->parseJSON($this->http->json(self::API_GET_AUTHORIZER_OPTION, $params));
    }

    /**
     * 设置授权方的选项信息
     *
     * @param $authorizer_appid
     * @param $option_name
     * @param $option_value
     * @return mixed
     */
    public function setAuthorizerOption($authorizer_appid, $option_name, $option_value)
    {
        $params = array(
            'component_appid'  => $this->appid,
            'authorizer_appid' => $authorizer_appid,
            'option_name'      => $option_name,
            'option_value'     => $option_value,
        );

        return $this->http->parseJSON($this->http->json(self::API_SET_AUTHORIZER_OPTION, $params));
    }
}
