<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class General
{
    protected $ci;

    function __construct()
    {
        $this->ci =& get_instance();
    }
 
    // 디비 형태 변경
    // 참조 http://mysql-python.sourceforge.net/MySQLdb-1.2.2/public/MySQLdb.constants.FIELD_TYPE-module.html
    function db_convert_result($query, $grouping = array())
    {
        $fields = $query->field_data();
        $query_result = $query->result();

        $group_result = array();

        if ($query_result) {
            foreach ($query_result as $key => $qr) {
                foreach ($fields as $field) {
                    $field_name = $field->name;
                    $field_type = $field->type;

                    switch ($field_type) {
                        case 1: // Tiny int
                        case 2: // Small int
                        case 3: // Int
                        case 8: // Big int
                        case 9: // Medium int
						case 'tinyint':
						case 'smallint':
						case 'int':
                            $query_result[$key]->{$field_name} = intval($query_result[$key]->{$field_name});
                            break;
                        case 4: // Float
                        case 5: // Double
                        case 246: // NEWDECIMAL
                        case 'float':
                        case 'double':
                            $query_result[$key]->{$field_name} = floatval($query_result[$key]->{$field_name});
                            break;
                    }

                    if ($field_name == 'thumb' || $field_name == 'original' || strrpos($field_name, 'image' ) !== false) {
                        if (strlen($query_result[$key]->{$field_name}) > 0 && substr($query_result[$key]->{$field_name}, 0, 4) != 'http') {
							$query_result[$key]->{$field_name} = UPLOAD['S3_URL'] . $query_result[$key]->{$field_name};
                        }
						else {
							if(strrpos($field_name, '_no_' ) === false){
								$query_result[$key]->{$field_name} = UPLOAD['S3_URL'].UPLOAD['NO_IMG'];
							}
						}
                    }

                    // 핸드폰 번호
                    if ($field_name == 'phone' || substr($field_name, -6) == '_phone') {
                        if (strlen($query_result[$key]->{$field_name}) > 0 && strpos($query_result[$key]->{$field_name}, '-') === FALSE) {
                            $query_result[$key]->{$field_name} = preg_replace("/([0-9]{3})([0-9]{3,4})([0-9]{4})$/", "\\1-\\2-\\3", $query_result[$key]->{$field_name});
                        }
                    }

                    // grouping
                    if (count($grouping) > 0) {
                        $group_check = false;
                        foreach ($grouping as $group_name) {

                            if (stripos($field_name, $group_name . '_') === 0) {
                                $field_new_name = substr($field_name, strlen($group_name) + 1);
                                $group_result[$key][$group_name][$field_new_name] = $query_result[$key]->{$field_name};

                                if ($field_new_name == 'thumb' || $field_new_name == 'original' || $field_new_name == 'image') {
                                    if (strlen($query_result[$key]->{$field_name}) > 0 && substr($query_result[$key]->{$field_name}, 0, 4) != 'http') {
										$group_result[$key][$group_name][$field_new_name] = UPLOAD['S3_URL'] . $query_result[$key]->{$field_name};
                                    }
									else{
										$group_result[$key][$group_name][$field_new_name] = UPLOAD['S3_URL'].UPLOAD['NO_IMG'];
									}
                                }


                                $group_check = true;
                            }
                        }

                        if (!$group_check) {
                            $group_result[$key][$field_name] = $query_result[$key]->{$field_name};

                            if ($field_name == 'mini' || $field_name == 'thumb' || $field_name == 'original' || $field_name == 'image') {
                                if (strlen($query_result[$key]->{$field_name}) > 0 && substr($query_result[$key]->{$field_name}, 0, 4) != 'http') {
									$group_result[$key][$field_name] = UPLOAD['S3_URL'] . $query_result[$key]->{$field_name};
                                }
								else{
									$group_result[$key][$field_name] = UPLOAD['S3_URL'].UPLOAD['NO_IMG'];

								}
                            }
                        }
                    }
                }

            }

        }

        if (count($group_result)) return $group_result;
        else return $query_result;

    }


    function db_convert_row($query, $grouping = array())
    {
        $fields = $query->field_data();
        $query_result = $query->row();
        $group_result = array();

        if ($query_result) {
            foreach ($fields as $field) {
                $field_name = $field->name;
                $field_type = $field->type;

                switch ($field_type) {
                    case 1: // Tiny int
                    case 2: // Small int
                    case 3: // Int
                    case 8: // Big int
                    case 9: // Medium int
					case 'tinyint':
					case 'smallint':
					case 'int':
                        $query_result->{$field_name} = intval($query_result->{$field_name});
                        break;
                    case 4: // Float
                    case 5: // Double
                    case 246: // NEWDECIMAL
                        $query_result->{$field_name} = floatval($query_result->{$field_name});
                        break;
                }

                if ($field_name == 'mini' || $field_name == 'thumb' || $field_name == 'image' || $field_name == 'original' || substr($field_name, -6) == '_image' || substr($field_name, -4) == '_img') {
                    if (strlen($query_result->{$field_name}) > 0 && substr($query_result->{$field_name}, 0, 4) != 'http') {
						$query_result->{$field_name} = UPLOAD['S3_URL'] . $query_result->{$field_name};
                    }
					else{
						if(strrpos($field_name, '_no_' ) === false){
							$query_result->{$field_name} = UPLOAD['S3_URL'].UPLOAD['NO_IMG'];
						}
					}
                }

                // 핸드폰 번호
                if ($field_name == 'phone' || substr($field_name, -6) == '_phone') {
                    if (strlen($query_result->{$field_name}) > 0 && strpos($query_result->{$field_name}, '-') === FALSE) {
                        $query_result->{$field_name} = preg_replace("/([0-9]{3})([0-9]{3,4})([0-9]{4})$/", "\\1-\\2-\\3", $query_result->{$field_name});
                    }
                }

                // grouping
                if (count($grouping) > 0) {
                    $group_check = false;
                    foreach ($grouping as $group_name) {

                        if (stripos($field_name, $group_name . '_') === 0) {
                            $field_new_name = substr($field_name, strlen($group_name) + 1);
                            $group_result[$group_name][$field_new_name] = $query_result->{$field_name};

                            if ($field_new_name == 'mini' || $field_new_name == 'thumb' || $field_new_name == 'original' || $field_new_name == 'image') {
                                if (strlen($query_result->{$field_name}) > 0 && substr($query_result->{$field_name}, 0, 4) != 'http') {
									$group_result[$group_name][$field_new_name] = UPLOAD['S3_URL'] . $query_result->{$field_name};
                                }
                            }

                            $group_check = true;
                        }
                    }

                    if (!$group_check) {
                        $group_result[$field_name] = $query_result->{$field_name};

                        if ($field_name == 'mini' || $field_name == 'thumb' || $field_name == 'original' || $field_name == 'image') {
                            if (strlen($query_result->{$field_name}) > 0 && substr($query_result->{$field_name}, 0, 4) != 'http') {
                                $group_result[$field_name] = UPLOAD['S3_URL'] . $query_result->{$field_name};
                            }
							else{
								$group_result[$field_name] = UPLOAD['S3_URL'].UPLOAD['NO_IMG'];
							}
                        }

                    }
                }
            }
        }

        if (count($group_result)) return $group_result;
        else return $query_result;
    }

    public function password_set($login_password)
    {
        return md5('ss' . md5(trim($login_password)) . '$$');
    }

    public function default($value, $default = '')
    {
        return $value ? $value : $default;
    }

    public function string_to_array($value)
    {
        $res = array();
        if ($value) {
            $explode_data = explode(",", $value);
            foreach ($explode_data as $sub_value) {
                $res[] = array('name' => $sub_value);
            }
        }

        return $res;
    }


    public function object_to_array($value)
    {
        $res = array();
        if (!empty($value)){
            foreach ($value as $key => $value) {
                $res[] = $value->name;
            }
        }
        return $res;
    }

    function alert($msg = '', $url = '')
    {
        if (!$msg){
            $msg = '올바른 방법으로 이용해 주십시오.';
        }

        echo "<meta http-equiv=\"content-type\" content=\"text/html; charset=utf-8\">";
        echo '<script type="text/javascript">alert("' . $msg . '");';
        if ($url){
            echo "location.replace('" . $url . "');";
        }
        else{
            echo "history.go(-1);";
        }

        echo "</script>";
        exit;
    }

	function getRandStr($length = 6)
	{
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		return $randomString;
	}

	function short_url($url){
		$client_id = NAVER_APP['NAVER-CLIENT-ID'];
		$client_secret = NAVER_APP['NAVER-CLIENT-SECRET'];
		$encText = urlencode($url);
		$postvars = "url=".$encText;

		$url = "https://openapi.naver.com/v1/util/shorturl?url=".$encText ;
		$is_post = false;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, $is_post);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$headers = array();
		$headers[] = "X-Naver-Client-Id: ".$client_id;
		$headers[] = "X-Naver-Client-Secret: ".$client_secret;
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		$response = curl_exec ($ch);
		$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close ($ch);

//		echo $response;
		if($status_code == 200) {
			$result = json_decode($response);
			return $result->result->url;
		} else {
			return false;
		}
	}

	function bilty_url($url){
		$token = BITLY['ACCESS-TOKEN'];

		$data = array();
		$data['long_url'] = $url;
		$payload = json_encode($data);

		$bitApi = "https://api-ssl.bitly.com/v4/bitlinks";

		$cURL = curl_init();
		curl_setopt($cURL, CURLOPT_URL, $bitApi);
		curl_setopt($cURL, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($cURL, CURLOPT_POSTFIELDS, $payload);
		curl_setopt($cURL, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($cURL, CURLOPT_HTTPHEADER, array(
				"Authorization:Bearer " . BITLY['ACCESS-TOKEN']
			, "Content-Type:application/json"
			, "Content-Length:" . strlen($payload),
			)
		);

		$result = json_decode(curl_exec($cURL));
		curl_close ($cURL);
		if (isset($result->link)) {
			return $result->link;
		} else {
			return false;
		}
	}
}