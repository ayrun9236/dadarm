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
                                item-key="user_no"
                                disable-sort
                                class="footable table table-stripped toggle-arrow-tiny"
                        >
                            <template v-slot:top>
                                <v-toolbar flat >
                                    <v-spacer></v-spacer>
                                    <v-dialog v-model="dialog" max-width="800px" >
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
                                                            <v-text-field v-model="editedItem.name" label="그룹명" required></v-text-field>
                                                        </v-col>
                                                        <v-col cols="12"
                                                               sm="6"
                                                               md="4">
                                                            <v-select
                                                                    v-model="editedItem.is_grant_all"
                                                                    :items="viewType"
                                                                    item-text="name"
                                                                    item-value="no"
                                                                    v-on:change="changeGrant"
                                                                    label="전체권한"
                                                                    persistent-hint
                                                            >
                                                            </v-select>
                                                        </v-col>
                                                    </v-row>
                                                    <v-row>
                                                        <v-col cols="12"
                                                               sm="6"
                                                               md="4"
                                                               v-for="menu in menus"
                                                               :key="menu.no">
                                                            <v-checkbox
                                                                    v-model="editedItem.menus"
                                                                    color="error"
                                                                    :label="menu.name"
                                                                    :value="menu.no"
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
                                            <v-card-title class="headline">해당 그룹을 삭제 하시겠습니까?</v-card-title>
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
                            <template v-slot:expanded-item="{ headers, item }">
                                <td :colspan="headers.length">
                                    탈퇴사유 -> {{ item.leave_reason_text }}, 탈퇴사유 직접입력-> {{ item.leave_reason_etc }}
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
            "Content-Type": "application/json"
        }
        new Vue({
            el: '#app',
            vuetify: new Vuetify(),
            components: {
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
                        { text: '그룹명', value: 'name' },
                        { text: '등록일', value: 'insert_dt' },
                        { text: '상세', value: 'data-table-expand' },
                        { text: "액션", value: 'actions'}
                    ],
                    schFields: {
                    },
                    dialog: false,
                    dialogDelete: false,
                    editedIndex: -1,
                    editedItem: {
                        no:0,
                        name: "",
                        menus : [],
                        is_grant_all : 0,
                    },
                    defaultItem: {
                        no:0,
                        name: "",
                        menus : [],
                        is_grant_all : 0,
                    },
                    viewType: [{no : true, name : 'Y'},{no : false, name : 'N'}],
                    formValid: false,
                    showMessage: {
                        show: false,
                        message : ''
                    },
                    expanded: [],
                    menus: [],
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
                        .get("/admin/group/data?per_page=" +itemsPerPage +"&page=" +page+'&'+searchs)
                        .then((res) => {
                            this.loading = false;
                            this.items = res.data.result.list.map((item) => {
                                let menus = item.menus ? item.menus.split(',') : [];
                                item.menus = [];
                                for(let key in menus) {
                                    item.menus.unshift(menus[key]*1);
                                }
                                return {
                                    ...item
                                }
                            });

                            this.itemsTotalCount = res.data.result.total_count;
                            this.totalPages = 100;
                        });
                },
                getCode(){
                    axios
                        .get('/admin/group/menus')
                        .then((res) => {
                            this.menus = res.data;
                        });
                },
                editItem (item) {
                    this.editedIndex = this.items.indexOf(item)
                    this.editedItem = Object.assign({}, item)
                    if(this.editedItem.is_grant_all){
                        for(let key in this.menus) {
                            this.editedItem.menus.unshift(key, this.menus[key].no);
                        }
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
                        .delete("/admin/group/delete/"+this.editedItem.no)
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

                    for(let key in this.editedItem.menus) {
                        formData.append('menus[]', this.editedItem.menus[key]);
                    }

                    axios
                    .post("/admin/group/"+(this.editedIndex > -1 ? 'modify' : 'add'),formData)
                        .then((response) => {
                            if(response.data.success) {
                                // this.showMessage.show = true;
                                // this.showMessage.message = response.data.message;
                                this.close();
                                showMessage(response.data.message);
                                window.location.reload();
                            }
                            else{
                                showMessage(response.data.message);
                            }
                        });
                },
                changeGrant(e){
                    this.editedItem.menus = [];
                    if(e === true){
                        for(let key in this.menus) {
                            this.editedItem.menus.unshift(key, this.menus[key].no);
                        }
                    }
                }
            },
            mounted() {
                this.getCode();
            },
        });

    </script>
