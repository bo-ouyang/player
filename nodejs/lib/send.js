let process      = require("process");
var ethereumjs   = require('ethereumjs-tx');
var config       = require('../config/config.js');
var funcLib      = require('../common/function.js');
var web3         = funcLib.getWeb3();
var Db           = require('../lib/Db.js');
var abi          = '';
var contract     = null;
var contractAddr = '';
var sender       = '';
var privateKey   = '';
var gasPrice     = 0;
var gasLimit     = 0;
var nonce        = 0;
var taskList     = [];
var isSending    = false;
var dbConfig     = {};
var tablename    = '';
var primaryKey   = '';
var dbFlag       = false;
/**
 * 初始化交易
 */
function init(_sender, _prikey, _dbConfig, _tablename, _primaryKey, _dbFlag, callback) {
    // let _contractAddr = config.tokenaddress;
    dbConfig   = _dbConfig;
    tablename  = _tablename;
    primaryKey = _primaryKey;
    dbFlag     = _dbFlag;

    web3.eth.getBalance(_sender, function(error, balance) {
        if (error) {
            return;
        }

        var fee = config.eth_fee*Math.pow(10,18);
        if (balance <= 0 || balance <= fee) {
            return;
        }

        // gas价格
        gasPrice = 32 * Math.pow(10,9);
        // 获取交易支付的最大Gas量[最少21000]
        gasLimit = 600000;
        // contract = new web3.eth.Contract(config.abi, _contractAddr);
        sender = _sender;
        // contractAddr = _contractAddr;
        if ('0x' == _prikey.substr(0, 2)) {
            _prikey = _prikey.substr(2);
        }

        privateKey = Buffer.from(_prikey, 'hex');
        web3.eth.getTransactionCount(sender, function(err, r) {
            if (err) {
                nonce = -1;
                return;
            }
            console.log("nonce:" + r);
            nonce = r;
            callback();
        });
    });
}

/**
 * 进入队列
 */
function addTask(task) {
    taskList.push(task);
}

/**
 * 发送ETH
 */
function send() {
    if (isSending) {
        return;
    }

    isSending = true;
    var task = taskList.shift();
    if (!task) {
        console.log('queue has no transfer data');
        process.exit();
        return;
    }

    // 组装数据
    var amount = task.value;
    var to     = task.to;
    var type   = task.type;
    var taskId = task.id;

    amount = funcLib.eToString(amount);
    let t = {
        from : sender,
        to : to,
        value : web3.utils.toHex(amount),
        nonce : web3.utils.toHex(nonce),
        gasPrice : web3.utils.toHex(gasPrice),
        gasLimit : web3.utils.toHex(gasLimit),
        data : '0x0'
    }

    // try {
    //     // 类型[1:利息,2:提取本金]
    //     if (type == 1) {
    //         t.data = contract.methods.sendToken(to, amount).encodeABI();
    //     } else {
    //         t.data = contract.methods.extractToken(to, amount).encodeABI();
    //     }
    // } catch (e) {
    //     console.log(e);
    //     return;
    // }

    // 初始化transaction
    var tx = new ethereumjs(t);
    // 签名
    tx.sign(privateKey);
    var serializedTx = '0x' + tx.serialize().toString('hex');
    // 发送原始transaction
    web3.eth.sendSignedTransaction(serializedTx, function(err, hash) {
        if (err) {
            console.log(err.message);
            process.exit();
            return;
        } else {

            let tmpAmount = amount / Math.pow(10,18);
            let tmpType   = (type == 1) ? 'income' : 'cash';

            console.log('address ' + to + ' amount ' + tmpAmount + ' type ' + tmpType + ' transfer success!');
            console.log('id ', taskId);
            console.log('hash ' + hash);
            nonce++;

            if (dbFlag) {
                let sendTime = new Date().getTime();
                let hashData = {
                    from: sender,
                    to: to,
                    hash: hash,
                    amount: tmpAmount,
                    create_time: sendTime,
                    day: funcLib.getDateUnifide()
                };

                // 删除数据
                let search = undefined;
                if (typeof taskId == 'object') {
                    search = "`" + primaryKey + "`" + ' in (' + taskId.join(',') + ')';
                } else {
                    search = {[primaryKey]: taskId};
                }

                let db = new Db(dbConfig);
                var r = db.table(tablename).where(search).delete(function(result) {
                    console.log('delete rows: ' + result);
                    if (result) {
                        db.table(config.log_table).add(hashData, function(res) {
                            db.end();
                            console.log('insert hash id: ' + res);
                            send();
                        });
                    } else {
                        console.log('database operate error!');
                        process.exit();
                        return;
                    }
                });

                isSending = false;
            } else {
                isSending = false;
                send();
            }
        }
    }).on("receipt", function(res) {
        // 转账完成事件
        console.log("------- receipt ------");
        console.log(res.transactionHash);
    }).on("error", function(e) {
        console.log("-------- error -------");
        console.log(e.message);
    });
}

module.exports = {
    init: init,
    addTask: addTask,
    send: send
};
