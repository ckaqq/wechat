var crypto = require('crypto');

exports.mpVerifySig = function (token, signature, timestamp, nonce) {
    var arr = new Array(token, timestamp, nonce);
    arr.sort();
    var sha1 = crypto.createHash('sha1');
    sha1.update(arr.join(''));
    return sha1.digest('hex') == signature;
};