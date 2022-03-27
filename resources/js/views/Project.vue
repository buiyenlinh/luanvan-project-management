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
      },
      file_show: '',
      file_updated: '',
      loading_delete_file: false,
      loading_take_project: false,
      response_finish: '',
      loading_finish_project: false
    }
  },
  methods: {
    onSubmit() {
      this.checkName();
      this.checkEndTime();
      this.checkStartTime();
      if (this.$root.auth.role.level < 3) {
        this.checkManager();
      } else {
        this.project.manager = this.$root.auth.id;
      }
      if (this.project.start_time > this.project.end_time) {
        this.$root.showError('Ngày bắt đầu phải trước ngày kết thức');
        this.loading_add = false;
        return;
      }
      if (this.project.name && this.project.start_time && this.project.end_time && this.project.manager && (this.project.active == 1 | this.project.active == 0)) {
        this.loading_add = true;
        let formData = new FormData();
        formData.append('active', this.project.active);
        formData.append('file', this.project.file);
        formData.append('describe', this.project.describe);

        if (this.project.id > 0) { // Cập nhật
          formData.append('id', this.project.id);
          this.$root.api.post('project/update', formData).then(res => {
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
          formData.append('name', this.project.name);
          formData.append('start_time', this.project.start_time);
          formData.append('end_time', this.project.end_time);
          formData.append('created_by', this.$root.auth.id);
          formData.append('manager', this.project.manager);
          this.$root.api.post('project/add', formData).then(res => {
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
    handleCloseModal() {
      this.validate_form = false;
      this.project = {
        id: null,
        name: '',
        describe: '',
        start_time: '',
        end_time: '',
        active: 1,
        file: '',
        manager: null,
        created_by: null
      }
      this.error = {
        name: '',
        start_time: '',
        end_time: '',
        manager: '',
        created_by: ''
      }
      this.file_show = '';
      this.file_updated = '';

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
      } else if (this.project.end_time && this.project.start_time >= this.project.end_time) {
        this.error.start_time = 'Thời gian bắt đầu phải trước thời gian kết thúc';
      } else {
        this.error.start_time = '';
        if (this.error.end_time == 'Thời gian kết thúc phải sau thời gian bắt đầu') 
          this.error.end_time = '';
      }
    },
    checkEndTime() {
      if (this.project.end_time == '') {
        this.error.end_time = 'Thời gian kết thúc là bắt buộc';
      } else if (this.project.start_time && this.project.start_time >= this.project.end_time) {
        this.error.end_time = 'Thời gian kết thúc phải sau thời gian bắt đầu';
      } else {
        this.error.end_time = '';
        if (this.error.start_time == 'Thời gian bắt đầu phải trước thời gian kết thúc')
          this.error.start_time = '';
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
    removeManager() {
      this.project.manager = null;
      this.text_select =  '-- Tìm quản lý --';
      this.reset_select_comp = !this.reset_select_comp;
    },
    getProjectUpdate(_project) {
      this.project = _.clone(_project);
      this.text_select = _project.manager.fullname || _project.manager.username;
      this.file_updated = _project.file;
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
    handleGetFile(_file) {
      this.project.file = _file.target.files[0];
      this.file_show = _file.target.files[0].name;
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
    },
    deleteFile() {
      this.loading_delete_file = true;
      this.$root.api.delete(`project/delete-file/${this.project.id}`).then(res => {
        this.loading_delete_file = false;
        if (res.data.status == 'OK') {
          this.$notify(res.data.message, 'success');
          this.getList();
          this.project.file = '';
          this.file_updated = '';
        } else {
          this.$root.showError(res.data.error);
        }
      }).catch (err => {
        this.loading_delete_file = false;
        this.$root.showError(err);
      })
    },
    removeFile() {
      $('#project_file').val('');
      this.file_show = '';
      this.project.file = '';
    },
    handleTakeProject(_project) {
      this.loading_take_project = true;
      this.$root.api.post(`project/take-project/${_project.id}`).then(res => {
        this.loading_take_project = false;
        if (res.data.status == 'OK') {
          this.$notify(res.data.message, 'success');
          this.getList();
        } else {
          this.$root.showError(res.data.error);
        }
      }).catch (err => {
        this.loading_take_project = false;
        this.$root.showError(err);
      }) 
    },
    handleFinishProject() {
      this.loading_finish_project = true;
      this.$root.api.post(`project/finish-project/${this.project.id}`, {
        'content': this.response_finish
      }).then(res => {
        this.loading_finish_project = false;
        if (res.data.status == 'OK') {
          $('#project_modal_finish').modal('hide');
          this.$notify(res.data.message, 'success');
          this.getList();
        } else {
          this.$root.showError(res.data.error);
        }
      }).catch (err => {
        this.loading_finish_project = false;
        this.$root.showError(err);
      }) 
    },
    setProjectName(_name) {
      this.project.name = _name;
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
    if (this.$route.query.name) {
      this.search.name = this.$route.query.name;
    }
    
    $(document).on('hidden.bs.modal', '#project_modal_add, #project_modal_delete, #project_modal_finish, #project_modal_details', () => {
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
        <div class="col-md-3 col-sm-5 col-12 mb-2" v-if="$root.isAdmin()">
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
        <div :class="[$root.auth.role.level == 3 ? 'col-md-7' : 'col-md-4' ,'col-sm-12 col-6 text-right mb-2']" v-if="$root.isManager()">
          <button class="btn btn-info btn-sm" data-toggle="modal" data-target="#project_modal_add">Thêm</button>
        </div>
      </div>
    </form>
    <div class="list">
      <div class="card">
        <div class="card-header bg-info text-white">Danh sách dự án</div>
        <div class="table-responsive">
          <table class="table table-bordered table-stripped mb-0">
            <thead>
              <tr>
                <td><b>STT</b></td>
                <td><b>Tên</b></td>
                <td><b>Quản lý</b></td>
                <td><b>Thống kê</b></td>
                <td><b>Trạng thái</b></td>
                <td><b>Bắt đầu</b></td>
                <td><b>Hoàn thành</b></td>
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
              <template v-else-if="list && list.data.length > 0">
                <tr v-for="(item, index) in list.data" :key="index">
                  <td>{{ index + 1 }}</td>
                  <td>{{ item.name }}</td>
                  <td>{{ item.manager.fullname || item.manager.username }}</td>
                  <td>
                    <div v-if="item.task_statistic" style="font-size: 11px">
                      <b>{{ item.task_statistic.finish }}/ {{ item.task_statistic.total }}</b> Hoàn thành 
                      <b>{{ item.task_statistic.overdue }}</b> quá hạn
                    </div>
                    <div class="progress" style="height: 0.5em" v-if="item && item.task_statistic.total != 0">
                      <div v-if="item.task_statistic.finish_percent >= 80" class="progress-bar bg-success" 
                        :style="'width:' + item.task_statistic.finish_percent + '%'"></div>
                  
                      <div v-else-if="item.task_statistic.finish_percent >= 50" class="progress-bar bg-warning" 
                        :style="'width:' + item.task_statistic.finish_percent + '%'"></div>
                   
                      <div v-else class="progress-bar bg-danger" 
                        :style="'width:' + item.task_statistic.finish_percent + '%'"></div>
                    </div>
                  </td>
                  <td>
                    <span v-if="item.active == 0" class="badge badge-danger">Khóa</span>
                    <template v-else>
                      <span v-if="item.status.status == 9">
                        <b v-if="item.delay_time == 0" class="badge badge-success">Hoàn thành đúng hạn</b>
                        <b v-else class="badge badge-warning">Hoàn thành trễ {{ item.delay_time }} ngày</b> 
                      </span>
                      
                      <template v-else>
                        <span class="badge badge-success">{{ $root.getStatusTaskName(item.status.status) }}</span>
                        <span :class="['badge', $root.checkDeadline(item) == 'Chưa tới hạn' ? 'badge-info' : 'badge-danger']">
                          {{ $root.checkDeadline(item) }}
                        </span>
                      </template>
                    </template>
                  </td>
                  <td style="font-size: 13px">{{ item.start_time }}</td>
                  <td style="font-size: 13px">Trước {{ item.end_time }}</td>
                  <td style="font-size: 13px">{{ item.created_at }}</td>
                  <td>
                    <button class="mb-1 btn btn-secondary btn-sm"
                      @click="getProjectUpdate(item)"
                      data-toggle="modal"
                      data-target="#project_modal_details"
                    >Xem</button>
                    <router-link :to="{name: 'task', params: { 'id': item.id }}" v-if="item.status.status != 0">
                      <button class="mb-1 btn btn-sm btn-dark">Công việc</button>
                    </router-link>
                    <template v-if="($root.auth.id == item.manager.id || $root.auth.id == item.created_by.id) && item.status.status != 9">
                      <button class="mb-1 btn btn-info btn-sm"
                        @click="getProjectUpdate(item)"
                        data-toggle="modal"
                        data-target="#project_modal_add"
                      >Sửa</button>
                      <button class="mb-1 btn btn-danger btn-sm"
                        @click="getProjectUpdate(item)"
                        data-toggle="modal"
                        data-target="#project_modal_delete"
                      >Xóa</button>
                      
                      <template v-if="item.active == 1 && $root.auth.id == item.manager.id">
                        <button class="mb-1 btn btn-dark btn-sm"
                          v-if="item.status && item.status.status == 0 && $root.auth.id == item.manager.id"
                          @click="handleTakeProject(item)"
                        >Tiếp nhận</button>
                        <button class="mb-1 btn btn-success btn-sm" 
                          v-if="item.status && item.status.status == 1"
                          @click="getProjectUpdate(item)"
                          data-toggle="modal"
                          data-target="#project_modal_finish"
                        >Hoàn thành</button>
                      </template>
                    </template>
                    <template v-if="$root.isAdmin() || $root.auth.id == item.manager.id">
                      <router-link :to="{ name: 'chart', params: { name: 'du-an', id: item.id } }" class="btn btn-info btn-sm mb-1">Gantt</router-link>
                    </template>
                  </td>
                </tr>
              </template>
              <tr v-else ><td colspan="1000" align="center">Không có dự án</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <div class="text-center mt-3" v-if="last_page > 1">
      <m-pagination :page="current_page" :last-page="last_page" @change="changePage" />
    </div>

    <div class="modal fade" id="project_modal_add">
      <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">Dự án</h4>
            <button type="button" class="close" data-dismiss="modal">&times;</button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="onSubmit">
              <div class="row">
                <div class="col-md-12 col-sm-12 col-12">
                  <div class="form-group">
                    <label><b>Tên dự án <span class="text-danger">*</span></b></label>
                    <input v-if="project.id" type="text" class="form-control form-control-sm" v-model="project.name" disabled>
                    <m-input url="project/get-name" :text="project.name" variable="name" @changeValue="setProjectName" v-else />
                    
                    <div class="text-danger font-italic error">{{error.name}}</div>
                  </div>
                </div>
                <div class="col-md-12 col-sm-12 col-12" v-if="!project.id">
                  <div class="form-group">
                    <label><b>Thời gian bắt đầu <span class="text-danger">*</span></b></label>
                    <input type="date" class="form-control form-control-sm" v-model="project.start_time">
                    <div class="text-danger font-italic error">{{error.start_time}}</div>
                  </div>
                </div>
                <div class="col-md-12 col-sm-12 col-12" v-if="!project.id">
                  <div class="form-group">
                    <label><b>Hoàn thành trước <span class="text-danger">*</span></b></label>
                    <input type="date" class="form-control form-control-sm" v-model="project.end_time">
                    <div class="text-danger font-italic error">{{error.end_time}}</div>
                  </div>
                </div>
                <div class="col-md-12 col-sm-12 col-12" v-if="$root.isAdmin() && !project.id">
                  <div class="form-group">
                    <label><b>Quản lý <span class="text-danger">*</span></b></label>
                    <m-select
                      :size="'sm'"
                      :text="text_select"
                      url="user/search-manager"
                      :statusReset="reset_select_comp"
                      @changeValue="getManager"
                      @remove="removeManager"
                      :variable="{first: 'fullname', second: 'username'}"
                    />
                    <div class="text-danger font-italic error">{{error.manager}}</div>
                  </div>
                </div>
                <div class="col-md-12 col-sm-12 col-12">
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

                <div class="col-md-12 col-sm-12 col-12">
                  <div class="form-group">
                    <label><b>Đính kèm</b></label>
                    <button type="button" class="btn btn-info btn-sm" @click="$refs.ref_file.click()">Chọn tập tin</button>
                    <input
                      id="project_file"
                      type="file"
                      ref="ref_file"
                      style="display: none"
                      @change="handleGetFile"
                    />
                    <div v-if="file_show">
                      <span>File được chọn: {{ file_show }}</span>
                      <i class="fas fa-times text-danger" style="cursor: pointer" @click="removeFile"></i>
                    </div>
                    <div v-if="file_updated">
                      <a target="_blank" :href="file_updated">Xem tệp đã chọn</a> &nbsp;
                      <i class="fas fa-times text-danger" style="cursor: pointer" @click="deleteFile"></i>
                    </div>
                  </div>
                </div>
              </div>
              <div class="modal-footer">
                <button type="submit" class="btn btn-info btn-sm">
                  {{ project.id ? 'Cập nhật' : 'Thêm'}}
                </button>
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Đóng</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="project_modal_details">
      <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">Thông tin dự án</h4>
            <button type="button" class="close" data-dismiss="modal">&times;</button>
          </div>
          <div class="modal-body">
            <div class="form-group">
              <b class="text-info">Tên: {{ project.name }}</b>
            </div>

            <div class="form-group" v-if="project.manager">
              <b>Quản lý dự án: </b> <span>{{ project.manager.fullname || project.manager.username }}</span>
            </div>

            <div class="form-group">
              <b>Thời gian bắt đầu: </b> <span>{{ project.start_time }}</span>
            </div>

            <div class="form-group">
              <b>Hoàn thành trước: </b> <span>{{ project.end_time }}</span>
            </div>

            <div class="form-group">
              <b>Trạng thái: </b> 
              <span :class="['badge', project.active ? 'badge-success' : 'badge-danger']">{{ project.active ? 'Kích hoạt' : 'Khóa'}}</span>
            </div>

            <div class="form-group" v-if="project.describe">
              <b>Mô tả: </b> <span>{{ project.describe }}</span>
            </div>

            <div class="form-group" v-if="file_updated">
              <b>Tệp đính kèm: </b>
              <a target="_blank" :href="file_updated">Xem tệp</a>
            </div>

          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Đóng</button>
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
            <div class="modal-body" v-if="$root.isManager()">
              <div v-if="project.name" class="d-flex justify-content-start">
                <i class="fas fa-exclamation-triangle text-danger icon-warm-delete"></i>
                <span>
                  Xóa dự án <b>{{ project.name }}</b> thì tất cả trạng thái của dự án sẽ bị xóa.
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

    <div class="modal fade" id="project_modal_finish">
      <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">Hoàn thành công việc</h4>
            <button type="button" class="close" data-dismiss="modal">&times;</button>
          </div>
          <div class="modal-body">
            <div v-if="project" class="row">
              <div class="col-md-12 col-sm-12 col-12">
                <div class="form-group">
                  <label><b>Tên dự án <span class="text-danger">*</span></b></label>
                  <input type="text" class="form-control form-control-sm" disabled v-model="project.name">
                </div>
              </div>

              <div class="col-md-12 col-sm-12 col-12">
                <div class="form-group">
                  <label><b>Phản hồi </b></label>
                  <textarea class="form-control" rows="5" v-model="response_finish"></textarea>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-success btn-sm" @click="handleFinishProject">Hoàn thành</button>
            <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Đóng</button>
          </div>
        </div>
      </div>
    </div>

    <m-loading v-if="loading_add" :title="this.project.id != null ? 'Đang cập nhật dự án' : 'Đang thêm dự án'" :full="true" />
    <m-loading v-if="loading_delete" title="Đang xóa dự án" :full="true" />
    <m-loading v-if="loading_delete_file" title="Đang xóa tệp đính kèm của dự án" :full="true" />
    <m-loading v-if="loading_take_project" title="Đang tiếp nhận dự án" :full="true" />
    <m-loading v-if="loading_finish_project" title="Đang thực hiện hoàn thành dự án" :full="true" />
  </div>
</template>