<!DOCTYPE html>
<html>
<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>다-다름</title>

    <link href="/resources/css/bootstrap.min.css" rel="stylesheet">
    <link href="/resources/font-awesome/css/font-awesome.css" rel="stylesheet">

    <link href="/resources/css/animate.css" rel="stylesheet">
    <link href="/resources/css/style.css" rel="stylesheet">

</head>

<body class="gray-bg">

<div class="middle-box text-center loginscreen animated fadeInDown">
    <div id="app">
        <div>
            <h5 class="logo-name"><img src="/resources/img/logo.png" style="width:100%"></h5>
        </div>
        <form class="m-t" role="form" action="" onsubmit="return chkForm()">
            <input type="hidden" value="<?= $ref ?>" name="ref"/>
            <div class="form-group">
                <input type="text" name="id" v-model="input.username" v-on:keyup.enter="login" class="form-control" placeholder="Userid" required="아이디를 입력해주세요.">
            </div>
            <div class="form-group">
                <input type="password" name="pwd" v-model="input.password" v-on:keyup.enter="login" class="form-control" placeholder="Password" required="비밀번호를 입력해주세요.">
            </div>
            <div class="alert alert-danger" v-if="errorState">{{errorMessage}}</div>
            <button type="button" v-on:click="login" tabindex="3" class="btn btn-primary block full-width m-b">Login</button>
        </form>
    </div>
</div>

<!-- Mainly scripts -->
<script src="/resources/js/jquery-3.1.1.min.js"></script>
<script src="/resources/js/bootstrap.min.js"></script>
<?php //todo 링크변경 ?>
<script src="https://cdn.jsdelivr.net/npm/vue/dist/vue.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<!--<script src="https://cdn.jsdelivr.net/npm/vue/dist/vue.js"></script>-->
<!--<script src="https://unpkg.com/axios/dist/axios.min.js"></script>-->
<script>
    axios.defaults.headers.common = {
        "Content-Type": "application/json"
    }
    var app  = new Vue({
        el: '#app',
        data() {
            return {
                input: {
                    username: "",
                    password: ""
                },
                errorState:false,
                errorMessage : ''
            }
        },
        methods: {
            login() {
                if (this.input.username && this.input.password) {
                    let form = new FormData()
                    form.append('id', this.input.username)
                    form.append('pwd',this.input.password)

                    axios.post(
                        '/login/check_auth',
                        form).then(
                        res=> {
                            if(res.data.success == true){
                                location.href = '/';
                            }
                            else{
                                this.errorState = true;
                                this.errorMessage = res.data.message;
                            }
                        }
                    ).catch(function (error) {
                        console.log(error);
                    });

                } else {
                    this.errorState = true;
                    this.errorMessage = "아이디/비밀번호를 입력해 주세요."
                }
            }
        }
    })
</script>
</body>
</html>
