<script>
export default {
  data() {
    return {
      list: null,
      role_list: null,
      user: null,
      error: null,
      validate_form: false,
      avatar_preview: '',
      loading_get_user: true,
      loading_add: false,
      loading_delete: false,
      current_page: 1,
			last_page: 1,
      search: {
        keyword: '',
        active: -1
      }
    }
  },
  methods: {
    getUserList() {
      this.loading_get_user = true;
      this.$root.api('user/list', {
        params: {
					page: this.current_page,
          keyword: this.search.keyword,
          active: this.search.active
				}
      }).then(res => {
        if (res.data.status == "OK") {
          this.list = res.data.data;
					this.last_page = res.data.data.meta.last_page;

					if (this.current_page > 1 && !this.list.data.length) {
						this.$router.replace({
							name: 'user',
							query: {
								page: this.current_page - 1
							}
						})
						this.changePage(this.current_page - 1);
					}
        }
        this.loading_get_user = false;
      }).catch(err => {
        this.loading_get_user = false;
        this.$root.showError(err);
      })
    },
		changePage(page) {
			this.current_page = page;
			this.getUserList();
		},
    getRole() {
			this.$root.api.get('role/list').then(res => {
				this.role_list = res.data.data;
			}).catch(err => this.$root.showError(err));
		},
    handleChangeAvatar(image) {
      if (image?.target.files[0].type != "image/jpeg" && image?.target.files[0].type != "image/png" && image?.target.files[0].type != "image/jpg") {
        this.error.avatar = "Vui lòng chọn ảnh có phần mở rộng 'jpg', 'png', jpeg'";
      } else {
        this.avatar_preview = URL.createObjectURL(image?.target.files[0]);
        this.user.avatar = image?.target.files[0];
      }
    },
    onSubmit() {
      this.checkUserName();
      if (this.user.id == null) {
        this.checkPassword();
      }
      this.checkRole();
      this.checkActive();
      this.checkEmail();
      this.checkPhone();
      if (this.user.username && this.user.phone && this.user.email && this.error.active == '' && this.error.role == '') {
        this.loading_add = true;
        let formData = new FormData();
        formData.append('username', this.user.username);
        formData.append('fullname', this.user.fullname);
        formData.append('gender', this.user.gender);
        formData.append('active', this.user.active);
        formData.append('phone', this.user.phone);
        formData.append('role', this.user.role);
        formData.append('email', this.user.email);
        formData.append('birthday', this.user.birthday);

        // Tạo user
        if (this.user.id == null) {
          if (this.user.password) {
            formData.append('password', this.user.password);
            formData.append('avatar', this.user.avatar);
            this.$root.api.post('user/add',  formData).then(res => {
							this.loading_add = false;
              if (res.data.status == 'OK') {
                this.$notify(res.data.message, 'success');
                $('#user_modal').modal('hide');
								this.changePage(1);
              } else {
                this.$alert(res.data.error, '', 'error');
              }
            }).catch(err => {
              this.loading_add = false;
              this.$root.showError(err);
            })
          }
        } else {
          if (this.user.password) {
            formData.append('password', this.user.password);
          }
          if (this.user.avatar) {
            formData.append('avatar', this.user.avatar);
          }
          formData.append('id', this.user.id);
          this.loading_add = true;
          this.$root.api.post('user/update', formData).then(res => {
            this.loading_add = false;
            if (res.data.status == "OK") {
              this.$notify(res.data.message, 'success');
              $('#user_modal').modal('hide');
            } else {
              this.$alert(res.data.error, '', 'error');
            }
          }).catch(err => {
            this.loading_add = false;
            this.$root.showError(err);
          })
        }
      }
    },
    onSubmitDelete() {
      this.loading_delete = true;
      this.$root.api.delete(`user/delete/${this.user.id}`).then(res => {
        if (res.data.status == "OK") {
          this.$notify(res.data.message, 'success');
          $('#delete_user_modal').modal('hide');
          this.loading_delete = false;
          this.getUserList();
        } else {
          this.$alert(res.data.error, '', 'error');
          this.loading_add = false;
          this.loading_delete = false;
        }
      }).catch(err => {
        this.loading_add = false;
        this.$root.showError(err);
        this.loading_delete = false;
      })
    },
    checkUserName() {
      if (this.user.username == '') {
        this.error.username = 'Tên đăng nhập là bắt buộc';
      } else {
        this.error.username = '';
      }
    },
    checkPassword() {
      if (this.user.password == '') {
        this.error.password = 'Mật khẩu là bắt buộc';
      } else {
        if(this.user.password.length < 6) {
          this.error.password = 'Mật khẩu phải ít nhất 6 kí tự';
        } else {
          this.error.password = '';
        }
      }
    },
    checkRole() {
      if (this.user.role <= 0) {
        this.error.role = 'Quyền tài khoản là bắt buộc';
      } else {
        this.error.role = '';
      }
    },
    checkActive() {
      if (this.user.active != '0' && this.user.active != '1') {
        this.error.active = 'Trạng thái là bắt buộc';
      } else {
        this.error.active = '';
      }
    },
    checkEmail() {
      if (this.user.email == '') {
        this.error.email = 'Email là bắt buộc';
      } else {
        if (!this.$root.checkEmail(this.user.email)) {
          this.error.email = 'Email không hợp lệ';
        } else {
          this.error.email = '';
        }
      }
    },
    checkPhone() {
      if (this.user.phone == '') {
        this.error.phone = 'Số điện thoại là bắt buộc';
      } else {
        if (this.user.phone?.length < 10) {
          this.error.phone = 'Số điện thoại độ dài không hợp lệ'
        } else if (!(this.$root.checkPhone(this.user.phone))) {
          this.error.phone = 'Số điện thoại không hợp lệ'
        } else {
          this.error.phone = '';
        }
      }
    },
    handleCloseModal() {
      this.validate_form = false;

      this.user = {
        id: null,
        username: '',
        fullname: '',
        password: '',
        email: '',
        phone: '',
        avatar: '',
        birthday: '',
        gender: 'N',
        active: '0',
        role: ''
      };

      this.error = {
        username: '',
        password: '',
        email: '',
        active: '',
        role: '',
        phone: ''
      }
      
      this.avatar_preview = '';
      
      setTimeout(() => {
        this.validate_form = true;
      }, 300);
    },
    getInfoUpdate(item) {
      this.user.id = item.id;
      this.user.username = item.username;
      this.user.fullname = item.fullname;
      this.user.email = item.email;
      this.user.phone = item.phone;
      this.user.birthday = item.birthday;
      this.user.gender= item.gender;
      this.user.active= item.active;
      this.user.role = item.role.id;
      this.avatar_preview = item.avatar;
    },
    handleSearch() {
      this.last_page = 1;
      this.changePage(1);
    }
  },
  created() {
    this.handleCloseModal();
  },
  mounted() {
    this.current_page = parseInt(this.$route.query.page || 1);
    this.getUserList();
    this.getRole();
    
    $(document).on('hidden.bs.modal', '#user_modal', () => {
      this.handleCloseModal();
    });
  },
  watch: {
    'user.username'() {
      if (!this.validate_form) {
        return;
      }
      this.checkUserName();
    },
    'user.password'() {
      if (!this.validate_form) {
        return;
      }
      this.checkPassword();
    },
    'user.email'() {
      if (!this.validate_form) {
        return;
      }
      this.checkEmail();
    },
    'user.active'() {
      if (!this.validate_form) {
        return;
      }
      this.checkActive();
    },
    'user.role'() {
      if (!this.validate_form) {
        return;
      }
      this.checkRole();
    },
    'user.phone'() {
      if (!this.validate_form) {
        return;
      }
      this.checkPhone();
    }
  }
}
</script>

