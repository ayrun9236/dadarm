<div class="ibox-content m-b-sm border-bottom">
    <div class="row">
        <div class="col-sm-2">
            <div class="form-group">
                <label class="control-label">쿠폰명</label>
                <input type="text" v-model="schFields.product_name" v-on:keyup.enter="readDataFromAPI" class="form-control">
            </div>
        </div>
        <div class="col-sm-2">
            <div class="form-group">
                <label class="control-label" for="status">구분</label>
                <select v-model="schFields.order_type" class="form-control">
                    <option value="">전체</option>
                    <option value="0">구분없음</option>
                    <option v-for="item in orderType" :value="item.no">{{ item.name }}</option>
                </select>
            </div>
        </div>
        <div class="col-sm-1 .col-md-offset-3">
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
                                :expanded="expanded"
                                show-expand
                                item-key="product_no"
                                disable-sort
                                class="footable table table-stripped toggle-arrow-tiny"
                        >
                            <template v-slot:top>
                                <v-toolbar flat >
                                    <v-spacer></v-spacer>
                                    <v-dialog v-model="dialog" max-width="700px" >
                                        <template v-slot:activator="{ on, attrs }">
                                            <v-btn color="primary" dark small class="mb-2" v-bind="attrs" v-on="on" >
                                                신규 등록
                                            </v-btn>
                                        </template>
                                        <v-card>
                                            <v-card-title >
                                                <span class="headline">{{ formTitle }}</span>
                                            </v-card-title>
                                            <v-divider></v-divider>
                                            <v-card-text>
                                                <v-form
                                                        ref="form"
                                                        v-model="formValid"
                                                        lazy-validation
                                                >
                                                <v-container>
                                                    <v-row>
                                                        <v-col cols="12" sm="6" md="4">
                                                            <v-text-field  v-model="editedItem.coupon_name" :rules="rules.required" label="쿠폰명" required></v-text-field>
                                                        </v-col>
                                                        <v-col cols="12" sm="6" md="4">
                                                            <v-select
                                                                      v-model="editedItem.order_type"
                                                                    :items="orderType"
                                                                    item-text="name"
                                                                    item-value="no"
                                                                    label="사용타입(주문형태)"
                                                                    persistent-hint
                                                            >
                                                            </v-select>
                                                        </v-col>
                                                        <v-col cols="12" sm="6" md="4">
                                                            <v-select
                                                                    v-model="editedItem.publish_type"
                                                                    :items="publishType"
                                                                    item-text="name"
                                                                    item-value="no"
                                                                    label="발행타입"
                                                                    persistent-hint
                                                            >
                                                            </v-select>
                                                        </v-col>
                                                        <v-col cols="12" sm="6" md="4">
                                                            <v-text-field v-model="editedItem.discount_price"  type="text" label="할인금액"></v-text-field>
                                                        </v-col>
                                                        <v-col cols="12" sm="6" md="4">
                                                            <v-text-field  v-model="editedItem.order_min_price" label="최소주문금액" type="number"
                                                                          :rules="rules.required"></v-text-field>
                                                        </v-col>
                                                        <v-col cols="12" sm="6" md="4">
                                                            <v-text-field  v-model="editedItem.gifts"  label="증정품"></v-text-field>
                                                        </v-col>
