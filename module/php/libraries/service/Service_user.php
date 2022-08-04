<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Service_user extends Service_common
{
	protected $ci;

	public function __construct()
	{
		$this->ci =& get_instance();
		$this->ci->load->model('service/user_model');
	}

	public function create($params)
	{
		$user_no = 0;
		if (!is_numeric($params['regist_type'])) {
			$params['regist_type'] = $this->get_codes(array('parent_code' => 'REGIST_TYPE', 'code' => strtoupper($params['regist_type'])))[0]->no;

			if (!$params['regist_type']) {
				$this->error_message = '가입타입이 올바르지 않습니다.';
				return false;
			}
		}

		$insert_data = $params;

		$this->ci->user_model->set_table('user');

		$check_data = array(
			'user_name' => $insert_data['user_name'],
			'is_leave'  => 0,
		);
		$user_exists = $this->ci->user_model->get($check_data, 'user_no');
		if ($user_exists) {
			$this->error_message = '닉네임이 중복됩니다.';
			return false;
		}


		$check_data = array(
			'login_id'    => $insert_data['login_id'],
			'is_leave'    => 0,
		);

		$user_exists = $this->ci->user_model->get($check_data, 'user_no');
		if ($user_exists) {
			$this->error_message = '이미 가입되어 있는 메일입니다. 로그인을 시도해 보세요.';
			return false;
		}

		if(isset($params['sns_id']) && $params['sns_id'] != ''){
			$insert_data['sns_id'] = $params['sns_id'];
		}

		$insert_data['login_password'] = $this->ci->general->password_set(trim($insert_data['login_password']));

		$user_no = $this->ci->user_model->join($insert_data);


		return $user_no;
	}

	public function detail($user_no, $mode = ''){
		$user_data = $this->ci->user_model->detail($user_no);
		$return_data = [
			'name'             => $user_data->name,
			'email'            => $user_data->login_id,
			'img'              => $user_data->user_image,
			'regist_type_code' => $user_data->regist_type_code,
			//'created_at' => $output->created_at,
		];

		if($mode == 'token'){
			//todo 한번에 하기
			$this->ci->load->library('common/authorization_token');

			// Generate Token
			$token_data['user_no'] = $user_data->user_no;
			$token_data['name'] = $user_data->name;
			$token_data['email'] = $user_data->login_id;
//		$token_data['created_at'] = $output->created_at;
//		$token_data['updated_at'] = $output->updated_at;
			$token_data['time'] = time();

			$user_token = $this->ci->authorization_token->generateToken($token_data);

			$return_data['token'] = $user_token;

		}

		if($mode == 'child'){
			$child_list = $this->family_list($user_no);
			$return_data['childList'] = $child_list;
		}

		return $return_data;

	}

	public function signin($params)
	{
		$params['regist_type'] = strtoupper($params['regist_type']);
		$regist_type_code = $this->get_codes(array('parent_code' => 'REGIST_TYPE', 'code' => $params['regist_type']))[0]->no;

		if($params['regist_type'] == 'EMAIL'){
			$where_data = array(
				'login_id'    => $params['login_id'],
				'regist_type' => $regist_type_code,
				'is_leave'    => 0,
			);

			$user_data = $this->ci->user_model->get($where_data, 'login_id,login_password,user_no,user_name as name');
			if (!$user_data) {
				$this->error_message = '해당 계정이 존재하지 않습니다.';
				if ($params['regist_type'] == 'EMAIL') {
					$this->error_message .= "\n입력하신 정보 확인 후 다시 입력해 주시기 바랍니다.";
				}

				return false;
			}

			if ($params['regist_type'] == 'EMAIL' && $this->ci->general->password_set($params['login_password']) != $user_data->login_password) {
				$this->error_message = '비밀번호가 틀립니다.';
				return false;
			}
		}
		else{
			$where_data = array(
				'sns_id'    => $params['sns_id'],
				'regist_type' => $regist_type_code,
				'is_leave'    => 0,
			);

			$user_data = $this->ci->user_model->get($where_data, 'login_id,login_password,user_no,user_name as name');
			if (!$user_data) {
				$this->error_message = '해당 계정이 존재하지 않습니다.';
				return false;
			}
		}

		$update_data = array();
		$this->ci->db->set('login_count', 'login_count+1', FALSE);

		if(isset($params['ptoken']) && $params['ptoken']!= ''){
			$update_data['push_id'] = $params['ptoken'];
		}

		$this->ci->user_model->set_table('user_detail');
		$this->ci->user_model->update($update_data, array('user_no' =>$user_data->user_no));


//		$login_info->token_key = $this->ci->auth->token_make('user', $user_data->user_no, $params['device_id']);
		// $user_data->token = $this->ci->auth->token_make($user_data->user_no,  $user_data->user_no);

		$this->ci->user_model->set_table('moaayo_log.user_login_log');
		$insert_data = array(
			'user_no'  => $user_data->user_no
		);
		$this->ci->user_model->insert($insert_data);

		$this->ci->load->library('common/authorization_token');

		// Generate Token
		$token_data['user_no'] = $user_data->user_no;
		$token_data['name'] = $user_data->name;
		$token_data['email'] = $user_data->login_id;
//		$token_data['created_at'] = $output->created_at;
//		$token_data['updated_at'] = $output->updated_at;
		$token_data['time'] = time();

		$user_token = $this->ci->authorization_token->generateToken($token_data);

		$child_list = $this->family_list($user_data->user_no);
		$this->ci->user_model->set_table('family');
		$child_list = $this->ci->user_model->list(array('user_no' => $user_data->user_no), 'family_no as no, name');

		$user_img = $this->ci->user_model->image($user_data->user_no, 'user');
		if($user_img){
			$user_img = $user_img[0]->thumb_image;
		}
		else{
			$user_img = UPLOAD['S3_URL'].UPLOAD['NO_PROFILE'];
		}

		$return_data = [
			'no'       => $user_data->user_no,
			'name'       => $user_data->name,
			'email'      => $user_data->login_id,
			//'created_at' => $output->created_at,
			'img'		=> $user_img,
			'token'      => $user_token,
			'childList' => $child_list,
		];

		return $return_data;

	}

	public function family_list($user_no, $child_no = 0)
	{
		$data = $this->ci->user_model->child_list($user_no, $child_no);

		return $data;
	}

	public function recoverpassword_email_proc($params){

		$regist_type_code = $this->get_codes(array('parent_code' => 'REGIST_TYPE', 'code' => 'EMAIL'))[0]->no;

		$check_data = array(
			'login_id' => $params['email'],
			'regist_type' => $regist_type_code,
			'is_leave'    => 0,
		);

		$info = $this->ci->user_model->get($check_data, 'user_no');

		if (!$info) {
			$this->error_message = '존재하지 않는 이메일입니다.';
			return false;
		}

		// 메일 보내기
		$config['protocol'] = 'smtp';
		$config['smtp_host'] = 'ssl://email-smtp.ap-northeast-2.amazonaws.com';
		$config['smtp_user'] = 'AKIAVPTJWPZXCD25UFF3';
		$config['smtp_pass'] = 'BNdCmrAU32zIaRnYqNOmvIK+8ol4tc6AUllW97kv6De2';
		$config['smtp_port'] = 465;
//		$config['smtp_crypto'] = 'tls';
		$config['charset'] = 'utf-8';
		$config['mailtype'] = 'html';
		$config['wordwrap'] = true;
		$this->ci->load->library('email');
		$this->ci->email->initialize($config);
		$this->ci->email->set_newline("\r\n");

		$this->ci->load->library('common/mcrypt');
		$link = date('Y-m-d h:i:s').'##'.$params['email'].'$$'.time();
		$link = $this->ci->mcrypt->encrypt($link);
		$link = $this->ci->general->short_url(APP_URL.'/auth/recoverpassword/'.$link);
		$message = "안녕하세요. 다다름입니다.
비밀번호 재설정 링크를 보내드립니다.
비밀번호 재설정 링크 : <a href='".$link."''>".$link."</a>

감사합니다.		
		";
		$this->ci->email->from('help@da-daleum.com','다다름');
		$this->ci->email->to($params['email']);
		$this->ci->email->subject('다다름 비밀번호 찾기 정보 입니다.');
		$this->ci->email->message($message);
		$result = $this->ci->email->send();

		return true;
	}

	public function recoverpassword_proc($params){

		$this->ci->load->library('common/mcrypt');
		$check_data = $this->ci->mcrypt->decrypt($params['check']);
		$_check_data = explode('$$', $check_data);
		if(count($_check_data) < 2){
			$this->error_message = '이메일 정보가 올바르지 않습니다.';
			return false;
		}

		$_check_data = explode('##',$_check_data[0]);
		if(count($_check_data) < 2){
			$this->error_message = '이메일 정보가 올바르지 않습니다.';
			return false;
		}

		$email = $_check_data[1];
		$regist_type_code = $this->get_codes(array('parent_code' => 'REGIST_TYPE', 'code' => 'EMAIL'))[0]->no;
		$check_data = array(
			'login_id' => $email,
			'regist_type' => $regist_type_code,
			'is_leave'    => 0,
		);

		$update_data = array(
			'login_password' => $this->ci->general->password_set(trim($params['password']))
		);

		$data = $this->ci->user_model->update($update_data, $check_data);
		if($data < 1){
			$this->error_message = '올바른 접근이 아닙니다.';
			return false;
		}
		return true;
	}

	// todo 이미지 작게하기
	public function child_proc($user_no, $params)
	{
		$this->ci->user_model->set_table('family');
		$insert_data = array(
			'user_no'  => $user_no,
			'name'     => $params['name'],
			'birthday' => $params['birthday'],
			'gender'   => $params['gender'],
		);


		if ($params['no'] == 0) {
			$child_no = $this->ci->user_model->insert($insert_data);
		} else {
			$child_no = $this->ci->user_model->update($insert_data, array('family_no' => $params['no'], 'user_no' => $user_no));
			$child_no = $params['no'];
		}

		$ret = array('child_no' => $child_no, 'img' => '');
		if ($child_no > 0 && count($_FILES) > 0) {
			$this->ci->load->library('common/image_upload');
			$upload_result = $this->ci->image_upload->upload('family', $child_no, 'image', 'new');
			if ($upload_result === false) {
				$this->error_message = '첨부파일 업로드 시 오류가 발생했습니다.';
				return false;
			}

			$ret['img'] = UPLOAD['S3_URL'] . $upload_result[0]['thumb_img'];
		}

		return $ret;
	}

	public function child_delete_proc($user_no, $child_no)
	{
		$this->ci->user_model->set_table('family');
		$where_data = array(
			'user_no'   => $user_no,
			'family_no' => $child_no,
		);

		$is_delete = $this->ci->user_model->delete($where_data);
		if($is_delete){
			$this->ci->load->library('common/image_upload');
			$this->ci->image_upload->delete('family', $child_no);
			return true;
		}
		else{
			$this->error_message = '삭제 시 오류가 발생했습니다.';
			return false;
		}
	}

	// todo 이미지 작게하기
	public function modify_proc($user_no, $params)
	{
		$check_data = array(
			'user_name' => $params['name'],
			'is_leave'  => 0,
		);
		$user_exists = $this->ci->user_model->get($check_data, 'user_no');
		if ($user_exists && $user_exists->user_no != $user_no) {
			$this->error_message = '닉네임이 중복됩니다.';
			return false;
		}


		$check_data = array(
			'login_id'    => $params['email'],
			'is_leave'    => 0,
		);

		$user_exists = $this->ci->user_model->get($check_data, 'user_no');
		if ($user_exists && $user_exists->user_no != $user_no) {
			$this->error_message = '이메일이 중복됩니다.';
			return false;
		}

		$update_data = array(
			'user_name' => $params['name'],
			'login_id'  => $params['email'],
		);

		$data = $this->ci->user_model->update($update_data, array('user_no' => $user_no));


		$ret = array('user_no' => $user_no, 'img' => '');
		if ($user_no > 0 && count($_FILES) > 0) {
			$this->ci->load->library('common/image_upload');
			$upload_result = $this->ci->image_upload->upload('user', $user_no, 'image', 'new');
			if ($upload_result === false) {
				$this->error_message = '첨부파일 업로드 시 오류가 발생했습니다.';
				return false;
			}
		}

		return $ret;
	}

	public function password_proc($user_no, $params)
	{
		$check_data = array(
			'user_no' => $user_no
		);
		
		$info = $this->ci->user_model->get($check_data, 'login_password');
		
		if ($info && $info->login_password != $this->ci->general->password_set(trim($params['now']))) {
			$this->error_message = '현재 비밀번호가 올바르지 않습니다.';
			return false;
		}

		$update_data = array(
			'login_password' => $this->ci->general->password_set(trim($params['new']))
		);

		$data = $this->ci->user_model->update($update_data, array('user_no' => $user_no));

		return true;
	}

	public function child_introduce_list($user_no, $introduce_no = 0)
	{
		$data = $this->ci->user_model->child_introduce_list($user_no, $introduce_no);

		return $data;
	}

	public function block_list($user_no)
	{
		$data = $this->ci->user_model->block_list($user_no);

		return $data;
	}

	public function block_delete_proc($user_no, $block_user_no)
	{
		$this->ci->user_model->set_table('user_block_list');
		$where_data = array(
			'user_no'   => $user_no,
			'block_user_no' => $block_user_no,
		);

		$is_delete = $this->ci->user_model->delete($where_data);
		if($is_delete){
			return true;
		}
		else{
			$this->error_message = '삭제 시 오류가 발생했습니다.';
			return false;
		}
	}

	public function child_introduce_detail($user_no, $introduce_no)
	{
		$this->ci->user_model->set_table('user_child_introduce');
		$where_data = array(
			'user_no' => $user_no,
			'user_child_introduce_no' => $introduce_no,
		);

		$data = $this->ci->user_model->get($where_data, '*');

		return $data;
	}

	public function privacy($user_no)
	{
		$this->ci->user_model->set_table('user');
		$where_data = array(
			'user_no' => $user_no,
		);

		$data = $this->ci->user_model->get($where_data, 'profile_setting, group_setting_private, community_setting_private, is_supporter_write_mode, is_friend_write_mode');

		return $data;
	}

	public function privacy_proc($user_no, $params)
	{
		$this->ci->user_model->set_table('user');
		$where_data = array(
			'user_no' => $user_no,
		);

		if($params['profile_setting'] == 'private'){
			$params['is_supporter_write_mode'] = 0;
			$params['is_friend_write_mode'] = 0;
		}
		else if($params['profile_setting'] == 'supporter'){
			$params['is_friend_write_mode'] = 0;
		}

		$update_data = array(
			'profile_setting'           => $params['profile_setting'],
			'group_setting_private'     => $params['group_setting_private'],
			'community_setting_private' => $params['community_setting_private'],
			'is_supporter_write_mode'   => $params['is_supporter_write_mode'],
			'is_friend_write_mode'      => $params['is_friend_write_mode'],
		);

		$data = $this->ci->user_model->update($update_data, $where_data);

		return $data;
	}

	public function push($user_no)
	{
		$this->ci->user_model->set_table('user');
		$where_data = array(
			'user_no' => $user_no,
		);

		$data = $this->ci->user_model->get($where_data, 'is_push_profile, is_push_group, is_push_lecture, is_push_event');
		if($data) {
			$data->is_push_profile = $data->is_push_profile ? true : false;
			$data->is_push_group = $data->is_push_group ? true : false;
			$data->is_push_lecture = $data->is_push_lecture ? true : false;
			$data->is_push_event = $data->is_push_event ? true : false;
		}
		return $data;
	}

	public function push_proc($user_no, $params)
	{
		$this->ci->user_model->set_table('user');
		$where_data = array(
			'user_no' => $user_no,
		);

		$update_data = array(
			'is_push_profile' => $params['is_push_profile'],
			'is_push_group'   => $params['is_push_group'],
			'is_push_lecture' => $params['is_push_lecture'],
			'is_push_event'   => $params['is_push_event'],
		);

		$data = $this->ci->user_model->update($update_data, $where_data);

		return $data;
	}

	// todo 이미지 작게하기
	public function child_introduce_proc($user_no, $params)
	{
		$this->ci->user_model->set_table('user_child_introduce');
		$insert_data = array(
			'user_no'  => $user_no,
			'child_no' => $params['child_no'],
			'title'    => $params['title'],
			'contents' => $params['contents'],
		);

		if ($params['no'] == 0) {
			$data_no = $this->ci->user_model->insert($insert_data);
			if ($data_no > 0 && count($_FILES) > 0) {
				$this->ci->load->library('common/image_upload');
				$upload_result = $this->ci->image_upload->upload('user_child_introduce', $data_no, 'image', 'new');
				if ($upload_result === false) {
					$this->error_message = '첨부파일 업로드 시 오류가 발생했습니다.';
					return false;
				}

				$ret['img'] = UPLOAD['S3_URL'] . $upload_result[0]['thumb_img'];
			}
		} else {
			$data_no = $this->ci->user_model->update($insert_data, array('user_child_introduce_no' => $params['no'], 'user_no' => $user_no));
			$data_no = $params['no'];
		}

		$ret = array('user_child_introduce_no' => $data_no, 'img' => '');


		return $ret;
	}
}
