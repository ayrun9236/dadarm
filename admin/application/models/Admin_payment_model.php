<?php
require_once (MODULEPATH.'/models/service/Payment_model.php');
class Admin_payment_model extends Payment_model
{
    function __construct()
    {
        parent::__construct();
    }

}