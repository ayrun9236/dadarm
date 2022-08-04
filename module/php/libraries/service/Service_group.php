<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Service_group extends Service_common
{
	protected $ci;

	public function __construct()
	{
		$this->ci =& get_instance();
		$this->ci->load->model('service/group_model');
	}

	public function lists($params){
		$searchs = array();

		if(!isset($params['page'])){
			$params['page'] = 1;
		}

		if(!isset($params['per_page'])){
			$params['per_page'] = 1;
		}

		if(isset($params['search']) &&  $params['search']!= ''){
			$searchs['like']['or'][]  = array(
				'group_name' => $params['search'], 'group_desc' => $params['search'],
			);
		}

		if(isset($params['user_no']) &&  $params['user_no'] > 0){
			$searchs['user_no'] = $params['user_no'];
			$searchs['user_search_mode'] = $params['user_search_mode'];

		}

		if(isset($params['group_no']) &&  $params['group_no'] > 0){
			$searchs['and']['b.user_group_no'] = $params['group_no'];
		}

		$data = $this->ci->group_model->lists($params['page'], $params['per_page'], $searchs);

		return $data;
	}

	public function add_proc($user_no, $params)
	{
		$insert_data = array(
			'group_name'     => $params['group_name'],
			'group_desc' => $params['group_desc'],
			'is_private' => $params['is_private'],
		);

		$this->ci->group_model->set_table('user_group');
		if ($params['user_group_no'] == 0) {
			$insert_data['manager_user_no'] = $user_no;
			$group_no = $this->ci->group_model->insert($insert_data);
			$this->ci->group_model->set_table('user_group_member');
			$this->ci->group_model->insert(array('user_group_no' => $group_no, 'user_no' => $user_no, 'is_agree' => 1));
		} else {
			$this->ci->group_model->update($insert_data, array('user_group_no' => $params['user_group_no'], 'manager_user_no' => $user_no));
			$group_no = $params['user_group_no'];
		}


		$ret = array('group_no' => $group_no, 'img' => '');
		if ($group_no > 0 && count($_FILES) > 0) {
			$this->ci->load->library('common/image_upload');
			$upload_result = $this->ci->image_upload->upload('user_group', $group_no, 'image', 'new');
			if ($upload_result === false) {
				$this->error_message = '첨부파일 업로드 시 오류가 발생했습니다.';
				return false;
			}
		}

		return $ret;
	}

	public function join_proc($user_no, $params)
	{
		$insert_data = array(
			'user_no'       => $user_no,
			'user_group_no' => $params['user_group_no'],
			'is_agree'      => 0,
		);

		$user_join_validate = $this->user_join_validate($user_no, $params['user_group_no']);
		if($user_join_validate['is_join'] == 1 ){
			$this->error_message = '이미 가입(요청)된 그룹입니다.';
			return false;
		}

		if($user_join_validate['is_private'] == 1){
			$this->error_message = '비공개 그룹으로 가입된 멤버의 초대에 의해서만 가입 가능합니다.';
			return false;
		}

		$this->ci->group_model->set_table('user_group_member');
		$user_group_member_no = $this->ci->group_model->insert($insert_data);

//		$this->ci->group_model->set_table('user_group');
//		$update_data = array();
//		$this->ci->db->set('member_count', 'member_count+1', FALSE);
//		$is_update = $this->ci->group_model->update($update_data, array('user_group_no' =>$params['user_group_no'] ));

		return true;
	}

	public function invate_proc($user_no, $params)
	{
		$insert_data = array(
			'invate_user_no' => $user_no,
			'user_group_no'  => $params['user_group_no'],
			'is_agree'       => 0,
			'is_invate'      => 1,
		);

		$user_join_validate = $this->user_join_validate($user_no, $params['user_group_no']);
		if($user_join_validate['is_join'] === 0 && $user_join_validate['is_agree'] === 0){
			$this->error_message = '초대권한이 없습니다. 해당 그룹에 가입이 된 멤버만 친구초대를 할 수 있습니다.';
			return false;
		}

		$friend_no = 0;
		$this->ci->group_model->set_table('user');
		$user = $this->ci->group_model->get(array('login_id' => $params['email'], 'is_leave' => 0), 'user_no');
		if($user){
			$friend_no = $user->user_no;
		}
		else{
			$this->error_message = '입력하신 메일주소는 회원정보에 존재하지 않습니다.';
			return false;
		}

		$user_join_validate = $this->user_join_validate($friend_no, $params['user_group_no']);
		if($user_join_validate['is_join'] === 1 ){
			if($user_join_validate['is_agree'] === 1){
				$this->error_message = '이미 가입된 멤버입니다.';
			}
			else{
				$this->error_message = '이미 초대 요청된 멤버입니다.';
			}
			return false;
		}

		$insert_data['user_no'] = $friend_no;

		$this->ci->group_model->set_table('user_group_member');
		$user_group_member_no = $this->ci->group_model->insert($insert_data);

		$this->ci->group_model->set_table('user_group');
		$update_data = array();
		$this->ci->db->set('member_count', 'member_count+1', FALSE);
		$is_update = $this->ci->group_model->update($update_data, array('user_group_no' =>$params['user_group_no'] ));

		return true;
	}

	public function confirm_proc($user_no, $params)
	{
		$check_data = array(
			'manager_user_no' => $user_no,
			'user_group_no'   => $params['user_group_no'],
			'user_member_no'  => $params['user_member_no'],
		);

		$is_update = $this->ci->group_model->member_confirm($check_data);
		if($is_update){
			$this->ci->group_model->set_table('user_group');
			$update_data = array();
			$this->ci->db->set('member_count', 'member_count+1', FALSE);
			$is_update = $this->ci->group_model->update($update_data, array('user_group_no' => $params['user_group_no']));
		}
		else{
			$this->error_message = '잘못된 접근입니다.';
			return false;
		}
		return true;
	}

	public function invate_confirm_proc($user_no, $group_no)
	{
		$where_data = array(
			'user_no'       => $user_no,
			'user_group_no' => $group_no,
			'is_agree'      => 0,
			'is_invate'     => 1,
		);

		$this->ci->group_model->set_table('user_group_member');
		$is_update = $this->ci->group_model->update(array('is_agree' => 1), $where_data);
		if($is_update){
			$this->ci->group_model->set_table('user_group');
			$update_data = array();
			$this->ci->db->set('member_count', 'member_count+1', FALSE);
			$is_update = $this->ci->group_model->update($update_data, array('user_group_no' =>$group_no));
		}
		else{
			$this->error_message = '잘못된 접근입니다.';
			return false;
		}

		return true;
	}

	public function detail($group_no, $user_no = 0)
	{
		//권한 체크
		if($user_no > 0){
			$user_join_validate = $this->user_join_validate($user_no, $group_no);
			if(!($user_join_validate['is_agree']) && $user_join_validate['is_private']){
				$this->error_message = '비공개 그룹으로 그룹정보 확인을 할 수 없습니다.';
				return false;
			}
		}

		$searchs = array(
			'group_no'         => $group_no,
		);

		$ret = $this->lists($searchs);
		$ret['list'][0]->user_join = $user_join_validate;

		if (!$ret) {
			$this->error_message = '그룹 정보가 존재하지 않습니다.';
			return false;
		}

		$searchs = array();
		// todo 인원제한
		$page = 1;
		$per_page = 20;

		$searchs['and']['user_group_no'] = $group_no;
		// $searchs['and']['is_agree'] = 1;

		$ret['list'][0]->members = $this->ci->group_model->member_list($page, $per_page, $searchs, array('manager_user_no' => $ret['list'][0]->manager_user_no));
		return $ret['list'][0];
	}

	public function add_post_proc($user_no, $params)
	{
		$insert_data = array(
			'title'         => $params['title'],
			'contents'      => $params['contents'],
		);

		if($params['post_no'] == 0){
			// validate
			$user_join_validate = $this->user_join_validate($user_no, $params['user_group_no']);
			if($user_join_validate['is_agree'] === 0){
				$this->error_message = '그룹에 가입이 되어 있지 않아 글을 쓸 수 없습니다.';
				return false;
			}

			if(isset($params['child_no']) && $params['child_no'] > 0){
				if($this->user_child_validate($user_no, $params['child_no'])){
					$insert_data['child_no'] = $params['child_no'];
				}
			}

			if(isset($params['is_notice']) && $params['is_notice'] == 1){
				$insert_data['is_notice'] = $params['is_notice'];
			}

			$insert_data['user_no'] = $user_no;
			$insert_data['user_group_no'] = $params['user_group_no'];
		}

		$this->ci->group_model->set_table('user_group_post');
		if ($params['post_no'] == 0) {
			$board_no = $this->ci->group_model->insert($insert_data);
			$this->ci->group_model->set_table('user_group');
			$this->ci->db->set('post_count', 'post_count+1', FALSE);
			$this->ci->group_model->update(array(), array('user_group_no' => $params['user_group_no']));
		} else {
			$this->ci->group_model->update($insert_data, array('user_group_post_no' => $params['post_no'], 'user_no' => $user_no));
			$board_no = $params['post_no'];
		}

		$ret = array('post_no' => $board_no, 'images' => array());
		if ($board_no > 0 && count($_FILES) > 0) {
			$image = $this->image_save('user_group_post', $board_no, 'add');
			if($image === false){
				$this->error_message = '첨부파일 업로드 시 오류가 발생했습니다.';
				return false;
			}

			$ret['images'] = $this->ci->group_model->image($board_no, 'user_group_post');
		}

		//푸시
//		$this->push_check('post', array('target_table' => 'user_group_post', 'target_no' => $board_no, 'comment_parent_no' => 0, 'user_no' => $user_no));

		return $ret;
	}

	public function add_comment_proc($user_no, $params)
	{
		$insert_data = array(
			'user_no'      => $user_no,
			'target_table' => 'g',
			'target_no'    => $params['post_no'],
			'parent_no'    => $params['parent_no'],
			'comment'      => $params['comment'],
		);

		// validate
		$this->ci->group_model->set_table('user_group_post');
		if ($params['no'] == 0) {
			$group = $this->ci->group_model->get(array('user_group_post_no' =>$params['post_no'] ), 'user_group_no');
			if($group){
				$user_join_validate = $this->user_join_validate($user_no, $group->user_group_no);
				if($user_join_validate['is_agree'] === 0){
					$this->error_message = '그룹에 가입이 되어 있지 않아 글을 쓸 수 없습니다.';
					return false;
				}
			}
			else{
				$this->error_message = '글정보가 존재하지 않습니다.';
				return false;
			}
		}

		$this->ci->group_model->set_table('post_comment');
		if ($params['no'] == 0) {
			$comment_no = $this->ci->group_model->insert($insert_data);
			if($params['parent_no'] < 1){
				$this->ci->group_model->update(array('parent_no' => $comment_no), array('post_comment_no' => $comment_no));
			}

			$this->ci->group_model->set_table('user_group_post');
			$this->ci->db->set('comment_count', 'comment_count+1', FALSE);
			$this->ci->group_model->update(array('last_comment_no' => $comment_no), array('user_group_post_no' => $params['post_no']));
		} else {
			$this->ci->group_model->update($insert_data, array('post_comment_no' => $params['no'], 'user_no' => $user_no));
			$comment_no = $params['no'];
		}

		$ret = array('no' => $comment_no);

		return $ret;
	}

	public function delete_comment_proc($user_no, $comment_no)
	{
		$where_data = array(
			'user_no'         => $user_no,
			'post_comment_no' => $comment_no,
		);

		$this->ci->group_model->set_table('post_comment');
		$data = $this->ci->group_model->get($where_data, 'target_no,parent_no, (select count(*) from post_comment p where p.parent_no=post_comment.parent_no) as parent_count');
		if($data){
			$is_delete = 0;
			if(($data->parent_no == $comment_no && $data->parent_count < 2) || $data->parent_no != $comment_no){
				$this->ci->group_model->set_table('post_comment');
				$this->ci->group_model->delete($where_data);
				$is_delete = 1;
			}
			else{
				$this->ci->group_model->set_table('post_comment');
				$this->ci->group_model->update(array('comment' => '삭제되었습니다.','is_delete' => 1), $where_data);
			}

			$this->ci->group_model->set_table('user_group_post');
			$this->ci->db->set('comment_count', 'comment_count-1', FALSE);
			$this->ci->group_model->update(array(), array('user_group_post_no' => $data->target_no));
		}
		else{
			$this->error_message = '올바른 접근이 아닙니다.';
			return false;
		}

		return $is_delete;
	}

	public function delete_proc($user_no, $group_no)
	{
		$where_data = array(
			'manager_user_no' => $user_no,
			'user_group_no'   => $group_no,
		);

		$this->ci->group_model->set_table('user_group');
		$is_delete = $this->ci->group_model->update(array('is_delete' => 1), $where_data);
		if(!$is_delete){
			$this->error_message = '올바른 접근이 아닙니다.';
			return false;
		}

		return $is_delete;
	}

	public function post_delete_proc($user_no, $post_no)
	{
		$where_data = array(
			'user_no'            => $user_no,
			'user_group_post_no' => $post_no,
		);

		$this->ci->group_model->set_table('user_group_post');
		$data = $this->ci->group_model->get($where_data, 'user_group_no');

		if($data){
			$this->ci->group_model->delete($where_data);

			$this->ci->group_model->set_table('user_group');
			$this->ci->db->set('post_count', 'post_count-1', FALSE);
			$this->ci->group_model->update(array(), array('user_group_no' => $data->user_group_no));

			$where_data = array(
				'target_table' => 'g',
				'target_no'    => $post_no,
			);

			$this->ci->group_model->set_table('post_comment');
			$this->ci->db->set('comment_count', 'comment_count-1', FALSE);
			$this->ci->group_model->delete($where_data);

			$image = $this->image_delete('user_group_post', $post_no);
		}
		else{
			$this->error_message = '올바른 접근이 아닙니다.';
			return false;
		}

		return true;
	}

	public function add_like_proc($user_no, $post_no)
	{
		$insert_data = array(
			'user_no'      => $user_no,
			'target_table' => 'g',
			'target_no'    => $post_no,
		);

		$like_no = $this->ci->group_model->post_like_insert($insert_data);

		$this->ci->group_model->set_table('user_group_post');
		if ($like_no > 0) {
			$this->ci->db->set('like_count', 'like_count+1', FALSE);
			$this->ci->group_model->update(array(), array('user_group_post_no' => $post_no));
		}
		else{
			$this->ci->db->set('like_count', 'like_count-1', FALSE);
			$this->ci->group_model->update(array(), array('user_group_post_no' => $post_no));
			$this->ci->group_model->set_table('post_like');
			$this->ci->group_model->delete($insert_data);
		}

		$ret = array('is_like' => $like_no > 0 ? 1 : 0);

		return $ret;
	}

	public function post_lists($params, $user_no = 0){
		$searchs = array();

		$user_join_validate = $this->user_join_validate($user_no, $params['user_group_no']);
		if(($user_join_validate['is_agree'] === 0 && $user_join_validate['is_private'] === 1)){
			return array();
		}

		if(!isset($params['page'])){
			$params['page'] = 1;
		}

		if(!isset($params['per_page'])){
			$params['per_page'] = 1;
		}

		if(isset($params['search']) &&  $params['search']!= ''){
			$searchs['like']['or'][]  = array(
				'group_name' => $params['search'], 'group_desc' => $params['search'],
			);
		}
		$searchs['and']['user_group_no'] = $params['user_group_no'];
		$searchs['and']['u.community_setting_private'] = 0;

		$data = $this->ci->group_model->post_lists($params['page'], $params['per_page'], $searchs, array('user_no' => $user_no));

		$this->ci->group_model->set_table('post_comment');
		$searchs = array();
		$searchs['and']['target_table'] = 'g';
		foreach ($data['list'] as $key => $item){
			$searchs['and']['target_no'] = $item->post_no;
			// todo 해당건 처리 빠르게
			$data['list'][$key]->comments = $this->ci->group_model->comment_lists(1, 100, $searchs, array('user_no' => $user_no));
			$data['list'][$key]->images = $this->ci->group_model->image($item->post_no, 'user_group_post');
		}
		return $data;
	}

	public function comment_lists($params, $user_no){
		$searchs = array();
		$searchs['and']['target_table'] = 'g';
		$searchs['and']['target_no'] = $params['post_no'];
		$data = $this->ci->group_model->comment_lists(1, 100, $searchs, array('user_no' => $user_no));

		return $data;
	}

	public function user_join_validate($user_no, $group_no){
		$data = $this->ci->group_model->user_group_join_check($user_no, $group_no);

		return (array)$data;
	}
}
