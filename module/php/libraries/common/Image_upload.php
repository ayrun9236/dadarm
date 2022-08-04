<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

/**
 * Image_upload Class
 *
 * 이미지 업로드 라이브러리
 *
 */
class Image_upload
{
	protected $ci;
	protected $mini_size = 800;
	protected $is_origin_name_save = false;          // 사진원본 이름 저장
	protected $error_code = null;
	protected $error_message = null;

	/**
	 * 에러 코드 반환
	 *
	 * @return object
	 */
	public function get_error()
	{
		return (object)array(
			'code'    => $this->error_code,
			'message' => $this->error_message,
		);
	}

	function __construct()
	{
		$this->ci =& get_instance();
		$this->ci->load->library('upload');
		$this->ci->load->library('common/S3');
		$this->ci->load->library('image_lib');

	}

	/*
		다중 업로드 하기

		target_table : 관련 테이블
		target_no : 관련 테이블 no
		upload_name : 업로드 필드명 (주의 : 필드명에 반드시 배열 대괄호[]를 붙여준다. ex. files[] )
		type : 업로드 형태 ( new : 기존 이미지를 전부 지우고 업로드, add : 기존 업로드된 파일에 이어서 업로드)
	*/
	function upload($target_table, $target_no, $upload_name, $type = 'new', $add_data = array())
	{
		$is_temp = 0;
		if (isset($add_data['is_temp'])) {
			$is_temp = 1;
		}

		if($target_table == 'user'){
			$this->mini_size = 100;
		}
		$sub_type = null;
		if (isset($add_data['sub_type'])) {
			$sub_type = $add_data['sub_type'];
		}

		$is_editor_write = 0;
		if (isset($add_data['is_editor_write'])) {
			$is_editor_write = 1;
		}

		$add_folder = '';
		if (isset($add_data['is_month_folder'])) {
			$add_folder = date("Ym") . "/";
		}

		if (!is_dir(UPLOAD['PATH'])) {
			mkdir(UPLOAD['PATH']);
			chmod(UPLOAD['PATH'], 0777);
		}

		$upload_path = UPLOAD['PATH'];
		$s3_save_path = $target_table . '/' . $add_folder;

		// 타입이 new 이면 기존 파일을 지움
		$sort = 1;
		if ($type == 'new') {
			$this->delete($target_table, $target_no);
		} else {
			$sort_query = $this->ci->db->query('SELECT MAX(sort) AS max_sort FROM image WHERE target_table=? AND target_no=?', array($target_table, $target_no));
			if ($sort_query) {
				$sort = $sort_query->row()->max_sort + 1;
			}
		}

		$this->ci->upload->initialize(array(
			"upload_path"   => $upload_path,
			"allowed_types" => 'jpg|jpeg|gif|png',
			"encrypt_name"  => true,
		));

		$files_info = array();
		$insert_data = array();


		if (isset($_FILES[$upload_name])) {

			$files = $_FILES[$upload_name];
			$files_count = count($files['tmp_name']);

			for ($i = 0; $i < $files_count; $i++) {

				$file_i = 'files_' . $i;
				$_FILES[$file_i] = array(
					'name'     => $files['name'][$i],
					'type'     => $files['type'][$i],
					'tmp_name' => $files['tmp_name'][$i],
					'error'    => $files['error'][$i],
					'size'     => $files['size'][$i],
				);

				$is_s3_upload_end = 0;


				if ($this->ci->upload->do_upload($file_i)) {

					$file_info = $this->ci->upload->data();
					$files_info[] = $file_info;
					$s3_upload = $this->ci->s3->upload(UPLOAD['PATH'] . $file_info['file_name'], $s3_save_path);
//					if (!$s3_upload) {
//						//todo 지금까지 업로드 한거 삭제
//					}
//
//
//					if ($file_info['image_width'] > $this->mini_size) {
//						// 미니 생성
//						$this->ci->image_lib->initialize(array(
//							'image_library'  => 'gd2',
//							'source_image'   => $file_info['full_path'],
//							'create_thumb'   => true,
//							'thumb_marker'   => '_mini',
//							'new_image'      => $file_info['raw_name'] . $file_info['file_ext'],
//							'width'          => $this->mini_size,
//							'height'         => 0,
//							'maintain_ratio' => true,
//							'master_dim'     => 'auto',
//						));
//						$this->ci->image_lib->resize();
//					}
//
//					if (!file_exists($upload_path . $file_info['raw_name'] . '_mini' . $file_info['file_ext'])) {
//						copy($file_info['full_path'], $upload_path . $file_info['raw_name'] . '_mini' . $file_info['file_ext']);
//					}
//
//					$s3_upload = $this->ci->s3->upload(UPLOAD['PATH'] . "/" . $file_info['raw_name'] . '_mini' . $file_info['file_ext'], $s3_save_path);

					$this->del_file(UPLOAD['PATH'] . "/" . $file_info['file_name']);
//					$this->del_file(UPLOAD['PATH'] . "/" . $file_info['raw_name'] . '_mini' . $file_info['file_ext']);

					$insert_data[] = array(
						'target_table'    => $target_table,
						'target_no'       => (integer)$target_no,
						'sort'            => $sort,
						'original_img'    => '/' . $s3_save_path . $file_info['file_name'],
						'thumb_img'       => '/' . $s3_save_path . $file_info['file_name'],
						'is_temp'         => $is_temp,
						'is_editor_write' => $is_editor_write,
						'type'            => $sub_type,
						//'origin_name'	  => $this->is_origin_name_save ? $files['name'][$i] : null
					);

					$sort++;

				} else {
					//print_r($this->ci->upload->display_errors());
					$files_info[] = array('error' => $this->ci->upload->display_errors());
				}

			}

			if (count($insert_data) > 0) {
				foreach ($insert_data as $key => $noata) {
					$this->ci->db->insert('image', $noata);
					$insert_no = $this->ci->db->insert_id();
					$insert_data[$key]['no'] = $insert_no;

					$insert_data[$key]['filedata'] = $files_info[$key];
				}
			}

			return $insert_data;

		} else {
			return false;
		}
	}

