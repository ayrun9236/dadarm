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
                <label class="control-label" for="date_added">기준일</label>
                <div class="input-group">
                    <date-picker v-model="schFields.ssdate" value-type="format" format="YYYY-MM-DD"></date-picker>
                    <span class="input-group-addon">~</span>
                    <date-picker v-model="schFields.sedate" value-type="format" format="YYYY-MM-DD"></date-picker>
                </div>
            </div>
        </div>
        <div class="col-sm-1">
            <div class="form-group">
                <label class="control-label" for="view_type">구분</label>
                <select v-model="schFields.view_type" class="form-control">
                    <option v-for="item in viewType" :value="item.no">{{ item.name }}</option>
                </select>
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
                        <th class="text-center">#</th>
                        <th class="text-center">회원가입수</th>
                        <th class="text-center">주문수</th>
                        <th class="text-center">주문금액</th>
                    </tr>
                    </thead>
                    <tbody>
					<?php foreach($summary as $key => $value):?>
                    <tr>
                        <td class="text-center"><?=$value->ddate ? $value->ddate : '합계'?></td>
                        <td class="text-right"><?=number_format($value->user_join_count)?></td>
                        <td class="text-right"><?=number_format($value->total_order_count)?></td>
                        <td class="text-right"><?=number_format($value->total_price)?></td>
                    </tr>
                    <?php endforeach;?>
                    </tbody>
                </table>
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
                viewType: [{no: 'date',name : '일별'},{no: 'month',name : '월별'}],
                schFields: {
                    order_store: "<?=$sch_order_store?>",
                    view_type: "<?=$sch_view_type?>",
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
