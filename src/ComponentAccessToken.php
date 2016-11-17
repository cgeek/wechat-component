<?php

namespace IWankeji\Wechat;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\FilesystemCache;
use EasyWeChat\Core\Http;
use EasyWeChat\Core\Exceptions\HttpException;

/**
 * 全局通用 ComponentAccessToken
 */
class ComponentAccessToken
{
    /**
     * App ID.
     *
     * @var string
     */
    protected $appId;

    /**
     * App secret.
     *
     * @var string
     */
    protected $secret;

    /**
     * Cache.
     *
     * @var Cache
     */
    protected $cache;

    /**
     * Cache Key.
     *
     * @var cacheKey
     */
    protected $cacheKey;

    /**
     * Http instance.
     *
     * @var Http
     */
    protected $http;

    /**
     * Query name.
     *
     * @var string
     */
    protected $queryName = 'access_token';

    /**
     * Cache key prefix.
     *
     * @var string
     */
    protected $prefix = 'iwankeji.wechat.component_access_token.';


    const API_COMPONENT_TOKEN = 'https://api.weixin.qq.com/cgi-bin/component/api_component_token';

    protected static $ticketCacheKey = 'iwankeji.wechat.component_verify_ticket';

    public function __construct($appid, $appSecret, Cache $cache = null)
    {
        $this->appid = $appid;
        $this->appSecret = $appSecret;
    }

    /**
     * 获取Token
     *
     * @return string
     */
    public function getToken($forceRefresh = false)
    {
        $cacheKey = $this->getCacheKey();
        $cached = $this->getCache()->fetch($cacheKey);

        if ($forceRefresh || empty($cached)) {
            $response = $this->getTokenFromServer();

            $this->getCache()->save($cacheKey, $response['component_access_token'], $response['expires_in'] - 1500);

            return $response['component_access_token'];
        }
        return $cached;

    }


    /**
     * Return the app id.
     *
     * @return string
     */
    public function getAppId()
    {
        return $this->appId;
    }

    /**
     * Return the secret.
     *
     * @return string
     */
    public function getSecret()
    {
        return $this->secret;
    }

    /**
     * Set cache instance.
     *
     * @param \Doctrine\Common\Cache\Cache $cache
     *
     * @return AccessToken
     */
    public function setCache(Cache $cache)
    {
        $this->cache = $cache;

        return $this;
    }

    /**
     * Return the cache manager.
     *
     * @return \Doctrine\Common\Cache\Cache
     */
    public function getCache()
    {
        return $this->cache ?: $this->cache = new FilesystemCache(sys_get_temp_dir());
    }

    /**
     * Set the query name.
     *
     * @param string $queryName
     *
     * @return $this
     */
    public function setQueryName($queryName)
    {
        $this->queryName = $queryName;

        return $this;
    }

    /**
     * Return the query name.
     *
     * @return string
     */
    public function getQueryName()
    {
        return $this->queryName;
    }

    /**
     * Return the API request queries.
     *
     * @return array
     */
    public function getQueryFields()
    {
        return [$this->queryName => $this->getToken()];
    }


    public function getTokenFromServer()
    {
        $params = array(
            'component_appid'         => $this->appid,
            'component_appsecret'     => $this->appSecret,
            'component_verify_ticket' => 'ticket@@@sugsy8cJvWVADuYIgIVeJb5u2gPfU2EGTYYN9lqR_de8RMo_jkGRubLLYof6vV0JP0pawGv4_EaMuHTH21Qv0g',
        );

        $http = $this->getHttp();

        $response = $http->parseJSON($http->json(self::API_COMPONENT_TOKEN, $params));

        if (empty($response['component_access_token'])) {
            throw new HttpException('Request AccessToken fail. response: '.json_encode($response, JSON_UNESCAPED_UNICODE));
        }

        // XXX: T_T... 7200 - 1500



        //$token = $response['component_access_token'];

        return $response;
    }

    public function getHttp()
    {
        return $this->http ?: $this->http = new Http();
    }

    /**
     * Set the http instance.
     *
     * @param \EasyWeChat\Core\Http $http
     *
     * @return $this
     */
    public function setHttp(Http $http)
    {
        $this->http = $http;

        return $this;
    }

    /**
     * Set the access token prefix.
     *
     * @param string $prefix
     *
     * @return $this
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;

        return $this;
    }

    /**
     * Set access token cache key.
     *
     * @param string $cacheKey
     *
     * @return $this
     */
    public function setCacheKey($cacheKey)
    {
        $this->cacheKey = $cacheKey;

        return $this;
    }

    /**
     * Get access token cache key.
     *
     * @return string $this->cacheKey
     */
    public function getCacheKey()
    {
        if (is_null($this->cacheKey)) {
            return $this->prefix.$this->appId;
        }

        return $this->cacheKey;
    }

}
