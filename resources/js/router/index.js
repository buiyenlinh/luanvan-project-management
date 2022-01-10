import { toPairs } from 'lodash';
import Vue from 'vue';
import VueRouter from 'vue-router';
Vue.use(VueRouter);

const router = new VueRouter({
  mode:'history',
  routes: [
    {
      path: '/',
      name: 'home',
      component: require('../views/Home.vue').default,
      meta: {
        auth: true,
        title: 'Trang chủ'
      }
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