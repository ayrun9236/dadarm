<?php
			if ($page_content) {
			    unset($page_content['menu2_origin']);
				$this->load->view(join('/', $page_content));
			}
			?>
