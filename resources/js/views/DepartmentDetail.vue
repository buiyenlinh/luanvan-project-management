<script>
export default {
  data() {
    return {
      text_search: '',
      loading_list: false,
      list: null,
      department: '',
      user: null,
      select_member: {
        text: '--- Tìm họ tên hoặc tên đăng nhập',
        status: false,
        user_id: [],
        error_user_id: '',
        user_id_show: []
      },
      validate_form: false,
      loading_add_new_member: false
    }
  },
  methods: {
    getList() {
      this.loading_list = true;
      this.$root.api.post(`department/detail/${this.department_id}`, {
        'keyword' : this.text_search
      }).then(res => {
        this.loading_list = false;
        if (res.data.status == "OK") {
          this.list = res.data.data;

          // Kiểm tra user login có thuộc nhóm làm việc này không
          let ids = [];
          for (let i in res.data.data.list.data) {
            ids.push(res.data.data.list.data[i].id);
          }
          if (!ids.includes(this.$root.auth.id) && !this.$root.isAdmin()) {
            this.$router.replace({ name: 'department' });
            this.$root.$notify('Bạn không thuộc nhóm làm việc này', 'error');
          }
        } else {
          this.$root.$notify(res.data.error, 'error');
        }
      }).catch(err => {
        this.$root.$notify(err, 'error');
        this.loading_list = false;
      })
    },
    getDepartment() {
      this.$root.api.get(`department/info/${this.department_id}`).then(res => {
        if (res.data.status == "OK") {
          this.department = res.data.data;
        } else {
          this.$router.replace({name: 'department'});
        }
      }).catch(err => {
        this.$router.replace({name: 'department'});
        this.$root.$notify(err, 'error');
      })
    },
    handleCloseModal() {
      this.validate_form = false;
      this.user = {
        fullname: '',
        username: '',
        avatar: '',
        active: '',
        birthday: '',
      }

      this.select_member.user_id = [];
      this.select_member = {
        text: '--- Tìm họ tên hoặc tên đăng nhập',
        status: !this.select_member.status,
        user_id: [],
        error_user_id: '',
        user_id_show: []
      }

      setTimeout(() => {
        this.validate_form = true;
      }, 300);  
    },
    getInfoUser(_user) {
      this.user = _user;
    },
    getNewMemberInDepartment(_user) {
      this.select_member.user_id.push(_user.id);
      this.select_member.user_id_show.push(_user);
    },
    removeNewMember(_index) {
      this.select_member.user_id.splice(_index, 1);
      this.select_member.user_id_show.splice(_index, 1);
    },
    checkNewMember() {
      if (this.select_member.user_id.length == 0) {
        this.select_member.error_user_id = 'Thành viên là bắt buộc';
      } else {
        this.select_member.error_user_id = '';
      }
    },
    handleAddMember() {
      this.checkNewMember();
      this.loading_add_new_member = true;
      if (this.select_member.error_user_id == '') {
        this.$root.api.post(`department/add-member/${this.department_id}`, {
          new_members: this.select_member.user_id
        }).then(res => {
          this.loading_add_new_member = false;
          if (res.data.status == 'OK') {
            this.$notify(res.data.message, 'success');
            this.getList(); 
            $('#department_detail_modal_add').modal('hide');
          } else {
            this.$alert(res.data.error, '', 'error');
          }
        }).catch(err => {
          this.$root.showError(err);
          this.loading_add_new_member = false;
        })
      }
    },
  },
  created() {
    this.handleCloseModal();
  },
  mounted() {
    this.department_id = this.$route.params.department_id;
   
    $(document).on('hidden.bs.modal', '#department_detail_info, #department_detail_modal_add', () => {
      this.handleCloseModal();
    });
    this.getDepartment();
    this.getList();
  },
  watch: {
    'select_member.user_id'() {
      if (!this.validate_form)
        return;
      this.checkNewMember();
    }
  }
}
</script>

