<?php

/**
 * Created by PhpStorm.
 * User: SIL
 * Date: 2021-02-15 오후 5:40
 */
class Payment extends MY_Controller
{

	function __construct()
	{
		parent::__construct();
		$this->load->model('admin_payment_model');
		$this->load->library('service/service_payment');
		$this->load->library('form_validation');
	}


	/**
	 * 리스트
	 */
	public function index()
	{
		$this->load->view('view', $this->data);
	}


	public function data($mode = ''){
		$res = $this->input->get();
		$res['order_store'] = $this->general->default($this->input->get('order_store'), $this->user_info->store_no);

		if ($res['page'] == '' || $res['page'] < 1) {
			$res['page'] = 1;
		}

		if ($res['per_page'] == '' || $res['per_page'] < 1) {
			$this->data['per_page'] = PER_PAGE;
		}

		$data = $this->service_payment->lists($res);

		if($mode == 'excel'){
			return $data;
		}
		else{
			$ret = $this->json_output(true, '', $data);
			$this->output->set_output(json_encode($ret));
		}
	}

	/**
	 * 상세정보
	 */
	public function detail($order_no)
	{
		$params = array(
			'order_no' => $order_no
		);
		$res = $this->service_order->detail($params);

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

	public function excel_down(){

		$down_data = $this->data('excel');

		set_time_limit(0);
		ini_set('memory_limit','-1');
		$this->load->library('common/excelxml');

		$this->excelxml->docAuthor('slowraw');

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
		$sheet->writeString(1,3,'주문매장','StyleHeader');
		$sheet->writeString(1,4,'주문상태','StyleHeader');
		$sheet->writeString(1,5,'결제금액','StyleHeader');
		$sheet->writeString(1,6,'결제방법','StyleHeader');
		$sheet->writeString(1,7,'결제일','StyleHeader');
		$sheet->writeString(1,8,'취소일','StyleHeader');
		$sheet->writeString(1,9,'PG 주문번호','StyleHeader');
		$sheet->writeString(1,10,'PG TID','StyleHeader');

		$filename = 'pay_list.xls';

		foreach ($down_data['list'] as $key => $value) {
			$_row_num = $key+2;

			$sheet->writeNumber($_row_num,1,$value->order_no,'StyleBody_left');
			$sheet->writeString($_row_num,2,$value->user_name,'StyleBody_left');
			$sheet->writeString($_row_num,3,$value->store_text,'StyleBody');
			$sheet->writeString($_row_num,4,$value->payment_status_text,'StyleBody');
			$sheet->writeNumber($_row_num,5,$value->payment_price,'StyleNumberFormat');
			$sheet->writeString($_row_num,6,$value->payment_type_text,'StyleBody');
			$sheet->writeString($_row_num,7,$value->insert_dt,'StyleBody');
			$sheet->writeString($_row_num,8,$value->cancel_dt,'StyleBody');
			$sheet->writeString($_row_num,9,$value->pg_oid,'StyleBody');
			$sheet->writeString($_row_num,10,$value->pg_tid,'StyleBody');

		}

		$sheet->columnWidth(1,'50');
		$sheet->columnWidth(2,'100');
		$sheet->columnWidth(3,'70');
		$sheet->columnWidth(4,'70');
		$sheet->columnWidth(5,'70');
		$sheet->columnWidth(6,'70');
		$sheet->columnWidth(7,'100');
		$sheet->columnWidth(8,'100');
		$sheet->columnWidth(9,'120');
		$sheet->columnWidth(10,'250');



		$this->excelxml->sendHeaders($filename);
		$this->excelxml->writeData();
	}
}
