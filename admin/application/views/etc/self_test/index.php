<div class="ibox-content m-b-sm border-bottom">
    <div class="row">
        <div class="col-sm-2">
            <div class="form-group">
                <label class="control-label" for="status">구분</label>
                <select v-model="schFields.product_type" class="form-control">
                    <option value="">전체</option>
                    <option v-for="item in sub_type" :value="item.no">{{ item.name }}</option>
                </select>
            </div>
        </div>
        <div class="col-sm-1">
            <div class="form-group">
                <label class="control-label">제목</label>
                <input type="text" v-model="schFields.title" v-on:keyup.enter="readDataFromAPI" class="form-control">
            </div>
        </div>
        <div class="col-sm-2">
            <div class="form-group">
                <label class="control-label" for="date_added">등록일</label>
                <div class="input-group">
                    <date-picker v-model="schFields.insert_sdate"  value-type="format" format="YYYY-MM-DD"></date-picker>
                    <span class="input-group-addon">~</span>
                    <date-picker v-model="schFields.insert_edate"  value-type="format" format="YYYY-MM-DD"></date-picker>
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
                                :expanded="expanded"
                                show-expand
                                single-expand
                                item-key="board_no"
                                disable-sort
                                class="footable table table-stripped toggle-arrow-tiny"
                                @item-expanded="loadDetails"
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
                                                        <v-text-field persistent-hint v-model="editedItem.title" :rules="rules.required" label="제목" required style="margin-top: 20px"></v-text-field>
                                                    </v-row>
                                                    <v-row>
                                                        <v-col cols="8" sm="6" md="4">
                                                            <v-select
                                                                      v-model="editedItem.sub_type"
                                                                      :items="sub_type"
                                                                      item-text="name"
                                                                      item-value="no"
                                                                      label="구분"
                                                                      persistent-hint
                                                            ></v-select>
                                                        </v-col>
                                                        <v-col cols="8" sm="6" md="4">
                                                            <v-select
                                                                    v-model="editedItem.is_view"
                                                                    :items="viewType"
                                                                    item-text="name"
                                                                    item-value="no"
                                                                    label="노출"
                                                                    persistent-hint
                                                            ></v-select>
                                                        </v-col>
                                                        <v-col cols="8" sm="6" md="4">
                                                            <v-file-input
                                                                    v-model="editedItem.image"
                                                                    accept="image/*"
                                                                    label="이미지"
                                                                    persistent-hint
                                                            ></v-file-input>
                                                        </v-col>
                                                    </v-row>
                                                    <v-row>
                                                        <v-text-field persistent-hint v-model="editedItem.link" label="링크"></v-text-field>
                                                    </v-row>
                                                    <v-row>
                                                        <textarea v-model="editedItem.contents" name="contents" id="contents" rows="10" cols="100">에디터에 기본으로 삽입할 글(수정 모드)이 없다면 이 value 값을 지정하지 않으시면 됩니다.</textarea>
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
                                            <v-card-title class="headline">해당 글을 삭제 하시겠습니까?</v-card-title>
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
                            <template v-slot:item.is_view="{ item }">
                                {{item.is_view ? 'Y' : 'N' }}
                            </template>
                            <template v-slot:item.board_image="{ item }">
                                <v-img  contain     max-height="150"
                                        max-width="150"
                                        :src="item.board_image"
                                ></v-img>
                            </template>
                            <template v-slot:item.question="{ item }">
                                문항설정
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
                                    <div v-html="item.contents"></div>
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

<script type="text/javascript" src="/resources/js/plugins/naver-smart-editor/js/service/HuskyEZCreator.js?v=<?=time()?>" charset="utf-8"></script>
<script type="text/javascript">
    let oEditors = [];
    let is_make_contents = 0;
    let image_temp_no = 0;
    let image_target_no = 0;

    function photolink(){
        return "image_target=board&image_temp_no="+image_temp_no+'&image_target_no='+image_target_no;
    }
