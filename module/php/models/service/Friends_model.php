<?php

require_once MODULEPATH . '/models/service/Generic_model.php';
class Friends_model extends Generic_model
{
    function __construct() {
        parent::__construct();
    }

	function lists($page, $page_row, $searchs = array())
	{
		$offset = ($page - 1) * $page_row;
		$limit = $page_row;
		$this->set_searchs($searchs);
		$where_sql = $this->where_sql;
		$where_array = $this->where_array;

		$where_array[] = (int)$offset;
		$where_array[] = (int)$limit;
		$data_sql = "
SELECT 
	b.friend_no,b.user_name,case when b.is_agree then '' else b.memo end as memo,b.is_request,b.insert_dt,b.is_support,b.is_push_receive,
       ui.thumb_img AS user_image
FROM (
SELECT 
   f.*,u.user_name
FROM
   user_friends f
	INNER JOIN user u ON u.user_no = f.friend_no 
WHERE 1=1 " . $where_sql ."
ORDER BY f.user_friends_no DESC LIMIT ?,?) b
LEFT OUTER JOIN image ui ON ui.target_no = b.friend_no AND ui.target_table = 'user' AND ui.sort = 1
ORDER BY b.user_friends_no DESC 
";
		return  $this->general->db_convert_result($this->db->query($data_sql, $where_array));
	}

	function friend_insert($user_no, $friend_no){
		$sql = "
INSERT INTO user_friends (user_no, friend_no, is_request)
SELECT 
    user_no,friend_no,1
FROM
    (SELECT ? as user_no, ? as friend_no) d
WHERE
    NOT EXISTS( SELECT 
            1
        FROM
            user_friends u
        WHERE
            u.user_no = d.user_no
            AND u.friend_no = d.friend_no)    	
    	";

		$this->db->query($sql, array($user_no,$friend_no));
		if($this->db->insert_id() < 1){
			return false;
		}

		return true;
	}
}