/**
 * 配置文件
 */
process.env.SITE_ENVIROMENT = 'productive';//developer,devcloud,sandbox,sandbox_t,productive
// 获取环境变量配置
let envConfig = require('./conf_' + process.env.SITE_ENVIROMENT + '.js');

var config = {
	// 监听端口
	web_port: envConfig.web_port,
	// 联盟链配置
	web3: envConfig.web3,
	// 合约地址
	tokenaddress: envConfig.tokenaddress,
	// 合约ABI编码
	abi: envConfig.abi,
	// 验证密钥
	secret_key: 'H^f#2v&P@o2]r%H$6a@Mc&9',
	// eth 手续费
	eth_fee: 0.001,
	// 记录表
	log_table: 'send_log_acgg'
};

module.exports = config;