	/*
		이미지 삭제 하기

		target_table : 관련 테이블
		target_no : 관련 테이블 no
		image_no : 이미지 고유 no (생략가능, 생략했을시 target_table과  target_no의 모든 이미지가 삭제)
	*/
	function delete($target_table = null, $target_no = null, $image_no = null)
	{
		$where = array();
		if ($image_no) {
			$where['no'] = $image_no;
		} elseif ($target_table && $target_no) {
			$where['target_table'] = $target_table;
			$where['target_no'] = $target_no;
		} else {
			return false;
		}

		$image_list = $this->ci->db->get_where('image', $where)->result();
		$s3_images = array();
		if ($image_list) {
			foreach ($image_list as $image_info) {
				$this->ci->db->delete('image', array('no' => $image_info->no));
				array_push($s3_images, array('Key' => substr($image_info->thumb_img, 1)));
				array_push($s3_images, array('Key' => substr($image_info->original_img, 1)));

				$target_table = $image_info->target_table;
				$target_no = $image_info->target_no;
			}
		}

		if (count($s3_images)) {
			if (!IS_TEST_MODE) {
				$s3_upload = $this->ci->s3->delete(array('Objects' => $s3_images));
			}
		}

		// sort를 정렬해준다.
		if ($image_no) {

			$image_r_list = $this->ci->general->db_convert_result($this->ci->db->query("
					SELECT no FROM image WHERE target_table=? AND target_no=? ORDER BY sort ASC
			", array($target_table, $target_no)));
			if ($image_r_list) {
				$sort = 0;
				foreach ($image_r_list as $irl) {
					$sort++;
					$this->ci->db->update("image", array('sort' => $sort), array('no' => $irl->no));
				}
			}
		}

		return true;
	}

	// 폴더 생성
	function _make_dir($mk_dir)
	{
		if (!is_dir($mk_dir)) {
			@mkdir($mk_dir);
			@chmod($mk_dir, 0777);
		} else {
			return false;
		}
		return true;
	}

	// 파일 삭제
	function del_file($file_url)
	{
		$file_path = $file_url;
		if (file_exists($file_path)) {
			@chmod($file_path, 0777);
			@unlink($file_path);
		}
	}


}

