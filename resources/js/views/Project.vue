<script>
export default {
  data() {
    return {
      search: {
        keyword: ''
      },
      validate_form: false,
      project: null,
      error: null,
      list: null,
      loading_add: false,
      loading_delete: false,
      loading_list: false,
      current_page: 1,
			last_page: 1,
      text_select: '-- Tìm quản lý --',
      reset_select_comp: false,
      search: {
        name: '',
        manager: -1
      }
    }
  },
  methods: {
    onSubmit() {
      this.checkName();
      this.checkEndTime();
      this.checkStartTime();
      this.checkManager();
      if (this.project.start_time > this.project.end_time) {
        this.$root.showError('Ngày bắt đầu phải trước ngày kết thức');
        this.loading_add = false;
        return;
      }
      if (this.project.name && this.project.start_time && this.project.end_time && this.project.manager && (this.project.active == 1 | this.project.active == 0)) {
        this.loading_add = true;
        if (this.project.id > 0) { // Cập nhật
          this.$root.api.post('project/update', this.project).then(res => {
            if (res.data.status == 'OK') {
              this.$notify(res.data.message, 'success');
              $('#project_modal_add').modal('hide');
              this.getList();
            } else {
              this.$root.showError(res.data.error);
            }
            this.loading_add = false;
          }).catch(err => {
            this.loading_add = false;
            this.$root.showError(err);
          })
        } else { // Thêm mới
          this.project.created_by = this.$root.auth.id;
          this.$root.api.post('project/add', this.project).then(res => {
            this.loading_add = false;
            if (res.data.status == "OK") {
              this.$notify(res.data.message, 'success');
              $('#project_modal_add').modal('hide');
              this.getList();
            } else {
              this.$root.showError(res.data.error);
            }
          }).catch(err => {
            this.loading_add = false;
            this.$root.showError(err);
          })
        }
        
      }
    },
    handleSearch() {
      console.log("handleSearch")
    },
    handleCloseModal() {
      this.validate_form = false;
      this.project = {
        id: null,
        name: '',
        describe: '',
        start_time: '',
        end_time: '',
        delay_time: 0,
        active: 1,
        manager: null,
        created_by: null
      }
      this.error = {
        name: '',
        start_time: '',
        end_time: '',
        delay_time: '',
        manager: '',
        created_by: ''
      }
      this.text_select = '-- Tìm quản lý --';
      this.reset_select_comp = !this.reset_select_comp;
      setTimeout(() => {
        this.validate_form = true;
      }, 300);
    },
    checkName() {
      if (this.project.name == '') {
        this.error.name = 'Tên dự án là bắt buộc';
      } else {
        this.error.name = '';
      }
    },
    checkStartTime() {
      if (this.project.start_time == '') {
        this.error.start_time = 'Thời gian bắt đầu là bắt buộc';
      } else {
        this.error.start_time = '';
      }
    },
    checkEndTime() {
      if (this.project.end_time == '') {
        this.error.end_time = 'Thời gian kết thúc là bắt buộc';
      } else {
        this.error.end_time = '';
      }
    },
    checkManager() {
      if (this.project.manager == '' || this.project.manager == null) {
        this.error.manager = 'Quản lý dự án là bắt buộc';
      } else {
        this.error.manager = '';
      }
    },
    getManager(_user) {
      this.project.manager = _user.id;  
    },
    getProjectUpdate(_project) {
      this.project = _.clone(_project);
      this.project.manager = _project.manager.id;
      this.project.created_by = _project.created_by.id;
      this.text_select = _project.manager.fullname || _project.manager.username;
    },
    getList() {
      this.loading_list = true;
      this.$root.api.get('project/list',{
        params: {
          page: this.current_page,
          name: this.search.name,
          manager: this.search.manager,
        }
      }).then(res => {
        if (res.data.status == "OK") {
          this.list = res.data.data;
          this.last_page = res.data.data.meta.last_page;
          if (this.current_page > 1 && !this.list.data.length) {
            this.$router.replace({
							name: 'project',
							query: {
								page: this.current_page - 1
							}
						})
						this.changePage(this.current_page - 1);
          }
        }
        this.loading_list = false;
      }).catch(err => {
        this.$root.showError(err);
        this.loading_list = false;
      })
    },
    changePage(page) {
			this.current_page = page;
			this.getList();
		},
    onSubmitDelete() {
      this.loading_delete = true;
      this.$root.api.delete(`project/delete/${this.project.id}`).then(res => {
        if (res.data.status == "OK") {
          this.$notify(res.data.message, 'success');
          this.getList();
          $('#project_modal_delete').modal('hide');
        } else {
          this.$root.showError(res.data.error);
        }
        this.loading_delete = false;
      }).catch(err => {
        this.loading_delete = false;
        this.$root.showError(err);
      })
    },
    getUserSearch(_manager) {
      this.search.manager = _manager.id;
    },
    handleSearch() {
      this.last_page = 1;
      this.changePage(1);
    }
  },
  created() {
    this.handleCloseModal();
  },
  watch: {
    'project.name'() {
      if (!this.validate_form) {
        return;
      }
      this.checkName();
    },
    'project.start_time'() {
      if (!this.validate_form) {
        return;
      }
      this.checkStartTime();
    },
    'project.end_time'() {
      if (!this.validate_form) {
        return;
      }
      this.checkEndTime();
    },
    'project.manager'() {
      if (!this.validate_form) {
        return;
      }
      this.checkManager();
    }
  },
  mounted() {
    this.current_page = parseInt(this.$route.query.page || 1);
    $(document).on('hidden.bs.modal', '#project_modal_add, #project_modal_delete', () => {
      this.handleCloseModal();
    });

    this.getList();
  }
}
</script>

