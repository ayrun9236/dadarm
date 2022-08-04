<?php
require_once (SERVICE_ROOT.'/app/application/models/service/Generic_model.php');
class Order_model extends Generic_model
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
   o.*,  u.user_name
FROM
   `order` o
	INNER JOIN user u ON u.user_no = o.user_no
WHERE o.is_ready = 0 
        " . $where_sql;

		$total_sql = 'SELECT COUNT(*) cnt FROM ' . explode("FROM", $default_sql)[1];
		$total_count = $this->db->query($total_sql, $where_array)->row()->cnt;

		//todo output 필드정리
		$where_array[] = $offset;
		$where_array[] = $limit;
		$data_sql = "
SELECT 
	o.*,
       cs.name as store_text,
       co.name as order_status_text,
       co.code as order_status_code,
       cp.name as payment_type_text,
       cp.code as payment_type_code,
       ct.name as order_type_text,
       ct.code as order_type_code,
       cm.coupon_name, cm.gifts
FROM (
$default_sql
ORDER BY o.order_no DESC LIMIT ?,?) o
INNER JOIN code cs ON cs.no = o.store_no
INNER JOIN code co ON co.no = o.order_status
INNER JOIN code cp ON cp.no = o.payment_type
INNER JOIN code ct ON ct.no = o.order_type
LEFT OUTER JOIN user_coupon uc on uc.user_coupon_no = o.user_coupon_no
LEFT OUTER JOIN coupon_master cm on cm.coupon_master_no = uc.coupon_master_no
ORDER BY o.order_no DESC 
";
		return array(
			'total_count' => $total_count,
			'list'        => $this->general->db_convert_result($this->db->query($data_sql, $where_array)));
	}

	function user_lists($page, $page_row, $searchs = array())
	{
		$offset = ($page - 1) * $page_row;
		$limit = $page_row;
		$this->set_searchs($searchs);
		$where_sql = $this->where_sql;
		$where_array = $this->where_array;

		$default_sql = "
SELECT 
   o.*
FROM
   `order` o
WHERE o.is_ready = 0 
        " . $where_sql;

		$total_sql = 'SELECT COUNT(*) cnt FROM ' . explode("FROM", $default_sql)[1];
		$total_count = $this->db->query($total_sql, $where_array)->row()->cnt;

		$where_array[] = $offset;
		$where_array[] = $limit;
		$data_sql = "
SELECT 
	o.order_no, 
   	cs.name as store_text,
   	co.name as order_status_text,
   	co.code as order_status_code,
    cp.name as payment_type_text,   	
   	(select count(*) from order_detail od where od.order_no=o.order_no) as order_menu_count,
	p.product_name,
    concat(DATE_FORMAT(o.insert_dt,'%Y-%m-%d-'),o.order_no) AS order_view_no,
	DATE_FORMAT(o.insert_dt, '%Y.%m.%d %r %I:%i') as order_dt,
	o.delivery_address,
	o.delivery_address_detail,
	o.user_phone
FROM (
$default_sql
ORDER BY o.order_no DESC LIMIT ?,?) o
INNER JOIN code cs ON cs.no = o.store_no
INNER JOIN code co ON co.no = o.order_status
INNER JOIN code ct ON ct.no = o.order_type
INNER JOIN code cp ON cp.no = o.payment_type
INNER JOIN order_detail od ON od.order_no = o.order_no and od.sort = 1
INNER JOIN product p ON p.product_no = od.product_no
ORDER BY o.order_no DESC 
";
		return array(
			'total_count' => $total_count,
			'list'        => $this->general->db_convert_result($this->db->query($data_sql, $where_array)));
	}

	function detail($mode, $searchs){

    	if($mode == 'app'){
			$sql = "
SELECT 
	o.order_no, 
   	cs.name as store_text,
   	cs.etc_data as store_etc_data,
   	co.name as order_status_text,
   	co.code as order_status_code,
   	DATE_FORMAT(o.insert_dt, '%Y.%m.%d %p %I:%i') as order_dt,
   	DATE_FORMAT(o.update_dt, '%Y.%m.%d %p %I:%i') as last_update_dt,
	o.delivery_address,
	o.delivery_address_detail,
	o.user_phone,
    o.total_price,
    o.origin_price,
    cp.name as payment_type_text,
    cm.coupon_name,
    ct.code as order_type_code,
    o.delivery_price   
FROM `order` o
INNER JOIN code cs ON cs.no = o.store_no
INNER JOIN code co ON co.no = o.order_status
INNER JOIN code ct ON ct.no = o.order_type
INNER JOIN code cp ON cp.no = o.payment_type
LEFT OUTER JOIN user_coupon uc ON uc.user_coupon_no= o.user_coupon_no
LEFT OUTER JOIN coupon_master cm ON cm.coupon_master_no= uc.coupon_master_no
WHERE o.order_no=? and o.user_no=?;		
		";
			$ret = $this->general->db_convert_row($this->db->query($sql, array($searchs['order_no'], $searchs['user_no'])));

			if(!$ret){
				return false;
			}
		}

		$sql = "
SELECT 
	od.*,
    p.product_name,  
    c.name as product_type_text,
    i.original_img AS product_image
FROM
   	order_detail od
   	INNER JOIN product p ON p.product_no = od.product_no
	INNER JOIN code c ON c.no = p.product_type
	LEFT OUTER JOIN image i ON i.target_no = p.product_no AND i.target_table = 'product' AND i.sort = 1
WHERE 1=1 AND od.order_no = ?
ORDER BY od.order_detail_no ASC 		
		";

		$detail = $this->general->db_convert_result($this->db->query($sql, array($searchs['order_no'])));

		if($mode == 'app'){
			$ret->detail = $detail;

			//주문상태 조회
			$sql = "
SELECT 
    c.name,max(l.insert_dt) as insert_dt
FROM
    code c left outer join slowraw_log.order_status_log l on l.order_status = c.no and l.order_no = ?
WHERE
    c.parent_no IN (SELECT no FROM code WHERE code = 'ORDER_STATUS_".$ret->order_type_code."') and code like '".$ret->order_type_code."%'
GROUP BY c.name order by c.sort;			
			";
			$ret->status_dt = $this->general->db_convert_result($this->db->query($sql, array($searchs['order_no'])));
		}
		else{
			$ret = (object)array('detail' => $detail);
		}

		return $ret;
	}

	function cart_lists($searchs = array())
	{
		$this->set_searchs($searchs);
		$where_sql = $this->where_sql;
		$where_array = $this->where_array;

		$data_sql = "
SELECT 
	p.*,    
    c.name as product_type_text,
	i.original_img AS product_image,
    c.order_quantity,
    c.cart_detail_no,
    c.size,
    c.topping,
    c.is_soldout,
    c.product_price as cart_product_price,
    c.kcal as cart_kcal
FROM (
SELECT 
   cd.*,ps.is_soldout
FROM
   cart c 
   INNER JOIN cart_detail cd ON cd.cart_no = c.cart_no 
   INNER JOIN product_store ps ON ps.product_no = cd.product_no and ps.store_no=c.store_no 
WHERE 1=1   " . $where_sql.") c
INNER JOIN product p ON p.product_no = c.product_no
INNER JOIN code c ON c.no = p.product_type
LEFT OUTER JOIN image i ON i.target_no = p.product_no AND i.target_table = 'product' AND i.sort = 1
ORDER BY c.cart_detail_no DESC 
";
		return $this->general->db_convert_result($this->db->query($data_sql, $where_array));
	}

	function order_ready($data) {

    	$sql = "
INSERT INTO `order`
(user_no,
total_price,
store_no,
request_memo,
origin_price,
delivery_address,
delivery_address_detail,
order_type,
payment_type,
etc_data,
order_status,
is_ready,
user_phone,
 pickup_dt,
user_coupon_no,
 delivery_price)
SELECT 
cart.user_no,
cart.total_price,
cart.store_no,
cart.request_memo,
cart.origin_price,
cart.delivery_address,
cart.delivery_address_detail,
cart.order_type,
cart.payment_type,
cart.etc_data as etc_data,
? as order_status,
? as is_ready,
u.user_phone,
DATE_ADD(now(), INTERVAL pickup_time MINUTE),
cart.user_coupon_no,
       cart.delivery_price
FROM 
	cart cart
	INNER JOIN user u ON u.user_no = cart.user_no
WHERE cart.user_no = ? and cart.cart_no =?";
		$this->db->query($sql, array($data['order_status'], $data['is_ready'], $data['user_no'], $data['cart_no']));
		$order_no = $this->db->insert_id();
		if($order_no < 1){
			return false;
		}

		$sql = "
INSERT INTO slowraw.order_detail(
	order_no,
	product_no,
	order_quantity,
	product_price,
    size,
	topping,
	kcal,
	sort)
SELECT 
    ? AS order_no, 
	product_no, 
	order_quantity, 
    product_price, 
    size,
	topping,
	kcal,
	@rownum:=@rownum + 1 AS sort
FROM
    cart_detail cd
	INNER JOIN cart c ON c.cart_no = cd.cart_no
    INNER JOIN (SELECT @rownum:=0) tmp     
WHERE
    c.user_no = ?";
		$this->db->query($sql, array($order_no, $data['user_no']));
		if($this->db->insert_id() < 1){
			return false;
		}
		else{
			return $order_no;
		}
	}


	function event_list($searchs){

		$where_sql = '';

		if($searchs['mode'] != ''){
			if($searchs['mode'] == 'ing'){
				$where_sql .= " AND curdate() between b.sdate  and b.edate";
			}

			if($searchs['mode'] == 'end'){
				$where_sql .= " AND b.edate < curdate()";
			}
		}

		if(isset($searchs['coupon_publish_type'])){
			$where_sql .= " AND cm.publish_type in (".$searchs['coupon_publish_type'].")";
		}

		$sql = "
SELECT 
    cm.coupon_name,
	cm.coupon_master_no,
    b.sdate as use_sdate,
    b.edate as use_edate,
    cm.order_min_price,
    ifnull(cd.code,'ALL') as order_type_code,
    i.original_img AS coupon_image,       
    cm.use_store,
    cm.is_use_store_all,
	cp.code as publish_type_code
FROM
    coupon_master cm
    INNER JOIN board b ON b.board_type = (SELECT no FROM code WHERE code='EVENT') AND b.sub_type=cm.coupon_master_no    
    LEFT OUTER JOIN code cd ON cd.no = cm.order_type
    LEFT OUTER JOIN code cp ON cp.no = cm.publish_type
    LEFT OUTER JOIN image i ON i.target_no = b.board_no AND i.target_table = 'EVENT_image' AND i.sort = 1
WHERE
    1=1 ". $where_sql ."
ORDER BY cm.coupon_master_no desc
    ";

		return $this->general->db_convert_result($this->db->query($sql));
	}

	function order_stamp_insert($user_no,$order_no,$coupon_master_no){
		$sql = "
INSERT INTO user_stamp (user_no,order_no,coupon_master_no)
SELECT 
    user_no,order_no,coupon_master_no
FROM
    (SELECT ? as user_no, ? as order_no, ? as coupon_master_no) d
WHERE
    NOT EXISTS( SELECT 
            1
        FROM
            user_stamp u
        WHERE
            u.user_no = d.user_no
            AND u.order_no = d.order_no)    	
    	";

		$this->db->query($sql, array($user_no,$order_no,$coupon_master_no));
		if($this->db->insert_id() < 1){
			return false;
		}

		return true;
	}

	function order_coupon_insert($user_no,$order_no,$coupon_master_no, $etc_data = array()){

		$bind_data = array($user_no,$order_no,$coupon_master_no);

    	//if(isset($etc_data['login_id_check'])){
			$bind_data = array($order_no,$coupon_master_no,$user_no);

			$sql = "
INSERT INTO user_coupon (user_no,order_no,coupon_master_no, login_id)
SELECT 
    user_no,order_no,coupon_master_no,login_id
FROM
    (SELECT user_no, ? as order_no, ? as coupon_master_no, login_id from user where user_no=?) d
WHERE
    NOT EXISTS( SELECT 
            1
        FROM
            user_coupon u
        WHERE
            u.login_id = d.login_id            
                AND u.coupon_master_no = d.coupon_master_no)  	
    	";
//		}
//    	else{
//			$sql = "
//INSERT INTO user_coupon (user_no,order_no,coupon_master_no)
//SELECT
//    user_no,order_no,coupon_master_no
//FROM
//    (SELECT ? as user_no, ? as order_no, ? as coupon_master_no) d
//WHERE
//    NOT EXISTS( SELECT
//            1
//        FROM
//            user_coupon u
//        WHERE
//            u.user_no = d.user_no
//                AND u.coupon_master_no = d.coupon_master_no)
//    	";
//
//		}

		$this->db->query($sql, $bind_data);
		if($this->db->insert_id() < 1){
			return false;
		}

		return true;
	}

}