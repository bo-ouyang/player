var Web3        = require('web3');
var funcLib     = require('../common/function.js');
var ethereumjs  = require('ethereumjs-tx');
var abis ={
    "abi": [
        {
            "constant": true,
            "inputs": [],
            "name": "name",
            "outputs": [
                {
                    "name": "",
                    "type": "string"
                }
            ],
            "payable": false,
            "stateMutability": "view",
            "type": "function"
        },
        {
            "constant": false,
            "inputs": [
                {
                    "name": "_spender",
                    "type": "address"
                },
                {
                    "name": "_value",
                    "type": "uint256"
                }
            ],
            "name": "approve",
            "outputs": [
                {
                    "name": "",
                    "type": "bool"
                }
            ],
            "payable": false,
            "stateMutability": "nonpayable",
            "type": "function"
        },
        {
            "constant": true,
            "inputs": [],
            "name": "totalSupply",
            "outputs": [
                {
                    "name": "",
                    "type": "uint256"
                }
            ],
            "payable": false,
            "stateMutability": "view",
            "type": "function"
        },
        {
            "constant": false,
            "inputs": [
                {
                    "name": "_from",
                    "type": "address"
                },
                {
                    "name": "_to",
                    "type": "address"
                },
                {
                    "name": "_value",
                    "type": "uint256"
                }
            ],
            "name": "transferFrom",
            "outputs": [
                {
                    "name": "",
                    "type": "bool"
                }
            ],
            "payable": false,
            "stateMutability": "nonpayable",
            "type": "function"
        },
        {
            "constant": true,
            "inputs": [],
            "name": "INITIAL_SUPPLY",
            "outputs": [
                {
                    "name": "",
                    "type": "uint256"
                }
            ],
            "payable": false,
            "stateMutability": "view",
            "type": "function"
        },
        {
            "constant": true,
            "inputs": [],
            "name": "decimals",
            "outputs": [
                {
                    "name": "",
                    "type": "uint8"
                }
            ],
            "payable": false,
            "stateMutability": "view",
            "type": "function"
        },
        {
            "constant": true,
            "inputs": [],
            "name": "MAX_SUPPLY",
            "outputs": [
                {
                    "name": "",
                    "type": "uint256"
                }
            ],
            "payable": false,
            "stateMutability": "view",
            "type": "function"
        },
        {
            "constant": false,
            "inputs": [
                {
                    "name": "_spender",
                    "type": "address"
                },
                {
                    "name": "_subtractedValue",
                    "type": "uint256"
                }
            ],
            "name": "decreaseApproval",
            "outputs": [
                {
                    "name": "",
                    "type": "bool"
                }
            ],
            "payable": false,
            "stateMutability": "nonpayable",
            "type": "function"
        },
        {
            "constant": true,
            "inputs": [
                {
                    "name": "_owner",
                    "type": "address"
                }
            ],
            "name": "balanceOf",
            "outputs": [
                {
                    "name": "",
                    "type": "uint256"
                }
            ],
            "payable": false,
            "stateMutability": "view",
            "type": "function"
        },
        {
            "constant": true,
            "inputs": [],
            "name": "symbol",
            "outputs": [
                {
                    "name": "",
                    "type": "string"
                }
            ],
            "payable": false,
            "stateMutability": "view",
            "type": "function"
        },
        {
            "constant": false,
            "inputs": [
                {
                    "name": "_to",
                    "type": "address"
                },
                {
                    "name": "_value",
                    "type": "uint256"
                }
            ],
            "name": "transfer",
            "outputs": [
                {
                    "name": "",
                    "type": "bool"
                }
            ],
            "payable": false,
            "stateMutability": "nonpayable",
            "type": "function"
        },
        {
            "constant": false,
            "inputs": [
                {
                    "name": "_spender",
                    "type": "address"
                },
                {
                    "name": "_addedValue",
                    "type": "uint256"
                }
            ],
            "name": "increaseApproval",
            "outputs": [
                {
                    "name": "",
                    "type": "bool"
                }
            ],
            "payable": false,
            "stateMutability": "nonpayable",
            "type": "function"
        },
        {
            "constant": true,
            "inputs": [
                {
                    "name": "_owner",
                    "type": "address"
                },
                {
                    "name": "_spender",
                    "type": "address"
                }
            ],
            "name": "allowance",
            "outputs": [
                {
                    "name": "",
                    "type": "uint256"
                }
            ],
            "payable": false,
            "stateMutability": "view",
            "type": "function"
        },
        {
            "inputs": [],
            "payable": false,
            "stateMutability": "nonpayable",
            "type": "constructor"
        },
        {
            "payable": true,
            "stateMutability": "payable",
            "type": "fallback"
        },
        {
            "anonymous": false,
            "inputs": [
                {
                    "indexed": true,
                    "name": "owner",
                    "type": "address"
                },
                {
                    "indexed": true,
                    "name": "spender",
                    "type": "address"
                },
                {
                    "indexed": false,
                    "name": "value",
                    "type": "uint256"
                }
            ],
            "name": "Approval",
            "type": "event"
        },
        {
            "anonymous": false,
            "inputs": [
                {
                    "indexed": true,
                    "name": "from",
                    "type": "address"
                },
                {
                    "indexed": true,
                    "name": "to",
                    "type": "address"
                },
                {
                    "indexed": false,
                    "name": "value",
                    "type": "uint256"
                }
            ],
            "name": "Transfer",
            "type": "event"
        }
    ],
};
var web3 = new Web3("https://mainnet.infura.io");
var abi = abis.abi;
var Contractaddress='0x10d7d3991d8fd747faecea17f161f498fbedc6a1'
var ACGGContract = new web3.eth.Contract(abi,Contractaddress); //合约实例
console.info('web3 Version: '+Web3.version);
/*
//查询合约名称
contract.methods.name().call().then(
    function(result){
        console.log(result);
    }
);
//查询合约名称
contract.methods.balanceOf('0x34A556a18A64d5B4959efEa9E667D3cfA6FFBCCb').call().then(
    function(result){
        console.log(result);
    }
);*/
//return
var fromPrivateKey  ='64ab33ad3c8a90b87e410899af195bc72f2999f7067dfa78551adcf3c8249c97';
var from            ='0x5AB3B419A4D6D8D4De3E737a61b8d7006B84d09d';
var to              ='0x27Edf0FD743a423aE8B9EEF66dA73c80F8aFaE8C';

