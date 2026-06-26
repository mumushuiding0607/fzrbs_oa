<?php

namespace app\modules\weixin\commons;

use linslin\yii2\curl;
use app\modules\weixin\models\FzrbsQyhyyAccessToken;
use app\modules\api\models\WeixinQYAppInterface;

/**
 * 微信企业号接口api
 */
class QYWeiXinAPI
{
	// 企业号id
	protected $corpid = 'ww36092db762bf3430';
	// 应用secret
	protected $secret;
	// 应用token
	public $token;
	// 错误代码
	public $errcode;
	// 错误信息
	public $errmsg;


	public function __construct($appid = '', $cropid = '', $secret = '')
	{
		if ($cropid) {
			$this->corpid = $cropid;
		}
		if ($secret) {
			$this->secret = $secret;
		}
		if ($appid && !$this->secret) {
			$model = WeixinQYAppInterface::find()->where(['appid' => $appid, 'corpid' => $this->corpid])->one();
			if ($model) {
				$this->secret = $model->secret;
			} else {
				$this->secret = 'FlhwLO00JBWPJbS88sHBtHbYlr7WY_QFHYs3E25AtjA';
			}
		}
	}

	/**
	 * 获取保存应用token
	 */
	public function getToken()
	{
		if ($this->token) {
			return;
		}
		$model = FzrbsQyhyyAccessToken::find()->where(['corpid' => $this->corpid, 'secret' => $this->secret])->one();
		if ($model && $model->token && $model->expires > time()) {
			$this->token = $model->token;
		} else {
			$url = 'https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid=' . $this->corpid . '&corpsecret=' . $this->secret;
			$curl = new curl\Curl();
			$response = $curl->get($url);
			if ($curl->errorCode === null) {
				$result = json_decode($response, true);
				if (isset($result['access_token'])) {
					$expires = time() + 7000;
					$data = ['token' => $result['access_token'], 'expires' => $expires, 'corpid' => $this->corpid, 'secret' => $this->secret];
					if ($model) {
						$model->attributes = $data;
					} else {
						$model = new FzrbsQyhyyAccessToken;
						$model->attributes = $data;
					}
					$model->save();
					$this->token = $result['access_token'];
					$this->errcode = 0;
				} else {
					$this->errcode = $result['errcode'];
					$this->errmsg = $result['errmsg'];
				}
			} else {
				$this->errcode = 1000;
				$this->errmsg = 'curl 失败';
			}
		}
		return $this->token;
	}

	/**
	 * 发送应用消息
	 * @param json $data 发送信息
	 */
	public function sendMessage($data)
	{
		if (!$data) {
			return 0;
		}
		$this->getToken();
		$url = 'https://qyapi.weixin.qq.com/cgi-bin/message/send?access_token=' . $this->token;
		$curl = new curl\Curl();
		$response = $curl->setRequestBody($data)->post($url);
		if ($curl->errorCode === null) {
			$result = json_decode($response, true);
			if (isset($result['errcode']) && $result['errcode'] > 0) {
				$this->errcode = $result['errcode'];
				$this->errmsg = $result['errmsg'];
				return 0;
			} else {
				return $data;
			}
		} else {
			return 0;
		}
	}

	/**
	 * 获取通讯录部门列表
	 * @param int $id 父部门id
	 */
	public function departmentList($id = 0)
	{
		$this->getToken();
		$url = 'https://qyapi.weixin.qq.com/cgi-bin/department/list?access_token=' . $this->token . ($id ? '&id=' . $id : '');
		$curl = new curl\Curl();
		$response = $curl->get($url);
		if ($curl->errorCode === null) {
			$result = json_decode($response, true);
			if (isset($result['errcode']) && $result['errcode'] > 0) {
				$this->errcode = $result['errcode'];
				$this->errmsg = $result['errmsg'];
				return 0;
			} else {
				return $result;
			}
		} else {
			return 0;
		}
	}

	/**
	 * 获取通讯录部门ID列表
	 * @param int $id 父部门id
	 */
	public function departmentSimpleList($id = 0)
	{
		$this->getToken();
		$url = 'https://qyapi.weixin.qq.com/cgi-bin/department/simplelist?access_token=' . $this->token . ($id ? '&id=' . $id : '');
		$curl = new curl\Curl();
		$response = $curl->get($url);
		if ($curl->errorCode === null) {
			$result = json_decode($response, true);
			if (isset($result['errcode']) && $result['errcode'] > 0) {
				$this->errcode = $result['errcode'];
				$this->errmsg = $result['errmsg'];
				return 0;
			} else {
				return $result;
			}
		} else {
			return 0;
		}
	}

