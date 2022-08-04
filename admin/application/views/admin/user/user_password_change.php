<div class="ibox-content m-b-sm border-bottom passwordBox" style="padding-top:40px;">
    <div class="row">
        <div class="col-md-12">
                <h2 class="font-bold">비밀번호 변경</h2>
                <div class="row">
                    <div class="col-lg-12">
                        <div class="form-group">
                            <input type="password" v-model="password" class="form-control" placeholder="password" required="">
                        </div>
                        <button @click="change" class="btn btn-primary block full-width m-b">변경하기</button>
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
        data () {
            return {
                password: '',
            }
        },
        methods: {
            change() {
                let formData = new FormData();
                formData.append('password', this.password);

                axios
                    .post("/admin/user/user_password_change",formData)
                    .then((response) => {
                        alert(response.data.message);
                    });
            },
        },
    });
</script>