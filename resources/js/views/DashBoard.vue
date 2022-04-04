<script>
  export default {
    data() {
      return {
        data: null,
        arr_pie_job_chart: [],
        arr_pie_task_chart: [],
        arr_line_chart: [],
        loading_chart: false
      }
    },
    methods: {
      getNumber() {
        this.$root.api.get('dashboard/get-number').then(res => {
          if (res.data.status == "OK") {
            this.data = res.data.data;
          } else {
            this.$root.notify(res.data.error, 'error');
          }
        }).catch (err => {
          this.$root.notify(err, 'error');
        })
      },
      getDataForChart() {
        this.loading_chart = true;
        this.$root.api('dashboard/get-percent-job').then(res => {
          this.loading_chart = false;
          if (res.data.status == "OK") {
            this.arr_pie_job_chart = res.data.data.data_pie_job_chart;
            this.arr_pie_task_chart = res.data.data.data_pie_task_chart;
            this.arr_line_chart = res.data.data.data_line_chart;

            var arr_color = [ '#fc2020', '#1e6d53', '#068f25', '#d6a820', '#06618f'];
            this.pieChart(this.arr_pie_job_chart, arr_color, 'pie_chart_job_div');
            this.pieChart(this.arr_pie_task_chart, arr_color, 'pie_chart_task_div');
            this.lineChartTaskJob();
          } else {
            console.log(res.data.data);
          }
        }).catch (err => {
          console.log(err);
          this.loading_chart = false;
        })
      },
      lineChartTaskJob() {
        const labels = this.arr_line_chart.labels;
        const data = {
          labels: labels,
          datasets: this.arr_line_chart.data_label
        };

        const config = {
          type: 'line',
          data: data,
          options: {}
        };

        const line_chart_div = new Chart(
          document.getElementById('line_chart_div'),
          config
        );
      },
      pieChart(_arr, _arr_color, _id_html) {
        const data = {
          labels: _arr.labels,
          datasets: [{
            label: 'My First Dataset',
            data: _arr.data,
            backgroundColor: _arr_color,
            hoverOffset: 4
          }]
        };

        const config = {
          type: 'doughnut',
          data: data,
        };

        const pie_chart_div = new Chart(
          document.getElementById(_id_html),
          config,
        );
      }

    },
    mounted() {
      this.getNumber();
      this.getDataForChart();
    }
  }
</script>

<template>
  <div id="dashboard">
    <div class="number mb-3" v-if="data">
      <div class="row">
        <div class="col-md-3 col-sm-4 col-xs-12 mb-2">
          <div class="item">
            <div class="top"><b>DỰ ÁN</b></div>
            <div class="bottom d-flex justify-content-start" v-if="data && data.project">
              <h3>{{ data.project.total }}</h3>
              <ul>
                <li class="text-success">{{ data.project.finished }} Đã hoàn thành</li>
                <li class="text-info">{{ data.project.future }} Tương lai</li>
                <li>{{ data.project.working }} Đang thực hiện</li>
                <li class="text-danger">{{ data.project.late }} Trễ</li>
              </ul>
            </div>
          </div>
        </div>

        <div class="col-md-3 col-sm-4 col-xs-12 mb-2">
          <div class="item">
            <div class="top"><b>PHÒNG BAN</b></div>
           <div class="bottom d-flex justify-content-start" v-if="data && data.department">
              <h3>{{ data.department.total }}</h3>
              <ul>
                <li>{{ data.department.user_total }} Thành viên</li>
                <li class="text-success">{{ data.department.user_active }} Đang hoạt động</li>
                <li class="text-danger">{{ data.department.user_lock }} Bị khóa</li>
              </ul>
            </div>
          </div>
        </div>

        <div class="col-md-3 col-sm-4 col-xs-12 mb-2">
          <div class="item">
            <div class="top"><b>NGƯỜI DÙNG</b></div>
            <div class="bottom d-flex justify-content-start" v-if="data && data.user">
              <h3>{{ data.user.total }}</h3>
              <ul>
                <li class="text-success">{{ data.user.active }} Đang hoạt động</li>
                <li class="text-danger">{{ data.user.lock }} Bị khóa</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
    <m-spinner v-else />

    <div class="dashboard-01">
      <div class="row">
        <div class="col-md-3 col-sm-6 col-12 mb-4">
          <m-spinner v-if="loading_chart" />
          <b v-else>Tỷ lệ công việc tháng hiện tại</b>
          <canvas id="pie_chart_task_div"></canvas>
        </div>
        <div class="col-md-3 col-sm-6 col-12 mb-4">
          <m-spinner v-if="loading_chart" />
          <b v-else>Tỷ lệ nhiệm vụ tháng hiện tại</b>
          <canvas id="pie_chart_job_div"></canvas>
        </div>
        <div class="col-md-6 col-sm-12 col-12 mb-2">
          <m-spinner v-if="loading_chart" />
          <b v-else>Số lượng công việc, nhiệm vụ được tạo tháng hiện tại</b>
          <canvas id="line_chart_div"></canvas>
        </div>
      </div>
    </div>

    <!-- <div class="dashboard-02">
      <div class="row">
        <div class="col-md-3 col-sm-4 col-12">
          <b>Phòng ban có công việc trễ nhiều nhất</b>
        </div>

        <div class="col-md-3 col-sm-4 col-12">
          <b>Thành viên có nhiệm vụ trễ nhiều nhất</b>
        </div>
      </div>
    </div> -->
  </div>
</template>