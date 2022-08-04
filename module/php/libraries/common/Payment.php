<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 결제관련
 */
class Payment
{

    private $ci;
    private $inipay;
    private $inicis_authMap;    //이니시스
    private $httpUtil;          //이니시스
    private $is_test = false;
    protected $error_code = null;
    protected $error_message = null;

    function __construct()
    {
        $this->ci = & get_instance();
        $this->ci->load->helper(array('array'));

		if(IS_TEST_MODE){
			$this->is_test = 1;
		}
    }

    /**
     * 에러 코드 반환
     *
     * @return array
     */
    public function get_error()
    {
        return (object)array(
            'code'    => $this->error_code,
            'message' => $this->error_message,
        );
    }

    public function set_test(){
        $this->is_test = true;
    }

    public function inicis_init($mode ='start', $order_data = array())
    {
        $data = array();
		$data['pg_inicis_mid'] = PG_INICIS['mid'];
		$data['pg_inicis_key'] = PG_INICIS['key'];
		$data['pg_inicis_sign'] = PG_INICIS['sign'];
		$data['ini_js_url'] = PG_INICIS['js_url'];

        /**************************
         * 1. 라이브러리 인클루드 *
         **************************/
        include_once(MODULEPATH .'/inicis/libs/INILib.php');
        require_once(MODULEPATH . "/inicis/libs/INIStdPayUtil.php");
        require_once(MODULEPATH . "/inicis/libs//sha256.inc.php");


        /***************************************
         * 2. INIpay50 클래스의 인스턴스 생성 *
         ***************************************/
        $this->inipay = new INIpay50;

        $this->inipay->SetField('inipayhome', MODULEPATH . '/inicis'); // 이니페이 홈디렉터리(상점수정 필요)
        $this->inipay->SetField('debug', "false"); // 로그모드('true'로 설정하면 상세로그가 생성됨.)

		$data['util'] = new INIStdPayUtil();
		$data['timestamp'] = $data['util']->getTimestamp(); // util에 의해서 자동생성

		if($mode == 'start') {
			$data['mKey'] = hash("sha256", $data['pg_inicis_sign']);

			$params = array(
				"oid"       => $order_data['oid'],
				"price"     => $order_data['payment_price'],
				"timestamp" => $data['timestamp']
			);

			$data['signature'] = $data['util']->makeSignature($params, "sha256");


			$data['inipay_nointerest'] = 'no'; //무이자여부(no:일반, yes:무이자)
			$data['inipay_quotabase'] = '선택:일시불:2개월:3개월:4개월:5개월:6개월:7개월:8개월:9개월:10개월:11개월:12개월'; // 할부기간

			$data['returnUrl'] = SERVICE_URL . '/web/order/complate/' . urlencode($this->ci->mcrypt->encrypt($order_data['oid']));
			$data['closeUrl'] = CLIENT_URL;
			$data['popupUrl'] = CLIENT_URL;

			if ($order_data['pay_method']) {
				$order_data['pay_method_code'] = strtolower(array_search($order_data['pay_method'], PAYMENT_METHOD));
			}

			$data['P_NEXT_URL'] = SERVICE_URL . '/web/order/complate/' . urlencode($this->ci->mcrypt->encrypt($order_data['oid']));
			$data['P_CANCEL_URL'] = CLIENT_URL . '/order/info';
			$data['P_NOTI_URL'] = SERVICE_URL . '/web/order/vbank_complate/';
			$data['P_HPP_METHOD'] = 1;
			$data['P_CHARSET'] = 'utf8';
			if ($order_data['pay_method_code'] == 'vbank') {
				$data['P_VBANK_DT'] = date('Ymd', strtotime($order_data['pay_closing_dt']));
				$data['P_VBANK_TM'] = date('Hi', strtotime($order_data['pay_closing_dt']));
				$data['P_RESERVED'] = '';
			} else {
				$data['P_VBANK_DT'] = date('Ymd', strtotime($order_data['pay_closing_dt']));
				$data['P_VBANK_TM'] = date('Hi', strtotime($order_data['pay_closing_dt']));
				$data['P_RESERVED'] = 'twotrs_isp=Y&block_isp=Y';
				//$data['P_ONLY_CARDCODE'] = '11:06:12:14:01:04:03:17:16:26'; //부분취소가 가능한 카드사면 노출
			}
		}

		$data['BANK_CODE'] = array(
			'02' => '한국산업은행',
			'03' => '기업은행',
			'04' => '국민은행',
			'05' => '하나은행 (구 외환)',
			'06' => '국민은행 (구 주택)',
			'07' => '수협중앙회',
			'11' => '농협중앙회',
			'12' => '단위농협',
			'16' => '축협중앙회',
			'20' => '우리은행',
			'21' => '구)조흥은행',
			'22' => '상업은행',
			'23' => 'SC 제일은행',
			'24' => '한일은행',
			'25' => '서울은행',
			'26' => '구)신한은행',
			'27' => '한국씨티은행 (구 한미)',
			'31' => '대구은행',
			'32' => '부산은행',
			'34' => '광주은행',
			'35' => '제주은행',
			'37' => '전북은행',
			'38' => '강원은행',
			'39' => '경남은행',
			'41' => '비씨카드',
			'45' => '새마을금고',
			'48' => '신용협동조합중앙회',
			'50' => '상호저축은행',
			'53' => '한국씨티은행',
			'54' => '홍콩상하이은행',
			'55' => '도이치은행',
			'56' => 'ABN 암로',
			'57' => 'JP 모건 ',
			'59' => '미쓰비시도쿄은행',
			'60' => 'BOA(Bank of America) ',
			'64' => '산림조합',
			'70' => '신안상호저축은행 ',
			'71' => '우체국',
			'81' => '하나은행 ',
			'83' => '평화은행',
			'87' => '신세계 ',
			'88' => '신한(통합)은행',
			'D1' => '유안타증권(구 동양증권) ',
			'D2' => '현대증권',
			'D3' => '미래에셋증권 ',
			'D4' => '한국투자증권',
			'D5' => '우리투자증권 ',
			'D6' => '하이투자증권',
			'D7' => 'HMC 투자증권 ',
			'D8' => 'SK 증권',
			'D9' => '대신증권 ',
			'DA' => '하나대투증권',
			'DB' => '굿모닝신한증권 ',
			'DC' => '동부증권',
			'DD' => '유진투자증권 ',
			'DE' => '메리츠증권',
			'DF' => '신영증권 ',
			'DG' => '대우증권',
			'DH' => '삼성증권 ',
			'DI' => '교보증권',
			'DJ' => '키움증권 ',
			'DK' => '이트레이드',
			'DL' => '솔로몬증권 ',
			'DM' => '한화증권',
			'DN' => 'NH 증권 ',
			'DO' => '부국증권',
			'DP' => 'LIG 증권   ',
		);

		$data['CARD_CODE'] = array(
			'01' => '외환',
			'03' => '롯데',
			'04' => '현대',
			'06' => '국민',
			'11' => 'BC',
			'12' => '삼성',
			'14' => '신한',
			'15' => '한미',
			'16' => 'NH',
			'17' => '하나 SK',
			'21' => '해외비자',
			'22' => '해외마스터',
			'23' => 'JCB',
			'24' => '해외아멕스',
			'25' => '해외다이너스'
		);

		return array_merge($data,$order_data) ;
    }

