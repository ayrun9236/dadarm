<?php
require_once (SERVICE_ROOT.'/app/application/models/service/Generic_model.php');
class Product_model extends Generic_model
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
   product p
WHERE 1=1   
        " . $where_sql;

		$total_sql = 'SELECT COUNT(*) cnt FROM ' . explode("FROM", $default_sql)[1];
		$total_count = $this->db->query($total_sql, $where_array)->row()->cnt;

		$where_array[] = $offset;
		$where_array[] = $limit;
		$data_sql = "
SELECT 
	p.*, 
    c.name as product_type_text,
    c.code as product_type_code,
	i.original_img AS product_image,
    0 as is_soldout
FROM (
$default_sql
ORDER BY p.product_no DESC LIMIT ?,?) p
INNER JOIN code c ON c.no = p.product_type
LEFT OUTER JOIN image i ON i.target_no = p.product_no AND i.target_table = 'product' AND i.sort = 1
ORDER BY p.product_no DESC 
";
		return array(
			'total_count' => $total_count,
			'list'        => $this->general->db_convert_result($this->db->query($data_sql, $where_array)));
	}

	function store_lists($page, $page_row, $searchs = array())
	{

		$page_row = (int)$page_row;
		$offset = ($page - 1) * (int)$page_row;
		$limit = $page_row;
		$this->set_searchs($searchs);
		$where_sql = $this->where_sql;
		$where_array = $this->where_array;

		$default_sql = "
SELECT 
   *
FROM
   code c
WHERE parent_no IN (SELECT no FROM code WHERE code = 'STORE' AND parent_no=0)  
        " . $where_sql;

		$total_sql = 'SELECT COUNT(*) cnt FROM ('. $default_sql.') s';
		$total_count = $this->db->query($total_sql, $where_array)->row()->cnt;

		$where_array[] = $offset;
		$where_array[] = $limit;
		$data_sql = "
SELECT 
	c.*
FROM (
$default_sql
ORDER BY c.no DESC LIMIT ?,?) c
ORDER BY c.no DESC 
";
		return array(
			'total_count' => $total_count,
			'list'        => $this->general->db_convert_result($this->db->query($data_sql, $where_array)));
	}

	function store_product_lists($page, $page_row, $store, $searchs = array())
	{

		$offset = ($page - 1) * $page_row;
		$limit = (int)$page_row;
		$this->set_searchs($searchs);
		$where_sql = $this->where_sql;
		$where_array = $this->where_array;
		array_unshift( $where_array, $store);

		$default_sql = "
SELECT 
   p.*, ps.is_soldout, case when ps.product_no is not null then 1 else 0 end as is_view
FROM
   product p
   LEFT OUTER JOIN product_store ps ON ps.product_no = p.product_no AND ps.store_no = ?
WHERE 1=1  " . $where_sql;

		$total_sql = 'SELECT COUNT(*) cnt FROM ' . explode("FROM", $default_sql)[1];
		$total_count = $this->db->query($total_sql, $where_array)->row()->cnt;

		$where_array[] = $offset;
		$where_array[] = $limit;
		$data_sql = "
SELECT 
	p.*, 
    c.name as product_type_text,
    c.code as product_type_code,
	i.original_img AS product_image
FROM (
$default_sql
ORDER BY p.product_sort  LIMIT ?,?) p
INNER JOIN code c ON c.no = p.product_type
LEFT OUTER JOIN image i ON i.target_no = p.product_no AND i.target_table = 'product' AND i.sort = 1
ORDER BY is_view DESC, c.sort, p.product_sort
";
		return array(
			'total_count' => $total_count,
			'list'        => $this->general->db_convert_result($this->db->query($data_sql, $where_array)));
	}


	function store_product_add($store_no, $products){
    	$sql = "
INSERT INTO product_store (store_no, product_no)
SELECT 
    store_no, product_no
FROM
    (SELECT 
        p.product_no, ? AS store_no
    FROM
        product p
    WHERE
        product_no IN ? ) d
WHERE
    NOT EXISTS( SELECT 
            1
        FROM
            product_store ps
        WHERE
            ps.product_no = d.product_no
                AND ps.store_no = d.store_no)    	
    	";

		return $this->db->query($sql, array($store_no, $products));
	}


	function store_product_delete($store_no, $products){
		$sql = "DELETE FROM product_store WHERE store_no = ? AND product_no IN ?";

		return $this->db->query($sql, array($store_no, $products));
	}

	function store_product_soldout($store_no, $products, $mode){
		$sql = "UPDATE product_store SET is_soldout = ? WHERE store_no = ? AND product_no IN ?";

		return $this->db->query($sql, array($mode, $store_no, $products));
	}

	function topping_lists($page, $page_row, $searchs = array())
	{

		$page_row = (int)$page_row;
		$offset = ($page - 1) * (int)$page_row;
		$limit = $page_row;
		$this->set_searchs($searchs);
		$where_sql = $this->where_sql;
		$where_array = $this->where_array;

		$default_sql = "
SELECT 
   *
FROM
   code c
WHERE parent_no IN (SELECT no FROM code WHERE code = 'PRODUCT_TOPPING' AND parent_no=0)  
        " . $where_sql;

		$total_sql = 'SELECT COUNT(*) cnt FROM ('. $default_sql.') s';
		$total_count = $this->db->query($total_sql, $where_array)->row()->cnt;

		$where_array[] = $offset;
		$where_array[] = $limit;
		$data_sql = "
SELECT 
	c.*
FROM (
$default_sql
ORDER BY c.no DESC LIMIT ?,?) c
ORDER BY c.no DESC 
";
		return array(
			'total_count' => $total_count,
			'list'        => $this->general->db_convert_result($this->db->query($data_sql, $where_array)));
	}



	function store_topping_add($store_no, $products){
		$sql = "
INSERT INTO product_topping_store (store_no, topping_no)
SELECT 
    store_no, topping_no
FROM
    (SELECT 
        p.no as topping_no, ? AS store_no
    FROM
        code p
    WHERE
        parent_no = (SELECT 
				no
			FROM
				code
			WHERE
				code = 'PRODUCT_TOPPING' AND parent_no = 0) 
        and no IN ? ) d
WHERE
    NOT EXISTS( SELECT 
            1
        FROM
            product_topping_store ps
        WHERE
            ps.topping_no = d.topping_no
                AND ps.store_no = d.store_no)    	
    	";

		return $this->db->query($sql, array($store_no, $products));
	}


	function store_topping_delete($store_no, $products){
		$sql = "DELETE FROM product_topping_store WHERE store_no = ? AND topping_no IN ?";

		return $this->db->query($sql, array($store_no, $products));
	}

	function store_topping_soldout($store_no, $products, $mode){
		$sql = "UPDATE product_topping_store SET is_soldout = ? WHERE store_no = ? AND topping_no IN ?";

		return $this->db->query($sql, array($mode, $store_no, $products));
	}



	function topping_soldout_add($stores, $topping_no){
		$sql = "
INSERT INTO product_topping_store (store_no, topping_no)
SELECT 
    store_no, topping_no
FROM
    (SELECT 
		no AS store_no, ? AS topping_no
	FROM
		code
	WHERE
		parent_no = (SELECT 
				no
			FROM
				code
			WHERE
				code = 'STORE' AND parent_no = 0) 
        AND no in ?
    ) d
WHERE
    NOT EXISTS( SELECT 
            1
        FROM
            product_topping_store ps
        WHERE
            ps.topping_no = d.topping_no
                AND ps.store_no = d.store_no)    	
    	";

		return $this->db->query($sql, array($topping_no,$stores));
	}


	function topping_soldout_delete($stores, $topping_no){
		$sql = "DELETE FROM product_topping_store WHERE topping_no = ? AND store_no IN (?)";

		return $this->db->query($sql, array($topping_no, $stores));
	}

	function topping_soldout_stores($topping_no){
    	$sql = "

SELECT 
    GROUP_CONCAT(c.name) AS stores
FROM
    product_topping_store t
        INNER JOIN
    code c ON c.no = t.store_no
WHERE
    t.topping_no = ?;
    	";

		return $this->general->db_convert_row($this->db->query($sql, $topping_no));
	}

	function store_topping_lists($page, $page_row, $store, $searchs = array())
	{

		$offset = ($page - 1) * $page_row;
		$limit = (int)$page_row;
		$this->set_searchs($searchs);
		$where_sql = $this->where_sql;
		$where_array = $this->where_array;
		array_unshift( $where_array, $store);

		$default_sql = "
SELECT 
   c.no,c.name,c.code,c.etc_data,c.insert_dt, ps.is_soldout, case when ps.topping_no is not null then 1 else 0 end as is_view
FROM
   code c
   LEFT OUTER JOIN product_topping_store ps ON ps.topping_no = c.no AND ps.store_no = ?
WHERE c.parent_no = (SELECT no FROM code WHERE code = 'PRODUCT_TOPPING' AND parent_no = 0)   " . $where_sql;

		$total_sql = 'SELECT COUNT(*) cnt FROM ('.$default_sql.') d ';
		$total_count = $this->db->query($total_sql, $where_array)->row()->cnt;

		$where_array[] = $offset;
		$where_array[] = $limit;
		$data_sql = "
SELECT 
	c.*
FROM (
$default_sql
ORDER BY c.no DESC LIMIT ?,?) c
ORDER BY c.no DESC
";
		return array(
			'total_count' => $total_count,
			'list'        => $this->general->db_convert_result($this->db->query($data_sql, $where_array)));
	}
}