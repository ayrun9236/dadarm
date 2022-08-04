<?php

require_once MODULEPATH . '/models/service/Generic_model.php';
class Group_model extends Generic_model
{
    function __construct() {
        parent::__construct();
    }

	function lists($page, $page_row, $searchs = array())
	{
		$from_table = '';
		$from_field = ',b.is_private as is_group_lock, 0 as is_agree, 0 is_invate';
		if(isset($searchs['user_search_mode'])){
			$from_field = ',case when ugm.is_agree = 1 then 0 else b.is_private end is_group_lock, ifnull(ugm.is_agree,0) as is_agree, ifnull(ugm.is_invate,0) as is_invate';
			$from_table = $searchs['user_search_mode'].' JOIN user_group_member ugm ON ugm.user_group_no=b.user_group_no and ugm.user_no = '.$searchs['user_no'];
		}

		$offset = ($page - 1) * $page_row;
		$limit = $page_row;
		$this->set_searchs($searchs);
		$where_sql = $this->where_sql;
		$where_array = $this->where_array;

		$default_sql = "
SELECT 
   b.group_name, b.group_desc, b.manager_user_no, b.user_group_no, b.member_count,b.post_count,b.is_private".$from_field."
FROM
   user_group b
	".$from_table."
WHERE b.is_delete = 0  
        " . $where_sql;

		$where_array[] = (int)$offset;
		$where_array[] = (int)$limit;
		$data_sql = "
SELECT 
	b.*,
       i.thumb_img AS group_image
FROM (
$default_sql
ORDER BY b.user_group_no DESC LIMIT ?,?) b
LEFT OUTER JOIN image i ON i.target_no = b.user_group_no AND i.target_table = 'user_group' AND i.sort = 1
ORDER BY b.user_group_no DESC 
";
		return array(
			'total_count' => 0,
			'list'        => $this->general->db_convert_result($this->db->query($data_sql, $where_array)));
	}

	function member_list($page, $page_row, $searchs = array(), $add_params = array())
	{
		$offset = ($page - 1) * $page_row;
		$limit = $page_row;
		$this->set_searchs($searchs);
		$where_sql = $this->where_sql;
		$where_array = $this->where_array;

		array_unshift($where_array, $add_params['manager_user_no']);
		$where_array[] = (int)$offset;
		$where_array[] = (int)$limit;
		$data_sql = "
SELECT 
	u.user_name,
        i.thumb_img AS member_image,
       b.user_group_member_no,
       b.is_agree, b.is_invate, case when b.user_no = ? then 1 else 0 end is_manager
FROM (
SELECT 
   *
FROM
   user_group_member b
WHERE 1=1 " . $where_sql."
ORDER BY b.user_group_member_no DESC LIMIT ?,?) b
INNER JOIN user u ON u.user_no = b.user_no
LEFT OUTER JOIN image i ON i.target_no = b.user_no AND i.target_table = 'user' AND i.sort = 1
ORDER BY b.user_group_no DESC 
";
		return $this->general->db_convert_result($this->db->query($data_sql, $where_array));
	}

	function post_lists($page, $page_row, $searchs = array(), $params = array())
	{
		$offset = ($page - 1) * $page_row;
		$limit = $page_row;
		$this->set_searchs($searchs);
		$where_sql = $this->where_sql;
		$where_array = $this->where_array;

		array_unshift($where_array, $params['user_no']);
		$where_array[] = (int)$offset;
		$where_array[] = (int)$limit;
		$where_array[] = (int)$params['user_no'];
		$data_sql = " 
SELECT 
	b.title, b.insert_dt, b.user_group_post_no as post_no ,b.contents,b.like_count,b.comment_count, b.user_no,b.child_no, b.user_group_no,
       ui.thumb_img AS user_image,
       b.user_name,ifnull(pl.user_no,0) as is_like,
       f.name as child_name,
       f.birthday as child_birthday,
       f.gender as child_gender
FROM (
	SELECT
	b.*,u.user_name
	FROM
   user_group_post b
	INNER JOIN user u ON u.user_no = b.user_no

WHERE b.user_no not in (select block_user_no from user_block_list bl where bl.user_no = ?)   
        " . $where_sql."
ORDER BY b.user_group_post_no DESC LIMIT ?,?) b
LEFT OUTER JOIN post_like pl ON pl.target_no = b.user_group_post_no AND pl.target_table = 'g' AND pl.user_no = ?
LEFT OUTER JOIN image ui ON ui.target_no = b.user_no AND ui.target_table = 'user' AND ui.sort = 1
LEFT OUTER JOIN family f ON f.family_no = b.child_no
ORDER BY b.user_group_post_no DESC 
";
		return array(
			'total_count' => 0,
			'list'        => $this->general->db_convert_result($this->db->query($data_sql, $where_array)));
	}

	function user_group_join_check($user_no, $group_no){
		$sql = "
SELECT g.is_private, case when gm.user_no is null then 0 else 1 end as is_join, ifnull(gm.is_agree, 0) as is_agree
FROM
	user_group g 
	LEFT OUTER JOIN user_group_member gm ON gm.user_group_no = g.user_group_no and gm.user_no = ?
WHERE g.user_group_no = ?
		";

		return $this->general->db_convert_row($this->db->query($sql, array($user_no, $group_no)));
	}

	function member_confirm($data) {
		$sql = "
update user_group ug, user_group_member um set um.is_agree = 1 
where ug.user_group_no = um.user_group_no and ug.user_group_no = ? and ug.manager_user_no = ? and um.user_group_member_no = ?
		";
		$this->db->query($sql, array($data['user_group_no'], $data['manager_user_no'], $data['user_member_no']));
		return $this->db->affected_rows();
	}

}