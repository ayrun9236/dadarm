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
                                :expanded="expanded"
                                show-expand
                                single-expand
                                item-key="no"
                                disable-sort
                                class="footable table table-stripped toggle-arrow-tiny"
                                @item-expanded="loadDetails"
                        >
                            <template v-slot:top>
                                <v-toolbar flat >
                                    <v-spacer></v-spacer>
                                    <v-dialog v-model="dialog" max-width="700px" >
                                        <template v-slot:activator="{ on, attrs }">
                                            <v-btn color="primary" dark class="mb-2" v-bind="attrs" v-on="on" >
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
                                                            <v-text-field v-model="editedItem.name" :rules="rules.required" label="코드명" required></v-text-field>
                                                        </v-col>
                                                        <v-col cols="12" sm="6" md="4">
                                                            <v-text-field v-model="editedItem.code" :rules="rules.required" label="코드" required></v-text-field>
                                                        </v-col>
                                                        <v-col cols="12" sm="6" md="4">
                                                            <v-text-field v-model="editedItem.eng_name" label="사용자 노출명" required></v-text-field>
                                                        </v-col>
                                                        <v-col cols="12" sm="6" md="4">
                                                            <v-text-field v-model="editedItem.css" label="적용될 css" hint="노출될 css"></v-text-field>
                                                        </v-col>
                                                        <v-col cols="12" sm="6" md="4">
                                                            <v-text-field v-model="editedItem.sort" :rules="rules.required" label="순서" hint="숫자가 작을수록 상단노출" required></v-text-field>
                                                        </v-col>
                                                        <v-col cols="12" sm="6" md="4">
                                                            <v-file-input
                                                                    v-model="editedItem.code_image"
                                                                    accept="image/*"
                                                                    label="이미지"
                                                            ></v-file-input>
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
                                            <v-card-title class="headline">해당 코드를 삭제하시겠습니까?<br/>하위코드까지 삭제됩니다.</v-card-title>
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
                            <template v-slot:item.code_view="{ item }">
                                {{item.is_view ? 'Y' : 'N' }}
                            </template>
                            <template v-slot:item.view_image="{ item }">
                               <v-img  contain     max-height="100"
                                        max-width="100"
                                        :src="item.code_image"
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
                            <template v-slot:expanded-item="{ headers, item}">
                                <td :colspan="headers.length">
                                    <v-data-table
                                            :headers="detailHeaders"
                                            :items="item.sub_codes"
                                            item-key="no"
                                            style="background-color:#9fba9e"
                                            hide-default-footer
                                            class="elevation-2"
                                    >
                                        <template v-slot:top>
                                            <v-toolbar flat >
                                                <slot name="activator" >
                                                    <v-btn color="primary" dark class="mb-2" @click="addSubCodes()">
                                                        신규 등록
                                                    </v-btn>
                                                </slot>
                                            </v-toolbar>
                                        </template>
                                        <template v-slot:item.sub_actions="{ item }">
                                            <v-icon small @click="editItem(item)">fa fa-edit</v-icon>
                                            <v-icon small @click="deleteItem(item)">fa fa-trash</v-icon>
                                        </template>
                                    </v-data-table>
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
                        { text: '코드', value: 'code' },
                        { text: '코드명', value: 'name' },
                        { text: '사용자노출명', value: 'eng_name' },
                        { text: '이미지', value: 'view_image' },
                        { text: '등록일', value: 'insert_dt' },
                        { text: '상세', value: 'data-table-expand' },
                        { text: "액션", value: 'actions'},
                    ],
                    selected: [],
                    detailHeaders: [
                        { text: '코드', value: 'code' },
                        { text: '코드명', value: 'name' },
                        { text: '사용자노출명', value: 'eng_name' },
                        { text: '순서', value: 'sort' },
                        { text: '이미지', value: 'view_image' },
                        { text: '등록일', value: 'insert_dt' },
                        { text: "액션", value: 'sub_actions'},
                    ],
                    dialog: false,
                    dialogDelete: false,
                    editedIndex: -1,
                    editedItem: {
                        no: 0,
                        parent_no: 0,
                        sort: 0,
                        code: "",
                        name: "",
                        eng_name: "",
                        code_image: 0,
                        css: ''
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
                    expanded_item_index : 0,
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
                addSubCodes(){
                    this.dialog = true;
                    this.editedItem.parent_no = this.items[this.expanded_item_index].no;
                },
                loadDetails({item}) {
                    const { page, itemsPerPage } = this.options;
                    axios
                        .get("/etc/code/data?parent_no="+item.no+"&per_page=" +itemsPerPage +"&page=" +page)
                        .then((res) => {
                            item.sub_codes = res.data.result;
                        });

                    this.expanded_item_index = this.items.indexOf(item);
                },
                readDataFromAPI() {
                    const { page, itemsPerPage } = this.options;
                    axios
                        .get("/etc/code/data?per_page=" +itemsPerPage +"&page=" +page)
                        .then((res) => {
                            this.items = res.data.result.map((item) => {
                                return {
                                    sub_codes: [],
                                    ...item
                                }
                            });
                            this.itemsTotalCount = this.items.length;

                        });
                },
                editItem (item) {
                    if(item.parent_no > 0){
                        this.editedIndex = 9999;
                    }
                    else{
                        this.editedIndex = this.items.indexOf(item)
                    }
                    this.editedItem = Object.assign({}, item)
                    this.dialog = true
                },

                deleteItem (item) {
                    if(item.parent_no > 0){
                        this.editedIndex = this.items[this.expanded_item_index].sub_codes.indexOf(item);
                    }
                    else{
                        this.editedIndex = this.items.indexOf(item)
                    }

                    this.editedItem = Object.assign({}, item)
                    this.dialogDelete = true
                },
                deleteItemConfirm () {
                    axios
                        .delete("/etc/code/delete/"+this.editedItem.no)
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

                    if(this.editedItem.parent_no > 0){
                        this.items[this.expanded_item_index].sub_codes.splice(this.editedIndex, 1)
                    }
                    else{
                        this.items.splice(this.editedIndex, 1)
                    }

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

                    formData.append('code_image[]', this.editedItem.code_image);

                    axios
                        .post("/etc/code/"+(this.editedIndex > -1 ? 'modify' : 'add'),formData)
                        .then((response) => {
                            if(response.data.success){
                                // this.showMessage.show = true;
                                // this.showMessage.message = response.data.message;
                                this.close();
                                showMessage(response.data.message);
                                document.location.reload();
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
