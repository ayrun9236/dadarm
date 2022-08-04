<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Service_post extends Service_common
{
	protected $ci;

	public function __construct()
	{
		$this->ci =& get_instance();
		$this->ci->load->model('service/post_model');
	}

	public function add_proc($user_no, $params)
	{
		$insert_data = array(
			'user_no'      => $user_no,
			'view_type_no' => $params['view_type_no'],
			'title'        => $params['title'],
			'contents'     => $params['contents'],
		);

		if($params['board_type'] === 0 || $params['board_type'] =='undefined'){
			$params['board_type'] = 'FREE';
		}

		// validate
		if(isset($params['child_no']) && $params['child_no'] > 0){
			if($this->user_child_validate($user_no, $params['child_no'])){
				$insert_data['child_no'] = $params['child_no'];
			}
		}

		$params['board_type'] = strtoupper($params['board_type']);
		if($params['board_type'] == 'CHILD_PRAISE'){
			$params['board_type'] = 'PROFILE';
			$params['board_head_type_no'] = $this->get_codes(array('parent_code' => 'USER_PROFILE_BOARD_TYPE', 'code' => 'THANKYOU-NOTE'))[0]->no;
		}
		elseif($params['board_type'] == 'PROMISE'){
			$params['board_type'] = 'PROFILE';
			$params['board_head_type_no'] = $this->get_codes(array('parent_code' => 'USER_PROFILE_BOARD_TYPE', 'code' => 'PROMISE'))[0]->no;
		}

		if($params['board_type'] == 'PROFILE'){
			$insert_data['profile_user_no'] = $params['profile_user_no'];
		}

		if(isset($params['board_head_type_no'])){
			$insert_data['board_head_type_no'] = $params['board_head_type_no'];
		}

		if(isset($params['target_table'])){
			$insert_data['target_table'] = $params['target_table'];
		}

		if(isset($params['happen_date'])){
			$insert_data['happen_date'] = $params['happen_date'];
		}

		if(isset($params['target_no'])){
			$insert_data['target_no'] = $params['target_no'];
		}

		if(isset($params['board_head_sub_type'])){
			$insert_data['board_head_sub_type'] = $params['board_head_sub_type'];
		}

		if(isset($params['add_data'])){
			$insert_data['add_data'] = json_encode($params['add_data'], JSON_UNESCAPED_UNICODE);
		}

		if(isset($params['etc'])){
			$insert_data['etc_info'] = json_encode($params['etc'], JSON_UNESCAPED_UNICODE);
		}

		$this->ci->post_model->set_table('user_board');
		if ($params['post_no'] == 0) {
			$insert_data['board_type'] = $this->get_codes(array('parent_code' => 'USER_BOARD_TYPE', 'code' => $params['board_type']))[0]->no;
			$board_no = $this->ci->post_model->insert($insert_data);
		} else {

			if(isset($params['end_date'])){
				$insert_data['end_date'] = $params['end_date'];
				$insert_data['is_end'] = 1;
			}

			$this->ci->post_model->update($insert_data, array('user_board_no' => $params['post_no'], 'user_no' => $user_no));
			$board_no = $params['post_no'];
		}

		if(isset($params['delete_image'])) {
			$this->ci->load->library('common/image_upload');
			if(!is_array($params['delete_image'])){
				$params['delete_image'] = explode(',',$params['delete_image']);
			}
			foreach ($params['delete_image'] as $item)
			$this->ci->image_upload->delete('user_board', $board_no, $item);
		}

		$ret = array('post_no' => $board_no, 'images' => array());
		if ($board_no > 0 && (count($_FILES) > 0 || (isset($params['image']) && count($params['image']) > 0))) {
			$image = $this->image_save('user_board', $board_no, 'add');
			if($image === false){
				$this->error_message = '첨부파일 업로드 시 오류가 발생했습니다.';
				return false;
			}

			$ret['images'] = $this->ci->post_model->image($board_no, 'user_board');
		}

		return $ret;
	}

	public function add_user_recommend_proc($user_no, $params)
	{
		$insert_data = array(
			'user_no'    => $user_no,
			'sub_type' => $params['board_head_type_no'],
			'title'      => $params['title'],
			'contents'   => $params['contents'],
		);

		// todo 센터리뷰
		if($params['board_head_type_no'] == 444){
			$insert_data['add_data'] = json_encode(array('score' => $params['add_data'], 'center_no' => $params['center_no']), JSON_UNESCAPED_UNICODE);
			$insert_data['user_ip'] = $this->ci->input->ip_address();
			$insert_data['etc_data'] = $params['center_no'];
		}

		$this->ci->post_model->set_table('user_from_data');
		if ($params['post_no'] == 0) {
			$board_no = $this->ci->post_model->insert($insert_data);
			if($board_no > 0 && $params['board_head_type_no'] == 444) {
				$this->ci->post_model->set_table('center');
				$this->ci->db->set('review_count', 'review_count+1', FALSE);
				$this->ci->post_model->update(array(), array('center_no' => $params['center_no']));
			}
		}

		$ret = array('board_no' => $board_no, 'img' => '');
		if ($board_no > 0 && count($_FILES) > 0) {
			$this->ci->load->library('common/image_upload');
			$upload_result = $this->ci->image_upload->upload('user_from_data', $board_no, 'image', 'new');
			if ($upload_result === false) {
				$this->error_message = '첨부파일 업로드 시 오류가 발생했습니다.';
				return false;
			}
		}

		return $ret;
	}

	public function selftest_create($user_no, $params){
		$selftest_result = $this->selftest_result($params);

		if($user_no > 0){
			$insert_data = array(
				'user_no'  => $user_no,
				'child_no'     => 0,
				'view_type_no' => $params['view_type_no'],
				'title' => $params['title'],
				'content' => $selftest_result['text'],
				'board_head_type_no' => USER_BOARD_TYPE['SELFTEST'],
				'board_head_sub_type' => $params['test_type'],
				'add_data' => array (
					'score' => $selftest_result['score'],
					'answers' => $params['selectedAnswers'],
					'origin_board_no' => $params['board_no'],
				)
			);

			$insert_data['add_data'] = json_encode($insert_data['add_data'], JSON_UNESCAPED_UNICODE);

			if($user_no > 0 && isset($params['child_no']) && $params['child_no'] > 0){
				$this->ci->post_model->set_table('family');
				$data_exists = $this->ci->post_model->get(array('user_no' => $user_no, 'family_no' => $params['child_no']), 'family_no');
				if($data_exists){
					$insert_data['child_no'] = $params['child_no'];
				}
			}

			$this->ci->post_model->set_table('user_board');
			$board_no = $this->ci->post_model->insert($insert_data);
			if(!$board_no){
				$this->error_message = '자가진단 저장 중 오류가 발생했습니다.';
				return false;
			}

			$selftest_result['board_no'] = $board_no;
		}

		return  $selftest_result;

	}

	public function selftest_result($params){

		if($params['test_type'] == "AUTISM"){
			$total_score = 0;
			foreach ($params['selectedAnswers'] as $value){
				$value = explode('-',$value);
				$total_score += $value[1]*1;
			}

			$progress = array(
				0 => array('class' => 'primary', 'score' => 0, 'rate' => 0, 'max' => 2),
				1 => array('class' => 'warning', 'score' => 0, 'rate' => 0, 'max' => 4),
				2 => array('class' => 'danger', 'score' => 0, 'rate' => 0, 'max' => 17),
			);
			if($total_score<3){
				$text = '미해당 => 총23번 문항 중 '.$total_score.'개 해당됩니다. 후속 조치가 필요하지 않습니다. 어린이가 24개월 미만인 경우 24개월(또는 3개월 경과 후)에 다시 선별합니다. 발달 체크를 계속하십시오.';
				$progress[0]['score'] = $total_score;
				$progress[0]['rate'] = 4.4*$total_score;
			}
			elseif($total_score >=7){
				$progress[0]['score'] = 2;
				$progress[0]['rate'] = 9;
				$progress[1]['score'] = 4;
				$progress[1]['rate'] = 26;
				$progress[2]['score'] = $total_score-6;
				$progress[2]['rate'] = 4.4*($total_score-6);
				$text = '해당 => 총23번 문항 중 '.$total_score.'개 해당됩니다. M-CHAT/F는 위험을 평가하는 데 중요합니다. 아동이 계속해서 3점 이상을 받으면 즉시 임상 평가를 의뢰하고 조기 개입 서비스에 대한 적격성을 결정하십시오. Follow-Up 점수가 2점인 경우 아이가 추천을 받아야 할 수 있으므로 주의 깊게 모니터하십시오.';
			}
			else{
				$progress[0]['score'] = 2;
				$progress[0]['rate'] = 9;
				$progress[1]['score'] = $total_score-2;
				$progress[1]['rate'] = 4.4*($total_score-2);
				$text = '경계 => 총23번 문항 중 '.$total_score.'개 해당됩니다. 아동이 ASD 또는 기타 발달 지연의 위험이 있습니다. M-CHAT Follow-Up을 완료하지 않고 즉시 참조할 수 있습니다.';
			}

			$ret = array('comment' => $text, 'data' => array(array('score' => $total_score, 'text' => $text, 'progress' => $progress)));
		}
		elseif($params['test_type'] == "ADHD") {
			$answers_data = array();
			foreach ($params['selectedAnswers'] as $value){
				$value = explode('-',$value);
				$answers_data[] = $value;
			}

			$this->ci->load->library('service/service_board');
			$test_data = $this->ci->service_board->selftest_detail($params['board_no'], 1);

			//분류별 점수 산정
			$result_data = array();
			foreach ($test_data['question'] as $question){
				foreach ($answers_data as $answers){
					if($answers[0] == $question->selftest_question_no){
						foreach ($test_data['result'] as $key => $result){
							if($result->section == $question->section){
								if(!isset($test_data['result'][$key]->user)){
									$test_data['result'][$key] = (object)array_merge((array)$test_data['result'][$key], ['user' => 0]);
								}

								$test_data['result'][$key]->user += $answers[1] *1;
								break;
							}
						}
						break;
					}
				}
			}

			$colors = array('soft-success','soft-danger');
			$add_answer = '';
			$ret = array('comment' => '', 'data' => array(), 'add_comment' => $add_answer);

			foreach ($test_data['result'] as $result){
				$progress = array();
				$text = '';
				$sub_sum = 0;
				if($result->section == '공용'){
					$result->section = '';
				}

				foreach ($result->result_range as $key => $data){
					$rate = 50;
					$color = $colors[$key];
					if($data->range[0] <= $result->user && $data->range[1] >= $result->user){
						$text .= ($result->section == '' ? '' : $result->section .'는(은) '). ($result->use_score_question == 'Y' ? ' 총 '.$result->question_count.'점 중 ' : '').$result->user.'점으로 '.$data->title."\r\n";
						$ret['comment'] .= $text;
						$rate = ($result->user-$data->range[0])/($data->range[1]-$data->range[0])*25;
						$rate = 40;
						$color = str_replace('soft-','', $color);
					}

					$sub_sum += $data->range[1];
					$progress[] = array('class' => $color, 'score' => $result->user, 'title' => $data->title, 'rate' => $rate, 'max' => $data->range[1]);
				}

				$ret['data'][] = array('score' => $result->user, 'text' => $text, 'section' => $result->section, 'progress' => $progress, 'use_score_question' => $result->use_score_question, 'question_count' => $result->question_count);
			}
		}else{
//		elseif($params['test_type'] == "K-DST"){
			$answers_data = array();
			foreach ($params['selectedAnswers'] as $value){
				$value = explode('-',$value);
				$answers_data[] = $value;
			}

			$this->ci->load->library('service/service_board');
			$test_data = $this->ci->service_board->selftest_detail($params['board_no'], 1);

			//분류별 점수 산정
			$result_data = array();
			foreach ($test_data['question'] as $question){
				foreach ($answers_data as $answers){
					if($answers[0] == $question->selftest_question_no){
						foreach ($test_data['result'] as $key => $result){
							if($result->section == $question->section){
								if(!isset($test_data['result'][$key]->user)){
									$test_data['result'][$key] = (object)array_merge((array)$test_data['result'][$key], ['user' => 0]);
								}

								$test_data['result'][$key]->user += $answers[1] *1;
								break;
							}
						}
						break;
					}
				}
			}

			//추가질문관련
			$add_answer = '';
			foreach ($answers_data as $answers){
				if($answers[2] == 's' && $answers[1] == 1){
					$add_answer = "추가 질문에 체크로 각 영역 별 총점과는 별개로 심화평가와 전문가의 진찰을 권유";
				}
			}

			$colors = array('danger','warning','success','primary');
			$ret = array('comment' => '', 'data' => array(), 'add_comment' => $add_answer);
			foreach ($test_data['result'] as $result){
				$progress = array();
				$text = '';
				$sub_sum = 0;
				foreach ($result->result_range as $key => $data){
					$rate = 25;
					$color = 'soft-'.$colors[$key];
					if($data->range[0] <= $result->user && $data->range[1] >= $result->user){
						$text .= $result->section .'는(은) '.$result->user.'으로 '.$data->title."\r\n";
						$ret['comment'] .= $text;
						$rate = ($result->user-$data->range[0] )/($data->range[1]-$data->range[0])*25;
						$rate = 25;
						$color = str_replace('soft-','', $color);
					}

					$sub_sum += $data->range[1];
					$progress[] = array('class' => $color, 'score' => $result->user, 'title' => $data->title, 'rate' => $rate, 'max' => $data->range[1]);
				}

				$ret['data'][] = array('score' => $result->user, 'text' => $text, 'section' => $result->section, 'progress' => $progress);
			}

		}

		return $ret;
	}

	public function lists($params, $user_no = 0){
		$searchs = array();

		if (isset($params['type']) && $params['type']!= '') {
			$param_type = $params['type'];
			if($param_type == 'HOME'){
				$_codes = $this->get_codes(array('parent_code' => 'USER_BOARD_TYPE'));
				$_in_code = array();
				foreach ($_codes as $value){
					if($value->code != 'PROFILE'){
						$_in_code[] = $value->no;
					}
				}

				$searchs['and_in']['board_type'] = $_in_code;
			}
			else{
				if($param_type == 'FRIEND') {
					$param_type = 'PROFILE';

					// todo 코드화
					$searchs['and_in']['view_type_no'] = array(99);
					$this->ci->load->library('service/service_friend');
					$is_friend = $this->ci->service_friend->is_friend($params['friend_no'], $params['user_no']);
					if($is_friend === 1){
						$searchs['and_in']['view_type_no'][] = 98;
						$searchs['and_in']['view_type_no'][] = 426;
					}

					if($is_friend === 2){
						$searchs['and_in']['view_type_no'][] = 425;
						$searchs['and_in']['view_type_no'][] = 426;
					}

					$params['user_no'] = $params['friend_no'];
				}

				$params['type'] = $this->get_codes(array('parent_code' => 'USER_BOARD_TYPE', 'code' => $param_type))[0]->no;
				$searchs['and']['board_type'] = $params['type'];
			}
		}

		if (isset($params['title']) && $params['title']!= '') $searchs['like']['and']['title'] = $params['title'];
		if (isset($params['sdate']) && $params['sdate'] && $params['edate']) {
			$searchs['between'][] = array(
				'insert_dt' => array($params['sdate'], $params['edate']),
			);
		}

		if(isset($params['sub_type']) &&  $params['sub_type']!= ''){
			if(is_array($params['sub_type'])){
				$searchs['and_in']['board_head_type_no'] = $params['sub_type'];
			}
			else{
				$param_type = strtoupper($params['sub_type']);

				//감사일기의 경우 공개설정된 것만
				if($params['sub_type'] == 'THANKYOU-NOTE' && $params['user_no'] == 0){
					// todo 코드화
					$searchs['and_in']['view_type_no'] = array(99);
				}

				$params['sub_type'] = $this->get_codes(array('parent_code' => 'USER_PROFILE_BOARD_TYPE', 'code' => $param_type))[0]->no;
				$searchs['and']['board_head_type_no'] = $params['sub_type'];
			}
		}

		if(isset($params['search'])){
			$searchs['like']['or'][]  = array(
				'title' => $params['search'], 'contents' => $params['search'],
			);
		}

		if(isset($params['user_no']) && $params['user_no'] > 0){
			$searchs['and']['b.profile_user_no'] = $params['user_no'];
		}

		if(isset($params['post_no']) && $params['post_no'] > 0){
			$searchs['and']['user_board_no'] = $params['post_no'];
		}

		if(isset($params['is_private_check']) && $params['is_private_check'] > 0){
			$searchs['and']['u.community_setting_private'] = '0';
		}

		$data = $this->ci->post_model->lists($params['page'], $params['per_page'], $searchs, array('user_no' => $user_no));
		$this->ci->post_model->set_table('post_comment');
		$searchs = array();
		$searchs['and']['target_table'] = 'p';
		foreach ($data['list'] as $key => $item){
			$data['list'][$key]->add_data = json_decode($item->add_data);
			$data['list'][$key]->etc = json_decode($item->etc_info);
			unset($data['list'][$key]->etc_info);
			// todo $searchs['and']['u.community_setting_private'] = '0';
			$searchs['and']['target_no'] = $item->post_no;
			// todo 해당건 처리 빠르게
			$data['list'][$key]->comments = $this->ci->post_model->comment_lists(1, 100, $searchs, array('user_no' => $params['user_no']));
			$data['list'][$key]->images = $this->ci->post_model->image($item->post_no, 'user_board');
		}

		return $data;
	}

	public function comment_lists($params, $user_no){
		$searchs = array();
		$searchs['and']['target_table'] = 'p';
		$searchs['and']['target_no'] = $params['post_no'];
		$data = $this->ci->post_model->comment_lists(1, 100, $searchs, array('user_no' => $user_no));

		return $data;
	}

	public function add_like_proc($user_no, $post_no)
	{
		$insert_data = array(
			'user_no'      => $user_no,
			'target_table' => 'p',
			'target_no'    => $post_no,
		);

		$like_no = $this->ci->post_model->post_like_insert($insert_data);

		$this->ci->post_model->set_table('user_board');
		if ($like_no > 0) {
			$this->ci->db->set('like_count', 'like_count+1', FALSE);
			$this->ci->post_model->update(array(), array('user_board_no' => $post_no));
		}
		else{
			$this->ci->db->set('like_count', 'like_count-1', FALSE);
			$this->ci->post_model->update(array(), array('user_board_no' => $post_no));
			$this->ci->post_model->set_table('post_like');
			$this->ci->post_model->delete($insert_data);
		}

		$ret = array('is_like' => $like_no > 0 ? 1 : 0);

		return $ret;
	}

	public function add_comment_proc($user_no, $params)
	{
		$insert_data = array(
			'user_no'      => $user_no,
			'target_table' => 'p',
			'target_no'    => $params['post_no'],
			'parent_no'    => $params['parent_no'],
			'comment'      => $params['comment'],
		);

		if($user_no == 0){
			// 공유된 글인지 체크
			$this->ci->post_model->set_table('user_board');
			$data = $this->ci->post_model->get(array('user_board_no' => $params['post_no']), 'is_share');
			if($data && $data->is_share === 0){
				$this->error_message = '올바른 접근이 아닙니다.';
				return false;
			}

			$insert_data['guest_name'] = $params['name'];
		}

		$this->ci->post_model->set_table('post_comment');
		$comment_no = $this->ci->post_model->insert($insert_data);
		if($params['parent_no'] < 1){
			$this->ci->post_model->update(array('parent_no' => $comment_no), array('post_comment_no' => $comment_no));
			$this->ci->post_model->set_table('user_board');
			$this->ci->db->set('comment_count', 'comment_count+1', FALSE);
			$this->ci->post_model->update(array(), array('user_board_no' => $params['post_no']));
		}

		//푸시
		$this->push_check('comment', array('target_table' => 'user_board', 'target_no' => $params['post_no'], 'comment_parent_no' => $params['parent_no'], 'user_no' => $user_no));
		$ret = array('no' => $comment_no);

		return $ret;
	}

	public function delete_comment_proc($user_no, $comment_no)
	{
		$where_data = array(
			'user_no'         => $user_no,
			'post_comment_no' => $comment_no,
		);

		$this->ci->post_model->set_table('post_comment');
		$data = $this->ci->post_model->get($where_data, 'target_no,parent_no, (select count(*) from post_comment p where p.parent_no=post_comment.parent_no) as parent_count');
		if($data){
			$is_delete = 0;
			if(($data->parent_no == $comment_no && $data->parent_count < 2) || $data->parent_no != $comment_no){
				$this->ci->post_model->set_table('post_comment');
				$this->ci->post_model->delete($where_data);
				$is_delete = 1;
			}
			else{
				$this->ci->post_model->set_table('post_comment');
				$this->ci->post_model->update(array('comment' => '삭제되었습니다.','is_delete' => 1), $where_data);
			}
		}
		else{
			$this->error_message = '올바른 접근이 아닙니다.';
			return false;
		}
		return $is_delete;
	}

	public function center_review_delete($user_no, $review_no)
	{
		$where_data = array(
			'user_no'  => $user_no,
			'board_no' => $review_no,
		);

		$this->ci->post_model->set_table('user_from_data');
		$data = $this->ci->post_model->get($where_data, 'etc_data');
		if(!$data){
			$this->error_message = '잘못된 접근입니다.';
			return false;
		}

		$is_delete = $this->ci->post_model->delete($where_data);
		if ($is_delete) {
			$this->ci->post_model->set_table('center');
			$this->ci->db->set('review_count', 'review_count-1', FALSE);
			$this->ci->post_model->update(array(), array('center_no' => $data->etc_data));
		}
		else{
			$this->error_message = '올바른 접근이 아닙니다.';
			return false;
		}
		return $is_delete;
	}

	public function post_delete_proc($user_no, $post_no)
	{
		$where_data = array(
			'user_no'            => $user_no,
			'user_board_no' => $post_no,
		);

		$this->ci->post_model->set_table('user_board');
		$data = $this->ci->post_model->delete($where_data);

		if($data){
			$where_data = array(
				'target_table' => 'p',
				'target_no'    => $post_no,
			);

			$this->ci->post_model->set_table('post_comment');
			$this->ci->post_model->delete($where_data);

			$image = $this->image_delete('user_board', $post_no);
		}
		else{
			$this->error_message = '올바른 접근이 아닙니다.';
			return false;
		}

		return true;
	}

	public function share_link($user_no, $post_no){

		// 비밀번호 생성 규칙
		$this->ci->load->library('common/mcrypt');
		$check_no = rand(10,99).substr((($post_no%3)+($post_no%4)+$post_no),-2);
		$this->ci->post_model->set_table('user_board');
		$data = $this->ci->post_model->get(array('user_board_no' => $post_no), 'surl');
		if(!$data) {
			$this->error_message = '공유할 정보가 존재 하지 않습니다.';
			return false;
		}

		$epost_no = $this->ci->mcrypt->encrypt($post_no);
		$ori_url = APP_URL.'/share/'.$epost_no;
		if(!$data->surl){
			$link = $this->ci->general->bilty_url($ori_url);
			if($link === false){
				$this->error_message = '공유 링크 생성 시 오류가 발생했습니다.';
				return false;
			}

			$this->ci->post_model->update(array('surl' => $link), array('user_board_no' => $post_no));
		}
		else{
			$link = $data->surl;
		}

		return array('link' => $link, 'check_no' => $check_no, 'olink' => $ori_url);
	}

	public function share_check($params){
		$this->ci->load->library('common/mcrypt');
		$post_no = $this->ci->mcrypt->decrypt($params['post_no']);
		$check_no = substr((($post_no%3)+($post_no%4)+$post_no),-2);

		if(strpos($params['user_check_no'], $check_no) === false){
			$this->error_message = '비밀번호가 올바르지 않습니다.';
			return false;
		}

		//공유한 게시물표시
		$this->ci->post_model->set_table('user_board');
		$this->ci->post_model->update(array('is_share' => 1), array('user_board_no' => $post_no));
		$post_data = $this->lists(array('post_no' => $post_no, 'page' => 1, 'per_page' => 1, 'user_no' => 0));
		if(count($post_data['list']) > 0){
			$this->ci->load->library('service/service_friend');
			$post_data['friend_info'] = $this->ci->service_friend->friend_detail($post_data['list'][0]->user_no);
		}

		return $post_data;
	}
}
