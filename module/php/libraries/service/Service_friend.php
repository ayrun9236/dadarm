<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Service_friend extends Service_common
{
	protected $ci;

	public function __construct()
	{
		$this->ci =& get_instance();
		$this->ci->load->model('service/friends_model');
	}

	public function lists($params){
		$searchs = array();

		if(!isset($params['page'])){
			$params['page'] = 1;
		}

		if(!isset($params['per_page'])){
			$params['per_page'] = 1;
		}

		if(isset($params['mode']) &&  $params['mode']!= ''){
			$searchs['and']['f.is_agree'] = 0;
			$searchs['and']['f.is_request'] = 0;
			if($params['mode'] == 'request-you'){
				$searchs['and']['f.is_request'] = 1;
			}
		}
		else{
			$searchs['and']['f.is_agree'] = 1;
		}

		if(isset($params['search']) &&  $params['search']!= ''){
			$searchs['like']['and']['u.user_name']  =  $params['search'];
		}

		$searchs['and']['f.user_no'] = $params['user_no'];
		
		$data = $this->ci->friends_model->lists($params['page'], $params['per_page'], $searchs);
		foreach ($data as $key => $item) {
			$data[$key]->is_push_receive = $item->is_push_receive == 1 ? true : false;
		}

		return $data;
	}

	public function add_proc($user_no, $params)
	{
		$insert_data = array(
			'user_no'  => $user_no,
			'is_request'  => 1
		);

//		if (!is_numeric($params['friend_no'])) {
//			$params['friend_no'] = $this->mcrypt->decrypt($params['friend_no']);
//		}

		$friend_no = 0;
		$this->ci->friends_model->set_table('user');
		$user = $this->ci->friends_model->get(array('login_id' => $params['email'], 'is_leave' => 0), 'user_no');
		if($user){
			$friend_no = $user->user_no;
		}
		else{
			$this->ci->friends_model->set_table('user');
			$user = $this->ci->friends_model->get(array('user_name' => $params['email'], 'is_leave' => 0), 'user_no');
			if($user){
				$friend_no = $user->user_no;
			}
			else{
				$this->error_message = '입력하신 메일주소는 회원정보에 존재하지 않습니다.';
				return false;
			}
		}

		if($friend_no == $user_no){
			$this->error_message = '자기자신은 친구로 추가 할 수 없습니다.';
			return false;
		}

		$is_insert = $this->ci->friends_model->friend_insert($user_no, $friend_no);
		if($is_insert === false){
			$this->error_message = '이미 등록요청된 친구입니다.';
			return false;
		}

		$this->ci->friends_model->set_table('user_friends');
		$insert_data = array(
			'user_no'  => $friend_no,
			'friend_no'  => $user_no,
			'is_request'  => 0
		);
		$user_friend_no = $this->ci->friends_model->insert($insert_data);

		//푸시
		$this->push_check('friend_request', array('user_no' => $friend_no));

		return array();
	}

	public function delete_proc($user_no, $friend_no)
	{
		$this->ci->friends_model->set_table('user_friends');
		$where_data = array(
			'user_no'   => $user_no,
			'friend_no' => $friend_no,
		);

		$is_delete = $this->ci->friends_model->delete($where_data);
		if($is_delete){
			$where_data = array(
				'friend_no' => $user_no,
				'user_no'   => $friend_no,
			);

			$is_delete = $this->ci->friends_model->delete($where_data);
		}
		else{
			$this->error_message = '올바른 접근이 아닙니다.';
			return false;
		}

		return $is_delete;
	}

	public function comfirm_proc($user_no, $friend_no)
	{
		$this->ci->friends_model->set_table('user_friends');
		$where_data = array(
			'user_no'   => $user_no,
			'friend_no' => $friend_no,
		);

		$is_agree = $this->ci->friends_model->update(array('is_agree' => 1), $where_data);

		$where_data = array(
			'friend_no' => $user_no,
			'user_no'   => $friend_no,
		);
		$is_agree = $this->ci->friends_model->update(array('is_agree' => 1), $where_data);

		//푸시
		$this->push_check('friend_confirm', array('user_no' => $friend_no));

		$ret = array('is_agree' => 1);

		return $ret;
	}

	public function modify_proc($user_no, $params)
	{
		$this->ci->friends_model->set_table('user_friends');
		$where_data = array(
			'user_no'   => $user_no,
			'friend_no' => $params['friend_no'],
			'is_agree' => 1,
		);

		$update_data = array();

		if(isset($params['is_support'])){
			$update_data['is_support'] = $params['is_support'];
		}

		if(isset($params['is_push_receive'])){
			$update_data['is_push_receive'] = $params['is_push_receive'];
		}

		$is_update = $this->ci->friends_model->update($update_data, $where_data);

		return $is_update;
	}

	// 1: 친구 2: 서퍼터즈
	public function is_friend ($user_no, $friend_no) {
		$this->ci->friends_model->set_table('user_friends');
		$where_data = array(
			'user_no'   => $user_no,
			'friend_no' => $friend_no,
		);

		$info = $this->ci->friends_model->get($where_data, 'is_agree, is_support');
		if($info && $info->is_agree == 1){
			return $info->is_support == 1 ? 2 : 1;
		}
		else{
			$this->error_message = '친구가 아닙니다.';
			return false;
		}
	}

	public function friend_detail($friend_no, $user_no = 0){
		$this->ci->load->model('service/user_model');

		$user_data = $this->ci->user_model->detail($friend_no);

		$friend_mode = $this->is_friend($friend_no, $user_no);
		$comment_write = false;
		$post_write = false;
		if($friend_mode !== false){
			if($friend_mode == 1) {
				switch ($user_data->is_friend_write_mode){
					case '10' : $post_write = true; break;
					case '11' : $comment_write = true; $post_write = true; break;
					case '1' : $comment_write = true;break;
				}
			}

			if($friend_mode == 2) {
				switch ($user_data->is_supporter_write_mode){
					case '10' : $post_write = true; break;
					case '11' : $comment_write = true; $post_write = true; break;
					case '1' : $comment_write = true;break;
				}
			}
		}

		$return_data = [
			'name'             => $user_data->name,
			'img'              => $user_data->user_image,
			'profile_setting'  => $user_data->profile_setting,
			'is_post_write'    => $post_write,
			'is_comment_write' => $comment_write,
		];

		return $return_data;
	}

}