<!--                                                        <v-col cols="12" sm="6" md="4">-->
<!--                                                            <v-file-input-->
<!--                                                                    v-model="editedItem.coupon_image"-->
<!--                                                                    accept="image/*"-->
<!--                                                                    label="이미지"-->
<!--                                                                    :rules="rules.required"-->
<!--                                                            ></v-file-input>-->
<!--                                                        </v-col>-->
<!--                                                        <v-col cols="12" sm="6" md="4">-->
<!--                                                            <v-select-->
<!--                                                                    v-model="editedItem.is_stamp"-->
<!--                                                                    :items="viewType"-->
<!--                                                                    item-text="name"-->
<!--                                                                    item-value="no"-->
<!--                                                                    label="스템프이벤트여부"-->
<!--                                                                    persistent-hint-->
<!--                                                            >-->
<!--                                                            </v-select>-->
<!--                                                        </v-col>-->
                                                    </v-row>
                                                    <v-row>
                                                        <v-col cols="12" sm="6" md="4">
                                                        <v-menu
                                                                v-model="cevent_sdate"
                                                                :close-on-content-click="false"
                                                                :nudge-right="40"
                                                                transition="scale-transition"
                                                                offset-y
                                                                min-width="auto"
                                                        >
                                                            <template v-slot:activator="{ on, attrs }">
                                                                <v-text-field
                                                                              v-model="editedItem.use_sdate"
                                                                              label="이용기간 시작일"
                                                                              readonly
                                                                              v-bind="attrs"
                                                                              v-on="on"
                                                                ></v-text-field>
                                                            </template>
                                                            <v-date-picker
                                                                    v-model="editedItem.use_sdate"
                                                                    @input="cevent_sdate = false"
                                                            ></v-date-picker>
                                                        </v-menu>
                                                        </v-col><v-col cols="12" sm="6" md="4">
                                                        <v-menu
                                                                v-model="cevent_edate"
                                                                :close-on-content-click="false"
                                                                :nudge-right="40"
                                                                transition="scale-transition"
                                                                offset-y
                                                                min-width="auto"
                                                        >
                                                            <template v-slot:activator="{ on, attrs }">
                                                                <v-text-field
                                                                              v-model="editedItem.use_edate"
                                                                              label="이용기간 종료일"
                                                                              readonly
                                                                              v-bind="attrs"
                                                                              v-on="on"
                                                                ></v-text-field>
                                                            </template>
                                                            <v-date-picker
                                                                    v-model="editedItem.use_edate"
                                                                    @input="cevent_edate = false"
                                                            ></v-date-picker>
                                                        </v-menu>
                                                        </v-col>
                                                    </v-row>
                                                    <v-row>
                                                        <v-col cols="12"
                                                               sm="6"
                                                               md="4">
                                                            <v-select
                                                                    v-model="editedItem.is_use_store_all"
                                                                    :items="viewType"
                                                                    item-text="name"
                                                                    item-value="no"
                                                                    v-on:change="changeGrant"
                                                                    label="전체매장사용"
                                                                    persistent-hint
                                                            >
                                                            </v-select>
                                                        </v-col>
                                                        <v-col cols="12"
                                                               sm="6"
                                                               md="4"
                                                               label="사용지점"
                                                               v-for="store in storeInfo"
                                                               :key="store.no">
                                                            <v-checkbox
                                                                    v-model="editedItem.use_store"
                                                                    color="error"
                                                                    :label="store.name"
                                                                    :value="store.no"
                                                                    :disabled="checkbox_disabled"
                                                                    hide-details
                                                            ></v-checkbox>
                                                        </v-col>
                                                    </v-row>
                                                </v-container>
                                                </v-form>
                                            </v-card-text>
                                            <v-divider></v-divider>
                                            <v-card-actions>
                                                <v-spacer></v-spacer>
                                                <v-btn
                                                        color="blue darken-1"
                                                        text
                                                        @click="close"
                                                >
                                                    Cancel
                                                </v-btn>
                                                <v-btn
                                                        color="blue darken-1"
                                                        text
                                                        @click="save"
                                                >
                                                    Save
                                                </v-btn>
                                            </v-card-actions>

                                            <v-snackbar v-model="showMessage.show" color="color" timeout="-1">
                                                {{ showMessage.message }}
                                                <template v-slot:action="{ attrs }">
                                                    <v-btn text v-bind="attrs" @click="close"> Close </v-btn>
                                                </template>
                                            </v-snackbar>

                                        </v-card>
                                    </v-dialog>
                                    <v-dialog v-model="dialogDelete" max-width="500px">
                                        <v-card>
                                            <v-card-title class="headline">해당 쿠폰을 삭제하시겠습니까?</v-card-title>
                                            <v-card-actions>
                                                <v-spacer></v-spacer>
                                                <v-btn color="blue darken-1" text @click="close">Cancel</v-btn>
                                                <v-btn color="blue darken-1" text @click="deleteItemConfirm">OK</v-btn>
                                                <v-spacer></v-spacer>
                                            </v-card-actions>
                                        </v-card>
                                    </v-dialog>
                                </v-toolbar>
                            </template>
                            <template v-slot:item.coupon_order_type="{ item }">
                                {{item.order_type_text ? item.order_type_text : '전체' }}
                            </template>
                            <template v-slot:item.view_stamp="{ item }">
                                {{item.stamp_count > 0 ? 'Y' : 'N' }}
                            </template>
                            <template v-slot:item.coupon_date="{ item }">
                                {{item.use_sdate}} ~ {{item.use_edate}}
                            </template>
                            <template v-slot:item.actions="{ item }">
                                <v-icon small @click="editItem(item)">fa fa-edit</v-icon>
                                <v-icon small @click="deleteItem(item)">fa fa-trash</v-icon>
                            </template>
                            <template v-slot:no-data>
                                <v-alert :value="true" color="error">
                                    데이터가 존재하지 않습니다.
                                </v-alert>
                            </template>
                            <template v-slot:expanded-item="{ headers, item }">
                                <td :colspan="headers.length">
                                    설명 -> {{ item.product_desc }}
                                </td>
                            </template>
                        </v-data-table>
                    </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>
    <script>
        document.body.setAttribute('data-app', true)
        axios.defaults.headers.common = {
            "Content-Type": "multipart/form-data"
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
                        { text: '쿠폰명', value: 'coupon_name' },
                        { text: '사용타입(주문형태)', value: 'coupon_order_type' },
                        { text: '발행타입', value: 'publish_type_text' },
                        { text: '쿠폰사용가능기간', value: 'coupon_date' },
                        { text: '할인금액', value: 'discount_price' },
                        { text: '최소주문금액', value: 'order_min_price' },
                        { text: '증정품', value: 'gifts' },
                        { text: '사용매장', value: 'use_store_text' },
                        { text: '등록일', value: 'insert_dt' },
                        { text: '상세', value: 'data-table-expand' },
                        { text: "액션", value: 'actions'},
                    ],
                    selected: [],
                    orderType: [],
                    publishType: [],
                    viewType: [{no : true, name : 'Y'},{no : false, name : 'N'}],
                    checkbox_disabled : false,
                    schFields: {
                        product_name: "",
                        product_type: "",
                    },
                    dialog: false,
                    dialogDelete: false,
                    editedIndex: -1,
                    editedItem: {
                        coupon_master_no: 0,
                        coupon_name: "",
                        discount_price: 0,
                        order_min_price: 0,
                        is_use_store_all : 1,
                        gifts: '',
                        order_type : '',
                        publish_type : '',
                        use_store : [],
                    },
                    defaultItem: {
                        coupon_master_no: 0,
                        coupon_name: "",
                        discount_price: 0,
                        order_min_price: 0,
                        gifts: '',
                        order_type : '',
                        publish_type : '',
                        use_store : [],
                        is_use_store_all : 1,
                    },
                    formValid: false,
                    rules: {
                        required: [value => !!value || "필수입력"]
                    },
                    showMessage: {
                        show: false,
                        message : ''
                    },
                    expanded: [],
                    cevent_sdate: false,
                    cevent_edate: false,
                    storeInfo: [],
                    date: new Date().toISOString().substr(0, 10),
                }
            },
            computed: {
                formTitle () {
                    return this.editedIndex === -1 ? '신규등록' : '수정'
                },
            },

            watch: {
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
                readDataFromAPI() {
                    this.loading = true;
                    const { page, itemsPerPage } = this.options;

                    let searchs = Object.entries(this.schFields).map(e => e.join('=')).join('&');
                    axios
                        .get("/event/coupon/data?per_page=" +itemsPerPage +"&page=" +page+'&'+searchs)
                        .then((res) => {
                            this.loading = false;
                            this.items = res.data.result.list;
                            this.itemsTotalCount = res.data.result.total_count;
                            this.totalPages = 100;
                        });
                },
                getCode(){
                    axios
                        .get('/etc/code/sub_codes/ORDER_TYPE')
                        .then((res) => {
                            this.orderType = res.data.result;
                            this.orderType.unshift({'no':'0','name':'전체'});
                        });

                    axios
                        .get('/etc/code/sub_codes/COUPON_PUBLISH_TYPE')
                        .then((res) => {
                            this.publishType = res.data.result;
                        });

                    axios
                        .get('/etc/code/sub_codes/STORE')
                        .then((res) => {
                            this.storeInfo = res.data.result;
                        });

                },
                editItem (item) {
                    this.editedIndex = this.items.indexOf(item)
                    this.editedItem = Object.assign({}, item)

                    let chk = this.orderType.filter(function(i){
                        if(i.no == item.order_type) return i.no;
                    });
 
                    this.editedItem.order_type = chk[0];

                    chk = this.publishType.filter(function(i){
                        if(i.no == item.publish_type) return i.no;
                    });

                    this.editedItem.publish_type = chk[0];

                    this.checkbox_disabled = false;

                    if(this.editedItem.is_use_store_all === true){
                        this.checkbox_disabled = true;
                    }

                    this.dialog = true;
                },

                deleteItem (item) {
                    this.editedIndex = this.items.indexOf(item)
                    this.editedItem = Object.assign({}, item)
                    this.dialogDelete = true
                },
                deleteItemConfirm () {
                    axios
                        .delete("/event/coupon/delete/"+this.editedItem.coupon_master_no)
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
                close () {
                    this.dialog = false
                    this.showMessage.show = false
                    this.dialogDelete = false
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

                    if(typeof(this.editedItem.order_type) == 'object'){
                        formData.delete('order_type');
                        formData.append("order_type", this.editedItem.order_type.no);
                    }

                    if(typeof(this.editedItem.publish_type) == 'object'){
                        formData.delete('publish_type');
                        formData.append("publish_type", this.editedItem.publish_type.no);
                    }

                    if(typeof(this.editedItem.order_type) == 'is_use_store_all'){
                        formData.delete('is_use_store_all');
                        formData.append("is_use_store_all", this.editedItem.is_use_store_all.no);
                    }

                    formData.append('coupon_image[]', this.editedItem.coupon_image);

                    axios
                        .post("/event/coupon/"+(this.editedIndex > -1 ? 'modify' : 'add'),formData)
                        .then((response) => {
                            if(response.data.success){
                                // this.showMessage.show = true;
                                // this.showMessage.message = response.data.message;
                                axios
                                    .get('/event/coupon/detail/'+response.data.result)
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
                changeGrant(e){
                    this.checkbox_disabled = false;
                    if(e === true){
                        this.checkbox_disabled = true;
                    }

                }
            },
            mounted() {
               this.getCode();
            },
        });
    </script>
