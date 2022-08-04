<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Service_coupon extends Service_common
{
	protected $ci;
	protected $error_code = null;
	protected $error_message = null;

	public function __construct()
	{
		$this->ci =& get_instance();

		$this->ci->load->model('service/coupon_model');
		$this->ci->load->library('common/image_upload');
	}

	public function detail($coupon_no, $mode = '')
	{
		$ret = array();
		$searchs = array();
		$searchs['and']['coupon_master_no'] = $coupon_no;
		$data = $this->ci->coupon_model->lists(1, 1, $searchs);
		$ret = $data['list'][0];

		return $ret;
	}

	public function create($params)
	{
		$coupon_no = 0;
		$this->ci->coupon_model->set_table('coupon_master');

		$insert_data = array(
			'order_type' => $params['order_type'],
			'publish_type' => $params['publish_type'],
			'coupon_name' => $params['coupon_name'],
			'use_sdate' => $params['use_sdate'],
			'use_edate' => $params['use_edate'],
			'gifts' => $params['gifts'],
			'discount_price' => $params['discount_price'],
			'order_min_price' => $params['order_min_price'],
			'is_use_store_all' => $params['is_use_store_all'] === 'true' ? 1 : 0,
			'use_store' => $params['use_store'],
		);

		if($params['is_use_store_all'] === 'true'){
			$insert_data['use_store'] = '';
		}

		$coupon_no = $this->ci->coupon_model->insert($insert_data);
		if ($coupon_no > 0 && count($_FILES) > 0) {
			if (isset($_FILES['coupon_image'])) {
				$upload_result = $this->ci->image_upload->upload('coupon_master', $coupon_no, 'coupon_image', 'new');
				if ($upload_result === false) {
					$this->error_message = '첨부파일 업로드 시 오류가 발생했습니다.';
					return false;
				}
			}
		}
		return $coupon_no;
	}

	public function update($coupon_no, $params)
	{
		$update_data = array(
			'order_type' => $params['order_type'],
			'publish_type' => $params['publish_type'],
			'coupon_name' => $params['coupon_name'],
			'use_sdate' => $params['use_sdate'],
			'use_edate' => $params['use_edate'],
			'gifts' => $params['gifts'],
			'discount_price' => $params['discount_price'],
			'order_min_price' => $params['order_min_price'],
			'is_use_store_all' => $params['is_use_store_all'] === 'true' ? 1 : 0,
			'use_store' => $params['use_store'],
		);

		if($params['is_use_store_all'] === 'true'){
			$update_data['use_store'] = '';
		}

		$this->ci->coupon_model->set_table('coupon_master');
		$is_update = $this->ci->coupon_model->update($update_data, array('coupon_master_no' =>$coupon_no ));
		if(!$is_update){
			$this->error_message = '수정 중 오류가 발생했습니다.';
			return false;
		}

		if ($is_update > 0 && count($_FILES) > 0) {
			if (isset($_FILES['coupon_image'])) {
				$upload_result = $this->ci->image_upload->upload('coupon_master', $coupon_no, 'coupon_image', 'new');
				if ($upload_result === false) {
					$this->error_message = '첨부파일 업로드 시 오류가 발생했습니다.';
					return false;
				}
			}
		}

		return $is_update;
	}


	public function delete($coupon_no)
	{
		$this->ci->coupon_model->set_table('coupon_master');
		$is_delete = $this->ci->coupon_model->delete(array('coupon_master_no' =>$coupon_no ));

		$this->ci->image_upload->delete('coupon_master', $coupon_no);
		return $is_delete;
	}


	public function lists($params){
		$searchs = array();

		if (isset($params['type']) && $params['type']!= '') {
			$params['type'] = $this->get_codes(array('parent_code' => 'coupon_TYPE', 'code' => strtoupper($params['type'])))[0]->no;
			$searchs['and']['coupon_type'] = $params['type'];
		}

		if (isset($params['title']) && $params['title']!= '') $searchs['like']['and']['title'] = $params['title'];
		if (isset($params['sdate']) && $params['sdate'] && $params['edate']) {
			$searchs['between'][] = array(
				'insert_dt' => array($params['sdate'], $params['edate']),
			);
		}

		$data = $this->ci->coupon_model->lists($params['page'], $params['per_page'], $searchs);
		foreach ($data['list'] as $key => $item){
			if($item->use_store){
				$data['list'][$key]->use_store = explode(',',$item->use_store);
				$temp_store = array();
				foreach ($data['list'][$key]->use_store as $sub_key => $sub_item){
					$temp_store[] = $this->get_codes(array('parent_code' => 'STORE', 'no' => $sub_item))[0]->name;
					$data['list'][$key]->use_store[$sub_key] = $sub_item*1;
				}

				$data['list'][$key]->use_store_text = implode(',',$temp_store);
			}
			else{
				$data['list'][$key]->use_store = [];
				$data['list'][$key]->use_store_text = '전매장';
			}
		}

		return $data;
	}
}
