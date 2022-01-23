<script>
export default {
  data() {
    return {
      user: null,
      error: {
        username: '',
        password: '',
        email: '',
        active: '',
        role: '',
        phone: ''
      },
      avatar_preview: '',
      loading_update: false,
      loading_delete: false,
    }
  },
  created() {
    this.user = _.clone(this.$root.auth);
    this.avatar_preview = this.user.avatar;
    this.user.avatar = '';
  },
  methods: {
    onSubmit() {
      this.loading_update = true;
      let formData = new FormData();
      if (this.user.password) {
        formData.append("password", this.user.password);
      }
      if (this.user.avatar) {
        formData.append("avatar", this.user.avatar);
      }
      formData.append("username", this.user.username);
      formData.append("fullname", this.user.fullname);
      formData.append("gender", this.user.gender);
      formData.append("birthday", this.user.birthday);
      formData.append("email", this.user.email);
      formData.append("phone", this.user.phone);
      this.$root.api.post('profile', formData).then(res => {
        this.loading_update = false;
        if (res.data.status == 'OK') {
          this.$root.auth.username = res.data.data.username;
          this.$root.auth.fullname = res.data.data.fullname;
          this.$root.auth.avatar = res.data.data.avatar;
          this.$root.auth.gender = res.data.data.gender;
          this.$root.auth.birthday = res.data.data.birthday;
          this.$root.auth.phone = res.data.data.phone;
          this.$root.auth.email = res.data.data.email;
          this.avatar_preview = res.data.data.avatar;
          this.$notify(res.data.message, 'success');
        } else {
          this.$root.showError(res.data.error);
        }
      }).catch(err => {
        this.$root.showError(err);
        this.loading_update = false;
      })
    },
    handleChangeAvatar(image) {
      if (image?.target.files[0].type != "image/jpeg" && image?.target.files[0].type != "image/png" && image?.target.files[0].type != "image/jpg") {
        this.error.avatar = "Vui lòng chọn ảnh có phần mở rộng 'jpg', 'png', jpeg'";
      } else {
        this.avatar_preview = URL.createObjectURL(image?.target.files[0]);
        this.user.avatar = image?.target.files[0];
      }
    },
    handleDeleteAvatar() {
      this.loading_delete = true;
      this.$root.api.delete('profile/delete-avatar').then(res => {
        this.loading_delete = false;
        if (res.data.status == "OK") {
          this.$root.auth.avatar = res.data.data.avatar;
          this.$notify(res.data.message, 'success');
          this.avatar_preview = '';
        } else {
          this.$root.showError(res.data.error);
        }
      }).catch(err => {
        this.$root.showError(err);
        this.loading_update = false;
      })
    }
  }
}
</script>

<template>
  <div class="profile">
     <div class="row">
       <div class="col-md-3 col-sm-4 col-12 text-center">
         <div class="avatar mb-3">
          <img v-if="avatar_preview" :src="avatar_preview" alt="">
          <img v-else :src="$root.avatar_default" alt="">
          <div class="text-danger">{{error.avatar}}</div>
         </div>
         <button type="button" class="btn btn-info btn-sm mb-1" @click="$refs.RefAvatar.click()">Đổi ảnh</button>
          <input
            type="file"
            ref="RefAvatar"
            style="display: none"
            @change="handleChangeAvatar"
          />
          <button class="btn btn-danger btn-sm" @click="handleDeleteAvatar">Xóa ảnh</button>
       </div>
       <div class="col-md-9 col-sm-8 col-12">
         <form @submit.prevent="onSubmit">
          <div class="row">
            <div class="col-md-6 col-sm-12 col-12">
              <div class="form-group">
                <label><b>Tên tài khoản</b></label>
                <input type="text" class="form-control" v-model="user.fullname">
              </div>
            </div>
            <div class="col-md-6 col-sm-12 col-12">
              <div class="form-group">
                <label><b>Tên đăng nhập <span class="text-danger">*</span></b></label>
                <input type="text" class="form-control" v-model="user.username">
                <div class="error text-danger font-italic">{{ error.username }}</div>
              </div>
            </div>
            <div class="col-md-6 col-sm-12 col-12">
              <div class="form-group">
                <label><b>Mật khẩu <span class="text-danger">*</span></b></label>
                <input type="password" class="form-control" v-model="user.password">
                <div class="error text-danger font-italic">{{ error.password }}</div>
              </div>
            </div>
            <div class="col-md-6 col-sm-12 col-12">
              <div class="form-group">
                <label><b>Email <span class="text-danger">*</span></b></label>
                <input type="email" class="form-control" v-model="user.email">
                <div class="error text-danger font-italic">{{ error.email }}</div>
              </div>
            </div>
            <div class="col-md-6 col-sm-12 col-12">
              <div class="form-group">
                <label><b>Số điện thoại <span class="text-danger">*</span></b></label>
                <input type="tel" class="form-control" v-model="user.phone">
                <div class="error text-danger font-italic">{{ error.phone }}</div>
              </div>
            </div>
            <div class="col-md-6 col-sm-12 col-12">
              <div class="form-group">
                <label><b>Ngày sinh</b></label>
                <input type="date" class="form-control"  v-model="user.birthday">
              </div>
            </div>
            <div class="col-md-6 col-sm-12 col-12">
              <div class="form-group">
                <label><b>Giới tính</b></label><br>
                <div class="form-check-inline">
                  <label class="form-check-label">
                    <input type="radio" class="form-check-input" v-model="user.gender" value="F">Nữ
                  </label>
                </div>
                <div class="form-check-inline">
                  <label class="form-check-label">
                    <input type="radio" class="form-check-input" v-model="user.gender" value="M">Nam
                  </label>
                </div>
                <div class="form-check-inline">
                  <label class="form-check-label">
                    <input type="radio" class="form-check-input" v-model="user.gender" value="N">Khác
                  </label>
                </div>
              </div>
            </div>
            <div class="col-md-12 col-sm-12 col-12">
              <button type="submit" class="btn btn-info">Cập nhật</button>
            </div>
          </div>
        </form>
      </div>
    </div>
    <m-loading v-if="loading_update" title="Đang cập nhật thông tin" :full="true" />
    <m-loading v-if="loading_delete" title="Đang xóa ảnh đại diện" :full="true" />
  </div>
</template>