<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Service_product extends Service_common
{
	protected $ci;
	protected $error_code = null;
	protected $error_message = null;
	protected $product_size_keys = array();

	public function __construct()
	{
		$this->ci =& get_instance();

		$this->product_size_keys = array('A','B','C','D','E','F','G','F');
		$this->ci->load->model('service/product_model');
		$this->ci->load->library('common/image_upload');
	}

	public function detail($product_no, $store = 0)
	{
		$ret = array();
		$searchs = array();
		$searchs['and']['product_no'] = $product_no;

		$data = $this->ci->product_model->lists(1, 1, $searchs);
		if($data['total_count'] > 0){
			$ret = $data['list'][0];
			if($ret->product_size == '' || $ret->product_size == null){
				$ret->product_size = "[]";
			}

			$ret->product_size = json_decode($ret->product_size);
			$ret->topping = array();
			if($ret->product_use_topping){
				if($store == 0){
					$ret->topping = $this->get_codes(array('parent_code' => 'PRODUCT_TOPPING'));
					foreach ($ret->topping as $key => $item){
						$etc_data = json_decode($item->etc_data);
						$ret->topping[$key]->price = $etc_data->price;
						$ret->topping[$key]->soldout = false;
						unset($ret->topping[$key]->etc_data);
					}
				}
				else{
					$ret->topping = $this->store_topping_lists(array('page'=>1, 'per_page'=> 50,'is_view' => 1, 'store' => $store))['list'];
					foreach ($ret->topping as $key => $item){
						$ret->topping[$key]['soldout'] = $item['is_soldout'];
					}
				}
			}
		}

		return $ret;
	}

	public function create($params)
	{
		$prodict_no = 0;

		$product_sizes = array();
		if($params['product_size'] !=""){
			$product_size = explode("\n",$params['product_size']);
			foreach ($product_size as $key => $item){
				$_val = explode(":",$item);
				$product_sizes[] = array('no' => $this->product_size_keys[$key], 'name' => $_val[0], 'price' => $_val[1]*1, 'kcal' => isset($_val[2]) ? $_val[2]*1 : 0);
			}

			$product_sizes = $product_sizes;
		}

		$this->ci->product_model->set_table('product');
		$insert_data = array(
			'product_name' => $params['product_name'],
			'product_eng_name' => $params['product_eng_name'],
			'product_type' => $params['product_type'],
			'product_price' => $params['product_price'],
			'product_desc' => isset($params['product_desc']) ? $params['product_desc'] : '' ,
			'product_is_view' => $params['product_is_view'],
			'product_use_topping' => $params['product_use_topping'],
			'product_kcal' => $params['product_kcal'],
			'product_sort' => $params['product_sort'],
			'product_size' => json_encode($product_sizes),
		);

		$prodict_no = $this->ci->product_model->insert($insert_data);

		if ($prodict_no > 0 && count($_FILES) > 0) {
			if (isset($_FILES['product_image'])) {
				$upload_result = $this->ci->image_upload->upload('product', $prodict_no, 'product_image', 'new');
				if ($upload_result === false) {
					$this->error_message = '첨부파일 업로드 시 오류가 발생했습니다.';
					return false;
				}
			}
		}
		return $prodict_no;
	}

	public function modify($product_no, $params)
	{
		$this->ci->product_model->set_table('product');

		$product_sizes = array();
		if($params['product_size'] !=""){
			$product_size = explode("\n",$params['product_size']);
			foreach ($product_size as $key => $item){
				$_val = explode(":",$item);
				if(count($_val) == 3){
					$product_sizes[] = array('no' => $this->product_size_keys[$key], 'name' => $_val[0], 'price' => $_val[1]*1, 'kcal' => $_val[2]*1);
				}
			}

			$product_sizes = $product_sizes;
		}

		$update_data = array(
			'product_name' => $params['product_name'],
			'product_eng_name' => $params['product_eng_name'],
			'product_type' => $params['product_type'],
			'product_price' => $params['product_price'],
			'product_desc' => $params['product_desc'],
			'product_is_view' => $params['product_is_view'],
			'product_use_topping' => $params['product_use_topping'],
			'product_kcal' => $params['product_kcal'],
			'product_size' => json_encode($product_sizes),
			'product_sort' => $params['product_sort'],
		);

		$is_update = $this->ci->product_model->update($update_data, array('product_no' =>$product_no ));
		if(!$is_update){
			$this->error_message = '수정 중 오류가 발생했습니다.';
			return false;
		}
		if (count($_FILES) > 0) {
			if (isset($_FILES['product_image'])) {
				$upload_result = $this->ci->image_upload->upload('product', $product_no, 'product_image', 'new');
				if ($upload_result === false) {
					$this->error_message = '첨부파일 업로드 시 오류가 발생했습니다.';
					return false;
				}
			}
		}
		return $is_update;
	}


	public function delete($product_no)
	{
		//todo db 제약키 걸기
		$this->ci->product_model->set_table('product');
		$is_update = $this->ci->product_model->delete(array('product_no' =>$product_no ));

		$this->ci->image_upload->delete('product', $product_no);
		return $is_update;
	}

	public function menu_categorys()
	{
		return $this->get_codes(array('parent_code' => 'PRODUCT_TYPE'));
	}

	public function lists($params){
		$searchs = array();

		if(isset($params['product_type']) && $params['product_type']!="" && !is_numeric($params['product_type'])){
			$params['product_type'] = $this->get_codes(array('parent_code' => 'PRODUCT_TYPE', 'code' => strtoupper($params['product_type'])))[0]->no;
		}

		if (isset($params['product_type']) && $params['product_type'] != '') $searchs['and']['product_type'] = $params['product_type'];
		if (isset($params['product_name']) && $params['product_name']) {
			$searchs['like']['or'][]  = array(
				'product_name' => $params['product_name'], 'product_eng_name' => $params['product_name'],
			);
		}

		if (isset($params['is_view'])) $searchs['and']['product_is_view'] = 1;
		if (isset($params['product_no'])) $searchs['and']['product_no'] = $params['product_no'];

		//todo 아웃풋 필드, 정리
		$data = $this->ci->product_model->lists($params['page'], $params['per_page']*1, $searchs);
		foreach ($data['list'] as $key => $item) {
			if ($item->product_size) {
				$product_size = json_decode($item->product_size);

				$data['list'][$key]->product_size = array();
				foreach ($product_size as $sub_item) {
					$data['list'][$key]->product_size[] = $sub_item->name.':'.$sub_item->price.':'.$sub_item->kcal;
				}

				$data['list'][$key]->product_size = implode("\n", $data['list'][$key]->product_size);
			}
		}

		return $data;
	}


	public function store_product_lists($params){
		$searchs = array();

		if(isset($params['product_type']) && $params['product_type'] && !is_numeric($params['product_type'])){
			$params['product_type'] = $this->get_codes(array('parent_code' => 'PRODUCT_TYPE', 'code' => strtoupper($params['product_type'])))[0]->no;
		}

		if (isset($params['product_type']) && $params['product_type'] != '') $searchs['and']['product_type'] = $params['product_type'];
		if (isset($params['product_name']) && $params['product_name']) {
			$searchs['like']['or'][]  = array(
				'product_name' => $params['product_name'], 'product_eng_name' => $params['product_name'],
			);
		}

		if (isset($params['is_view'])) $searchs['and']['ps.store_no'] = $params['store'];

		$data = $this->ci->product_model->store_product_lists($params['page'], $params['per_page'], $params['store'], $searchs);

		return $data;
	}

	public function store_product_modify($params)
	{

		if($params['mode'] == 'view'){
			//추가/삭제
			if($params['mode_value'] == 1){
				$is_action = $this->ci->product_model->store_product_add($params['store'], $params['product_nos']);
			}
			else{
				$is_action = $this->ci->product_model->store_product_delete($params['store'], $params['product_nos']);
			}
		}
		else if($params['mode'] == 'soldout'){
			$is_action = $this->ci->product_model->store_product_soldout($params['store'], $params['product_nos'], $params['mode_value']);
		}

		return $is_action;
	}

	public function store_lists($params){
		$searchs = array();
		if (isset($params['store']) && $params['store']>0) $searchs['and']['no'] = $params['store'];

		$data = $this->ci->product_model->store_lists($params['page'], $params['per_page'], $searchs);
		foreach ($data['list'] as $key => $item){
			$data['list'][$key] = array_merge((array)$item, (array)json_decode($item->etc_data));
			unset($data['list'][$key]->etc_data);
		}

		return $data;
	}


	public function store_create($params)
	{
		$store_no = 0;

		$this->ci->product_model->set_table('code');
		$parent_no = $this->ci->product_model->get(array('code' => 'STORE', 'parent_no' => 0), 'no')->no;
		$insert_data = array(
			'parent_no' => $parent_no,
			'name' => $params['name'],
			'code' => $params['code'],
			'is_view' => $params['is_view'] === false ? 0 : 1,
		);

		$insert_data['etc_data'] = json_encode(array(
			'use_time' => $params['use_time'],
			'delivery_price' => $params['delivery_price'],
			'phone' => $params['phone'],
			'tel' => $params['tel'],
			'latitude' => $params['latitude'],
			'longitude' => $params['longitude'],
			'address' => $params['address']));

		$store_no = $this->ci->product_model->insert($insert_data);

		return $store_no;
	}

	public function store_delete($store_no)
	{
		$this->ci->product_model->set_table('code');
		$is_update = $this->ci->product_model->delete(array('no' =>$store_no ));
		return $is_update;
	}

	public function store_modify($store_no, $params)
	{

		$update_data = array(
			'name' => $params['name'],
			'code' => $params['code'],
			'is_view' => $params['is_view'] === false ? 0 : 1,
		);

		$update_data['etc_data'] = json_encode(array(
			'use_time' => $params['use_time'],
			'delivery_price' => $params['delivery_price'],
			'phone' => $params['phone'],
			'tel' => $params['tel'],
			'latitude' => $params['latitude'],
			'longitude' => $params['longitude'],
			'address' => $params['address']));
		$this->ci->product_model->set_table('code');
		$store_no = $this->ci->product_model->update($update_data, array('no' => $store_no));

		return $store_no;
	}

	public function topping_lists($params){
		$searchs = array();
		if (isset($params['store_name']) && $params['store_name']) {
			$searchs['like']['or'][]  = array('name' => $params['store_name']);
		}

		$data = $this->ci->product_model->topping_lists($params['page'], $params['per_page'], $searchs);
		foreach ($data['list'] as $key => $item){
			$data['list'][$key] = array_merge((array)$item, (array)json_decode($item->etc_data));
			unset($data['list'][$key]->etc_data);
		}

		return $data;
	}


	public function topping_create($params)
	{
		$this->ci->product_model->set_table('code');
		$parent_no = $this->ci->product_model->get(array('code' => 'PRODUCT_TOPPING', 'parent_no' => 0), 'no')->no;
		$insert_data = array(
			'parent_no' => $parent_no,
			'name' => $params['name'],
			'code' => $params['code'],
		);

		$insert_data['etc_data'] = json_encode(array(
			'price' => $params['price']*1,
			'kcal' => $params['kcal']*1,
		));

		return $this->ci->product_model->insert($insert_data);
	}

	public function topping_modify($topping_no, $params)
	{

		$update_data = array(
			'name' => $params['name'],
			'code' => $params['code'],
		);

		$update_data['etc_data'] = json_encode(array(
			'price' => $params['price']*1,
			'kcal' => $params['kcal']*1,
		));

		$this->ci->product_model->set_table('code');
		$topping_no = $this->ci->product_model->update($update_data, array('no' => $topping_no));

		return $topping_no;
	}


	public function store_topping_modify($params)
	{

		if($params['mode'] == 'view'){
			//추가/삭제
			if($params['mode_value'] == 1){
				$is_action = $this->ci->product_model->store_topping_add($params['store'], $params['product_nos']);
			}
			else{
				$is_action = $this->ci->product_model->store_topping_delete($params['store'], $params['product_nos']);
			}
		}
		else if($params['mode'] == 'soldout'){
			$is_action = $this->ci->product_model->store_topping_soldout($params['store'], $params['product_nos'], $params['mode_value']);
		}

		return $is_action;
	}

	public function store_topping_lists($params){
		$searchs = array();

		if (isset($params['is_view'])) $searchs['and']['ps.store_no'] = $params['store'];
		$data = $this->ci->product_model->store_topping_lists($params['page'], $params['per_page'], $params['store'], $searchs);
		foreach ($data['list'] as $key => $item){
			$data['list'][$key] = array_merge((array)$item, (array)json_decode($item->etc_data));
			unset($data['list'][$key]->etc_data);
		}

		return $data;
	}
}
