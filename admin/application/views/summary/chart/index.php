<div class="ibox-content m-b-sm border-bottom">
    <div class="row">
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
                <div id="lineChart"></div>
            </div>
        </div>
    </div>
</div>
<link href="/resources/css/plugins/c3/c3.min.css" rel="stylesheet">
<script src="/resources/js/plugins/d3/d3.min.js"></script>
<script src="/resources/js/plugins/c3/c3.min.js"></script>
    <script>
        $(function() {

            var x = ['x'];
            var total_price = ['전체'];
            var delivery_total_price = ['배달'];
            var pickup_total_price = ['매장방문'];

			<?php foreach($graph as $key => $value):?>
                delivery_total_price.push(<?=$value->delivery_total_price?>);
                pickup_total_price.push(<?=$value->pickup_total_price?>);
                total_price.push(<?=$value->delivery_total_price+$value->pickup_total_price?>);
                x.push('<?=$value->ddate?>');
            <?php endforeach;?>

            c3.generate({
                size: {
                    height: 480
                },
                bindto: '#lineChart',
                data:{
                    x: 'x',
                    columns: [
                        x,
                        total_price,
                        delivery_total_price,
                        pickup_total_price
                    ],
                    colors:{
                        data1: '#1ab394',
                        data2: '#BABABA'
                    }
                },
                axis: {
                    x: {
                        type: 'timeseries',
                        tick: {
                            format: '%m-%d',
                            culling: false
                        }
                    },
                },
                tooltip: {
                    format: {
                        value: function(value) {
                            return d3.format(",.0f")(value)
                        }
                    }
                }
            });
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
                orderStore: [],
                schFields: {
                    order_store: "<?=$sch_order_store?>",
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

                // var sdt = new Date(this.schFields.order_sdate);
                // var edt = new Date(this.schFields.order_edate );
                // var dateDiff = Math.ceil((edt.getTime()-sdt.getTime())/(1000*3600*24));
                //
                // if(dateDiff>31){
                //     alert('주문일은 최대 31일까지 검색 가능합니다.');
                //    return false;
                // }

                document.location.href='?'+ Object.entries(this.schFields).map(e => e.join('=')).join('&');

            },
            getCode(){
                axios
                    .get('/etc/code/sub_codes/store')
                    .then((res) => {
                        this.orderStore = res.data.result;
                    });

            },

        },
        mounted() {
            this.getCode();
        },
    });
</script>
