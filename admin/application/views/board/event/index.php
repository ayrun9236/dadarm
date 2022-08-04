<div class="ibox-content m-b-sm border-bottom">
    <div class="row">
        <div class="col-sm-1">
            <div class="form-group">
                <label class="control-label">제목</label>
                <input type="text" v-model="schFields.title" v-on:keyup.enter="readDataFromAPI" class="form-control">
            </div>
        </div>
        <div class="col-sm-2">
            <div class="form-group">
                <label class="control-label" for="date_added">이벤트기간</label>
                <div class="input-group">
                    <date-picker v-model="schFields.event_sdate"  value-type="format" format="YYYY-MM-DD"></date-picker>
                    <span class="input-group-addon">~</span>
                    <date-picker v-model="schFields.event_edate"  value-type="format" format="YYYY-MM-DD"></date-picker>
                </div>
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
                                                        <v-text-field solo v-model="editedItem.title" :rules="rules.required" label="제목" required style="margin-top: 10px"></v-text-field>
                                                    </v-row>
                                                    <v-row>
                                                        <v-file-input solo
                                                                    v-model="editedItem.main_image"
                                                                    accept="image/*"
                                                                    label="메인 이미지"
                                                                    :rules="editedIndex > 0 ? '' : rules.required"
                                                            ></v-file-input>

                                                    </v-row>
                                                    <v-row>
                                                        <v-select solo
                                                                      v-model="editedItem.sub_type"
                                                                      :items="coupons"
                                                                      item-text="coupon_name"
                                                                      item-value="coupon_master_no"
                                                                      label="쿠폰선택"
                                                            ></v-select>
                                                        <v-menu
                                                                v-model="cevent_sdate"
                                                                :close-on-content-click="false"
                                                                :nudge-right="40"
                                                                transition="scale-transition"
                                                                offset-y
                                                                min-width="auto"
                                                        >
                                                            <template v-slot:activator="{ on, attrs }">
                                                                <v-text-field solo
                                                                        v-model="editedItem.sdate"
                                                                        label="이벤트 시작일"
                                                                        readonly
                                                                        v-bind="attrs"
                                                                        v-on="on"
                                                                ></v-text-field>
                                                            </template>
                                                            <v-date-picker
                                                                    v-model="editedItem.sdate"
                                                                    @input="cevent_sdate = false"
                                                            ></v-date-picker>
                                                        </v-menu>
                                                        <v-menu
                                                                v-model="cevent_edate"
                                                                :close-on-content-click="false"
                                                                :nudge-right="40"
                                                                transition="scale-transition"
                                                                offset-y
                                                                min-width="auto"
                                                        >
                                                            <template v-slot:activator="{ on, attrs }">
                                                                <v-text-field solo
                                                                              v-model="editedItem.edate"
                                                                              label="이벤트 종료일"
                                                                              readonly
                                                                              v-bind="attrs"
                                                                              v-on="on"
                                                                ></v-text-field>
                                                            </template>
                                                            <v-date-picker
                                                                    v-model="editedItem.edate"
                                                                    @input="cevent_edate = false"
                                                            ></v-date-picker>
                                                        </v-menu>
                                                    </v-row>
                                                    
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
                            <template v-slot:item.event_date="{ item }">
                                {{item.sdate}}~{{item.edate}}
                            </template>
                            <template v-slot:item.view_image="{ item }">
                                <v-img  contain     max-height="150"
                                        max-width="150"
                                        :src="item.main_image"
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
        return "image_target=event&image_temp_no="+image_temp_no+'&image_target_no='+image_target_no;
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
                        { text: '이벤트기간', value: 'event_date' },
                        { text: '등록일', value: 'insert_dt' },
                        { text: '쿠폰', value: 'coupon_name' },
                        { text: '이미지배너', value: 'view_image' },
                        { text: '상세', value: 'data-table-expand' },
                        { text: "액션", value: 'actions'}
                    ],
                    registType: [],
                    schFields: {
                        title: "",
                        event_sdate: "",
                        event_edate: "",
                        insert_sdate: "",
                        insert_edate: "",
                        type: ""
                    },
                    dialog: false,
                    dialogDelete: false,
                    editedIndex: -1,
                    editedItem: {
                        type_code:'event',
                        image_temp_no : 0,
                        sub_type : 0,
                        main_image : '',
                        sdate : '',
                        edate : '',
                        board_no:0,
                        title: "",
                        contents : "",
                    },
                    defaultItem: {
                        type_code:'event',
                        image_temp_no : 0,
                        sub_type : 0,
                        main_image : '',
                        sdate : '',
                        edate : '',
                        board_no:0,
                        title: "",
                        contents : "",
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
                    is_editor_make : false,
                    type : 'event',
                    sub_type : [],
                    coupons : [],
                    cevent_sdate: false,
                    cevent_edate: false,
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
                                setTimeout(this.smarteditor_make, 100, item.contents);
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
                getCoupon(){
                    axios
                        .get('/event/coupon/data?page=1&per_page=100')
                        .then((res) => {
                            this.coupons = res.data.result.list;
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

                    formData.append('main_image[]', this.editedItem.main_image);

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

                    oEditors.getById['contents'].exec("SET_IR", [contents]);
                }
            },
            mounted() {
                this.schFields.type = this.type;
                this.editedItem.type = this.type;
                this.defaultItem.type = this.type;

                this.getCoupon();
            },
        });

    </script>

