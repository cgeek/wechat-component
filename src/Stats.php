<?php

namespace IWankeji\Wechat;

/**
 * 数据统计
 */
class Stats extends \Overtrue\Wechat\Stats
{
    /**
     * @param string|AccessToken $accessToken
     */
    public function __construct($accessToken)
    {
        $this->http = new Http($accessToken);
    }
}
