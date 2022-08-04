<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once 'Message.php';

class Sms extends Message
{
	//TODO 관리자에서 사용하는 링크도 바꿔줘야함.
	/**
	 * 아이디
	 *
	 * @var string
	 */
	protected $id;

	/**
	 * 고유키
	 *
	 * @var string
	 */
	private $key;

	/**
	 * Sms constructor.
	 */
	function __construct()
	{
		$this->ci =& get_instance();
		//parent::__construct($this->id, $this->key);
		$this->id = SMS['id'];
		$this->key = SMS['key'];
	}

	/**
	 * SMS 발송
	 *
	 * @param $params to_phone 받는사람
	 *                content 내용
	 *                title SMS일 경우 null, LMS일 경우 값
	 * @return boolean
	 */
	public function send($params)
	{
		$result = false;
		$sms_url = "https://apis.aligo.in/send/"; // 전송요청 URL
		$sms['user_id'] = $this->id; //SMS 아이디.
		$sms['key'] = $this->key;//인증키
		$sms['msg'] = stripslashes($params->content);
		$sms['msg_type'] = 'SMS'; // SMS, LMS, MMS등 메세지 타입을 지정
		if (mb_strlen($params->content, 'EUC-KR') > 80) {
			$sms['msg_type'] = 'LMS';
			$sms['subject'] = $params->title;
		}

		$sms['receiver'] = str_replace('-', '', $params->to_phone);
		$sms['sender'] = SMS['sender'];

		if (isset($params->testmode_yn)) {
			$sms['testmode_yn'] = 'Y';
		}

		$host_info = explode("/", $sms_url);
		$port = $host_info[0] == 'https:' ? 443 : 80;

		$oCurl = curl_init();
		curl_setopt($oCurl, CURLOPT_PORT, $port);
		curl_setopt($oCurl, CURLOPT_URL, $sms_url);
		curl_setopt($oCurl, CURLOPT_POST, 1);
		curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($oCurl, CURLOPT_POSTFIELDS, $sms);
		curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
		$ret = curl_exec($oCurl);
		curl_close($oCurl);

		//echo $ret;
		$retArr = json_decode($ret); // 결과배열
		// print_r($retArr); // Response 출력 (연동작업시 확인용)
		//log_message('error', print_r($retArr, true));
		/**** Response 예문 끝 ****/

		//발송결과 알림
		if ($retArr->result_code == 1) {
			$result = true;
		} else {
			$this->message = '[Error]' . $retArr->message;
		}

		if (!isset($params->template_code)) {
			$params->template_code = '';
		}

		if (false === isset($params->user_no)) {
			$params->user_no = 0;
		}

		if (false === isset($params->order_no)) {
			$params->order_no = 0;
		}

		$insert_data = array(
			'result'        => $retArr->result_code,
			'template_code' => $params->template_code,
			'send_sms_no'   => isset($retArr->msg_id) ? $retArr->msg_id : 0,
			'phone'         => $params->to_phone,
			'sms'           => $params->content,
			'from_phone'    => $sms['sender'],
			'user_no'       => $params->user_no,
			'order_no'      => $params->order_no,
		);
		$this->ci->db->insert('slowraw_log.sms_log', $insert_data);

		return $result;
	}


	/**
	 * SMS 잔여건수
	 *
	 * @return integer (-1: 오류)
	 */
	public function remain_count()
	{
		$result = -1;
		$sms_url = "https://apis.aligo.in/remain/";
		$sms['user_id'] = $this->id; //SMS 아이디.
		$sms['key'] = $this->key;//인증키

		$host_info = explode("/", $sms_url);
		$port = $host_info[0] == 'https:' ? 443 : 80;

		$oCurl = curl_init();
		curl_setopt($oCurl, CURLOPT_PORT, $port);
		curl_setopt($oCurl, CURLOPT_URL, $sms_url);
		curl_setopt($oCurl, CURLOPT_POST, 1);
		curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($oCurl, CURLOPT_POSTFIELDS, $sms);
		curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);

		$ret = curl_exec($oCurl);
		curl_close($oCurl);

		$retArr = json_decode($ret); // 결과배열
		if ($retArr->result_code == 1) {
			return $retArr->SMS_CNT;
		}
		return $result;
	}
}