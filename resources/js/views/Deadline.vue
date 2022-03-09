<script>
export default {
  data() {
    return {
      list: null,
      route_name: '',
      loading_list: false
    }
  },
  methods: {
    getJobLateOrToday() {
      this.loading_list = true;
      let st = 'today';
      if (this.route_name == 'tre')
        st = 'late'
      else if (this.route_name == 'dang-thuc-hien') 
        st = 'working'

      this.$root.api.post(`job/late-today-working/${st}`).then(res => {
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
      this.route_name = this.$route.params.name;
      if ((this.route_name != 'hom-nay' && this.route_name != 'tre' && this.route_name != 'dang-thuc-hien') || this.$root.isAdmin()) {
        this.$router.replace({ name: 'dashboard' });
      }
      this.getJobLateOrToday();
    }
  },
  mounted() {
    this.getInfo();
  },
  watch: {
    '$route.params.name'() {
      this.getInfo();
    }
  }
}
</script>

<template>
  <div id="deadline">
    <div class="list">
      <div class="card">
        <div class="card-header bg-info text-white">Danh sách {{ route_name == 'tre' ? ' trễ' : 'hôm nay'}}</div>

        <div class="table-responsive">
          <table class="table table-bordered table-stripped mb-0">
            <thead>
              <tr>
                <td><b>STT</b></td>
                <td><b>Tên</b></td>
                <td><b>Trạng thái</b></td>
                <td><b>Bắt đầu</b></td>
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
                    <td>{{ item.name }}</td>
                    <td>
                      <span v-if="item.active == 0" class="badge badge-danger">Khóa</span>
                      <span v-else class="badge badge-success">{{ $root.getStatusTaskName(item.status.status) }}</span>
                      <span :class="['badge', $root.checkDeadline(item) == 'Chưa tới hạn' ? 'badge-info' : 'badge-danger']">
                        {{ $root.checkDeadline(item) }}
                      </span>
                    </td>
                    <td>{{ item.start_time }}</td>
                    <td>{{ item.created_at }}</td>
                    <td>
                      <router-link :to="{name: 'task', params: { 'id': item.id }}">
                        <button class="mb-1 btn btn-sm btn-dark">Xem</button>
                      </router-link>
                    </td>
                  </tr>
                </template>
                <template v-if="$root.auth.role.level == 4">
                  <tr v-for="(item, index) in list" :key="index">
                    <td>{{ index + 1}} </td>
                    <td>{{ item.job.name }}</td>
                    <td>
                      <span class="badge badge-info" v-if="item.job.status.status == 0 || item.job.status.status == 1">{{ $root.getStatusTaskName(item.job.status.status) }}</span>
                      <span class="badge badge-danger" v-else>{{ $root.getStatusTaskName(item.job.status.status) }}</span>
                      <span :class="['badge', $root.checkDeadline(item.job) == 'Chưa tới hạn' ? 'badge-info' : 'badge-danger']">
                        {{ $root.checkDeadline(item.job) }}
                      </span>
                    </td>
                    <td>{{ item.job.start_time }}</td>
                    <td>{{ item.job.created_at }}</td>
                    <td>
                      <router-link :to="{name: 'job', params: { 'project_id': item.project.id, 'id': item.task.id }}">
                        <button v-if="item.job && item.job.status.status != 0" class="mb-1 btn btn-dark btn-sm">Xem</button>
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