exports.textMsg=function(Msg,CreateTime,ToUserName,FromUserName,res){
//res.writeHead(200,{"Content-Type":"text/xml"});
	if(Msg==""){
		Msg="暂无信息";	
	}
	var msg="<xml><ToUserName><![CDATA[" + FromUserName + "]]></ToUserName>" +
        "<FromUserName><![CDATA[" + ToUserName + "]]></FromUserName>" +
        "<CreateTime>" + CreateTime + "</CreateTime><MsgType><![CDATA[text]]></MsgType>" +
        "<Content><![CDATA[" + Msg + "]]></Content><FuncFlag>0</FuncFlag></xml>";
	console.log(msg);
    res.write(msg);
    res.end();
}
exports.musicMsg=function(Title,Description,MusicUrl,HQMusicUrl,CreateTime,FromUserName,ToUserName,res){

}