<template>
  <div class="user">
    <form @submit.prevent="handleSearch">
      <div class="row">
        <div class="col-md-3 col-sm-5 col-12 mb-2">
          <input type="text" class="form-control form-control-sm" placeholder="Tìm tên hoặc tên đăng nhập..." v-model="search.keyword">
        </div>
        <div class="col-md-3 col-sm-5 col-12 mb-2">
          <select class="form-control form-control-sm" v-model.number="search.active">
            <option value="-1">  -- Trạng thái --</option>
            <option value="1">Kích hoạt</option>
            <option value="0">Khóa</option>
          </select>
        </div>
        <div class="col-md-2 col-sm-2 col-6 mb-2"> 
          <button type="submit" class="btn btn-info btn-sm">
            <i class="fas fa-search"></i> Tìm
          </button> 
        </div>
        <div class="col-md-4 col-sm-12 col-6 text-right mb-2" v-if="$root.isAdmin()">
          <button class="btn btn-info btn-sm" data-toggle="modal" data-target="#user_modal">Thêm</button>
        </div>
      </div>
    </form>
    <div class="card">
      <div class="card-header bg-info text-white">Danh sách người dùng</div>
      <div class="table-responsive">
        <table class="table table-bordered table-stripped mb-0">
          <thead>
            <tr>
              <td><b>STT</b></td>
              <td><b>Tên đăng nhập</b></td>
              <td><b>Tên tài khoản</b></td>
              <td><b>Email</b></td>
              <td><b>Trạng thái</b></td>
              <td><b>Ngày tạo</b></td>
              <td></td>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading_get_user">
              <td colspan="1000" align="center">
                <m-spinner />
              </td>
            </tr>
            <template v-else-if="list">
              <tr v-for="(item, index) in list.data" :key="index">
                <td>{{ index + 1 }}</td>
                <td>{{ item.username }}</td>
                <td>{{ item.fullname }}</td>
                <td>{{ item.email }}</td>
                <td>
                  <span class="badge" :class="{
                    'badge-secondary': !item.active,
                    'badge-success': item.active
                  }">{{ item.active ? 'Kích hoạt' : 'Khóa' }}</span>
                </td>
                <td>{{ item.created_at }}</td>
                <td>
                  <div v-if="$root.auth.role.level < item.role.level && $root.isAdmin()" class="icon">
                    <i class="fas fa-edit text-info"
                      title="Cập nhật"
                      @click="getInfoUpdate(item)"
                      data-toggle="modal"
                      data-target="#user_modal"></i>
                    <i class="fas fa-trash-alt text-danger"
                      title="Xóa"
                      @click="getInfoUpdate(item)"
                      data-toggle="modal"
                      data-target="#delete_user_modal"></i>
                  </div>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
    </div>

    <div class="text-center mt-3" v-if="last_page > 1">
      <m-pagination :page="current_page" :last-page="last_page" @change="changePage" />
    </div>

    <div class="modal fade" id="user_modal">
      <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">Tài khoản</h4>
            <button type="button" class="close" data-dismiss="modal">&times;</button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="onSubmit" v-if="$root.isAdmin()">
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
                <div class="col-md-6 col-sm-12 col-12">
                  <div class="form-group">
                    <label><b>Trạng thái <span class="text-danger">*</span></b></label><br>
                    <div class="form-check-inline">
                      <label class="form-check-label">
                        <input type="radio" class="form-check-input" v-model="user.active" value="1">Kích hoạt
                      </label>
                    </div>
                    <div class="form-check-inline">
                      <label class="form-check-label">
                        <input type="radio" class="form-check-input" v-model="user.active" value="0">Khóa
                      </label>
                    </div>
                    <div class="error text-danger font-italic">{{ error.active }}</div>
                  </div>
                </div>
                <div class="col-md-6 col-sm-12 col-12">
                  <div class="form-group">
                    <label><b>Phân quyền <span class="text-danger">*</span></b></label>
                    <select class="form-control" v-model="user.role">
                      <option value="">   -- Chọn quyền --</option>
                      <template v-for="(item, index) in role_list">
                      <option
                        :key="index"
                        v-if="item.level > $root.auth.role.level && item.level != 1"
                        :value="item.id">{{item.name}}</option>
                      </template>
                    </select>
                    <div class="error text-danger font-italic">{{ error.role }}</div>
                  </div>
                </div>
                <div class="col-md-6 col-sm-12 col-12">
                  <div class="form-group">
                    <label><b>Ảnh đại diện</b></label><br>
                    <button type="button" class="btn btn-info btn-sm" @click="$refs.RefAvatar.click()">Chọn ảnh</button>
                    <input
                      type="file"
                      ref="RefAvatar"
                      style="display: none"
                      @change="handleChangeAvatar"
                    />
                    <img v-if="avatar_preview" :src="avatar_preview" alt="" class="pl-2" style="width: 150px; height: auto;"><br>
                    <div class="error text-danger font-italic">{{ error.avatar }}</div>
                  </div>
                </div>
              </div>
              <div class="modal-footer">
                <button type="submit" class="btn btn-info btn-sm">
                  {{ user.id > 0 ? 'Cập nhật' : 'Thêm'}}
                </button>
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Đóng</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="delete_user_modal">
      <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
          <form @submit.prevent="onSubmitDelete">
            <div class="modal-header">
              <h4 class="modal-title">Xóa tài khoản</h4>
              <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" v-if="$root.isAdmin()">
            <div v-if="user.username"> Bạn có muốn xóa người dùng <b>{{ user.username }}</b> không?</div>
            </div>
            <div class="modal-footer">
              <button type="submit" class="btn btn-danger btn-sm">Xóa</button>
              <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Đóng</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <m-loading v-if="loading_add" :title="this.user.id != null ? 'Đang cập nhật tài khoản' : 'Đang thêm tài khoản'" :full="true" />
    <m-loading v-if="loading_delete" title="Đang xóa tài khoản" :full="true" />
  </div>
</template>

<style scoped>
.icon i {
  cursor: pointer;
}
</style>