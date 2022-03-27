<script>
export default {
  data() {
    return {
      name_route: {
        'name': '',
        'id': ''
      },
      data: null,
      info: null,
      loading_get_date: false
    }
  },
  methods: {
    getInfo() {
      // lấy thông tin của project or task
      this.$root.api.post(`chart/get-info/${this.name_route.name}/${this.name_route.id}`).then(res => {
        if (res.data.status == "OK") {
          this.info = res.data.data;
        } else {
          this.$router.replace({ name: 'dashboard' });
        }
      }).catch (err => {
        this.$router.replace({ name: 'dashboard' });
      })
    },
    getData(_name) {
      this.loading_get_date = true;
      if (_name == 'project') {
        this.$root.api.post(`chart/get-chart-project/${this.name_route.id}`).then(res => {
          this.loading_get_date = false;
          if (res.data.status == "OK") {
            if (res.data.data && res.data.data.length > 0) {
              this.data = res.data.data;
              this.$root.ganttChart('chart_div', this.data);
            }
          } else {
            this.$root.notify(res.data.error, 'error');
          }
        }).catch(err => {
          this.$root.notify(err, 'error');
          this.loading_get_date = false;
        })
      } else if (_name == 'task') {
        this.$root.api.post(`chart/get-chart-task/${this.name_route.id}`).then(res => {
          this.loading_get_date = false;
          if (res.data.status == "OK") {
            if (res.data.data && res.data.data.length > 0) {
              this.data = res.data.data;
              this.$root.ganttChart('chart_div', this.data);
            }
          } else {
            this.$root.notify(res.data.error, 'error');
          }
        }).catch(err => {
          this.$root.notify(err, 'error');
          this.loading_get_date = false;
        })
      }
    }
  },
  mounted() {
    this.name_route.name = this.$route.params.name;
    this.name_route.id = this.$route.params.id;
    if (this.name_route.name != 'du-an' && this.name_route.name != 'cong-viec' && this.name_route.id == '') {
      this.$router.replace({ name: 'dashboard' });
    }

    this.getInfo();
    if (this.name_route.name == 'du-an')
      this.getData('project');
    else if (this.name_route.name == 'cong-viec')
      this.getData('task');
  }
}
</script>

<template>
  <div id="chart">
    <div v-if="info">
      <h3 style="font-size: 17px">{{ name_route.name == 'du-an' ? 'Dự án' : 'Công việc' }} "{{ info.name }}"</h3>
      <p class="mb-2"><b>Thời gian: </b> {{ info.start_time }} - {{ info.end_time }}</p>
      <p class="mb-2"><b>Biểu đồ</b></p>
    </div>
    <m-spinner v-else/>

    <template v-if="!loading_get_date">
      <div v-if="data && data.length > 0" class="table-responsive">
        <div id="chart_div"></div>
      </div>

      <div v-else>Chưa có {{ name_route.name == 'du-an' ? ' công việc' : ' nhiệm vụ' }}</div>
    </template>
    <m-spinner v-else/>
  </div>
</template