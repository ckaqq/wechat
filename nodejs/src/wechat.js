var url = require('url');
var xml = require("node-xml");
var http = require('http');
var func = require('./function');
var crypt = require('./crypt');
var querystring = require('querystring');

// 配置
var config = {};
// POST内容
var data = '';
// 请求内容
var request = {};

// 解析xml要用的临时变量
var result = {};
var tempName = "";

// xml模板字符串
var templateText        = "<xml><ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName><CreateTime>%s</CreateTime><MsgType><![CDATA[text]]></MsgType><Content><![CDATA[%s]]></Content></xml>";
var templateNewsBegin   = "<xml><ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName><CreateTime>%s</CreateTime><MsgType><![CDATA[news]]></MsgType><ArticleCount>%s</ArticleCount><Articles>";
var templateNewsContent = "<item><Title><![CDATA[%s]]></Title><Description><![CDATA[%s]]></Description><PicUrl><![CDATA[%s]]></PicUrl><Url><![CDATA[%s]]></Url></item>";
var templateNewsEnd     = "</Articles></xml>";

// 构造函数
start = function(conf) {
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

// 解析xml
var parser = new xml.SaxParser(function(cb) {
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
    savePostData(encrypted);
    var msg = func.sprintf(
        templateText,
        request['fromusername'],
        request['tousername'],
        request['createtime'],
        "你刚才说的是：" + request['content']
    );
    res.end(msg);
};

var savePostData = function(encrypted){
    result = {};
    parser.parseString(data);
    if (encrypted) {
        // 解密消息体，待开发
    }
    // 将数组键名转换为小写，提高健壮性，减少因大小写不同而出现的问题
    for (var key in result) {
        request[key.toLowerCase] = result[key];
    };
};

exports.start = start;