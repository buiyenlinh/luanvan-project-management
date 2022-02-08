<script>
export default {
  data() {
    return {
      project_id: '',
      project: null,
      search: {
        name: ''
      },
      list: null,
      loading_list: false,
      loading_add: false,
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
      file_show: ''
    }
  },
  methods: {
    getList() {
      this.loading_list = true;
      this.$root.api.get(`project/${this.project_id}/task/list`).then(res => {
        if (res.data.status == "OK") {
          this.list = res.data.data;
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
      this.checkRelayTime();
      if (this.error.name == '' && this.error.start_time == '' && this.error.end_time == '' && this.error.department == '' && this.error.delay_time == '') {

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
        formData.append('delay_time', this.task.delay_time);

        if (this.task.id != null) {
          console.log("Cập nhật")
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
            this.$root.showError(res.data.error);
            this.loading_add = false;
          })
        }
      }
    },
    handleSearch() {
      console.log("handleSearch");
    },
    getDepartmentSearch(_department) {
      this.task.department_id = _deparment.id;
    },
    removeDepartmentSearch() {
      this.task.department_id = '';
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
        delay_time: '',
        label_id: '',
        pre_task_id: null,
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
        delay_time: '',
        label_id: '',
        project_id: '',
        pre_task_id: ''
      }

      this.file_show = '';

      this.list = [];

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
    getDepartment(_deparment) {
      this.task.department_id= _deparment.id;
      this.select_department.text = _deparment.name;
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
      this.task.label_id= null;
      this.select_label.text = '--- Tìm tên nhãn ---';
    },
    handleGetFile(_file) {
      this.task.file = _file.target.files[0];
      this.file_show = _file.target.files[0].name;
    },
    getPreTask(_pre_task) {
      this.task.pre_task_id= _pre_task.id;
      this.select_pre_task.text = _pre_task.name;
    },
    removePreTask() {
      this.task.pre_task_id = null;
      this.select_pre_task.text = '--- Tìm tên công việc ---';
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
      let delay_time_project = Number(this.project.delay_time) * 24 * 60 * 60 * 1000;

      if (this.task.end_time == '') {
        this.error.end_time = 'Thời gian kết thúc là bắt buộc';
      } else if (this.task.end_time < this.project.start_time || end_time_task > end_time_project + delay_time_project) {
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
    checkRelayTime() {
      let end_time_task = new Date(this.task.end_time).getTime();
      let end_time_project = new Date(this.project.end_time).getTime();
      let delay_time_task = Number(this.task.delay_time) * 24 * 60 * 60 * 1000;
      let delay_time_project = Number(this.project.delay_time) * 24 * 60 * 60 * 1000;

      if (Number(end_time_task) + delay_time_task > Number(end_time_project) + delay_time_project) {
        this.error.delay_time = 'Thời gian trì hoãn quá hạn thời gian dự án';
      } else {
        this.error.delay_time = '';
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
      this.checkRelayTime();
    },
    'task.delay_time' () {
      if (!this.validate_form) return;
      this.checkRelayTime();
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
    <h3 v-if="project" class="mb-2">Dự án: {{ project.name }}</h3>
    <m-spinner v-else class="mb-2" />
    <form @submit.prevent="handleSearch">
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
        <b>Danh sách công việc</b>
        
      </div>
      <div v-else>
        Chưa có công việc nào
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
                  <label><b>Thời gian trì hoãn</b></label>
                  <div class="input-group input-group-sm">
                    <input type="number" class="form-control form-control-sm" v-model="task.delay_time">
                    <div class="input-group-append">
                      <span class="input-group-text">( Ngày )</span>
                    </div>
                  </div>
                  <div class="text-danger font-italic error mb-3">{{error.delay_time}}</div>
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
                    <div v-if="file_show">File được chọn: {{ file_show }}</div>
                  </div>
                </div>

              </div>
              <div class="modal-footer">
                <button type="submit" class="btn btn-info btn-sm">
                  {{ task.id ? 'Cập nhật' : 'Thêm'}}
                </button>
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <m-loading v-if="loading_add" :title="this.task.id != null ? 'Đang cập nhật công việc' : 'Đang thêm công việc'" :full="true" />
  </div>
</template>