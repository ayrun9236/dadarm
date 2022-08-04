<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once 'Message.php';

class Firebase extends Message

{

    /**
     * Firebase 고유키
     *
     * @var string
     */
    protected $app_key = 'AAAAKjDQ2FY:APA91bEp0OnPRBImAArRepukDP4ZGlxzZD2ZA_26vrRYVOHXSS3MSo00meyEKC57Z6v1PjLzEDIHhfchAv1egyWI4ZWF4aSBQhG08zqWIM5qROvM0oQhoNniFbCrHXSvKWgE_URXrYQW';

    /**
     * Firebase 발송 URL
     *
     * @var string
     */

    protected $fcm_url = 'https://fcm.googleapis.com/fcm/send';

    /**
     * 헤더
     *
     * @var
     */
    protected $headers;

    /**
     * Firebase constructor.
     */
    function __construct() {
        $this->ci =& get_instance();

        $this->headers = array(
            'Authorization: key=' . $this->app_key,
            'Content-Type: application/json',
        );
    }

    /**
     * 발송하기
     *
     * @param $params to 받는사람
     *                content 내용
     *                action 후속 액션
     *                data 추가 데이터
     * @return mixed
     */
    public function send($params) {
        $fields = array(
            'to' => $params->to,
            'data' => array(
            	'content' => $params->content,
				'title' => $params->title,
				'content_id' => $params->content_id,
				'target_link' => $params->target_link,
            ),
			'notification' => array(
				'body' => $params->content,
				'title' => $params->title,
			),
        );

        log_message('error', print_r($fields,true));

        $fields_json = json_encode($fields);
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->fcm_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_json);

        $result = curl_exec($ch);

        if($result === false){
			$this->message = '[Error]' . curl_error();
			//echo('Curl failed ' . curl_error());
		}

		log_message('error',print_r($result,true));
        curl_close($ch);
        return json_decode($result);
    }
}