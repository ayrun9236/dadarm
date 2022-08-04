<div class="ibox-content m-b-sm border-bottom">
    <div class="row">
        <div class="col-sm-2">
            <div class="form-group">
                <label class="control-label" for="status">매장</label>
                <select v-model="schFields.store" class="form-control">
                    <option value="">선택</option>
                    <option v-for="item in storeInfo" :value="item.no">{{ item.name }}</option>
                </select>
            </div>
        </div>
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
                                v-model="selected"
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
                                show-select
                                item-key="product_no"
                                disable-sort
                                class="footable table table-stripped toggle-arrow-tiny"
                        >
                            <template v-slot:top>
                                <v-toolbar flat >
                                    <v-spacer></v-spacer>
                                    <div class="actions clearfix">
                                        <div class="float--right clearfix">
                                            <v-btn class="test" small @click="setProductView">제품 노출/제외</v-btn>
                                        </div>
                                    </div>
                                    <div class="actions clearfix">
                                        <div class="float--right clearfix">
                                            <v-btn class="test" small @click="setProductSoldout">매진처리/해제</v-btn>
                                        </div>
                                    </div>

                                    <v-dialog v-model="dialog" max-width="300px">
                                        <v-card>
                                            <v-card-title >
                                                <span class="headline">{{ formTitle }}</span>
                                            </v-card-title>
                                            <v-card-text>
                                                <v-row>
                                                    <v-col cols="12" sm="8" md="4">
                                                    <v-select
                                                            v-model="setModeValue"
                                                            :items="viewType"
                                                            item-text="name"
                                                            item-value="value"
                                                            label="설정"
                                                            persistent-hint
                                                    ></v-select>
                                                    </v-col>
                                                </v-row>
                                            </v-card-text>
                                            <v-card-actions>
                                                <v-spacer></v-spacer>
                                                <v-btn color="blue darken-1" text @click="close">Cancel</v-btn>
                                                <v-btn color="blue darken-1" text @click="modeConfirm">OK</v-btn>
                                                <v-spacer></v-spacer>
                                            </v-card-actions>
                                        </v-card>
                                    </v-dialog>
                                </v-toolbar>
                            </template>
                            <template v-slot:item.product_view="{ item }">
                                {{item.is_view ? 'Y' : 'N' }}
                            </template>
                            <template v-slot:item.soldout_view="{ item }">
                                {{item.is_view ? (item.is_soldout ? 'Y' : 'N' ) : '-' }}
                            </template>
                            <template v-slot:item.view_image="{ item }">
                               <v-img  contain     max-height="150"
                                        max-width="150"
                                        :src="item.product_image"
                                ></v-img>
                            </template>
                            <template v-slot:no-data>
                                <v-alert :value="true" color="error">
                                    데이터가 존재하지 않습니다.
                                </v-alert>
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
                    selected: [],
                    headers: [
                        { text: '제품명', value: 'product_name' },
                        { text: '구분', value: 'product_type_text' },
                        { text: '가격', value: 'product_price' },
                        { text: '노출', value: 'product_view' },
                        { text: '매진', value: 'soldout_view' },
                        { text: '이미지', value: 'view_image' },
                    ],
                    productType: [],
                    storeInfo: [],
                    viewType: [],
                    schFields: {
                        product_name: "",
                        product_type: "",
                        store: '',
                    },
                    dialog: false,
                    showMessage: {
                        show: false,
                        message : ''
                    },
                    setMode : '',
                    setModeValue : '',
                    listLoad : false,
                }
            },
            computed: {
                formTitle () {
                    return this.setMode === 'view' ? '매장별 제품 추가/제외' : '매장별 제품 매진처리/해제 '
                },
            },
            watch: {
                dialog (val) {
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
                    if(this.listLoad){
                        this.loading = true;
                        const { page, itemsPerPage } = this.options;

                        if(this.schFields.store > 0){
                            let searchs = Object.entries(this.schFields).map(e => e.join('=')).join('&');
                            const lists =  new Promise((resolve, reject) => {
                                axios.get("/product/store_product/data?per_page=" +itemsPerPage +"&page=" +page+'&'+searchs).then((res) => {
                                    resolve(res.data.result);
                                });
                            });

                            lists.then(res => {
                                this.loading = false;
                                this.items = res.list;
                                this.itemsTotalCount = res.total_count;
                                this.totalPages = 100;
                            });
                        }
                    }

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
                            this.schFields.store = this.storeInfo[0].no;
                            this.listLoad= true;
                            this.readDataFromAPI();
                        }
                        else{
                            this.productType = res;
                        }
                    });
                },
                modeConfirm () {

                    if(this.setModeValue === ''){
                        showMessage("설정할 값을 선택해 주세요.");
                        return;
                    }

                    let formData = new FormData();
                    formData.append('mode_value', this.setModeValue);
                    formData.append('mode', this.setMode);
                    formData.append('store', this.schFields.store);
                    for(let key in this.selected) {
                        formData.append('product_nos[]', this.selected[key].product_no);
                    }
                    //todo 리프레쉬 데이터
                    this.snackbar = true;
                    axios
                        .post("/product/store_product/modify",formData)
                        .then((response) => {
                            if(response.data.success){
                                // this.showMessage.show = true;
                                // this.showMessage.message = response.data.message;

                                for(let key in this.selected) {
                                    this.editedIndex = this.items.indexOf(this.selected[key]);
                                    if(this.setMode == 'view'){
                                        this.items[this.editedIndex].is_view = this.setModeValue === 1 ? true : false;
                                    }
                                    else{
                                        this.items[this.editedIndex].is_soldout = this.setModeValue === 1 ? true : false;
                                    }
                                }
                                this.selected = [];

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
                setProductView(){
                    if(this.schFields.store == ''){
                        showMessage("매장을 선택 후 검색을 진행 후 해당 기능을 진행해 주세요.");
                        return;
                    }

                    if(this.selected.length < 1){
                        showMessage('추가/제외 할 제품을 선택 해주세요.');
                        return;
                    }

                    this.dialog = true;
                    this.setMode = 'view';
                    this.viewType = [{value : 1, name : '제품추가'},{value : 0, name : '제품제외'}];
                },
                setProductSoldout(){
                    if(this.schFields.store == ''){
                        showMessage("매장을 선택 후 검색을 진행 후 해당 기능을 진행해 주세요.");
                        return;
                    }


                    if(this.selected.length < 1){
                        showMessage('매진/해제할 제품을 선택 해주세요.');
                        return;
                    }

                    this.dialog = true;
                    this.setMode = 'soldout';
                    this.viewType = [{value : 1, name : '매진추가'},{value : 0, name : '매진해제'}];
                }
            },
            mounted() {
               this.getCode('PRODUCT_TYPE');
               this.getCode('STORE');
            },
        });
    </script>
