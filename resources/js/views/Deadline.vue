<script>
export default {
  data() {
    return {
      list: null,
      route_name: '',
      loading_list: false,
      title_text: {
        type: 'dự án',
        status: 'tới hạn'
      }
    }
  },
  methods: {
    getWork() {
      this.loading_list = true;
      let _status = 'today';
      if (this.route_name.status == 'tre')  { _status = 'late'; this.title_text.status = 'trễ' }
      else if (this.route_name.status == 'dang-thuc-hien')  { _status = 'working'; this.title_text.status = 'đang thực hiện' }

      let _type = 'project';
      if (this.route_name.type == 'cong-viec')  { _type = 'task'; this.title_text.type = 'công việc' }
      else if (this.route_name.type == 'nhiem-vu') { _type = 'job'; this.title_text.type = 'nhiệm vụ' }

      this.$root.api.post(`job/get-work/${_type}/${_status}`).then(res => {
        this.loading_list = false;
        if (res.data.status == "OK") {
          this.list = res.data.data;
        } else {
          this.$root.showError(res.data.error);
        }
      }).catch(err => {
        this.loading_list = false;
        this.$root.showError(res.data.err);
      })
    },
    getInfo() {
      this.route_name = this.$route.params;
      let _arr1 = ['du-an', 'cong-viec', 'nhiem-vu'];
      let _arr2 = ['tre', 'toi-han', 'dang-thuc-hien'];
      if (this.$root.isAdmin() || (!_arr1.includes(this.route_name.type) || !_arr2.includes(this.route_name.status))) {
        this.$router.replace({ name: 'dashboard' });
      }
      
      this.getWork();
    }
  },
  mounted() {
    this.getInfo();
  },
  watch: {
    '$route.params'() {
      this.getInfo();
    }
  }
}
</script>

<template>
  <div id="deadline">
    <div class="list">
      <div class="card">
        <div class="card-header bg-info text-white">
          Danh sách {{ title_text.type }} {{ title_text.status }}
        </div>

        <div class="table-responsive">
          <table class="table table-bordered table-stripped mb-0">
            <thead>
              <tr>
                <td><b>STT</b></td>
                <td><b>Tên</b></td>
                <td><b>Trạng thái</b></td>
                <td><b>Bắt đầu</b></td>
                <td><b>Hoàn thành trước</b></td>
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
                <template v-if="$root.auth.role.level == 3">
                  <tr v-for="(item, index) in list" :key="index">
                    <td>{{ index + 1}} </td>
                    <td>{{ item.task ? item.task.name : item.project.name }}</td>
                    <td>
                      <template v-if="item.task">
                        <span class="badge badge-success">{{ $root.getStatusTaskName(item.task.status.status) }}</span>

                        <span :class="['badge', $root.checkDeadline(item.task) == 'Chưa tới hạn' ? 'badge-info' : 'badge-danger']">
                          {{ $root.checkDeadline(item.task) }}
                        </span>
                      </template>

                      <template v-else>
                        <span v-if="item.project.active == 0" class="badge badge-danger">Khóa</span>
                        <span v-else class="badge badge-success">{{ $root.getStatusTaskName(item.project.status.status) }}</span>

                        <span :class="['badge', $root.checkDeadline(item.project) == 'Chưa tới hạn' ? 'badge-info' : 'badge-danger']">
                          {{ $root.checkDeadline(item.project) }}
                        </span>
                      </template>
                    </td>
                    <td>{{ item.task ? item.task.start_time : item.project.start_time }}</td>
                    <td>{{ item.task ? item.task.end_time : item.project.end_time }}</td>
                    <td>{{ item.task ? item.task.created_at : item.project.created_at }}</td>
                    <td>
                      <router-link v-if="item.task" :to="{name: 'task', params: { 'id': item.project.id }, query: { 'name' : item.task.name }}">
                        <button class="mb-1 btn btn-sm btn-dark">Xem</button>
                      </router-link>

                      <router-link v-else :to="{name: 'project', query: { 'name': item.project.name }}">
                        <button class="mb-1 btn btn-sm btn-dark">Xem</button>
                      </router-link>
                    </td>
                  </tr>
                </template>


                <template v-else-if="$root.auth.role.level == 4">
                  <tr v-for="(item, index) in list" :key="index">
                    <td>{{ index + 1}} </td>
                    <td>{{ item.job ? item.job.name : item.task.name }}</td>
                    <td>
                      <template v-if="item.job">
                        <span class="badge badge-info" v-if="item.job.status.status == 0 || item.job.status.status == 1">{{ $root.getStatusTaskName(item.job.status.status) }}</span>

                        <span v-else class="badge badge-danger">{{ $root.getStatusTaskName(item.job.status.status) }}</span>

                        <span :class="['badge', $root.checkDeadline(item.job) == 'Chưa tới hạn' ? 'badge-info' : 'badge-danger']">
                          {{ $root.checkDeadline(item.job) }}
                        </span>
                      </template>

                      <template v-else>
                        <span class="badge badge-info" v-if="item.task.status.status == 0 || item.task.status.status == 1">{{ $root.getStatusTaskName(item.task.status.status) }}</span>
                        <span class="badge badge-danger" v-else>{{ $root.getStatusTaskName(item.task.status.status) }}</span>
                        <span :class="['badge', $root.checkDeadline(item.task) == 'Chưa tới hạn' ? 'badge-info' : 'badge-danger']">
                          {{ $root.checkDeadline(item.task) }}
                        </span>
                      </template>
                    </td>
                    <td>{{ item.job ? item.job.start_time : item.task.start_time }}</td>
                    <td>{{ item.job ? item.job.end_time : item.task.end_time }}</td>
                    <td>{{ item.job ? item.job.created_at : item.task.created_at }}</td>
                    <td>
                      <router-link v-if="item.job" :to="{name: 'job', params: { 'project_id': item.project.id, 'id' : item.task.id }, query: { 'name' : item.job.name }}">
                        <button class="mb-1 btn btn-dark btn-sm">Xem</button>
                      </router-link>

                      <router-link v-else :to="{name: 'task', params: { 'id': item.project.id }, query: { 'name' : item.task.name }}">
                        <button class="mb-1 btn btn-dark btn-sm">Xem</button>
                      </router-link>
                    </td>
                  </tr>
                </template>
              </template>
              <tr v-else>
                <td colspan="1000" align="center">
                  <b>Không có danh sách</b>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>