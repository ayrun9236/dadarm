<?php

require_once MODULEPATH . '/models/service/Generic_model.php';
class Post_model extends Generic_model
{
    function __construct() {
        parent::__construct();
    }

	function lists($page, $page_row, $searchs = array(), $params = array())
	{

		$offset = ($page - 1) * $page_row;
		$limit = $page_row;
		$this->set_searchs($searchs);
		$where_sql = $this->where_sql;
		$where_array = $this->where_array;

		// todo count 없애기, 모드별로 sql 변경하기
		$default_sql = "
SELECT 
   b.*,u.user_name
FROM
   user_board b
	INNER JOIN user u ON u.user_no = b.user_no
WHERE b.user_no not in (select block_user_no from user_block_list bl where bl.user_no = ?) 
        " . $where_sql;

		array_unshift($where_array, $params['user_no']);

		$total_sql = 'SELECT COUNT(*) cnt FROM ' . explode("FROM", $default_sql)[1];
		$total_count = $this->db->query($total_sql, $where_array)->row()->cnt;

		$where_array[] = (int)$offset;
		$where_array[] = (int)$limit;
		$where_array[] = (int)$params['user_no'];

		$data_sql = " 
SELECT 
	b.title, b.insert_dt, b.board_head_type_no,b.contents,b.like_count,b.like_count,b.comment_count,b.user_no,b.user_board_no as post_no,
       b.happen_date,b.board_head_sub_type,b.add_data,b.etc_info,
       c.code as sub_type_code,
       c.name as sub_type_text,
       c.css as sub_type_css,
       ct.name as board_type_text,
       ui.thumb_img AS user_image,
       b.user_name,
       b.is_end,
       b.end_date,
       f.name as child_name,
       f.birthday as child_birthday,
       f.gender as child_gender,
       ifnull(pl.user_no,0) as is_like,
       ifnull(cv.name, '비공개') as view_type
FROM (
$default_sql
ORDER BY b.user_board_no DESC LIMIT ?,?) b
INNER JOIN code ct ON ct.no = b.board_type
LEFT OUTER JOIN code c ON c.no = b.board_head_type_no
LEFT OUTER JOIN code cv ON cv.no = b.view_type_no
LEFT OUTER JOIN family f ON f.family_no = b.child_no
LEFT OUTER JOIN post_like pl ON pl.target_no = b.user_board_no AND pl.target_table = 'p' AND pl.user_no = ?    
LEFT OUTER JOIN image ui ON ui.target_no = b.user_no AND ui.target_table = 'user' AND ui.sort = 1
ORDER BY b.user_board_no DESC 
";
		return array(
			'total_count' => $total_count,
			'list'        => $this->general->db_convert_result($this->db->query($data_sql, $where_array)));
	}
}