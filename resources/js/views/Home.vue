<script>
export default {
  data() {
    return {
      change_bar: false,
      menu: [
        {
          label: 'Thống kê',
          link: "dashboard",
          icon: "fas fa-tachometer-alt",
        },
        {
          label: 'Người dùng',
          link: "user",
          icon: "fas fa-users",
        },
        {
          label: 'Dự án',
          link: "project",
          icon: "fas fa-folder",
        },
        {
          label: 'Cá nhân',
          link: "profile",
          icon: "fas fa-user-circle",
        }
      ],
      name_route: '',
      loading_logout: false
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
    }
  },
  watch: {
    '$route': function(_new, _old) {
      this.name_route = _new.name;
    }
  },
  mounted() {
    this.name_route = this.$root.$route.name;
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
      
      <ul class="nav flex-column" role="tablist">
        <li v-for="(item, index) in menu"
          :key="index"
          :class="[ name_route == item.link ? 'router-link-exact-active' : '' ]"
          :title="item.label"
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
          <div class="btn-group">
            <div class="avatar" data-toggle="dropdown">
              <img v-if="$root.auth.avatar" :src="$root.auth.avatar" alt="">
              <img v-else :src="$root.avatar_default" alt="">
              <b> {{ $root.auth.fullname }}</b>
            </div>
            <div class="dropdown-menu">
              <a class="dropdown-item" @click="handleLogout">Đăng xuất</a>
              <router-link :to="{ name: 'profile' }"  class="dropdown-item">Trang cá nhân</router-link>
            </div>
          </div>
        </div>
      </div>
      <div class="main-relative">
        <div class="main">
          <router-view />
        </div>
      </div>
    </div>
  </div>
</template>
