<div class="ibox-content m-b-sm border-bottom">
    <div class="row">
        <div class="col-sm-2">
            <div class="form-group">
                <label class="control-label" for="status">카테고리</label>
                <select v-model="schFields.product_type" class="form-control">
                    <option value="">전체</option>
                    <option v-for="item in productType" :value="item.no">{{ item.name }}</option>
                </select>
            </div>
        </div>
        <div class="col-sm-1">
            <div class="form-group">
                <label class="control-label" for="status">매장</label>
                <select v-model="schFields.order_store" class="form-control">
                    <option value="">전체</option>
                    <option v-for="item in orderStore" :value="item.no">{{ item.name }}</option>
                </select>
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-group">
                <label class="control-label" for="date_added">주문일</label>
                <div class="input-group">
                    <date-picker v-model="schFields.ssdate" value-type="format" format="YYYY-MM-DD"></date-picker>
                    <span class="input-group-addon">~</span>
                    <date-picker v-model="schFields.sedate" value-type="format" format="YYYY-MM-DD"></date-picker>
                </div>
            </div>
        </div>
        <div class="col-sm-1 text-right">
            <div class="form-group">
                <label class="control-label" for="status" style="color:#fff">.</label>
                <div class="input-group-btn"><button class="btn btn-primary btn-xs" @click="readDataFromAPI">검색 </button></div>
            </div>

        </div>
    </div>
</div>

<div class="row">

    <div class="col-lg-12">
        <div class="ibox">
            <div class="ibox-content">
                <table class="table table-bordered">
                    <thead>
                    <tr>
                        <th class="text-center">카테고리</th>
                        <th class="text-center">메뉴</th>
                        <th class="text-center">판매수량</th>
                        <th class="text-center">취소건수</th>
                        <th class="text-center">판매금액</th>
                        <th class="text-center">취소금액</th>
                        <th class="text-center">합계</th>
                        <th class="text-center">판매율</th>
                    </tr>
                    </thead>
                    <tbody>
					<?php foreach($summary as $key => $value):?>
                    <tr>
                        <td class="text-center"><?=$value->product_name ? $value->name : '합계'?></td>
                        <td><?=$value->product_name ? $value->product_name : '합계'?></td>
                        <td class="text-right"><?=number_format($value->order_count)?></td>
                        <td class="text-right"><?=number_format($value->cancel_count)?></td>
                        <td class="text-right"><?=number_format($value->order_price)?></td>
                        <td class="text-right"><?=number_format($value->cancel_price)?></td>
                        <td class="text-right"><?=number_format($value->order_price-$value->cancel_price)?></td>
                        <td class="text-right"><?=$value->order_rate?>%</td>
                    </tr>
                    <?php endforeach;?>
                    </tbody>
                </table>
                <div class="">
                    <v-btn class="btn-excel" small @click="downExcel" style="margin-bottom: 10px">엑셀다운</v-btn>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.body.setAttribute('data-app', true)

    const headers = {
        'content-type': 'application/json;charset=UTF-8',
    }

    new Vue({
        el: '#app',
        data () {
            return {
                orderStore: [],
                productType : [],
                viewType: [{no: 'date',name : '일별'},{no: 'month',name : '월별'}],
                schFields: {
                    order_store: "<?=$sch_order_store?>",
                    product_type: "<?=$sch_product_type?>",
                    ssdate:"<?=$sch_sdate?>",
                    sedate:"<?=$sch_edate?>",
                },
            }
        },
        methods: {
            readDataFromAPI() {

                if(this.schFields.order_sdate == "" || this.schFields.order_edate == ""){
                    alert("주문일이 존재해야합니다.");
                    return false;
                }

                document.location.href='?'+ Object.entries(this.schFields).map(e => e.join('=')).join('&');

            },

            getCode(){
                axios
                    .get('/etc/code/sub_codes/store')
                    .then((res) => {
                        this.orderStore = res.data.result;
                    });

                axios
                    .get('/etc/code/sub_codes/PRODUCT_TYPE')
                    .then((res) => {
                        this.productType = res.data.result;
                    });
            },
            downExcel(){
                let searchs = Object.entries(this.schFields).map(e => e.join('=')).join('&');
                location.href = "/summary/menu_order/excel_down?per_page=100000&page=1&"+searchs;
            }

        },
        mounted() {
            this.getCode();
        },
    });
</script>
