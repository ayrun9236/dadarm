<?php

/**
 * Created by PhpStorm.
 * User: SIL
 * Date: 2021-02-02 오후 05:40
 */
class User extends MY_Controller
{

    function __construct()
    {
        parent::__construct();
        $this->load->model('admin_user_model');
		$this->load->library('service/service_user');
		$this->load->library('form_validation');
    }


    /**
     * 리스트
     */
    public function index()
    {
		$this->data['user_no'] = $this->input->get('user_no');
    	$this->load->view('view', $this->data);
    }



	/**
	 * 상세정보
	 */
	public function detail($user_no)
	{
		$res = $this->service_user->detail($user_no);

		$ret = $this->json_output(true, '', $res);
		$this->output->set_output(json_encode($ret));
	}



	public function data($mode = ''){

		$this->data['sch_regist_type'] = $this->input->get('regist_type');
		$this->data['sch_name'] = $this->input->get('name');
		$this->data['sch_phone'] = $this->input->get('phone');
		$this->data['sch_login_id'] = $this->input->get('login_id');
		$this->data['per_page'] = (int)$this->input->get('per_page');
		$this->data['page'] = (int)$this->input->get('page');
		$this->data['sch_sdate'] = $this->input->get('regist_sdate');
		$this->data['sch_edate'] = $this->input->get('regist_edate');
		$this->data['sch_user_no'] = $this->input->get('no');

		if ($this->data['page'] == '' OR $this->data['page'] < 1) {
			$this->data['page'] = 1;
		}

		if ($this->data['per_page'] == '' OR $this->data['per_page'] < 1) {
			$this->data['per_page'] = PER_PAGE;
		}

		$searchs = array();
		if ($this->data['sch_regist_type'] != '') $searchs['and']['regist_type'] = $this->data['sch_regist_type'];
		if ($this->data['sch_user_no'] > 0) $searchs['and']['u.user_no'] = $this->data['sch_user_no'];
		if ($this->data['sch_name'] != '') $searchs['like']['and']['user_name'] = $this->data['sch_name'];
		if ($this->data['sch_phone'] != '') $searchs['like']['and']['user_phone'] = $this->data['sch_phone'];
		if ($this->data['sch_login_id'] != '') $searchs['like']['and']['login_id'] = $this->data['sch_login_id'];

		if ($this->data['sch_sdate'] && $this->data['sch_edate']) {
			$searchs['between'][] = array(
				'insert_dt' => array($this->data['sch_sdate'], $this->data['sch_edate']),
			);
		}

		$data = $this->admin_user_model->lists($this->data['page'], $this->data['per_page'], $searchs);

		if($mode == 'excel'){
			return $data;
		}
		else{
			$ret = $this->json_output(true, '', $data);
			$this->output->set_output(json_encode($ret));
		}

	}


	/**
	 * 등록하기
	 */
	public function add()
	{
		$para_validation = array(
			array('field' => 'user_name', 'rules' => 'required', 'label' => '이름'),
			array('field' => 'login_id', 'rules' => 'required', 'label' => '로그인id'),
			array('field' => 'login_password', 'rules' => 'required', 'label' => '비밀번호'),
			array('field' => 'user_phone', 'rules' => 'required|integer', 'label' => '핸드폰'),
		);
		$this->form_validation->set_rules($para_validation);

		if (true !== $this->form_validation->run()) {
			$ret = $this->json_output(false, '필수 입력 사항이 누락되었습니다.' . $this->form_validation->error_string());
			$this->output->set_output(json_encode($ret));
			return;
		}

		$res = $this->input->post();
		$res['regist_type'] = $this->get_codes(array('parent_code' => 'REGIST_TYPE', 'code' => 'EMAIL'))[0]->no;
		$res['email'] = $res['login_id'];

		$this->db->trans_begin();
		$regist_no = $this->service_user->create($res);
		if ($regist_no > 0) {
			$ret = true;
			$message = '가입을 완료 하였습니다.';
			$this->db->trans_commit();

		} else {
			$ret = false;
			$_error = $this->service_user->get_error();
			$message = $_error->message;
			$this->db->trans_rollback();
		}

		$ret = $this->json_output($ret, $message, $regist_no);
		$this->output->set_output(json_encode($ret));

	}

    /**
     * 수정하기
     */
    public function modify()
    {
		$params = array(
			'user_name'        => $this->input->post('user_name'),
			'login_id'    => $this->input->post('login_id'),
			'login_password'       => $this->input->post('login_password'),
			'user_no'     => $this->input->post('user_no'),
			'regist_type'     => $this->input->post('regist_type'),
			'user_phone' => $this->input->post('user_phone'),
			'is_marketing_agree_sms' => $this->input->post('is_marketing_agree_sms'),
			'is_marketing_agree_push' => $this->input->post('is_marketing_agree_push'),
			'is_marketing_agree_email' => $this->input->post('is_marketing_agree_email'),
		);

		$this->db->trans_begin();
		$is_update = $this->service_user->update($params);
		if ($is_update > 0) {
			$ret = true;
			$message = '수정을 완료 하였습니다.';
			$this->db->trans_commit();
		} else {
			$ret = false;
			$_error = $this->service_user->get_error();
			$message = $_error->message;
			$this->db->trans_rollback();
		}

		$ret = $this->json_output($ret, $message);
		$this->output->set_output(json_encode($ret));
    }

