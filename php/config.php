<?php

// 开启SESSION
session_start();

// 设置编码格式UTF-8
header("Content-Type:text/html;charset=UTF-8");

// 屏蔽所有错误
//error_reporting(0);

define('APPID', '你的APPID');  // 公众号可不填，企业号必填
define('TOKEN', '你的TOKEN');  // 非调试模式下必填
define('ENCODINGAESKEY', '你的ENCODINGAESKEY'); // 公众号明文模式可不填，企业号必填

// 调试模式
//define('DEBUG', TRUE);
define('DEBUG', FALSE);

// 公众号类型：1.公众号; 2.企业号
//define('TYPE', 1);
define('TYPE', 1);