    public function inipay_mobile_result($order_data = array()){

        $config = $this->inicis_init('complate', $order_data);
		log_message('error', 2);
        $mid = element('pg_inicis_mid', $config);
        $BANK_CODE = element('BANK_CODE', $config);

        $pg_status = $_REQUEST['P_STATUS'];
        $pg_message = $_REQUEST['P_RMESG1'];

        if ($pg_status === '00') {
			log_message('error', 3);
            // pg req_url call
            $pg_data = $this->call_req_url($mid, $_REQUEST);
            if (false === $pg_data) {
                return false;
            }

            log_message('error', print_r($pg_data, true));

            //최종결제요청 결과 성공 DB처리
            $result = array(
                'pg_tid' => $pg_data['P_TID'],
                'pay_price' => $pg_data['P_AMT'],
                'app_datetime' => $pg_data['P_AUTH_DT'],
            );

            switch ($pg_data['P_TYPE']) {
                case 'VBANK':
                    $result['bank_code'] = $pg_data['P_VACT_BANK_CODE'];
                    $result['bank_account'] = $pg_data['P_VACT_NUM'];
                    $result['bank_owner_name'] = TRIM($pg_data['P_VACT_NAME']);
                    $result['bank_owner'] = TRIM($pg_data['P_VACT_NAME']);
                    $result['bank_name'] = isset($BANK_CODE[$pg_data['P_VACT_BANK_CODE']]) ? $BANK_CODE[$pg_data['P_VACT_BANK_CODE']] : '';

                    break;
                default:
                    break;
            }
        }
        else{
			log_message('error', 4);
        	if($pg_status){
				$this->error_code = $pg_status;
				$this->error_message = $pg_message;
			}
        	else{
				$this->error_code = $pg_status;
				$this->error_message = '잘못된 접근입니다.';
			}

            return false;
        }

        return (object)$result;
    }


