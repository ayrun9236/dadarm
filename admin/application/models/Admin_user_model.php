<?php
//todo 변경
require_once (MODULEPATH.'/models/service/User_model.php');
class Admin_user_model extends User_model
{
    function __construct()
    {
        parent::__construct();
    }


    function lists($page, $page_row, $searchs = array())
    {

        $offset = ($page - 1) * $page_row;
        $limit = $page_row;
        $this->set_searchs($searchs);
        $where_sql = $this->where_sql;
        $where_array = $this->where_array;

        $default_sql = "
SELECT 
   u.*,ud.is_marketing_agree,ud.leave_dt,ud.leave_reason,ud.leave_reason_etc, ud.login_count
FROM
   user u
   INNER JOIN user_detail ud ON ud.user_no = u.user_no
WHERE 1=1 " . $where_sql;

        $total_sql = 'SELECT COUNT(*) cnt FROM ' . explode("FROM", $default_sql)[1];
        $total_count = $this->db->query($total_sql, $where_array)->row()->cnt;

        $where_array[] = $offset;
        $where_array[] = $limit;
        $data_sql = "
SELECT 
	u.user_no, u.user_name, u.user_phone, u.login_id, u.leave_dt, c.name as regist_type_text,u.regist_type, DATE_FORMAT(u.insert_dt, '%Y-%m-%d') AS insert_dt,
	u.is_marketing_agree,
    l.name as leave_reason_text,
    u.leave_reason_etc,u.login_count
FROM (
$default_sql
ORDER BY u.user_no DESC LIMIT ?,?) u
INNER JOIN code c ON c.no = u.regist_type
LEFT OUTER JOIN code l ON l.no = u.leave_reason
ORDER BY u.user_no DESC 
";
        return array(
            'total_count' => $total_count,
            'list'        => $this->general->db_convert_result($this->db->query($data_sql, $where_array)));
    }

}