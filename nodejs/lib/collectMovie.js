var http = require('http'),
    $ = require('jquery'),
    iconv = require('iconv-lite'),
    db=require('./mongoDb');
exports.collect=function(){
    http.get('http://theater.mtime.com/China_Henan_Province_Anyang/movie/',function(res){
       var htmls='';
        res.setEncoding('binary');
        res.on('data',function(data){
            htmls+=data;
        }).on('end',function(){
                var buf = new Buffer(html, 'binary');
                var str = iconv.decode(buf, 'utf-8');
                var dom=$(str);
                var ul = $(dom).find("#hotplayRegion");
                $(ul).find("h3").each(function (i, h3) {
                    var title = $(h3).text();
                    var id = $(h3).find("a").attr('href').toString().replace('http://theater.mtime.com/China_Henan_Province_Anyang/movie/','').replace('/','');
                    db.insert(title,id);
                });
               
            });
    })
}
