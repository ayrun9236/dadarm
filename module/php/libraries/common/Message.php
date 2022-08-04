<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Message Class
 *
 * 알림 라이브러리
 */
class Message
{

    /**
     * @var CI_Controller
     */
    protected $ci;

    protected $error_code;
    protected $message;

    /**
	 *  constructor.
	 */
	function __construct()
	{
		$this->ci =& get_instance();
		$this->error_code = ERROR_CODE['HTTP_NOT_OK'];
	}

    /**
     * 메시지 만들어 반환함.
     *
     * @param $params template_code
     *                content
     * @return array|bool
     */
    public function get_message($params) {
        if (is_array($params)) {
            $params = (object)$params;
        }

        $sql = "SELECT code, title, content
                                     FROM message_template
                                     WHERE code = ?";
        $template = $this->ci->db->query($sql, array($params->template_code))->row();

        if(!$template){
            $this->message = '메세지 템플릿이 존재하지 않습니다.';
            return false;
        }


        // 메시지 내용 만들기
        $content = $template->content;

        if(isset($params->content)){
			foreach ($params->content as $key => $value) {
				$content = str_replace('#{' . $key . '}', (string)$value, $content);
			}
		}

		$content = preg_replace('/\#\{.*?\}/',"-",$content);

        return (object)array(
            'template_code' => $template->code,
            'title' => $template->title,
            'content' => $content,
        );
    }

    /**
     * sms 실패일 경우 결과 확인
     * send() 가 false 일 경우 호출
     *
     * @return mixed
     */
    public function get_error() {
        return (object)array(
            'code' => $this->error_code,
            'message' => 'Message api '.$this->message,
        );
    }
}