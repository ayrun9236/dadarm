<?php

/**
 * Created by PhpStorm.
 * User: SIL
 * Date: 2021-02-01 오전 11:55
 */
class Webview
{
    protected $ci;

    function __construct()
    {
        $this->ci =& get_instance();
    }

    function basic_setting()
    {

		$login_data = $this->ci->auth->info();
		$admin_group_no = -1;
		if(isset($login_data->admin_group_no)){
			$admin_group_no = $login_data->admin_group_no;
			if($login_data->is_grant_all){
				$admin_group_no = 0;
			}
		}

        $foler = $this->ci->uri->segment(1);
        $ctr_name = $this->ci->uri->segment(2);
        $method = $this->ci->uri->segment(3) ? $this->ci->uri->segment(3) : 'index';

        $setting['page_content'] = array(
            'menu1' => $foler,
            'menu2' => $ctr_name,
            'menu2_origin' => $ctr_name,
            'menu3' => $method);

        if($admin_group_no == 0){
			$_menus = $this->ci->general->db_convert_result($this->ci->db->query('SELECT * FROM admin_menu where is_view=1 ORDER BY depth, sort'));
		}
		else{
			$sql = "
SELECT 
    am.*
FROM
    admin_menu am
	INNER JOIN admin_group_menu_grant agmg ON agmg.admin_menu_no = am.no
WHERE
    agmg.admin_group_no = ? and am.is_view=1 
    union
SELECT 
    am1.*
FROM
    admin_menu am
	INNER JOIN admin_group_menu_grant agmg ON agmg.admin_menu_no = am.no
    INNER JOIN admin_menu am1 ON am.link like concat(am1.link,'%') and am1.depth = 1
WHERE
    agmg.admin_group_no = ? and am.is_view=1 
ORDER BY depth , sort";
			$_menus = $this->ci->general->db_convert_result($this->ci->db->query($sql, array($admin_group_no, $admin_group_no)));
		}

		$menus = array();
		foreach($_menus as $item){
			if($item->depth == 1){
				$menus[str_replace('/','', $item->link)]['links'] = array('name' => $item->name, 'icon' => $item->icon);
			}
			else{
				$menu_slice = explode('/', $item->link);
				if(isset($menus[$menu_slice[1]]['links'])) {
					if ($item->depth == 2 && $item->sort == 1) {
						$menus[$menu_slice[1]]['links'] = array_merge($menus[$menu_slice[1]]['links'], array('link' => $item->link));
					}

					if (!isset($menus[$menu_slice[1]]['links']['link'])) {
						$menus[$menu_slice[1]]['links'] = array_merge($menus[$menu_slice[1]]['links'], array('link' => $item->link));
					}

					$menus[$menu_slice[1]][$menu_slice[2]] = array(
						'name'  => $item->name,
						'links' => array('link' => $item->link)
					);
				}
			}
		}

		if ($login_data && $login_data->login_id == 'admin') {
			//todo 관리자

			$menus['admin']['links'] = array('name' => '관리자관리', 'icon' => 'fa-lock');
			$menus['admin']['links']['link'] = '/admin/group';

			$menus['admin']['group'] = array(
				'name' => '그룹관리',
				'links' => array('link' => '/admin/group')
			);

			$menus['admin']['user'] = array(
				'name' => '관리자관리',
				'links' => array('link' => '/admin/user')
			);

		}

		$setting['i'] = 0;
        $setting['menus'] = $menus;
        return $setting;
    }

}
