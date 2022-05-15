<script>
export default {
  data() {
    return {
      params_route: '',
      data: null,
      history_name: ''
    }
  },
  methods: {
    getHistoryJob() {
      this.$root.api.post(`project/${this.params_route.project_id}/task/${this.params_route.task_id}/history/${this.params_route.job_id}`).then(res => {
        if (res.data.status == "OK") {
          this.data = res.data.data;
        } else {
          this.$root.notify(res.data.error, 'error');
          this.$router.replace({name: 'job', params: { 'project_id': this.params_route.project_id, 'id' : this.params_route.task_id }});
        }
      }).catch (err => {
        console.log(err);
      })
    },
    getHistoryTask() {
      this.$root.api.post(`project/${this.params_route.project_id}/task/history/${this.params_route.task_id}`).then(res => {
        if (res.data.status == "OK") {
          this.data = res.data.data;
        } else {
          this.$root.notify(res.data.error, 'error');
          this.$router.replace({name: 'task', params: { 'id': this.params_route.project_id }});
        }
      }).catch (err => {
        console.log(err);
      })
    },
    getHistoryProject() {
      this.$root.api.post(`project/history/${this.params_route.project_id}`).then(res => {
        if (res.data.status == "OK") {
          this.data = res.data.data;
        } else {
          this.$root.notify(res.data.error, 'error');
          this.$router.replace({name: 'project'});
        }
      }).catch (err => {
        console.log(err);
      })
    }
  },
  mounted() {
    this.params_route = this.$route.params;
    if (this.params_route.job_id) {
      this.getHistoryJob();
      this.history_name = 'job';
    } else if (this.params_route.task_id) {
      this.getHistoryTask();
      this.history_name = 'task';
    } else if (this.params_route.project_id) {
      this.getHistoryProject();
      this.history_name = 'project';
    }
  }
}
</script>

<template>
  <div id="history">
    <nav aria-label="breadcrumb" v-if="data">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><router-link :to="{ name: 'project' }">Dự án</router-link>  </li>
        <li class="breadcrumb-item">
          <router-link
            v-if="history_name == 'job' || history_name == 'task'"
            :to="{name: 'task', params: { 'id': data.project.id }}">
            {{ data.project.name }}
          </router-link>
          <span 
            v-else-if="history_name == 'project'"
            class="breadcrumb-item active"
            aria-current="page">
            Lịch sử dự án {{ data.project.name }}
          </span>
        </li>
        <li class="breadcrumb-item">
          <router-link 
            v-if="history_name == 'job'"
            :to="{name: 'job', params: { 'project_id': data.project.id, 'id' : data.task.id }}">
            {{ data.task.name }}
          </router-link>
          <span 
            v-else-if="history_name == 'task'"
            class="breadcrumb-item active"
            aria-current="page">
            Lịch sử công việc {{ data.task.name }}
          </span>
        </li>
        <li v-if="history_name == 'job'" class="breadcrumb-item active" aria-current="page">Lịch sử nhiệm vụ {{ data.job.name }}</li>
      </ol>
    </nav>
    
    <div class="list" v-if="data && data.status.length > 0">
      <div class="card">
        <div class="card-header bg-info text-white">
          Lịch sử: Thời gian thực hiện: 
          <span v-if="history_name=='job'">{{ data.job.start_time }} - {{ data.job.end_time }}</span>
          <span v-else-if="history_name=='task'">{{ data.task.start_time }} - {{ data.task.end_time }}</span>
          <span v-else-if="history_name=='project'">
            {{ data.project.start_time }} - {{ data.project.end_time }}
            - Tạo dự án: {{ data.created_by.fullname || data.created_by.username }}
          </span>
        </div>
        <div class="table-responsive">
          <table class="table table-bordered table-stripped mb-0">
            <thead>
              <tr>
                <td><b>Thời gian</b></td>
                <td><b>Trạng thái</b></td>
                <td><b>Người thực hiện</b></td>
                <td v-if="history_name == 'task'"><b>Nhóm làm việc</b></td>
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

                  <span v-if="(item.status == 4) && (history_name == 'job' || history_name == 'task')" class="badge badge-danger">
                    {{ $root.getStatusTaskName(item.status) }}
                  </span>

                  <span v-else-if="item.status == 7" class="badge badge-warning">
                    {{ $root.getStatusTaskName(item.status) }}
                  </span>
                  <span v-else>
                    {{ $root.getStatusTaskName(item.status) }}
                  </span>
                </td>
                <td>
                  <span v-if="history_name == 'job' || history_name == 'task'">
                    {{ item.user.info.fullname || item.user.info.username }}
                  </span>

                  <span v-else-if="history_name == 'project'">
                    <template v-if="item.status == 0">
                      {{ data.created_by.fullname || data.created_by.username }}  
                    </template>
                    <template v-else>
                      {{ data.manager.fullname || data.manager.username }}
                    </template>
                  </span>
                </td>
                <td v-if="history_name == 'task'">{{ item.department.name }}</td>
                <td>{{ item.content }}</td>
                <td>
                  <span v-if="history_name != 'project'">{{ item.user.position }}</span>

                  <span v-if="item.status == 3">
                    <template v-if="history_name == 'job'">
                      <b v-if="data.job.delay_time == 0" class="badge badge-success">Hoàn thành đúng hạn</b>
                      <b v-else class="badge badge-warning">Hoàn thành trễ {{ data.job.delay_time }} ngày</b>
                    </template>

                    <template v-else-if="history_name == 'task'">
                      <b v-if="data.task.delay_time == 0" class="badge badge-success">Hoàn thành đúng hạn</b>
                      <b v-else class="badge badge-warning">Hoàn thành trễ {{ data.task.delay_time }} ngày</b> 
                    </template>

                    <template v-else-if="history_name == 'project'">
                      <b v-if="data.project.delay_time == 0" class="badge badge-success">Hoàn thành đúng hạn</b>
                      <b v-else class="badge badge-warning">Hoàn thành trễ {{ data.project.delay_time }} ngày</b> 
                    </template>
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <m-spinner v-else class="mb-2"/>
  </div>
</template>