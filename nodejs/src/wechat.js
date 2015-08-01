var crypt = require('./crypt');
var xml = require("node-xml");
var querystring = require('querystring');
var url = require('url');
var http = require('http');
var config = {};
var data = '';

// 解析xml
var result = {};
var tempName = "";
var parser = new xml.SaxParser(function(cb){
    cb.onStartDocument(function () {});
    cb.onEndDocument(function () {});
    cb.onStartElementNS(function (elem, attrs, prefix, uri, namespaces) {
        tempName = elem;
    });
    cb.onEndElementNS(function (elem, prefix, uri) {
        tempName = '';
    });
    cb.onCharacters(function (chars) {
        result[tempName] = chars;
    });
    cb.onCdata(function (cdata) {
        result[tempName] = cdata;
    });
    cb.onComment(function (msg) {});
    cb.onWarning(function (msg) {});
    cb.onError(function (msg) {});
});

var wechat = function (req, res) {
    res.writeHead(200, {"Content-Type":"text/html"});
    var getQuery= url.parse(req.url).query;
    var query = querystring.parse(getQuery);
    
    var nonce = query.nonce;
    var msg_signature = query.msg_signature;
    var signature = query.signature;
    var timestamp = query.timestamp;
    var echostr = query.echostr;
    // 微信号类型，1：公众号  ；2：企业号。
    var wechatType = signature ? 1 : 2;
    // 消息是否加密
    var encrypted = msg_signature ? true : false;
    if (!config['debug']) {
        if (!crypt.mpVerifySig(config['token'], signature, timestamp, nonce)) {
            res.end('error');
        }
    }
    if (echostr) {
        res.end(echostr);
    }
    result = {};
    parser.parseString(data);
    var msg = "<xml><ToUserName><![CDATA[" + result['FromUserName'] + "]]></ToUserName>" +
        "<FromUserName><![CDATA[" + result['ToUserName'] + "]]></FromUserName>" +
        "<CreateTime>" + result['CreateTime'] + "</CreateTime><MsgType><![CDATA[text]]></MsgType>" +
        "<Content><![CDATA[" + "你刚才说的是：" + result['Content'] + "]]></Content></xml>";
    res.end(msg);
};

exports.start = function(conf) {
    config = conf;
    http.createServer(function(req, res){
        if (req.method == "GET") {
            wechat(req, res);
        } else if (req.method == "POST") {
            req.on("data", function(datas){
                data += datas;
            }).on("end", function(){
                wechat(req, res);
            });
        }
    }).listen(config['port']);
    console.log("Wechat start listen " + config['port'] + " port");
}