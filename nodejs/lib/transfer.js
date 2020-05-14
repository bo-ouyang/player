var ethereumjs  = require('ethereumjs-tx');
var config      = require('../config/config.js');
var funcLib     = require('../common/function.js');
var web3        = funcLib.getWeb3();
var getTime     = funcLib.time;
var json_encode = funcLib.json_encode;

/**
 * ETH自动转账,提取本金需要查询账户余额
 * 测试网络地址:
 * 0x5E6E109A7DAC9993b872e0abFfc4430a592e1aAd   C0DFB7C18A31172B6EAD0FF2E565C6A05F09D60D7F20C2F977ABD72C5DF03E16
 * 0x8d06e448D8ab6A3024bDa881bcEEa8Fc182b0F0F   4CFE83E0EFDA86A6DB3BFC333EC3CF732A9379CD4C0421041A39DB825F505BA6
 */
function ethSend(from, to, amount, privateKey, type) {
    web3.eth.getBalance(from, function(error, balance) {
        if (error) {
            return;
        }

        var fee = config.eth_fee*Math.pow(10,18);
        if (balance <= 0 || balance <= fee) {
            return;
        }

        // gas价格
        var gasPrice = 32*Math.pow(10,9);
        var nonce = 0;

        // 获取交易支付的最大Gas量[最少21000]
        var gasLimit = 600000;

        web3.eth.getGasPrice().then(function(p) {
            web3.eth.getTransactionCount(from, function(err, r) {
                if (err) {
                    nonce = -1;
                    return;
                }

                nonce = r;

                var contractAddress = config.tokenaddress;
                var contract = new web3.eth.Contract(config.abi, contractAddress);

                // 组装数据
                let t = {
                    from : from,
                    to : contractAddress,
                    value : "0x0",
                    nonce : web3.utils.toHex(nonce),
                    gasPrice : web3.utils.toHex(gasPrice),
                    gasLimit : web3.utils.toHex(gasLimit)
                }

                try {
                    // 类型[1:利息,2:提取本金]
                    if (type == 1) {
                        t.data = contract.methods.sendToken(to, amount).encodeABI();
                    } else {
                        t.data = contract.methods.extractToken(to, amount).encodeABI();
                    }
                } catch (e) {
                    console.log(e);
                    return;
                }

                // 初始化transaction
                var tx = new ethereumjs(t);
                if ('0x' == privateKey.substr(0, 2)) {
                    privateKey = privateKey.substr(2);
                }

                privateKey = Buffer.from(privateKey, 'hex');
                // 签名
                tx.sign(privateKey);
                var serializedTx = '0x' + tx.serialize().toString('hex');
                // 发送原始transaction
                web3.eth.sendSignedTransaction(serializedTx, function(err, hash) {
                    if (err) {
                        console.log(err.message);
                        return;
                    } else {
                        console.log(hash);
                        nonce ++;
                    }
                }).on('receipt', function(res) {
                    // 转账完成
                    console.log("--------- receipt -----");
                    console.log(res.transactionHash);

                    // request.post({
                    //     url: url,
                    //     body: JSON.stringify({order:order,type:type}),
                    //     headers:{
                    //         'Content-Type':'application/json'
                    //     }
                    // });
                }).on("error", function(e) {
                    console.log(e.message);
                });
            });
        });
    });
}

module.exports = {
    ethSend: ethSend
};