	/**
	 * 탈퇴하기
	 */
	public function leave($user_no)
	{
		$this->db->trans_begin();
		$is_delete = $this->service_user->leave($user_no);
		if ($is_delete) {
			$ret = true;
			$message = '탈퇴를 완료 하였습니다.';
			$this->db->trans_commit();
		} else {
			$ret = false;
			$_error = $this->service_user->get_error();
			$message = $_error->message;
			$this->db->trans_rollback();
		}

		$ret = $this->json_output($ret, $message);
		$this->output->set_output(json_encode($ret));
	}


	public function post($mode = ''){
		$this->load->library('service/service_post');
		$params = array(
			'type' => 'PROFILE',
			'sub_type' => '',
		);

		$params['per_page'] = (int)$this->input->get('per_page');
		$params['page'] = (int)$this->input->get('page');
		$params['user_no'] = $this->input->get('user_no');

		$data = $this->service_post->lists($params, $params['user_no']);
		$ret = $this->json_output(true, '', $data);
		$this->output->set_output(json_encode($ret));
	}

	public function excel_down(){

		$down_data = $this->data('excel');

		set_time_limit(0);
		ini_set('memory_limit','-1');
		$this->load->library('common/excelxml');

		$this->excelxml->docAuthor('dadaleum');

		$sheet = $this->excelxml->addSheet('sheet1');

		$format = $this->excelxml->addStyle('StyleHeader');
		$format->fontSize(11);
		$format->fontBold();
		$format->fontFamily('Nanum Gothic');
		$format->bgColor('#dedede');
		$format->fontColor('#333');
		$format->alignHorizontal('Center');
		$format->alignVertical('Center');
		$format->border('ALL','1','#ccc','Continuous');

		$format = $this->excelxml->addStyle('StyleBody');
		$format->alignHorizontal('Center');
		$format->alignWraptext();
		$format->fontSize(10);

		$format = $this->excelxml->addStyle('StyleBody_left');
		$format->alignHorizontal('Left');
		$format->fontSize(10);

		$format = $this->excelxml->addStyle('StyleNumberFormat');
		$format->numberFormat('#,##0_ ');
		$format->alignHorizontal('Right');
		$format->fontSize(10);


		$sheet->writeString(1,1,'No','StyleHeader');
		$sheet->writeString(1,2,'이름','StyleHeader');
		$sheet->writeString(1,3,'가입타입','StyleHeader');
		$sheet->writeString(1,4,'핸드폰','StyleHeader');
		$sheet->writeString(1,5,'로그인ID','StyleHeader');
		$sheet->writeString(1,6,'광고성 정보동의/EMAIL/PUSH','StyleHeader');
		$sheet->writeString(1,7,'가입일','StyleHeader');
		$sheet->writeString(1,8,'탈퇴일','StyleHeader');

		$filename = 'user_list.xls';

		foreach ($down_data['list'] as $key => $value) {
			$_row_num = $key+2;

			$sheet->writeNumber($_row_num,1,$key+1,'StyleBody');
			$sheet->writeString($_row_num,2,$value->user_name,'StyleBody_left');
			$sheet->writeString($_row_num,3,$value->regist_type_text,'StyleBody');
			$sheet->writeString($_row_num,4,$value->user_phone,'StyleBody');
			$sheet->writeString($_row_num,5,$value->login_id,'StyleBody_left');
			$sheet->writeString($_row_num,6,($value->is_marketing_agree_sms ? 'Y':'N') .'/'.($value->is_marketing_agree_email ? 'Y':'N') .'/'.($value->is_marketing_agree_push ? 'Y':'N'),'StyleBody');
			$sheet->writeString($_row_num,7,$value->insert_dt,'StyleBody');
			$sheet->writeString($_row_num,8,$value->leave_dt,'StyleBody');
		}

		$sheet->columnWidth(1,'50');
		$sheet->columnWidth(2,'100');
		$sheet->columnWidth(3,'80');
		$sheet->columnWidth(4,'100');
		$sheet->columnWidth(5,'200');
		$sheet->columnWidth(6,'150');
		$sheet->columnWidth(7,'80');
		$sheet->columnWidth(8,'100');


		$this->excelxml->sendHeaders($filename);
		$this->excelxml->writeData();
	}

}