var tokenValue = 0.001 ;
var gasPrice = 32 * Math.pow(10,9);;
var gasLimit = 600000
tokenValue = funcLib.eToString(tokenValue)
console.log('tokenValue:'+tokenValue)

ACGGContract.methods.balanceOf(from).call().then(function(balance){
    ACGGContract.methods.decimals().call().then(function(decimal){
        web3.eth.getTransactionCount(from,function (error,TransactionCount) {
            console.log('holder交易次数:'+TransactionCount)
            var privKey = new Buffer.from(fromPrivateKey, 'hex');
            //交易信息
            var rawTransaction = {
                "from": from,
                "nonce": web3.utils.toHex(TransactionCount),
                "gasPrice": web3.utils.toHex(gasPrice),
                "gasLimit": web3.utils.toHex(gasLimit),
                "to": Contractaddress,
                "value": "0x0",
                // "data": ACGGContract.methods.transfer(to, tokenValue*(10**decimal)),
            };
            try {
                rawTransaction.data = ACGGContract.methods.transfer(to,tokenValue*(10**decimal)).encodeABI();
            } catch (e) {
                console.log(e);
                return;
            }

//实例交易
            var tx = new ethereumjs(rawTransaction);
//私钥交易签名
            tx.sign(privKey);
//交易发送前实例化
            var serializedTx = '0x' + tx.serialize().toString('hex');
            //return
//发送交易，留下hash
            web3.eth.sendSignedTransaction(serializedTx, function(err, hash) {
                if (!err){
                    console.log(hash);
                } else {
                    console.log(err);
                }
            });


        });
    });

    });

