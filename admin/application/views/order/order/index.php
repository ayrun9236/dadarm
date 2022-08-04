<div class="ibox-content m-b-sm border-bottom">
    <div class="row">
        <div class="col-sm-1">
            <div class="form-group">
                <label class="control-label">주문번호</label>
                <input type="text" v-model="schFields.order_no" v-on:keyup.enter="readDataFromAPI" class="form-control">
            </div>
        </div>
        <div class="col-sm-1">
            <div class="form-group">
                <label class="control-label">주문자명</label>
                <input type="text" v-model="schFields.user_name" v-on:keyup.enter="readDataFromAPI" class="form-control">
            </div>
        </div>
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
                <label class="control-label" for="status">주문매장</label>
                <select v-model="schFields.order_store" class="form-control">
                    <option value="">전체</option>
                    <option v-for="item in orderStore" :value="item.no">{{ item.name }}</option>
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
                <div>
                    <template>
                    <div>
                        <v-data-table
                                v-model="selected"
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
                                :expanded="expanded"
                                show-expand
                                show-select
                                item-key="order_no"
                                disable-sort
                                class="footable table table-stripped toggle-arrow-tiny"
                                @item-expanded="loadDetails"
                        >
                            <template v-slot:top>
                                <v-snackbar v-model="showMessage.show" color="info" timeout="-1" centered >
                                    {{ showMessage.message }}
                                    <template v-slot:action="{ attrs }">
                                        <v-btn text v-bind="attrs" @click="close"> Close </v-btn>
                                    </template>
                                </v-snackbar>
                                <v-toolbar flat >
                                    <v-spacer></v-spacer>
                                    <div class="actions clearfix">
                                        <div class="float--right clearfix">
                                            <v-btn class="test" small @click="orderStatusModify">주문상태 변경</v-btn>
                                        </div>
                                    </div>
                                    <div class="actions clearfix">
                                        <div class="float--right clearfix">
                                            <v-btn class="test" small @click="orderSelectPrint">출력</v-btn>
                                        </div>
                                    </div>

                                    <v-dialog v-model="dialogOrderStatusModify" max-width="500px">
                                        <v-card>
                                            <v-card-title class="headline">선택한 주문 상태변경</v-card-title>
                                            <v-divider></v-divider>
                                            <v-card-text>
                                                <v-container>
                                                <v-row>
                                                    <v-col cols="12" sm="6" md="4">
                                                    <v-select
                                                            v-model="editOrderStatus"
                                                            :items="orderTypeStatus"
                                                            item-text="name"
                                                            item-value="no"
                                                            label="주문상태"
                                                            persistent-hint
                                                    ></v-select>
                                                    </v-col>
                                                        <v-col cols="12" sm="6" md="4">
                                                            <v-select
                                                                    v-model="editOrderPickupTime"
                                                                    v-if="viewOrderPickupTime"
                                                                    :items="orderPickupTime"
                                                                    item-text="name"
                                                                    item-value="no"
                                                                    label="배달완료예정시간"
                                                                    persistent-hint
                                                            ></v-select>
                                                        </v-col>
                                                </v-row>
                                                </v-container>
                                            </v-card-text>
                                            
                                            <v-card-actions>
                                                <v-spacer></v-spacer>
                                                <v-btn color="blue darken-1" text @click="close">Cancel</v-btn>
                                                <v-btn color="blue darken-1" text @click="editedOrderStatusConfirm">OK</v-btn>
                                            </v-card-actions>
                                        </v-card>
                                    </v-dialog>

                                    <v-dialog v-model="dialogUserDetail" max-width="1000px">
                                        <v-card>
                                            <v-toolbar
                                                    flat
                                                    color="primary"
                                                    style="background-color: #37474F"
                                            >
                                                <v-toolbar-title style="color:#fff">회원상세
                                                </v-toolbar-title>
                                                <v-flex class="text-right">
                                                    <v-btn
                                                            style="color:#fff"
                                                            text
                                                            @click="close"
                                                    >
                                                        Close
                                                    </v-btn>

                                                </v-flex>
                                            </v-toolbar>
                                            <v-tabs vertical v-model="selectedTab">
                                                <v-tab>
                                                    <v-icon left>
                                                        mdi-cart
                                                    </v-icon>
                                                    주문내역
                                                </v-tab>
                                                <v-tab>
                                                    <v-icon left>
                                                        mdi-gift
                                                    </v-icon>
                                                    쿠폰내역
                                                </v-tab>
                                                <v-tab>
                                                    <v-icon left>
                                                        mdi-infinity
                                                    </v-icon>
                                                    회원상세
                                                </v-tab>
                                                <v-tab-item>
                                                    <div>
                                                        <v-data-table
                                                                :headers="userOrderheaders"
                                                                :items="userOrderItem"
                                                                :expanded="expanded"
                                                                show-expand
                                                                single-expand
                                                                item-key="order_no"
                                                                disable-sort
                                                                class="footable table table-stripped toggle-arrow-tiny"
                                                                @item-expanded="orderloadDetails"
                                                        >
                                                            <template v-slot:item.order_view="{ item }">
                                                                {{item.order_is_view ? 'Y' : 'N' }}
                                                            </template>
                                                            <template v-slot:item.etc="{ item }">
                                                                {{item.delivery_price}}
                                                            </template>

                                                            <template v-slot:no-data>
                                                                <v-alert :value="true" color="error">
                                                                    데이터가 존재하지 않습니다.
                                                                </v-alert>
                                                            </template>

                                                            <template v-slot:expanded-item="{ headers, item}">
                                                                <td :colspan="headers.length">
                                                                    <v-simple-table dense
                                                                                    style="background-color:#bababa;margin:20px">
                                                                        <template v-slot:default>
                                                                            <tbody>
                                                                            <tr>
                                                                                <th class="text-left" style="width:150px;">
                                                                                    요청메모
                                                                                </th>
                                                                                <td class="text-left">{{item.request_memo}}</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <th class="text-left">
                                                                                    주소
                                                                                </th>
                                                                                <td class="text-left">{{item.delivery_address}} {{item.delivery_address_detail}}</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <th class="text-left">
                                                                                    사용쿠폰명
                                                                                </th>
                                                                                <td class="text-left">{{item.coupon_name}}</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <th class="text-left">
                                                                                    증정품
                                                                                </th>
                                                                                <td class="text-left">{{item.gifts}}</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <th class="text-left">
                                                                                    픽업예정시간
                                                                                </th>
                                                                                <td class="text-left">{{item.order_type_code =='PICKUP' ? item.order_pickup_time : '-'}}</td>
                                                                            </tr>
                                                                            </tbody>
                                                                        </template>
                                                                    </v-simple-table>
                                                                    <v-data-table
                                                                            dense
                                                                            :headers="userOrderdetailHeaders"
                                                                            :items="userOrderItem_details"
                                                                            item-key="id"
                                                                            style="background-color:#bababa;margin:20px"
                                                                            hide-default-footer
                                                                            disable-sort
                                                                            class="elevation-1"
                                                                    >
                                                                    </v-data-table>
                                                                </td>
                                                            </template>
                                                        </v-data-table>
                                                    </div>
                                                </v-tab-item>
                                                <v-tab-item>
                                                    <div>
                                                        <v-data-table
                                                                :headers="userCouponHeaders"
                                                                :items="userCouponItems"
                                                                item-key="no"
                                                                disable-sort
                                                                class="footable table table-stripped toggle-arrow-tiny"
                                                        >
                                                            <template v-slot:item.use_date="{ item }">
                                                                {{item.use_sdate}}~{{item.use_edate}}
                                                            </template>

                                                            <template v-slot:no-data>
                                                                <v-alert :value="true" color="error">
                                                                    데이터가 존재하지 않습니다.
                                                                </v-alert>
                                                            </template>
                                                        </v-data-table>
                                                    </div>
                                                </v-tab-item>
                                                <v-tab-item>
                                                    <v-card flat>
                                                        <v-simple-table dense>
                                                            <template v-slot:default>
                                                                <tbody>
                                                                <tr>
                                                                    <th>이름</th>
                                                                    <th>가입타입</th>
                                                                    <th>휴대폰</th>
                                                                    <th>로그인ID</th>
                                                                    <th>가입일</th>
                                                                </tr>
                                                                <tr>
                                                                    <td>{{userDetailItem.user_name}}</td>
                                                                    <td>{{userDetailItem.regist_type_text}}</td>
                                                                    <td>{{userDetailItem.user_phone}}</td>
                                                                    <td>{{userDetailItem.login_id}}</td>
                                                                    <td>{{userDetailItem.insert_dt}}</td>
                                                                </tr>
                                                                </tbody>
                                                            </template>
                                                        </v-simple-table>
                                                    </v-card>
                                                </v-tab-item>
                                            </v-tabs>
                                        </v-card>
                                    </v-dialog>
                                </v-toolbar>
                            </template>
                            <template v-slot:item.user_info="{ item }">
                                <a @click="userDetail(item.user_no)">{{item.user_name}}</a>
                            </template>
                            <template v-slot:item.order_view="{ item }">
                                {{item.order_is_view ? 'Y' : 'N' }}
                            </template>
                            <template v-slot:item.etc="{ item }">
                               {{item.delivery_price}}
                            </template>
                            <template v-slot:item.actionss="{ item }">
                                <v-btn class="test" small @click="orderPrint(item)">출력</v-btn>
                            </template>
                            <template v-slot:item.order_end_ready_time="{ item }">
                                {{item.pickup_dt == '0000-00-00 00:00:00' ? '-': item.pickup_dt}}
                            </template>
                            <template v-slot:item.view_image="{ item }">
                               <v-img  contain     max-height="150"
                                        max-width="150"
                                        :src="item.order_image"
                                ></v-img>
                            </template>
                            <template v-slot:item.actions="{ item }">
                                <v-icon small @click="editItem(item)">fa fa-edit</v-icon>
                                <v-icon small @click="deleteItem(item)">fa fa-trash</v-icon>
                            </template>
                            <template v-slot:item.data-table-select="{ item, isSelected, select }">
                                <v-simple-checkbox
                                        :value="isSelected"
                                        :disabled="item.order_status_text == '취소'"
                                        @input="select($event)"
                                ></v-simple-checkbox>
                            </template>

                            <template v-slot:no-data>
                                <v-alert :value="true" color="error">
                                    데이터가 존재하지 않습니다.
                                </v-alert>
                            </template>

                            <template v-slot:expanded-item="{ headers, item}">
                                <td :colspan="headers.length">
                                    <v-simple-table dense
                                                    style="background-color:#bababa;margin:20px">
                                        <template v-slot:default>
                                        <tbody>
                                        <tr>
                                            <th class="text-left" style="width:150px;">
                                                요청메모
                                            </th>
                                            <td class="text-left">{{item.request_memo}}</td>
                                        </tr>
                                        <tr>
                                            <th class="text-left">
                                                주소
                                            </th>
                                            <td class="text-left">{{item.delivery_address}} {{item.delivery_address_detail}}</td>
                                        </tr>
                                        <tr>
                                            <th class="text-left">
                                                사용쿠폰명
                                            </th>
                                            <td class="text-left">{{item.coupon_name}}</td>
                                        </tr>
                                        <tr>
                                            <th class="text-left">
                                                증정품
                                            </th>
                                            <td class="text-left">{{item.gifts}}</td>
                                        </tr>
                                        <tr v-if="item.payment_type_code =='STOREPAY_CASH'">
                                            <th class="text-left">
                                                결제관련
                                            </th>
                                            <td class="text-left">{{item.etc_data.cash_receipts === true ? '현금영수증 번호:'+ item.etc_data.cash_receipts_phone  : ''}} / {{item.etc_data.use_bill_5thousand  === true? '5만원권계산': ''}}</td>
                                        </tr>
                                        </tbody>
                                        </template>
                                    </v-simple-table>
                                    <v-data-table
                                            dense
                                            :headers="detailHeaders"
                                            :items="item.details"
                                            item-key="id"
                                            style="background-color:#bababa;margin:20px"
                                            hide-default-footer
                                            disable-sort
                                            class="elevation-1"
                                    >
                                    </v-data-table>
                                </td>
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
<div v-for="item in oprintItems">
    <iframe :src="'/order/order/oprint/'+item" style="width: 0;height: 0"></iframe>
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
                        { text: '주문번호', value: 'order_no' },
                        { text: '주문자명', value: 'user_info' },
                        { text: '휴대전화', value: 'user_phone' },
                        { text: '주문매장', value: 'store_text' },
                        { text: '주문상태', value: 'order_status_text' },
                        { text: '결제방법', value: 'payment_type_text' },
                        { text: '주문형태', value: 'order_type_text' },
                        { text: '완료예정시간', value: 'order_end_ready_time' },
                        { text: '결제금액', value: 'total_price' },
                        { text: '배달팁', value: 'etc' },
                        { text: '주문일', value: 'insert_dt' },
                        { text: '액션', value: 'actionss' },
                        { text: '상세', value: 'data-table-expand' },
                    ],
                    selected: [],
                    detailHeaders: [
                        { text: '제품명', value: 'product_name' },
                        { text: '구분', value: 'product_type_text' },
                        { text: '사이즈', value: 'size'},
                        { text: '토핑', value: 'topping'},
                        { text: '주문수량', value: 'order_quantity',align: 'right', },
                        { text: '금액', value: 'product_price',align: 'right', },
                    ],
                    orderStore: [],
                    orderStatus: [],
                    orderType: [],
                    orderTypeStatus: [],
                    orderPickupTime: [],
                    orderPaymentType: [],
                    schFields: {
                        user_name: "",
                        order_store: "",
                        order_type: "",
                        order_status: "",
                        order_payment_type: "",
                        order_sdate:"",
                        order_edate:"",
                        order_no:"<?=$order_no?>",
                    },
                    dialog: false,
                    dialogDelete: false,
                    dialogOrderStatusModify : false,
                    editedIndex: -1,
                    editedItem: {
                        order_no: 0,
                        order_name: "",
                        order_type: "",
                        order_is_view: 0,
                        order_price: 0,
                        order_desc : "",
                    },
                    editOrderStatus : '',
                    editOrderPickupTime : 0,
                    viewOrderPickupTime : false,
                    formValid: false,
                    rules: {
                        required: [value => !!value || "필수입력"]
                    },
                    showMessage: {
                        show: false,
                        message : ''
                    },
                    expanded: [],
                    dialogUserDetail:false,
                    userOrderheaders: [
                        { text: '주문번호', value: 'order_no' },
                        { text: '주문매장', value: 'store_text' },
                        { text: '주문상태', value: 'order_status_text' },
                        { text: '결제방법', value: 'payment_type_text' },
                        { text: '주문형태', value: 'order_type_text' },
                        { text: '결제금액', value: 'total_price' },
                        { text: '배달팁', value: 'etc' },
                        { text: '주문일', value: 'insert_dt' },
                        { text: '상세', value: 'data-table-expand' },
                    ],
                    userOrderdetailHeaders: [
                        { text: '제품명', value: 'product_name' },
                        { text: '구분', value: 'product_type_text' },
                        { text: '사이즈', value: 'size'},
                        { text: '토핑', value: 'topping'},
                        { text: '주문수량', value: 'order_quantity',align: 'right', },
                        { text: '금액', value: 'product_price',align: 'right', },
                    ],
                    userCouponHeaders: [
                        { text: '쿠폰명', value: 'coupon_name' },
                        { text: '발급일', value: 'insert_dt' },
                        { text: '사용기간', value: 'use_date' },
                        { text: '사용일', value: 'use_dt'},
                    ],
                    userOrderItem:[],
                    userOrderItem_details:[],
                    userCouponItems : [],
                    userDetailItem : {},
                    nowItem : {},
                    selectedTab : 0,
                    oprintItems : []
                }
            },
            computed: {
                formTitle () {
                    return this.editedIndex === -1 ? '신규등록' : '수정'
                },
            },

            watch: {
                editOrderStatus (val) {
                    this.viewOrderPickupTime = false;
                    if(this.selected[0].order_type_code == 'DELIVERY'){
                        let nowStatus = this.orderTypeStatus.filter(function(item){
                            if(item.code == 'DELIVERY_STEP3'){
                                return item;
                            }
                        })[0];

                        if(nowStatus.no == val){
                            this.viewOrderPickupTime = true;
                        }
                    }

                    this.editOrderPickupTime = 0;
                },
                dialog (val) {
                    val || this.close()
                },
                dialogDelete (val) {
                    val || this.close()
                },
                options: {
                    handler() {
                        this.readDataFromAPI();
                    },
                }
            },

            methods: {
                loadDetails({item}) {
                    if(!item.details.length){
                        axios
                            .get('/order/order/detail/'+item.order_no)
                            .then((res) => {
                                item.details = res.data.result.detail;
                            });
                    }
                },
                readDataFromAPI() {
                    this.loading = true;
                    const { page, itemsPerPage } = this.options;
                    let searchs = Object.entries(this.schFields).map(e => e.join('=')).join('&');

                    axios
                        .get("/order/order/data?per_page=" +itemsPerPage +"&page=" +page+'&'+searchs)
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
                getCode(){
                    axios
                        .get('/etc/code/sub_codes/store')
                        .then((res) => {
                            this.orderStore = res.data.result;
                        });

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
                editItem (item) {
                    this.editedIndex = this.items.indexOf(item)
                    this.editedItem = Object.assign({}, item)
                    this.dialog = true
                },

                deleteItem (item) {
                    this.editedIndex = this.items.indexOf(item)
                    this.editedItem = Object.assign({}, item)
                    this.dialogDelete = true
                },
                deleteItemConfirm () {
                    axios
                        .delete("/order/order/delete/"+this.editedItem.order_no)
                        .then((response) => {
                            if(response.data.success){
                                // this.showMessage.show = true;
                                // this.showMessage.message = response.data.message;

                                this.close();
                                showMessage(response.data.message);
                            }
                            else{
                                showMessage(response.data.message);
                            }
                        });

                    this.items.splice(this.editedIndex, 1)
                    this.close()
                },
                editedOrderStatusConfirm () {
                    if(this.editOrderStatus == ''){
                        showMessage("변경할 주문상태를 선택 해 주세요");
                        return;
                    }

                    if(this.selected[0].order_type_code == 'DELIVERY'){
                        let nowStatus = this.orderTypeStatus.filter(function(item){
                            if(item.code == 'DELIVERY_STEP3'){
                                return item;
                            }
                        })[0];

                        if(nowStatus.no == this.editOrderStatus && this.editOrderPickupTime<1){
                            showMessage("배달완료 예정시간을 선택 해 주세요");
                            return;
                        }
                    }

                    let formData = new FormData();

                    formData.append('order_edit_status', this.editOrderStatus);
                    if(this.selected[0].order_type_code == 'DELIVERY') {
                        formData.append('order_edit_pickup_time', this.editOrderPickupTime);
                    }

                    for(let key in this.selected) {
                        formData.append('order_nos[]', this.selected[key].order_no);
                    }

                    let parent = this;
                    let modifyStatus = this.orderTypeStatus.filter(function(item){
                        if(item.no == parent.editOrderStatus){
                            return item;
                        }
                    })[0];

                    if(modifyStatus.code == 'CANCEL'){
                        if(!confirm("취소 시 결제도 같이 취소되고 이후 상태변경을 할 수 없습니다.\n취소를 진행하시겠습니까?")){
                            return;
                        }
                    }
                    this.snackbar = true;
                    axios
                        .post("/order/order/status_modify",formData)
                        .then((response) => {
                            if(response.data.success){
                                // this.showMessage.show = true;
                                // this.showMessage.message = response.data.message;

                                for(let key in this.selected) {
                                    this.editedIndex = this.items.indexOf(this.selected[key]);
                                    this.items[this.editedIndex].order_status = modifyStatus.no;
                                    this.items[this.editedIndex].order_status_text = modifyStatus.name;
                                }

                                this.selected = [];

                                this.close();
                                showMessage(response.data.message);
                            }
                            else{
                                showMessage(response.data.message);
                            }
                        });

                    this.close()
                },
                close () {
                    this.dialog = false
                    this.showMessage.show = false
                    this.dialogUserDetail = false
                    this.dialogDelete = false
                    this.dialogOrderStatusModify = false
                    this.$nextTick(() => {
                        this.editedItem = Object.assign({}, this.defaultItem)
                        this.editedIndex = -1
                    })
                },
                save () {
                    if (this.$refs.form.validate()) {
                        this.snackbar = true
                    }
                    else{
                        return;
                    }

                    let formData = new FormData();
                    for(let key in this.editedItem) {
                        formData.append(key, this.editedItem[key]);
                    }

                    formData.append('order_image[]', this.editedItem.order_image);

                    axios
                        .post("/order/order/"+(this.editedIndex > -1 ? 'modify' : 'add'),formData)
                        .then((response) => {
                            if(response.data.success){
                                // this.showMessage.show = true;
                                // this.showMessage.message = response.data.message;
                                axios
                                    .get('/order/order/detail/'+response.data.result)
                                    .then((res) => {
                                        if(this.editedIndex > -1){
                                            Object.assign(this.items[this.editedIndex], res.data.result)
                                        }
                                        else{
                                            this.items.unshift(res.data.result)
                                        }

                                        this.close();
                                        showMessage(response.data.message);
                                    });

                            }
                            else{
                                showMessage(response.data.message);
                            }
                        });
                },
                orderStatusModify(){
                    if(this.selected.length < 1){
                        showMessage('주문상태를 변경할 주문을 선택 해주세요.');
                        return;
                    }

                    let checks = [0,0];
                    this.selected.forEach(function(value, index) {
                        checks[value.order_type_text == '배달' ? 0 : 1] += 1;
                    });

                    if(checks[0] >0 && checks[1] >0){
                        showMessage('주문형태가 같은 것끼리만 상태 변경이 가능합니다.');
                        return;
                    }

                    this.viewOrderPickupTime = false;

                    axios
                        .get('/etc/code/sub_codes/ORDER_STATUS_'+this.selected[0].order_type_code)
                        .then((res) => {
                            this.orderTypeStatus = res.data.result;
                        });

                    this.dialogOrderStatusModify = true;
                },
                orderSelectPrint(){
                    if(this.selected.length < 1){
                        showMessage('출력할 주문을 선택 해주세요.');
                        return;
                    }
                    this.oprintItems = [];

                    for(let key in this.selected) {
                        //printnos.push(this.selected[key].order_no);
                        this.oprintItems.push(this.selected[key].order_no);
                    }

                    //var ret = window.open('/order/order/oprint/'+printnos.join(','), "", "_blank");
                    //this.oprintSrc = '/order/order/oprint/'+item.order_no;

                },
                orderPrint(item){
                    this.oprintItems = [];
                    this.oprintItems.push(item.order_no);
                },
                userDetail(user_no){
                    this.selectedTab = 0;

                    axios
                        .get("/order/order/data?per_page=100&page=1&user_no="+user_no)
                        .then((res) => {
                            this.loading = false;
                            this.userOrderItem = res.data.result.list.map((item) => {
                                return {
                                    details: [],
                                    ...item
                                }
                            })
                        });

                    axios
                        .get('/member/user/coupon_list/'+user_no)
                        .then((res) => {
                            this.userCouponItems = res.data.result;
                        });

                    axios
                        .get('/member/user/data?per_page=1&page=1&no='+user_no)
                        .then((res) => {
                            console.log(res);
                            this.userDetailItem = res.data.result.list[0];
                        });

                    this.dialogUserDetail = true;
                },
                orderloadDetails({item}) {
                    axios
                        .get('/order/order/detail/'+item.order_no)
                        .then((res) => {
                            this.userOrderItem_details = res.data.result.detail;
                        });


                    console.log(item)

                },
                downExcel(){
                    let searchs = Object.entries(this.schFields).map(e => e.join('=')).join('&');
                    location.href = "/order/order/excel_down?per_page=100000&page=1&"+searchs;
                }
            },
            mounted() {
               this.getCode();
                for(let i=10;i<=70; i+=5){
                    this.orderPickupTime.push({no : i, name : i+'분'});
                }
            },
        });
    </script>
