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
      loading_delete: false,
      loading_take_job: false,
      loading_refuse_job: false,
      reason: '',
      reason_error: '',
      note_finish: '',
      loading_finish_job: false,
      loading_approval_job: false,
      loading_not_approval_job: false,
      reason_not_approval: '',
      reason_not_approval_error: '',
      reason_not_approval_refuse: '',
      reason_not_approval_refuse_error: '',
      loading_not_approval_refuse_job: '',
    }
  },
  methods: {
    getList() {
      this.loading_list = true;
      this.$root.api.get(`project/${this.params.project_id}/task/${this.params.task_id}/list`, {
        params: {
          name: this.search,
        }
      }).then(res => {
        this.loading_list = false;
        if (res.data.status == "OK") {
          this.list = res.data.data.data;
        } else {
          this.$root.showError(res.data.error);
        }
      }).catch(err => {
        this.loading_list = false;
        this.$root.showError(err);
      })
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

      this.reason =  '';
      this.reason_not_approval = '';
      this.reason_not_approval_refuse = '';

      setTimeout(() => {
        this.validate_form = true;
      }, 300);
    },
    getUser(_user) {
      this.job.user_id = _user.id;
      this.select_user.text = _user.fullname || _user.username;
      this.checkUser();
    },
    removeUser() {
      this.job.user_id = null;
      this.select_user.text = '--- Tìm thành viên ---';
      this.checkUser();
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

      if (!check && this.error.name == '' && this.error.start_time == '' && this.error.end_time == '' && this.error.pre_job_ids == '' && this.error.user_id == '') {
        let formData = new FormData();
        formData.append('content', this.job.content);
        formData.append('user_id', this.job.user_id);
        formData.append('file', this.job.file);
        
        if (this.job.id != null && this.job.id != '' && this.job.id > 0) { // cập nhật
          formData.append('id', this.job.id);
          this.loading_add = true;

          this.$root.api.post(`project/${this.params.project_id}/task/${this.params.task_id}/update/${this.job.id}`, formData).then(res => {
            this.loading_add = false;
            if (res.data.status == 'OK') {
              this.$notify(res.data.message, 'success');
              $('#job_modal_add_update').modal('hide');
              this.getList();
            } else {
              this.$root.showError(res.data.error);
            }
          }).catch (err => {
            this.$root.showError(err);
            this.loading_add = false;
          })
        } else { // Thêm
          formData.append('name', this.job.name);
          formData.append('start_time', this.job.start_time);
          formData.append('end_time', this.job.end_time);
          if (this.pre_job_id.show.length > 0) {
            for (let i in this.pre_job_id.show) {
              formData.append('pre_job_ids[]', this.pre_job_id.show[i].id);
            }
          }

          this.loading_add = true;
          this.$root.api.post(`project/${this.info.project.id}/task/${this.info.task.id}/add`, formData).then(res => {
            this.loading_add = false;
            if (res.data.status == 'OK') {
              this.$notify(res.data.message, 'success');
              $('#job_modal_add_update').modal('hide');
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
      this.pre_job_id.check.splice(_index, 1);
    },
    checkName() {
      if (this.job.name == '') {
        this.error.name = 'Tên nhiệm vụ là bắt buộc';
      } else {
        this.error.name = '';
      }
    },
    checkUser() {
      if (this.job.user_id == null || this.job.user_id == '' || this.job.user_id <= 0) {
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
      } else if (start_time_job < start_time_task) {
        this.error.start_time = 'Thời gian bắt đầu nhiệm vụ không phù hợp với thời gian bắt đầu của công việc';
      } else if (end_time_job > end_time_task) {
        this.error.end_time = 'Thời gian kết thúc nhiệm vụ không phù hợp với thời gian kết thúc của công việc';
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
          this.pre_job_id.show.push(_pre_job);
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
    },
    getJobUpdate(_job) {
      this.job = _.clone(_job);
      this.file.updated = _job.file;
      this.job.user_id = _job.user.id;  
      this.select_user.text = _job.user.fullname || _job.user.username;
      this.pre_job_id.show = _job.pre_job_ids;

      if (_job.pre_job_ids) {
        for (let i in _job.pre_job_ids) {
          this.pre_job_id.check.push(_job.pre_job_ids[i].id);
        }
      }
    },
    onSubmitDelete() {
      this.loading_delete = true;
      this.$root.api.delete(`project/${this.params.project_id}/task/${this.params.task_id}/delete/${this.job.id}`).then(res => {
        this.loading_delete = false;
        if (res.data.status == "OK") {
          this.$notify(res.data.message, 'success');
          $('#job_modal_delete').modal('hide');
          this.getList();
        } else {
          this.$root.showError(res.data.error);
        }
      }).catch (err => {
        this.$root.showError(err);
        this.loading_delete = false;
      })
    },
    handleTakeJob(_job) {
      this.loading_take_job = true;
      this.$root.api.post(`project/${this.params.project_id}/task/${this.params.task_id}/take-job/${_job.id}`).then(res => {
        this.loading_take_job = false;
        if (res.data.status == "OK") {
          this.$notify(res.data.message, 'success');
          this.getList();
        } else {
          this.$root.showError(res.data.error);
        }
      }).catch (err => {
        this.$root.showError(err);
        this.loading_take_job = false;
      })
    },
    checkReasonRefuse() {
      if (this.reason == '')
        this.reason_error = "Lý do từ chối nhiệm vụ là bắt buộc";
      else 
        this.reason_error = '';
    },
    handleRefuseJob() {
      this.checkReasonRefuse();
      if (this.reason_error == '') {
        this.loading_refuse_job = true;
        this.$root.api.post(`project/${this.params.project_id}/task/${this.params.task_id}/refuse-job/${this.job.id}`, {
          'content' : this.reason
        }).then(res => {
          this.loading_refuse_job = false;
          if (res.data.status == "OK") {
            this.$notify(res.data.message, 'success');
            $('#job_refuse_modal').modal('hide');
            this.getList();
          } else {
            this.$root.showError(res.data.error);
          }
        }).catch (err => {
          this.$root.showError(err);
          this.loading_refuse_job = false;
        })
      }
    },
    handleFinishJob() {
      this.loading_finish_job = true;
      this.$root.api.post(`project/${this.params.project_id}/task/${this.params.task_id}/finish-job/${this.job.id}`, {
        'content' : this.note_finish
      }).then(res => {
        this.loading_finish_job = false;
        if (res.data.status == "OK") {
          this.$notify(res.data.message, 'success');
          $('#job_finish_modal').modal('hide');
          this.getList();
        } else {
          this.$root.showError(res.data.error);
        }
      }).catch (err => {
        this.$root.showError(err);
        this.loading_finish_job = false;
      })
    },
    handleApprovalJob(_job) {
      this.loading_approval_job = true;
      this.$root.api.post(`project/${this.params.project_id}/task/${this.params.task_id}/approval-job/${_job.id}`).then(res => {
        this.loading_approval_job = false;
        if (res.data.status == "OK") {
          this.$notify(res.data.message, 'success');
          $('#job_finish_modal').modal('hide');
          this.getList();
        } else {
          this.$root.showError(res.data.error);
        }
      }).catch (err => {
        this.$root.showError(err);
        this.loading_approval_job = false;
      })
    },
    handleNotApprovalJob() {
      this.checkReasonNotApproval();
      if (this.reason_not_approval_error == '') {
        this.loading_not_approval_job = true;
        this.$root.api.post(`project/${this.params.project_id}/task/${this.params.task_id}/not-approval-job/${this.job.id}`, {
          'content' : this.reason_not_approval
        }).then(res => {
          this.loading_not_approval_job = false;
          if (res.data.status == "OK") {
            this.$notify(res.data.message, 'success');
            $('#job_not_approval_modal').modal('hide');
            this.getList();
          } else {
            this.$root.showError(res.data.error);
          }
        }).catch (err => {
          this.$root.showError(err);
          this.loading_not_approval_job = false;
        })
      }
    },
    checkReasonNotApproval() {
      if (this.reason_not_approval == '')
        this.reason_not_approval_error = 'Lý do không duyệt nhiệm vụ là bắt buộc';
      else {
        this.reason_not_approval_error = '';
      }
    },
    checkReasonNotApprovalRefuse() {
      if (this.reason_not_approval_refuse == '')
        this.reason_not_approval_refuse_error = 'Lý do không duyệt yêu cầu từ chối nhiệm vụ là bắt buộc';
      else {
        this.reason_not_approval_refuse_error = '';
      }     
    },
    handleNotApprovalRefuseJob() {
      this.checkReasonNotApprovalRefuse();
      if (this.reason_not_approval_refuse_error == '') {
        this.loading_not_approval_refuse_job = true;
        this.$root.api.post(`project/${this.params.project_id}/task/${this.params.task_id}/not-approval-refuse-job/${this.job.id}`, {
          'content' : this.reason_not_approval_refuse
        }).then(res => {
          this.loading_not_approval_refuse_job = false;
          if (res.data.status == "OK") {
            this.$notify(res.data.message, 'success');
            $('#job_not_approval_refuse_modal').modal('hide');
            this.getList();
          } else {
            this.$root.showError(res.data.error);
          }
        }).catch (err => {
          this.$root.showError(err);
          this.loading_not_approval_refuse_job = false;
        })
      }
    }
  },
  created() {
    this.closeModal();
  },
  mounted() {
    this.params.project_id = this.$route.params.project_id;
    this.params.task_id = this.$route.params.id;
    this.getInfo();
    this.getList();
    $(document).on('hidden.bs.modal', 
      '#job_modal_add_update, #job_modal_delete, #job_modal_details, #job_not_approval_modal, #job_refuse_modal, #job_finish_modal, #job_not_approval_refuse_modal',
      () => {
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
    },
    'reason'() {
      if (!this.validate_form) return;
      this.checkReasonRefuse();
    },
    'reason_not_approval'() {
      if (!this.validate_form) return;
      this.checkReasonNotApproval();
    },
    'reason_not_approval_refuse'() {
      if (!this.validate_form) return;
      this.checkReasonNotApprovalRefuse();
    }
  }
}
</script>

<template>
  <div id="job">
    <div v-if="info">
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><router-link :to="{ name: 'project' }">Dự án</router-link>  </li>
          <li class="breadcrumb-item">
            <router-link :to="{name: 'task', params: { 'id': info.project.id }}">{{ info.project.name }}</router-link>
          </li>
          <li class="breadcrumb-item active" aria-current="page">{{ info.task.name }}</li>
        </ol>
      </nav>

      <div v-if="info.task.status.status <= 0">
        <m-spinner class="mb-2" />
        <b>Hãy chọn tiếp nhận công việc trước khi thêm nhiệm vụ</b>
      </div>
      <div v-else-if="info.task.status.status == 5">
        <m-spinner class="mb-2" />
        <b>Công việc đã gửi yêu cầu từ chối nhận</b>
      </div>
      <div v-else>
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
            <div class="col-md-6 col-sm-3 col-12 text-right mb-2" v-if="$root.isManager() || $root.auth.id == info.task.department.leader.id">
              <button class="btn btn-info btn-sm" data-toggle="modal" data-target="#job_modal_add_update">Thêm</button>
            </div>
          </div>
        </form>

        <div class="list">
          <div class="card">
            <div class="card-header bg-info text-white">Danh sách nhiệm vụ</div>
              <div class="table-responsive">
                <table class="table table-bordered table-stripped mb-0">
                  <thead>
                    <tr>
                      <td><b>STT</b></td>
                      <td><b>Tên</b></td>
                      <td><b>Phân cho</b></td>
                      <td><b>Thống kê</b></td>
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
                    <template v-else-if="list && list.length > 0">
                      <tr v-for="(item, index) in list" :key="index">
                        <td>{{ index + 1 }}</td>
                        <td>{{ item.name }}</td>
                        <td>{{ item.user.fullname || item.user.username }}</td>
                        <td>SLHT / SLTH</td>
                        <td>
                          <span class="badge badge-danger" v-if="item.status.status == 2 || item.status.status == 4 || item.status.status == 5 || item.status.status == 6">
                            {{ $root.getStatusTaskName(item.status.status) }}
                          </span>

                          <span v-else-if="item.status.status == 3">
                            <b v-if="item.delay_time == 0"  class="badge badge-success">Hoàn thành đúng hạn</b>
                            <b v-else class="badge badge-danger">Hoàn thành trễ {{ item.delay_time }} ngày</b> 
                          </span>
                          
                          <span class="badge badge-info" v-else>
                            {{ $root.getStatusTaskName(item.status.status) }}
                          </span>

                          <span v-if="item.status.status != 3">
                            <b v-if="$root.checkDeadline(item) == 'Chưa tới hạn'" class="badge badge-success">{{ $root.checkDeadline(item) }}</b>
                            <b v-else class="badge badge-danger">{{ $root.checkDeadline(item) }}</b>
                          </span>
                        </td>
                        <td>{{ item.created_at }}</td>
                        <td>
                          <button @click="getJobUpdate(item)"
                            data-toggle="modal"
                            data-target="#job_modal_details"
                            class="btn btn-secondary btn-sm">Xem</button>

                          <template v-if="info && $root.auth.id == info.task.department.leader.id">
                            <template v-if="item.status.status != 3">
                              <button
                                class="btn btn-info btn-sm"
                                @click="getJobUpdate(item)"
                                data-toggle="modal"
                                data-target="#job_modal_add_update">Sửa</button>

                              <button
                                class="btn btn-danger btn-sm"
                                @click="getJobUpdate(item)"
                                data-toggle="modal"
                                data-target="#job_modal_delete">Xóa</button>
                            </template>
                            <template v-if="item.status.status == 2">
                              <button class="btn btn-info btn-sm" @click="handleApprovalJob(item)">Duyệt </button>
                              <button class="btn btn-danger btn-sm"
                                @click="getJobUpdate(item)" data-toggle="modal" data-target="#job_not_approval_modal">
                                Không duyệt
                              </button>
                            </template>

                            <button v-if="item.status.status == 5"
                              class="btn btn-danger btn-sm"
                              @click="getJobUpdate(item)"
                              data-toggle="modal"
                              data-target="#job_not_approval_refuse_modal"
                            >Không duyệt từ chối</button>
                          </template>

                          <template v-else-if="item && item.user.id == $root.auth.id">
                            <span v-if="item.status.status == 0">
                              <button @click="handleTakeJob(item)" class="btn btn-info btn-sm">Tiếp nhận</button>
                              <button @click="getJobUpdate(item)" data-toggle="modal" data-target="#job_refuse_modal" class="btn btn-sm btn-dark">Từ chối</button>
                            </span>
                            <span v-else-if="item.status.status == 1 || item.status.status == 4 || item.status.status == 6">
                              <button @click="getJobUpdate(item)" data-toggle="modal" data-target="#job_finish_modal"
                                class="btn btn-sm btn-success">Hoàn thành</button>
                            </span>
                          </template>
                        </td>
                      </tr>
                    </template>
                    <tr v-else ><td colspan="1000" align="center">Không có nhiệm vụ</td></tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
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
                    <input type="text" class="form-control form-control-sm" v-model="job.name" v-if="job.id" disabled>
                    <input type="text" class="form-control form-control-sm" v-model="job.name" v-else>
                    <div class="text-danger font-italic error">{{error.name}}</div>
                  </div>
                </div>

                <div class="col-md-6 col-sm-12 col-12" v-if="!job.id">
                  <div class="form-group">
                    <label><b>Thời gian bắt đầu <span class="text-danger">*</span></b></label>
                    <input type="date" class="form-control form-control-sm" v-model="job.start_time">
                    <div class="text-danger font-italic error">{{error.start_time}}</div>
                  </div>
                </div>

                <div class="col-md-6 col-sm-12 col-12" v-if="!job.id">
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

                <div class="col-md-6 col-sm-12 col-12" v-if="!job.id">
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
                  <div class="pre-jobs">
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

    <div class="modal fade" id="job_modal_details">
      <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">Thông tin chi tiết</h4>
            <button type="button" class="close" data-dismiss="modal">&times;</button>
          </div>
          <div class="modal-body" v-if="job">
            <div class="form-group">
              <b class="text-info">Tên: {{ job.name }}</b>
            </div>
            <div class="form-group" v-if="job.user">
              <b>Phân công cho: </b> <span>{{ job.user.fullname || job.user.username }}</span>
            </div>

            <div class="form-group">
              <b>Thời gian bắt đầu: </b> <span>{{ job.start_time }}</span>
            </div>

            <div class="form-group">
              <b>Hạn chót: </b> <span>{{ job.end_time }}</span>
            </div>

            <div class="form-group" v-if="job.content">
              <b>Nội dung: </b> <span>{{ job.content }}</span>
            </div>

            <div class="form-group" v-if="file && file.updated">
              <b>Tệp đính kèm: </b>
              <a target="_blank" :href="file.updated">Xem tệp</a>
            </div>

            <div class="form-group">
              <b>Nhiệm vụ tiên quyết: </b>
              <div class="pre-jobs mt-2" v-if="pre_job_id && pre_job_id.show.length > 0">
                <ul>
                  <li v-for="(item, index) in pre_job_id.show" :key="index">
                    {{item.name }}
                  </li>
                </ul>
              </div>
              <span v-else >Không có</span>
            </div>

            <div class="form-group" v-if="job.status">
              <b>Trạng thái: </b><span>{{ $root.getStatusTaskName(job.status.status) }}</span>
              <div class="pl-2" v-if="job.status.content"><b>Nội dung phản hồi: </b>
              <br><span>{{ job.status.content }}</span> </div>
            </div> 

            <div class="form-group">
              <b v-if="$root.checkDeadline(job) == 'Chưa tới hạn'" class="badge badge-info">{{ $root.checkDeadline(job) }}</b>
              <b v-else class="badge badge-danger">{{ $root.checkDeadline(job) }}</b>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Đóng</button>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="job_modal_delete">
      <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">Xóa nhiệm vụ</h4>
            <button type="button" class="close" data-dismiss="modal">&times;</button>
          </div>
          <div class="modal-body">
            <div v-if="job.name" class="d-flex justify-content-start">
              <i class="fas fa-exclamation-triangle text-danger icon-warm-delete"></i>
              <span>
                Bạn có muốn xóa nhiệm vụ <b>{{ job.name }}</b>
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

    <div class="modal fade" id="job_refuse_modal">
      <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">Từ chối nhiệm vụ</h4>
            <button type="button" class="close" data-dismiss="modal">&times;</button>
          </div>
          <div class="modal-body">
            <div v-if="job" class="row">
              <div class="col-md-12 col-sm-12 col-12">
                <div class="form-group">
                  <label><b>Tên nhiệm vụ <span class="text-danger">*</span></b></label>
                  <input type="text" class="form-control form-control-sm" disabled v-model="job.name">
                </div>
              </div>

              <div class="col-md-12 col-sm-12 col-12">
                <div class="form-group">
                  <label><b>Lý do từ chối <span class="text-danger">*</span></b></label>
                  <textarea class="form-control" rows="5" v-model="reason"></textarea>
                  <div class="text-danger font-italic error">{{ reason_error }}</div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-danger btn-sm" @click="handleRefuseJob">Gửi</button>
            <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Đóng</button>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="job_finish_modal">
      <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">Hoàn thành nhiệm vụ</h4>
            <button type="button" class="close" data-dismiss="modal">&times;</button>
          </div>
          <div class="modal-body">
            <div v-if="job" class="row">
              <div class="col-md-12 col-sm-12 col-12">
                <div class="form-group">
                  <label><b>Tên nhiệm vụ <span class="text-danger">*</span></b></label>
                  <input type="text" class="form-control form-control-sm" disabled v-model="job.name">
                </div>
              </div>

              <div class="col-md-12 col-sm-12 col-12">
                <div class="form-group">
                  <label><b>Ghi chú </b></label>
                  <textarea class="form-control" rows="5" v-model="note_finish"></textarea>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-danger btn-sm" @click="handleFinishJob">Gửi</button>
            <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Đóng</button>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="job_not_approval_modal">
      <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">Từ chối duyệt nhiệm vụ</h4>
            <button type="button" class="close" data-dismiss="modal">&times;</button>
          </div>
          <div class="modal-body">
            <div v-if="job" class="row">
              <div class="col-md-12 col-sm-12 col-12">
                <div class="form-group">
                  <label><b>Tên nhiệm vụ <span class="text-danger">*</span></b></label>
                  <input type="text" class="form-control form-control-sm" disabled v-model="job.name">
                </div>
              </div>

              <div class="col-md-12 col-sm-12 col-12">
                <div class="form-group">
                  <label><b>Lý do </b></label>
                  <textarea class="form-control" rows="5" v-model="reason_not_approval"></textarea>
                  <div class="text-danger font-italic error">{{ reason_not_approval_error }}</div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-danger btn-sm" @click="handleNotApprovalJob">Từ chối duyệt</button>
            <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Đóng</button>
          </div>
        </div>
      </div>
    </div>


    <div class="modal fade" id="job_not_approval_refuse_modal">
      <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">Không duyệt yêu cầu từ chối nhiệm vụ</h4>
            <button type="button" class="close" data-dismiss="modal">&times;</button>
          </div>
          <div class="modal-body">
            <div v-if="job" class="row">
              <div class="col-md-12 col-sm-12 col-12">
                <div class="form-group">
                  <label><b>Tên nhiệm vụ <span class="text-danger">*</span></b></label>
                  <input type="text" class="form-control form-control-sm" disabled v-model="job.name">
                </div>
              </div>

              <div class="col-md-12 col-sm-12 col-12">
                <div class="form-group">
                  <label><b>Lý do <span class="text-danger">*</span></b></label>
                  <textarea class="form-control" rows="5" v-model="reason_not_approval_refuse"></textarea>
                  <div class="text-danger font-italic error">{{ reason_not_approval_refuse_error }}</div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-danger btn-sm" @click="handleNotApprovalRefuseJob">Từ chối duyệt</button>
            <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Đóng</button>
          </div>
        </div>
      </div>
    </div>

    <m-loading v-if="loading_add" :title="job.id != null ? 'Đang cập nhật nhiệm vụ' : 'Đang thêm nhiệm vụ'" :full="true" />
    <m-loading v-if="loading_delete_file" title="Đang xóa tệp" :full="true" />
    <m-loading v-if="loading_delete" title="Đang xóa nhiệm vụ" :full="true" />
    <m-loading v-if="loading_refuse_job" title="Đang gửi yêu cầu từ chối nhiệm vụ" :full="true" />
    <m-loading v-if="loading_take_job" title="Đang thực hiện tiếp nhận nhiệm vụ" :full="true" />
    <m-loading v-if="loading_finish_job" title="Đang gửi yêu cầu duyệt hoàn thành nhiệm vụ" :full="true" />
    <m-loading v-if="loading_approval_job" title="Đang duyệt nhiệm vụ" :full="true" />  
    <m-loading v-if="loading_not_approval_job" title="Đang từ chối duyệt nhiệm vụ" :full="true" />  
    <m-loading v-if="loading_not_approval_refuse_job" title="Đang thực hiện không duyệt yêu cầu từ chối nhiệm vụ" :full="true" />  
  </div>
</template>