</script>

    <script>
        document.body.setAttribute('data-app', true);

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
                        { text: '제목', value: 'title' },
                        { text: '등록일', value: 'insert_dt' },
                        { text: '노출', value: 'is_view' },
                        { text: '이미지', value: 'board_image' },
                        { text: '분류', value: 'sub_type_text' },
                        { text: '문항설정', value: 'question' },
                        { text: '상세', value: 'data-table-expand' },
                        { text: "액션", value: 'actions'}
                    ],
                    schFields: {
                        title: "",
                        insert_sdate: "",
                        insert_edate: "",
                        type: ""
                    },
                    dialog: false,
                    dialogDelete: false,
                    editedIndex: -1,
                    viewType: [{no : 1, name : 'Y'},{no : 0, name : 'N'}],
                    editedItem: {
                        type:'',
                        image_temp_no : 0,
                        sub_type : 0,
                        board_no:0,
                        title: "",
                        is_view:0,
                        link:'',
                        contents : "",
                    },
                    defaultItem: {
                        type:'',
                        image_temp_no : 0,
                        sub_type : 0,
                        board_no:0,
                        title: "",
                        contents : "",
                    },
                    formValid: false,
                    rules: {
                        required: [value => !!value || "필수입력"],
                    },
                    showMessage: {
                        show: false,
                        message : ''
                    },
                    expanded: [],
                    is_editor_make : false,
                    type : 'self_test',
                    sub_type : []
                }
            },
            computed: {
                formTitle () {
                    return this.editedIndex === -1 ? '신규등록' : '수정'
                },
            },
            watch: {
                dialog (val) {
                    val || this.close();
                    if(val == true){
                        if(this.editedIndex == -1){
                            image_temp_no = Math.floor(Math.random() * 10000000) + 1;
                            image_target_no = 0;
                            this.editedItem.image_temp_no = image_temp_no;
                            setTimeout(this.smarteditor_make, 200, '');
                        }
                    }
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
                        .get("/etc/board/data?per_page=" +itemsPerPage +"&page=" +page+'&'+searchs)
                        .then((res) => {
                            this.loading = false;
                            this.itemsTotalCount = res.data.result.total_count;
                            this.totalPages = 100;

                            this.items = res.data.result.list.map((item) => {
                                return {
                                    contents: '',
                                    ...item
                                }
                            })

                        });
                },
                editItem (item) {

                    //동기로 변경하기
                    this.editedIndex = this.items.indexOf(item);
                    if(item.contents == ''){
                        axios
                            .get('/etc/board/detail/'+item.board_no)
                            .then((res) => {
                                item.contents = res.data.result.contents;
                                this.editedItem = Object.assign({}, item)

                                image_temp_no = 0;
                                image_target_no = this.editedItem.board_no;
                                this.editedItem.image_temp_no = 0;
                                this.dialog = true;
                                setTimeout(this.smarteditor_make, 500, item.contents);

                            });
                    }
                    else{
                        this.editedItem = Object.assign({}, item)

                        image_temp_no = 0;
                        image_target_no = this.editedItem.board_no;
                        this.editedItem.image_temp_no = 0;
                        this.dialog = true;
                        setTimeout(this.smarteditor_make, 100, item.contents);
                    }
                },

                deleteItem (item) {
                    this.editedIndex = this.items.indexOf(item)
                    this.editedItem = Object.assign({}, item)
                    this.dialogDelete = true
                },

                deleteItemConfirm () {
                    axios
                        .delete("/etc/board/delete/"+this.editedItem.board_no)
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
                getCode(sub_code){
                    axios
                        .get('/etc/code/sub_codes/'+sub_code)
                        .then((res) => {
                            this.sub_type = res.data.result;
                        });
                },
                loadDetails({item}) {
                    if(item.contents == ''){
                        axios
                            .get('/etc/board/detail/'+item.board_no)
                            .then((res) => {
                                item.contents = res.data.result.contents;
                            });
                    }

                },
                save () {
                    if (this.$refs.form.validate()) {
                        this.snackbar = true
                    }
                    else{
                        return;
                    }

                    oEditors.getById["contents"].exec("UPDATE_CONTENTS_FIELD", []);
                    this.editedItem.contents = document.getElementById("contents").value;

                    let formData = new FormData();
                    for(let key in this.editedItem) {
                        formData.append(key, this.editedItem[key]);
                    }

                    formData.append('attach_image[]', this.editedItem.image);
                    axios
                    .post("/etc/board/"+(this.editedIndex > -1 ? 'modify' : 'add'),formData)
                        .then((response) => {
                            if(response.data.success){
                                // this.showMessage.show = true;
                                // this.showMessage.message = response.data.message;
                                axios
                                    .get('/etc/board/detail/'+response.data.result+'/simple')
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
                smarteditor_make(contents) {
                    if(this.is_editor_make === false){
                        nhn.husky.EZCreator.createInIFrame({
                            oAppRef: oEditors,
                            elPlaceHolder: 'contents',
                            sSkinURI: "/resources/js/plugins/naver-smart-editor/SmartEditor2Skin.html?v=<?=time()?>",
                            htParams : {
                                bUseToolbar : true,
                                bUseVerticalResizer : true,
                                bUseModeChanger : true,
                                bSkipXssFilter : true,
                                fOnBeforeUnload : function(){ },
                            },
                            fCreator: "createSEditor2"
                        });

                        this.is_editor_make = true;
                    }

                    setTimeout(function() {oEditors.getById['contents'].exec("SET_IR", [contents])}, 100);


                }
            },
            mounted() {
                this.schFields.type = this.type;
                this.editedItem.type = this.type;
                this.defaultItem.type = this.type;

                if(this.type == 'self_test'){
                    this.getCode('SELF_TEST_TYPE');
                }
            },
        });

    </script>

