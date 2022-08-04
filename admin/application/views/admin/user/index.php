<div class="ibox-content m-b-sm border-bottom">
    <div class="row">
        <div class="col-sm-1">
            <div class="form-group">
                <label class="control-label">매장</label>
                <select v-model="schFields.store" class="form-control">
                    <option value="">선택</option>
                    <option v-for="item in storeInfo" :value="item.no">{{ item.name }}</option>
                </select>
            </div>
        </div>
        <div class="col-sm-1">
            <div class="form-group">
                <label class="control-label">이름</label>
                <input type="text" v-model="schFields.name" v-on:keyup.enter="readDataFromAPI" class="form-control">
            </div>
        </div>
        <div class="col-sm-2">
            <div class="form-group">
                <label class="control-label" for="date_added">등록일</label>
                <div class="input-group">
                    <date-picker v-model="schFields.sdate"  value-type="format" format="YYYY-MM-DD"></date-picker>
                    <span class="input-group-addon">~</span>
                    <date-picker v-model="schFields.edate"  value-type="format" format="YYYY-MM-DD"></date-picker>
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
                                item-key="no"
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
                                                            <v-text-field v-model="editedItem.name" :rules="rules.required" label="이름" required></v-text-field>
                                                        </v-col>
                                                        <v-col cols="12"
                                                               sm="6"
                                                               md="4">
                                                            <v-text-field v-model="editedItem.login_id" :rules="rules.required" label="로그인ID"></v-text-field>
                                                        </v-col>
                                                        <v-col cols="12"
                                                               sm="6"
                                                               md="4">
                                                            <v-text-field v-model="editedItem.login_password" type="password" :rules="editedIndex > -1 ? '':rules.required" label="비밀번호"></v-text-field>
                                                        </v-col>
                                                        <v-col cols="12"
                                                               sm="6"
                                                               md="4">
                                                            <v-select
                                                                    v-model="editedItem.store_no"
                                                                    :items="storeInfo"
                                                                    item-text="name"
                                                                    item-value="no"                                                                    
                                                                    label="매장"
                                                                    persistent-hint
                                                            >
                                                            </v-select>
                                                        </v-col>

                                                        <v-col cols="12"
                                                               sm="6"
                                                               md="4">
                                                            <v-select
                                                                    v-model="editedItem.admin_group_no"
                                                                    :items="groupInfo"
                                                                    item-text="name"
                                                                    item-value="no"
                                                                    label="관리자그룹"
                                                                    persistent-hint
                                                            >
                                                            </v-select>
                                                        </v-col>

                                                        <v-col cols="12"
                                                               sm="6"
                                                               md="4">
                                                            <v-select
                                                                    v-model="editedItem.is_leave"
                                                                    :items="viewType"
                                                                    item-text="name"
                                                                    item-value="no"
                                                                    label="탈퇴여부"
                                                                    persistent-hint
                                                            >
                                                            </v-select>
                                                        </v-col>
                                                    </v-row>
                                                    <v-row>
                                                        <v-col sm="20" v-if="!editedIndex">
                                                            <span class="headline">* 비밀번호를 입력하면 비밀번호도 수정됩니다.</span>
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
                                            <v-card-title class="headline">해당 관리자를 퇴사처리 하시겠습니까?</v-card-title>
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
                            <template v-slot:item.leave="{ item }">
                                {{item.is_leave ? 'Y' : 'N' }}
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
                        { text: '그룹명', value: 'admin_group_name' },
                        { text: '지점', value: 'store_name' },
                        { text: '이름', value: 'name' },
                        { text: '로그인ID', value: 'login_id' },
                        { text: '등록일', value: 'insert_dt' },
                        { text: '퇴사여부', value: 'leave' },
                        { text: "액션", value: 'actions'}
                    ],
                    storeInfo: [],
                    groupInfo : [],
                    schFields: {
                        store: "",
                        name: "",
                        sdate: "",
                        edate: ""
                    },
                    dialog: false,
                    dialogDelete: false,
                    editedIndex: -1,
                    viewType: [{no : true, name : 'Y'},{no : false, name : 'N'}],
                    editedItem: {
                        no:0,
                        name : '',
                        admin_group_no: "",
                        login_id: "",
                        login_password: "",
                        store_no:0
                    },
                    formValid: false,
                    rules: {
                        required: [value => !!value || "필수입력"],
                    },
                    showMessage: {
                        show: false,
                        message : ''
                    },
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
                        .get("/admin/user/data?per_page=" +itemsPerPage +"&page=" +page+'&'+searchs)
                        .then((res) => {
                            this.loading = false;
                            this.items = res.data.result.list;
                            this.itemsTotalCount = res.data.result.total_count;
                            this.totalPages = 100;
                        });
                },
                getCode(){
                    axios
                        .get('/etc/code/sub_codes/STORE')
                        .then((res) => {
                            this.storeInfo = res.data.result;
                            this.storeInfo.unshift({'no':'0','name':'전체'});
                        });

                    axios
                        .get('/admin/group/data')
                        .then((res) => {
                            this.groupInfo = res.data.result.list;
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
                        .delete("/admin/user/leave/"+this.editedItem.no)
                        .then((response) => {
                            if(response.data.success){
                                // this.showMessage.show = true;
                                // this.showMessage.message = response.data.message;
                                axios
                                    .get('/admin/user/detail/'+this.editedItem.no)
                                    .then((res) => {
                                        if(this.editedIndex > -1){
                                            Object.assign(this.items[this.editedIndex], res.data.result)
                                        }

                                        this.close();
                                        showMessage(response.data.message);
                                    });
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
                    .post("/admin/user/"+(this.editedIndex > -1 ? 'modify' : 'add'),formData)
                        .then((response) => {
                            if(response.data.success) {
                                // this.showMessage.show = true;
                                // this.showMessage.message = response.data.message;
                                axios
                                    .get('/admin/user/detail/'+response.data.result)
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
