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
          path: '',
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
        }
      ]
    },
    {
      path: '/dang-nhap',
      'name': 'login',
      component: require('../views/Login.vue').default,
      meta: {
        guest: true,
        title: 'Đăng nhập'
      }
    },
    {
      path: '/quen-mat-khau',
      'name': 'forget-password',
      component: require('../views/ForgetPassword.vue').default,
      meta: {
        guest: true,
        title: 'Quên mật khẩu'
      }
    },
    {
      path: '*',
      name: '404',
      component: require('../views/404.vue').default,
      meta: {
        title: 'Trang không tồn tại'
      }
    }
  ]
})

router.beforeEach((to, from, next) => {
  let token = localStorage.getItem('yl_token');
  if (to.meta) {
    if (to.meta.auth) {
      if (!token) {
        next({name: 'login'});
      } else {
        next();
      }
    } else if (to.meta.guest) {
      if (token) {
        next({name: 'home'});
      } else {
        next();
      }
    }
  }
})

export default router;