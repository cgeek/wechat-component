<?php

namespace IWankeji\Wechat;

class ComponentHttp extends HttpClient
{

    public function __construct($token = null)
    {

        $this->component_access_token = $token;

        //parent::__construct();
    }
}
