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
      loading_delete_file: false
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
      console.log("OnSubmit");
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
  },
  created() {
    this.closeModal();
  },
  mounted() {
    this.params.project_id = this.$route.params.project_id;
    this.params.task_id = this.$route.params.id;
    this.getInfo();
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

                  <!-- <div class="col-md-6 col-sm-12 col-12">
                    <div class="form-group">
                      <label><b>Nhiệm vụ tiên quyết</b></label>
                      <m-select
                        :size="'sm'"
                        :text="select_pre_job.text"
                        :url="`project/${project_id}/task/${task_id}/search`"
                        :statusReset="select_pre_job.status"
                        @changeValue="getPreTask"
                        @remove="removePreTask"
                        :variable="{first: 'name'}"
                      />
                    </div>
                    <div class="pre-tasks">
                      <ul v-if="pre_task_ids">
                        <li v-for="(item, index) in pre_task_ids.show" :key="index">
                          {{item.name }}
                          <i class="fas fa-times icon-remove" @click="removePreJobId(index)"></i>
                        </li>
                      </ul>
                    </div>
                  </div> -->

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
  </div>
</template>