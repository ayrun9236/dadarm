<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: SIL
 * Date: 2021-02-01 오전 11:55
 */
class Login extends MY_Controller
{

	function __construct() {
		parent::__construct();

	}

	public function index()
	{
	//	phpinfo();
		$this->data['ref'] = $this->input->get('ref');
		$this->load->view('login/index', $this->data);
	}


	public function check_auth() {
		$id = $this->input->post('id');
		$pwd = $this->input->post('pwd');
		$pwd = $this->general->password_set($pwd);

		if (true !== $this->auth->login($id, $pwd)) {
			$ret = $this->json_output(false, '올바른 계정 정보가 아닙니다!');
		}
		else{
			$ret = $this->json_output(true, '로그인 되었습니다');
		}

		$this->output->set_output(json_encode($ret));
	}

	function logout(){
		$this->auth->logout();
		$this->load->helper('url');
		redirect("/login/", 'refresh');
	}

	function password_edit_proc(){

		$this->load->library('form_validation');
		$this->form_validation->set_rules('password', '비밀번호', 'required');
		if (true !== $this->form_validation->run()) {
			$ret = array('code' => '500', 'message' => '필수 입력 사항이 누락되었습니다.' . $this->form_validation->error_string());
			$this->output->set_output(json_encode($ret));
			return;
		}

		$pwd = $this->input->post('password');
		$pwd = $this->cl->password_set($pwd);
		$this->admin_model->update(array('login_password' => $pwd), array('no' => $this->auth->info()->no));
		$this->output->set_output(json_encode(array('code' => 200)));
	}

}
