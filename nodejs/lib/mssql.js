var Database = require('odbc').Database,
    db = new Database();


exports.rc = function (callback) {
    var result = "";    
    var daystr = getMyDate(); 
    var sql="select top 10 worksite,left(NeedDeal,45) as NeedDeal,UserCoName,gz from [WorkSite] where ToDate>\'"+daystr+"\' and stop=0 order by PDate desc";
    var con = "DSN=mssql194;UID=sa;PWD=sa;DATABASE=db";	
    db.open(con, function (err) {
        if (err) return;
        db.query(sql, function (err, rows, moreResultSets) {
            if (err) {
                console.log(err);
                callback(null);
            } 

            else {
	            
		    for (i in rows) {
                    var NeedDeal =removeHTMLTag(rows[i].NeedDeal.replace("性", "*").replace("小姐", "*").replace("情","*"));
                    result += rows[i].worksite + ":\r\n" + NeedDeal + "....\r\n【工资】：" + rows[i].gz + "\r\n【公司】：" + rows[i].UserCoName + "\r\n\r\n";
                }
		
                callback(result); 
            }

        });
        db.close(function (err) {
            console.log("the database connection is now closed");
        });
    });
}

exports.vip = function (callback) {
    var result = "";
    var con = "DSN=mssql194;UID=sa;PWD=sa;DATABASE=db";
    db.open(con, function (err) {
        if (err) return console.log(err);
        db.query("select top 10 info from indexvip_info where lbid='23' order by id desc", function (err, rows, moreResultSets) {
            if (err) {
                console.log(err);
                callback(null);
            }
            else {
                for (i in rows) {
                    result += rows[i].info + "。\r\n";
                }
                callback(result);console.log(result);
            }

        });
        db.close(function (err) {
            console.log("the database connection is now closed");
        });
    });
}

exports.huangye = function (key, callback) {
    var result = "";
    var sql="select top 10 jieshao,address,tel,cx,shopname from shop where shopname like '%" + key + "%' and shenhe=1 order by id desc";
    var con = "DSN=mssql194;UID=sa;PWD=sa;DATABASE=db";
    db.open(con, function (err) {
        if (err) return console.log(err);
        db.query(sql, function (err, rows, moreResultSets) {
            if (err) {
                console.log(err);
                callback(null);
            }
            else {
 		console.log("rows:"+rows.length+sql);
                for (i in rows) {
                    var JieShao =removeHTMLTag(rows[i].jieshao.replace("性", "*").replace("小姐", "*"));
                    result += "店名:" + rows[i].shopname + "\r\n" + (JieShao.length > 50 ? JieShao.substring(0, 50) : JieShao) + "\r\n电话:" + rows[i].tel + "\r\n地址:" + rows[i].address + "\r\n\r\n";
                }
                callback(result);console.log(result);
            }
        });
        db.close(function (err) {
            console.log("the database connection is now closed");
        });
    });
}


exports.qc = function (callback) {
    var result = "";
    var sql="select top 10 seriesname,shuoming,money,xianmoney from tuijian where seriesclass='热门' and status=1 order by id desc";
    var con = "DSN=mssql194;UID=sa;PWD=sa;DATABASE=db";
    db.open(con, function (err) {
        if (err) return console.log(err);
        db.query(sql, function (err, rows, moreResultSets) {
            if (err) {
                console.log(err);
                callback(null);
            }
            else { 		
                for (i in rows) {                    
                    result += "【"+rows[i].seriesname+"】" + rows[i].shuoming + "\r\n原价：" + rows[i].money + "万   现价：" + rows[i].xianmoney + "万\r\n\r\n";
                }
                callback(result);console.log(result);
            }
        });
        db.close(function (err) {
            console.log("the database connection is now closed");
        });

    });
}

function getMyDate() {
var d = new Date();
return d.getFullYear() + '-' + d.getMonth() + '-' + d.getDate() + ' ' + d.getHours() + ':' + d.getMinutes();
}
function removeHTMLTag(str) {
            str = str.replace(/<\/?[^>]*>/g,''); //去除HTML tag
            str = str.replace(/[ | ]*\n/g,'\n'); //去除行尾空白
            //str = str.replace(/\n[\s| | ]*\r/g,'\n'); //去除多余空行
            str=str.replace(/&nbsp;/ig,'');//去掉&nbsp;
            return str;
    }
