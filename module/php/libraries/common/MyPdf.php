<?php
require THIRD_PARTY . '/TCPDF-main/tcpdf.php';

class MyPdf extends TCPDF
{
	protected $title = '';
	//Page header
	public function Header()
	{
		// Logo
		$image_file = 'http://api.da-daleum.com/resources/images/logo.png';
		$this->Image($image_file, 10, 10, 20, '', 'png', '', 'T', false, 300, '', false, false, 0, false, false, false);
		// Set font
		$this->SetFont('nanumbarungothicyethangul', 'B', 15);
		// Title
		$this->Cell(0, 15, $this->title, 0, false, 'C', 0, '', 0, false, 'M', 'M');
	}

	public function setTitle($title){
		$this->title = $title;
	}
}