    public function inipay_pc_result($order_data = array())
    {
        $result = false;
        $config = $this->inicis_init('complate', $order_data);

        $mid = element('pg_inicis_mid', $config);
        $inicis_pay_result = false;

        try {

            $log_write = ture;
            require_once(MODULEPATH . "/inicis/libs/HttpClient.php");
            require_once(MODULEPATH . "/inicis/libs/json_lib.php");
            //#############################
            // 인증결과 파라미터 일괄 수신
            //#############################
            //		$var = $_REQUEST["data"];

            //#####################
            // 인증이 성공일 경우만
            //#####################

            $this->m_Log = new INILog( array("inipayhome" => MODULEPATH . "inicis", 'type' => 'pcpay', 'mid' => $mid, 'debug' => true) );
            if (strcmp("0000", $_REQUEST["resultCode"]) == 0) {
                //############################################
                // 1.전문 필드 값 설정(***가맹점 개발수정***)
                //############################################;

                $util           = element('util', $config);

                $mid 			= element('pg_inicis_mid', $config);   // 가맹점 ID 수신 받은 데이터로 설정
                $signKey 		= element('pg_inicis_sign', $config); 	// 가맹점에 제공된 키(이니라이트키) (가맹점 수정후 고정) !!!절대!! 전문 데이터로 설정금지
                $timestamp 		= element('timestamp', $config);   	// util에 의해서 자동생성
                $charset 		= "UTF-8";        							// 리턴형식[UTF-8,EUC-KR](가맹점 수정후 고정)
                $format 		= "JSON";        							// 리턴형식[XML,JSON,NVP](가맹점 수정후 고정)
                $authToken 		= $_REQUEST["authToken"];   				// 취소 요청 tid에 따라서 유동적(가맹점 수정후 고정)
                $authUrl 		= $_REQUEST["authUrl"];    					// 승인요청 API url(수신 받은 값으로 설정, 임의 세팅 금지)
                $netCancel 		= $_REQUEST["netCancelUrl"];   				// 망취소 API url(수신 받은f값으로 설정, 임의 세팅 금지)
                $mKey 			= hash("sha256", $signKey);					// 가맹점 확인을 위한 signKey를 해시값으로 변경 (SHA-256방식 사용)
                $merchantData 	= $_REQUEST["merchantData"];     			// 가맹점 관리데이터 수신


                //#####################
                // 2.signature 생성
                //#####################
                $signParam["authToken"] 	= $authToken;  	// 필수
                $signParam["timestamp"] 	= $timestamp;  	// 필수
                // signature 데이터 생성 (모듈에서 자동으로 signParam을 알파벳 순으로 정렬후 NVP 방식으로 나열해 hash)
                $signature = $util->makeSignature($signParam);


                //#####################
                // 3.API 요청 전문 생성
                //#####################
                $authMap["mid"] 			= $mid;   		// 필수
                $authMap["authToken"] 		= $authToken; 	// 필수
                $authMap["signature"] 		= $signature; 	// 필수
                $authMap["timestamp"] 		= $timestamp; 	// 필수
                $authMap["charset"] 		= $charset;  	// default=UTF-8
                $authMap["format"] 			= $format;  	// default=XML


                try {

                    $httpUtil = new HttpClient();



                    //#####################
                    // 4.API 통신 시작
                    //#####################

                    $authResultString = "";

                    if(!$this->m_Log->StartLog())
                    {
                        $log_write = false;
                    }

                    if ($httpUtil->processHTTP($authUrl, $authMap)) {
                        $authResultString = $httpUtil->body;

                        if($log_write){
                            $this->m_Log->WriteLog( DEBUG, $authResultString);
                        }

                    } else {

                        if($log_write){
                            $this->m_Log->WriteLog( DEBUG, "Http Connect Error\n".$httpUtil->errormsg);
                        }

                        throw new Exception("Http Connect Error");
                    }

                    //############################################################
                    //5.API 통신결과 처리(***가맹점 개발수정***)
                    //############################################################

                    $resultMap = json_decode($authResultString, true);

                    /*************************  결제보안 추가 2016-05-18 START ****************************/
                    $secureMap["mid"]		= $mid;							//mid
                    $secureMap["tstamp"]	= $timestamp;					//timestemp
                    $secureMap["MOID"]		= $resultMap["MOID"];			//MOID
                    $secureMap["TotPrice"]	= $resultMap["TotPrice"];		//TotPrice

                    // signature 데이터 생성
                    $secureSignature = $util->makeSignatureAuth($secureMap);
                    /*************************  결제보안 추가 2016-05-18 END ****************************/

                    if ((strcmp("0000", $resultMap["resultCode"]) == 0) && (strcmp($secureSignature, $resultMap["authSignature"]) == 0) ){	//결제보안 추가 2016-05-18
                        /*****************************************************************************
                         * 여기에 가맹점 내부 DB에 결제 결과를 반영하는 관련 프로그램 코드를 구현한다.

                        [중요!] 승인내용에 이상이 없음을 확인한 뒤 가맹점 DB에 해당건이 정상처리 되었음을 반영함
                        처리중 에러 발생시 망취소를 한다.
                         ******************************************************************************/

                        if($log_write){
                            $this->m_Log->WriteLog( DEBUG, '결제성공');
                        }

                        //최종결제요청 결과 성공 DB처리
                        $result = array(
                            'pg_tid' => $resultMap['tid'],
                            'real_payment_price' => $resultMap['TotPrice'],
                            'app_datetime' => $resultMap['applDate'].$resultMap['applTime'],
                            //'pay_method' => $resultMap['payMethod'],
                        );

                        switch($resultMap['payMethod']) {
                            case 'VBank':
                                $result['bank_code']  = $resultMap['VACT_BankCode'];
                                $result['bank_account']  = $resultMap['VACT_Num'];
                                $result['bank_name'] = $resultMap['vactBankName'];
                                $result['bank_owner_name'] = TRIM($resultMap['VACT_Name']);
                                $result['bank_owner'] = TRIM($resultMap['VACT_Name']);

                                break;
                            default:
                                break;
                        }

                        //log_message('error', print_r($result, true));
                        //log_message('error', print_r($resultMap, true));
                        $result = (object)$result;

                        $inicis_pay_result = true;

                    } else {

                        if($log_write){
                            $this->m_Log->WriteLog( DEBUG, '(오류코드:'.$resultMap['resultCode'].') '.$resultMap['resultMsg']);
                        }

                        $this->error_message = $resultMap['resultMsg'];
                        $this->error_code = $resultMap['resultCode'];

                        //결제보안키가 다른 경우.
                        if ((strcmp($secureSignature, $resultMap["authSignature"]) != 0) && (strcmp("0000", $resultMap["resultCode"]) == 0)) {
                            //망취소
                            if(strcmp("0000", $resultMap["resultCode"]) == 0) {
                                //throw new Exception("데이터 위변조 체크 실패");
                                throw new Exception("");
                                if($log_write){
                                    $this->m_Log->WriteLog( DEBUG, '데이터 위변조 체크 실패');
                                }

                                $this->error_message .= '데이터 위변조 체크 실패';
                            }
                        }
                    }

                    // 수신결과를 파싱후 resultCode가 "0000"이면 승인성공 이외 실패
                    // 가맹점에서 스스로 파싱후 내부 DB 처리 후 화면에 결과 표시
                    // payViewType을 popup으로 해서 결제를 하셨을 경우
                    // 내부처리후 스크립트를 이용해 opener의 화면 전환처리를 하세요
                    //throw new Exception("강제 Exception");
                } catch (Exception $e) {
                    // $s = $e->getMessage() . ' (오류코드:' . $e->getCode() . ')';
                    //####################################
                    // 실패시 처리(***가맹점 개발수정***)
                    //####################################
                    //---- db 저장 실패시 등 예외처리----//
                    $s = $e->getMessage() . ' (오류코드:' . $e->getCode() . ')';
                    //echo $s;
                    if($log_write){
                        $this->m_Log->WriteLog( DEBUG, $s);
                    }

                    $this->error_message = $e->getMessage();
                    $this->error_code = $e->getCode();

                    //#####################
                    // 망취소 API
                    //#####################

                    $netcancelResultString = ""; // 망취소 요청 API url(고정, 임의 세팅 금지)

                    if ($httpUtil->processHTTP($netCancel, $authMap)) {
                        $netcancelResultString = $httpUtil->body;
                    } else {
                        echo "Http Connect Error\n";
                        echo $httpUtil->errormsg;

                        throw new Exception("Http Connect Error");
                    }

                    //echo "<br/>## 망취소 API 결과 ##<br/>";

                    /*##XML output##*/
                    //$netcancelResultString = str_replace("<", "&lt;", $$netcancelResultString);
                    //$netcancelResultString = str_replace(">", "&gt;", $$netcancelResultString);

                    // 취소 결과 확인
                    //echo "<p>". $netcancelResultString . "</p>";

                    if($log_write){
                        $this->m_Log->WriteLog( DEBUG, "망취소 API 결과\n".$netcancelResultString);
                    }

                }
            } else {

                if($log_write){
                    $this->m_Log->WriteLog( DEBUG, '####인증실패####');
                    $this->m_Log->WriteLog( DEBUG, var_dump($_REQUEST));
                }

                $this->error_message = 'PG 인증실패';
                $this->error_code = '';

            }
        } catch (Exception $e) {
            $s = $e->getMessage() . ' (오류코드:' . $e->getCode() . ')';
            //echo $s;
            if($log_write){
                $this->m_Log->WriteLog( DEBUG, s);
            }

            $this->error_message = $e->getMessage();
            $this->error_code = $e->getCode();

        }

        return $result;
    }

