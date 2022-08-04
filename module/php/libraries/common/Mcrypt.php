<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Mcrypt File
 *
 *
 *
 * @category    Mcrypt
 * @author      SILI
 * @date        2016-09-27 오후 1:41
 */
define('ENC_KEY', md5('aktlakfhWkd2014!'));
define('ENC_IV', str_repeat(chr(0), 16));

class Mcrypt
{
	var $ci;

	public function __construct()
	{
		$this->ci =& get_instance();
	}

	/**
	 * PKCS5 패드추가
	 * @param string $text
	 * @param int $blocksize
	 * @return string
	 */
	public function pkcs5_pad ($text, $blocksize) {
		$pad = $blocksize - (strlen($text) % $blocksize);
		return $text . str_repeat(chr($pad), $pad);
	}


	/**
	 * PKCS5 패드제거
	 * @param string $text
	 * @return boolean|string
	 */
	public function pkcs5_unpad($text) {
		$pad = ord($text{strlen($text)-1});
		if ($pad > strlen($text)) return false;
		if (strspn($text, chr($pad), strlen($text) - $pad) != $pad) return false;
		return substr($text, 0, -1 * $pad);
	}


	/**
	 * 암호화
	 * @param string $str
	 * @return string
	 */
	public function encrypt($str)
	{
//		$size = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, 'cbc');
//		$input = $this->pkcs5_pad($str, $size);
//		$cipher = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, ENC_KEY, $input, MCRYPT_MODE_CBC, ENC_IV);

		$data = openssl_encrypt ( $str , "AES-128-CBC" , ENC_KEY, 0, ENC_IV );

		return base64_encode($data);

	}

	/**
	 * 복호화
	 * @param string $str
	 * @return bool|string
	 */
	public function decrypt($str)
	{
		$cipher = base64_decode($str);
//		if($cipher === false)
//			return false;
////        $len = strlen($cipher);
////        if($len < 48)
////            return false;
////
////        $ciphertext = substr($cipher, 0, $len - 32);
//
//		$ciphertext = $cipher;
//		$plaintext = @mcrypt_decrypt(MCRYPT_RIJNDAEL_128, ENC_KEY, $ciphertext, MCRYPT_MODE_CBC, ENC_IV);
		$plaintext = openssl_decrypt ( $cipher , "AES-128-CBC" , ENC_KEY, 0, ENC_IV );
		if($plaintext === false)
			return false;
		else
			return $plaintext;
	}
}
