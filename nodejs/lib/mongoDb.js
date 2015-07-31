var mongo = require('mongoskin');
var db = mongo.db('localhost:27017/test');
exports.insert=function(title,id){
	console.log("title"+title);
db.collection("movie").count({'id':id},function(err,sid){
if(err) return;
if(sid==0)
{
    db.collection("movie").insert({ title: title,id:id }, function (err, posts)
    {   if(err) return;
	console.log("posts:"+posts);
        
    });

}

});


}

exports.find=function(title,callback){
	console.log("mongotitle:"+title);
	var titlelike=new RegExp("^.*"+title.trim()+".*$");
	console.log(titlelike);
    	db.collection("movie").findOne({title:titlelike},function(err,result){
        if(err) callback(null);
	if(result!=null){
	console.log("mongoid:"+result.id);
        callback(result.id);
	}
    });
}
