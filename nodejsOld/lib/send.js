let process = require("process");
var ethereumjs = require('ethereumjs-tx');
var config = require('../config/config.js');
var funcLib = require('../common/function.js');
var web3 = funcLib.getWeb3();
var Db = require('../lib/Db.js');
var abi = '';
var contract = null;
var contractAddr = '';
var sender = '';
// gas价格
var gasPrice = 32 * Math.pow(10, 9);
// 获取交易支付的最大Gas量[最少21000]
var gasLimit = 600000;
var nonce = 0;
var taskList = [];
var isSending = false;
var dbConfig = {};
var tablename = '';
var primaryKey = '';
var dbFlag = false;
var _contractAddr = '';
var privateKey = '';

/**
 * 初始化交易
 */
function init(_sender, _prikey, _dbConfig, _tablename, _primaryKey, _dbFlag, callback) {
    _contractAddr = config.tokenaddress;
    dbConfig = _dbConfig;
    tablename = _tablename;
    primaryKey = _primaryKey;
    dbFlag = _dbFlag;
    sender = _sender
    privateKey = new Buffer.from(_prikey, 'hex');
    contract = new web3.eth.Contract(config.abi, _contractAddr);
    web3.eth.getTransactionCount(_sender, function (error, TransactionCount) {
        if (error) {
            TransactionCount = -1;
            console.log("交易次数cuouw:" + error);
            return;
        }
        console.log("交易次数:" + TransactionCount);
        nonce = TransactionCount;
        callback();
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
    var to = task.to;
    var type = task.type;
    var taskId = task.id;

    amount = funcLib.eToString(amount);
    let t = {
        "from": sender,
        "nonce": web3.utils.toHex(nonce),
        "gasPrice": web3.utils.toHex(gasPrice),
        "gasLimit": web3.utils.toHex(gasLimit),
        "to": _contractAddr,
        "value": "0x0",
    }
    console.log('from:' + sender)
    console.log('交易次数:' + nonce)
    console.log('gasPrice:' + gasPrice)
    console.log('gasLimit:' + gasLimit)
    console.log('_contractAddr:' + _contractAddr)
   // return;
    try {
        t.data = contract.methods.transfer(to, amount).encodeABI();
    } catch (e) {
        console.log(e);
        return;
    }

    // 初始化transaction
    var tx = new ethereumjs(t);
    // 签名
    tx.sign(privateKey);
    var serializedTx = '0x' + tx.serialize().toString('hex');
    // 发送原始transaction
    console.log('start sendSignedTransaction')
    web3.eth.sendSignedTransaction(serializedTx, function (err, hash) {
        if (err) {
            console.log(err.message);
            process.exit();
            return;
        } else {
            let tmpAmount = amount / Math.pow(10, 18);
            let tmpType = (type == 1) ? 'income' : 'cash';
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
                var r = db.table(tablename).where(search).delete(function (result) {
                    console.log('delete rows: ' + result);
                    if (result) {
                        db.table(config.log_table).add(hashData, function (res) {
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
    }).on("receipt", function (res) {
        // 转账完成事件
        console.log("------- receipt ------");
        console.log(res.transactionHash);
    }).on("error", function (e) {
        console.log("-------- error -------");
        console.log(e.message);
    });
}

module.exports = {
    init: init,
    addTask: addTask,
    send: send
};