	/**
	 * 获取单个部门详情
	 * @param int $id 部门id
	 */
	public function departmentGet($id)
	{
		$this->getToken();
		$url = 'https://qyapi.weixin.qq.com/cgi-bin/department/get?access_token=' . $this->token . '&id=' . $id;
		$curl = new curl\Curl();
		$response = $curl->get($url);
		if ($curl->errorCode === null) {
			$result = json_decode($response, true);
			if (isset($result['errcode']) && $result['errcode'] > 0) {
				$this->errcode = $result['errcode'];
				$this->errmsg = $result['errmsg'];
				return 0;
			} else {
				return $result;
			}
		} else {
			return 0;
		}
	}

	/**
	 * 获取部门成员
	 * @param int $id 部门id
	 */
	public function userList($id)
	{
		$this->getToken();
		$url = 'https://qyapi.weixin.qq.com/cgi-bin/user/list?access_token=' . $this->token . '&department_id=' . $id;
		$curl = new curl\Curl();
		$response = $curl->get($url);
		if ($curl->errorCode === null) {
			$result = json_decode($response, true);
			if (isset($result['errcode']) && $result['errcode'] > 0) {
				$this->errcode = $result['errcode'];
				$this->errmsg = $result['errmsg'];
				return 0;
			} else {
				return $result;
			}
		} else {
			return 0;
		}
	}

	/**
	 * 获取部门成员
	 * @param int $id 部门id
	 */
	public function userSimpleList($id = 0)
	{
		$this->getToken();
		$url = 'https://qyapi.weixin.qq.com/cgi-bin/user/simplelist?access_token=' . $this->token . '&department_id=' . $id;
		$curl = new curl\Curl();
		$response = $curl->get($url);
		if ($curl->errorCode === null) {
			$result = json_decode($response, true);
			if (isset($result['errcode']) && $result['errcode'] > 0) {
				$this->errcode = $result['errcode'];
				$this->errmsg = $result['errmsg'];
				return 0;
			} else {
				return $result;
			}
		} else {
			return 0;
		}
	}

	/**
	 * 读取成员
	 * @param string $userId 成员id
	 */
	public function userGet($userId)
	{
		$this->getToken();
		$url = 'https://qyapi.weixin.qq.com/cgi-bin/user/get?access_token=' . $this->token . '&userid=' . $userId;
		$curl = new curl\Curl();
		$response = $curl->get($url);
		if ($curl->errorCode === null) {
			$result = json_decode($response, true);
			if (isset($result['errcode']) && $result['errcode'] > 0) {
				$this->errcode = $result['errcode'];
				$this->errmsg = $result['errmsg'];
				return 0;
			} else {
				return $result;
			}
		} else {
			return 0;
		}
	}

	/**
	 * openid转userid
	 * @param string $openId openId
	 */
	public function convert2UserId($openId)
	{
		$param = json_encode(['openid' => $openId]);
		$this->getToken();
		$url = 'https://qyapi.weixin.qq.com/cgi-bin/user/convert_to_userid?access_token=' . $this->token;
		$curl = new curl\Curl();
		$response = $curl->setRequestBody($param)->post($url);
		if ($curl->errorCode === null) {
			$result = json_decode($response, true);
			if (isset($result['errcode']) && $result['errcode'] > 0) {
				$this->errcode = $result['errcode'];
				$this->errmsg = $result['errmsg'];
				return 0;
			} else {
				return $result;
			}
		} else {
			return 0;
		}
	}

	/**
	 * 根据扫描回调code获取userid
	 * @param string $code code
	 */
	public function getUserInfo($code)
	{
		$this->getToken();
		$url = 'https://qyapi.weixin.qq.com/cgi-bin/auth/getuserinfo?access_token=' . $this->token . '&code=' . $code;
		$curl = new curl\Curl();
		$response = $curl->get($url);
		if ($curl->errorCode === null) {
			$result = json_decode($response, true);
			if ($result['errmsg'] == 'ok' && isset($result['userid'])) {
				return $result;
			} else {
				$this->errcode = $result['errcode'];
				$this->errmsg = $result['errmsg'];
				return 0;
			}
		} else {
			$this->errcode = $curl->errorCode;
			return 0;
		}
	}

