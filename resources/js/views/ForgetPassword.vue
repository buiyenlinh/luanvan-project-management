<script>
export default {
  data() {
    return {
      loading: false,
      loading_change: false,
      info: {
        email: '',
        username: '',
        password: '',
        code: ''
      },
      error: {
        username: '',
        password: '',
        code: '',
        email: ''
      },
      checkForm: false,
      see_password: false
    }
  },
  methods: {
    submitForm() {
      if (this.info.email == '') {
        this.error.email = 'Email là bắt buộc';
      } else {
        this.loading = true;
        this.$root.api.post('forget-password', { 'email': this.info.email }).then(res => {
          this.loading = false
          if (res.data.status == "OK") {
            this.$root.notify(res.data.message, 'success');
            this.checkForm = true;
          } else {
            this.$root.notify(res.data.error, 'error');
          }
        }).catch(err => {
          this.loading = false;
          this.$root.notify(err, 'error');
        })
      }
    },
    submitChangePass() {
      this.checkUsername();
      this.checkNewPass();
      this.checkCode();

      if (this.info.username && this.info.email && this.info.password && this.info.code) {
        this.loading_change = true;
        this.$root.api.post('change-password', this.info).then(res => {
          this.loading_change = false
          if (res.data.status == "OK") {
            this.$root.notify(res.data.message, 'success');
            this.$router.replace({ name: 'login' })
          } else {
            this.$root.notify(res.data.error, 'error');
          }
        }).catch(err => {
          this.loading_change = false;
          this.$root.notify(err, 'error');
        })
      }
      
    },
    checkUsername() {
      if (this.info.username == '')
        this.error.username = 'Tên đăng nhập là bắt buộc'
      else 
        this.error.username = ''
    },
    checkNewPass() {
      if (this.info.password == '')
        this.error.password = 'Mật khẩu mới là bắt buộc'
      else {
        if(this.info.password.length < 6) {
          this.error.password = 'Mật khẩu phải ít nhất 6 kí tự';
        } else {
          this.error.password = '';
        }
      }
    },
    checkCode() {
      if (this.info.code == '')
        this.error.code = 'Mã là bắt buộc'
      else 
        this.error.code = ''
    }
  },
  watch: {
    'info.email'() {
      if (this.info.email == '')
        this.error.email = 'Email là bắt buộc';
      else if (!this.$root.checkEmail(this.info.email))
        this.error.email = 'Email không hợp lệ';
      else 
        this.error.email = ''
    },
    'info.username'() {
      this.checkUsername();
    },
    'info.password'() {
      this.checkNewPass();
    },
    'info.code'() {
      this.checkCode();
    }
  }
}
</script>

<template>
  <div class="forget-password">
    <div class="forget-password-form">
      <form @submit.prevent="submitForm" v-if="!checkForm">
        <div class="text-center mb-3">
          <h3 style="font-weight: bold">XIN CHÀO</h3>
          <b>QUÊN MẬT KHẨU</b>
          <p>Vui lòng nhập thông tin bên dưới</p>
        </div>
        
        <div class="mb-3">
          <label for=""><b>Email</b> <span class="text-danger font-weight-bold">*</span></label>
          <div class="input-group">
            <div class="input-group-prepend">
              <span class="input-group-text"><i class="fas fa-envelope"></i></span>
            </div>
            <input type="email" class="form-control" v-model="info.email" />
          </div>
          <div class="text-danger font-italic error">{{ error.email }}</div>
        </div>

        <div>
          <button type="submit" class="btn btn-primary" style="width: 100%">Gửi đến email</button>
          <div style="border-top: 1px solid #ddd;" class="mt-4 ml-5 mr-5 mb-1"></div>
          <div class="text-center">
            <router-link :to="{ name: 'login' }">Đăng nhập</router-link>
          </div>
        </div>
      </form>

      <form @submit.prevent="submitChangePass" v-else>
        <div class="text-center mb-3">
          <h3 style="font-weight: bold">XIN CHÀO</h3>
          <b>TẠO MẬT KHẨU MỚI</b>
          <p>Vui lòng nhập thông tin bên dưới</p>
        </div>

        <div>
          <div class="mb-3">
            <label for=""><b>Tên đăng nhập</b> <span class="text-danger font-weight-bold">*</span></label>
            <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text"><i class="fas fa-user"></i></span>
              </div>
              <input type="text" class="form-control" v-model="info.username" />
            </div>
            <div class="text-danger font-italic error">{{ error.username }}</div>
          </div>

          <div class="mb-3">
            <label for=""><b>Mật khẩu</b> <span class="text-danger font-weight-bold">*</span></label>
            <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text"><i class="fas fa-lock"></i></span>
              </div>
              <input :type="see_password ? 'text' : 'password'" class="form-control" v-model="info.password" />

              <div class="eye-icon" style="position: absolute; right: 10px; top: 13px; z-index: 10">
                <i class="fas fa-eye" v-if="!see_password" style="cursor: pointer" @click="see_password = true"></i>
                <i class="fas fa-eye-slash" v-else style="cursor: pointer" @click="see_password = false"></i>
              </div>
            </div>
            <div class="text-danger font-italic error">{{ error.password }}</div>
          </div>

          <div class="mb-3">
            <label><b>Nhập mã <span class="text-danger">*</span></b></label>
            <input type="text" class="form-control" v-model="info.code">
            <div class="text-danger font-italic error">{{ error.code }}</div>
          </div>

          <button type="submit" class="btn btn-primary" style="width: 100%">Đổi mật khẩu</button>
          <div style="border-top: 1px solid #ddd;" class="mt-4 ml-5 mr-5 mb-1"></div>
          <div class="text-center">
            <router-link :to="{ name: 'login' }">Đăng nhập</router-link>
          </div>
        </div>
      </form>
    </div>

    <m-loading v-if="loading" :title="'Đang gửi tới mã đến ' + info.email" :full="true" />
  </div>
</template>