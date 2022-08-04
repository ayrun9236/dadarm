
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
                                item-key="no"
                                disable-sort
                                class="footable table table-stripped toggle-arrow-tiny"
                        >
                            <template v-slot:top>
                                <v-toolbar flat >
                                    <v-spacer></v-spacer>

                                    <v-dialog v-model="dialog" max-width="500px" >
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
                                                        <v-col cols="12" sm="6" md="6">
                                                            <v-text-field v-model="editedItem.name" :rules="rules.required" label="토핑명" required></v-text-field>
                                                        </v-col>
                                                        <v-col cols="12" sm="6" md="6">
                                                            <v-text-field v-model="editedItem.code" :rules="rules.required" label="토핑코드" required></v-text-field>
                                                        </v-col>
                                                    </v-row>
                                                    <v-row>
                                                        <v-col cols="12" sm="6" md="6">
                                                            <v-text-field v-model="editedItem.price" label="가격" type="number"
                                                                          :rules="rules.required"
                                                                          required></v-text-field>
                                                        </v-col>
                                                        <v-col cols="12" sm="6" md="6">
                                                            <v-text-field v-model="editedItem.kcal" label="칼로리" type="number"
                                                                          :rules="rules.required"
                                                                          required></v-text-field>
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
                            <template v-slot:item.topping_view="{ item }">
                                {{item.is_view ? 'Y' : 'N' }}
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
                                    설명 -> {{ item.details }}
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
                        { text: '토핑명', value: 'name' },
                        { text: '토핑코드', value: 'code' },
                        { text: '가격', value: 'price' },
                        { text: '칼로리', value: 'kcal' },
                        { text: '등록일', value: 'insert_dt' },
                        { text: "액션", value: 'actions'},
                    ],
                    selected: [],
                    viewType: [{no : 1, name : 'Y'},{no : 0, name : 'N'}],
                    dialog: false,
                    dialogDelete: false,
                    editedIndex: -1,
                    editedItem: {
                        no: 0,
                        name: "",
                        code: "",
                        price : '',
                        kcal : '',
                    },
                    defaultItem: {
                        no: 0,
                        name: "",
                        code: "",
                        price : '',
                        kcal : '',
                    },
                    viewType: [],
                    formValid: false,
                    rules: {
                        required: [value => !!value || "필수입력"]
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

                    axios
                        .get("/product/topping/data?per_page=" +itemsPerPage +"&page=" +page)
                        .then((res) => {
                            this.loading = false;
                            this.items = res.data.result.list.map((item) => {
                                return {
                                    details: '',
                                    ...item
                                }
                            })
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
                        .delete("/product/topping/delete/"+this.editedItem.no)
                        .then((response) => {
                            if(response.data.success){
                                //this.showMessage.show = true;
                                //this.showMessage.message = response.data.message;
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
                    this.soldout_dialog = false
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
                        .post("/product/topping/"+(this.editedIndex > -1 ? 'modify' : 'add'),formData)
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
            },
            mounted() {

            },
        });
    </script>