<template>
  <div id="department-detail">
    <template v-if="department">
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
          <li class="breadcrumb-item">
            <router-link :to="{ name: 'department' }">Nhóm làm việc</router-link>  
          </li>
          <li class="breadcrumb-item active" aria-current="page">{{ department.name }}</li>
        </ol>
      </nav>

      <form @submit.prevent="getList">
        <div class="row">
          <div class="col-md-5 col-sm-5 col-8 mb-2">
            <input type="text" class="form-control form-control-sm" placeholder="Tên thành viên..." v-model="text_search">
          </div>
          <div class="col-md-2 col-sm-3 col-4 mb-2"> 
            <button type="submit" class="btn btn-info btn-sm">
              <i class="fas fa-search"></i> Tìm
            </button>
          </div>  
          <div class="col-md-5 col-sm-12 col-12 text-right mb-2" v-if="$root.isAdmin() || (list && $root.auth.id == this.list.leader)">
            <button type="button" class="btn btn-info btn-sm" data-toggle="modal" data-target="#department_detail_modal_add">Thêm</button>
          </div>
        </div>
      </form>

      <div class="list">
        <div class="card">
          <div class="card-header bg-info text-white">Danh sách thành viên</div>
            <div class="table-responsive">
              <table class="table table-bordered table-stripped mb-0">
                <thead>
                  <tr>
                    <td><b>STT</b></td>
                    <td><b>Tên</b></td>
                    <td><b>Chức vụ</b></td>
                    <td><b>Trạng thái</b></td>
                    <td><b>Ngày tạo</b></td>
                    <td></td>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="loading_list">
                    <td colspan="1000" align="center">
                      <m-spinner />
                    </td>
                  </tr>
                  <template v-else-if="list && list.list.data.length > 0">
                    <tr v-for="(item, index) in list.list.data" :key="index">
                      <td>{{ index + 1 }}</td>
                      <td>{{ item.fullname || item.username }}</td>
                      <td>
                        <span class="badge badge-success" v-if="list && list.leader == item.id">Trưởng nhóm</span>
                        <span class="badge badge-info" v-else>thành viên</span>
                      </td>
                      <td>
                        <span :class="['badge', item.active ? 'badge-success' : 'badge-danger']">
                          {{ item.active ? 'Kích hoạt' : 'Khóa'}}
                        </span>
                      </td>
                      <td style="font-size: 13px">{{ item.created_at }}</td>
                      <td>
                        <button class="btn btn-sm btn-dark" @click="getInfoUser(item)"
                          data-toggle="modal" data-target="#department_detail_info">Thông tin</button>
                      </td>
                    </tr>
                  </template>
                  <tr v-else ><td colspan="1000" align="center">Không có thành viên</td></tr>
                </tbody>
              </table>
            </div>
          </div>
      </div>

      <div class="modal fade" id="department_detail_info">
        <div class="modal-dialog modal-dialog-scrollable">
          <div class="modal-content">
            <div class="modal-header">
              <h4 class="modal-title">Thông tin thành viên</h4>
              <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body text-center">
              <div>
                <img v-if="user.avatar" :src="user.avatar" alt="" style="width: 170px; height: 170px">
                <img v-else :src="$root.avatar_default" alt="" style="width: 170px; height: 170px">
              </div>
              <div class="form-group">
                <b>{{ user.fullname || user.username }}</b>
              </div>
              <div class="form-group">{{ user.birthday }}</div>
              <div class="form-group">
                <b>Trạng thái tài khoản</b><br>
                <span :class="['badge', user.active ? 'badge-success' : 'badge-danger']">{{ user.active ? 'Kích hoạt' : 'Khóa'}}</span>
              </div>
            </div>

            <div class="modal-footer">
              <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Đóng</button>
            </div>
          </div>
        </div>
      </div>

      <div class="modal fade" id="department_detail_modal_add">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h4 class="modal-title">Thêm thành viên</h4>
              <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body mb-3">
              <div class="form-group">
                <label><b>Người dùng</b><span class="text-danger">*</span></label>
                <m-select
                  :size="'sm'"
                  :text="select_member.text"
                  :show_icon_x="false"
                  url="user/search-user-not-deparment" 
                  :statusReset="select_member.status"
                  @changeValue="getNewMemberInDepartment"
                  :variable="{first: 'fullname', second: 'username'}"
                />
                <div class="text-danger font-italic error">{{select_member.error_user_id}}</div>
                <div class="new-members">
                  <ul v-if="select_member" class="d-flex flex-wrap">
                    <li v-for="(item, index) in select_member.user_id_show" :key="index">
                      <img :src="item.avatar ? item.avatar : $root.avatar_default" alt="">
                      {{item.fullname || item.username}}
                      <i class="fas fa-times icon-remove" @click="removeNewMember(index)"></i>
                    </li>
                  </ul>
                </div>
              </div>
            </div>

            <div class="modal-footer">
              <button type="button" class="btn btn-info btn-sm" @click="handleAddMember">Thêm thành viên</button>
              <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Đóng</button>
            </div>
          </div>
        </div>
      </div>
    </template>
    <m-spinner v-else class="mb-2" />

    <m-loading v-if="loading_add_new_member" title="Đang thêm thành viên" :full="true" />
  </div>
</template>