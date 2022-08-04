<?php
require_once (MODULEPATH.'/models/service/Order_model.php');
class Admin_order_model extends Order_model
{
    function __construct()
    {
        parent::__construct();
    }

    function order_summary($sdate,$edate,$searchs){

		$this->set_searchs($searchs);
		$where_sql = $this->where_sql;
		$where_array = $this->where_array;
		array_unshift($where_array, $sdate);
		array_unshift($where_array, $edate);
		array_unshift($where_array, $sdate);
    	$sql = "
SELECT 
    ddate,
    ifnull(da.order_num,0) as order_num,
    ifnull(da.total_price,0) as total_price
FROM
    (SELECT 
        DATE_ADD(?, INTERVAL no - 1 DAY) AS ddate
    FROM
        `dual`
    WHERE
        no < datediff(?,?)+2) d
        LEFT OUTER JOIN
    (SELECT 
        DATE_FORMAT(insert_dt, '%Y-%m-%d') AS order_date,
            COUNT(*) AS order_num,
            SUM(total_price) AS total_price
    FROM
        `order` o
    WHERE
        1=1 ".$where_sql."
    GROUP BY DATE_FORMAT(insert_dt, '%Y-%m-%d')) da ON da.order_date = d.ddate
ORDER BY d.ddate ASC  	
    	";

		return $this->general->db_convert_result($this->db->query($sql, $where_array));
	}

	function store_summary($sdate,$edate,$searchs){

		$this->set_searchs($searchs);
		$where_sql = $this->where_sql;
		$where_array = $this->where_array;
		array_unshift($where_array, $sdate);
		array_unshift($where_array, $edate);
		array_unshift($where_array, $sdate);
		$sql = "
SELECT 
    ddate,
    c.no as store_no,
    c.name as store_name,
    IFNULL(da.total_price, 0) AS total_price
FROM
    (SELECT 
        DATE_ADD(?, INTERVAL no - 1 DAY) AS ddate
    FROM
        `dual`
    WHERE
        no < DATEDIFF(?, ?) + 2) d
        INNER JOIN
    code c ON parent_no IN (SELECT 
            no
        FROM
            code
        WHERE
            code = 'STORE')
        LEFT OUTER JOIN
    (SELECT 
        DATE_FORMAT(o.insert_dt, '%Y-%m-%d') AS order_date,
            c.no,
            SUM(total_price) AS total_price
    FROM
        code c
    LEFT OUTER JOIN `order` o ON o.store_no = c.no
    WHERE
        parent_no IN (SELECT no FROM code WHERE code = 'STORE') ".$where_sql."
    GROUP BY c.no , DATE_FORMAT(o.insert_dt, '%Y-%m-%d')) da ON da.order_date = d.ddate AND c.no = da.no
ORDER BY store_no, d.ddate ASC	 	
    	";

		return $this->general->db_convert_result($this->db->query($sql, $where_array));
	}


	function chart_summary($sdate,$edate,$searchs){

		$this->set_searchs($searchs);
		$where_sql = $this->where_sql;
		$where_array = $this->where_array;
		array_unshift($where_array, $sdate);
		array_unshift($where_array, $edate);
		array_unshift($where_array, $sdate);
		$sql = "
SELECT 
    d.ddate,
    IFNULL(delivery_total_price, 0) AS delivery_total_price,
    IFNULL(pickup_total_price, 0) AS pickup_total_price
FROM
    (SELECT 
        DATE_ADD(?, INTERVAL no - 1 DAY) AS ddate
    FROM
        `dual`
    WHERE
        no < DATEDIFF(?, ?) + 2) d
        LEFT OUTER JOIN
    (SELECT 
        DATE_FORMAT(o.insert_dt, '%Y-%m-%d') AS order_date,
            SUM(CASE
                WHEN o.order_type = 36 THEN total_price
                ELSE 0
            END) AS delivery_total_price,
            SUM(CASE
                WHEN o.order_type = 37 THEN total_price
                ELSE 0
            END) AS pickup_total_price
    FROM
        `order` o
    WHERE
        o.order_status NOT IN (49 , 54)
            ".$where_sql."
    GROUP BY DATE_FORMAT(o.insert_dt, '%Y-%m-%d')) o ON o.order_date = ddate
ORDER BY d.ddate ASC	 	
    	";

		return $this->general->db_convert_result($this->db->query($sql, $where_array));
	}

