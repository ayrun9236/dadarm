<?php

/**
 * Created by PhpStorm.
 * User: SIL
 * Date: 2021-02-15 오후 5:40
 */
class Order extends MY_Controller
{

	function __construct()
	{
		parent::__construct();
		$this->load->model('admin_order_model');
		$this->load->library('service/service_order');
		$this->load->library('form_validation');
	}


	/**
	 * 리스트
	 */
	public function index()
	{
		$this->data['order_no'] = $this->input->get('order_no');
		$this->load->view('view', $this->data);
	}


	public function data($mode = ''){
		$this->data['sch_user_name'] = $this->input->get('user_name');
		$this->data['sch_order_store'] = $this->general->default($this->input->get('order_store'), $this->user_info->store_no);
		$this->data['sch_order_status'] = $this->input->get('order_status');
		$this->data['sch_payment_type'] = $this->input->get('order_payment_type');
		$this->data['sch_order_type'] = $this->input->get('order_type');
		$this->data['sch_sdate'] = $this->input->get('order_sdate');
		$this->data['sch_edate'] = $this->input->get('order_edate');
		$this->data['per_page'] = (int)$this->input->get('per_page');
		$this->data['page'] = (int)$this->input->get('page');
		$this->data['sch_order_no'] = (int)$this->input->get('order_no');
		$this->data['sch_user_no'] = (int)$this->input->get('user_no');

		if ($this->data['page'] == '' OR $this->data['page'] < 1) {
			$this->data['page'] = 1;
		}

		if ($this->data['per_page'] == '' OR $this->data['per_page'] < 1) {
			$this->data['per_page'] = PER_PAGE;
		}

		$searchs = array();
		if ($this->data['sch_order_no'] > 0) $searchs['and']['order_no'] = $this->data['sch_order_no'];
		if ($this->data['sch_user_no'] > 0) $searchs['and']['o.user_no'] = $this->data['sch_user_no'];
		if ($this->data['sch_order_store'] != '' && $this->data['sch_order_store']> 0) $searchs['and']['store_no'] = $this->data['sch_order_store'];
		if ($this->data['sch_order_status'] != '') $searchs['and']['order_status'] = $this->data['sch_order_status'];
		if ($this->data['sch_payment_type'] != '') $searchs['and']['payment_type'] = $this->data['sch_payment_type'];
		if ($this->data['sch_order_type'] != '') $searchs['and']['order_type'] = $this->data['sch_order_type'];
		if ($this->data['sch_user_name'] != '') $searchs['like']['and']['u.user_name'] = $this->data['sch_user_name'];

		if ($this->data['sch_sdate'] && $this->data['sch_edate']) {
			$searchs['between'][] = array(
				'o.insert_dt' => array($this->data['sch_sdate'], $this->data['sch_edate']),
			);
		}

		$data = $this->admin_order_model->lists($this->data['page'], $this->data['per_page'], $searchs);
		foreach ($data['list'] as $key => $item){
			$data['list'][$key]->etc_data = (array)json_decode($item->etc_data);

		}

		if($mode == 'excel'){
			return $data;
		}
		else{
			$ret = $this->json_output(true, '', $data);
			$this->output->set_output(json_encode($ret));
		}
	}

	protected function _detail($order_no){
		$params = array(
			'order_no' => $order_no
		);

		return $this->service_order->detail($params);
	}

	/**
	 * 상세정보
	 */
	public function detail($order_no)
	{
		$res = $this->_detail($order_no);

		$ret = $this->json_output(true, '', $res);
		$this->output->set_output(json_encode($ret));
	}

	/**
	 * 주문상태 변경
	 */
	public function status_modify()
	{
		$para_validation = array(
			array('field' => 'order_nos[]', 'rules' => 'required', 'label' => '주문번호'),
			array('field' => 'order_edit_status', 'rules' => 'required', 'label' => '변경주문상태'),
		);
		$this->form_validation->set_rules($para_validation);

		if (true !== $this->form_validation->run()) {
			$ret = $this->json_output(false, '필수 입력 사항이 누락되었습니다.' . $this->form_validation->error_string());
			$this->output->set_output(json_encode($ret));
			return;
		}

		$res = $this->input->post();

		$is_update = $this->service_order->modify_status($res);
		if ($is_update === TRUE) {
			$ret = true;
			$message = '주문 상태를 변경했습니다.';
		} else {
			$ret = false;
			$_error = $this->service_order->get_error();
			$message = $is_update.'건 : '.$_error->message;

		}

		$ret = $this->json_output($ret, $message, array());
		$this->output->set_output(json_encode($ret));
	}

