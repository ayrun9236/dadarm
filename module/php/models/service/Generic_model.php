<?php

class Generic_model extends CI_Model
{

    /**
     * 테이블명
     *
     * @var string
     */
    protected $table_name;
    protected $where_sql = '';
    protected $where_array = array();


    /**
     * Generic_model constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->table_name = substr(strtolower(get_class($this)), 0, -6);
    }

    /**
     * @param $data
     * @return 시퀀스
     */
    function insert($data)
    {
        $this->db->insert($this->table_name, $data);

        return $this->db->insert_id();
    }

    /**
     * @param $data
     * @param $where
     * @return Boolean값
     */
    function update($data, $where)
    {
        $test_log = array(
            'table'=>$this->table_name,
            'data' => $data,
            'where' => $where
        );

        $this->db->set('update_dt', 'NOW()', FALSE);
        $this->db->update($this->table_name, $data, $where);
        return $this->db->affected_rows();
    }

    /**
     * @param $where
     * @return Boolean값
     */
    function delete($where, $not_in = array())
    {
        if (count($not_in)) {
            $this->db->where_not_in($not_in['name'], $not_in['value']);
        }

        return $this->db->delete($this->table_name, $where);
    }


    /**
     * @param $where
     * @param string $select
     * @return mixed
     */
    function get($where, $select = '*')
    {
        $this->db->select($select);
        return $this->general->db_convert_row($this->db->get_where($this->table_name, $where));
    }


    /**
     * @param $where
     * @param string $select
     * @param string $order
     * @return mixed
     */
    function list($where, $select = '*', $order = '', $limit = '')
    {
        $this->db->select($select);
        if ($order) {
            $this->db->order_by($order);
        }

		if ($limit) {
			$this->db->limit($limit);
		}

        return $this->general->db_convert_result($this->db->get_where($this->table_name, $where));
    }


    /**
     * @param $table_name
     * @return void
     */
    function set_table($table_name)
    {
        $this->table_name = $table_name;
    }


    function set_searchs($searchs){
        $this->where_sql = '';
        $this->where_array = array();

        if (isset($searchs['and'])) {
            foreach ($searchs['and'] as $key => $value) {
                $this->where_sql .= ' AND ' . $key . ' = ? ';
                $this->where_array[] = $value;
            }
        }

		if (isset($searchs['and_in'])) {
			foreach ($searchs['and_in'] as $key => $value) {
				$this->where_sql .= ' AND ' . $key . ' in (?) ';
				$this->where_array[] = $value;
			}
		}

        if (isset($searchs['like'])) {
            foreach ($searchs['like'] as $key => $value) {
                if($key == 'and'){
                    foreach ($value as $sub_key => $sub_value) {
                        $this->where_sql .= " AND " . $sub_key . " LIKE CONCAT('%', ?, '%') ";
                        $this->where_array[] = $sub_value;
                    }
                }
                else{
                    foreach ($value as $sub_key => $sub_value) {
                        $sub_sql = '';
                        foreach ($sub_value as $sub_key1 => $sub_value1) {
                            $sub_sql .= ($sub_sql ? ' OR ' : ' ') . $sub_key1 . " LIKE CONCAT('%', ?, '%') ";
                            $this->where_array[] = $sub_value1;
                        }
                    }

                    $this->where_sql .= ' AND (' . $sub_sql . ')';
                }
            }
        }

        if (isset($searchs['or'])) {
            foreach ($searchs['or'] as $key => $value) {
                foreach ($value as $sub_key => $sub_value) {
                    $sub_sql = '';
                    foreach ($sub_value as $sub_key1 => $sub_value1) {
                        $sub_sql .= ($sub_sql ? ' OR ' : ' ') . $sub_key1 . " LIKE CONCAT('%', ?, '%') ";
                        $this->where_array[] = $sub_value1;
                    }
                }

                $this->where_sql .= ' AND (' . $sub_sql . ')';
            }
        }

        if (isset($searchs['between'])) {
            foreach ($searchs['between'] as $value) {
                foreach ($value as $sub_key => $sub_value) {
                    if(strpos($sub_key, '_dt') !== false){
                        $this->where_sql .= ' AND ' . $sub_key . ' BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY) ';
                    }
                    else{
                        $this->where_sql .= ' AND ' . $sub_key . ' BETWEEN ? AND ? ';
                    }

                    $this->where_array[] = $sub_value[0];
                    $this->where_array[] = $sub_value[1];
                }
            }
        }

        if (isset($searchs['static'])) {
            foreach ($searchs['static'] as $key => $value) {
                if($key == 'and'){
                    foreach ($value as $sub_value) {
                        $this->where_sql .= ' AND ' . $sub_value;
                    }
                }
            }

			$sub_sql = '';
			foreach ($searchs['static'] as $key => $value) {
				if($key == 'or'){
					foreach ($value as $sub_key => $sub_value) {
						$sub_sql .= ($sub_sql ? ' OR ' : ' ') . $sub_value;
					}
				}
			}

			if($sub_sql){
				$this->where_sql .= ' OR (' . $sub_sql . ')';
			}
        }

        return;
    }

