<?php
require_once (MODULEPATH.'/models/service/Generic_model.php');
class Admin_model extends Generic_model
{
    function __construct()
    {
        parent::__construct();
    }


    function group_lists($page, $page_row, $searchs = array())
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
   admin_group g
WHERE 1=1 " . $where_sql;

        $total_sql = 'SELECT COUNT(*) cnt FROM ' . explode("FROM", $default_sql)[1];
        $total_count = $this->db->query($total_sql, $where_array)->row()->cnt;

        $where_array[] = $offset;
        $where_array[] = $limit;
        $data_sql = "
SELECT 
	*,
    (SELECT group_concat(admin_menu_no) FROM admin_group_menu_grant gg WHERE gg.admin_group_no = g.no) menus   
FROM (
$default_sql
ORDER BY g.no DESC LIMIT ?,?) g
ORDER BY g.no DESC 
";
        return array(
            'total_count' => $total_count,
            'list'        => $this->general->db_convert_result($this->db->query($data_sql, $where_array)));
    }

    function menu(){
    	$sql = "
SELECT 
    am2.no, 
    CONCAT(am1.name, '->', am2.name) as name,  am2.link
FROM
    admin_menu am1
    INNER JOIN admin_menu am2 ON am2.link LIKE CONCAT(am1.link, '%') AND am2.depth = 2 AND am1.depth = 1
ORDER BY am1.sort ASC, am2.sort ASC;    	
    	";

    	return $this->general->db_convert_result($this->db->query($sql));
	}


	function group_grant_insert($group_no, $menus){
		$sql = "
INSERT INTO admin_group_menu_grant (admin_group_no, admin_menu_no)
SELECT 
    ?, no
FROM
    admin_menu
WHERE no in ?
    	";

		return $this->db->query($sql, array($group_no,$menus));
	}


	function admin_lists($page, $page_row, $searchs = array())
	{

		$offset = ($page - 1) * $page_row;
		$limit = $page_row;
		$this->set_searchs($searchs);
		$where_sql = $this->where_sql;
		$where_array = $this->where_array;

		$default_sql = "
SELECT 
   a.`no`,
a.`admin_group_no`,
a.`login_id`,
a.`is_leave`,
a.`name`,
a.`store_no`,
a.`last_login_dt`,
a.`insert_dt`
       , ag.name as admin_group_name,s.name as store_name
FROM
   admin a
   INNER JOIN admin_group ag ON ag.no = a.admin_group_no
   LEFT OUTER JOIN code s ON s.no = a.store_no
WHERE 1=1 " . $where_sql;

		$total_sql = 'SELECT COUNT(*) cnt FROM ' . explode("FROM", $default_sql)[1];
		$total_count = $this->db->query($total_sql, $where_array)->row()->cnt;

		$where_array[] = $offset;
		$where_array[] = $limit;
		$data_sql = "
SELECT 
	*
FROM (
$default_sql
ORDER BY a.no DESC LIMIT ?,?) a
ORDER BY a.no DESC 
";
		return array(
			'total_count' => $total_count,
			'list'        => $this->general->db_convert_result($this->db->query($data_sql, $where_array)));
	}


}