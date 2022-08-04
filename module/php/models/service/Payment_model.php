<?php
require_once (SERVICE_ROOT.'/app/application/models/service/Generic_model.php');
class Payment_model extends Generic_model
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
   p.*,  
   u.user_name,
   o.store_no   
FROM
   `order` o
	INNER JOIN payment p ON p.order_no = o.order_no
	INNER JOIN user u ON u.user_no = o.user_no
WHERE o.is_ready = 0 
        " . $where_sql;

		$total_sql = 'SELECT COUNT(*) cnt FROM ' . explode("FROM", $default_sql)[1];
		$total_count = $this->db->query($total_sql, $where_array)->row()->cnt;

		$where_array[] = $offset;
		$where_array[] = $limit;
		$data_sql = "
SELECT 
	o.*, 
       cs.name as store_text,
       co.name as payment_status_text,
       co.code as payment_status_code,
       cp.name as payment_type_text,
       cp.code as payment_type_code
FROM (
$default_sql
ORDER BY o.order_no DESC LIMIT ?,?) o
INNER JOIN code cs ON cs.no = o.store_no
INNER JOIN code co ON co.no = o.payment_status
INNER JOIN code cp ON cp.no = o.payment_type
ORDER BY o.order_no DESC 
";
		return array(
			'total_count' => $total_count,
			'list'        => $this->general->db_convert_result($this->db->query($data_sql, $where_array)));
	}

}