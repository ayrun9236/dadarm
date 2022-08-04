<div class="ibox-content m-b-sm border-bottom">
    <div class="row">
        <div class="col-sm-1">
            <div class="form-group">
                <label class="control-label">회원번호</label>
                <input type="text" v-model="schFields.no" v-on:keyup.enter="readDataFromAPI" class="form-control">
            </div>
        </div>
        <div class="col-sm-1">
            <div class="form-group">
                <label class="control-label">이름</label>
                <input type="text" v-model="schFields.name" v-on:keyup.enter="readDataFromAPI" class="form-control">
            </div>
        </div>
        <div class="col-sm-1">
            <div class="form-group">
                <label class="control-label" >로그인ID</label>
                <input type="text" v-model="schFields.login_id" v-on:keyup.enter="readDataFromAPI" class="form-control">
            </div>
        </div>
        <div class="col-sm-2">
            <div class="form-group">
                <label class="control-label">휴대폰</label>
                <input type="text" v-model="schFields.user_phone" v-on:keyup.enter="readDataFromAPI"  class="form-control">
            </div>
        </div>
        <div class="col-sm-2">
            <div class="form-group">
                <label class="control-label" for="status">가입타입</label>
                <select v-model="schFields.regist_type" class="form-control">
                    <option v-for="item in registType" :value="item.no">{{ item.name }}</option>
                </select>
            </div>
        </div>
        <div class="col-sm-2">
            <div class="form-group">
                <label class="control-label" for="date_added">가입일</label>
                <div class="input-group">
                    <date-picker v-model="schFields.regist_sdate"  value-type="format" format="YYYY-MM-DD"></date-picker>
                    <span class="input-group-addon">~</span>
                    <date-picker v-model="schFields.regist_edate"  value-type="format" format="YYYY-MM-DD"></date-picker>
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
                                item-key="user_no"
                                disable-sort
                                class="footable table table-stripped toggle-arrow-tiny"
                        >
                            <template v-slot:top>
                                <v-toolbar flat >
                                    <v-spacer></v-spacer>
                                    <v-dialog v-model="dialog" max-width="700px" >
                                        <template v-slot:activator="{ on, attrs }">
                                            <v-btn color="primary" small dark class="mb-2" v-bind="attrs" v-on="on" >
                                                신규 등록
                                            </v-btn>
                                        </template>
                                        <v-card>
                                            <v-card-title>
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
                                                        <v-col cols="12"
                                                               sm="6"
                                                               md="4">
                                                            <v-text-field v-model="editedItem.user_name" :rules="rules.required" label="이름" required></v-text-field>
                                                        </v-col>
                                                        <v-col cols="12"
                                                               sm="6"
                                                               md="4">
                                                            <v-text-field v-model="editedItem.login_id" :rules="rules.required.concat(rules.email)" :disabled="editedIndex > -1 && !editedfieldDisable" label="로그인ID"></v-text-field>
                                                        </v-col>
                                                        <v-col cols="12"
                                                               sm="6"
                                                               md="4">
                                                            <v-text-field v-model="editedItem.login_password" type="password" :rules="editedIndex > -1 ? '':rules.required.concat(rules.password)" :disabled="editedIndex > -1 && !editedfieldDisable" label="비밀번호"
                                                                          ></v-text-field>
                                                        </v-col>
                                                        <v-col cols="12"
                                                               sm="6"
                                                               md="4">
                                                            <v-text-field v-model="editedItem.user_phone" :rules="rules.required" label="핸드폰" ></v-text-field>
                                                        </v-col>

                                                        <v-col cols="12"
                                                               sm="6"
                                                               md="4">
                                                            <v-checkbox
                                                                    v-model="editedItem.is_marketing_agree_sms"
                                                                    :label="`광고성 정보동의`"
                                                            ></v-checkbox>
                                                        </v-col>
                                                        <v-col cols="12"
                                                               sm="6"
                                                               md="4">
                                                            <v-checkbox
                                                                    v-model="editedItem.is_marketing_agree_email"
                                                                    :label="`email`"
                                                            ></v-checkbox>

                                                        </v-col>
                                                        <v-col cols="12"
                                                               sm="6"
                                                               md="4">
                                                            <v-checkbox
                                                                    v-model="editedItem.is_marketing_agree_push"
                                                                    :label="`push`"
                                                            ></v-checkbox>
                                                        </v-col>
                                                    </v-row>
                                                    <v-row>
                                                        <v-col sm="20" v-if="!editedIndex">
                                                            <span class="headline">* 비밀번호를 입력하면 비밀번호도 수정됩니다.</span>
                                                            <span class="headline">* 가입타입이 이메일 경우 로그인정보 수정가능합니다.</span>
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
                                            <v-card-title class="headline">해당 회원을 탈퇴처리 하시겠습니까?</v-card-title>
                                            <v-card-actions>
                                                <v-spacer></v-spacer>
                                                <v-btn color="blue darken-1" text @click="close">Cancel</v-btn>
                                                <v-btn color="blue darken-1" text @click="deleteItemConfirm">OK</v-btn>
                                                <v-spacer></v-spacer>
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
                                                    글리스트
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
                                                    기타내역
                                                </v-tab>
                                                <v-tab-item>
                                                    <div>
                                                        <v-data-table
                                                                :headers="userPostheaders"
                                                                :items="userPostItem"
                                                                :expanded="expanded"
                                                                show-expand
                                                                single-expand
                                                                item-key="order_no"
                                                                disable-sort
                                                                class="footable table table-stripped toggle-arrow-tiny"
                                                                @item-expanded="orderloadDetails"
                                                        >
                                                            <template v-slot:no-data>
                                                                <v-alert :value="true" color="error">
                                                                    데이터가 존재하지 않습니다.
                                                                </v-alert>
                                                            </template>

                                                            <template v-slot:expanded-item="{ headers, item}">
                                                                <td :colspan="headers.length">
                                                                    <v-simple-table dense
                                                                                    style="margin:20px">
                                                                        <template v-slot:default>
                                                                            <tbody>
                                                                            <tr>
                                                                                <td class="text-left"><p v-html="item.contents.replace(/(?:\r\n|\r|\n)/g, '<br />')"></p></td>
                                                                            </tr>
                                                                            </tbody>
                                                                        </template>
                                                                    </v-simple-table>
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
                                                                    <th class="text-left" style="width:150px;">
                                                                        탈퇴사유 / 직접입력
                                                                    </th>
                                                                    <td class="text-left">{{ nowItem.leave_reason_text }} / {{ nowItem.leave_reason_etc }}</td>
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
                            <template v-slot:item.leave="{ item }">
                                {{item.is_leave ? 'Y' : 'N' }}
                            </template>
                            <template v-slot:item.data_detail="{ item }">
                                <button type="button" @click="userDetail(item)" class="v-icon notranslate v-icon--link fa fa fa fa-database theme--light" style="font-size: 16px;"></button>
                            </template>
                            <template v-slot:item.marketings="{ item }">
                                {{item.is_marketing_agree_sms ? 'Y' : 'N' }}/{{item.is_marketing_agree_email ? 'Y' : 'N' }}/{{item.is_marketing_agree_push ? 'Y' : 'N' }}
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

        axios.defaults.headers.common = {
            "Content-Type": "application/json"
        }
        new Vue({
            el: '#app',
            vuetify: new Vuetify(),
            components: {
                DatePicker
            },
            data () {
                return {
                    page: 1,
                    itemsTotalCount: 0,
                    totalPages: 10,
                    items: [],
                    loading: true,
                    options: {},
                    headers: [
                        { text: '이름', value: 'user_name' },
                        { text: '가입타입', value: 'regist_type_text' },
                        { text: '핸드폰', value: 'user_phone' },
                        { text: '로그인ID', value: 'login_id' },
                        { text: '가입일', value: 'insert_dt' },
                        { text: '로그인횟수', value: 'login_count' },
                        { text: "광고성 정보동의/email/push", value: 'marketings' },
                        { text: '탈퇴일', value: 'leave_dt' },
                        { text: '상세', value: 'data_detail' },
                        { text: "액션", value: 'actions'}
                    ],
                    registType: [],
                    schFields: {
                        name: "",
                        regist_type: "",
                        phone: "",
                        login_id: "",
                        regist_sdate: "",
                        regist_edate: "",
                        no: "<?=$user_no?>"
                    },
                    dialog: false,
                    dialogDelete: false,
                    editedIndex: -1,
                    editedfieldDisable : true,
                    editedItem: {
                        user_no:0,
                        user_name: "",
                        login_id: "",
                        login_password: "",
                        user_phone: "",
                        regist_type : "",
                        is_marketing_agree_sms: false,
                        is_marketing_agree_email: false,
                        is_marketing_agree_push: false,
                    },
                    formValid: false,
                    rules: {
                        required: [value => !!value || "필수입력"],
                        email: [v => !v || /^\w+([.-]?\w+)*@\w+([.-]?\w+)*(\.\w{2,3})+$/.test(v) || 'E-mail형식'],
                        password: [v => !v || /^(?=.*[A-Za-z])(?=.*\d)(?=.*[~!@#$%^&*()_+])[A-Za-z\d~!@#$%^&*()_+]{10,20}$/.test(v) || '영문,숫저,특수기호 조합(10-20자)'],
                    },
                    showMessage: {
                        show: false,
                        message : ''
                    },
                    expanded: [],
                    dialogUserDetail:false,
                    userPostheaders: [
                        { text: '제목', value: 'title' },
                        { text: '보기권한', value: 'view_type' },
                        { text: '등록일', value: 'insert_dt' },
                        { text: '상세', value: 'data-table-expand' },
                    ],
                    userCouponHeaders: [
                        { text: '쿠폰명', value: 'coupon_name' },
                        { text: '사용기간', value: 'use_date' },
                        { text: '사용일', value: 'use_dt'},
                    ],
                    userPostItem:[],
                    userPostItem_details:[],
                    userCouponItems : [],
                    nowItem : {},
                    selectedTab : 0,
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
                orderloadDetails({item}) {
                        axios
                            .get('/user/post/detail/'+item.order_no)
                            .then((res) => {
                                this.userPostItem_details = res.data.result.detail;
                            });


                        console.log(item)

                },
                userDetail(userItem){
                    this.selectedTab = 0;
                    this.nowItem = userItem;
                    axios
                        .get("/member/user/post/data?per_page=100&page=1&user_no="+userItem.user_no)
                        .then((res) => {
                            this.loading = false;
                            this.userPostItem = res.data.result.list.map((item) => {
                                return {
                                    details: [],
                                    ...item
                                }
                            })
                        });

                    axios
                        .get('/member/user/coupon_list/'+userItem.user_no)
                        .then((res) => {
                            this.userCouponItems = res.data.result;
                        });
                    this.dialogUserDetail = true;
                },

                readDataFromAPI() {
                    this.loading = true;
                    const { page, itemsPerPage } = this.options;

                    let searchs = Object.entries(this.schFields).map(e => e.join('=')).join('&');
                    axios
                        .get("/member/user/data?per_page=" +itemsPerPage +"&page=" +page+'&'+searchs)
                        .then((res) => {
                            this.loading = false;
                            this.items = res.data.result.list.map((item) => {
                                return {
                                    coupons: [],
                                    ...item
                                }
                            })
                            this.itemsTotalCount = res.data.result.total_count;
                            this.totalPages = 100;
                        });
                },
                getCode(){
                    axios
                        .get('/etc/code/sub_codes/REGIST_TYPE')
                        .then((res) => {
                            this.registType = res.data.result;
                            this.registType.unshift({'no':'','name':'전체'});
                        });
                },
                editItem (item) {
                    this.editedIndex = this.items.indexOf(item)
                    this.editedItem = Object.assign({}, item)

                    this.editedfieldDisable = false;
                    let chk = this.registType.filter(function(item){
                        if(item.code == "EMAIL") return item.no;
                    });

                    if(chk[0].no == this.editedItem.regist_type){
                        console.log(chk);
                        console.log(this.editedItem.regist_type);
                        this.editedfieldDisable = true;
                    }
                    this.dialog = true
                },

                deleteItem (item) {
                    this.editedIndex = this.items.indexOf(item)
                    this.editedItem = Object.assign({}, item)
                    this.dialogDelete = true
                },

                deleteItemConfirm () {
                    axios
                        .delete("/member/user/leave/"+this.editedItem.user_no)
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
                    this.dialogUserDetail = false
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

                    axios
                    .post("/member/user/"+(this.editedIndex > -1 ? 'modify' : 'add'),formData)
                        .then((response) => {
                            if(response.data.success) {

                                // this.showMessage.show = true;
                                // this.showMessage.message = response.data.message;

                                if (this.editedIndex > -1) {
                                    Object.assign(this.items[this.editedIndex], this.editedItem)
                                } else {
                                    axios
                                        .get('/member/user/detail/' + response.data.result)
                                        .then((res) => {
                                            this.items.unshift(res.data.result)
                                        });
                                }

                                this.close();
                                showMessage(response.data.message);
                            }
                            else{
                                showMessage(response.data.message);
                            }
                        });
                },
                downExcel(){
                    let searchs = Object.entries(this.schFields).map(e => e.join('=')).join('&');
                    location.href = "/member/user/excel_down?per_page=100000&page=1&"+searchs;
                }
            },
            mounted() {
                this.getCode();
            },
        });

    </script>
