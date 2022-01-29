<script>
export default {
  data() {
    return {
      project_id: '',
      search: {
        name: ''
      },
      list: null,
      loading_list: false,
      task: null,
      error: null,
      validate_form: false,
      select_department: {
        text: '--- Tìm phòng ban ---',
        status: false
      },
      select_label: {
        text: '--- Tìm nhãn ---',
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
    },
    handleSearch() {
      console.log("handleSearch");
    },
    getDepartmentSearch(_department) {
      this.task.department_id = _deparment.id;
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
        project_id: null,
        pre_task_id: null,
        department_id: null,
        file: ''
      };

      this.error = {
        name: '',
        describe: '',
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
        text: '--- Tìm nhãn ---',
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
    getLabel(_label) {
      this.task.label_id= _label.id;
      this.select_label.text = _label.name;
    },
    handleGetFile(_file) {
      this.task.file = _file.target.files[0];
      this.file_show = _file.target.files[0].name;
    },
    getPreTask(_pre_task) {
      this.task.pre_task_id= _pre_task.id;
      this.select_pre_task.text = _pre_task.name;
    },
    checkName() {
      if (this.task.name == '') {
        this.error.name = 'Tên công việc là bắt buộc';
      } else {
        this.error.name = '';
      }
    }
  },
  created() {
    this.closeModal();
  },
  mounted() {
    this.project_id = this.$route.params.id;
    this.getList();

    $(document).on('hidden.bs.modal', '#task_modal_add_update, #task_modal_delete', () => {
      this.closeModal();
    });
  },
  watch: {
    'task.name' () {
      if (!this.validate_form) {
        return;
      }
      this.checkName();
    }
  }
}
</script>
<template>
  <div id="task">
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
      <div v-if="list && list.length > 0">
        <h3>Danh sách task</h3>
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
                    <label><b>Công việc tiên quyết <span class="text-danger">*</span></b></label>
                    <m-select
                      :size="'sm'"
                      :text="select_pre_task.text"
                      :url="`project/${this.project_id}/task/search`"
                      :statusReset="select_pre_task.status"
                      @changeValue="getPreTask"
                      :variable="{first: 'name'}"
                    />
                    <div class="text-danger font-italic error">{{error.end_time}}</div>
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
                  <div class="input-group input-group-sm mb-3">
                    <input type="number" class="form-control form-control-sm" v-model="task.delay_time">
                    <div class="input-group-append">
                      <span class="input-group-text">( Ngày )</span>
                    </div>
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
                      :variable="{first: 'name'}"
                    />
                    <div class="text-danger font-italic error">{{error.manager}}</div>
                  </div>
                </div>

                <div class="col-md-6 col-sm-12 col-12">
                  <div class="form-group">
                    <label><b>Chọn nhãn </b></label>
                    <m-select
                      :size="'sm'"
                      :text="select_label.text"
                      url="label/search"
                      :statusReset="select_label.status"
                      @changeValue="getLabel"
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
  </div>
</template>