import { toPairs } from 'lodash';
import Vue from 'vue';
import VueRouter from 'vue-router';
Vue.use(VueRouter);

const router = new VueRouter({
  mode:'history',
  routes: [
    {
      path: '/',
      component: require('../views/Home.vue').default,
      meta: {
        auth: true,
        title: 'Trang chủ'
      },
      children: [
        {
          path: '/',
          name: 'dashboard',
          component: require('../views/DashBoard.vue').default,
          meta: {
            auth: true,
            title: 'Thống kê'
          }
        },
        {
          path: 'nguoi-dung',
          name: 'user',
          component: require('../views/User.vue').default,
          meta: {
            auth: true,
            title: 'Người dùng'
          }
        },
        {
          path: 'du-an',
          name: 'project',
          component: require('../views/Project.vue').default,
          meta: {
            auth: true,
            title: 'Dự án'
          },
        },
        {
          path: 'ca-nhan',
          name: 'profile',
          component: require('../views/Profile.vue').default,
          meta: {
            auth: true,
            title: 'Trang cá nhân'
          }
        },
      ]
    },
    {
      path: '/dang-nhap',
      name: 'login',
      component: require('../views/Login.vue').default,
      meta: {
        guest: true,
        title: 'Đăng nhập'
      }
    },
    {
      path: '/quen-mat-khau',
      name: 'lostpass',
      component: require('../views/ForgetPassword.vue').default,
      meta: {
        guest: true,
        title: 'Quên mật khẩu'
      }
    },
    {
      path: '*',
      name: 'page_404',
      component: require('../views/Page404.vue').default,
      meta: {
        title: 'Trang không tồn tại'
      }
    }
  ]
})

router.beforeEach((to, from, next) => {
  let token = localStorage.getItem('yl_token');
  if (to.meta) {
    if (to.meta.auth && !token) {
      next({name: 'login'});
    } else if (to.meta.guest && token) {
      next({name: 'dashboard'});
    } else {
      next();
    }
  } else {
    next();
  }
})

export default router;