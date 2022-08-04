<?php
class Code_model extends Generic_model
{
    function __construct()
    {
        parent::__construct();
    }

    function sub_codes($parent_code, $search = array()){

        $bind_data = array($parent_code);
        $add_sql = '';

		if (isset($search['code'])) {
			$bind_data[] = $search['code'];
			$add_sql = ' AND code = ?';
		}

		if (isset($search['no'])) {
			$bind_data[] = $search['no'];
			$add_sql = ' AND no = ?';
		}

		if (isset($search['is_view'])) {
			$add_sql = ' AND is_view = 1';
		}

        $sql = "
SELECT 
    code, no, name, etc_data, css
FROM
    code
WHERE
    parent_no IN (SELECT no FROM code WHERE code = ?)       
        ". $add_sql." ORDER by sort asc";

        return $this->general->db_convert_result($this->db->query($sql, $bind_data));
    }
}