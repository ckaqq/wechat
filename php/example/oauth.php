<?php

require_once __DIR__ . '/../src/Weixin.php';
require_once __DIR__ . '/config.php';

$weixin = new Weixin(APPID, SECRET, AGENTID);

$code = $_GET["code"] ? $_GET["code"] : '';
$state = $_GET["state"] ? $_GET["state"] : '';
$reUrl = isset($_GET['url']) ? $_GET['url'] : $_SESSION['url'];

if ($_SESSION['userInfo']) header("location: " . $reUrl);

if (!$state && !$code) {
    $currentUrl = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
    $url = $weixin->getOauthUrl($currentUrl, "login");
    header("location: {$url}");
} else {
    if ($code) {
        $result = $weixin->getOauthResult($code);
        if (!empty($result['openid'])) {
            $_SESSION['userInfo'] = $weixin->getUserInfo($result['openid']);
            header("location: " . $reUrl);
        } else {
            echo "获取个人信息失败";
        }
    } else {
        echo "登陆失败";
    }
}
