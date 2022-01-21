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
      text_select_leader: '-- Tìm họ tên hoặc tên đăng nhập --',
      loading_update_add: false,
      _leader: null
    }
  },
  methods: {
    getList() {
      this.loading_list = true;
      this.$root.api.get('department/list', {
        params: {
          page: this.current_page
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
    getUserSearch(_leader) {
      this.search.leader = _leader.id;
    },
    handleSearch() {
      this.last_page = 1;
      this.changePage(1);

    },
    getDepartment(_department) {
      this.department = _.clone(_department);
      this.department.leader = _department.leader.id;
      this.text_select_leader = _department.leader.fullname || _department.leader.username;
      this.members_show = _.clone(_department.members);
      this._leader = _.clone(_department.leader);
    },
    onSubmit() {
      this.checkName();
      this.checkMember();
      this.checkLeader();
      if (this.department.name != '' && this.error.members == '' && this.error.leader == '') {
        this.loading_update_add = true;
        let arr = [];
        for (let i in this.members_show) {
          arr.push(this.members_show[i]['id']);
        }
        this.department.members = arr;
        if (this.department.id != null) {
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

        } else {
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
        leader: null
      }
      this.error = {
        name: '',
        members: '',
        created_by: '',
        leader: ''
      }
      this.text_select = '-- Tìm người tạo --';
      this.reset_select_comp = !this.reset_select_comp;
      this.members_show = [];
      this.text_select_leader = '-- Tìm họ tên hoặc tên đăng nhập --';

      setTimeout(() => {
        this.validate_form = true;
      }, 300);
    },
    checkName() {
      if (this.department.name == '') {
        this.error.name = 'Tên dự án là bắt buộc';
      } else {
        this.error.name = '';
      }
    },
    checkMember() {
      if (this.members_show.length == 0) {
        this.error.members = 'Thành viên là bắt buộc'
      } else {
        this.error.members = '';
      }
    },
    checkLeader() {
      if (this.error.leader == '') {
        if (!this.department.leader) {
          this.error.leader = 'Trưởng phòng ban là bắt buộc';
        } else {
          this.error.leader = '';
        }
      }
    },
    getMember(_member) {
      if (this.department.leader == _member.id) { 
        this.error.members = 'Thành viên này đã được chọn làm trưởng phòng ban';
      } else if (!this.members_show.includes(_member.id)) {
        this.error.members = '';
        this.members_show.push(_member);
      }
    },
    getLeader(_leader) {
      this._leader = _.clone(_leader);
      let check = false;
      for (let i in this.members_show) {
        if (_leader.id == this.members_show[i]['id']) {
          this.error.leader = 'Thành viên này đã được chọn vào phòng ban';
          check = true;
        }
      }
      if (!check) {
        this.department.leader = _leader.id;
        this.error.leader = '';
      }
    },
    removeMember(_index) {
      this.members_show.splice(_index, 1);
      this.getLeader(this._leader);
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
    'members_show' () {
      if (!this.validate_form) {
        return;
      }
      this.checkMember();
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
    <form @submit.prevent="handleSearch">
      <div class="row">
        <div class="col-md-3 col-sm-5 col-12 mb-2">
          <input type="text" class="form-control form-control-sm" placeholder="Tên phòng ban..." v-model="search.name">
        </div>
        <div class="col-md-3 col-sm-5 col-12 mb-2">
          <m-select
            :size="'sm'"
            text="--Tìm theo trưởng phòng--"
            url="user/search-user"
            :statusReset="false"
            @changeValue="getUserSearch"
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
        <template v-if="list">
          <li v-for="(item, index) in list.data" :key="index" class="col-md-3 col-sm-4 col-6">
            <div class="info bg-fff">
              <p><i class="fas fa-folder"></i>&nbsp; <b>{{ item.name }}</b></p>
              <p style="font-size: 12px; margin-bottom: 0px">
                <b>Người tạo: </b>{{item.created_by.fullname || item.created_by.username}} <br>
                <b>Trưởng phòng: </b>{{item.leader.fullname || item.leader.username}} <br>
                <b>Tạo lúc: </b>{{item.created_at}} <br>
                <b>Thành viên:</b> <br>
              </p>
              <ul class="avt-mem d-flex justify-content-start mt-1">
                <li v-for="(mem, _index) in item.members" :key="_index">
                  <img :src="mem.avatar ? mem.avatar : $root.avatar_default" alt="" v-if="_index <= 6">
                  <span v-if="_index == 7">{{_index}}</span>
                </li>
              </ul>
              <div class="text-right" v-if="$root.isManager()">
                <span
                  class="text-danger"
                  @click="getDepartment(item)"
                  data-toggle="modal"
                  data-target="#department_modal_delete"
                >
                  <b>Xóa</b>
                </span>
                <span
                  class="text-info"
                  @click="getDepartment(item)"
                  data-toggle="modal"
                  data-target="#department_modal_add"
                >
                  <b>Sửa</b>
                </span>
              </div>
            </div>
          </li>
        </template>
      </ul>
    </div>

    <div class="text-center mt-3" v-if="last_page > 1">
      <m-pagination :page="current_page" :last-page="last_page" @change="changePage" />
    </div>

    <div class="modal fade" id="department_modal_add">
      <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">Phòng ban</h4>
            <button type="button" class="close" data-dismiss="modal">&times;</button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="onSubmit">
              <div class="row">
                <div class="col-md-12 col-sm-12 col-12">
                  <div class="form-group">
                    <label><b>Tên phòng ban <span class="text-danger">*</span></b></label>
                    <input type="text" class="form-control form-control-sm" v-model="department.name">
                    <div class="text-danger font-italic error">{{error.name}}</div>
                  </div>
                </div>

                <div class="col-md-12 col-sm-12 col-12">
                  <div class="form-group">
                    <label><b>Trưởng phòng ban <span class="text-danger">*</span></b></label>
                    <m-select
                      :size="'sm'"
                      :text="text_select_leader"
                      url="user/search-user-not-deparment" 
                      :statusReset="reset_select_comp"
                      @changeValue="getLeader"
                      :variable="{first: 'fullname', second: 'username'}"
                    />
                    <div class="text-danger font-italic error">{{error.leader}}</div>
                  </div>
                </div>

                <div class="col-md-12 col-sm-12 col-12">
                  <div class="form-group">
                    <label><b>Thành viên <span class="text-danger">*</span></b></label>
                    <m-select
                      :size="'sm'"
                      text="-- Tìm họ tên hoặc tên đăng nhập --"
                      url="user/search-user-not-deparment" 
                      :statusReset="reset_select_comp"
                      @changeValue="getMember"
                      :variable="{first: 'fullname', second: 'username'}"
                    />
                    <div class="text-danger font-italic error">{{error.members}}</div>
                    <div class="members">
                      <ul>
                        <li v-for="(item, index) in members_show" :key="index">
                          <img :src="item.avatar ? item.avatar : $root.avatar_default" alt="">
                          {{item.fullname || item.username}}
                          <i class="fas fa-times icon-remove" @click="removeMember(index)"></i>
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
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
    <m-loading v-if="loading_update_add" :title="department.id != null ? 'Đang cập nhật phòng ban' : 'Đang thêm phòng ban'" :full="true" />
  </div>
</template>