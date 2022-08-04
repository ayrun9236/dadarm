<?php

/**
 * Created by PhpStorm.
 * User: SIL
 * Date: 2021-02-05 오후 9:37
 */
class Order extends CI_Controller
{

	function __construct()
	{
		parent::__construct();
		$this->load->model('service/code_model');
	}


	/**
	 * 배달완료 처리
	 */
	function delivery_end()
	{
		$ch = curl_init();
		$url = 'https://api.odcloud.kr/api/15066351/v1/uddi:cf3fa47b-fba7-4c54-8b3e-e0a94d4da3b6?page=3&perPage=1000&serviceKey=XjIdhkEUE4akj1Yo2HmTzT4LS8Z2rHF9V%2FWuB6JvW3npLHml89Ddjz6SVIUnzszAsuc2jPM5thQwlN28j7TR6Q%3D%3D'; /*URL*/

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
		$response = curl_exec($ch);
		curl_close($ch);


		$api_data = json_decode($response);
		foreach ($api_data->data as $value) {
			$insert_data[] = array(
				'adress_sido'    => $value->도시군구,
				'title'          => $value->{'제공 기관명'},
				'address_detail' => $value->주소,
			);

		}

		$this->db->insert_batch('center', $insert_data);

		//print_r($api_data) ;

	}

	function center_address_set(){

		$sql = "
SELECT 
    address_detail,center_no
FROM
    `center`
where location_lat=0
";

		$list = $this->general->db_convert_result($this->db->query($sql));
		$this->code_model->set_table('center');
		foreach ($list as $value) {

			$address = explode(',',$value->address_detail);
			//print_r($address);
			$address = urlencode($value->address_detail);
			//$address = ($address[0]);
			$url = "https://naveropenapi.apigw.ntruss.com/map-geocode/v2/geocode?query=".$address;
			$headers = array();
			$headers[] ="X-NCP-APIGW-API-KEY-ID:".NAVER_MAP['X-NCP-APIGW-API-KEY-ID'];
			$headers[] ="X-NCP-APIGW-API-KEY:".NAVER_MAP['X-NCP-APIGW-API-KEY'];

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$result = curl_exec($ch);
			curl_close($ch);
			$result = json_decode($result);
			//print_r($result);
			$address_location = array('lat' => 0, 'lng' => 0);
			if($result->status == 'OK' && $result->meta->totalCount > 0){
				$address_location = array('lat' => $result->addresses[0]->y*1, 'lng' => $result->addresses[0]->x*1);
			}

			$update_data = array(
				'location_lng' => $address_location['lng'],
				'location_lat' => $address_location['lat']
			);

			$this->code_model->update($update_data,array('center_no' => $value->center_no));
		}

	}


	function center_tel_set($page){

		$sql = "
SELECT 
    address_detail,center_no,title
FROM
    `center`
where location_lat!=0 and center_no between $page and ($page+100) and (tel is null) and is_check in (0,2,3)
";

		$list = $this->general->db_convert_result($this->db->query($sql));
		$this->code_model->set_table('center');
		foreach ($list as $value) {


			$client_id = "0kYpXz9JsiyNelWIAcyj";
			$client_secret = "Tnun13U4te";
			$encText = urlencode($value->title);
			$url = "https://openapi.naver.com/v1/search/local.json?query=".$encText; // json 결과
			$is_post = false;
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_POST, $is_post);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$headers = array();
			$headers[] = "X-Naver-Client-Id: ".$client_id;
			$headers[] = "X-Naver-Client-Secret: ".$client_secret;
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			$response = curl_exec ($ch);
			$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

			curl_close ($ch);

			$result = json_decode($response);
			if($status_code != 200) {
				continue;
			}

			$tel = '';
			$home = '';
			$use = '';

			$update_data = array('is_check' => '1');
			if(isset($result->items[0]) && strrpos(str_replace(' ','',$value->address_detail),str_replace(' ','',$result->items[0]->roadAddress)) !== false ){
				$tel = $result->items[0]->telephone;
				$home = $result->items[0]->link;
				//$use = $result->result->place->list[0]->bizhourInfo;
			}

			if($use != ''){
				$update_data['use_time'] = $use;
			}

			if($home != ''){
				$update_data['homepage'] = $home;
			}

			if($tel != ''){
				$update_data['tel'] = $tel;
			}

			if(count($update_data)> 1){
				$this->code_model->update($update_data,array('center_no' => $value->center_no));
				print_r(array_merge($update_data,array('center_no' => $value->center_no)));
			}
			else{
				print_r($result);
				$this->code_model->update(array('is_check' => '4'),array('center_no' => $value->center_no));
			}

		}

	}


	function tel_set($page){

		$sql = "
SELECT 
    address_detail,center_no,title
FROM
    `center`
where location_lat!=0 and center_no between $page and ($page+10) and (tel is null) and is_check in (0,2,3)
";

		$list = $this->general->db_convert_result($this->db->query($sql));
		$this->code_model->set_table('center');
		foreach ($list as $value) {

			$address = explode(',',$value->address_detail);
			//print_r($address);
			$address = urlencode($value->title);
			//$address = ($address[0]);
			$url = "https://map.naver.com/v5/api/search?caller=pcweb&query=".$address."&type=all&searchCoord=126.9304769;37.5545852&page=1&displayCount=20&isPlaceRecommendationReplace=true&lang=ko";
			$headers = array();
			$client_id = "0kYpXz9JsiyNelWIAcyj";
			$client_secret = "Tnun13U4te";
			$headers[] = "X-Naver-Client-Id: ".$client_id;
			$headers[] = "X-Naver-Client-Secret: ".$client_secret;

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$result1 = curl_exec($ch);
			curl_close($ch);
			$result = json_decode($result1);



			$tel = '';
			$home = '';
			$use = '';

			$update_data = array('is_check' => '1');
			if(isset($result->result->place->list[0])){
				$tel = $result->result->place->list[0]->tel;
				$home = $result->result->place->list[0]->homePage;
				$use = $result->result->place->list[0]->bizhourInfo;
			}
			elseif(isset($result->tel)){
				$tel = $result->tel;
				$home = $result->homePage;
				$use = $result->bizhourInfo;
			}

			if($use){
				$update_data['use_time'] = $use;
			}

			if($home){
				$update_data['homepage'] = $home;
			}

			if($tel){
				$update_data['tel'] = $tel;
			}

			if(count($update_data)> 1){
				$this->code_model->update($update_data,array('center_no' => $value->center_no));

			}
			else{

				echo $result1;
				print_r($result);
				echo 'error';
				$this->code_model->update(array('is_check' => '5'),array('center_no' => $value->center_no));
			}

		}

	}

	function test(){

		$client_id = "0kYpXz9JsiyNelWIAcyj";
		$client_secret = "Tnun13U4te";
		$encText = urlencode("하원아동발달연구소");
		$url = "https://openapi.naver.com/v1/search/local.json?query=".$encText; // json 결과
		$is_post = false;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, $is_post);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$headers = array();
		$headers[] = "X-Naver-Client-Id: ".$client_id;
		$headers[] = "X-Naver-Client-Secret: ".$client_secret;
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		$response = curl_exec ($ch);
		$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		echo "status_code:".$status_code."
";
		curl_close ($ch);
		if($status_code == 200) {
			echo $response;
		} else {
			echo "Error 내용:".$response;
		}

}
}