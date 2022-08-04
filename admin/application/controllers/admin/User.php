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
        $this->load->model('admin_model');
		$this->load->library('form_validation');
    }


    /**
     * 리스트
     */
    public function index()
    {
        $this->load->view('view', $this->data);
    }

	/**
	 * 상세정보
	 */
	public function detail($user_no)
	{
		$searchs = array();
		$searchs['and']['a.no'] = $user_no;

		$data = $this->admin_model->admin_lists(1, 1, $searchs);

		$ret = $this->json_output(true, '', $data['list'][0]);
		$this->output->set_output(json_encode($ret));
	}

	public function data(){
		$this->data['sch_store'] = $this->input->get('store');
		$this->data['sch_name'] = $this->input->get('name');
		$this->data['per_page'] = (int)$this->input->get('per_page');
		$this->data['page'] = (int)$this->input->get('page');
		$this->data['sch_sdate'] = $this->input->get('sdate');
		$this->data['sch_edate'] = $this->input->get('edate');

		if ($this->data['page'] == '' OR $this->data['page'] < 1) {
			$this->data['page'] = 1;
		}

		if ($this->data['per_page'] == '' OR $this->data['per_page'] < 1) {
			$this->data['per_page'] = PER_PAGE;
		}

		$searchs = array();
		if ($this->data['sch_store'] != '') $searchs['and']['store_no'] = $this->data['sch_store'];
		if ($this->data['sch_name'] != '') $searchs['like']['and']['name'] = $this->data['sch_name'];

		if ($this->data['sch_sdate'] && $this->data['sch_edate']) {
			$searchs['between'][] = array(
				'insert_dt' => array($this->data['sch_sdate'], $this->data['sch_edate']),
			);
		}

		$data = $this->admin_model->admin_lists($this->data['page'], $this->data['per_page'], $searchs);

		$ret = $this->json_output(true, '', $data);
		$this->output->set_output(json_encode($ret));
	}


	/**
	 * 등록하기
	 */
	public function add()
	{
		$para_validation = array(
			array('field' => 'name', 'rules' => 'required', 'label' => '이름'),
			array('field' => 'login_id', 'rules' => 'required', 'label' => '로그인id'),
			array('field' => 'login_password', 'rules' => 'required', 'label' => '비밀번호'),
			array('field' => 'store_no', 'rules' => 'required|integer', 'label' => '매장'),
			array('field' => 'admin_group_no', 'rules' => 'required|integer', 'label' => '그룹정보'),
		);
		$this->form_validation->set_rules($para_validation);

		if (true !== $this->form_validation->run()) {
			$ret = $this->json_output(false, '필수 입력 사항이 누락되었습니다.' . $this->form_validation->error_string());
			$this->output->set_output(json_encode($ret));
			return;
		}

		$res = $this->input->post();

		$this->db->trans_begin();

		$id_exists = $this->admin_model->get(array('login_id' => $res['login_id']),'no');
		if($id_exists){
			$ret = $this->json_output(false, '아이디가 이미 사용 중입니다.' . $this->form_validation->error_string());
			$this->output->set_output(json_encode($ret));
			return;
		}

		$insert_data = array(
			'name' => $res['name'],
			'login_id' => $res['login_id'],
			'login_password' => $this->general->password_set(trim($res['login_password'])),
			'store_no' => $res['store_no'],
			'admin_group_no' => $res['admin_group_no'],
		);
		$regist_no = $this->admin_model->insert($insert_data);
		if ($regist_no > 0) {
			$ret = true;
			$message = '가입을 완료 하였습니다.';
			$this->db->trans_commit();

		} else {
			$ret = false;
			$message = '가입시 오류가 발생 하였습니다.';
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
		$para_validation = array(
			array('field' => 'no', 'rules' => 'required', 'label' => '고유번호'),
			array('field' => 'name', 'rules' => 'required', 'label' => '이름'),
			array('field' => 'login_id', 'rules' => 'required', 'label' => '로그인id'),
			array('field' => 'store_no', 'rules' => 'required|integer', 'label' => '매장'),
			array('field' => 'admin_group_no', 'rules' => 'required|integer', 'label' => '그룹정보'),
		);
		$this->form_validation->set_rules($para_validation);

		if (true !== $this->form_validation->run()) {
			$ret = $this->json_output(false, '필수 입력 사항이 누락되었습니다.' . $this->form_validation->error_string());
			$this->output->set_output(json_encode($ret));
			return;
		}

		$res = $this->input->post();
		$id_exists = $this->admin_model->get(array('login_id' => $res['login_id']),'no');
		if($id_exists && $id_exists->no != $res['no']){
			$ret = $this->json_output(false, '아이디가 이미 사용 중입니다.' . $this->form_validation->error_string());
			$this->output->set_output(json_encode($ret));
			return;
		}
		$this->db->trans_begin();
		$update_data = array(
			'name' => $res['name'],
			'login_id' => $res['login_id'],
			'store_no' => $res['store_no'],
			'admin_group_no' => $res['admin_group_no'],
			'is_leave' => $res['is_leave'] === false ? 1 : 0,
		);

		if(isset($res['login_password'])){
			$update_data['login_password'] = $this->general->password_set(trim($res['login_password']));
		}

		$is_update = $this->admin_model->update($update_data, array('no' => $res['no']));
		if ($is_update > 0) {
			$ret = true;
			$message = '수정을 완료 하였습니다.';
			$this->db->trans_commit();

		} else {
			$ret = false;
			$message = '수정 시 오류가 발생 하였습니다.';
			$this->db->trans_rollback();
		}

		$ret = $this->json_output($ret, $message, $res['no']);
		$this->output->set_output(json_encode($ret));
    }

	/**
	 * 탈퇴하기
	 */
	public function leave($user_no)
	{
		$this->db->trans_begin();

		$update_data = array(
			'login_id' => '',
			'login_password' => '',
			'is_leave' => 1,
		);

		$is_delete = $this->admin_model->update($update_data, array('no' => $user_no));
		if ($is_delete) {
			$ret = true;
			$message = '퇴사를 완료 하였습니다.';
			$this->db->trans_commit();
		} else {
			$ret = false;
			$message = '퇴사처리 중 오류가 발생 하였습니다.';
			$this->db->trans_rollback();
		}

		$ret = $this->json_output($ret, $message);
		$this->output->set_output(json_encode($ret));
	}


	public function user_password_change(){

		$password = $this->input->post('password');
		if($password){
			$update_data = array(
				'login_password' => $this->general->password_set(trim($password))
			);

			$is_update = $this->admin_model->update($update_data, array('no' => $this->user_info->no));

			if ($is_update > 0) {
				$ret = true;
				$message = '수정을 완료 하였습니다.';				
			} else {
				$ret = false;
				$message = '수정 시 오류가 발생 하였습니다.';				
			}

			$ret = $this->json_output($ret, $message,'');
			$this->output->set_output(json_encode($ret));
		}
		else{
			$this->load->view('view', $this->data);
		}

	}
}
