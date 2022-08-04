<?php
require_once  MODULEPATH.'/models/service/Code_model.php';
class Admin_code_model extends Code_model
{
    function __construct() {
        parent::__construct();
    }

    function lists($parent_no = 0, $sort = 'c.no DESC' ) {
    	$where_sql = '';

    	if($parent_no == 0){
    		$where_sql = ' AND c.is_edit_possible = 1';
		}

        $sql = "
SELECT 
   c.*,
   '-' as parent_name,
    i.original_img as code_image
FROM
   code c
   LEFT OUTER JOIN image i ON i.target_no = c.no AND i.target_table = 'code'
WHERE
    c.parent_no = ?
	".$where_sql."
ORDER BY ".$sort;
        return $this->general->db_convert_result($this->db->query($sql, array($parent_no)));
    }


    function detail($no) {
        $sql = "
SELECT 
   c.*,
   '-' as parent_name,
    i.original_img,
    ino.no as original_img_no,
    id.original_img as code_detail_original_img,
    idn.no as code_detail_original_no
FROM
   code c
   LEFT OUTER JOIN image i ON i.target_no = c.no AND i.target_table = 'code'
   LEFT OUTER JOIN image ino ON ino.target_no = c.no AND ino.target_table = 'code'
   LEFT OUTER JOIN image id ON id.target_no = c.no AND id.target_table = 'code_detail'
   LEFT OUTER JOIN image idn ON idn.target_no = c.no AND idn.target_table = 'code_detail'
WHERE
    c.no = ?
        ";
        return $this->general->db_convert_row($this->db->query($sql, array($no)));
    }
}