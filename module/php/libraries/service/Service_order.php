<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Service_order extends Service_common
{
	protected $ci;
	protected $error_code = null;
	protected $error_message = null;

	public function __construct()
	{
		$this->ci =& get_instance();

		$this->ci->load->model('service/order_model');
		$this->ci->load->library('service/service_payment');
		$this->ci->load->library('service/service_product');
	}

	//주문처리
	public function ready($params)
	{
		$order_no = 0;
		$this->ci->order_model->set_table('order');
		$insert_data = array(
			'user_no'  => $params['user_no'],
			'is_ready' => 1,
		);

		$params['order_type'] = strtoupper($params['order_type']);
		$order_type = $this->get_codes(array('parent_code' => 'ORDER_TYPE', 'code' => $params['order_type']))[0]->no;

		$this->ci->order_model->set_table('cart');
		$cart_data = $this->ci->order_model->get(array('user_no' => $params['user_no'], 'order_type' => $order_type), 'cart_no, user_no,payment_type, total_price,user_coupon_no');
		if (!$cart_data) {
			$this->error_message = '주문 요청 중 오류가 발생했습니다.[0]';
			return false;
		}

		$pay_type_store = array();
		$pay_type_store[] = $this->get_codes(array('parent_code' => 'PAYMENT_TYPE', 'code' => 'STOREPAY_CARD'))[0]->no;
		$pay_type_store[] = $this->get_codes(array('parent_code' => 'PAYMENT_TYPE', 'code' => 'STOREPAY_CASH'))[0]->no;
		if (in_array($cart_data->payment_type,$pay_type_store, TRUE)) {
			$insert_data['is_ready'] = 0;
		}

		$insert_data['order_status'] = $this->get_codes(array('parent_code' => 'ORDER_STATUS_' . $params['order_type'], 'code' => $params['order_type'] . '_STEP1'))[0]->no;
		$insert_data['cart_no'] = $cart_data->cart_no;
		$order_no = $this->ci->order_model->order_ready($insert_data);
		if ($order_no < 1) {
			$this->error_message = '주문 요청 중 오류가 발생했습니다.[1]';
			return false;
		}

		//쿠폰사용여부 체크
		if($cart_data->user_coupon_no > 0){
			$this->ci->order_model->set_table('user_coupon');
			$this->ci->db->set('use_dt', 'NOW()', FALSE);
			$is_update = $this->ci->order_model->update(array(), array('user_no' => $cart_data->user_no,'user_coupon_no' => $cart_data->user_coupon_no));
		}

		//결제 타입이 매장주문이 아닌 경우 결제정보 저장
		$ret = array('order_no' => $order_no);
		if (in_array($cart_data->payment_type,$pay_type_store, TRUE)) {
			$data = array(
				'user_no' => $params['user_no'],
				'order_type' => $order_type,
			);
			$this->ci->service_order->cart_delete($data);

			$this->order_request_sms($order_no);
		}
		else{
			$this->ci->order_model->set_table('order');
			$order_data = $this->ci->order_model->get(array('order_no' => $order_no), 'user_phone');

			$payment_params = array(
				'payment_type' => $cart_data->payment_type,
				'order_no'     => $order_no,
				'total_price'  => $cart_data->total_price,
				'user_phone'  => $order_data->user_phone,
			);

			$payment_data = $this->ci->service_payment->ready($payment_params);
			if ($payment_data === false) {
				$_error = $this->ci->service_payment->get_error();
				$this->error_message = $_error->message;
				return false;
			}

			$ret['payment_request_data'] = $payment_data;
		}

		return $ret;
	}

	//
	public function payment_complate($order_no)
	{
		$this->ci->order_model->set_table('order');
		//카드비우기
		$data = $this->ci->order_model->get(array('order_no' => $order_no), 'user_no');
		$is_update = $this->ci->order_model->update(array('is_ready' => 0), array('order_no' => $order_no));
		if (!$is_update) {
			$this->error_message = '주문 결제완료 중 오류가 발생했습니다.';
			return false;
		}

		$this->cart_delete(array('user_no' => $data->user_no));
		return $is_update;
	}

	public function detail($params)
	{
		$mode = '';
		if (API_URL == $_SERVER['HTTP_HOST']) {
			$mode = 'app';
		}

		$data = $this->ci->order_model->detail($mode, $params);

		if ($data) {
			if(isset($data->store_etc_data)){
				$data->store_etc_data = json_decode($data->store_etc_data);
				$data->store_phone = $data->store_etc_data->tel;
				unset($data->store_etc_data);
			}

			foreach ($data->detail as $key => $item) {
				$data->detail[$key]->topping = json_decode($item->topping);
				$data->detail[$key]->size = json_decode($item->size);
				$_item = array();
				if(isset($data->detail[$key]->topping)){
					foreach ($data->detail[$key]->topping as $sitem) {
						$_item[] = $sitem->name;
					}

					$data->detail[$key]->topping = implode(' / ', $_item);
				}

				if(isset($data->detail[$key]->size)) {
					$_item = array();
					foreach ($data->detail[$key]->size as $sitem) {
						$_item[] = $sitem->name;
					}

					$data->detail[$key]->size = implode(' / ', $_item);
				}
			}
		}

		//주문하자마자 조리라고 표시해 달라고함;;
		if($mode == 'app'){
			if (strpos($data->order_status_code, '_STEP1') !== FALSE) {
				$next_step = $this->get_codes(array('parent_code' => 'ORDER_STATUS_' . $data->order_type_code, 'code' => $data->order_type_code . '_STEP2'))[0];
				$data->order_status_code = $next_step->code;
				$data->order_status_text = $next_step->name;

				if(isset($data->status_dt[1]->name) && $data->status_dt[1]->insert_dt == ''){
					$data->status_dt[1]->insert_dt = $data->status_dt[0]->insert_dt;
				}

			}
		}
		return $data;
	}

	public function modify_status($params)
	{
		$fail_count = 0;
		$stamp_event = array();
		$coupon_event = array();
		$first_buy_coupon_event = array();
		$this->ci->load->library('common/firebase');
		//결제가 물려 있는 경우가 있어서 트랜잭션 처리는 여기서
		foreach ($params['order_nos'] as $key => $order_no) {
			$this->ci->db->trans_begin();
			$add_push_content = '';
			$this->ci->order_model->set_table('order');
			$update_data = array(
				'order_status' => $params['order_edit_status']
			);

			if(isset($params['order_edit_pickup_time'])){
				$this->ci->db->set('pickup_dt', 'DATE_ADD(now(), INTERVAL '.$params['order_edit_pickup_time'].' MINUTE)', FALSE);
			}

			$is_update = $this->ci->order_model->update($update_data, array('order_no' => $order_no));
			if (!$is_update) {
				log_message('error', '주문상태변경실패-'.$order_no);
				$fail_count++;
				continue;
			}

			$searchs = array();
			$searchs['and']['o.order_no'] = $order_no;
			$order_info = $this->ci->order_model->lists(1, 1, $searchs)['list'][0];

			if ($order_info->order_status_code == 'CANCEL') {
				if (!in_array($order_info->payment_type_code,array('STOREPAY_CARD','STOREPAY_CASH'), TRUE)) {
					$payment_data = $this->ci->service_payment->cancel($order_no);
					if ($payment_data === false) {
						$_error = $this->ci->service_payment->get_error();
						$this->error_message = $_error->message;
						$fail_count++;
						log_message('error', '주문상태변경실패-'.$order_no);
						continue;
					}
				}

				// 이벤트 관련 처리해주기, 중복은 제거
				$this->ci->order_model->set_table('user_stamp');
				$this->ci->order_model->delete(array('order_no' => $order_no, 'user_no' => $order_info->user_no));

				$this->ci->order_model->set_table('user_coupon');
				$this->ci->order_model->delete(array('order_no' => $order_no, 'user_no' => $order_info->user_no));

				// 사용된 쿠폰 되돌려주기
				$this->ci->order_model->set_table('user_coupon');
				$this->ci->db->set('use_dt', 'NULL', FALSE);
				$this->ci->order_model->update(array(), array('user_coupon_no' => $order_info->user_coupon_no, 'user_no' => $order_info->user_no));
			}

			if (strpos($order_info->order_status_code, '_END') !== FALSE){
				if($key == 0){
					$coupon_publish_type = $this->get_codes(array('parent_code' => 'COUPON_PUBLISH_TYPE', 'code' => 'STAMP'))[0]->no;
					$_tmp = $this->event_list_get(array('mode' => 'ing', 'coupon_publish_type' => $coupon_publish_type));
					if($_tmp){
						$stamp_event = $_tmp[0];
						//$coupon_event->use_store = explode(',',$coupon_event->use_store);
					}

					//$coupon_publish_type = $this->get_codes(array('parent_code' => 'COUPON_PUBLISH_TYPE', 'code' => 'BUY'))[0]->no;
//					$this->ci->order_model->set_table('code');
////					$this->ci->db->like('code', 'BUY', 'before');
////					$code_list = $this->ci->order_model->list(array('parent_no' => 90),'no');
////					$event_buy_code = array();
////					foreach ($code_list as $code){
////						$event_buy_code[] = $code->no;
////					}

					$_tmp = $this->event_list_get(array('mode' => 'ing', 'coupon_publish_type' => '92,95,96'));
					if($_tmp){
						$coupon_event = $_tmp;
						foreach ($coupon_event as $key => $_item){
							$coupon_event[$key]->use_store = explode(',',$_item->use_store);
						}

						//print_r($coupon_event);
					}

					//첫구매 쿠폰발행
					$coupon_publish_type = $this->get_codes(array('parent_code' => 'COUPON_PUBLISH_TYPE', 'code' => 'FIRST_BUY'))[0]->no;
					$_tmp = $this->event_list_get(array('mode' => 'ing', 'coupon_publish_type' => $coupon_publish_type));
					if($_tmp){
						$first_buy_coupon = $_tmp[0];
						$first_buy_coupon->use_store = explode(',',$first_buy_coupon->use_store);
					}
				}

				//if(isset($stamp_event->order_min_price) &&  $stamp_event->order_min_price<=$order_info->total_price){
					if($this->ci->order_model->order_stamp_insert($order_info->user_no,$order_no,$stamp_event->coupon_master_no) !== false){
						$add_push_content .=" 스탬프가 지급되었습니다.";
					}
				//}

				if(isset($first_buy_coupon->order_min_price) &&  $first_buy_coupon->order_min_price<=$order_info->total_price && ($first_buy_coupon->publish_type_code == 'BUY' || strrpos($first_buy_coupon->publish_type_code,$order_info->order_type_code)!== false)){
					//첫구매인지 체크
					$this->ci->order_model->set_table('order');
					$order_user_list = $this->ci->order_model->list(array('user_no' => $order_info->user_no),'order_status', '', 2);
					if(count($order_user_list) == 1 && $this->ci->order_model->order_coupon_insert($order_info->user_no,$order_no,$first_buy_coupon->coupon_master_no) !== false){
						$add_push_content .=" [".$first_buy_coupon->coupon_name."]쿠폰이 지급되었습니다.";
					}
				}


				foreach ($coupon_event as $coupon_item){
					if(isset($coupon_item->order_min_price) &&  $coupon_item->order_min_price<=$order_info->total_price && ($coupon_item->publish_type_code == 'BUY' || strrpos($coupon_item->publish_type_code,$order_info->order_type_code)!== false)){
						//특정매장인지 체크
						if($coupon_item->is_use_store_all || in_array($order_info->store_no,$coupon_item->use_store)){
							if($this->ci->order_model->order_coupon_insert($order_info->user_no,$order_no,$coupon_item->coupon_master_no) !== false){
								$add_push_content .=" [".$coupon_item->coupon_name."]쿠폰이 지급되었습니다.";
							}
						}
					}
				}
			}

			//푸시 발송
			$this->ci->order_model->set_table('user_detail');
			$user_info = $this->ci->order_model->get(array('user_no' => $order_info->user_no),'push_id');
			if($user_info && $user_info->push_id != ''){
				$order_template = array(
					'template_code' => 'USER_ORDER_STATUS',
					'content' => array('status' => $order_info->order_status_text . ''),
				);

				$push_data = $this->ci->firebase->get_message($order_template);


				$push_data = array(
					'to' => $user_info->push_id,
					'title' => $order_info->order_status_text,
					'content' => $push_data->content.$add_push_content,
					'content_id' => $order_info->order_no,
					'target_link' => CLIENT_URL."/orderList/detail?order_no=".$order_info->order_no,
				);
				$push_result = $this->ci->firebase->send((object)$push_data);
			}

			$this->ci->db->trans_commit();

		}

		return $fail_count>0 ? $fail_count : true;
	}

	public function user_lists($params)
	{
		$searchs = array();
		if (isset($params['order_type']) && !is_numeric($params['order_type'])) {
			$params['order_type'] = $this->get_codes(array('parent_code' => 'ORDER_TYPE', 'code' => strtoupper($params['order_type'])))[0]->no;
		}

		if (isset($params['is_lately'])) {
			$params['per_page'] = 1;
		}

		if (isset($params['order_type']) && $params['order_type'] != '') $searchs['and']['o.order_type'] = $params['order_type'];
		if (isset($params['user_no']) && $params['user_no'] != '') $searchs['and']['o.user_no'] = $params['user_no'];

		$data = $this->ci->order_model->user_lists($params['page'], $params['per_page'], $searchs);

		return $data;
	}

	public function cart_add($params)
	{
		//todo 매진처리

		$cart_no = 0;
		$params['order_type'] = $this->get_codes(array('parent_code' => 'ORDER_TYPE', 'code' => strtoupper($params['order_type'])))[0]->no;
		$this->ci->order_model->set_table('cart');
		$cart_data = $this->ci->order_model->get(array('user_no' => $params['user_no'], 'order_type' => $params['order_type']), '*');
		if (!$cart_data) {
			$insert_data = array(
				'user_no'                 => $params['user_no'],
				'store_no'                => $params['store_no'],
				'total_price'             => 0,
				'origin_price'            => 0,
				'delivery_address'        => $params['delivery_address'],
				'delivery_address_detail' => $params['delivery_address_detail'],
				'order_type'              => $params['order_type'],
			);

			$cart_no = $this->ci->order_model->insert($insert_data);

			if ($cart_no < 1) {
				$this->error_message = '카트 담기 중 오류가 발생했습니다.[1]';
				return false;
			}
		} else {
			//todo 수정처리
			$cart_no = $cart_data->cart_no;
		}

		//todo 배달지와 가까운 지점정리


		//제품가격
		$product_data = $this->ci->service_product->detail($params['product_no']);
		if (!$product_data) {
			$this->error_message = '카트 담기 요청 중 오류가 발생했습니다.[제품정보]';
			return false;
		}

		$add_option = array('size' => array(), 'topping' => array());
		$options_price = 0;
		if (isset($params['options'])) {
			foreach ($params['options'] as $option) {
				//토핑
				if (is_int($option)) {
					foreach ($product_data->topping as $topping) {
						if ($topping->no == $option) {
							$add_option['topping'][] = array('name' => $topping->name, 'price' => $topping->price);
							$options_price += $topping->price;
							break;
						}
					}
				} else {
					foreach ($product_data->product_size as $size) {
						if ($size->no == $option) {
							$add_option['size'][] = array('name' => $size->name, 'price' => $size->price);
							$options_price += $size->price;
							break;
						}
					}
				}
			}
		}

		$insert_data = array(
			'cart_no'        => $cart_no,
			'product_no'     => $params['product_no'],
			'order_quantity' => $params['order_quantity'],
			'product_price'  => $product_data->product_price + $options_price,
			'kcal'           => $params['kcal'],
			'size'           => json_encode($add_option['size']),
			'topping'        => json_encode($add_option['topping']),
		);

		// 같은 상품이 존재하면 합치기
		$where_data = array(
			'cart_no'        => $cart_no,
			'product_no'     => $params['product_no'],
			'size'           => json_encode($add_option['size']),
			'topping'        => json_encode($add_option['topping']),
		);

		$this->ci->order_model->set_table('cart_detail');
		$cart_detail = $this->ci->order_model->get($where_data, 'cart_detail_no, order_quantity');
		if($cart_detail){
			$where_data = array(
				'cart_no'        => $cart_no,
				'cart_detail_no' => $cart_detail->cart_detail_no,
			);

			$update_data = array(
				'order_quantity' => $cart_detail->order_quantity + $params['order_quantity'],
			);

			$is_action = $this->ci->order_model->update($update_data, $where_data);
		}
		else{
			$is_action = $this->ci->order_model->insert($insert_data);
		}

		if ($is_action < 1) {
			$this->error_message = '카드 담기 요청 중 오류가 발생했습니다.[2]';
			return false;
		}

		$cart_summary = $this->cart_total_price_set($cart_no);
		if ($cart_summary  === false) {
			return false;
		}

		return $cart_summary->cart_count;
	}

	public function cart_quantity_update($params)
	{
		//todo 매진처리
		$params['order_type'] = $this->get_codes(array('parent_code' => 'ORDER_TYPE', 'code' => strtoupper($params['order_type'])))[0]->no;
		$this->ci->order_model->set_table('cart');
		$cart_data = $this->ci->order_model->get(array('user_no' => $params['user_no'], 'order_type' => $params['order_type']), 'cart_no');
		$update_data = array(
			'order_quantity' => $params['order_quantity'],
		);

		$where_data = array(
			'cart_no'        => $cart_data->cart_no,
			'cart_detail_no' => $params['cart_detail_no'],
		);

		$this->ci->order_model->set_table('cart_detail');
		$cart_detail_no = $this->ci->order_model->update($update_data, $where_data);
		if ($cart_detail_no < 1) {
			$this->error_message = '카드 담기 요청 중 오류가 발생했습니다.[1]';
			return false;
		}

		$cart_summary = $this->cart_total_price_set($cart_data->cart_no);
		if ($cart_summary  === false) {
			return false;
		}

		return true;
	}

	public function cart_update($params)
	{
		$this->ci->order_model->set_table('cart');
		$params['order_type'] = strtoupper($params['order_type']);
		$order_type = $this->get_codes(array('parent_code' => 'ORDER_TYPE', 'code' => $params['order_type']))[0]->no;
		$this->ci->order_model->set_table('cart');
		$cart_data = $this->ci->order_model->get(array('user_no' => $params['user_no'], 'order_type' => $order_type), 'cart_no, payment_type, order_type, origin_price, store_no');

		$update_data = array();

		if($cart_data) {

			if (isset($params['store'])) {
				$update_data['store_no'] = $params['store'];
			}

			if (isset($params['pickup_time'])) {
				$update_data['pickup_time'] = $params['pickup_time'];
			}

			if (isset($params['pay_type'])) {
				$pay_type = strtoupper($params['pay_type']);
				$update_data['etc_data'] = array();
				if (isset($params['etc_data']) && $pay_type == 'STOREPAY_CASH') {
					if($params['etc_data']['cash_receipts']){
						$update_data['etc_data']['cash_receipts'] = $params['etc_data']['use_bill_5thousand'];
						$update_data['etc_data']['cash_receipts_phone'] = $params['etc_data']['cash_receipts_phone'];
					}

					if($params['etc_data']['use_bill_5thousand']){
						$update_data['etc_data']['use_bill_5thousand'] = $params['etc_data']['use_bill_5thousand'];
					}
				}

				$update_data['etc_data'] = json_encode($update_data['etc_data']);

				$params['pay_type'] = $this->get_codes(array('parent_code' => 'PAYMENT_TYPE', 'code' => $pay_type))[0]->no;
				if ($params['pay_type'] != $cart_data->payment_type) {
					$update_data['payment_type'] = $params['pay_type'];
				}

			}

			if (isset($params['request'])) {
				$update_data['request_memo'] = $params['request']['memo'];
				//메모저장
				$user_request_memo = $params['request']['memo'];
				if (!$params['request']['save']) {
					$user_request_memo = '';
				}

				$this->ci->load->model('service/user_model');
				$this->ci->user_model->set_table('user_detail');
				$this->ci->user_model->update(array('order_memo' => $user_request_memo), array('user_no' => $params['user_no']));
			}


			//쿠폰유효성 체크
			if (isset($params['coupon']) && $params['coupon'] > 0) {
				$this->ci->load->library('service/service_user');
				$coupon_params = array(
					'coupon_no'  => $params['coupon'],
					'order_type' => $cart_data->order_type,
				);

				$user_coupon = $this->ci->service_user->use_coupon_check($params['user_no'], $coupon_params);
				if ($user_coupon === false) {
					$_error = $this->ci->service_user->get_error();
					$this->error_message = $_error->message;
					$this->error_code = $_error->code;
					return false;
				}

				$update_data['user_coupon_no'] = $user_coupon[0]->user_coupon_no;
				$update_data['total_price'] = $cart_data->origin_price - $user_coupon[0]->discount_price;

			}

			$update_data['delivery_price'] = 0;
			if ($params['order_type'] == 'DELIVERY') {
				$this->ci->order_model->set_table('code');
				$store_data = $this->ci->order_model->get(array('no' => $cart_data->store_no), 'etc_data')->etc_data;

				$store_data = json_decode($store_data);
				$update_data['delivery_price'] = $store_data->delivery_price * 1;
				if (isset($update_data['total_price'])) {
					$update_data['total_price'] = $update_data['total_price'] + ($store_data->delivery_price * 1);
				} else {
					$update_data['total_price'] = $cart_data->origin_price + ($store_data->delivery_price * 1);
				}
			}

			$where_data = array(
				'cart_no' => $cart_data->cart_no,
			);

			$this->ci->order_model->set_table('cart');
			$cart_detail_no = $this->ci->order_model->update($update_data, $where_data);
			if ($cart_detail_no < 1) {
				$this->error_message = '카드 담기 요청 중 오류가 발생했습니다.[1]';
				return false;
			}

		}
		return true;
	}

	public function cart_delete($params)
	{
		$cart_count = 0;
		$this->ci->order_model->set_table('cart');

		if (isset($params['cart_detail_nos']) && count($params['cart_detail_nos']) > 0) {
			if (isset($params['order_type']) && !is_numeric($params['order_type'])) {
				$params['order_type'] = $this->get_codes(array('parent_code' => 'ORDER_TYPE', 'code' => strtoupper($params['order_type'])))[0]->no;
			}

			$this->ci->order_model->set_table('cart');
			$cart_data = $this->ci->order_model->get(array('user_no' => $params['user_no'], 'order_type' => $params['order_type']), 'cart_no');
			$where_data = array(
				'cart_no' => $cart_data->cart_no,
			);

			$this->ci->order_model->set_table('cart_detail');
			$this->ci->db->where_in('cart_detail_no', $params['cart_detail_nos']);
			$this->ci->order_model->delete($where_data);

			$cart_summary = $this->cart_total_price_set($cart_data->cart_no);
			if ($cart_summary  === false) {
				return false;
			}
			$cart_count = $cart_summary->cart_count;

		} else {
			$this->ci->order_model->set_table('cart');
			$cart_list = $this->ci->order_model->list(array('user_no' => $params['user_no']), 'cart_no');
			foreach ($cart_list as $item){
				$where_data = array('cart_no' => $item->cart_no);
				$this->ci->order_model->delete($where_data);
				$this->ci->order_model->set_table('cart_detail');
				$this->ci->order_model->delete($where_data);
			}
		}

		return $cart_count;
	}

	public function delivery_address_create($params)
	{
		$this->ci->order_model->set_table('user_delivery_address');

		$insert_data = array(
			'user_no'        => $params['user_no'],
			'address'        => $params['delivery_address'],
			'address_detail' => $params['delivery_address_detail'],
		);

		$data_no = $this->ci->order_model->insert($insert_data);

		if ($data_no < 1) {
			$this->error_message = '배달지 주소 등록 중 오류가 발생했습니다.';
			return false;
		}

		return $data_no;
	}

	public function delivery_address_get($params)
	{
		$where_data = array('user_no' => $params['user_no']);
		if ($params['user_delivery_address_no'] > 0) {
			$where_data['user_delivery_address_no'] = $params['user_delivery_address_no'];
		}

		$this->ci->order_model->set_table('user_delivery_address');
		$address_data = $this->ci->order_model->get($where_data, 'user_delivery_address_no as no,address,address_detail');

		//todo 주소 기준으로 매장가져오기
		$store = $this->get_codes(array('parent_code' => 'STORE'))[0];

		$store_data = array(
			'no'   => $store->no,
			'name' => $store->name,
		);

		return array('address' => $address_data, 'store' => $store_data);
	}

	public function cart_get($mode, $params)
	{
		$params['order_type'] = strtoupper($params['order_type']);
		$order_type = $this->get_codes(array('parent_code' => 'ORDER_TYPE', 'code' => $params['order_type']))[0]->no;

		$ret = array();
		$searchs = array();
		$searchs['and']['user_no'] = $params['user_no'];
		$searchs['and']['order_type'] = $order_type;
		$searchs['and']['c.store_no'] = $params['store'];
		$ret['menus'] = $this->ci->order_model->cart_lists($searchs);

		foreach ($ret['menus'] as $key => $menu) {
			$ret['menus'][$key]->topping = json_decode($menu->topping);
			$ret['menus'][$key]->size = json_decode($menu->size);
			$_item = array();
			if(isset($ret['menus'][$key]->topping)){
				foreach ($ret['menus'][$key]->topping as $item) {
					$_item[] = $item->name . '(' . number_format($item->price) . '원)';
				}
			}

			$ret['menus'][$key]->topping = implode(' / ', $_item);

			$_item = array();
			if(isset($ret['menus'][$key]->size)) {
				foreach ($ret['menus'][$key]->size as $item) {
					$_item[] = $item->name . '(' . number_format($item->price) . '원)';
				}
			}

			$ret['menus'][$key]->size = implode(' / ', $_item);
		}

		if ($mode == 'ready') {
			$this->ci->load->library('service/service_user');

			$ret['request']['memo'] = $this->ci->service_user->request_memo($params['user_no']);
			$ret['request']['save'] = $ret['request']['memo'] ? true : false;

			$ret['store']['delivery_price'] = 0;
			if($params['order_type'] == 'DELIVERY'){
				$this->ci->order_model->set_table('cart');
				$cart_data = $this->ci->order_model->get(array('user_no' => $params['user_no'], 'order_type' => $order_type), 'store_no');

				$this->ci->order_model->set_table('code');
				$store_data = $this->ci->order_model->get(array('no' => $cart_data->store_no), 'etc_data')->etc_data;
				$store_data = json_decode($store_data);
				$ret['store']['delivery_price'] = $store_data->delivery_price;
			}

		}

		return $ret;
	}

	public function store_get()
	{
		$ret = array();
		$data = $this->get_codes(array('parent_code' => 'STORE', 'is_view' => 1));

		foreach ($data as $key => $item) {
			$ret[$key]['info'] = json_decode($item->etc_data);
			$ret[$key]['no'] = $item->no;
			$ret[$key]['name'] = $item->name;
		}

		return $ret;
	}

	protected function cart_total_price_set($cart_no){
		$data = $this->ci->order_model->get(array('cart_no' => $cart_no), 'count(*) as cart_count, sum(product_price*order_quantity) as total_price');

		//총금액 수정
		$this->ci->order_model->set_table('cart');
		$update_check = $this->ci->order_model->update(array('total_price' => $data->total_price,'origin_price' => $data->total_price), array('cart_no' => $cart_no));
		if ($update_check < 1) {
			$this->error_message = '카드 담기 요청 중 오류가 발생했습니다.[3]';
			return false;
		}

		return $data;
	}

	public function event_list_get($params){
		return $this->ci->order_model->event_list($params);
	}

	public function order_request_sms($order_no){

		$this->ci->load->library('common/sms');
		$sms_template = array(
			'template_code' => 'ORDER_ADMIN_SMS',
		);

		$sms_data = $this->ci->sms->get_message($sms_template);

		if($sms_data === false){
			$sms_error = $this->ci->sms->get_error();
			$this->error_code = $sms_error->code;
			$this->error_message = $sms_error->message;
			return false;
		}

		$this->ci->order_model->set_table('order');
		$order_info = $this->ci->order_model->get(array('order_no' => $order_no), 'store_no');
		$this->ci->order_model->set_table('code');
		$store_data = $this->ci->order_model->get(array('no' => $order_info->store_no), 'etc_data')->etc_data;
		$store_data = json_decode($store_data);
		$sms_data->to_phone = $store_data->phone;
		if($sms_data->to_phone){
			$sms_result = $this->ci->sms->send((object)$sms_data);

			if (!$sms_result) {
				$sms_error = $this->ci->sms->get_error();
				$this->error_code = $sms_error->code;
				$this->error_message = $sms_error->message;
				return false;
			}
		}


		return true;
	}

}
