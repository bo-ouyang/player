var funcs = require('./common/function.js');
var web3 = funcs.getWeb3();
var post = funcs.post;
var json_encode = funcs.json_encode;
var json_decode = funcs.json_decode;
var sendReturn = funcs.sendReturn;
var failReturn = funcs.failReturn;

var express = require('express');
var bodyParser = require('body-parser');
var etherapi = require('./lib/etherapi.js');
var app = express();
var urlencodedParser = bodyParser.urlencoded({ extended: false });
var bitcoin = require('bitcoinjs-lib');
var crypto  = require('crypto');

var config = require('./config/config.js');
var utils = require('./lib/utils.js');
var transfer = require('./lib/transfer.js');

// 以太坊代币交易记录
app.get('/block-txlist', function(req, rep) {
    etherapi.getTxList(req.query.address, req.query.startblock, function(res) {
        rep.send(sendReturn(res));
    });
});

// 获取智能合约余额
app.get('/block-balance', urlencodedParser, function (req, rep) {
    var walletAddress = req.query.address;

    web3.eth.getBalance(walletAddress).then(function(result) {
        rep.send(sendReturn({address:walletAddress, balance:result}));
    });
});

// 比特币交易记录
app.get('/block-btctxlist', function(req, rep) {
    etherapi.btctxlist(req.query.address, function(res) {
        rep.send(sendReturn(res));
    });
});

// ETH交易记录
app.get('/block-ethtxlist', function(req, rep) {
    etherapi.txlist(req.query.address, 0, 1000, function(res) {
        rep.send(sendReturn(res));
    });
});

// 提取本金和发送利息
app.post('/block-transfer', urlencodedParser, function(req, rep) {
    let body  = req.body;
    let value = body.value || 0;// 金额
    let type  = body.type || '';// 类型[1:利息,2:提取本金]
    let token = body.token || '';// 编码后token
    let time  = body.time || 0;// 10位数时间戳

    if (type == '' || token == '' || time == 0) {
        rep.send(failReturn('数据缺失'));
        return;
    }

    if (funcs.time() - time > 900) {
        rep.send(failReturn('数据异常'));
        return;
    }

    if (value <= 0) {
        rep.send(failReturn('金额异常'));
        return;
    }

    let from = body.from || '';
    if (from == '') {
        rep.send(failReturn('付款地址不能为空'));
        return;
    }

    // 验证token
    let originToken = utils.secretKey(from+type+time);
    if (originToken != token) {
        rep.send(failReturn('token异常'));
        return;
    }

    let to = body.to || '';
    if (to == '') {
        rep.send(failReturn('收款地址不能为空'));
        return;
    }

    var prikey = body.prikey || '';
    if (prikey == '') {
        rep.send(failReturn('私钥缺失'));
        return;
    }

    if (!web3.utils.isAddress(from)) {
        rep.send(failReturn('非法付款地址'));
        return;
    }

    if (!web3.utils.isAddress(to)) {
        rep.send(failReturn('非法收款地址'));
        return;
    }

    transfer.ethSend(from, to, value, prikey, type);
    rep.send(sendReturn(null, '转账处理完成'));
});

// 以太坊代币交易记录
app.get('/block-hash', function(req, rep) {
    var hash = req.query.hash;
    web3.eth.getTransaction(hash, function(error, res) {
        if (!error) {
            if (!res) {
                rep.send(failReturn('hash异常'));
            } else {
                web3.eth.getTransactionReceipt(hash, function(error2, res2) {
                    let status = false;
                    if (!error2) {
                        status = (res2 === null) ? false : res2.status;
                    } else {
                        if (!res2) {
                            status = false;
                        }
                    }

                    res.status = !status ? false : status;
                    rep.send(sendReturn(res));
                });
            }
        } else {
            rep.send(failReturn('hash异常'));
        }
    });
});

// 判断是否以太坊地址
app.get('/block-isAddress', function(req, rep) {
    var request = req.query;

    var address = request.address || '';
    if (address == '') {
        rep.send(failReturn('地址缺失'));
        return;
    }

    if (!web3.utils.isAddress(address)) {
        rep.send(failReturn('非法地址'));
        return;
    }

    rep.send(sendReturn(null, '合法地址'));
});

var server = app.listen(config.web_port, function () {
  var host = server.address().address;
  var port = server.address().port;
});
