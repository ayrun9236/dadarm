<?php

/**
 * Created by PhpStorm.
 * User: SIL
 * Date: 2021-04-01 오전 11:07
 */
class Image extends MY_Controller
{
    function __construct()
    {
        parent::__construct();
    }

    /**
     * 스마트 에디터 이미지를 업로드
     */
    public function smarteditor($ed_nonce='')
    {
        $this->load->helper('array');
        $this->load->library('common/image_upload');

        $image_target = $_REQUEST['image_target'];
        $image_temp_no = $_REQUEST['image_temp_no'];
        $image_target_no = $_REQUEST['image_target_no'];

        $is_temp = 1;
        if($image_target_no > 0){
            $is_temp = 0;
        }
        else{
            $image_target_no = $image_temp_no;
        }

        $file_add_data = array(
        	'is_temp' => $is_temp,
        	'is_editor_write' => 1,
		);

		$upload_result = $this->image_upload->upload($image_target, $image_target_no, 'files', 'add', $file_add_data);

        $info = new stdClass();
        $info->url = UPLOAD['S3_URL'] .$upload_result[0]['original_img'];

        $info->width = element('image_width', $upload_result[0]['filedata'])
            ? element('image_width', $upload_result[0]['filedata']) : 0;
        $info->height = element('image_height', $upload_result[0]['filedata'])
            ? element('image_height', $upload_result[0]['filedata']) : 0;

        $return['files'][0] = $info;

        $this->output->set_output(json_encode($return));

    }
}