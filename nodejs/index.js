var http = require('http');
var wechat = require('./src/wechat');
http.createServer(wechat.wechat).listen(18888);
console.log("wechat start listen 18889 port");
