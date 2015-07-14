<?php

require_once __DIR__ . '/../src/Wechat.php';
require_once __DIR__ . '/config.php';

class MyWechat extends Wechat
{
    
}

$wechat = new MyWechat(TYPE, TOKEN, ENCODINGAESKEY, APPID, DEBUG);
