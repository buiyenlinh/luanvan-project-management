<script>
export default {
  data() {
    return {
      project_name: '',
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
      }
    }
  },
  methods: {
    getList() {
      this.loading_list = true;
      this.$root.api.post('task/list', {'project_name' : this.project_name}).then(res => {
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
      console.log("onSubmit");
    },
    handleSearch() {
      console.log("handleSearch");
    },
    getDepartmentSearch() {
      console.log("getDepartmentSearch");
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
        pre_task_id: null
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

      this.list = [];

      this.select_department = {
        text: '--- Tìm phòng ban ---',
        status: false
      }

      setTimeout(() => {
        this.validate_form = true;
      }, 300);
    },
    getDepartment() {
      console.log("getDepartment");
    },
    getLabel() {
      console.log("getLabel");
    }
  },
  created() {
    this.closeModal();
  },
  mounted() {
    this.project_name = this.$route.params.project_name;
    this.getList();
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
          <button class="btn btn-info btn-sm" data-toggle="modal" data-target="#task_modal_add">Thêm</button>
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

    <div class="modal fade" id="task_modal_add">
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
                    <label><b>Thời gian kết thức <span class="text-danger">*</span></b></label>
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
                      url="user/search-manager"
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
                      url="user/search-manager"
                      :statusReset="select_label.status"
                      @changeValue="getLabel"
                      :variable="{first: 'name'}"
                    />
                    <div class="text-danger font-italic error">{{error.manager}}</div>
                  </div>
                </div>
                
                <div class="col-md-6 col-sm-12 col-12">
                  <div class="form-group">
                    <label><b>Kết quả mong muốn</b></label>
                    <input type="number" class="form-control form-control-sm" v-model="task.result">
                  </div>
                </div>

                <div class="col-md-12 col-sm-12 col-12">
                  <div class="form-group">
                    <label><b>Mô tả</b></label><br>
                    <textarea type="text" class="form-control form-control-sm" rows="5" v-model="task.describe"></textarea>
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