	function data_summary($sdate,$edate,$searchs,$view_type){
		$this->set_searchs($searchs);
		$where_sql = $this->where_sql;
		$where_array = $this->where_array;
		array_unshift($where_array, $edate);
		array_unshift($where_array, $sdate);
		array_unshift($where_array, $edate);
		array_unshift($where_array, $sdate);

		if($view_type == 'month'){
			$view_type = '%Y-%m';
		}
		else{
			$view_type = '%Y-%m-%d';
		}
		$sql = "
SELECT * FROM (
SELECT 
    ddate,
    SUM(total_price) as total_price,
    SUM(total_order_count) as total_order_count,
    SUM(user_join_count) as user_join_count
FROM
    (SELECT 
        DATE_FORMAT(u.insert_dt, '$view_type') AS ddate,
            0 AS total_price,
            0 AS total_order_count,
            COUNT(*) AS user_join_count
    FROM
        user u
    WHERE
        u.insert_dt BETWEEN ? AND ?
    GROUP BY DATE_FORMAT(u.insert_dt, '$view_type') UNION ALL SELECT 
        DATE_FORMAT(o.insert_dt, '$view_type') AS ddate,
            SUM(total_price) AS total_price,
            COUNT(*) AS total_order_count,
            0 AS user_join_count
    FROM
        `order` o
    WHERE
        o.order_status NOT IN (49 , 54)
            AND o.insert_dt BETWEEN ? AND ? ".$where_sql."
    GROUP BY DATE_FORMAT(o.insert_dt, '$view_type')) d
GROUP BY ddate   
 WITH ROLLUP) d 	
ORDER BY ddate desc
    	";

		return $this->general->db_convert_result($this->db->query($sql, $where_array));
	}

	function menu_summary($sdate,$edate,$searchs,$product_type){
		$this->set_searchs($searchs);
		$where_sql = $this->where_sql;
		$where_array = $this->where_array;
		array_unshift($where_array, $edate);
		array_unshift($where_array, $sdate);

		$where1= '';
		$where2= '';

		if($product_type){
			$where1 = " and od.product_no IN (SELECT p.product_no FROM product p WHERE p.product_type = '".$this->data['sch_product_type']."') ";
			$where2 = " and p.product_type = '".$this->data['sch_product_type']."' ";
		}

		$sql = "
SELECT 
    c.name,
    p.product_name,
    CASE WHEN d.fsort IS NULL THEN 0 ELSE d.fsort END fsort,
    ifnull(order_count,0) as order_count,
    ifnull(cancel_count,0) as cancel_count,
    ifnull(cancel_price,0) as cancel_price,
    ifnull(order_price,0) as order_price,
    ifnull(order_rate,0) as order_rate
FROM
    product p
        INNER JOIN
    code c ON c.no = p.product_type
        LEFT OUTER JOIN
    (SELECT 
        CASE
                WHEN product_no IS NULL THEN 1
                ELSE 0
            END fsort,
            d.*,
            ROUND(100 * order_price / @total) AS order_rate
    FROM
        (SELECT 
        od.product_no,
            SUM(od.order_quantity) order_count,
            COUNT(DISTINCT CASE
                WHEN o.order_status IN (49 , 54) THEN o.order_no
                ELSE NULL
            END) AS cancel_count,
            SUM(CASE
                WHEN o.order_status IN (49 , 54) THEN od.order_quantity * od.product_price
                ELSE 0
            END) AS cancel_price,
            SUM(od.order_quantity * od.product_price) order_price,
            @total:=SUM(od.order_quantity * od.product_price)
    FROM
        `order` o
    INNER JOIN order_detail od ON od.order_no = o.order_no
    WHERE
        o.insert_dt BETWEEN ? AND ? ".$where_sql.$where1."
    GROUP BY od.product_no WITH ROLLUP) d) d ON d.product_no = p.product_no
WHERE
	1=1 ".$where2." 
ORDER BY fsort , c.sort , c.name , p.product_name;
    	";

		$ret = $this->general->db_convert_result($this->db->query($sql, $where_array));
		$max = count($ret);
		foreach ($ret as $key => $item){
			if($key == 0){
				$ret[$max] = new stdClass();

				$ret[$max]->name = '합계';
				$ret[$max]->product_name = '합계';
				$ret[$max]->order_count = 0;
				$ret[$max]->cancel_count = 0;
				$ret[$max]->cancel_price = 0;
				$ret[$max]->order_price = 0;
				$ret[$max]->order_rate = 100;
			}

			$ret[$max]->order_count += $item->order_count;
			$ret[$max]->cancel_count += $item->cancel_count;
			$ret[$max]->cancel_price += $item->cancel_price;
			$ret[$max]->order_price += $item->order_price;
			//$ret[$max]->order_rate += $item->order_rate;
		}

		return $ret;
	}

}