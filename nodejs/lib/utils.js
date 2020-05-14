var crypto  = require('crypto');
var config  = require('../config/config.js');
var process = require("process");
var funcs   = require('../common/function.js');
var web3    = funcs.getWeb3();

// 编码数据
exports.secretKey = (data) => {
	var hmac = crypto.createHmac('sha256', config.secret_key);
	hmac.update(data);

	return hmac.digest('hex');
};

// 是否合法地址
exports.isAddress = (address) => {
	return web3.utils.isAddress(address);
}

// 对数组进行大小写遍历[1:小写,2:大写]
exports.caseArray = (originArr, type) => {
    var newList = [];
	originArr.forEach(function(v, i) {
		let newValue = (type == 1) ? v.toLowerCase() : v.toUpperCase();
		newList.push(newValue);
	});

	return newList;
}

// 解密方法
exports.decrypt = (encrypted, privatePem) => {
	const Rsa = require('node-rsa');
	var private_key = new Rsa(privatePem);
	private_key.setOptions({encryptionScheme: 'pkcs1'});

	var decrypted = private_key.decrypt(encrypted, 'utf8');
	return decrypted;
};

// 导出excel
exports.writeExcel = (datas, fileName) => {
	var fs = require('fs');
	var xlsx = require('node-xlsx');
	fileName = 'excel/' + fileName;

	var buffer = xlsx.build([
		{
			name:'sheet1',
			data:datas
		}
	]);
	fs.writeFileSync(fileName, buffer, {'flag':'w'});   //生成excel
}

// 加法
exports.numAdd = (leftNum, rightNum) => {
	const BigNumber = require('bignumber.js');

	let a = new BigNumber(leftNum);
	let b = new BigNumber(rightNum);
	let result = a.plus(b).toFixed();

	return result;
}

// 减法
exports.numSub = (leftNum, rightNum) => {
	const BigNumber = require('bignumber.js');

	let a = new BigNumber(leftNum);
	let result = a.minus(rightNum).toFixed();

	return result;
}

// 除法
exports.numDiv = (leftNum, rightNum) => {
	const BigNumber = require('bignumber.js');
	let x = new BigNumber(leftNum);
	let result = x.div(rightNum).toFixed();

	return result;
}

// 乘法
exports.numMul = (leftNum, rightNum) => {
	const BigNumber = require('bignumber.js');
	let x = new BigNumber(leftNum);
	let result = x.multipliedBy(rightNum).toFixed();                            // '0.3333333333'

	return result;
}