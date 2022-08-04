<div class="ibox-content m-b-sm border-bottom">
    <div class="row">
        <div class="col-sm-2">
            <div class="form-group">
                <label class="control-label" for="status">매장</label>
                <select v-model="schFields.store" class="form-control">
                    <?php if(!($store_no>0)){?><option value="">전체</option><?php } ?>
                    <option v-for="item in storeInfo" :value="item.no">{{ item.name }}</option>
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
                                item-key="no"
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
                                                            <v-text-field v-model="editedItem.name" :rules="rules.required" label="매장명" required></v-text-field>
                                                        </v-col>
                                                        <v-col cols="12" sm="6" md="4">
                                                            <v-text-field v-model="editedItem.code" :rules="rules.required" label="매장코드" required></v-text-field>
                                                        </v-col>
                                                        <v-col cols="12" sm="6" md="4">
                                                            <v-text-field v-model="editedItem.tel" :rules="rules.required" label="연락처" required></v-text-field>
                                                        </v-col>
                                                        <v-col cols="12" sm="6" md="4">
                                                            <v-text-field v-model="editedItem.use_time" :rules="rules.required" label="이용시간" required></v-text-field>
                                                        </v-col>
                                                        <v-col cols="12" sm="6" md="4">
                                                            <v-text-field v-model="editedItem.delivery_price" label="배탈팁" type="number"
                                                                          :rules="rules.required"
                                                                          required></v-text-field>
                                                        </v-col>
                                                        <v-col cols="12" sm="6" md="4">
                                                            <v-select
                                                                    v-model="editedItem.is_view"
                                                                    :items="viewType"
                                                                    item-text="name"
                                                                    item-value="no"
                                                                    label="노출"
                                                                    persistent-hint
                                                            ></v-select>
                                                        </v-col>
                                                        <v-col cols="12" sm="6" md="4">
                                                            <v-text-field v-model="editedItem.latitude" :rules="rules.required" label="위도" required></v-text-field>
                                                        </v-col>
                                                        <v-col cols="12" sm="6" md="4">
                                                            <v-text-field v-model="editedItem.longitude" :rules="rules.required" label="경도" required></v-text-field>
                                                        </v-col>
                                                        <v-col cols="12" sm="6" md="10">
                                                            <v-text-field v-model="editedItem.phone" :rules="rules.required"
                                                                          hint="두개 이상 존재할 경우 ,로 구분추가"
                                                                          label="주문접수 시 알림 휴대전화" required></v-text-field>
                                                        </v-col>
                                                        <v-col cols="12" sm="12" md="10">
                                                            <v-text-field
                                                                    v-model="editedItem.address"
                                                                    label="주소"
                                                                    value=""
                                                            ></v-text-field>
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
                                            <v-card-title class="headline">해당 제품을 삭제하시겠습니까?</v-card-title>
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
                            <template v-slot:item.store_view="{ item }">
                                {{item.is_view ? 'Y' : 'N' }}
                            </template>
                            <template v-slot:item.store_location="{ item }">
                               {{item.latitude }},{{item.longitude }}
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
                    totalPages: 20,
                    items: [],
                    loading: true,
                    options: {},
                    headers: [
                        { text: '매장명', value: 'name' },
                        { text: '연락처', value: 'tel' },
                        { text: '주문알림 휴대전화', value: 'phone' },
                        { text: '이용시간', value: 'use_time' },
                        { text: '배탈팁', value: 'delivery_price' },
                        { text: '노출', value: 'store_view' },
                        { text: '주소', value: 'address' },
                        { text: '위치(위도,경도)', value: 'store_location' },
                        { text: '등록일', value: 'insert_dt' },
                        { text: "액션", value: 'actions'},
                    ],
                    selected: [],
                    viewType: [{no : true, name : 'Y'},{no : false, name : 'N'}],
                    schFields: {
                        store: '<?=$store_no > 0 ? $store_no : ''?>',
                    },
                    dialog: false,
                    dialogDelete: false,
                    editedIndex: -1,
                    storeInfo: [],
                    editedItem: {
                        no: 0,
                        name: "",
                        code: "",
                        use_time : '',
                        tel: "",
                        phone: "",
                        is_view: true,
                        delivery_price: 0,
                        latitude: 0,
                        longitude: 0,
                        address : "",
                    },
                    defaultItem: {
                        no: 0,
                        name: "",
                        code: "",
                        use_time : '',
                        tel: "",
                        phone: "",
                        is_view: true,
                        delivery_price: 0,
                        latitude: 0,
                        longitude: 0,
                        address : "",
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
                        .get("/product/store/data?per_page=" +itemsPerPage +"&page=" +page+'&'+searchs)
                        .then((res) => {
                            this.loading = false;
                            this.items = res.data.result.list;
                            this.itemsTotalCount = res.data.result.total_count;
                            this.totalPages = 100;
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
                        .delete("/product/store/delete/"+this.editedItem.no)
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

                    axios
                        .post("/product/store/"+(this.editedIndex > -1 ? 'modify' : 'add'),formData)
                        .then((response) => {
                            if(response.data.success){
                                // this.showMessage.show = true;
                                // this.showMessage.message = response.data.message;
                                if(this.editedIndex > -1){
                                    Object.assign(this.items[this.editedIndex], this.editedItem);
                                }
                                else{
                                    this.items.unshift(this.editedItem)
                                }

                                this.close();
                                showMessage(response.data.message);

                            }
                            else{
                                showMessage(response.data.message);
                            }
                        });
                },
                getCode(code){
                    const codes =  new Promise((resolve, reject) => {
                        axios.get('/etc/code/sub_codes/'+code).then((res) => {
                            resolve(res.data.result);
                        });
                    });

                    codes.then(res => {
                        if(code == 'STORE'){
                            this.storeInfo = res;
                        }
                        else{
                            this.productType = res;
                        }
                    });
                },
            },
            mounted() {
                this.getCode('STORE');
            },
        });
    </script>
