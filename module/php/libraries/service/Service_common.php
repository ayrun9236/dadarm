<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Service_common
{
	protected $ci;
	protected $error_code = null;
	protected $error_message = null;
	protected $platform = '';

	public function __construct()
	{
		$this->ci =& get_instance();
		$this->error_code = ERROR_CODE['HTTP_NOT_OK'];
		$this->platform = $this->ci->input->get_request_header('Platform');
		$this->ci->load->model('service/generic_model');
	}

	/** 
	 * 에러 코드 반환
	 *
	 * @return object
	 */
	public function get_error()
	{
		return (object)array(
			'code'    => $this->error_code,
			'message' => $this->error_message,
		);
	}

	function get_codes($searchs = array()){
		$this->ci->load->model('service/code_model');
		$parent_code = $searchs['parent_code'];
		unset($searchs['parent_code']);

		$ret = $this->ci->code_model->sub_codes($parent_code, $searchs);

		if($ret){
			foreach ($ret as $key => $value){
				$ret[$key]->etc_data = json_decode($value->etc_data);
			}
		}
		return $ret ;
	}

	function user_child_validate($user_no, $child_no){
		$this->ci->generic_model->set_table('family');
		$data_exists = $this->ci->generic_model->get(array('user_no' => $user_no, 'family_no' => $child_no), 'family_no');
		if($data_exists){
			return true;
		}

		return false;
	}

	function image_save($target_table, $target_no, $mode = 'new'){
		$this->ci->load->library('common/image_upload');
		$upload_result = $this->ci->image_upload->upload($target_table, $target_no, 'image', $mode);
		if ($upload_result === false) {
			$this->error_message = '첨부파일 업로드 시 오류가 발생했습니다.';
			return false;
		}

		return $upload_result;
	}

	function image_delete($target_table, $target_no){
		$this->ci->load->library('common/image_upload');
		$upload_result = $this->ci->image_upload->delete($target_table, $target_no);

		return $upload_result;
	}

	function push_check($mode, $params){

		$push_array = array();
		if(in_array($mode, array('post','comment'))){
			$push_check = $this->ci->generic_model->post_push_check($params['target_table'], $params['target_no'], $params['comment_parent_no']);
			if($push_check){
				if($push_check->user_no != $params['user_no'] && $push_check->is_push_agree == 1 && $push_check->push_id != ''){
					$push_array[] = array(
						'template_code' => 'COMMENT_WRITE',
						'content' => array('GROUP_NAME' => '그룹명'),
						'to' => $push_check->push_id,
						'target_link' => APP_URL."/community/good-story"
					);
				}

				if($push_check->comment_user_info != ''){
					$push_check->comment_user_info = explode('||', $push_check->comment_user_info);
					if($push_check->comment_user_info[2] != $params['user_no'] && $push_check->comment_user_info[1] == 1 && $push_check->comment_user_info['0'] != ''){
						$push_array[] = array(
							'template_code' => 'COMMENT_WRITE',
							'content' => array('GROUP_NAME' => '그룹명'),
							'to' => $push_check->comment_user_info['0'],
							'target_link' => APP_URL."/community/good-story"
						);
					}
				}
			}
		}
		else if($mode == 'friend_request'){
			$push_check = $this->ci->generic_model->user_push_check($params['user_no']);
			if($push_check && $push_check->is_push_agree == 1 && $push_check->push_id != ''){
				$push_array[] = array(
					'template_code' => 'FRIEND_REQUEST',
					'content' => array(),
					'to' => $push_check->push_id,
					'target_link' => APP_URL."/profile"
				);
			}
		}else if($mode == 'friend_confirm'){
			$push_check = $this->ci->generic_model->user_push_check($params['user_no']);
			if($push_check && $push_check->is_push_agree == 1 && $push_check->push_id != ''){
				$push_array[] = array(
					'template_code' => 'FRIEND_CONFIRM',
					'content' => array(),
					'to' => $push_check->push_id,
					'target_link' => APP_URL."/profile"
				);
			}
		}

//		print_r($push_array);
		if(count($push_array)){
			$this->ci->load->library('common/firebase');
			foreach ($push_array as $key => $item){
//				$push_data = array(
//					'to' => $params['ptoken'],
//					'title' =>'로그인',
//					'content' =>'로그인되었다',
//					'content_id' =>1,
//					'target_link' => "https://app.da-daleum.com/group",
//				);
//				$push_result = $this->ci->firebase->send((object)$push_data);

				$push_data = $this->ci->firebase->get_message($item);


				$push_data = array(
					'to' => $item['to'],
					'title' => $push_data->title,
					'content' => $push_data->content,
					'content_id' => $key,
					'target_link' => $item['target_link'],
				);
//				print_r($push_data);
				$push_result = $this->ci->firebase->send((object)$push_data);
			}
		}

	}

	public function block_proc($user_no, $params)
	{
		$table = 'user_board';
		if($params['type'] == 'group_post'){
			$table = 'user_group_post';
		}else if($params['type'] == 'comment'){
			$table = 'post_comment';
		}

		$this->ci->generic_model->set_table($table);
		$data = $this->ci->generic_model->get(array($table.'_no' => $params['post_no']));
		if(!$data){
			$this->error_message = '잘못된 접근입니다.';
			return false;
		}

		$this->ci->generic_model->block_insert(array('user_no' => $user_no, 'block_user_no' => $data->user_no));

		return true;
	}

	public function declaration_proc($user_no, $params)
	{
		$insert_data = array(
			'user_no'      => $user_no,
			'post_no'      => $params['post_no'],
			'target_table' => $params['type'],
		);

		$this->ci->generic_model->set_table('user_post_declaration');
		$data = $this->ci->generic_model->insert($insert_data);

		return true;
	}
	
}
