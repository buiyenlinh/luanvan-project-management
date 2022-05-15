<script>
export default {
  data() {
    return {
      search: {
        name: '',
        leader: ''
      },
      loading_list: false,
      list: null,
      current_page: 1,
      last_page: 1,
      department: null,
      validate_form: false,
      error: null,
      reset_select_comp: false,
      members_show: [],
      members_id: [],
      select_leader: {
        text: '-- Tìm họ tên hoặc tên đăng nhập --',
        status: false
      },
      select_members: {
        text: '-- Tìm họ tên hoặc tên đăng nhập --',
        status: false
      },
      loading_update_add: false,
      loading_delete: false,
      _leader: null
    }
  },
  methods: {
    getList() {
      this.loading_list = true;
      this.$root.api.get('department/list', {
        params: {
          page: this.current_page,
          leader: this.search.leader,
          department: this.search.name
        }
      }).then(res => {
        if (res.data.status == "OK") {
          this.last_page = res.data.data.meta.last_page;
          this.list = res.data.data;
        } else {
          this.$notify(res.data.error);
        }
        this.loading_list = false;
      }).catch(err => {
        this.loading_list = false;
      })
    },
    changePage(page) {
			this.current_page = page;
			this.getList();
		},
    getLeaderSearch(_leader) {
      this.search.leader = _leader.id;
    },
    removeLeaderSearch() {
      this.search.leader = null;
    },
    handleSearch() {
      this.last_page = 1;
      this.changePage(1);

    },
    getDepartment(_department) {
      this.department = _.clone(_department);
      this.department.leader = _department.leader.id;
      this.select_leader.text = _department.leader.fullname || _department.leader.username;
      this._leader = _.clone(_department.leader);
    },
    onSubmit() {
      this.checkName();
      this.checkLeader();
      if (this.department.name != '' && this.error.leader == '') {
        this.loading_update_add = true;

        if (this.department.id != null) { // Cập nhật
          this.department.members = this.members_id;
          this.$root.api.post('department/update', this.department).then(res => {
            if (res.data.status == 'OK') {
              this.$notify(res.data.message, 'success');
              $('#department_modal_add').modal('hide');
              this.changePage(this.current_page);
            } else {
              this.$alert(res.data.error, '', 'error');
            }
            this.loading_update_add = false;
          }).catch(err => {
            this.$root.showError(err);
            this.loading_update_add = false;
          })

        } else { // Thêm
          this.department.members = this.members_id;
          this.department.created_by = this.$root.auth.id;
          this.$root.api.post('department/add', this.department).then(res => {
            if (res.data.status == 'OK') {
              this.$notify(res.data.message, 'success');
              $('#department_modal_add').modal('hide');
              this.changePage(1);
            } else {
              this.$alert(res.data.error, '', 'error');
            }
            this.loading_update_add = false;
          }).catch(err => {
            this.$root.showError(err);
            this.loading_update_add = false;
          })
        }
      }
      
    },
    handleCloseModal() {
      this.validate_form = false;
      this.department = {
        id: null,
        name: '',
        members: [],
        created_by: null,
        leader: null,
      }
      this.error = {
        name: '',
        created_by: '',
        leader: '',
        members: ''
      }
      this.select_members = {
        text: '-- Tìm họ tên hoặc tên đăng nhập --',
        status: !this.select_members.status
      }

      this.select_leader = {
        text: '-- Tìm họ tên hoặc tên đăng nhập --',
        status: !this.select_leader.status
      }

      this.text_select = '-- Tìm người tạo --';
      this.members_show = [];
      this.members_id = [];

      setTimeout(() => {
        this.validate_form = true;
      }, 300);
    },
    checkName() {
      if (this.department.name == '') {
        this.error.name = 'Tên nhóm làm việc là bắt buộc';
      } else {
        this.error.name = '';
      }
    },
    checkLeader() {
      if (this.error.leader == '') {
        if (!this.department.leader) {
          this.error.leader = 'Trưởng nhóm là bắt buộc';
        } else {
          this.error.leader = '';
        }
      }
    },
    getMember(_member) {
      if (this.department.leader == _member.id) { 
        this.error.members = 'Thành viên này đã được chọn làm trưởng nhóm làm việc';
        this.select_members.text = '-- Tìm họ tên hoặc tên đăng nhập --';
        this.select_members.status = !this.select_members.status;
      } else if (!this.members_id.includes(_member.id)) {
        this.members_id.push(_member.id);
        this.members_show.push(_member);
        this.error.members = '';
      }
    },
    getLeader(_leader) {
      this._leader = _.clone(_leader);
    
      if (!this.members_id.includes(_leader.id)) {
        this.department.leader = _leader.id;
        this.error.leader = '';
      } else {
        this.error.leader = 'Thành viên này đã được chọn vào nhóm làm việc';
        this.select_leader.text = '-- Tìm họ tên hoặc tên đăng nhập --';
        this.select_leader.status = !this.select_leader.status;
      }
    },
    removeLeader() {
      if (this.error.members == 'Thành viên này đã được chọn làm trưởng nhóm làm việc') {
        this.error.members = '';
      }
      this.department.leader = null;
      this.select_leader.text = '-- Tìm họ tên hoặc tên đăng nhập --';
      this.select_leader.status = !this.select_leader.status;
    },
    removeNewMember(_index) {
      this.members_show.splice(_index, 1);
      this.members_id.splice(_index, 1);
      this.select_members.text = '-- Tìm họ tên hoặc tên đăng nhập --';
      this.select_members.status = !this.select_members.status;

      this.getLeader(this._leader);
    },
    onSubmitDelete() {
      this.loading_delete = true;
      this.$root.api.delete(`department/delete/${this.department.id}`).then(res => {
        if (res.data.status == "OK") {
          this.$notify(res.data.message, 'success');
          $('#department_modal_delete').modal('hide');
          this.changePage(this.current_page);
        } else {
          this.$root.showError(res.data.error);
        }
        this.loading_delete = false;
      }).catch(err => {
        this.$root.showError(err);
        this.loading_delete = false;
      })
    }
  },
  created() {
    this.getList();
    this.handleCloseModal();
  },
  watch: {
    'department.name' () {
      if (!this.validate_form) {
        return;
      }
      this.checkName();
    },
    'department.leader'() {
      if (!this.validate_form) {
        return;
      }
      this.checkLeader();
    }
  },
  mounted() {
    this.current_page = parseInt(this.$route.query.page || 1);
    $(document).on('hidden.bs.modal', '#department_modal_add, #department_modal_delete', () => {
      this.handleCloseModal();
    });

    this.getList();
  }
}
</script>

