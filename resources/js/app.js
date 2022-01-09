window._ = require('lodash');

window.axios = require('axios');
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

import Vue from 'vue';
import App from './App.vue';
import router from './router/index';

new Vue({
  router,
  render: h => h(App),
  data: {

  },
  methods: {

  },
  mounted() {

  }
}).$mount('#app')