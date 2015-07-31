var http = require('http');
var valid = require('./lib/valid');
//var processMsg = require('./lib/processMessage');
http.createServer(function (req, res) {
    res.writeHead(200, {"Content-Type":"text/html"});
    valid.token(req, res);
    /*if (req.method == "GET") {
        valid.token(req, res);
    }
    if (req.method == "POST") {
        var datas="";
        req.on("data",function(data){
            datas+=data;
        }).on("end",function(){
            processMsg.processMessages(datas,res);
        });
    }*/
}).listen(18889)
console.log("runing 18889")