	/**
	 * 根据企业微信oauth code获取userid动作
	 * @param string $code code
	 */
	public function oauth2UserId($code)
	{
		$this->getToken();
		$url = "https://qyapi.weixin.qq.com/cgi-bin/user/getuserinfo?access_token=" . $this->token . "&code=" . $code;
		$curl = new curl\Curl();
		$response = $curl->get($url);
		if ($curl->errorCode === null) {
			$result = json_decode($response, true);
			return $result;
		} else {
			$this->errcode = $curl->errorCode;
			return 0;
		}
	}

	/**
	 * userid转openid
	 * @param string $userId userId
	 */
	public function convert2OpenId($userId)
	{
		$param = json_encode(['userid' => $userId]);
		$this->getToken();
		$url = 'https://qyapi.weixin.qq.com/cgi-bin/user/convert_to_openid?access_token=' . $this->token;
		$curl = new curl\Curl();
		$response = $curl->setRequestBody($param)->post($url);
		if ($curl->errorCode === null) {
			$result = json_decode($response, true);
			if (isset($result['errcode']) && $result['errcode'] > 0) {
				$this->errcode = $result['errcode'];
				$this->errmsg = $result['errmsg'];
				return 0;
			} else {
				return $result;
			}
		} else {
			return 0;
		}
	}
	
	/**
	 * [getcheckinoption 获取员工打卡规则]
	 * $para = {
	 * 		    "datetime": 1511971200,
	 * 		    "useridlist": ["james","paul"]
	 * 		}
	 * @return [data] [指定员工指定日期的打卡规则]
	 */
	public function getcheckinoption($para=null){
		$this->getToken();
		$url = 'https://qyapi.weixin.qq.com/cgi-bin/checkin/getcheckinoption?access_token='.$this->token;
		$curl = new curl\Curl();
		$response = $curl->setRawPostData($para)->post($url);
		if ($curl->errorCode === null) {
			$result = json_decode($response, true);
			if (isset($result['errcode']) && $result['errcode'] > 0) {
				$this->errcode = $result['errcode'];
				$this->errmsg = $result['errmsg'];
				return 0;
			} else {
				return $result;
			}
		} else {
			return 0;
		}
	}
	
	/**
	 * [getcheckindata 获取打卡记录数据]
	 * $para = {
	 * 			"opencheckindatatype": 3,
	 * 			"starttime": 1492617600,
	 * 			"endtime": 1492790400,
	 * 			"useridlist": ["james","paul"]
	 * 			}
	 * @return [data] [指定员工指定时间段内的打卡记录数据]
	 */
	public function getcheckindata($para=null){
		$this->getToken();
		$url = 'https://qyapi.weixin.qq.com/cgi-bin/checkin/getcheckindata?access_token='.$this->token;
		$curl = new curl\Curl();
		$response = $curl->setRawPostData($para)->post($url);
		if ($curl->errorCode === null) {
			$result = json_decode($response, true);
			if (isset($result['errcode']) && $result['errcode'] > 0) {
				$this->errcode = $result['errcode'];
				$this->errmsg = $result['errmsg'];
				return 0;
			} else {
				return $result;
			}
		} else {
			return 0;
		}
	}
	
	/**
	 * [addcheckinrecord 添加打卡记录]
	 * $para = {
	 * 			"records": [
	 * 				{
	 * 					"userid": "userId",
	 * 					"checkin_time": 1654486800,
	 * 					"location_title": "1234",
	 * 					"location_detail": "1234",
	 * 					"mediaids": [
	 * 						"mediaId",
	 * 					],
	 * 					"notes": "",
	 * 					"device_type": 1,
	 * 					"lat": 22234233,
	 * 					"lng": 1233123,
	 * 					"device_detail": "device_detail_test",
	 * 					"wifiname": "Tencent-WiFi",
	 * 					"wifimac": "a2:8b:7f:c0:27:4b"
	 * 				}
	 * 			]
	 * 		}
	 * @return [data] [{"errcode": 0,"errmsg": "ok"}]
	 */
	public function addcheckinrecord($para=null){
		$this->getToken();
		$url = 'https://qyapi.weixin.qq.com/cgi-bin/checkin/add_checkin_record?access_token='.$this->token;
		$curl = new curl\Curl();
		$response = $curl->setRawPostData($para)->post($url);
		if ($curl->errorCode === null) {
			$result = json_decode($response, true);
			if (isset($result['errcode']) && $result['errcode'] > 0) {
				$this->errcode = $result['errcode'];
				$this->errmsg = $result['errmsg'];
				return 0;
			} else {
				return $result;
			}
		} else {
			return 0;
		}
	}
}
