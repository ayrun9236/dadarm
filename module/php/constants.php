<?php

defined('PER_PAGE') or define('PER_PAGE', 10); // 리스트 데이터 갯수

// 모듈 PATH
defined('SERVICE_ROOT') or define('SERVICE_ROOT', '/var/www/moaayo');

defined('MODULEPATH') or define('MODULEPATH', SERVICE_ROOT . '/module/php');
defined('THIRD_PARTY') or define('THIRD_PARTY', SERVICE_ROOT . '/module/third_party');

//define('IS_TEST_MODE', substr($_SERVER['HTTP_HOST'], 0, 5) == 'test.' ? 1 : 0);
defined('IS_TEST_MODE') or define('IS_TEST_MODE', 0);
// DOCUMENT_ROOT
 
defined('API_URL') or define('API_URL', 'api.da-daleum.com');
defined('APP_VERSION') or define('APP_VERSION', '1.0.0');


// 이미지 정보
defined('UPLOAD') or define('UPLOAD', array(
	'S3_BUCKET' => 'img.da-daleum.com',
	'S3_URL'    => 'https://img.da-daleum.com',
	'S3_KEY'    => 'jtUU5K16GPB6gHBcvpdw0fTk+n0S6mnGiSUgAii2',
	'S3_ID'     => 'AKIAVPTJWPZXLWPCV4W7',
	'PATH'      => SERVICE_ROOT.'/admin/resources/temp/',
	'NO_IMG' 	=> '/default/no-image.jpg',
	'NO_PROFILE' 	=> '/default/no-profile.png',
));

// naver 지도
defined('NAVER_MAP') or define('NAVER_MAP', array(
	'X-NCP-APIGW-API-KEY-ID' => 'ml3ehpa7kw',
	'X-NCP-APIGW-API-KEY'    => 'rmmdv80a5z7OBkLCyit3czMiVsm1lvfpHxLpyYt7',
));

defined('NAVER_APP') or define('NAVER_APP', array(
	'NAVER-CLIENT-ID'     => '0kYpXz9JsiyNelWIAcyj',
	'NAVER-CLIENT-SECRET' => 'Tnun13U4te',
));

defined('BITLY') or define('BITLY', array(
	'ACCESS-TOKEN' => 'ad694ec449ae26336f2bbd612ab9d6717d1cd6fa',
));

// error code
defined('ERROR_CODE') or define('ERROR_CODE', array(
	'HTTP_NOT_OK'      => 400,
	'HTTP_DATA_EXISTS' => 601,
));

// DB DATA
define('USER_BOARD_TYPE', array(
	'SELFTEST' => 112
));


define('DEFAULT_IMAGE', array(
	'USER' => '/default/user.png'
));


//todo 링크 변경하기
if (IS_TEST_MODE) {
	defined('SERVICE_URL') or define('SERVICE_URL', 'https://api.da-daleum.com');
	defined('CLIENT_URL') or define('CLIENT_URL', 'https://www.da-daleum.com');
	defined('APP_URL') or define('APP_URL', 'https://app.da-daleum.com');
	defined('ADMIN_URL') or define('ADMIN_URL', 'https://admin.da-daleum.com');

	//이니시스 결제
	defined('PG_INICIS') or define('PG_INICIS', array(
		'mid'    => 'INIpayTest',
		'key'    => '1111',
		'sign'   => 'SU5JTElURV9UUklQTEVERVNfS0VZU1RS',
		'log'    => SERVICE_ROOT . '/logs/inicis',
		'js_url' => 'https://stgstdpay.inicis.com/stdjs/INIStdPay.js',
	));
} else {

	defined('SERVICE_URL') or define('SERVICE_URL', 'https://api.da-daleum.com');
	defined('CLIENT_URL') or define('CLIENT_URL', 'https://www.da-daleum.com');
	defined('APP_URL') or define('APP_URL', 'https://app.da-daleum.com');
	defined('ADMIN_URL') or define('ADMIN_URL', 'https://admin.da-daleum.com');

	defined('PG_INICIS') or define('PG_INICIS', array(
		'mid'    => 'INIpayTest',
		'key'    => '1111',
		'sign'   => 'SU5JTElURV9UUklQTEVERVNfS0VZU1RS',
		'log'    => SERVICE_ROOT . '/logs/inicis',
		'js_url' => 'https://stgstdpay.inicis.com/stdjs/INIStdPay.js',
	));
}

