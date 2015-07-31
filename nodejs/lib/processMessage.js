var xml = require("node-xml");
var formatMsg=require("./formatMsg");
var mtime=require("./mtime");
var mssql=require("./mssql");
exports.processMessages = function (data, res) {
    var ToUserName = "";
    var FromUserName = "";
    var CreateTime = "";
    var MsgType = "";
    var Content = "";//消息模式
    var Location_X = "";//地理位置
    var Location_Y = "";//地理位置
    var Scale = 1;//地图缩放
    var Label = "";//地理位置信息
    var Title = "";//图文消息标题
    var PicUrl = "";//图片链接
    var Description = "";//图文消息描述
    var FuncFlag = "";//是否标星
    var tagName = "";
    var parser = new xml.SaxParser(function (cb) {
        cb.onStartDocument(function () {

        });
        cb.onEndDocument(function () {
            if(MsgType=="text"){
                processContent(Content,CreateTime,ToUserName,FromUserName,res);
            }
        });
        cb.onStartElementNS(function (elem, attrs, prefix, uri, namespaces) {
            tagName = elem;
        });
        cb.onEndElementNS(function (elem, prefix, uri) {
            tagName = "";
        });
        cb.onCharacters(function (chars) {
            if (tagName == "CreateTime") {
                CreateTime = chars;
            } else if (tagName == "Location_X") {
                Location_X = chars;
            } else if (tagName == "Location_Y") {
                Location_Y = chars;
            } else if (tagName == "Scale") {
                Scale = chars;
            }
        });
        cb.onCdata(function (cdata) {
            if (tagName == "ToUserName") {
                ToUserName = cdata;
            } else if (tagName == "FromUserName") {
                FromUserName = cdata;
            } else if (tagName == "MsgType") {
                MsgType = cdata;
            } else if (tagName == "Content") {
                Content = cdata;
            } else if (tagName == "PicUrl") {
                PicUrl = cdata;
            } else if (tagName == "Label") {
                Label = cdata;
            }else if(tagName=="Title"){
                Title=cdata;
            }else if(tagName=="Description"){
                Description=cdata;
            }
        });
        cb.onComment(function (msg) {

        });
        cb.onWarning(function (msg) {

        });
        cb.onError(function (msg) {

        });
       
    });
 parser.parseString(data);
 console.log(data);
}

var processContent=function(content,createtime,ToUserName,FromUserName,res){
    content=content.replace("－", "-").trim().toLowerCase();
    var key="";
    if(content.indexOf("-rc")!=-1){	
        mssql.rc(function(result){
            if(result==null){
                formatMsg.textMsg("暂无信息",createtime,ToUserName,FromUserName,res);
            }
            else{
                formatMsg.textMsg(result,createtime,ToUserName,FromUserName,res);
            }
        });
    }else if(content.indexOf("-vip")!=-1){
        mssql.vip(function(result){
            if(result==null){
                formatMsg.textMsg("暂无信息",createtime,ToUserName,FromUserName,res);
            }
            else{
                formatMsg.textMsg(result,createtime,ToUserName,FromUserName,res);
            }
        });
    }else if(content.indexOf("-114")!=-1){
	console.log("-114");
        key=content.replace("-114","");
        mssql.huangye(key,function(result){
            if(result==null){
                formatMsg.textMsg("暂无信息",createtime,ToUserName,FromUserName,res);
            }
            else{
                formatMsg.textMsg(result,createtime,ToUserName,FromUserName,res);
            }
        });
    }else if(content.indexOf("-tuan")!=-1){
	console.log("-tuan");

    }else if(content.indexOf("-qc")!=-1){
	mssql.qc(function(result){
		if(result==null){
                formatMsg.textMsg("暂无信息",createtime,ToUserName,FromUserName,res);
            }
            else{
                formatMsg.textMsg(result,createtime,ToUserName,FromUserName,res);
            }	
	});

    }else if(content.indexOf("-newm")!=-1){
	console.log("-newM");
        mtime.mtime("all","",function(result){
            if(result==null){
                formatMsg.textMsg("暂无信息",createtime,ToUserName,FromUserName,res);
            }
            else{
                formatMsg.textMsg(result,createtime,ToUserName,FromUserName,res);
            }

        });
    }else if(content.indexOf("-movie")!=-1){
        key=content.replace("-movie","");
	console.log("key:"+key);
        mtime.mtime("movie",key,function(result){
            if(result==null){
                formatMsg.textMsg("暂无信息",createtime,ToUserName,FromUserName,res);
            }
            else{
                formatMsg.textMsg(result,createtime,ToUserName,FromUserName,res);
            }

        });
    }else if(content.indexOf("-cinema")!=-1){
        key=content.replace("-cinema","").trim();
	switch(key){
		case "whg":
		key="2090";
		break;
		case "askyd":
		key="3105";
		break;
		case "ask":
		key="1105";
		break;
		case "hlw":
		key="2845";
		break;
	}
        mtime.mtime("cinema",key,function(result){
            if(result==null){
                formatMsg.textMsg("暂无信息",createtime,ToUserName,FromUserName,res);
            }
            else{
                formatMsg.textMsg(result,createtime,ToUserName,FromUserName,res);
            }

        });
    }else if(content.indexOf("-h")!=-1){
        var result= "欢迎使用安阳信息网微信，您可以发送:\r\n-vip 查看VIP会员最新活动\r\n-rc 查看最新招聘信息\r\n-114-商家名称 查看商家信息\r\n-qc 查看最新汽车团购信息\r\n-newm 查看最新电影\r\n-movie 电影名称 查看电影播放信息\r\n-cinema whg(文化宫) askyd(奥斯卡殷都区) ask(奥斯卡北关) hlw(好莱坞影城) 查看影院电影信息";
        formatMsg.textMsg(result,createtime,ToUserName,FromUserName,res);
    }
    else{
	console.log("else");
        var result= "你好，发送-h可以查看更多功能";
        formatMsg.textMsg(result,createtime,ToUserName,FromUserName,res);
    }

}
