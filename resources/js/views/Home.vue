<script>
export default {
  data() {
    return {
      change_bar: false,
      menu: null,
      name_route: '',
      loading_logout: false,
      number_deadline: 0
    }
  },
  methods: {
    handleSideBar() {
      this.change_bar = !this.change_bar;
    },
    handleCloseSideBar() {
      this.change_bar = false;
    },
    handleLogout() {
      this.loading_logout = true;
      this.$root.api.get('logout').then(res => {
        if (res.data.status == "OK") {
          this.$root.setAuth(null);
          localStorage.removeItem('yl_token');
          this.$notify(res.data.message, 'success');
        } else {
          this.$alert(res.data.error, '', 'error');
        }
        this.loading_logout = false;
      }).catch(err => {
        this.loading_logout = false;
        this.$root.showError(err);
      })
    },
    createMenu() {
      this.menu = [
        {
          label: 'Thống kê',
          link: "dashboard",
          icon: "fas fa-tachometer-alt",
        },
        {
          label: 'Dự án',
          link: "project",
          icon: "fas fa-folder",
        },
        {
          label: 'Phòng ban',
          link: "department",
          icon: "fas fa-folder",
        },
      ]
      if (this.$root.isAdmin()) {
        this.menu.push({
          label: 'Người dùng',
          link: "user",
          icon: "fas fa-users",
        });

        this.menu.push({
          label: 'Nhãn',
          link: "label",
          icon: "fas fa-tags",
        });
      }
      this.menu.push({
        label: 'Cá nhân',
        link: "profile",
        icon: "fas fa-user-circle",
      },
      {
        label: 'Chat',
        link: "chat",
        icon: "fas fa-comment-dots",
      })
    },
    closeSideBarTablet() {
      this.change_bar = false;
    },
    getNumberJob() {
      this.$root.api.get('job/number-job').then(res => {
        if (res.data.status == "OK") {
          this.number_deadline = res.data.data;
        }
      }).catch(err => {
        console.log(err);
      })
    }
  },
  watch: {
    '$route': function(_new, _old) {
      this.name_route = _new.name;
    }
  },
  mounted() {
    this.name_route = this.$root.$route.name;
    if (!this.$root.isAdmin()) {
      this.getNumberJob();
    }
  },
  created() {
    this.createMenu();
  }
}
</script>

<template>
  <div :class="[change_bar ? 'side-bar-small' : '' , 'wrap']">
    <div class="wrap-bg" @click="handleCloseSideBar"></div>
    <div id="side-bar">
      <div class="top text-center mt-3 pb-3">
        <div class="avatar">
          <router-link :to="{ name: 'profile' }">
            <img v-if="$root.auth.avatar" :src="$root.auth.avatar" alt="">
            <img v-else :src="$root.avatar_default" alt="">
          </router-link>
        </div>
        <div class="role pt-1">
          <b>{{ $root.auth.fullname }}</b><br>
          <span class="text-light">( {{ $root.auth.role.name }} )</span>
        </div>
      </div>
      
      <ul class="menu scrollbar" role="tablist">
        <li v-for="(item, index) in menu"
          :key="index"
          :class="[ name_route == item.link ? 'router-link-exact-active' : '' ]"
          :title="item.label"
          @click="closeSideBarTablet"
        >
          <router-link :to="{ name: item.link }" class="nav-link">
            <i :class="item.icon"></i>
            <span class="ml-2">{{ item.label }}</span>
          </router-link>
        </li>
      </ul>
    </div>
    <div id="main">
      <div class="header">
        <div class="d-flex justify-content-between">
          <i class="fas fa-bars pt-2" style="font-size: 25px" @click="handleSideBar"></i>
          <div>
            <div class="btn-group mr-4 clock-icon" title="Nhiệm vụ" v-if="number_deadline && !$root.isAdmin()">
              <div data-toggle="dropdown">
                <i class="far fa-clock" style="font-size: 18px"></i>
                <div class="number-job">{{ number_deadline.count }}</div>
              </div>
              <div class="dropdown-menu">
                <div class="pl-2 pr-2">
                  <div>
                    <template v-if="$root.auth.role.level == 3">
                    <b style="font-size: 12px">Dự án</b>
                    <div class="d-flex justify-content-between mb-1">
                      <router-link :to="{ name: 'deadline', params: { type: 'du-an', status: 'tre'} }">
                        <span>{{ number_deadline.project.late }} Trễ</span>
                      </router-link>

                      <router-link :to="{ name: 'deadline', params: { type: 'du-an', status: 'toi-han'} }">
                        <span>{{ number_deadline.project.today }} Tới hạn</span>
                      </router-link>

                      <router-link :to="{ name: 'deadline', params: { type: 'du-an', status: 'dang-thuc-hien'} }">
                        <span>{{ number_deadline.project.working }} Đang thực hiện</span>
                      </router-link>
                    </div>
                    </template>
                    
                    <template v-if="$root.auth.role.level == 3 || ($root.auth.role.level == 4 && $root.auth.leader)">
                    <b style="font-size: 12px">Công việc</b>
                    <div class="d-flex justify-content-between mb-1">
                      <router-link :to="{ name: 'deadline', params: { type: 'cong-viec', status: 'tre'} }">
                        <span>{{ number_deadline.task.late }} Trễ</span>
                      </router-link>

                      <router-link :to="{ name: 'deadline', params: { type: 'cong-viec', status: 'toi-han'} }">
                        <span>{{ number_deadline.task.today }} Tới hạn</span>
                      </router-link>

                      <router-link :to="{ name: 'deadline', params: { type: 'cong-viec', status: 'dang-thuc-hien'} }">
                        <span>{{ number_deadline.task.working }} Đang thực hiện</span>
                      </router-link>
                    </div>
                    </template>

                    <template v-if="$root.auth.role.level == 4">
                    <b style="font-size: 12px">Nhiệm vụ</b>
                    <div class="d-flex justify-content-between">
                      <router-link :to="{ name: 'deadline', params: { type: 'nhiem-vu', status: 'tre'} }">
                        <span>{{ number_deadline.job.late }} Trễ</span>
                      </router-link>

                      <router-link :to="{ name: 'deadline', params: { type: 'nhiem-vu', status: 'toi-han'} }">
                        <span>{{ number_deadline.job.today }} Tới hạn</span>
                      </router-link>

                      <router-link :to="{ name: 'deadline', params: { type: 'nhiem-vu', status: 'dang-thuc-hien'} }">
                        <span>{{ number_deadline.job.working }} Đang thực hiện</span>
                      </router-link>
                    </div>
                    </template>
                  </div>
                </div>
              </div>
            </div>

            <div class="btn-group avatar">
              <div data-toggle="dropdown">
                <img :src="$root.auth.avatar ? $root.auth.avatar : $root.avatar_default" alt="">
                <b> {{ $root.auth.fullname || $root.auth.username }}</b>
              </div>
              <div class="dropdown-menu">
                <a class="dropdown-item" @click="handleLogout">Đăng xuất</a>
                <router-link :to="{ name: 'profile' }"  class="dropdown-item">Trang cá nhân</router-link>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="main-content scrollbar">
        <router-view />
      </div>
    </div>
    <m-loading v-if="loading_logout" title="Đang đăng xuất" :full="true" />
  </div>
</template>
