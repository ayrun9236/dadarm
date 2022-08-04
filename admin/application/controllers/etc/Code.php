<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Code extends MY_Controller
{

    function __construct()
    {
        parent::__construct();

        $this->load->model('admin_code_model');
		$this->load->library('form_validation');
		$this->load->library('common/image_upload');
    }


    public function index()
    {
        $this->load->view('view', $this->data);
    }

	public function data(){
		$parent_no = $this->input->get('parent_no');
		if($parent_no == ''){
			$parent_no = 0;
		}

		$data = $this->admin_code_model->lists($parent_no);

		$ret = $this->json_output(true, '', $data);
		$this->output->set_output(json_encode($ret));
	}


    public function sub_codes($search_data)
    {

    	$code_no = $search_data;
        if (is_numeric($code_no) === false) {
            $code_no = 0;
            $code_info = $this->db->get_where('code', array('code' => $search_data, 'is_view' => 1))->row();

            if ($code_info) {
                $code_no = $code_info->no;
            }
        }

        $data = $this->admin_code_model->lists($code_no, ' c.sort ASC ');

        if(strtoupper($search_data) == 'STORE' && $data && $this->user_info->store_no>0){
        	$ret = array();
        	foreach ($data as $item){
        		if($item->no == $this->user_info->store_no){
					$ret[] = $item;
        			break;
				}
			}
		}
        else{
			$ret = $data;
		}

		$ret = $this->json_output(true, '', $ret);
		$this->output->set_output(json_encode($ret));
    }

	/**
	 * 등록하기
	 */
	public function add()
	{
		$para_validation = array(
			array('field' => 'name', 'rules' => 'required', 'label' => '코드명'),
			array('field' => 'code', 'rules' => 'required', 'label' => '코드'),
		);
		$this->form_validation->set_rules($para_validation);

		if (true !== $this->form_validation->run()) {
			$ret = $this->json_output(false, '필수 입력 사항이 누락되었습니다.' . $this->form_validation->error_string());
			$this->output->set_output(json_encode($ret));
			return;
		}

		$res = $this->input->post();

		$this->db->trans_begin();
		$code_no = 0;
		$this->admin_code_model->set_table('code');

		$insert_data = array(
			'code' => strtoupper($res['code']),
			'name' => $res['name'],
			'parent_no' => $res['parent_no'],
			'sort' => $res['sort'],
			'css' => $res['css'],
		);

		$code_no = $this->admin_code_model->insert($insert_data);

		if ($code_no > 0 && count($_FILES) > 0) {
			if (isset($_FILES['code_image'])) {
				$upload_result = $this->image_upload->upload('code', $code_no, 'code_image', 'new');
				if ($upload_result === false) {
					$ret = $this->json_output(false, '첨부파일 업로드 시 오류가 발생했습니다.');
					$this->output->set_output(json_encode($ret));
					return;
				}
			}
		}

		if ($code_no > 0) {
			$ret = true;
			$message = '등록을 완료 하였습니다.';
			$this->db->trans_commit();

		} else {
			$ret = false;
			$message = '등록 시 오류가 발생했습니다.';
			$this->db->trans_rollback();
		}

		$ret = $this->json_output($ret, $message, $code_no);
		$this->output->set_output(json_encode($ret));

	}

	/**
	 * 수정하기
	 */
	public function modify()
	{
		$para_validation = array(
			array('field' => 'name', 'rules' => 'required', 'label' => '코드명'),
			array('field' => 'code', 'rules' => 'required', 'label' => '코드'),
			array('field' => 'no', 'rules' => 'required', 'label' => '코드번호'),
		);
		$this->form_validation->set_rules($para_validation);

		if (true !== $this->form_validation->run()) {
			$ret = $this->json_output(false, '필수 입력 사항이 누락되었습니다.' . $this->form_validation->error_string());
			$this->output->set_output(json_encode($ret));
			return;
		}

		$res = $this->input->post();

		$update_data = array(
			'code' => strtoupper($res['code']),
			'name' => $res['name'],
			'sort' => $res['sort'],
			'css' => $res['css'],
		);

		$this->admin_code_model->set_table('code');
		$is_update = $this->admin_code_model->update($update_data, array('no' => $res['no']));

		if ($is_update && count($_FILES) > 0) {
			if (isset($_FILES['code_image'])) {
				$upload_result = $this->image_upload->upload('code', $res['no'], 'code_image', 'new');
				if ($upload_result === false) {
					$ret = $this->json_output(false, '첨부파일 업로드 시 오류가 발생했습니다.');
					$this->output->set_output(json_encode($ret));
					return;
				}
			}
		}

		if (!$is_update) {
			$ret = false;
			$message = '수정 시 오류가 발생했습니다.';
			$this->db->trans_rollback();
		} else {
			$ret = true;
			$message = '수정을 완료 하였습니다.';
			$this->db->trans_commit();
		}

		$ret = $this->json_output($ret, $message);
		$this->output->set_output(json_encode($ret));
	}

	/**
	 * 삭제하기
	 */
	public function delete($code_no)
	{
		$this->db->trans_begin();
		$this->admin_code_model->set_table('code');
		$is_delete = $this->admin_code_model->delete(array('no' => $code_no));
		$this->image_upload->delete('code', $code_no);
		if ($is_delete) {
			$ret = true;
			$message = '삭제를 완료 하였습니다.';
			$this->db->trans_commit();
		} else {
			$ret = false;
			$message = '삭제 시 오류가 발생했습니다.';
			$this->db->trans_rollback();
		}

		$ret = $this->json_output($ret, $message);
		$this->output->set_output(json_encode($ret));
	}

}
