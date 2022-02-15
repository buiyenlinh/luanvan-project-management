<script>
export default {
  data() {
    return {
      job: null,
      list: [],
      search: '',
      info: null,
      params: {
        project_id: '',
        task_id: ''
      },
      loading_info: false,
      loading_list: false,
      validate_form: false,
      file: {
        show: '',
        updated: ''
      },
      pre_job_id: {
        show: [],
        check: []
      },
      select_pre_job: null,
      select_user: null,
      error: null,
      loading_delete_file: false,
      loading_add: false,
    }
  },
  methods: {
    getList() {
      console.log("get list");
    },
    getInfo() {
      this.loading_info = true;
      this.$root.api.get(`project/${this.params.project_id}/task/${this.params.task_id}/info`).then(res => {
        if (res.data.status == "OK") {
          this.info = res.data.data;
        } else {
          this.$root.showError(res.data.error);
        }
      }).catch(err => {
        this.loading_info = false;
        this.$root.showError(err);
      })
    },
    closeModal() {
      this.validate_form = false;
      this.job = {
        id: null,
        name: '',
        content: '',
        file: '',
        task_id: null,
        start_time: '',
        end_time: '',
        pre_job_ids: [],
        user_id: null,
      };

      this.error = {
        name: '',
        content: '',
        file: '',
        task_id: '',
        start_time: '',
        end_time: '',
        pre_job_ids: '',
        user_id: '',
      }

      this.file =  {
        show: '',
        updated: ''
      }
      this.pre_job_id =  {
        show: [],
        check: []
      }

      this.select_user = {
        text: '--- Tìm tên hoặc tên đăng nhập ---',
        status: false
      }

      this.select_pre_job = {
        text: '--- Tìm tên nhiệm vụ ---',
        status: false
      }

      setTimeout(() => {
        this.validate_form = true;
      }, 300);
    },
    getUser(_user) {
      this.job.user_id = _user.id;
      this.select_user.text = _user.fullname || _user.username;
    },
    removeUser() {
      this.job.user_id = null;
      this.select_user.text = '--- Tìm thành viên ---';
    },
    onSubmit() {
      this.checkName();
      this.checkStartTime();
      this.checkEndTime();
      this.checkUser();

      let check = false;
      if (this.pre_job_id.show.length > 0) {
        for (let i in this.pre_job_id.show) {
          if (!this.checkTimePreJob(this.pre_job_id.show[i], this.job)) {
            check = true;
          }
        }
      }

      if (!check && this.error.name == '' && this.error.start_time == '' && this.error.end_time == '' && this.error.pre_job_ids == '') {
        let formData = new FormData();
        formData.append('name', this.job.name);
        formData.append('content', this.job.content);
        formData.append('user_id', this.job.user_id);
        formData.append('file', this.job.file);
        formData.append('start_time', this.job.start_time);
        formData.append('end_time', this.job.end_time);

        if (this.pre_job_id.show.length > 0) {
          for (let i in this.pre_job_id.show) {
            formData.append('pre_job_ids[]', this.pre_job_id.show[i].id);
          }
        }
        
        if (this.job.id != null) { // cập nhật
          formData.append('id', this.task.id);
          this.loading_add = true;
          console.log("Update");
          // this.$root.api.post(`project/${this.project_id}/task/update`, formData).then(res => {
          //   this.loading_add = false;
          //   if (res.data.status == 'OK') {
          //     this.$notify(res.data.message, 'success');
          //     $('#task_modal_add_update').modal('hide');
          //     this.getList();
          //   } else {
          //     this.$root.showError(res.data.error);
          //   }
          // }).catch (err => {
          //   this.$root.showError(err);
          //   this.loading_add = false;
          // })
        } else {
          this.loading_add = true;
          this.$root.api.post(`project/${this.info.project.id}/task/${this.info.task.id}/add`, formData).then(res => {
            this.loading_add = false;
            if (res.data.status == 'OK') {
              this.$notify(res.data.message, 'success');
              $('#modal_add_update').modal('hide');
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
    removePreJobId(_index) {
      this.pre_job_id.show.splice(_index, 1);
    },
    checkName() {
      if (this.job.name == '') {
        this.error.name = 'Tên nhiệm vụ là bắt buộc';
      } else {
        this.error.name = '';
      }
    },
    checkUser() {
      if (this.job.user_id == '' || this.job.user_id == null) {
        this.error.user_id = 'Phân công cho thành viên là bắt buộc';
      } else {
        this.error.user_id = '';
      }
    },
    checkStartTime() {
      let end_time_task = new Date(this.info.task.end_time).getTime();
      let start_time_task = new Date(this.info.task.start_time).getTime();
      let end_time_job = new Date(this.job.end_time).getTime();
      let start_time_job = new Date(this.job.start_time).getTime();

      if (this.job.start_time == '') {
        this.error.start_time = 'Thời gian bắt đầu là bắt buộc';
      } else if (start_time_job < start_time_task || end_time_job > end_time_task) {
        this.error.start_time = 'Thời gian bắt đầu nhiệm vụ không phù hợp với thời gian của công việc'
      } else if (this.job.end_time != '' && this.job.start_time > this.job.end_time) {
        this.error.start_time = 'Thời gian bắt đầu phải nhỏ hơn thời gian kết thúc'
      } else {
        if (this.error.end_time == 'Thời gian kết thúc phải lớn hơn thời gian bắt đầu') {
          this.error.end_time = '';
        }
        this.error.start_time = '';
      }
    },
    checkEndTime() {
      let end_time_task = new Date(this.info.task.end_time).getTime();
      let start_time_task = new Date(this.info.task.start_time).getTime();
      let end_time_job = new Date(this.job.end_time).getTime();
      let start_time_job = new Date(this.job.start_time).getTime();

      console.log('start time task: ' + start_time_task + ', start time job: ' + start_time_job);
      console.log('end time task: ' + end_time_task + ', end time job: ' + end_time_job);

      if (this.job.end_time == '') {
        this.error.end_time = 'Thời gian kết thúc là bắt buộc';
      } else if (start_time_job < start_time_task || end_time_job > end_time_task) {
        this.error.end_time = 'Thời gian kết thúc nhiệm vụ không phù hợp với thời gian của công việc'
      } else if (this.job.start_time != '' && this.job.start_time > this.job.end_time) {
        this.error.end_time = 'Thời gian kết thúc phải lớn hơn thời gian bắt đầu'
      } else {
        if (this.error.start_time == 'Thời gian bắt đầu phải nhỏ hơn thời gian kết thúc') {
          this.error.start_time = ''; 
        }
        this.error.end_time = '';
      }
    },
    handleGetFile(_file) {
      this.job.file = _file.target.files[0];
      this.file.show = _file.target.files[0].name;
    },
    removeFile() {
      this.file.show = '';
      this.job.file = '';
    },
    deleteFile() {
      this.loading_delete_file = true;
      this.$root.api.delete(`project/${this.info.project.id}/task/${this.info.task.id}/delete-file/${this.job.id}`).then(res => {
        this.loading_delete_file = false;
        if (res.data.status == "OK") {
          this.$notify(res.data.message, 'success');
          this.job.file = '';
          this.file.updated = '';
          this.getList();
        } else {
          this.$root.showError(res.data.error);
        }
      }).catch (err => {
        this.$root.showError(err);
        this.loading_delete_file = false;
      })
    },
    getPreJob(_pre_job) {
      if (this.checkTimePreJob(_pre_job, this.job)) {
        if (!this.pre_job_id.check.includes(_pre_job.id) && _pre_job.id != this.job.id) {
          this.pre_job_id.check.push(_pre_job.id);
          this.pre_job_ids.show.push(_pre_job);
        } else {
          this.select_pre_job.text = '';
          this.select_pre_job.text = '--- Tìm tên nhiệm vụ --- ';
        }
      }
    },
    checkTimePreJob(_pre_job, _job) {
      let start_time_job = new Date(_job.start_time).getTime(); // đơn vị mili giây
      let end_time_pre_job = new Date(_pre_job.end_time).getTime();
      if (start_time_job <= end_time_pre_job) {
        this.error.start_time = 'Thời gian bắt đầu nhiệm vụ không phù hợp với nhiệm vụ tiên quyết "' + _pre_job.name + '"';
        this.select_pre_job.text = '--- Tìm tên nhiệm vụ --- ';
        return false;
      }
      return true;
    },
    removePreJob() {
      this.select_pre_job.text = '--- Tìm tên nhiệm vụ ---';
    }
  },
  created() {
    this.closeModal();
  },
  mounted() {
    this.params.project_id = this.$route.params.project_id;
    this.params.task_id = this.$route.params.id;
    this.getInfo();
    $(document).on('hidden.bs.modal', '#job_modal_add_update, #job_modal_delete', () => {
      this.closeModal();
    });
  },
  watch: {
    'job.name'() {
      if (!this.validate_form) return;
      this.checkName();
    },
    'job.start_time'() {
      if (!this.validate_form) return;
      this.checkStartTime();
    },
    'job.end_time'() {
      if (!this.validate_form) return;
      this.checkEndTime();
    },
    'job.user_id'() {
      if (!this.validate_form) return;
      this.checkUser();
    }
  }
}
</script>

<template>
  <div id="job">
    <div v-if="info">
      <h3 class="mb-3">Công việc: {{ info.task.name }}</h3>
      <form @submit.prevent="getList">
        <div class="row">
          <div class="col-md-4 col-sm-6 col-8 mb-2">
            <input type="text" class="form-control form-control-sm" placeholder="Tên nhiệm vụ..." v-model="search">
          </div>
          <div class="col-md-2 col-sm-3 col-4 mb-2"> 
            <button type="submit" class="btn btn-info btn-sm">
              <i class="fas fa-search"></i> Tìm
            </button>
          </div>  
          <div class="col-md-6 col-sm-3 col-12 text-right mb-2" v-if="$root.isManager()">
            <button class="btn btn-info btn-sm" data-toggle="modal" data-target="#job_modal_add_update">Thêm</button>
          </div>
        </div>
      </form>

      <div class="text-center" v-if="loading_list">
        <m-spinner/>
      </div>
    </div>
    <m-spinner v-else class="mb-2" />

    <div class="modal fade" id="job_modal_add_update">
      <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">Nhiệm vụ</h4>
            <button type="button" class="close" data-dismiss="modal">&times;</button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="onSubmit">
              <div class="row">
                <div class="col-md-6 col-sm-12 col-12">
                  <div class="form-group">
                    <label><b>Tên nhiệm vụ <span class="text-danger">*</span></b></label>
                    <input type="text" class="form-control form-control-sm" v-model="job.name">
                    <div class="text-danger font-italic error">{{error.name}}</div>
                  </div>
                </div>

                <div class="col-md-6 col-sm-12 col-12">
                  <div class="form-group">
                    <label><b>Thời gian bắt đầu <span class="text-danger">*</span></b></label>
                    <input type="date" class="form-control form-control-sm" v-model="job.start_time">
                    <div class="text-danger font-italic error">{{error.start_time}}</div>
                  </div>
                </div>

                <div class="col-md-6 col-sm-12 col-12">
                  <div class="form-group">
                    <label><b>Thời gian kết thúc <span class="text-danger">*</span></b></label>
                    <input type="date" class="form-control form-control-sm" v-model="job.end_time">
                    <div class="text-danger font-italic error">{{error.end_time}}</div>
                  </div>
                </div>

                <div class="col-md-6 col-sm-12 col-12">
                  <div class="form-group">
                    <label><b>Phân công cho thành viên <span class="text-danger">*</span></b></label>
                    <m-select
                      v-if="info"
                      :size="'sm'"
                      :text="select_user.text"
                      :url="`project/${info.project.id}/task/${info.task.id}/search-user-member`"
                      :statusReset="select_user.status"
                      @changeValue="getUser"
                      @remove="removeUser"
                      :variable="{first: 'fullname', second: 'username'}"
                    />
                    <div class="text-danger font-italic error">{{error.user_id}}</div>
                  </div>
                </div>

                <div class="col-md-6 col-sm-12 col-12">
                  <div class="form-group">
                    <label><b>Nhiệm vụ tiên quyết</b></label>
                    <m-select
                      v-if="info"
                      :size="'sm'"
                      :text="select_pre_job.text"
                      :url="`project/${info.project.id}/task/${info.task.id}/search-job`"
                      :statusReset="select_pre_job.status"
                      @changeValue="getPreJob"
                      @remove="removePreJob"
                      :variable="{first: 'name'}"
                    />
                  </div>
                  <div class="pre-tasks">
                    <ul v-if="pre_job_id">
                      <li v-for="(item, index) in pre_job_id.show" :key="index">
                        {{item.name }}
                        <i class="fas fa-times icon-remove" @click="removePreJobId(index)"></i>
                      </li>
                    </ul>
                  </div>
                </div>

                <div class="col-md-12 col-sm-12 col-12">
                  <div class="form-group">
                    <label><b>Nội dung</b></label><br>
                    <textarea type="text" class="form-control form-control-sm" rows="4" v-model="job.content"></textarea>
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
                    <div v-if="file && file.show">
                      <span>File được chọn: {{ file.show }}</span>
                      <i class="fas fa-times text-danger" style="cursor: pointer" @click="removeFile"></i>
                    </div>
                    <div v-if="file && file.updated">
                      <a target="_blank" :href="file.updated">Xem tệp đã chọn</a>
                      <i class="fas fa-times text-danger" style="cursor: pointer" @click="deleteFile"></i>
                    </div>
                  </div>
                </div>

              </div>
              <div class="modal-footer">
                <button type="submit" class="btn btn-info btn-sm">
                  {{ job.id ? 'Cập nhật' : 'Thêm'}}
                </button>
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Đóng</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <m-loading v-if="loading_add" :title="job.id != null ? 'Đang cập nhật nhiệm vụ' : 'Đang thêm nhiệm vụ'" :full="true" />
  </div>
</template>