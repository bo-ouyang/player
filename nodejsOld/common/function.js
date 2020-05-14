const config = require('../config/config.js');

/**
 * json对象转成字符串
 */
function json_encode(data) {
	return JSON.stringify(data);
}

/**
 * 字符串解析成json对象
 */
function json_decode(data) {
	return JSON.parse(data);
}

/**
 * 获取web3服务
 */
function getWeb3() {
	var Web3 = require('web3');
	var url = config.web3;
	return new Web3(new Web3.providers.HttpProvider(url));
}

/**
 * 获取当前时间戳
 */
function time() {
	return Math.ceil(new Date().getTime() / 1000);
}

/**
 * 成功返回
 */
function sendReturn(data, msg) {
	return JSON.stringify({status:'success', code:10000, data:data || [], msg:msg || 'request success', time: time()});
}

/**
 * 失败返回
 */
function failReturn(msg, code) {
	return JSON.stringify({status:'error', code:code || 10001, data:[], msg:msg || 'request error', time: time()});
}

function query(search) {
	var querys = search.split("&")
	var param = {}
	querys.forEach(function(v,i){
		var ktv = v.split("=")
		param[ktv[0]] = ktv[1] === undefined ? "" : ktv[1]
	})
	return param
}

function post(params){
	var Base64 = require('js-base64').Base64
	var md5 = require("md5")
	var key = "Xc3dpbW(MT#js!MVF5"
	params = query(Base64.decode(params))
	var sign = params._sign
	delete params._sign
	var keys = Object.keys(params).sort()
	var str = []
	keys.forEach(function(k){
		str.push(k + "=" + params[k])
	})
	str = str.join("&")
	var sign1 = md5(str + key)
	if(sign1 !== sign){
		return null
	}
	return params
}

function eToString(num) {
	num = num.toString()
	if(num.includes("e")){ //科学计算
		var len = num.match(/(\+|e)\d+/i)[0]
		len = len.match(/\d+/)[0]

		var pre = num.match(/\d+\.?\d*e/i)[0]
		pre = num.match(/\d+\.?\d*/i)[0]

		var pres = pre.split(".")

		var pre0 = pres[0]
		var pre1 = ""
		if(pres[1] !== undefined){
			pre1 = pres[1]
			len -= pre1.length
		}
		num = pre0 + "" + pre1
		for(var i=0; i<len; i++){
			num += "0"
		}
	}
	return num
}

function getDateUnified (date = new Date(), format = 'YYYY-MM-DD hh:mm:ss') {
	const o = {
	  // '(Y+)': date.getFullYear(),
	  '(M+)': date.getMonth() + 1,
	  '(D+)': date.getDate(),
	  '(h+)': date.getHours(),
	  '(m+)': date.getMinutes(),
	  '(s+)': date.getSeconds(),
	  '(S+)': date.getMilliseconds()
	}
	if (/(Y+)/.test(format)) {
	  format = format.replace(RegExp.$1, ('' + date.getFullYear()).substring(4 - RegExp.$1.length))
	}
	for (let [k, v] of Object.entries(o)) {
	  if ((k = new RegExp(k).exec(format))) {
		format = format.replace(k[1], v.toString().padStart(k[1].length, '0'))
	  }
	}
	return format
  }

module.exports = {
	getWeb3 : getWeb3,
	post : post,
	json_encode : json_encode,
	json_decode : json_decode,
	sendReturn : sendReturn,
	failReturn : failReturn,
	time : time,
	eToString : eToString,
	getDateUnifide: getDateUnified
}
