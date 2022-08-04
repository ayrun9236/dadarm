<div class="ibox-content m-b-sm border-bottom">
    <div class="row">
        <div class="col-sm-1">
            <div class="form-group">
                <label class="control-label" for="status">주문형태</label>
                <select v-model="schFields.order_type" class="form-control">
                    <option value="">전체</option>
                    <option v-for="item in orderType" :value="item.no">{{ item.name }}</option>
                </select>
            </div>
        </div>
        <div class="col-sm-1">
            <div class="form-group">
                <label class="control-label" for="status">주문상태</label>
                <select v-model="schFields.order_status" class="form-control">
                    <option value="">전체</option>
                    <option v-for="item in orderStatus" :value="item.no">{{ item.name }}</option>
                </select>
            </div>
        </div>
        <div class="col-sm-1">
            <div class="form-group">
                <label class="control-label" for="status">결제방법</label>
                <select v-model="schFields.order_payment_type" class="form-control">
                    <option value="">전체</option>
                    <option v-for="item in orderPaymentType" :value="item.no">{{ item.name }}</option>
                </select>
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-group">
                <label class="control-label" for="date_added">주문일</label>
                <div class="input-group">
                    <!--                    <date-picker v-model="schFields.order_sdate" type="datetime" ></date-picker>-->
                    <date-picker v-model="schFields.order_sdate" value-type="format" format="YYYY-MM-DD"></date-picker>
                    <span class="input-group-addon">~</span>
                    <date-picker v-model="schFields.order_edate" value-type="format" format="YYYY-MM-DD"></date-picker>
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
                <div class="flot-chart">
                    <div class="flot-chart-content" id="flot-line-chart"></div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="/resources/js/plugins/flot/jquery.flot.js"></script>
<script src="/resources/js/plugins/flot/jquery.flot.tooltip.min.js"></script>
<script src="/resources/js/plugins/flot/jquery.flot.resize.js"></script>
<script src="/resources/js/plugins/flot/jquery.flot.pie.js"></script>
<script src="/resources/js/plugins/flot/jquery.flot.time.js"></script>
    <script>
        $(function() {

            var barOptions = {
                series: {
                    lines: {
                        show: true,
                        lineWidth: 2,
                        fill: true,
                        fillColor: {
                            colors: [{
                                opacity: 0.0
                            }, {
                                opacity: 0.0
                            }]
                        }
                    }
                },
                xaxis: {
                    mode: 'time',
                    timeformat: "%m.%d",
                    tickSize: [1, "day"],
                },

                colors: ["#1ab394"],
                grid: {
                    color: "#999999",
                    hoverable: true,
                    clickable: true,
                    tickColor: "#D4D4D4",
                    borderWidth:0
                },
                legend: {
                    show: true
                },
                tooltip: true,
                tooltipOpts: {
                    content: "%s %x - %y원"
                },colors: ["#1ab394",'blue','red','green','black'],
            };
            var barData = [
				<?php foreach($graph as $key => $value):?>
                    <?php if($key == 0 || $graph[$key-1]->store_no != $graph[$key]->store_no):?>
                        <?=$key>0?  ']},':''?>
                        {label: "<?=$value->store_name?>",
                        data: [
                    <?php endif;?>
                    [<?=strtotime($value->ddate)*1000?>, <?=$value->total_price?>],
                            <?php endforeach;?>
                    ]}];
            $.plot($("#flot-line-chart"), barData, barOptions);

        });
    </script>
<script>
    document.body.setAttribute('data-app', true)

    const headers = {
        'content-type': 'application/json;charset=UTF-8',
    }

    new Vue({
        el: '#app',
        data () {
            return {
                orderStatus: [],
                orderType: [],
                orderTypeStatus: [],
                orderPaymentType: [],
                schFields: {
                    order_type: "<?=$sch_order_type?>",
                    order_status: "<?=$sch_order_status?>",
                    order_payment_type: "<?=$sch_payment_type?>",
                    order_sdate:"<?=$sch_sdate?>",
                    order_edate:"<?=$sch_edate?>",
                },
            }
        },
        methods: {
            readDataFromAPI() {

                if(this.schFields.order_sdate == "" || this.schFields.order_edate == ""){
                    alert("주문일이 존재해야합니다.");
                    return false;
                }

                var sdt = new Date(this.schFields.order_sdate);
                var edt = new Date(this.schFields.order_edate );
                var dateDiff = Math.ceil((edt.getTime()-sdt.getTime())/(1000*3600*24));

                if(dateDiff>31){
                    alert('주문일은 최대 31일까지 검색 가능합니다.');
                   return false;
                }

                document.location.href='?'+ Object.entries(this.schFields).map(e => e.join('=')).join('&');

            },
            getCode(){

                axios
                    .get('/etc/code/sub_codes/order_status')
                    .then((res) => {
                        this.orderStatus = res.data.result;
                    });

                axios
                    .get('/etc/code/sub_codes/payment_type')
                    .then((res) => {
                        this.orderPaymentType = res.data.result;
                    });

                axios
                    .get('/etc/code/sub_codes/order_type')
                    .then((res) => {
                        this.orderType = res.data.result;
                    });
            },

        },
        mounted() {
            this.getCode();
        },
    });
</script>
