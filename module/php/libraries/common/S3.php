<?php

require THIRD_PARTY . '/aws/autoload.php';

use Aws\S3\S3Client;

class S3
{

	private $client;
	private $bucket;
	public $error;

	function __construct() {

		// Instantiate the client.
		$options = [
			'region' => 'ap-northeast-2',
			'version' => 'latest',
			'signature_version' => 'v4',
			'credentials' => [
				'key' => UPLOAD['S3_ID'],
				'secret' => UPLOAD['S3_KEY'],
			],
		];
		$this->bucket = UPLOAD['S3_BUCKET'];
		$this->client = new S3Client($options);
	}

	function set_bucket($bucket) {

		$this->bucket = trim($bucket);

		return $this;
	}

	function upload($file_path, $dir = '') {

		if (!file_exists($file_path)) {
			return false;
		}

		# 업로드 버킷설정
		if ($this->bucket == '') {
			return false;
		}

		# 확장자 추출
		preg_match_all("/\.(jpg|jpeg|gif|png)$/", strtolower($file_path), $out);
		if (isset($out[1][0])) {
			$ext = $out[1][0];
			$exp = explode("/", $file_path);
			$file = array_pop($exp);
		} else {
			return false;
		}

		if ($dir != '') {
			$dir = preg_replace("/\/$/", "", $dir);
			$dir .= '/';
		}

		try {

			$result = $this->client->putObject(array(
				'Bucket' => $this->bucket,
				'Key' => $dir . $file,
				'SourceFile' => $file_path,
				'ContentType' => mime_content_type($file_path),
				'ACL' => 'public-read',
				//'StorageClass' => 'REDUCED_REDUNDANCY',
			));
			$url = $result['ObjectURL'];
		} catch (Exception $ex) {

			$this->error = $ex->getMessage();
			echo $this->error;
			echo "ffffff";
			return false;
		}

		return $url;
	}

	function delete($objects) {
		try {

			$result = $this->client->deleteObjects(array(
				'Bucket' => $this->bucket,
				'Delete' => $objects
			));

			return true;
		} catch (Exception $ex) {

			$this->error = $ex->getMessage();
			return false;
		}

	}
}


