<div class="ibox-content m-b-sm border-bottom">
    <div class="row">
        <div class="col-sm-2">
            <div class="form-group">
                <label class="control-label">제품명</label>
                <input type="text" v-model="schFields.product_name" v-on:keyup.enter="readDataFromAPI" class="form-control">
            </div>
        </div>
        <div class="col-sm-2">
            <div class="form-group">
                <label class="control-label" for="status">구분</label>
                <select v-model="schFields.product_type" class="form-control">
                    <option value="">전체</option>
                    <option v-for="item in productType" :value="item.no">{{ item.name }}</option>
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
                                    itemsPerPageOptions: [30,50,100],
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
                                                            <v-text-field v-model="editedItem.product_name" :rules="rules.required" label="제품명" required></v-text-field>
                                                        </v-col>
                                                        <v-col cols="12" sm="6" md="4">
                                                            <v-text-field v-model="editedItem.product_eng_name" :rules="rules.required" label="영어제품명" required></v-text-field>
                                                        </v-col>
                                                        <v-col cols="12" sm="6" md="4">
                                                            <v-select
                                                                    v-model="editedItem.product_type"
                                                                    :items="productType"
                                                                    item-text="name"
                                                                    item-value="no"
                                                                    label="구분"
                                                                    persistent-hint
                                                            ></v-select>
                                                        </v-col>
                                                        <v-col cols="12" sm="6" md="4">
                                                            <v-text-field v-model="editedItem.product_price" label="가격" type="number"
                                                                          :rules="rules.required"
                                                                          required></v-text-field>
                                                        </v-col>
                                                        <v-col cols="12" sm="6" md="4">
                                                            <v-select
                                                                    v-model="editedItem.product_is_view"
                                                                    :items="viewType"
                                                                    item-text="name"
                                                                    item-value="no"
                                                                    label="노출"
                                                                    persistent-hint
                                                            ></v-select>
                                                        </v-col>
                                                        <v-col cols="12" sm="6" md="4">
                                                            <v-file-input
                                                                    v-model="editedItem.product_image"
                                                                    accept="image/*"
                                                                    label="이미지"
                                                                    :rules="rules.required"
                                                            ></v-file-input>
                                                        </v-col>

                                                        <v-col cols="12" sm="6" md="4">
                                                            <v-select
                                                                    v-model="editedItem.product_use_topping"
                                                                    :items="viewType"
                                                                    item-text="name"
                                                                    item-value="no"
                                                                    label="토핑사용여부"
                                                                    persistent-hint
                                                            ></v-select>
                                                        </v-col>
                                                        <v-col cols="12" sm="6" md="4">
                                                            <v-text-field v-model="editedItem.product_kcal" label="칼로리" type="number"
                                                                          :rules="rules.required"
                                                                          required></v-text-field>
                                                        </v-col>
                                                        <v-col cols="12" sm="6" md="4">
                                                            <v-text-field v-model="editedItem.product_sort" label="노출순서" type="number"
                                                                          :rules="rules.required"
                                                                          hint="숫자가 작을수록 상단노출"
                                                                          required></v-text-field>
                                                        </v-col>
                                                        <v-col cols="12" sm="6" md="4">
                                                            <v-textarea
                                                                    v-model="editedItem.product_size"
                                                                    label="사이즈"
                                                                    value=""
                                                            ></v-textarea>
                                                        </v-col>
                                                        <v-col cols="12" sm="12" md="8">
                                                            <v-textarea
                                                                    v-model="editedItem.product_desc"
                                                                    label="설명"
                                                                    value=""
                                                            ></v-textarea>
                                                        </v-col>
                                                    </v-row>
                                                    <v-row>
                                                        <v-col sm="20" style="color:#0D47A1">
                                                            <span class="headline">* 사이즈 입력 형식 => 사이즈명:추가금액:칼로리, 두개 이상일 경우 줄바꿈으로 처리입력<br/>
                                                            * 사이즈 입력 예 => Large size:2000:200</span>
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
                            <template v-slot:item.product_view="{ item }">
                                {{item.product_is_view ? 'Y' : 'N' }}
                            </template>
                            <template v-slot:item.product_use_size="{ item }">
                                {{item.product_size ? 'Y' : 'N' }}
                            </template>
                            <template v-slot:item.product_use_topping="{ item }">
                                {{item.product_use_topping ? 'Y' : 'N' }}
                            </template>
                            <template v-slot:item.view_image="{ item }">
                               <v-img  contain     max-height="150"
                                        max-width="150"
                                        :src="item.product_image"
                                ></v-img>
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
                                    사이즈 -> {{ item.product_size }}
                                    <hr>
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
                    totalPages: 50,
                    items: [],
                    loading: true,
                    options: {},
                    headers: [
                        { text: '제품명', value: 'product_name' },
                        { text: '영어 제품명', value: 'product_eng_name' },
                        { text: '구분', value: 'product_type_text' },
                        { text: '가격', value: 'product_price' },
                        { text: '노출', value: 'product_view' },
                        { text: '노출순서', value: 'product_sort' },
                        { text: '사이즈 사용', value: 'product_use_size' },
                        { text: '토핑 사용', value: 'product_use_topping' },
                        { text: '칼로리', value: 'product_kcal' },
                        { text: '이미지', value: 'view_image' },
                        { text: '등록일', value: 'insert_dt' },
                        { text: '상세', value: 'data-table-expand' },
                        { text: "액션", value: 'actions'},
                    ],
                    selected: [],
                    productType: [],
                    viewType: [{no : 1, name : 'Y'},{no : 0, name : 'N'}],
                    schFields: {
                        product_name: "",
                        product_type: "",
                    },
                    dialog: false,
                    dialogDelete: false,
                    editedIndex: -1,
                    editedItem: {
                        product_no: 0,
                        product_name: "",
                        product_type: "",
                        product_is_view: 0,
                        product_size: '',
                        product_use_topping: 0,
                        product_price: '',
                        product_kcal: '',
                        product_sort: '',
                        product_desc : "",
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
                        .get("/product/product/data?per_page=" +itemsPerPage +"&page=" +page+'&'+searchs)
                        .then((res) => {
                            this.loading = false;
                            this.items = res.data.result.list;
                            this.itemsTotalCount = res.data.result.total_count;
                            this.totalPages = 100;
                        });
                },
                getCode(){
                    axios
                        .get('/etc/code/sub_codes/PRODUCT_TYPE')
                        .then((res) => {
                            this.productType = res.data.result;
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
                        .delete("/product/product/delete/"+this.editedItem.product_no)
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

                    formData.append('product_image[]', this.editedItem.product_image);

                    axios
                        .post("/product/product/"+(this.editedIndex > -1 ? 'modify' : 'add'),formData)
                        .then((response) => {
                            if(response.data.success){
                                // this.showMessage.show = true;
                                // this.showMessage.message = response.data.message;
                                axios
                                    .get('/product/product/detail/'+response.data.result)
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
            },
            mounted() {
               this.getCode();
            },
        });
    </script>
