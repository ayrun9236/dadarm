<?php
class Board_model extends Generic_model
{
    function __construct() {
        parent::__construct();
    }

	function lists($page, $page_row, $searchs = array(), $add_params = array())
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
   ".$add_params['table']." b
WHERE 1=1
        " . $where_sql;

		$total_sql = 'SELECT COUNT(*) cnt FROM ' . explode("FROM", $default_sql)[1];
		$total_count = $this->db->query($total_sql, $where_array)->row()->cnt;

		$where_array[] = (int)$offset;
		$where_array[] = (int)$limit;

		$select = 'b.is_view,b.link,b.etc_data,b.like_count,b.source,';
		$from = '';
		if($add_params['table'] == 'user_from_data'){
			$select = 'null as etc_data,u.user_no,u.user_name,';
			$from = ' LEFT OUTER JOIN user u ON u.user_no = b.user_no ';
		}

		$data_sql = "
SELECT 
	b.title, b.insert_dt, b.board_no, b.sub_type,b.contents,b.surl, b.comment_count, b.sort,".$select."
       c.code as sub_type_code,
       c.name as sub_type_text,
       c.css as sub_type_css,
       i.thumb_img AS board_image
FROM (
$default_sql
ORDER BY b.sort, b.board_no DESC LIMIT ?,?) b
LEFT OUTER JOIN code c ON c.no = b.sub_type
LEFT OUTER JOIN image i ON i.target_no = b.board_no AND i.target_table = 'board_main' AND i.sort = 1
".$from."
ORDER BY b.sort, b.board_no DESC 
";
		return array(
			'total_count' => $total_count,
			'list'        => $this->general->db_convert_result($this->db->query($data_sql, $where_array)));
	}

	function center_lists($page, $page_row, $searchs = array(), $add_searchs = array())
	{

		$offset = ($page - 1) * $page_row;
		$limit = $page_row;
		$this->set_searchs($searchs);
		$where_sql = $this->where_sql;
		$where_array = $this->where_array;

		$bind_data = array(
			$add_searchs['location_lat'],
			$add_searchs['location_lng'],
			$add_searchs['location_lat'],
		);

		$where_array = array_merge($bind_data, $where_array);

		$where_array[] = (int)$offset;
		$where_array[] = (int)$limit;
		$data_sql = "
SELECT 
       title, address_detail, tel,c.location_lat,c.location_lng, center_no,review_count,
       (6371 * ACOS(COS(RADIANS(?)) * COS(RADIANS(c.location_lat)) * COS(RADIANS(c.location_lng) - RADIANS(?)) + SIN(RADIANS(?)) * SIN(RADIANS(c.location_lat)))) AS distance
FROM center c WHERE 1=1   
        " . $where_sql." ORDER BY distance asc LIMIT ?,?";

		return $this->general->db_convert_result($this->db->query($data_sql, $where_array));
	}

	function center_review($searchs)
	{

		$this->set_searchs($searchs);
		$where_sql = $this->where_sql;
		$where_array = $this->where_array;

		$data_sql = "
SELECT 
       b.title, b.contents, b.add_data, b.user_no,b.board_no as post_no,
       ui.thumb_img AS user_image,
       u.user_name
FROM 
	user_from_data b 
	INNER JOIN user u on u.user_no = b.user_no
	LEFT OUTER JOIN image ui ON ui.target_no = b.user_no AND ui.target_table = 'user' AND ui.sort = 1
WHERE 1=1   
        " . $where_sql." ORDER BY b.board_no desc";

		return $this->general->db_convert_result($this->db->query($data_sql, $where_array));
	}


	function etc_lists($page, $page_row, $mode, $searchs = array())
	{
		$offset = ($page - 1) * $page_row;
		$limit = $page_row;
		$this->set_searchs($searchs);
		$where_sql = $this->where_sql;
		$where_array = $this->where_array;

		if($mode != ''){
			if($mode == 'ing'){
				$where_sql .= " AND curdate() between b.sdate and b.edate";
			}

			if($mode == 'end'){
				$where_sql .= " AND b.edate < curdate()";
			}
		}

		if(isset($searchs['stamp'])){
			//$where_sql .= " AND cm.stamp_count > 0";
		}

		$default_sql = "
SELECT 
   *
FROM
   board b
WHERE 1=1   
        " . $where_sql;

		$total_sql = 'SELECT COUNT(*) cnt FROM ' . explode("FROM", $default_sql)[1];
		$total_count = $this->db->query($total_sql, $where_array)->row()->cnt;

		$where_array[] = (int)$offset;
		$where_array[] = (int)$limit;
		$data_sql = "
SELECT 
	b.title, b.insert_dt, b.board_no, b.sub_type,b.contents,b.sdate, b.edate,
       c.coupon_name,
       si.original_img AS main_image,
       ct.code as type_code, b.link, b.is_view
FROM (
$default_sql
ORDER BY b.board_no DESC LIMIT ?,?) b
INNER JOIN code ct ON ct.no = b.board_type
LEFT OUTER JOIN coupon_master c ON c.coupon_master_no = b.sub_type
LEFT OUTER JOIN image si ON si.target_no = b.board_no AND si.target_table = concat(lower(ct.code),'_image') AND si.sort = 1
ORDER BY b.board_no DESC 
";
		return array(
			'total_count' => $total_count,
			'list'        => $this->general->db_convert_result($this->db->query($data_sql, $where_array)));
	}

	function event_listd($searchs){

		$where_sql = '';

		$sql = "
SELECT 
    cm.coupon_name,
	cm.coupon_master_no,
    cm.use_sdate,
    cm.use_edate,
    cm.order_min_price,
    i.original_img AS coupon_image
FROM
    coupon_master cm
    LEFT OUTER JOIN image i ON i.target_no = cm.coupon_master_no AND i.target_table = 'coupon' AND i.sort = 3
WHERE
    1=1 ". $where_sql ."
ORDER BY cm.coupon_master_no desc
    ";

		return $this->general->db_convert_result($this->db->query($sql));
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

	function like_insert($user_no,$board_no){
		$sql = "
INSERT INTO board_like (user_no,board_no)
SELECT 
    user_no,board_no
FROM
    (SELECT ? as user_no, ? as board_no) d
WHERE
    NOT EXISTS( SELECT 
            1
        FROM
            board_like u
        WHERE
            u.user_no = d.user_no
            AND u.board_no = d.board_no)    	
    	";

		$this->db->query($sql, array($user_no,$board_no));
		if($this->db->insert_id() < 1){
			return false;
		}

		return true;
	}
}