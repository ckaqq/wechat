# wechat
微信公众号和企业号模板

##一、开发者中心验证URl

###PHP
1. 新建 config.php 如下：
```php
<?php
define('APPID', '你的APPID');  // 公众号可不填，企业号必填
define('TOKEN', '你的TOKEN');  // 非调试模式下必填
define('ENCODINGAESKEY', '你的ENCODINGAESKEY'); // 公众号明文模式可不填，企业号必填
// 调试模式
define('DEBUG', FALSE);
// 公众号类型：1.公众号; 2.企业号
define('TYPE', 1);
```
2. 按照 example.php 中的样例引入文件 config.php 和 src/Wechat.php
3. 新建一个类继承 Wechat
4. 按照 example.php 中的样例启动新类即可
