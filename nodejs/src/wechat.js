var crypt = require('./crypt');
var querystring = require('querystring');
var url = require('url');
// 调试模式
var debug = true;

exports.wechat = function (req, res) {
    res.writeHead(200, {"Content-Type":"text/html"});
    var getQuery= url.parse(req.url).query;
    var query = querystring.parse(getQuery);
    
    var nonce = query.nonce;
    var msg_signature = query.msg_signature;
    var signature = query.signature;
    var timestamp = query.timestamp;
    var echostr = query.echostr;
    // 微信号类型，1：公众号；2：企业号
    var wechatType = signature ? 1 : 2;
    // 消息是否加密
    var encrypted = msg_signature ? true : false;
    if (!debug && !crypt.mpVerifySig(signature, timestamp, nonce)) {
        res.end('error');
    }
    if (echostr) {
        res.end(echostr);
    }
    res.end('end');
};

