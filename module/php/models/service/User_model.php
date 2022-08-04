<?php

require_once MODULEPATH . '/models/service/Generic_model.php';
class User_model extends Generic_model
{
    function __construct() {
        parent::__construct();
    }

    /*
     * 회원 가입
     */
    function join($data) {

        $_user = array(
            'user_name' => $data['user_name']
            , 'login_id' => $data['login_id']
            , 'login_password' => $data['login_password']
            , 'regist_type' => $data['regist_type']
            , 'is_leave' => 0
			, 'sns_id' => isset($data['sns_id']) ? $data['sns_id'] : ''
        );

        $this->db->insert("user", $_user);
        $user_no = $this->db->insert_id();

        $_user_detail = array(
            'user_no' => $user_no
            , 'device_id' => isset($data['device_id']) ? $data['device_id'] : ''
            , 'is_marketing_agree' => isset($data['is_marketing_agree']) ? $data['is_marketing_agree'] : 0
        );

        $this->db->insert("user_detail", $_user_detail);
        return $user_no;
    }

	function child_list($user_no, $child_no = 0){

		$bind_data = array($user_no);

		$add_sql = '';
		if($child_no > 0){
			$bind_data['family_no'] = $child_no;
			$add_sql = ' AND f.family_no = ?';
		}


		$sql = "
SELECT 
    f.family_no as no, f.name,f.gender,f.birthday, i.thumb_img as image
FROM
    family f 
    left outer join image i ON i.target_table='family' and i.target_no = f.family_no
WHERE
    f.user_no = ? ".$add_sql."
ORDER BY f.family_no desc
";

		return $this->general->db_convert_result($this->db->query($sql, $bind_data));
	}

	function detail($user_no){

		$sql = "
SELECT 
	login_id,login_password,user_no,user_name as name,u.profile_setting,u.is_supporter_write_mode,is_friend_write_mode,
	IFNULL(ui.thumb_img ,'" . UPLOAD['NO_PROFILE'] . "') AS user_image, c.code as regist_type_code
FROM
	user u
	INNER JOIN code c ON c.no = u.regist_type
   	LEFT OUTER JOIN image ui ON ui.target_no = u.user_no AND ui.target_table = 'user' AND ui.sort = 1
WHERE u.user_no = ? and u.is_leave = 0
ORDER BY u.user_no DESC 		
		";

		return $this->general->db_convert_row($this->db->query($sql, array($user_no)));
	}

	function child_introduce_list($user_no, $introduce_no = 0){

		$bind_data = array($user_no);

		$add_sql = '';
		if($introduce_no > 0){
			$bind_data['user_child_introduce_no'] = $introduce_no;
			$add_sql = ' AND d.user_child_introduce_no = ?';
		}


		$sql = "
SELECT 
    d.title, d.contents, f.name, i.thumb_img as image, d.insert_dt, d.update_dt, f.name as child_name, d.user_child_introduce_no as no
FROM
    user_child_introduce d
    left outer join family f ON f.family_no = d.child_no
    left outer join image i ON i.target_table='family' and i.target_no = f.family_no
WHERE
    d.user_no = ? ".$add_sql."
ORDER BY f.family_no desc, d.user_child_introduce_no desc
";

		return $this->general->db_convert_result($this->db->query($sql, $bind_data));
	}

	function block_list($user_no){

		$bind_data = array($user_no);

		$sql = "
SELECT 
    d.block_user_no, d.insert_dt, u.user_name, i.thumb_img as image
FROM
    user_block_list d
    inner join user u ON u.user_no = d.block_user_no
    left outer join image i ON i.target_table='user' and i.target_no = d.block_user_no
WHERE
    d.user_no = ? 
ORDER BY d.insert_dt desc
";

		return $this->general->db_convert_result($this->db->query($sql, $bind_data));
	}
}