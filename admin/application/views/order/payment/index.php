<div class="ibox-content m-b-sm border-bottom">
    <div class="row">
        <div class="col-sm-1">
            <div class="form-group">
                <label class="control-label">결제자명</label>
                <input type="text" v-model="schFields.user_name" v-on:keyup.enter="readDataFromAPI" class="form-control">
            </div>
        </div>
        <div class="col-sm-1">
            <div class="form-group">
                <label class="control-label" for="status">주문매장</label>
                <select v-model="schFields.order_store" class="form-control">
                    <option value="">전체</option>
                    <option v-for="item in orderStore" :value="item.no">{{ item.name }}</option>
                </select>
            </div>
        </div>
        <div class="col-sm-1">
            <div class="form-group">
                <label class="control-label" for="status">결제상태</label>
                <select v-model="schFields.payment_status" class="form-control">
                    <option value="">전체</option>
                    <option v-for="item in paymentStatus" :value="item.no">{{ item.name }}</option>
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
                <label class="control-label" for="date_added">결제일시</label>
                <div class="input-group">
                    <date-picker v-model="schFields.sdate" value-type="format" format="YYYY-MM-DD"></date-picker>
                    <span class="input-group-addon">~</span>
                    <date-picker v-model="schFields.edate" value-type="format" format="YYYY-MM-DD"></date-picker>
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
                <div>
                    <template>
                    <div>
                        <v-data-table
                                :headers="headers"
                                :items="items"
                                :server-items-length="itemsTotalCount"
                                :loading="loading"
                                :page="page"
                                :pageCount="totalPages"
                                :options.sync="options"
                                :footer-props="{
                                    itemsPerPageOptions: [10, 20, 30,50,100],
                                    itemsPerPageText: '',
                                  }"
                                item-key="order_no"
                                disable-sort
                                class="footable table table-stripped toggle-arrow-tiny"
                                @item-expanded="loadDetails"
                        >
                            <template v-slot:no-data>
                                <v-alert :value="true" color="error">
                                    데이터가 존재하지 않습니다.
                                </v-alert>
                            </template>
                            <template v-slot:item.order_info="{ item }">
                                <a :href="'/order/order?order_no='+item.order_no+''" target="_blank">{{item.order_no}}</a>
                            </template>
                        </v-data-table>
                        <div class="table-footer-prepend d-flex pl-2 align-center">
                            <v-btn class="btn-excel" small @click="downExcel">엑셀다운</v-btn>
                        </div>
                    </div>
                    </template>
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
            vuetify: new Vuetify(),
            data () {
                return {
                    page: 1,
                    itemsTotalCount: 0,
                    totalPages: 10,
                    items: [],
                    loading: true,
                    options: {},
                    headers: [
                        { text: '주문번호', value: 'order_info' },
                        { text: '주문자명', value: 'user_name' },
                        { text: '주문매장', value: 'store_text' },
                        { text: '주문상태', value: 'payment_status_text' },
                        { text: '결제금액', value: 'payment_price' ,align: 'right'},
                        { text: '결제방법', value: 'payment_type_text' },
                        { text: '결제일', value: 'insert_dt' },
                        { text: '취소일', value: 'cancel_dt' },
                        { text: 'PG 주문번호', value: 'pg_oid' },
                        { text: 'PG TID', value: 'pg_tid' },
                    ],
                    orderStore: [],
                    paymentStatus: [],
                    orderPaymentType: [],
                    schFields: {
                        user_name: "",
                        order_store: "",
                        payment_status: "",
                        order_payment_type: "",
                        sdate:"",
                        edate:"",
                    },
                }
            },

            watch: {
                options: {
                    handler() {
                        this.readDataFromAPI();
                    },
                }
            },

            methods: {
                loadDetails({item}) {
                    if (!item.details.length) {
                        axios
                            .get('/order/order/detail/' + item.order_no)
                            .then((res) => {
                                item.details = res.data.result.detail;
                            });
                    }
                },
                readDataFromAPI() {
                    this.loading = true;
                    const {page, itemsPerPage} = this.options;
                    let searchs = Object.entries(this.schFields).map(e => e.join('=')).join('&');

                    axios
                        .get("/order/payment/data?per_page=" + itemsPerPage + "&page=" + page + '&' + searchs)
                        .then((res) => {
                            this.loading = false;
                            this.items = res.data.result.list.map((item) => {
                                return {
                                    details: [],
                                    isSelectable: item.order_status_text != '취소',
                                    ...item
                                }
                            })
                            this.itemsTotalCount = res.data.result.total_count;
                            this.totalPages = 100;
                        });
                },
                getCode() {
                    axios
                        .get('/etc/code/sub_codes/store')
                        .then((res) => {
                            this.orderStore = res.data.result;
                        });

                    axios
                        .get('/etc/code/sub_codes/payment_status')
                        .then((res) => {
                            this.paymentStatus = res.data.result;
                        });

                    axios
                        .get('/etc/code/sub_codes/payment_type')
                        .then((res) => {
                            this.orderPaymentType = res.data.result;
                        });

                },
                downExcel(){
                    let searchs = Object.entries(this.schFields).map(e => e.join('=')).join('&');
                    location.href = "/order/payment/excel_down?per_page=100000&page=1&"+searchs;
                }
            },
            mounted() {
               this.getCode();
            },
        });
    </script>
