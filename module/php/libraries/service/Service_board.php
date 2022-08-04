<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Service_board extends Service_common
{
	protected $ci;
	protected $error_code = null;
	protected $error_message = null;

	public function __construct()
	{
		$this->ci =& get_instance();

		$this->ci->load->model('service/board_model');
		$this->ci->load->library('common/image_upload');
	}

	public function create($params)
	{
		$board_no = 0;
		$this->ci->board_model->set_table('board');

		$insert_data = array(
			'board_type' => $params['type'],
			'title' => $params['title'],
			'contents' => $params['contents'],
		);

		if (is_numeric($params['type']) === false) {
			$insert_data['board_type'] = $this->get_codes(array('parent_code' => 'BOARD_TYPE', 'code' => strtoupper($params['type'])))[0]->no;
		}
		else{
			$insert_data['board_type'] = $params['type'];
		}

		if(isset($params['sub_type'])){
			$insert_data['sub_type'] = $params['sub_type'];
		}

		if(isset($params['source'])){
			$insert_data['source'] = $params['source'];
		}

		if(isset($params['sdate'])){
			$insert_data['sdate'] = $params['sdate'];
		}

		if(isset($params['edate'])){
			$insert_data['edate'] = $params['edate'];
		}

		if(isset($params['link'])){
			$insert_data['link'] = $params['link'];
		}

		if(isset($params['is_view'])){
			$insert_data['is_view'] = $params['is_view'];
		}

		if(isset($params['etc_data']) && $params['etc_data']){
			$etc_data = explode("\r\n", $params['etc_data']);
			foreach ($etc_data as $value){
				$_etc_data = explode("|", $value);
				$_etc_data_array = array();
				foreach ($_etc_data as $sub_value){
					$_sub_etc_data = explode(":", $sub_value);
					$_etc_data_array[$_sub_etc_data[0]] = $_sub_etc_data[1];
				}

				$insert_data['etc_data'][] = $_etc_data_array;
			}

			$insert_data['etc_data'] = json_encode($insert_data['etc_data'], JSON_UNESCAPED_UNICODE);
		}

		$board_no = $this->ci->board_model->insert($insert_data);

		$ori_url = APP_URL.'/detail/'.$board_no;
		$link = $this->ci->general->bilty_url($ori_url);
		if($link !== false){
			$this->ci->board_model->update(array('surl' => $link), array('board_no' => $board_no));
		}

		if ($board_no && isset($params['image_temp_no']) && $params['image_temp_no'] > 0) {

			$this->ci->board_model->set_table('image');
			$update_data = array(
				'target_no' => $board_no,
				'is_temp' => '0',
			);
			$where_data = array(
				'target_no'    => $params['image_temp_no'],
				'target_table' => 'board',
			);

			$update_count = $this->ci->board_model->update($update_data, $where_data);
		}

		if ($board_no > 0 && count($_FILES) > 0) {
			if (isset($_FILES['attach_image'])) {
				$upload_result = $this->ci->image_upload->upload('board_main', $board_no, 'attach_image', 'new');
				if ($upload_result === false) {
					$this->error_message = '첨부파일 업로드 시 오류가 발생했습니다.';
					return false;
				}
			}
		}

		return $board_no;
	}


	public function selftest_detail($board_no, $sort = 0, $mode = '')
	{
		$ret = array();
		$this->ci->board_model->set_table('selftest_main');
		$where_data = array(
			'board_no' => $board_no,
		);

		if($sort > 0){
			$where_data['sort'] = $sort;
		}

		$selftest_main = $this->ci->board_model->get($where_data, 'selftest_main_no,answer_data,answer_type_fix,answer_data');

		if (!$selftest_main) {
			$this->error_message = '자가진단 정보 호출 중 오류가 발생했습니다.[0]';
			return false;
		}

		$selftest_main->answer_data = json_decode($selftest_main->answer_data);
		$this->ci->board_model->set_table('selftest_question');
		$selftest_question = $this->ci->board_model->list(array('selftest_main_no' => $selftest_main->selftest_main_no), 'question,section,selftest_question_no,answer_type');

		if (!$selftest_question) {
			$this->error_message = '자가진단 정보 호출 중 오류가 발생했습니다.[1]';
			return false;
		}

		$this->ci->board_model->set_table('selftest_result');
		$selftest_result = $this->ci->board_model->list(array('selftest_main_no' => $selftest_main->selftest_main_no), 'section,result_range,question_count,use_score_question');
		foreach ($selftest_result as $key => $value){
			$selftest_result[$key]->result_range = json_decode($value->result_range);
		}

		return array('main' => $selftest_main, 'question' => $selftest_question, 'result' => $selftest_result);
	}

	public function detail($board_no, $mode = '')
	{
		$ret = array();
		$searchs = array();
		$searchs['and']['board_no'] = $board_no;

		if($mode == 'simple'){
			$data = $this->ci->board_model->lists(1, 1, $searchs);
			if($data['total_count'] > 0){
				$ret = $data['list'][0];
			}
		}
		else{
			$ret = $this->ci->board_model->detail($board_no);
		}

		return $ret;
	}


	public function update($board_no, $params)
	{
		$this->ci->board_model->set_table('board');
		$update_data = array(
			'title' => $params['title'],
			'contents' => $params['contents'],
		);

		if(isset($params['sub_type'])){
			$update_data['sub_type'] = $params['sub_type'];
		}

		if(isset($params['sort'])){
			$update_data['sort'] = $params['sort'];
		}

		if(isset($params['source'])){
			$update_data['source'] = $params['source'];
		}

		if(isset($params['sdate'])){
			$update_data['sdate'] = $params['sdate'];
		}

		if(isset($params['edate'])){
			$update_data['edate'] = $params['edate'];
		}

		if(isset($params['link'])){
			$update_data['link'] = $params['link'];
		}

		if(isset($params['is_view'])){
			$update_data['is_view'] = $params['is_view'];
		}

		if(isset($params['etc_data']) && $params['etc_data']){
			$etc_data = explode("\r\n", $params['etc_data']);
			foreach ($etc_data as $value){
				$_etc_data = explode("|", $value);
				$_etc_data_array = array();
				foreach ($_etc_data as $sub_value){
					$_sub_etc_data = explode(":", $sub_value);
					$_etc_data_array[$_sub_etc_data[0]] = $_sub_etc_data[1];
				}

				$update_data['etc_data'][] = $_etc_data_array;
			}

			$update_data['etc_data'] = json_encode($update_data['etc_data'], JSON_UNESCAPED_UNICODE);
		}

		$is_update = $this->ci->board_model->update($update_data, array('board_no' =>$board_no ));
		if(!$is_update){
			$this->error_message = '수정 중 오류가 발생했습니다.';
			return false;
		}

		if ($board_no > 0 && count($_FILES) > 0) {
			if (isset($_FILES['attach_image'])) {
				$upload_result = $this->ci->image_upload->upload('board_main', $board_no, 'attach_image', 'new');
				if ($upload_result === false) {
					$this->error_message = '첨부파일 업로드 시 오류가 발생했습니다.';
					return false;
				}
			}
		}

		//todo 안쓰는 이미지들 정리
		return $is_update;
	}


	public function delete($board_no)
	{
		$this->ci->board_model->set_table('board');
		$is_delete = $this->ci->board_model->delete(array('board_no' =>$board_no ));

		$this->ci->image_upload->delete('board', $board_no);
		$this->ci->image_upload->delete('banner_image', $board_no);
		$this->ci->image_upload->delete('event_image', $board_no);
		return $is_delete;
	}

	public function selftest_create($params){

		$board_no = 0;

		$insert_data = array(
			'board_no' => $params['board_no'],
			'answer_type' => $params['answer_type'],
			'sort' => $params['sort'],
			'answer_type_fix' => $params['answer_type_fix'],
		);

		$answer_data = $params['answer_data'];
		$answer_data = explode("\r\n", $params['answer_data']);
		foreach ($answer_data as $value){
			$insert_data['answer_data'][] = explode('|', $value);
		}

		$insert_data['answer_data'] = json_encode($insert_data['answer_data'], JSON_UNESCAPED_UNICODE);

		$this->ci->board_model->set_table('selftest_main');

		if(isset($params['selftest_main_no']) && $params['selftest_main_no'] > 0){
			$selftest_no = $params['selftest_main_no'];
			$is_update = $this->ci->board_model->update($insert_data, array('selftest_main_no' => $selftest_no));
			if(!$is_update){
				$this->error_message = '수정 중 오류가 발생했습니다.';
				return false;
			}

			$this->ci->board_model->set_table('selftest_question');
			$question_text = explode("\r\n", $params['question_text']);
			foreach ($question_text as $value){

				$sub_question_text = explode("|", $value);
				if($sub_question_text[1]){
					$insert_data = array(
						'selftest_main_no' => $selftest_no,
						'question' => $sub_question_text[1],
						'section' => '',
						'answer_type' => '',
					);

					$where_data = array(
						'question' => $sub_question_text[1],
						'selftest_main_no' => $selftest_no,
					);

					if(isset($sub_question_text[0])){
						$insert_data['section'] = $sub_question_text[0];
					}

					if(isset($sub_question_text[2])){
						$insert_data['answer_type'] = $sub_question_text[2];
					}

					$this->ci->board_model->update($insert_data, $where_data);
				}
			}

			$this->ci->board_model->set_table('selftest_result');
			$this->ci->board_model->delete(array('selftest_main_no' => $selftest_no));
			$question_text = explode("\r\n", $params['result_text']);
			foreach ($question_text as $value){

				$sub_question_text = explode("||", $value);
				$insert_data = array(
					'selftest_main_no' => $selftest_no,
					'section'          => $sub_question_text[0],
					'result_range' => array()
				);

				if(isset($sub_question_text[2])){
					$question_info = explode("|", $sub_question_text[2]);
					$insert_data['question_count'] = $question_info[0];
					$insert_data['use_score_question'] = $question_info[1];
				}

				$result_text = explode("|", $sub_question_text[1]);
				foreach ($result_text as $sub_value){
					$sub_range = explode(":", $sub_value);
					$insert_data['result_range'][] = array('title' => $sub_range[0],'range' => explode("~", $sub_range[1]));
				}


				$insert_data['result_range'] = json_encode($insert_data['result_range'], JSON_UNESCAPED_UNICODE);
				$this->ci->board_model->insert($insert_data);

			}
		}
		else{
			$selftest_no = $this->ci->board_model->insert($insert_data);

			if (!$selftest_no) {
				$this->error_message = '문항 저장 시 오류가 발생했습니다.';
				return false;
			}

			$this->ci->board_model->set_table('selftest_question');
			$question_text = explode("\r\n", $params['question_text']);
			foreach ($question_text as $value){

				$sub_question_text = explode("|", $value);
				if($sub_question_text[1]) {
					$insert_data = array(
						'selftest_main_no' => $selftest_no,
						'question'         => $sub_question_text[1],
						'section'          => '',
						'answer_type'      => '',
					);

					if (isset($sub_question_text[0])) {
						$insert_data['section'] = $sub_question_text[0];
					}

					if (isset($sub_question_text[2])) {
						$insert_data['answer_type'] = $sub_question_text[2];
					}

					$this->ci->board_model->insert($insert_data);
				}
			}

			$this->ci->board_model->set_table('selftest_result');
			$question_text = explode("\r\n", $params['result_text']);
			foreach ($question_text as $value){

				$sub_question_text = explode("||", $value);
				$insert_data = array(
					'selftest_main_no' => $selftest_no,
					'section'          => $sub_question_text[0],
					'result_range' => array()
				);

				if(isset($sub_question_text[2])){
					$question_info = explode("|", $sub_question_text[2]);
					$insert_data['question_count'] = $question_info[0];
					$insert_data['use_score_question'] = $question_info[1];
				}

				$result_text = explode("|", $sub_question_text[1]);
				foreach ($result_text as $sub_value){
					$sub_range = explode(":", $sub_question_text[1]);
					$insert_data['result_range'][] = array('title' => $sub_range[0],'range' => explode("~", $sub_range[1]));
				}

				$insert_data['result_range'] = json_encode($insert_data['result_range'], JSON_UNESCAPED_UNICODE);
				$this->ci->board_model->insert($insert_data);

			}
		}

		return $selftest_no;
	}

	public function lists($params){
		$searchs = array();
		$add_params = array();
		$add_params['table'] = 'board';

		if (!isset($params['user_no'])) {
			$params['user_no'] = 0;
		}

		if (isset($params['type']) && $params['type']!= '') {
			$param_type = strtoupper($params['type']);
			if($param_type == 'BOARD_ETC_USER'){
				$add_params['table'] = 'user_from_data';
//				$params['type'] = $this->get_codes(array('parent_code' => 'BOARD_ETC_USER', 'code' => $param_type))[0]->no;
//				$searchs['and']['sub_type'] = $params['type'];
			}
			else{
				if($param_type != 'BASIC'){
					$params['type'] = $this->get_codes(array('parent_code' => 'BOARD_TYPE', 'code' => $param_type))[0]->no;
					$searchs['and']['board_type'] = $params['type'];
				}
			}
		}

		if (isset($params['title']) && $params['title']!= '') $searchs['like']['and']['title'] = $params['title'];
		if (isset($params['sdate']) && $params['sdate'] && $params['edate']) {
			$searchs['between'][] = array(
				'insert_dt' => array($params['sdate'], $params['edate']),
			);
		}

		if(isset($params['sub_type']) &&  isset($param_type) && $param_type != 'BOARD_ETC_USER' && $params['sub_type']!= ''){
			$searchs['and_in']['sub_type'] = $params['sub_type'];
		}

		if(isset($params['is_view']) &&  isset($param_type) && $param_type != 'BOARD_ETC_USER'){
			$searchs['and']['is_view'] = 1;
		}

		if(isset($params['search']) &&  $params['search']!= ''){
			$searchs['like']['or'][]  = array(
				'title' => $params['search'], 'contents' => $params['search'],
			);
		}

		if(isset($params['board_no']) &&  $params['board_no'] > 0){
			$searchs['and']['board_no'] = $params['board_no'];
		}

		$data = $this->ci->board_model->lists($params['page'], $params['per_page'], $searchs, $add_params);
		$this->ci->board_model->set_table('post_comment');
		$searchs = array();
		$searchs['and']['target_table'] = 'b';
		foreach($data['list'] as $key => $item){
			$data['list'][$key]->etc_data = (array)json_decode($item->etc_data);
			$searchs['and']['target_no'] = $item->board_no;
			$data['list'][$key]->comments = $this->ci->board_model->comment_lists(1, 100, $searchs, array('user_no' => $params['user_no']));
		}

		return $data;
	}

	public function center_lists($params){
		$searchs = array();

		if (isset($params['type']) && $params['type']!= '') {
			$param_type = strtoupper($params['type']);
			$params['type'] = $this->get_codes(array('parent_code' => 'BOARD_TYPE', 'code' => $param_type))[0]->no;
			$searchs['and']['board_type'] = $params['type'];
		}

		if (isset($params['title']) && $params['title']!= '') $searchs['like']['and']['title'] = $params['title'];
		if (isset($params['sdate']) && $params['sdate'] && $params['edate']) {
			$searchs['between'][] = array(
				'insert_dt' => array($params['sdate'], $params['edate']),
			);
		}

		if(isset($params['sub_type']) &&  $params['sub_type']!= ''){
			$searchs['and_in']['sub_type'] = $params['sub_type'];
		}

		if(isset($params['search']) ){
			$searchs['like']['or'][]  = array(
				'title' => $params['search'], 'address_detail' => $params['search'],
			);
		}

		$add_searchs = array(
			'location_lat' => $params['location_lat'],
			'location_lng' => $params['location_lng'],
		);

		$data = $this->ci->board_model->center_lists($params['page'], $params['per_page'], $searchs, $add_searchs);

		return $data;
	}

	public function center_review($params){
		$searchs = array();

		$searchs['and']['sub_type'] = 444;
		$searchs['and']['etc_data'] = $params['center_no'];

		$data = $this->ci->board_model->center_review($searchs);
		foreach ($data as $key => $item) {
			$data[$key]->add_data = json_decode($item->add_data);
		}
		return $data;
	}

	public function like_create($user_no, $params)
	{
		$insert_data = array(
			'user_no'      => $user_no,
			'target_table' => 'b',
			'target_no'    => $params['board_no'],
		);

		$like_no = $this->ci->board_model->post_like_insert($insert_data);
		$this->ci->board_model->set_table('board');
		if ($like_no > 0) {
			$this->ci->db->set('like_count', 'like_count+1', FALSE);
			$this->ci->board_model->update(array(), array('board_no' => $params['board_no']));
		}
		else{
			$this->ci->db->set('like_count', 'like_count-1', FALSE);
			$this->ci->board_model->update(array(), array('board_no' => $params['board_no']));
			$this->ci->board_model->set_table('post_like');
			$this->ci->board_model->delete($insert_data);
		}

		$ret = array('is_like' => $like_no > 0 ? 1 : 0);

		return $ret;
	}

	public function add_comment_proc($user_no, $params)
	{
		$insert_data = array(
			'user_no'      => $user_no,
			'target_table' => 'b',
			'target_no'    => $params['post_no'],
			'parent_no'    => $params['parent_no'],
			'comment'      => $params['comment'],
		);

		$this->ci->board_model->set_table('post_comment');
		$comment_no = $this->ci->board_model->insert($insert_data);
		if($params['parent_no'] < 1){
			$this->ci->board_model->update(array('parent_no' => $comment_no), array('post_comment_no' => $comment_no));
			$this->ci->board_model->set_table('board');
			$this->ci->db->set('comment_count', 'comment_count+1', FALSE);
			$this->ci->board_model->update(array(), array('board_no' => $params['post_no']));
		}

		$ret = array('no' => $comment_no);

		return $ret;
	}

	public function delete_comment_proc($user_no, $comment_no)
	{
		$where_data = array(
			'user_no'         => $user_no,
			'post_comment_no' => $comment_no,
			'target_table' => 'b'
		);

		$this->ci->board_model->set_table('post_comment');
		$data = $this->ci->board_model->get($where_data, 'target_no');
		if($data) {
			$is_delete = $this->ci->board_model->delete($where_data);
			$this->ci->board_model->set_table('board');
			$this->ci->db->set('comment_count', 'comment_count-1', FALSE);
			$this->ci->board_model->update(array(), array('board_no' => $data->target_no));
		}else{
			$this->error_message = '올바른 접근이 아닙니다.';
			return false;
		}

		return 1;
	}
}
