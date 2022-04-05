<script>
export default {
  data() {
    return {
      params_route: '',
      data: null
    }
  },
  methods: {
    getHistory() {
      this.$root.api.post(`project/${this.params_route.project_id}/task/${this.params_route.task_id}/history/${this.params_route.job_id}`).then(res => {
        if (res.data.status == "OK") {
          this.data = res.data.data;
        } else {
          this.$root.notify(res.data.error, 'error');
        }
      }).catch (err => {
        console.log(err);
      })
    }
  },
  mounted() {
    this.params_route = this.$route.params;
    this.getHistory();
  }
}
</script>

<template>
  <div id="history">

    <nav aria-label="breadcrumb">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><router-link :to="{ name: 'project' }">Dự án</router-link>  </li>
        <li class="breadcrumb-item">
          <router-link :to="{name: 'task', params: { 'id': data.project.id }}">{{ data.project.name }}</router-link>
        </li>
        <li class="breadcrumb-item active" aria-current="page">Lịch sử {{ data.job.name }}</li>
      </ol>
    </nav>
    
    <div class="list">
      <div class="card">
        <div class="card-header bg-info text-white">Lịch sử: Thời gian thực hiện: {{ data.job.start_time }} - {{ data.job.end_time }}</div>
          <div class="table-responsive">
            <table class="table table-bordered table-stripped mb-0">
              <thead>
                <tr>
                  <td><b>Thời gian</b></td>
                  <td><b>Trạng thái</b></td>
                  <td><b>Người thực hiện</b></td>
                  <td><b>Phản hồi</b></td>
                  <td><b>Ghi chú</b></td>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(item, index) in data.status" :key="index">
                  <td>{{ item.created_at }}</td>
                  <td>
                    <span v-if="item.status == 3" class="badge badge-success">
                      {{ $root.getStatusTaskName(item.status) }}
                    </span>
                    <span v-else-if="item.status == 7" class="badge badge-warning">
                      {{ $root.getStatusTaskName(item.status) }}
                    </span>
                    <span v-else>
                      {{ $root.getStatusTaskName(item.status) }}
                    </span>
                  </td>
                  <td>{{ item.user.fullname || item.user.username }}</td>
                  <td>{{ item.content }}</td>
                  <td>
                    <span v-if="item.status == 3">
                      <b v-if="data.job.delay_time == 0" class="badge badge-success">Hoàn thành đúng hạn</b>
                      <b v-else class="badge badge-warning">Hoàn thành trễ {{ data.job.delay_time }} ngày</b> 
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
      </div>
    </div>
  </div>
</template>