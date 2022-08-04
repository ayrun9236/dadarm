<?php

/**
 * Created by PhpStorm.
 * User: SIL
 * Date: 2021-02-02 오후 05:40
 */
class Group extends MY_Controller
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

	public function menus()
	{
		$res = $this->admin_model->menu();
		$this->output->set_output(json_encode($res));
	}

	public function data(){

		$this->data['per_page'] = (int)$this->input->get('per_page');
		$this->data['page'] = (int)$this->input->get('page');

		if ($this->data['page'] == '' OR $this->data['page'] < 1) {
			$this->data['page'] = 1;
		}

		if ($this->data['per_page'] == '' OR $this->data['per_page'] < 1) {
			$this->data['per_page'] = PER_PAGE;
		}

		$searchs = array();

		$data = $this->admin_model->group_lists($this->data['page'], $this->data['per_page'], $searchs);

		$ret = $this->json_output(true, '', $data);
		$this->output->set_output(json_encode($ret));
	}


	/**
	 * 등록하기
	 */
	public function add()
	{
		$para_validation = array(
			array('field' => 'name', 'rules' => 'required', 'label' => '그룹명'),
			array('field' => 'is_grant_all', 'rules' => 'required', 'label' => '전체권한여부'),
			array('field' => 'menus[]', 'rules' => 'required', 'label' => '권한부여메뉴'),
		);
		$this->form_validation->set_rules($para_validation);

		if (true !== $this->form_validation->run()) {
			$ret = $this->json_output(false, '필수 입력 사항이 누락되었습니다.' . $this->form_validation->error_string());
			$this->output->set_output(json_encode($ret));
			return;
		}

		$res = $this->input->post();
		$is_grant_all = isset($res['is_grant_all']) ? $res['is_grant_all'] : 0;
		$insert_data = array(
			'name' => $res['name'],
			'is_grant_all' => $is_grant_all,
		);

		$this->db->trans_begin();
		$this->admin_model->set_table('admin_group');
		$insert_no = $this->admin_model->insert($insert_data);

		$is_insert = true;
		if($insert_no >0 && isset($res['menus']) && $is_grant_all == 0){
			$is_insert = $this->admin_model->group_grant_insert($insert_no, $res['menus']);
		}

		if ($insert_no > 0 && $is_insert) {
			$ret = true;
			$message = '등록을 완료 하였습니다.';
			$this->db->trans_commit();
		} else {
			$ret = false;
			$message = '등록 중 오류가 발생했습니다.';
			$this->db->trans_rollback();
		}

		$ret = $this->json_output($ret, $message, $insert_no);
		$this->output->set_output(json_encode($ret));

	}

    /**
     * 수정하기
     */
    public function modify()
    {
		$para_validation = array(
			array('field' => 'no', 'rules' => 'required', 'label' => '그룹번호'),
			array('field' => 'name', 'rules' => 'required', 'label' => '그룹명'),
			array('field' => 'is_grant_all', 'rules' => 'required', 'label' => '전체권한여부'),
			array('field' => 'menus[]', 'rules' => 'required', 'label' => '권한부여메뉴'),
		);
		$this->form_validation->set_rules($para_validation);

		if (true !== $this->form_validation->run()) {
			$ret = $this->json_output(false, '필수 입력 사항이 누락되었습니다.' . $this->form_validation->error_string());
			$this->output->set_output(json_encode($ret));
			return;
		}

		$res = $this->input->post();
		$is_grant_all = isset($res['is_grant_all']) ? $res['is_grant_all'] : 0;
		$update_data = array(
			'name' => $res['name'],
			'is_grant_all' => $is_grant_all,
		);

		$this->db->trans_begin();
		$this->admin_model->set_table('admin_group');
		$is_update = $this->admin_model->update($update_data, array('no' => $res['no']));

		$this->admin_model->set_table('admin_group_menu_grant');
		$this->admin_model->delete(array('admin_group_no' => $res['no']));
		$is_insert = true;
		if(isset($res['menus']) && $is_grant_all == 0){
			$is_insert = $this->admin_model->group_grant_insert($res['no'], $res['menus']);
		}

		if ($is_update  && $is_insert) {
			$ret = true;
			$message = '수정을 완료 하였습니다.';
			$this->db->trans_commit();
		} else {
			$ret = false;
			$message = '수정 중 오류가 발생했습니다.';
			$this->db->trans_rollback();
		}

		$ret = $this->json_output($ret, $message, $is_update);
		$this->output->set_output(json_encode($ret));
    }


	/**
	 * 삭제하기
	 */
	public function delete($no)
	{
		$this->db->trans_begin();
		$this->admin_model->set_table('admin_group');
		$is_delete = $this->admin_model->delete(array('no' => $no));

		$this->admin_model->set_table('admin_group_menu_grant');
		$this->admin_model->delete(array('admin_group_no' => $no));

		if ($is_delete) {
			$ret = true;
			$message = '삭제를 완료 하였습니다.';
			$this->db->trans_commit();
		} else {
			$ret = false;
			$message = '삭제중 오류가 발생 하였습니다.';
			$this->db->trans_rollback();
		}

		$ret = $this->json_output($ret, $message);
		$this->output->set_output(json_encode($ret));
	}
}
