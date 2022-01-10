<script>
export default {
  data() {
    return {
      username: '',
      password: '',
      username_error: '',
      password_error: '',
      loading_login: false,
    }
  },
  methods: {
    submitForm() {
      if (this.username && this.password) {
        let data = {
          username: this.username,
          password: this.password 
        }

        this.loading_login = true;
        this.$root.api.post('login', data).then(res => {
          this.loading_login = false;
          if (res.data.status == 'OK') {
            this.$root.setAuth(res.data.data.auth);
            this.$notify(res.data.message, 'success');
          } else {
            this.$alert(res.data.error, '', 'error');
          }
        }).catch(err => {
          this.loading_login = false;
          this.$root.showError(err);
        })
      } else {
        this.username_error = 'Tên đăng nhập là bắt buộc!';
        this.password_error = 'Mật khẩu là bắt buộc!';
      }
    }
  },
  watch: {
    'username': function (_new, _old) {
      if (_new == '') {
        this.username_error = 'Tên đăng nhập là bắt buộc!';
      } else {
        this.username_error = '';
      }
    },
    'password': function (_new, _old) {
      if (_new == '') {
        this.password_error = 'Mật khẩu là bắt buộc!';
      } else {
        this.password_error = '';
      }
    },
  }
}
</script>

<template>
  <div class="login">
    <div class="login-form">
      <form action="">
        <div class="text-center mb-3">
          <h3 style="font-weight: bold">WELCOME</h3>
          <p>Vui lòng nhập thông tin bên dưới</p>
        </div>
        
        <div class="mb-3">
          <label for="">Tên đăng nhập <span class="text-danger font-weight-bold">*</span></label>
          <div class="input-group">
            <div class="input-group-prepend">
              <span class="input-group-text"><i class="fas fa-user"></i></span>
            </div>
            <input type="text" class="form-control" v-model="username" />
          </div>
          <div class="text-danger">{{ username_error }}</div>
        </div>

        <div class="mb-2">
          <label for="">Mật khẩu <span class="text-danger font-weight-bold">*</span></label>
          <div class="input-group">
            <div class="input-group-prepend">
              <span class="input-group-text"><i class="fas fa-lock"></i></span>
            </div>
            <input type="password" class="form-control" v-model="password" />
          </div>
          <div class="text-danger">{{ password_error }}</div>
        </div>

        <div>
          <div class="text-right font-weight-bold mb-4"><a href="">Quên mật khẩu</a></div>
          <button 
            type="button"
            class="btn btn-primary"
            style="width: 100%"
            @click="submitForm"
          >
            Đăng nhập
          </button>
        </div>
      </form>
    </div>
    <m-loading v-if="loading_login" title="Đang đăng nhập" :full="true" />
  </div>
</template>