<template>
  <div id="department">
    <form @submit.prevent="handleSearch" v-if="$root.isManager()">
      <div class="row">
        <div class="col-md-3 col-sm-5 col-12 mb-2">
          <input type="text" class="form-control form-control-sm" placeholder="Tên nhóm làm việc..." v-model="search.name">
        </div>
        <div class="col-md-3 col-sm-5 col-12 mb-2">
          <m-select
            :size="'sm'"
            text="-- Tìm theo trưởng nhóm --"
            url="user/search-user"
            :statusReset="false"
            @changeValue="getLeaderSearch"
            @remove="removeLeaderSearch"
            :variable="{first: 'fullname', second: 'username'}"
          />
        </div>
        <div class="col-md-2 col-sm-2 col-6 mb-2"> 
          <button type="submit" class="btn btn-info btn-sm">
            <i class="fas fa-search"></i> Tìm
          </button>
        </div>  
        <div class="col-md-4 col-sm-12 col-6 text-right mb-2" v-if="$root.isAdmin()">
          <button class="btn btn-info btn-sm" data-toggle="modal" data-target="#department_modal_add">Thêm</button>
        </div>
      </div>
    </form>

    <div class="list">
      <div class="text-center" v-if="loading_list">
        <m-spinner/>
      </div>
      <ul v-else class="row">
        <template v-if="list && list.data.length > 0">
          <li v-for="(item, index) in list.data" :key="index" class="col-md-3 col-sm-6 col-12 mb-2">
            <div class="info bg-fff">
              <p><i class="fas fa-folder"></i>&nbsp; <b>{{ item.name }}</b></p>
              <p style="font-size: 12px; margin-bottom: 0px">
                <b>Người tạo: </b>{{item.created_by.fullname || item.created_by.username}} <br>
                <b>Trưởng phòng: </b>{{item.leader.fullname || item.leader.username}} <br>
                <b>Tạo lúc: </b>{{item.created_at}} <br>
                <b>Thành viên:</b> <br>
              </p>
              <ul class="avt-mem d-flex justify-content-start mt-1 mb-2">
                <li><img :src="item.leader.avatar ? item.leader.avatar : $root.avatar_default" alt=""></li>
                <li v-for="(mem, _index) in item.members" :key="_index">
                  <img :src="mem.avatar ? mem.avatar : $root.avatar_default" alt="" v-if="_index <= 6">
                  <span v-if="_index == 7">{{item.members.length}}</span>
                </li>
              </ul>
              <div class="text-right" >
                <router-link :to="{ name: 'department-detail', params: { 'department_id': item.id } }">
                  <button class="btn btn-sm btn-dark" data-toggle="modal" data-target="#department_modal_details">Xem</button>
                </router-link>
                
                <template v-if="$root.isManager()">
                  <button class="btn btn-info btn-sm" @click="getDepartment(item)"
                    data-toggle="modal"
                    data-target="#department_modal_add">Sửa</button>

                  <button
                  class="btn btn-sm btn-danger" @click="getDepartment(item)"
                  data-toggle="modal" data-target="#department_modal_delete">Xóa</button>
                </template>
              </div>
            </div>
          </li>
        </template>
        <div v-else class="p-3">Chưa có nhóm làm việc</div>
      </ul>
    </div>

    <div class="text-center mt-3" v-if="last_page > 1">
      <m-pagination :page="current_page" :last-page="last_page" @change="changePage" />
    </div>

    <div class="modal fade" id="department_modal_add">
      <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">Nhóm làm việc</h4>
            <button type="button" class="close" data-dismiss="modal">&times;</button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="onSubmit">
              <div class="row">
                <div class="col-md-12 col-sm-12 col-12">
                  <div class="form-group">
                    <label><b>Tên nhóm làm việc <span class="text-danger">*</span></b></label>
                    <input v-if="department.id" type="text" class="form-control form-control-sm" v-model="department.name" disabled>
                    <input v-else type="text" class="form-control form-control-sm" v-model="department.name">
                    <div class="text-danger font-italic error">{{error.name}}</div>
                  </div>
                </div>

                <div class="col-md-12 col-sm-12 col-12">
                  <div class="form-group">
                    <label><b>Trưởng nhóm <span class="text-danger">*</span></b></label>
                    
                    <m-select
                      :size="'sm'"
                      :text="select_leader.text"
                      url="user/search-user-not-deparment" 
                      :statusReset="select_leader.status"
                      @changeValue="getLeader"
                      @remove="removeLeader"
                      :variable="{first: 'fullname', second: 'username'}"
                    />
                    <div class="text-danger font-italic error">{{error.leader}}</div>
                  </div>
                </div>

                <div class="col-md-12 col-sm-12 col-12">
                  <div class="form-group">
                    <label><b>Thành viên<span class="text-danger">*</span></b></label>
                    <m-select
                      :size="'sm'"
                      :text="select_members.text"
                      :show_icon_x="false"
                      url="user/search-user-not-deparment" 
                      :statusReset="select_members.status"
                      @changeValue="getMember"
                      :variable="{first: 'fullname', second: 'username'}"
                    />
                    <div class="text-danger font-italic error">{{error.members}}</div>
                    <div class="members">
                      <ul class="d-flex flex-wrap">
                        <li v-for="(item, index) in department.members" :key="index">
                          <img :src="item.avatar ? item.avatar : $root.avatar_default" alt="">
                          {{item.fullname || item.username}}
                        </li>
                        <li v-for="(item, index) in members_show" :key="index + department.members.length">
                          <img :src="item.avatar ? item.avatar : $root.avatar_default" alt="">
                          {{item.fullname || item.username}}
                          <i class="fas fa-times icon-remove" @click="removeNewMember(index)"></i>
                        </li>
                      </ul>
                    </div>
                  </div>
                </div>
              </div>
              <div class="modal-footer">
                <button type="submit" class="btn btn-info btn-sm">
                  {{ department.id ? 'Cập nhật' : 'Thêm'}}
                </button>
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Đóng</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="department_modal_delete">
      <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
          <form @submit.prevent="onSubmitDelete">
            <div class="modal-header">
              <h4 class="modal-title">Xóa nhóm làm việc</h4>
              <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" v-if="$root.isAdmin()">
              <div v-if="department.name" class="d-flex justify-content-start">
                <i class="fas fa-exclamation-triangle text-danger icon-warm-delete"></i>
                <span>
                  Bạn có muốn xóa nhóm làm việc <b>{{ department.name }}</b> không?
                </span>
              </div>
            </div>
            <div class="modal-footer">
              <button type="submit" class="btn btn-danger btn-sm">Xóa</button>
              <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Hủy</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <m-loading v-if="loading_update_add" :title="department.id != null ? 'Đang cập nhật nhóm làm việc' : 'Đang thêm nhóm làm việc'" :full="true" />
    <m-loading v-if="loading_delete" title="Đang xóa nhóm làm việc" :full="true" />
  
  </div>
</template>