    public function inipay_admin_cancel($result, $return_msg=false){
        $config = $this->inicis_init('end');

//        /*********************
//         * 3. 취소 정보 설정 *
//         *********************/
//        $this->inipay->SetField("type",      "cancel");                        // 고정 (절대 수정 불가)
//        $this->inipay->SetField("mid",       element('pg_inicis_mid', $config));       // 상점아이디
//        /**************************************************************************************************
//         * admin 은 키패스워드 변수명입니다. 수정하시면 안됩니다. 1111의 부분만 수정해서 사용하시기 바랍니다.
//         * 키패스워드는 상점관리자 페이지(https://iniweb.inicis.com)의 비밀번호가 아닙니다. 주의해 주시기 바랍니다.
//         * 키패스워드는 숫자 4자리로만 구성됩니다. 이 값은 키파일 발급시 결정됩니다.
//         * 키패스워드 값을 확인하시려면 상점측에 발급된 키파일 안의 readme.txt 파일을 참조해 주십시오.
//         **************************************************************************************************/
//        $this->inipay->SetField("admin",     element('pg_inicis_key', $config)); //비대칭 사용키 키패스워드
//        $this->inipay->SetField("tid",       element('tid', $result));                   // 취소할 거래의 거래아이디
//        $this->inipay->SetField("cancelmsg", element('refund_msg', $result));                     // 취소사유
//        $this->inipay->SetField("log", "true");                     // 취소로그를 생성하지 않습니다.
//
//        /****************
//         * 4. 취소 요청 *
//         ****************/
//        $this->inipay->startAction();
//
//        /****************************************************************
//         * 5. 취소 결과                                           	*
//         *                                                        	*
//         * 결과코드 : $inipay->getResult('ResultCode') ("00"이면 취소 성공)  	*
//         * 결과내용 : $inipay->getResult('ResultMsg') (취소결과에 대한 설명) 	*
//         * 취소날짜 : $inipay->getResult('CancelDate') (YYYYMMDD)          	*
//         * 취소시각 : $inipay->getResult('CancelTime') (HHMMSS)            	*
//         * 현금영수증 취소 승인번호 : $inipay->getResult('CSHR_CancelNum')    *
//         * (현금영수증 발급 취소시에만 리턴됨)                          *
//         ****************************************************************/
//
//        $res_cd  = $this->inipay->getResult('ResultCode');
//        $res_msg = $this->inipay->getResult('ResultMsg');
//
//
//        $pg_res_cd = '';
//
//        if($res_cd != '00') {
//
//            $pg_res_cd = $res_cd;
//            //$pg_res_msg = iconv('euc-kr', 'utf-8', $res_msg);
//
//			log_message('error', '이니시스 취소 에러: '.$res_cd);
//			log_message('error', '이니시스 취소 에러1: '.$res_msg);
//            $this->error_message = $res_msg;
//            return false;
//        }

		$eip = file_get_contents('http://169.254.169.254/latest/meta-data/public-ipv4');
		$post_data = array(
			'type' => 'Refund',
			'paymethod' => 'Card',
			'timestamp' => date("YmdHis"),
			'clientIp' => $eip,
			'mid' => PG_INICIS['mid'],
			'tid' => element('tid', $result),
			'msg' => element('refund_msg', $result),
		);

		$post_data['hashData'] = hash('sha512',PG_INICIS['key'].$post_data['type'].$post_data['paymethod'].$post_data['timestamp'].$post_data['clientIp'].$post_data['mid'].$post_data['tid']);
		$url ="https://iniapi.inicis.com/api/v1/refund";  // 전송 URL

		$ch = curl_init();                                                   //curl 초기화
		curl_setopt($ch, CURLOPT_URL, $url);                        // 전송 URL 지정하기
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);     //요청 결과를 문자열로 반환
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);      //connection timeout 10초
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));       //POST data
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded; charset=utf-8')); // 전송헤더 설정
		curl_setopt($ch, CURLOPT_POST, 1);                          // post 전송

		$response = curl_exec($ch);
		curl_close($ch);
		$response = json_decode($response);

		if($response->resultCode != '00') {

			log_message('error', '이니시스 취소 에러: '.$response->resultCode .' - '.$response->resultMsg);
            $this->error_message = $response->resultMsg;
            if($response->resultMsg != '기 취소 거래')
            return false;
        }

        return true;
    }

    public function inipay_cancel($result, $agent_type='')
    {
        /*******************************************************************
         * 7. DB연동 실패 시 강제취소 *
         * *
         * 지불 결과를 DB 등에 저장하거나 기타 작업을 수행하다가 실패하는 *
         * 경우, 아래의 코드를 참조하여 이미 지불된 거래를 취소하는 코드를 *
         * 작성합니다. *
         *******************************************************************/

        $cancelFlag = 'true';

        // $cancelFlag를 'ture'로 변경하는 condition 판단은 개별적으로
        // 수행하여 주십시오.

        if ($cancelFlag === 'true') {

            if( $agent_type != 'mobile' ){     // 모바일
            	if(!isset($result['refund_msg'])){
					$result['refund_msg'] = '결제 입력 시 DB FAIL';    // 취소사유
				}

                if($this->inipay_admin_cancel($result) === false){
                	return false;
				}
				else{
                	return true;
				}

            } else {    // PC

                $netCancel = $this->ci->input->post_get('netCancelUrl', null, '');   // 망취소 API url(수신 받은f값으로 설정, 임의 세팅 금지)
				echo $netCancel;
                $this->httpUtil->processHTTP($netCancel, $this->inicis_authMap);

            }

        }
    }


    public function call_req_url($mid, $params)
    {

        $url = $params['P_REQ_URL'];
        $queryParams = 'P_MID=' . $mid
            . '&P_TID=' . $params['P_TID'];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $queryParams);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);

        $response = curl_exec($ch);
        if ($response === false) {
            throw new Exception("curl_exec error for url $url.");
        }

        curl_close($ch);
        $ret = array();

        parse_str($response, $ret);

        if (!isset($ret['P_STATUS'])) {
            $this->error_code = '';
            $this->error_message = '결제 통신 결과가 존재하지 않습니다.';
            return false;
        }

        if ($ret['P_STATUS'] != '00') {
            $this->error_code = '';
            $this->error_message = iconv('euc-kr', 'utf-8', $ret['P_RMESG1']);
            return false;
        }

        return $ret;
    }
}