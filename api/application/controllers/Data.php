<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Data extends CI_Controller {

	public function child_introduce_down()
	{

		$this->load->library('service/service_user');
		$this->load->library('Auth');

		$no = $this->input->post('no');
		$no = 5;
		$login_user = $this->auth->info('post');
		if($login_user === false){
			//잘못된 접근
			echo '로그인 정보가 존재하지 않습니다.';
			exit;
		}

		$data = $this->service_user->child_introduce_detail($login_user->user_no, $no);
		$this->load->library('common/MyPdf');
		$pdf = new MyPdf(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, "UTF-8", false);
		$pdf->setTitle($data->title);
		$pdf->SetCreator(PDF_CREATOR);
		// set default header data
		$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);
		// set header and footer fonts
		$pdf->setHeaderFont(array('nanumbarungothicyethangul', '', PDF_FONT_SIZE_MAIN));
		$pdf->setFooterFont(array('nanumbarungothicyethangul', '', PDF_FONT_SIZE_DATA));
		// set default monospaced font
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
		// set margins
		$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
		$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
		// set auto page breaks
		$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
		// set image scale factor
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
		$pdf->SetFont('nanumbarungothicyethangul', '', 12);
		$pdf->AddPage();
		$pdf->writeHTMLCell(0, 0, "", "", $data->contents, 0, 1, 0, true, "", true);
//		$name = '홍길동';
//		$tel = '010-2112-1234';
//		$yyyy = '2019';
//		$mm = '12';
//		$dd = '24';
//		$pdfData = '';
//		$pdfData .= "<div align=\"center\">";
//		$pdfData .= "<h1>테이블 예시</h1>";
//		$pdfData .= "<table border=\"1\" cellpadding=\"5\" cellspacing=\"0\">";
//		$pdfData .= "<thead>";
//		$pdfData .= "<tr><th colspan=\"4\" bgcolor=\"#ddd\" align=\"center\">고객 정보</th></tr>";
//		$pdfData .= "</thead>";
//		$pdfData .= "<tbody>";
//		$pdfData .= "<tr>";
//		$pdfData .= "<th>성명</th>";
//		$pdfData .= "<td>" . $name . "</td>";
//		$pdfData .= "<th>연락처</th>";
//		$pdfData .= "<td>" . $tel . "</td>";
//		$pdfData .= "</tr>";
//		$pdfData .= "<tr>";
//		$pdfData .= "<th>일정</th>";
//		$pdfData .= "<td colspan=\"3\">";
//		$pdfData .= $yyyy . '년 ' . $mm . '월 ' . $dd . '일';
//		$pdfData .= "</td>";
//		$pdfData .= "</tr>";
//		$pdfData .= "</table>";
//		$pdfData .= "</div>";
		// 테이블을 출력할 때 사용하는 방법
//		$pdf->writeHTML($pdfData, true, false, false, false, '');
		$pdf->Output(getcwd() . "/example_test_01.pdf", "D");

	}
}
