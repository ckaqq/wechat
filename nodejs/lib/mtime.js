var http = require('http'),
    $ = require('jquery'),
    iconv = require('iconv-lite'),
    url = require('url'),
    querystring = require('querystring'),
    db=require('./mongoDb');

exports.mtime = function (type, values, callback) {
    var getUrl = "", movie = "", cinema = "";
    if (type == "all") {
        getUrl = 'http://theater.mtime.com/China_Henan_Province_Anyang/movie/';//所有电影
	collectmovie(getUrl,type,callback)
    }
    if (type == "movie") {
        // movie = values;
	
	 db.find(values,function(id){
		getUrl = 'http://theater.mtime.com/China_Henan_Province_Anyang/movie/' + id+'/';//单个电影
		collectmovie(getUrl,type,callback)
	});
        
    }
    if (type == "cinema") {
        //cinema=values;
//        switch (values) {
//            case "1105":
//                getUrl = "http://theater.mtime.com/China_Henan_Province_Anyang_BeiGuanQu/1105/";
//                break;
//            case "2090":
//                getUrl = "http://theater.mtime.com/China_Henan_Province_Anyang_BeiGuanQu/2090/";
//                break;
//            case "3328":
//                getUrl = "http://theater.mtime.com/China_Henan_Province_Anyang_LinZhouShi/3328/";
//                break;
//            case "3105":
//                getUrl = "http://theater.mtime.com/China_Henan_Province_Anyang_YanDouQu/3105/";
//                break;
//            case "3481":
//                getUrl = "http://theater.mtime.com/China_Henan_Province_Anyang/3481/";
//                break;
//            case "3030":
//                getUrl = "http://theater.mtime.com/China_Henan_Province_Anyang_HuaXian/3030/";
//                break;
//            case "3221":
//                getUrl = "http://theater.mtime.com/China_Henan_Province_Anyang_NeiHuangXian/3221/";
//                break;
//            default:
//                getUrl = "";
//                break;
//        }
	getUrl = "http://theater.mtime.com/China_Henan_Province_Anyang_BeiGuanQu/"+values+"/";
	collectmovie(getUrl,type,callback)
    }    

}

function collectmovie(getUrl,type,callback){
console.log("geturl:"+getUrl);
    if (getUrl == "") {       
        callback(null)

    } else {
        var html = "";
        var now = new Date();
	
        http.get(getUrl, function (res) {
            res.setEncoding('binary');
            res.on('data',function (data) {
		if (type == "movie"){
                html+=data.replace('<script type="text/javascript">','<div class="zcl">').replace('</script>','</div>');
		}else{
		html+=data;
		}
            }).on('end', function () {
                    var buf = new Buffer(html, 'binary');
                    var str = iconv.decode(buf, 'utf-8');
                    var dom = $(str).html();
                    if (type == "all") {
                        var result = "最近所有上映新片：\r\n";
                        var ul = $(dom).find("#hotplayRegion");
                        $(ul).find("h3").each(function (i, h3) {
                            var title = $(h3).text();
                            result += title + "\r\n";  				
                    	    var id = $(h3).find("a").attr('href').toString().replace('http://theater.mtime.com/China_Henan_Province_Anyang/movie/','').replace('/','');
			    db.insert(title,id);
				
                        });
                    }
                   else if (type == "movie") {
			var dl=$(dom).find("div.zcl").eq(-2);	
			var result = "";
			$(dl).find("dd").each(function(i,dd){
				var title=$(dd).find("h3").text();
				var ul=$(dd).find("ul").eq(1);
				var list="";
				$(ul).find("li").each(function(i,li){
					var time=$(li).find("a strong strong").text();
					var money=$(li).find("a em").text();
					list += time + "-----" + money + "\r\n";
						
				});
				result += "电影院：" + title + "\r\n放映时间及价格:\r\n" + list + "\r\n\r\n";
					
			});

                       
                    }
                   else if (type == "cinema") {
                       var table=$(dom).find("#theaterShowtimeListDiv");	
			//console.log($(dom).html());
                        var result = "";
                        $(table).find("dd").each(function (i, div) {
                            var title=$(div).find("a.c_000").text();
                            var times=$(div).find("ul.s_timelist");
                            var list = "";
                            $(times).find("li.ticketnone").each(function (i, li) {
                                var money = $(li).find("em").text();
                                var time = $(li).find("a strong strong").text();
                                list += time + "-----" + money + "\r\n";
                            });
                            result += "电影名称：" + title + "\r\n放映时间及价格:\r\n" + list + "\r\n\r\n";
                        });
                    }
			else{
				result="无";
			}
                    console.log('耗时：' + (new Date().getTime() - now.getTime()) + 'ms');
                    callback(result);
                });
        });
    }


}
