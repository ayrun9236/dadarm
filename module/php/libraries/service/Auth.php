<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
//todo 다시
class Auth
{
	protected $ci;

	public function __construct()
	{
		$this->ci =& get_instance();
	}

	public function check()
	{
//		$token_key = $this->ci->input->get_request_header('Authorization');
//		if (!$token_key) {
//			$this->ci->response_error('인증정보가 없습니다.[1]', RestController::HTTP_UNAUTHORIZED);
//		}
//
//		$auth_token = $this->ci->db->get_where('user_auth_token', array('token_key' => $token_key))->row();
//
//		if ($auth_token) {
//			// 토큰 만료 체크
////			if (date_diff(new DateTime($auth_token->{'insert_dt'}), new DateTime())->days > 10) {
////				$this->ci->db->delete('user_auth_token',array('user_id'=>$auth_token->no));
////				$this->ci->response_error('토큰이 만료 되었습니다.', REST_Controller::HTTP_UNAUTHORIZED);
////			}
//
//			$info = $this->ci->db->get_where('user', array('user_no' => $auth_token->user_no, 'is_leave' => 0))->row();
//
//			if (!$info) {
//				$this->ci->response_error('권한이 없습니다.[1]', RestController::HTTP_UNAUTHORIZED);
//			}
//
//			return $info;
//
//		} else {
//			$this->ci->response_error('권한이 없습니다.[2]', RestController::HTTP_UNAUTHORIZED);
//		}

		$this->ci->load->library('common/authorization_token');

		$is_valid_token = $this->ci->authorization_token->validateToken();

		if(!empty($is_valid_token) AND $is_valid_token['status'] === TRUE){
			return $is_valid_token['data'];
		}
		else{
			$this->ci->response_error('로그인이 필요합니다.', RestController::HTTP_UNAUTHORIZED);
		}
	}

	// 인증 정보로 회원정보만 가져오기
	public function info($mode = 'api')
	{
//		if ($token_key == '') {
//			$token_key = $this->ci->input->get_request_header('Authorization');
//		}
//
//		if ($token_key) {
			//$auth_token = $this->ci->db->get_where('user_auth_token', array('token_key' => $token_key))->row();

			$this->ci->load->library('common/authorization_token');
			if($mode == 'post'){
				$is_valid_token = $this->ci->authorization_token->validateTokenPost();
			}
			else{
				$is_valid_token = $this->ci->authorization_token->validateToken();
			}

			if(!empty($is_valid_token) AND $is_valid_token['status'] === TRUE){
				return $is_valid_token['data'];
			}


//			if ($auth_token) {
//				//$auth_type = $auth_token->auth_type;
//				$info = $this->ci->db->get_where('user', array('user_no' => $auth_token->user_no, 'is_leave' => 0))->row();
//
//				if ($info) {
//					return $info;
//				}
//
//			}
		//}
		return null;
	}


	public function token_make($user_no, $unique_id)
	{
		$token_data = $this->ci->db->get_where('user_auth_token', array( 'user_no' => $user_no))->row();
		if($token_data){
			$new_key = $token_data->token_key;
		}
		else{
			$this->ci->db->delete('user_auth_token', array('user_no' => $user_no));

			do {
				$salt = base_convert(bin2hex($this->ci->security->get_random_bytes(64)), 16, 36);

				if ($salt === FALSE) {
					$salt = hash('sha256', time() . mt_rand());
				}

				$new_key = substr($salt, 0, 40);
			} while ($this->token_exists($new_key));

			$this->ci->db->insert('user_auth_token', array(
				'user_no'   => $user_no,
				'unique_id' => $unique_id,
				'token_key' => $new_key,
			));

		}

		return $new_key;
	}

	private function token_exists($key)
	{
		return $this->ci->db->where('token_key', $key)->count_all_results('user_auth_token') > 0;
	}

}
