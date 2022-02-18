<script>
export default {
  data() {
    return {
      project_id: '',
      project: null,
      search: {
        name: '',
        department_id: ''
      },
      list: null,
      loading_list: false,
      loading_add: false,
      loading_delete: false,
      task: null,
      error: null,
      validate_form: false,
      select_department: {
        text: '--- Tìm phòng ban ---',
        status: false
      },
      select_label: {
        text: '--- Tìm tên nhãn ---',
        status: false
      },
      select_pre_task: {
        text: '--- Tìm tên công việc ---',
        status: false
      },
      file_show: '',
      file_updated: '',
      pre_task_ids_show: [],
      pre_task_id_check: [],
      loading_delete_file: false
    }
  },
  methods: {
    getList() {
      this.loading_list = true;
      this.$root.api.get(`project/${this.project_id}/task/list`, {
        params: this.search
      }).then(res => {
        if (res.data.status == "OK") {
          this.list = res.data.data.data;
          this.loading_list = false;
        } else {
          this.$root.showError(res.data.error);
        }
      }).catch(err => {
        this.$root.showError(err);
        this.loading_list = false;
      })
    },
    onSubmit() {
      this.checkName();
      this.checkStartTime();
      this.checkEndTime();
      this.checkDepartment();
      let check = false;
      if (this.pre_task_ids_show.length > 0) {
        for (let i in this.pre_task_ids_show) {
          if (!this.checkTimePreTask(this.pre_task_ids_show[i], this.task)) {
            check = true;
          }
        }
      }

      if (!check && this.error.name == '' && this.error.start_time == '' && this.error.end_time == '' && this.error.department == '' && this.error.pre_task_ids == '') {
        let formData = new FormData();
        formData.append('name', this.task.name);
        formData.append('describe', this.task.describe);
        formData.append('result', this.task.result);
        formData.append('label_id', this.task.label_id);
        formData.append('project_id', this.task.project_id);
        formData.append('department_id', this.task.department_id);
        formData.append('file', this.task.file);
        formData.append('start_time', this.task.start_time);
        formData.append('end_time', this.task.end_time);

        if (this.pre_task_ids_show.length > 0) {
          for (let i in this.pre_task_ids_show) {
            formData.append('pre_task_ids[]', this.pre_task_ids_show[i].id);
          }
        }
        
        if (this.task.id != null) {
          formData.append('id', this.task.id);
          this.loading_add = true;
          this.$root.api.post(`project/${this.project_id}/task/update`, formData).then(res => {
            this.loading_add = false;
            if (res.data.status == 'OK') {
              this.$notify(res.data.message, 'success');
              $('#task_modal_add_update').modal('hide');
              this.getList();
            } else {
              this.$root.showError(res.data.error);
            }
          }).catch (err => {
            this.$root.showError(err);
            this.loading_add = false;
          })
        } else {
          this.loading_add = true;
          this.$root.api.post(`project/${this.project_id}/task/add`, formData).then(res => {
            this.loading_add = false;
            if (res.data.status == 'OK') {
              this.$notify(res.data.message, 'success');
              $('#task_modal_add_update').modal('hide');
              this.getList();
            } else {
              this.$root.showError(res.data.error);
            }
          }).catch (err => {
            this.$root.showError(err);
            this.loading_add = false;
          })
        }
      }
    },
    onSubmitDelete() {
      this.loading_delete = true;
      this.$root.api.delete(`project/${this.project_id}/task/delete/${this.task.id}`).then(res => {
        this.loading_delete = false;
        if (res.data.status == "OK") {
          this.$notify(res.data.message, 'success');
          $('#task_modal_delete').modal('hide');
          this.getList();
        } else {
          this.$root.showError(res.data.error);
        }
      }).catch (err => {
        this.$root.showError(err);
        this.loading_delete = false;
      })
    },
    getDepartmentSearch(_department) {
      this.search.department_id = _department.id;
    },
    removeDepartmentSearch() {
      this.search.department_id = '';
    },
    closeModal() {
      this.validate_form = false;
      this.task = {
        id: null,
        name: '',
        describe: '',
        result: '',
        start_time: '',
        end_time: '',
        label_id: 0,
        pre_task_ids: [],
        department_id: null,
        file: ''
      };

      this.error = {
        name: '',
        describe: '',
        department: '',
        result: '',
        start_time: '',
        end_time: '',
        label_id: '',
        project_id: '',
        pre_task_ids: ''
      }

      this.file_show = '';
      this.file_updated = '';
      this.pre_task_ids_show = [];
      this.pre_task_id_check = [];

      this.select_department = {
        text: '--- Tìm phòng ban ---',
        status: !this.select_department.status
      }

      this.select_label = {
        text: '--- Tìm tên nhãn ---',
        status: !this.select_label.status
      }

      this.select_pre_task = {
        text: '--- Tìm tên công việc ---',
        status: !this.select_pre_task.status
      }

      setTimeout(() => {
        this.validate_form = true;
      }, 300);
    },
    getDepartment(_department) {
      this.task.department_id= _department.id;
      this.select_department.text = _department.name;
    },
    removeDepartment() {
      this.task.department_id= null;
      this.select_department.text = '--- Tìm phòng ban ---';
    },
    getLabel(_label) {
      this.task.label_id= _label.id;
      this.select_label.text = _label.name;
    },
    removeLabel() {
      this.task.label_id= 0;
      this.select_label.text = '--- Tìm tên nhãn ---';
    },
    handleGetFile(_file) {
      this.task.file = _file.target.files[0];
      this.file_show = _file.target.files[0].name;
    },
    deleteFile() {
      this.loading_delete_file = true;
      this.$root.api.delete(`project/${this.project_id}/task/delete-file/${this.task.id}`).then(res => {
        this.loading_delete_file = false;
        if (res.data.status == "OK") {
          this.$notify(res.data.message, 'success');
          this.task.file = '';
          this.file_updated = '';
          this.getList();
        } else {
          this.$root.showError(res.data.error);
        }
      }).catch (err => {
        this.$root.showError(err);
        this.loading_delete_file = false;
      })
    },
    removeFile() {
      this.file_show = '';
      this.task.file = '';
    },
    checkTimePreTask(_pre_task, _task) {
      let start_time_task = new Date(_task.start_time).getTime(); // đơn vị mili giây
      if (start_time_task <= _pre_task.end_time * 1000) { // Dvi giây nên * 1000
        this.error.start_time = 'Thời gian bắt đầu công việc không phù hợp với công việc tiên quyết "' + _pre_task.name + '"';
        this.select_pre_task.text = '--- Tìm tên công việc --- ';
        return false;
      }
      return true;
    },
    getPreTask(_pre_task) {
      if (this.checkTimePreTask(_pre_task, this.task)) {
        if (!this.pre_task_id_check.includes(_pre_task.id) && _pre_task.id != this.task.id) {
          this.pre_task_id_check.push(_pre_task.id);
          this.pre_task_ids_show.push(_pre_task);
        } else {
          this.select_pre_task.text = '';
          this.select_pre_task.text = '--- Tìm tên công việc --- ';
        }
      }
      
    },
    removePreTask() {
      this.select_pre_task.text = '--- Tìm tên công việc ---';
    },
    removePreTaskId(_index) {
      this.pre_task_ids_show.splice(_index, 1);
    },
    checkName() {
      if (this.task.name == '') {
        this.error.name = 'Tên công việc là bắt buộc';
      } else {
        this.error.name = '';
      }
    },
    checkStartTime() {
      if (this.task.start_time == '') {
        this.error.start_time = 'Thời gian bắt đầu là bắt buộc';
      } else if (this.task.start_time < this.project.start_time || this.task.start_time > this.project.end_time) {
        this.error.start_time = 'Thời gian bắt đầu công việc không phù hợp với thời gian của dự án'
      } else if (this.task.end_time != '' && this.task.start_time > this.task.end_time) {
        this.error.start_time = 'Thời gian bắt đầu phải nhỏ hơn thời gian kết thúc'
      } else {
        if (this.error.end_time == 'Thời gian kết thúc phải lớn hơn thời gian bắt đầu') {
          this.error.end_time = '';
        }
        this.error.start_time = '';
      }
    },
    checkEndTime() {
      let end_time_task = new Date(this.task.end_time).getTime();
      let end_time_project = new Date(this.project.end_time).getTime();

      if (this.task.end_time == '') {
        this.error.end_time = 'Thời gian kết thúc là bắt buộc';
      } else if (this.task.end_time < this.project.start_time || end_time_task > end_time_project) {
        this.error.end_time = 'Thời gian kết thúc công việc không phù hợp với thời gian của dự án'
      } else if (this.task.start_time != '' && this.task.start_time > this.task.end_time) {
        this.error.end_time = 'Thời gian kết thúc phải lớn hơn thời gian bắt đầu'
      } else {
        if (this.error.start_time == 'Thời gian bắt đầu phải nhỏ hơn thời gian kết thúc') {
          this.error.start_time = '';
        }
        this.error.end_time = '';
      }
    },
    checkDepartment() {
      if (this.task.department_id == null || this.task.department_id < 0) {
        this.error.department = 'Phân công cho phòng ban là bắt buộc';
      } else {
        this.error.department = '';
      }
    },
    getProject() {
      this.$root.api.get(`project/info/${this.project_id}`).then(res => {
        if (res.data.status == 'OK') {
          this.project = res.data.data;
        } else {
          this.$root.showError(res.data.error);
        }
      }).catch(err => {
        this.$root.showError(err);
      })
    },
    getTaskUpdate(_task) {
      this.task = _.clone(_task);
      this.select_department.text = _task.department.name;
      this.task.department_id = _task.department.id;
      if (_task.label) {
        this.select_label.text = _task.label.name;
        this.task.label_id = _task.label.id;
      }
      
      this.file_updated = _task.file;
      if (_task.pre_task_ids) {
        for (let i in _task.pre_task_ids) {
          this.pre_task_id_check.push(_task.pre_task_ids[i].id);
        }
        this.pre_task_ids_show = _task.pre_task_ids;
      }
    }
  },
  created() {
    this.closeModal();
  },
  mounted() {
    this.project_id = this.$route.params.id;
    this.getList();
    this.getProject();

    $(document).on('hidden.bs.modal', '#task_modal_add_update, #task_modal_delete', () => {
      this.closeModal();
    });
  },
  watch: {
    'task.name' () {
      if (!this.validate_form) return;
      this.checkName();
    },
    'task.start_time' () {
      if (!this.validate_form) return;
      this.checkStartTime();
    },
    'task.end_time' () {
      if (!this.validate_form) return;
      this.checkEndTime();
    },
    'task.department_id' () {
      if (!this.validate_form) return;
      this.checkDepartment();
    }
  }
}
</script>
<template>
  <div id="task">
    <div v-if="project">
      <h3 class="mb-3">Dự án: {{ project.name }}</h3>
      <form @submit.prevent="getList">
        <div class="row">
          <div class="col-md-3 col-sm-5 col-12 mb-2">
            <input type="text" class="form-control form-control-sm" placeholder="Tên công việc..." v-model="search.name">
          </div>
          <div class="col-md-3 col-sm-5 col-12 mb-2">
            <m-select
              :size="'sm'"
              text="--Tìm theo phòng ban--"
              url="department/search"
              :statusReset="false"
              @changeValue="getDepartmentSearch"
              @remove="removeDepartmentSearch"
              :variable="{first: 'name'}"
            />
          </div>
          <div class="col-md-2 col-sm-2 col-6 mb-2"> 
            <button type="submit" class="btn btn-info btn-sm">
              <i class="fas fa-search"></i> Tìm
            </button>
          </div>  
          <div class="col-md-4 col-sm-12 col-6 text-right mb-2" v-if="$root.isManager()">
            <button class="btn btn-info btn-sm" data-toggle="modal" data-target="#task_modal_add_update">Thêm</button>
          </div>
        </div>
      </form>
      <div class="list">
        <div class="text-center" v-if="loading_list">
          <m-spinner/>
        </div>
        <div v-else-if="list && list.length > 0">
          <div class="mb-3"><b>Danh sách công việc</b></div>
          <ul class="row">
            <li class="col-md-3 col-sm-4 col-12 mb-3"
              v-for="(item, index) in list"
              :key="index"
            >
              <div class="bg-fff info">
                <router-link :to="{name: 'job', params: { 'project_id': project_id, 'id': item.id }}">
                  <p><i class="fas fa-folder"></i>&nbsp; <b>{{ item.name }}</b></p>
                  <p style="font-size: 12px; margin-bottom: 0px"> 
                    <b>Phân cho: </b>{{item.department.name}} <br>
                    <b>Tạo lúc: </b>{{item.created_at}} <br>
                    <b v-if="item.status != null" >Trạng thái: </b>
                    <span class="badge badge-danger" v-if="item.status.status == 2 || item.status.status == 5">
                      {{ $root.getStatusTaskName(item.status.status) }}
                    </span>
                    <span class="badge badge-success" v-else>
                      {{ $root.getStatusTaskName(item.status.status) }}
                    </span> <br>

                     <b v-if="$root.checkDeadline(item) == 'Chưa tới hạn'" class="badge badge-info">{{ $root.checkDeadline(item) }}</b>
                    <b v-else class="badge badge-danger">{{ $root.checkDeadline(item) }}</b>
                  </p>
                </router-link>

                <div class="text-right" style="padding: 0px 10px 10px 0px" v-if="$root.isManager()">
                  <span
                    class="text-danger"
                    @click="getTaskUpdate(item)"
                    data-toggle="modal"
                    data-target="#task_modal_delete"
                    style="cursor: pointer"
                  >
                    <b>Xóa</b>
                  </span>
                  <span
                    class="text-info"
                    @click="getTaskUpdate(item)"
                    data-toggle="modal"
                    data-target="#task_modal_add_update"
                    style="cursor: pointer"
                  >
                    <b>Sửa</b>
                  </span>
                </div>

                <div v-else-if="item && item.department.leader.id == $root.auth.id" style="padding: 0px 5px 5px 0px" class="text-right">
                  <div v-if="item.status.status == 0">
                    <span @click="handleTakeTask(item)" style="cursor: pointer" class="text-info">
                      <b>Tiếp nhận</b>
                    </span>
                    <span @click="getJobUpdate(item)" data-toggle="modal" data-target="#job_refuse_modal"
                      style="cursor: pointer" class="text-danger">
                      <b>Từ chối</b>
                    </span>
                  </div>
                  <div v-else-if="item.status.status == 1">
                    <span @click="getTaskUpdate(item)" data-toggle="modal" data-target="#job_modal_delete"
                      style="cursor: pointer" class="text-success">
                      <b>Hoàn thành</b>
                    </span>
                  </div>
                </div>
              </div>
            </li>
          </ul>
        </div>
        <div v-else>
          Không có công việc nào
        </div>
      </div>

      <div class="modal fade" id="task_modal_add_update">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
          <div class="modal-content">
            <div class="modal-header">
              <h4 class="modal-title">Công việc</h4>
              <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
              <form @submit.prevent="onSubmit">
                <div class="row">
                  <div class="col-md-6 col-sm-12 col-12">
                    <div class="form-group">
                      <label><b>Tên công việc <span class="text-danger">*</span></b></label>
                      <input type="text" class="form-control form-control-sm" v-model="task.name">
                      <div class="text-danger font-italic error">{{error.name}}</div>
                    </div>
                  </div>

                  <div class="col-md-6 col-sm-12 col-12">
                    <div class="form-group">
                      <label><b>Thời gian bắt đầu <span class="text-danger">*</span></b></label>
                      <input type="date" class="form-control form-control-sm" v-model="task.start_time">
                      <div class="text-danger font-italic error">{{error.start_time}}</div>
                    </div>
                  </div>

                  <div class="col-md-6 col-sm-12 col-12">
                    <div class="form-group">
                      <label><b>Thời gian kết thúc <span class="text-danger">*</span></b></label>
                      <input type="date" class="form-control form-control-sm" v-model="task.end_time">
                      <div class="text-danger font-italic error">{{error.end_time}}</div>
                    </div>
                  </div>

                  <div class="col-md-6 col-sm-12 col-12">
                    <div class="form-group">
                      <label><b>Phân công cho phòng ban <span class="text-danger">*</span></b></label>
                      <m-select
                        :size="'sm'"
                        :text="select_department.text"
                        url="department/search"
                        :statusReset="select_department.status"
                        @changeValue="getDepartment"
                        @remove="removeDepartment"
                        :variable="{first: 'name'}"
                      />
                      <div class="text-danger font-italic error">{{error.department}}</div>
                    </div>
                  </div>

                  <div class="col-md-6 col-sm-12 col-12">
                    <div class="form-group">
                      <label><b>Nhãn </b></label>
                      <m-select
                        :size="'sm'"
                        :text="select_label.text"
                        url="label/search"
                        :statusReset="select_label.status"
                        @changeValue="getLabel"
                        @remove="removeLabel"
                        :variable="{first: 'name'}"
                      />
                      <div class="text-danger font-italic error">{{error.manager}}</div>
                    </div>
                  </div>

                  <div class="col-md-6 col-sm-12 col-12">
                    <div class="form-group">
                      <label><b>Công việc tiên quyết</b></label>
                      <m-select
                        :size="'sm'"
                        :text="select_pre_task.text"
                        :url="`project/${this.project_id}/task/search`"
                        :statusReset="select_pre_task.status"
                        @changeValue="getPreTask"
                        @remove="removePreTask"
                        :variable="{first: 'name'}"
                      />
                    </div>
                    <div class="pre-tasks">
                      <ul>
                        <li v-for="(item, index) in pre_task_ids_show" :key="index">
                          {{item.name }}
                          <i class="fas fa-times icon-remove" @click="removePreTaskId(index)"></i>
                        </li>
                      </ul>
                    </div>
                  </div>

                  <div class="col-md-12 col-sm-12 col-12">
                    <div class="form-group">
                      <label><b>Mô tả</b></label><br>
                      <textarea type="text" class="form-control form-control-sm" rows="4" v-model="task.describe"></textarea>
                    </div>
                  </div>

                  <div class="col-md-12 col-sm-12 col-12">
                    <div class="form-group">
                      <label><b>Kết quả</b></label>
                      <textarea type="text" class="form-control form-control-sm" rows="4" v-model="task.result"></textarea>
                    </div>
                  </div>

                  <div class="col-md-6 col-sm-12 col-12">
                    <div class="form-group">
                      <label><b>Đính kèm</b></label>
                      <button type="button" class="btn btn-info btn-sm" @click="$refs.ref_file.click()">Chọn tập tin</button>
                      <input
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
                        <a target="_blank" :href="file_updated">Xem tệp đã chọn</a>
                        <i class="fas fa-times text-danger" style="cursor: pointer" @click="deleteFile"></i>
                      </div>
                    </div>
                  </div>

                </div>
                <div class="modal-footer">
                  <button type="submit" class="btn btn-info btn-sm">
                    {{ task.id ? 'Cập nhật' : 'Thêm'}}
                  </button>
                  <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Đóng</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>

      <div class="modal fade" id="task_modal_delete">
        <div class="modal-dialog modal-dialog-scrollable">
          <div class="modal-content">
            <div class="modal-header">
              <h4 class="modal-title">Xóa Công việc</h4>
              <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
              <div v-if="task.name" class="d-flex justify-content-start">
                <i class="fas fa-exclamation-triangle text-danger icon-warm-delete"></i>
                <span>
                  Bạn có muốn xóa công việc <b>{{ task.name }}</b>
                </span>
              </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-danger btn-sm" @click="onSubmitDelete">Xóa</button>
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Đóng</button>
              </div>
          </div>
        </div>
      </div>

      <m-loading v-if="loading_add" :title="task.id != null ? 'Đang cập nhật công việc' : 'Đang thêm công việc'" :full="true" />
      <m-loading v-if="loading_delete" title="Đang xóa công việc" :full="true" />
      <m-loading v-if="loading_delete_file" title="Đang xóa tệp đính kèm" :full="true" />
    </div>
    <m-spinner v-else class="mb-2" />
  </div>
</template>