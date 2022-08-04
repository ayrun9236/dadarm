<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="decorator" content="none"/>
    <title>앱 주문서</title>
    <style type="text/css">
        body {font-size: 15px; font-weight: bold}
        @media print {
            body {
                width: 80mm;
                margin: 0;
            }

            header, .no-print { display:none }
            @page { margin: 0; }

        }

        div.line{font-size: 12px !important; font-weight: normal !important;}
        .basic{font-size: 12px !important;}
        table td{vertical-align: text-top}
        .section{page-break-before:always;}
        .center{text-align: center}
        .right{text-align: right}
        .receipt_btn {
            display: inline-block;
            height: 28px;
            font-size: 0;
            background-image: url(/resources/img/sp_receipt2.png);
            background-size: 305px 153px;
        }

        .receipt_btn.btn_print {
            background-position: -155px -30px;
            width: 49px;
        }

        .receipt_btn.btn_close {
            background-position: -155px 0px;
            width: 49px;
        }
    </style>
</head>
<body>
<!--1. 매장방문 ->총 배말팀이 사라지고 매장방문-->
<!--2. 옵션명관련 -> +로 계속노출-->
<!--3.-->
<!--<img src="/resources/img/_temp/sample.jpg" width="300">-->
<div>
    <?php foreach ($orders as $item):?>
        <div>
            <div class="line">┌--------------------------------------------------------┐</div>
            <div class="center">앱 주문서</div>
            <div class="line">└--------------------------------------------------------┘</div>
        </div>
        <div id="container">
            <div>주문번호 <?=$item['order_no']?></div>
            <div>결제방식 <?=$item['payment_type_text']?></div>
            <div>주문형태 : <?=$item['order_type_text']?></div>
			<?php if($item['order_type_code'] == 'PICKUP'):?>
                <div>주문매장 : <?=$item['store_text']?></div>
			<?php else:?>
                <div>주소 : <?=$item['delivery_address']?> <?=$item['delivery_address_detail']?></div>
            <?php endif;?>
            <div>연락처 : <?=$item['user_phone']?></div>
            <div class="line">-------------------------------------------------------------</div>
            <div class="basic">요청사항:</div>
            <div>가게: <?=$item['request_memo']?></div>
<!--            <div>배달: 조심히 와주세요.</div>-->
            <div class="line">-------------------------------------------------------------</div>
            <div>
                <table cellspacing="0" border="0" style="width:100%">
                    <colgroup>
                        <col width="95%">
                        <col width="5%">
                    </colgroup>
                    <tbody>
                    <?php foreach ($item['detail'] as $sub_item):?>
                    <tr>
                        <td><?=$sub_item->product_name?> <?=$sub_item->size?><?=$sub_item->topping ? '<br>+'.$sub_item->topping : ''?></td>
                        <td class="right"><?=$sub_item->order_quantity?></td>
                    </tr>
                    <?php endforeach;?>
                    <tr><td style="color: #fff;font-size: 10px">.</td><td></td></tr>
					<?php if($item['gifts'] != ''):?>
                    <tr><td>★ <?=$item['gifts']?> ★</td><td>★</td></tr>
					<?php endif;?>
                    </tbody>
                </table>
            </div>
			<?php if($item['coupon_name'] != ''):?>
                <div class="line">-------------------------------------------------------------</div>
                <div>
                    <table cellspacing="0" border="0" style="width:100%">
                        <colgroup>
                            <col width="70%">
                            <col width="30%">
                        </colgroup>
                        <tbody>
                        <tr>
                            <td class="basic">쿠폰 <?=$item['coupon_name']?></td>
                            <td class="basic right">-<?=number_format($item['origin_price']-$item['total_price'])?></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
			<?php endif;?>
			<?php if($item['order_type_code'] == 'DELIVERY'):?>
            <div class="line">-------------------------------------------------------------</div>
            <div class="basic">총 배달팁 <?=number_format($item['delivery_price'])?></div>
			<?php endif;?>
            <div class="line">-------------------------------------------------------------</div>
            <div>
                <table cellspacing="0" border="0" style="width:100%">
                    <colgroup>
                        <col width="20%">
                        <col width="80%">
                    </colgroup>
                    <tbody>
                    <tr>
                        <td>합계</td>
                        <td class="right"><?=number_format($item['total_price'])?></td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <div class="line">-------------------------------------------------------------</div>
            <div class="basic"><?=date("Y.m.d H:i")?></div>
            <div style="color: #fff;font-size: 20px">.</div>
        </div>
<!--            <div class="section" style="page-break-after:always;page-break-inside:avoid;"></div>-->
        <?php if(isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'],'/order/oprint/') === false):?>
            <iframe src="<?=$_SERVER['REQUEST_URI']?>" style="width: 0;height: 0"></iframe>
        <?php endif;?>
    <?php endforeach;?>
</div>
<!-- pop_footer -->
<footer id="pop_footer" class="pop_footer">
    <a href="javascript:;" onclick="window.print();" class="receipt_btn btn_print">인쇄</a>
    <a href="javascript:;" onclick="top.close();" class="receipt_btn btn_close">닫기</a>
</footer>
<!-- //pop_footer -->
<script type="text/javascript">
    window.print();
</script>
</body>
</html>