<template>
  <div id="project">
    <form @submit.prevent="handleSearch">
      <div class="row">
        <div class="col-md-3 col-sm-5 col-12 mb-2">
          <input type="text" class="form-control form-control-sm" placeholder="Tên dự án..." v-model="search.name">
        </div>
        <div class="col-md-3 col-sm-5 col-12 mb-2">
          <m-select
            :size="'sm'"
            text="--Tìm theo quản lý--"
            url="user/search-manager"
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
        <div class="col-md-4 col-sm-12 col-6 text-right mb-2" v-if="$root.isManager()">
          <button class="btn btn-info btn-sm" data-toggle="modal" data-target="#project_modal_add">Thêm</button>
        </div>
      </div>
    </form>
    <div class="list">
        <div class="text-center" v-if="loading_list">
          <m-spinner/>
        </div>
        <ul v-else class="row">
          <template v-if="list && list.data.length > 0">
            <li v-for="(item, index) in list.data" :key="index" class="col-md-3 col-sm-4 col-12 mb-2">
              <div :class="['info', !item.active ? 'block': 'bg-fff']">
                <span v-if="!item.active" class="text-danger block-text">Khóa</span>
                <p><i class="fas fa-folder"></i>&nbsp; <b>{{ item.name }}</b></p>
                <p style="font-size: 12px; margin-bottom: 0px">
                  <b>Người tạo: </b>{{item.created_by.fullname || item.created_by.username}} <br>
                  <b>Quản lý: </b>{{item.manager.fullname || item.manager.username}} <br>
                  <b>Tạo lúc: </b>{{item.created_at}}
                </p>
                <div class="text-right" v-if="$root.isManager()">
                  <span
                    class="text-danger"
                    @click="getProjectUpdate(item)"
                    data-toggle="modal"
                    data-target="#project_modal_delete"
                  >
                    <b>Xóa</b>
                  </span>
                  <span
                    class="text-info"
                    @click="getProjectUpdate(item)"
                    data-toggle="modal"
                    data-target="#project_modal_add"
                  >
                    <b>Sửa</b>
                  </span>
                </div>
              </div>
            </li>
          </template>
          <li class="col-md-12" v-else>Bạn chưa có dự án</li>
        </ul>
    </div>
    <div class="text-center mt-3" v-if="last_page > 1">
      <m-pagination :page="current_page" :last-page="last_page" @change="changePage" />
    </div>

    <div class="modal fade" id="project_modal_add">
      <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">Dự án</h4>
            <button type="button" class="close" data-dismiss="modal">&times;</button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="onSubmit">
              <div class="row">
                <div class="col-md-6 col-sm-12 col-12">
                  <div class="form-group">
                    <label><b>Tên dự án <span class="text-danger">*</span></b></label>
                    <input type="text" class="form-control form-control-sm" v-model="project.name">
                    <div class="text-danger font-italic error">{{error.name}}</div>
                  </div>
                </div>
                <div class="col-md-6 col-sm-12 col-12">
                  <div class="form-group">
                    <label><b>Thời gian bắt đầu <span class="text-danger">*</span></b></label>
                    <input type="date" class="form-control form-control-sm" v-model="project.start_time">
                    <div class="text-danger font-italic error">{{error.start_time}}</div>
                  </div>
                </div>
                <div class="col-md-6 col-sm-12 col-12">
                  <div class="form-group">
                    <label><b>Thời gian kết thức <span class="text-danger">*</span></b></label>
                    <input type="date" class="form-control form-control-sm" v-model="project.end_time">
                    <div class="text-danger font-italic error">{{error.end_time}}</div>
                  </div>
                </div>
                <div class="col-md-6 col-sm-12 col-12">
                  <label><b>Thời gian trì hoãn</b></label>
                  <div class="input-group input-group-sm mb-3">
                    <input type="number" class="form-control form-control-sm" v-model="project.delay_time">
                    <div class="input-group-append">
                      <span class="input-group-text">( Ngày )</span>
                    </div>
                  </div>
                </div>
                <div class="col-md-6 col-sm-12 col-12">
                  <div class="form-group">
                    <label><b>Quản lý <span class="text-danger">*</span></b></label>
                    <m-select
                      :size="'sm'"
                      :text="text_select"
                      url="user/search-manager"
                      :statusReset="reset_select_comp"
                      @changeValue="getManager"
                      :variable="{first: 'fullname', second: 'username'}"
                    />
                    <div class="text-danger font-italic error">{{error.manager}}</div>
                  </div>
                </div>
                <div class="col-md-6 col-sm-12 col-12">
                  <div class="form-group">
                    <label><b>Trạng thái <span class="text-danger">*</span></b></label><br>
                    <div class="form-check-inline">
                      <label class="form-check-label">
                        <input type="radio" class="form-check-input" v-model.number="project.active" value="1">Kích hoạt
                      </label>
                    </div>
                    <div class="form-check-inline">
                      <label class="form-check-label">
                        <input type="radio" class="form-check-input" v-model.number="project.active" value="0">Khóa
                      </label>
                    </div>
                    <div class="error text-danger font-italic">{{ error.active }}</div>
                  </div>
                </div>
                <div class="col-md-12 col-sm-12 col-12">
                  <div class="form-group">
                    <label><b>Mô tả</b></label><br>
                    <textarea type="text" class="form-control form-control-sm" rows="5" v-model="project.describe"></textarea>
                  </div>
                </div>
              </div>
              <div class="modal-footer">
                <button type="submit" class="btn btn-info btn-sm">
                  {{ project.id ? 'Cập nhật' : 'Thêm'}}
                </button>
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="project_modal_delete">
      <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
          <form @submit.prevent="onSubmitDelete">
            <div class="modal-header">
              <h4 class="modal-title">Xóa dự án</h4>
              <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" v-if="$root.isAdmin()">
              <div v-if="project.name" class="d-flex justify-content-start">
                <i class="fas fa-exclamation-triangle text-danger icon-warm-delete"></i>
                <span>
                  Xóa dự án <b>{{ project.name }}</b> thì tất cả các công việc liên quan sẽ bị xóa.
                  <br>
                  Bạn có muốn xóa?
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
    <m-loading v-if="loading_add" :title="this.project.id != null ? 'Đang cập nhật dự án' : 'Đang thêm dự án'" :full="true" />
  </div>
</template>