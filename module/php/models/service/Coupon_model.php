<?php
require_once (SERVICE_ROOT.'/app/application/models/service/Generic_model.php');
class Coupon_model extends Generic_model
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

		$default_sql = "
SELECT 
   *
FROM
   coupon_master c
WHERE 1=1   
        " . $where_sql;

		$total_sql = 'SELECT COUNT(*) cnt FROM ' . explode("FROM", $default_sql)[1];
		$total_count = $this->db->query($total_sql, $where_array)->row()->cnt;

		$where_array[] = (int)$offset;
		$where_array[] = (int)$limit;
		$data_sql = "
SELECT 
	c.*,
	cd.name as order_type_text,
	cp.name as publish_type_text,
    case when c.stamp_count > 0 then 1 else 0 end is_stamp,    
       i.original_img AS coupon_image
FROM (
$default_sql
ORDER BY c.coupon_master_no DESC LIMIT ?,?) c
LEFT OUTER JOIN code cd ON cd.no = c.order_type
LEFT OUTER JOIN code cp ON cp.no = c.publish_type
LEFT OUTER JOIN image i ON i.target_no = c.coupon_master_no AND i.target_table = 'coupon_master' AND i.sort = 1
ORDER BY c.coupon_master_no DESC 
";
		return array(
			'total_count' => $total_count,
			'list'        => $this->general->db_convert_result($this->db->query($data_sql, $where_array)));
	}

	function detail($board_no){

		$sql = "
SELECT 
	b.*,
    c.name as board_type_text   
FROM
	board b
   	INNER JOIN code c ON c.no = b.board_type
WHERE b.board_no = ? 		
		";

		return $this->general->db_convert_row($this->db->query($sql, array($board_no)));
	}
}