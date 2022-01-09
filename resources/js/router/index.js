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
    },
    {
      path: '*',
      name: '404',
      component: require('../views/404.vue').default
    }
  ]
})

export default router;