    // 이미지
    function image($target_no, $target_table = 'data')
    {
        // TODO 기본이미지셋팅
		$sql = "
SELECT
    i.no AS no,
    i.thumb_img  AS thumb_image,
    i.original_img AS original_image
FROM
    image i
WHERE
    target_no = ? AND target_table=?
ORDER BY sort ASC;";
        return $this->general->db_convert_result($this->db->query($sql, array($target_no, $target_table)));
    }


	function comment_lists($page, $page_row, $searchs = array(), $params = array())
	{
		$offset = ($page - 1) * $page_row;
		$limit = $page_row;
		$this->set_searchs($searchs);
		$where_sql = $this->where_sql;
		$where_array = $this->where_array;

		array_unshift($where_array, $params['user_no']);
		$where_array[] = (int)$offset;
		$where_array[] = (int)$limit;
		$data_sql = "
SELECT 
	b.comment, b.insert_dt, b.post_comment_no ,b.like_count, b.user_no, b.is_delete,
       ui.thumb_img AS user_image,
       b.user_name, case when b.post_comment_no = b.parent_no then 1 else 2 end depth,b.parent_no, case when b.user_no = 0 then 1 else 0 end is_guest
FROM (
	SELECT
	b.*,ifnull(u.user_name,b.guest_name) as user_name
	FROM
   post_comment b
	LEFT OUTER JOIN user u ON u.user_no = b.user_no
WHERE b.user_no not in (select block_user_no from user_block_list bl where bl.user_no = ?)   
        " . $where_sql."
ORDER BY b.parent_no DESC, b.post_comment_no ASC LIMIT ?,?) b
LEFT OUTER JOIN image ui ON ui.target_no = b.user_no AND ui.target_table = 'user' AND ui.sort = 1
ORDER BY b.parent_no DESC, b.post_comment_no ASC
";
		return $this->general->db_convert_result($this->db->query($data_sql, $where_array));
	}

	function post_like_insert($params){
		$sql = "
INSERT INTO post_like (user_no, target_table, target_no)
SELECT 
    user_no, target_table, target_no
FROM
    (SELECT 
        ? user_no, ? target_table, ? target_no
    ) d
WHERE
    NOT EXISTS( SELECT 
            1
        FROM
            post_like pl
        WHERE
            pl.user_no = d.user_no
                AND pl.target_table = d.target_table    	
                AND pl.target_no = d.target_no)    	
    	";

		$this->db->query($sql, array($params['user_no'], $params['target_table'], $params['target_no']));

		return $this->db->affected_rows();
	}

	function post_push_check($target_table, $target_no, $comment_no = 0){

		$bind_data = array($target_no);
		$add_sql = ", null as comment_user_info";
		if($comment_no > 0 ){
			array_unshift($bind_data, $comment_no);
			array_unshift($bind_data, $target_table == 'user_board' ? 'p' : 'g');
			$add_sql = "
,
    (select concat(ifnull(sud.push_id,''),'||',".($target_table == 'user_board' ? 'su.is_push_profile' : 'su.is_push_group').",'||',su.user_no) from post_comment uc
    inner join user su on su.user_no = uc.user_no
    inner join user_detail sud on sud.user_no = su.user_no
    where uc.target_no=ub.user_board_no and uc.target_table =? and uc.post_comment_no=?) comment_user_info			
			";
		}

		if( $target_table == 'user_board'){
			$sql = "
SELECT 
    ud.push_id,u.is_push_profile as is_push_agree,u.user_no ".$add_sql."
FROM
    user_board ub
    inner join user u on ub.user_no = u.user_no
    inner join user_detail ud on ud.user_no = u.user_no
WHERE
    ub.user_board_no = ?; 		
		";
		}
		else {
			$sql = "
SELECT 
    ud.push_id, u.is_push_group as is_push_agree,u.user_no ".$add_sql."
FROM
    user_group_post ub
    inner join user u on ub.user_no = u.user_no
    inner join user_detail ud on ud.user_no = u.user_no
WHERE
    ub.user_group_post_no = ?; 		
		";
		}

		return $this->general->db_convert_row($this->db->query($sql, $bind_data));
	}

	function user_push_check($user_no) {
		$sql = "
SELECT 
    ud.push_id, u.is_push_profile as is_push_agree
FROM
    user u 
    inner join user_detail ud on ud.user_no = u.user_no
WHERE
    u.user_no = ?; 		
		";

	return $this->general->db_convert_row($this->db->query($sql, array($user_no)));
	}

	function block_insert($params){
		$sql = "
INSERT INTO user_block_list (user_no, block_user_no)
SELECT 
    user_no, block_user_no
FROM
    (SELECT 
        ? user_no, ? block_user_no
    ) d
WHERE
    NOT EXISTS( SELECT 
            1
        FROM
            user_block_list pl
        WHERE
            pl.user_no = d.user_no
                AND pl.block_user_no = d.block_user_no)    	
    	";

		$this->db->query($sql, array($params['user_no'], $params['block_user_no']));

		return $this->db->affected_rows();
	}
}