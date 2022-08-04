<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Service_payment extends Service_common
{
	protected $ci;
	protected $error_code = null;
	protected $error_message = null;
	protected $order_no = 0;
	protected $payment_no = 0;

	public function __construct()
	{
		$this->ci =& get_instance();

		$this->ci->load->model('service/payment_model');
		$this->ci->load->library('common/payment');
	}

	private function _set_oid($rno){
		return date('YmdHms') . '_' . $rno;
	}

	public function lists($params){
		$searchs = array();
		if ($params['order_store']!= '' && $params['order_store'] > 0) $searchs['and']['store_no'] = $params['order_store'];
		if ($params['payment_status'] != '') $searchs['and']['payment_status'] = $params['payment_status'];
		if ($params['order_payment_type'] != '') $searchs['and']['payment_type'] = $params['order_payment_type'];
		if ($params['user_name'] != '') $searchs['like']['and']['u.user_name'] = $params['user_name'];

		if ($params['sdate'] && $params['edate']) {
			$searchs['between'][] = array(
				'o.insert_dt' => array($params['sdate'], $params['edate']),
			);
		}

		$data = $this->ci->admin_payment_model->lists($params['page'], $params['per_page']*1, $searchs);
		return $data;
	}

	public function get_orderno(){
		return $this->order_no;
	}
	public function ready($params)
	{
		$order_data = array();
		$payment_no = 0;
		$this->ci->payment_model->set_table('payment');
		$payment_data = array(
			'payment_type' => $params['payment_type'],
			'order_no' => $params['order_no'],
			'payment_price' => $params['total_price'],
		);

		$insert_data['payment_status'] = $this->get_codes(array('parent_code' => 'PAYMENT_STATUS', 'code' => 'READY'))[0]->no;

		$payment_no = $this->ci->payment_model->insert($payment_data);

		if ($payment_no < 1) {
			$this->error_message = '결제 정보 저장 중 오류가 발생했습니다.[1]';
			return false;
		}

		$payment_data['oid'] = $this->_set_oid($payment_no);

		if (false === $this->oid_update($payment_no, $payment_data['oid'])) {
			$this->error_message = '결제 정보 저장 중 오류가 발생했습니다.[2]';
			return false;
		}

		$this->ci->payment_model->set_table('payment_detail');
		$payment_detail_data = array('payment_no' => $payment_no);
		if(isset($params['payment_data'])){
			$payment_detail_data['payment_data'] = json_encode($params['payment_data']);
		}

		if(isset($params['payment_memo'])){
			$payment_detail_data['payment_memo'] = $params['payment_memo'];
		}

		$payment_detail_no = $this->ci->payment_model->insert($payment_detail_data);
		if ($payment_detail_no < 1) {
			$this->error_message = '결제 정보 저장 중 오류가 발생했습니다.[3]';
			return false;
		}

		$payment_data['user_phone'] =$params['user_phone'];
		$payment_data['goods'] = '제품';
		$pg_data = $this->ci->payment->inicis_init('start',array_merge($payment_data, $payment_detail_data));

		return $pg_data;
	}

	public function oid_update($payment_no, $oid)
	{
		$this->ci->payment_model->set_table('payment');
		return $this->ci->payment_model->update(array('pg_oid' => $oid), array('payment_no' => $payment_no));
	}

	public function is_exists_tid($tid)
	{
		$this->ci->payment_model->set_table('payment');
		$data = $this->ci->payment_model->get(array('pg_tid' => $tid),'payment_no');
		if ($data) {
			return true;
		}

		return false;
	}

	public function complate($oid, $device_type)
	{
		log_message('error', 1);
		if($device_type == 'mobile'){
			$pay_result = $this->ci->payment->inipay_mobile_result();
		}
		else{
			$pay_result = $this->ci->payment->inipay_pc_result();
		}

		if (false === $pay_result) {
			$_error = $this->ci->payment->get_error();
			$this->error_message = 'PG ERROR - '.$_error->message;
			return false;
		}
		
		$pg_tid = $pay_result->pg_tid;
		log_message('error', 2);
		// 이미 db에 저장되어 있는지 확인
		if (true === $this->is_exists_tid($pg_tid)) {
			$this->error_message = '결제 처리 중 오류가 발생했습니다.[이미 결제 완료]';
			return false;
		}

		$this->ci->payment_model->set_table('payment');
		$pay_data = $this->ci->payment_model->get(array('pg_oid' => $oid),'payment_no, payment_price, order_no');
		log_message('error', 3);
		if($pay_result->pay_price != $pay_data->payment_price){
			$this->error_message = '결제 처리 중 오류가 발생했습니다.[결제금액이 다름]';
			return false;
		}

		$this->order_no = $pay_data->order_no;
		$update_data = array(
			'pg_tid' => $pg_tid,
			'is_ready' => 0,
			'payment_dt' => date('Y-m-d H:i:s'),
		);
		log_message('error', 4);

		$update_data['payment_status'] = $this->get_codes(array('parent_code' => 'PAYMENT_STATUS', 'code' => 'END'))[0]->no;

		$is_update = $this->ci->payment_model->update($update_data, array('payment_no' =>$pay_data->payment_no));
		if(!$is_update){
			$this->error_message = '결제 처리 중 오류가 발생했습니다.[결제데이터없음]';
			return false;
		}
		log_message('error', 5);
		$this->ci->load->library('service/service_order');
		$is_update = $this->ci->service_order->payment_complate($pay_data->order_no);
		if(!$is_update){
			$_error = $this->ci->service_order->get_error();
			$this->error_message = $_error->message;
			return false;
		}
		log_message('error', 6);

		$this->ci->payment_model->set_table('order');
		$order_data = $this->ci->payment_model->get(array('order_no' => $pay_data->order_no),'user_no, order_type');
		//카트 삭제
		$data = array(
			'user_no' => $order_data->user_no,
			'order_type' => $order_data->order_type,
		);
		$this->ci->service_order->cart_delete($data);
		return $is_update;
	}

	public function cancel($order_no){

		$this->ci->payment_model->set_table('payment');
		$payment_data = $this->ci->payment_model->get(array('order_no' => $order_no),'payment_no, pg_tid');

		$inicis_set_data = array(
			'refund_msg' => '관리자 주문 취소',
			'tid' => $payment_data->pg_tid
		);


		$cancel_result = $this->ci->payment->inipay_cancel($inicis_set_data);

		if (false === $cancel_result) {
			$_error = $this->ci->payment->get_error();
			$this->error_message = 'PG ERROR - '.$_error->message;
			return false;
		}

		$update_data = array(
			'cancel_dt' => date('Y-m-d H:i:s'),
		);
		$update_data['payment_status'] = $this->get_codes(array('parent_code' => 'PAYMENT_STATUS', 'code' => 'CANCEL_END'))[0]->no;

		$payment_cancel = $this->ci->payment_model->update($update_data, array('payment_no' =>  $payment_data->payment_no));
		if (false === $payment_cancel) {
			$this->error_message = '결제 취소 저장 중 오류가 발생했습니다.';
			return false;
		}

		return true;

	}
}
