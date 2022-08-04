<!DOCTYPE html>
<html>
<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, minimal-ui">

    <title>다다름</title>

    <link href="/resources/css/bootstrap.min.css" rel="stylesheet">
    <link href="/resources/font-awesome/css/font-awesome.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Roboto:100,300,400,500,700,900" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@mdi/font@4.x/css/materialdesignicons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">

    <link href="/resources/css/animate.css" rel="stylesheet">
    <link href="/resources/css/style.css" rel="stylesheet">


    <link href="https://unpkg.com/vue2-datepicker@3.9.0/index.css" rel="stylesheet">

    <link href="/resources/css/custom.css" rel="stylesheet">

	<?php //todo 링크변경 ?>
    <script src="https://cdn.jsdelivr.net/npm/vue/dist/vue.min.js"></script>
    <script src="https://unpkg.com/vuetify/dist/vuetify.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.js"></script>
    <!--<script src="https://cdn.jsdelivr.net/npm/vue/dist/vue.js"></script>-->
    <script src="https://unpkg.com/axios/dist/axios.min.js"></script>

    <!-- Mainly scripts -->
    <script src="/resources/js/jquery-3.1.1.min.js"></script>
    <script src="/resources/js/bootstrap.min.js"></script>
    <script src="/resources/js/plugins/metisMenu/jquery.metisMenu.js"></script>
    <script src="/resources/js/plugins/slimscroll/jquery.slimscroll.min.js"></script>

    <!-- Custom and plugin javascript -->
    <script src="/resources/js/inspinia.js"></script>
    <script src="/resources/js/plugins/pace/pace.min.js"></script>

    <script src="https://unpkg.com/vue2-datepicker/index.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.24.0/moment.min.js"></script>
    <script src="/resources/js/common.js"></script>
</head>
<body>
<div id="wrapper">
    <nav class="navbar-default navbar-static-side" role="navigation">
        <div class="sidebar-collapse">
            <ul class="nav metismenu" id="side-menu">
                <li class="nav-header">
                </li>
                <?php if(isset($menus)) : ?>
					<?php foreach ($menus as $_key => $_value) : ?>
					<?php if(isset($menus[$_key]['links']['link'])) : ?>
                    <?php $is_sub_menus = count($menus[$_key]) > 2; ?>
                        <li class="<?=strpos($_SERVER['REQUEST_URI'],'/'.$_key.'/') ===0 ? 'active' : ''?>">
                            <a href="<?=$menus[$_key]['links']['link']?>"><i class="fa <?=$_value['links']['icon']?>"></i> <span class="nav-label"><?=$_value['links']['name']?></span><?=$is_sub_menus  ? '<span class="fa arrow"></span>' :''?></a>
                            <?php if($is_sub_menus) : ?>
                                <ul class="nav nav-second-level collapse">
									<?php foreach ($menus[$_key] as $_subkey => $_subvalue) : ?>
									    <?php if($_subkey != 'links' && isset($_subvalue['links'])) : ?>
                                            <li  class="<?=$_SERVER['REQUEST_URI'] == $_subvalue['links']['link'] ? 'active' : ''?>"><a href="<?=$_subvalue['links']['link']?>"><?=$_subvalue['name']?></a></li>
										<?php endif; ?>
									<?php endforeach ?>
                                </ul>
							<?php endif; ?>
                        </li>
						<?php endif; ?>
					<?php endforeach ?>
				<?php endif; ?>
            </ul>

        </div>
    </nav>

    <div id="page-wrapper" class="gray-bg">
        <div class="row border-bottom" style="padding-top: 5px;display: block;">
            <div class="navbar-header">
                <a class="navbar-minimalize minimalize-styl-2 btn btn-primary " href="#"><i class="fa fa-bars"></i> </a>
                <div class="navbar-form-custom">
                        <img src="/resources/img/logo.png" style="width:100px"> Local master
                </div>
            </div>
            <ul class="nav navbar-top-links navbar-right">
                <li class="dropdown">
                    <a class="dropdown-toggle count-info" data-toggle="dropdown" href="#">
                        <i class="fa fa-envelope"></i>  <?=$this->auth->info()->name?></strong> Login+
                    </a>
                    <ul class="dropdown-menu m-t-xs">
                        <li>
                            <a href="/admin/user/user_password_change">비밀번호 변경</a>
                        </li>
                        <li class="divider"></li>
                        <li><a href="/login/logout">Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
        <?php if(isset($menus[$page_content['menu1']])):?>
        <div class="row wrapper border-bottom white-bg page-heading">
            <div class="col-lg-10">
                <ol class="breadcrumb">
                    <li>
                        <a href="/">Home</a>
                    </li>
                    <li>
                        <a><?= $menus[$page_content['menu1']]['links']['name'] ?></a>
                    </li>
                    <li class="active">
                        <strong><?= $menus[$page_content['menu1']][$page_content['menu2_origin']]['name'] ?></strong>
                    </li>
                </ol>
            </div>
            <div class="col-lg-2">
            </div>
        </div>
        <?php endif;?>
        <div id="app" class="wrapper wrapper-content animated fadeInRight ecommerce">
			<?php
			if ($page_content) {
			    unset($page_content['menu2_origin']);
				$this->load->view(join('/', $page_content));
			}
			?>
        </div>
        <div class="footer">
            <div>
                <strong>Copyright</strong> 다다름 &copy; 2021
            </div>
        </div>
    </div>
</div>
</body>
</html>