	/**
	 * 상세정보
	 */
	public function prints()
	{
		$this->load->view('blank', $this->data);
	}

	/**
	 * 상세정보
	 */
	public function oprint($order_no)
	{
		$searchs = array();
		$searchs['static']['and'][] = " o.order_no in (".$order_no.")";
		$detail_data = $this->admin_order_model->lists(1, 100, $searchs);
		foreach ($detail_data['list'] as $key => $list){
			$detail_data['list'][$key] = array_merge((array)$detail_data['list'][$key],(array)$this->_detail($list->order_no));
		}

		$this->data['orders'] = $detail_data['list'];
		$this->load->view('blank', $this->data);
	}

	public function excel_down(){

		$down_data = $this->data('excel');

		set_time_limit(0);
		ini_set('memory_limit','-1');
		$this->load->library('common/excelxml');

		$this->excelxml->docAuthor('dadaleum');

		$sheet = $this->excelxml->addSheet('sheet1');

		$format = $this->excelxml->addStyle('StyleHeader');
		$format->fontSize(11);
		$format->fontBold();
		$format->fontFamily('Nanum Gothic');
		$format->bgColor('#dedede');
		$format->fontColor('#333');
		$format->alignHorizontal('Center');
		$format->alignVertical('Center');
		$format->border('ALL','1','#ccc','Continuous');

		$format = $this->excelxml->addStyle('StyleBody');
		$format->alignHorizontal('Center');
		$format->alignWraptext();
		$format->fontSize(10);

		$format = $this->excelxml->addStyle('StyleBody_left');
		$format->alignHorizontal('Left');
		$format->fontSize(10);

		$format = $this->excelxml->addStyle('StyleNumberFormat');
		$format->numberFormat('#,##0_ ');
		$format->alignHorizontal('Right');
		$format->fontSize(10);


		$sheet->writeString(1,1,'주문번호','StyleHeader');
		$sheet->writeString(1,2,'주문자명','StyleHeader');
		$sheet->writeString(1,3,'휴대전화','StyleHeader');
		$sheet->writeString(1,4,'주문매장','StyleHeader');
		$sheet->writeString(1,5,'주문상태','StyleHeader');
		$sheet->writeString(1,6,'결제방법','StyleHeader');
		$sheet->writeString(1,7,'주문형태','StyleHeader');
		$sheet->writeString(1,8,'결제금액','StyleHeader');
		$sheet->writeString(1,9,'배달팁','StyleHeader');
		$sheet->writeString(1,10,'완료예정시간','StyleHeader');
		$sheet->writeString(1,11,'주문일','StyleHeader');

		$filename = 'order_list.xls';

		foreach ($down_data['list'] as $key => $value) {
			$_row_num = $key+2;

			$sheet->writeNumber($_row_num,1,$value->order_no,'StyleBody_left');
			$sheet->writeString($_row_num,2,$value->user_name,'StyleBody_left');
			$sheet->writeString($_row_num,3,$value->user_phone,'StyleBody');
			$sheet->writeString($_row_num,4,$value->store_text,'StyleBody');
			$sheet->writeString($_row_num,5,$value->order_status_text,'StyleBody');
			$sheet->writeString($_row_num,6,$value->payment_type_text,'StyleBody');
			$sheet->writeString($_row_num,7,$value->order_type_text,'StyleBody');
			$sheet->writeNumber($_row_num,8,$value->total_price,'StyleNumberFormat');
			$sheet->writeNumber($_row_num,9,$value->delivery_price,'StyleNumberFormat');
			$sheet->writeString($_row_num,10,$value->pickup_dt == '0000-00-00 00:00:00' ? '-': $value->pickup_dt,'StyleBody');
			$sheet->writeString($_row_num,11,$value->insert_dt,'StyleBody');
		}

		$sheet->columnWidth(1,'50');
		$sheet->columnWidth(2,'100');
		$sheet->columnWidth(3,'80');
		$sheet->columnWidth(4,'100');
		$sheet->columnWidth(5,'200');
		$sheet->columnWidth(6,'150');
		$sheet->columnWidth(7,'80');
		$sheet->columnWidth(8,'100');
		$sheet->columnWidth(9,'100');
		$sheet->columnWidth(10,'100');
		$sheet->columnWidth(11,'100');


		$this->excelxml->sendHeaders($filename);
		$this->excelxml->writeData();
	}
}
