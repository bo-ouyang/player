var request = require("request");
var url = "https://api.etherscan.io/api?";
//var apikey = "K8PAGMNCQCCRKNR56MKJSZ8MB44D9AU6V6";
function getTransactionByHash(hash,callback) {
	var query = `module=proxy&action=eth_getTransactionByHash&txhash=${hash}`
	request(url + query,function(err,response,res){
		var result = {}
		try{
			res = JSON.parse(res)
			result = res.result
		}catch(e){
		}
		callback(result)
	})
}

function txexist(hash, callback){
  var query = `module=localchk&action=txexist&txhash=${hash}`
  request(url + query,function(err,response,res){
		res = JSON.parse(res)
		callback(res.result)
	})

}

//查询交易接收情况
function gettxreceiptstatus(hash, callback) {
  var query = `module=transaction&action=getstatus&txhash=${hash}`
  request(url + query,function(err,response,res){
  		var result = {}
  		console.log(response)
		try{
			res = JSON.parse(res)
			result = res.result
		}catch(e){
		}
		callback(result)
	})

}


function balance(address, callback){
  var query = `module=account&action=balance&tag=latest&address=${address}`
  console.log(url + query)
  request(url + query,function(err,response,res){
  		var result = 0
  		try{
			res = JSON.parse(res)
			result = res.result
		}catch(e){
		}
		callback(result)
	})

}

function balances(addresses, callback) {
  var query = `module=account&action=balancemulti&tag=latest&address=${addresses}`
  request(url + query,function(err,response,res){
  		var result = []
		try{
			res = JSON.parse(res)
			result = res.result
		}catch(e){

		}
		callback(result)
	})

}

/**
 * 获取交易列表[无代币详情]
 * https://api.etherscan.io/api?module=account&action=txlist&page=1&offset=100&sort=desc&address=0xAA3AB469d39413014Ecc5ac599B32944312Db1b3&apikey=K8PAGMNCQCCRKNR56MKJSZ8MB44D9AU6V6
 */
function txlist(address,p,offset,callback){
  var query = `module=account&action=txlist&page=${p}&offset=${offset}&sort=desc&address=${address}`
  request(url + query,function(err,response,res){
  		var result = []
		try{
			res = JSON.parse(res)
			result = res.result
		}catch(e){

		}
		callback(result)
	})
}

function getabi(tokenaddress,callback){
  	var query = `module=contract&action=getabi&address=${tokenaddress}`
  	request(url + query,function(err,response,res){
  		var abi = null
		try{
			res = JSON.parse(res)
			if(res.result){
				abi = JSON.parse(res.result)
			}
		}catch(e){

		}
		callback(abi)
	})
}

/**
 * 获取钱包地址交易详情列表
 * http://api.etherscan.io/api?module=account&action=tokentx&address=0xAA3AB469d39413014Ecc5ac599B32944312Db1b3&startblock=0&endblock=999999999&sort=asc&apikey=K8PAGMNCQCCRKNR56MKJSZ8MB44D9AU6V6
 */
function getTxList(walletAddr, startblock, callback) {
	var query = `module=account&action=tokentx&address=${walletAddr}&startblock=${startblock}&endblock=999999999&sort=asc&`;
  	request(url + query, function(err, response, res) {
  		var result = [];

		try {
			res = JSON.parse(res);
			result = res.result;
		} catch(e) {

		}

		callback(result);
	})
}

/**
 * 获取比特币交易记录
 * https://blockchain.info/rawaddr/3CbZG5NSt9peKsZRaaV7sZ3r7Q1QTGkyXs
 */
function btctxlist(walletAddr, callback) {
	var btcUrl = `https://blockchain.info/rawaddr/${walletAddr}`;
  	request(btcUrl, function(err, response, res) {
  		var result = [];

		try {
			res = JSON.parse(res);
			result = res.txs;
		} catch(e) {

		}

		callback(result);
	})
}

module.exports = {
  getTransactionByHash: getTransactionByHash,
  gettxreceiptstatus: gettxreceiptstatus,
  balance: balance,
  balances: balances,
  txlist: txlist,
  txexist: txexist,
  getabi:getabi,
  getTxList: getTxList,
  btctxlist: btctxlist
}
