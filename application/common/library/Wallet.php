<?php
/**
 * 钱包服务
 * @version 1.0
 */
namespace app\common\library;

use think\Db;
use token\Token;
use think\facade\Config;
use Exception;
use http\Http;
use math\BCMath;
use rsa\Rsa;
use app\common\model\SendLibrary;
use think\facade\Env;

class Wallet
{
	/**
	 * 转账类型
	 * @var array
	 */
	public static $typeList = [
		'interest' => 1,// 利息
		'basic'    => 2,// 本金
		'token'    => 3
	];

	/**
	 * 转账
	 */
	public static function transfer($to, $amount, $type)
	{
		//common_log('tsetset');
		if (!$to || !$amount || !$type) {
			return false;
		}

		if (!in_array($type, self::$typeList)) {
			return false;
		}

		if (strlen($to) > 42 && stripos($to, '0x') !== 0) {
			//common_log(34134);
			$to = self::decodeAddress($to);
		}

        $amount = BCMath::mul($amount,1, 12);
		$value = BCMath::mul($amount, pow(10, 18));
		$rsa = new Rsa();
		//$key = $rsa->getPubKey();
		//common_log($key);
		$attribute = [
			'address'     => $rsa->pubEncrypt($to),
            //'address' => $to,
			'amount'      => $rsa->pubEncrypt($value),
            //'amount' => $value,
			'type'        => $type,
			'status'      => SendLibrary::STATUS_UNDONE,
			'create_time' => time(),
		];
		//common_log($attribute['address']);
		$newId = Db::name('send_library')->insertGetId($attribute);
		if (!$newId) {
			return false;
		}
        return true;
	}
	/**
	 * 转账
	 */
	public static function transferAcgg($to, $amount, $type)
	{
		//common_log('tsetset');
		if (!$to || !$amount || !$type) {
			return false;
		}

		if (!in_array($type, self::$typeList)) {
			return false;
		}

		if (strlen($to) > 42 && stripos($to, '0x') !== 0) {
			//common_log(34134);
			$to = self::decodeAddress($to);
		}

		$amount = BCMath::mul($amount,1, 12);
		$value = BCMath::mul($amount, pow(10, 18));
		$rsa = new Rsa();
		//$key = $rsa->getPubKey();
		//common_log($key);
		$attribute = [
			'address'     => $rsa->pubEncrypt($to),
			//'address' => $to,
			'amount'      => $rsa->pubEncrypt($value),
			//'amount' => $value,
			'type'        => $type,
			'status'      => SendLibrary::STATUS_UNDONE,
			'create_time' => time(),
		];
		//common_log($attribute['address']);
		$newId = Db::name('send_library_acgg')->insertGetId($attribute);
		if (!$newId) {
			return false;
		}
		return true;
	}

	/**
	 * 获取token
	 */
	public static function send($url, $from, $prikey, $to, $amount, $type)
	{
		$nowTime = time();
		$secretString = $from . $type . $nowTime;

		$params = [
			'from'   => $from,
			'prikey' => $prikey,
			'value'  => $amount,
			'type'   => $type,
			'time'   => $nowTime,
			'to'     => $to,
			'token'  => hash_hmac('sha256', $secretString, Config::get('app.secret_key')),
		];

		$header = array('Content-Type: application/x-www-form-urlencoded');
		$response = Http::post($url, $params, [], $header);

		$response = json_decode($response, true);
        if ($response['status'] == 'success') {
        	return true;
        }

        return false;
	}

	/**
	 * 地址编码
	 */
	public static function encodeAddress($address, $pubKey = null, $prvKey = null)
	{
		if (!$address) {
			return false;
		}

		$pubKey = !empty($pubKey) ? $pubKey : Env::get('extend_path') . 'rsa/address-pub.key';
		$prvKey = !empty($prvKey) ? $prvKey : Env::get('extend_path') . 'rsa/address-prv.key';

		$rsa = new Rsa($pubKey, $prvKey);
		return $rsa->pubEncrypt($address);
	}

	/**
	 * 地址解码
	 */
	public static function decodeAddress($address, $pubKey = null, $prvKey = null)
	{
		if (!$address) {
			return false;
		}

		$pubKey = !empty($pubKey) ? $pubKey : Env::get('extend_path') . 'rsa/address-pub.key';
		$prvKey = !empty($prvKey) ? $prvKey : Env::get('extend_path') . 'rsa/address-prv.key';

		$rsa = new Rsa($pubKey, $prvKey);
		return $rsa->privDecrypt($address);
	}

	/**
	 * 验证hash是否真实
	 * @param  string $hash   前端提交的hash
	 * @param  string $from   付款地址
	 * @param  int    $amount 前端提交的原始金额[单位wei]
	 */
	public static function validHash($hash, $from, $amount, $contract)
	{
		if (!$hash || !$from || !$amount) {
			return false;
		}

		// http://127.0.0.1:6023/block-hash?hash=0x91d2e0020e26f1b1abc7c348b7c45491e804bd7268dde50d11f5a33e704a5d2f
		// {"status":"success","code":10000,"data":{"blockHash":"0xd70826b674916fb053261e44c1adc5cd29a7205e766b252d8bb79daa8cb3efba","blockNumber":7703675,"from":"0x3Ba5c7Bb72A0771f70997c551CE6935Dc1e79E0f","gas":36691,"gasPrice":"4000000000","hash":"0xd4f8bd7d34dcd59e9324692287c5b3d7df9db6f5ec0cc321589fea107a6cf154","input":"0x","nonce":17,"r":"0x4d3c6bfea73713b16785e7c0fc41f3c56ecf1483f8332ba1bcf6f0519b651653","s":"0x7f878cf54345cf3686a4c314940963510a70331b7f338ab7c63b67ab1e2869a2","to":"0x10cdc55Aff063Eb94B6f7eA03d14165e0A5edECa","transactionIndex":44,"v":"0x25","value":"1000000000000000000"},"msg":"request success","time":1557129408}

		$response = Http::get(Config::get('node_addr') . 'block-hash', ['hash' => $hash]);
		$response = json_decode($response, true);
		if ($response['status'] == 'success' && !empty($response['data'])) {
			$originHash  = strtolower($response['data']['hash']);
			$originFrom  = strtolower($response['data']['from']);
			$originTo    = strtolower($response['data']['to']);
			$finalValue  = BCMath::convertScientificNotationToString($response['data']['value']);
			$frontValue  = BCMath::convertScientificNotationToString($amount);
			/*$contract    = strtolower(Config::get('app.contract_address'));*/
			$hashStatus  = $response['data']['status'];

			if (strtolower($hash) == $originHash && strtolower($from) == $originFrom && $frontValue == $finalValue &&  $contract == $originTo && $hashStatus) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	/**
	 * 验证地址合法性
	 */
	public static function validAddress($address)
	{
		if (!$address) {
			return false;
		}

		$response = Http::get(Config::get('node_addr') . 'block-isAddress', ['address' => $address]);
		$response = json_decode($response, true);
		if ($response['status'] == 'success' && $response['code'] == '10000') {
			return true;
		}

		return false;
	}
}
