<script>
export default {
  data () {
    return {
      search_name: '',
      loading_get_list: false,
      list: '',
      current_page: 1,
      last_page: 1,
      label: null,
      form_validate: false,
      error_name: '',
      loading_add_update: false,
      file_show: {
        name: '',
        url: ''
      },
      loading_delete: false
    }
  },
  methods: {
    getList() {
      this.loading_get_list = true;
      this.$root.api.get(`label/list`, {
        params: {
          page: this.current_page,
          keyword: this.search_name,
        }
      }).then(res => {
        this.loading_get_list = false;
        if (res.data.status == "OK") {
          this.list = res.data.data;
          this.last_page = res.data.data.meta.last_page;
          if (this.current_page > 1 && !this.list.data.length) {
            this.$router.replace({
							name: 'label',
							query: {
								page: this.current_page - 1
							}
						})
						this.changePage(this.current_page - 1);
          }
        }
      }).catch(err => {
        this.$root.showError(err);
        this.loading_get_list = false;
      })
    },
    changePage(_page) {
			this.current_page = _page;
			this.getList();
		},
    handleCloseModal() {
      this.form_validate = true;
      this.label = {
        name: '',
        describe: '',
        file: '',
        active: 1
      }

      this.file_show = {
        name: '',
        url: ''
      }

      this.error_name = '';

      setTimeout(() => {
        this.form_validate = false;
      }, 300);
    },
    getInfoLabel(_label) {
      this.label = _.clone(_label); 
      this.file_show.name = 'Xem tệp';
      this.file_show.url = _label.file;
    },
    handleGetFile(_file) {
      this.label.file = _file.target.files[0];
      this.file_show.name = _file.target.files[0].name;
      this.file_show.url = URL.createObjectURL(_file.target.files[0]);
    },
    onSubmit() {
      this.checkName();
      if (this.label.name != '') {
        this.loading_add_update = true;
        let formData = new FormData();
        formData.append('name', this.label.name);
        formData.append('describe', this.label.describe);
        formData.append('file', this.label.file);
        formData.append('active', this.label.active);

        if (this.label.id > 0) { // Cập nhật nhãn

          this.$root.api.post(`label/update/${this.label.id}`, formData).then(res => {
            this.loading_add_update = false;
            if (res.data.status == "OK") {
              this.$root.notify(res.data.message, 'success');
              this.getList();
              $('#label_add_update').modal('hide');
            } else {
              this.$root.showError(res.data.error);
            }
          }).catch(err => {
            this.$root.showError(err);
            this.loading_add_update = false;
          })

        } else { // Thêm nhãn

          this.$root.api.post('label/add', formData).then(res => {
            this.loading_add_update = false;
            if (res.data.status == "OK") {
              this.$root.notify(res.data.message, 'success');
              this.getList();
              $('#label_add_update').modal('hide');
            } else {
              this.$root.showError(res.data.error);
            }
          }).catch(err => {
            this.$root.showError(err);
            this.loading_add_update = false;
          })
        }
      }
    },
    checkName() {
      if (this.label.name == '') {
        this.error_name = 'Tên nhãn là bắt buộc';
      } else {
        this.error_name = '';
      }
    },
    onSubmitDelete() {
      this.loading_delete = true;
      this.$root.api.delete(`label/delete/${this.label.id}`).then(res => {
        this.loading_delete = false;
        if (res.data.status == "OK") {
          this.$root.notify(res.data.message, 'success');
          this.getList();
          $('#label_delete').modal('hide');
        } else {
          this.$root.showError(res.data.error);
        }
      }).catch(err => {
        this.$root.showError(err);
        this.loading_delete = false;
      })
    }
  },
  created() {
    this.handleCloseModal();
  },
  mounted() {
    if (this.$root.auth.role.level != 1 && this.$root.auth.role.level != 2) {
      this.$router.replace({ name: 'dashboard' });
    }

    this.current_page = 1;  
    $(document).on('hidden.bs.modal', '#label_details, #label_add_update', () => {
      this.handleCloseModal();
    });

    this.getList();
  },
  watch: {
    'label.name'() {
      if (this.form_validate) return;
      this.checkName();
    }
  }
}
</script>
<template>
  <div id="label">
    <form @submit.prevent="getList">
      <div class="row">
        <div class="col-md-5 col-sm-8 col-8 mb-2">
          <input type="text" class="form-control form-control-sm" placeholder="Tên nhãn..." v-model="search_name">
        </div>
        <div class="col-md-3 col-sm-4 col-4 mb-2"> 
          <button type="submit" class="btn btn-info btn-sm">
            <i class="fas fa-search"></i> Tìm
          </button>
        </div>  
        <div class="col-md-4 col-sm-12 col-12 text-right mb-2" v-if="$root.isManager()">
          <button class="btn btn-info btn-sm" data-toggle="modal" data-target="#label_add_update">Thêm</button>
        </div>
      </div>
    </form>
    
    <div class="text-center" v-if="loading_get_list">
      <m-spinner />
    </div>
    <div class="list" v-else>
      <ul class="row" v-if="list && list.data.length > 0">
        <li v-for="(item, index) in list.data" :key="index" class="col-md-3 col-sm-6 col-xs-12 mb-2">
          <div class="item">
            <div class="mb-2">
              <i class="fas fa-tag"></i>
              <b>{{ item.name }}</b>
            </div>
            <div>
              Tệp đính kèm: 
              <a target="_blank" :href="item.file" v-if="item.file">Xem tệp</a>
              <span v-else>Không có</span>
            </div>
            <div>
              <span :class="[item.active ? 'badge-success' : 'badge-danger', 'badge']">
                {{ item.active ? 'Kích hoạt' : 'Khóa'}}
              </span>
            </div>
            <div>
              <i class="far fa-clock" style="font-size: 12px"></i>
              {{ item.created_at }}
            </div>
            <div class="text-right mt-2">
              <button class="btn btn-sm btn-dark" @click="getInfoLabel(item)" data-toggle="modal" data-target="#label_details">Xem</button>
              <button class="btn btn-sm btn-info" @click="getInfoLabel(item)" data-toggle="modal" data-target="#label_add_update">Sửa</button>
              <button class="btn btn-sm btn-danger" @click="getInfoLabel(item)" data-toggle="modal" data-target="#label_delete">Xóa</button>
            </div>
          </div>
        </li>
      </ul>
      <div v-else><b>Không có nhãn</b></div>
    </div>

    <div class="modal fade" id="label_add_update">
      <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">Nhãn</h4>
            <button type="button" class="close" data-dismiss="modal">&times;</button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="onSubmit">
              <div class="form-group">
                <label><b>Tên </b><span class="text-danger">* </span></label>
                <input type="text" class="form-control form-control-sm" v-model="label.name">
                <div class="text-danger font-italic error">{{ error_name }}</div>
              </div>

              <div class="form-group">
                <label><b>Mô tả </b></label><br>
                <textarea type="text" rows="5" class="form-control" v-model="label.describe"></textarea>
              </div>

              <div class="form-group">
                <label><b>Tập đính kèm </b></label><br>
                <button type="button"
                  style="padding: 2px 5px"
                  class="btn btn-info btn-sm"
                  @click="$refs.ref_file.click()">Chọn tập tin</button>
                <input id="label_file" type="file" ref="ref_file" style="display: none" @change="handleGetFile" />

                <div class="show" v-if="file_show && file_show.name">
                  <b>File đã chọn: </b>
                  <a v-if="file_show.url" target="blank" :href="file_show.url">{{ file_show.name }}</a>
                  <span v-else>Không có</span>
                </div>
              </div>

              <div class="form-group">
                <label><b>Trạng thái:</b><span class="text-danger">*</span></label>
                <br>
                <div class="form-check-inline">
                  <label class="form-check-label">
                    <input type="radio" class="form-check-input" v-model.number="label.active" value="1">Kích hoạt
                  </label>
                </div>
                <div class="form-check-inline">
                  <label class="form-check-label">
                    <input type="radio" class="form-check-input" v-model.number="label.active" value="0">Khóa
                  </label>
                </div>
              </div>

              <div class="modal-footer">
                <button type="submit" class="btn btn-info btn-sm">
                      {{ label.id ? 'Cập nhật' : 'Thêm nhãn'}}
                    </button>
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Đóng</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="label_details">
      <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">Thông tin nhãn</h4>
            <button type="button" class="close" data-dismiss="modal">&times;</button>
          </div>
          <div class="modal-body" v-if="label">
            <div class="form-group">
              <b class="text-info">Tên: {{ label.name }}</b>
            </div>

            <div class="form-group" v-if="label.describe">
              <b>Mô tả: </b> <span>{{ label.describe }}</span>
            </div>

            <div class="form-group" v-if="label.file">
              <b>Tệp đính kèm: </b> 
              <a target="_blank" :href="label.file">Xem tệp</a>
            </div>

            <div class="form-group">
              <b>Trạng thái nhãn: </b>
              <span :class="['badge', label.active ? 'badge-success' : 'badge-danger']">
                {{ label.active ? 'Kích hoạt' : 'Khóa' }}
              </span>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Đóng</button>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="label_delete">
      <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">Xóa nhãn</h4>
            <button type="button" class="close" data-dismiss="modal">&times;</button>
          </div>
          <div class="modal-body" v-if="label">
            <div class="d-flex justify-content-start">
                <i class="fas fa-exclamation-triangle text-danger icon-warm-delete"></i>
                <span>
                  Bạn có muốn xóa nhãn <b>{{ label.name }} không?</b>
                </span>
              </div>
          </div>
          <div class="modal-footer">
            <button type="button" @click="onSubmitDelete" class="btn btn-sm btn-danger">Xóa nhãn</button>
            <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Đóng</button>
          </div>
        </div>
      </div>
    </div>

    <m-loading v-if="loading_add_update" :title="label.id != null ? 'Đang cập nhật nhãn' : 'Đang thêm nhãn'" :full="true" />
    <m-loading v-if="loading_delete" title="Đang xóa nhãn" :full="true" />
  </div>
</template>