<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once MODULEPATH ."/third_party/ExcelWriterXML/ExcelWriterXML.php";

class Excelxml extends ExcelWriterXML {
    public function __construct() {
        parent::__construct();
    }
}
