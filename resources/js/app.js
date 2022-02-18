window._ = require('lodash');

window.axios = require('axios');
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

import Vue from 'vue';
import App from './App.vue';
import router from './router/index';

Vue.component('m-spinner', require('./components/Spinner.vue').default);
Vue.component('m-loading', require('./components/Loading.vue').default);
Vue.component('m-pagination', require('./components/Pagination.vue').default);
Vue.component('m-select', require('./components/Select.vue').default);

new Vue({
  router,
  render: h => h(App),
  data: {
    alert_container: {el: null, child: []},
		notify_container: {},
    api: axios.create({
      baseURL: '/api/',
      headers: {
        'Accept': 'application/json'
      }
    }),
    loading: true,
    config: null,
    auth: null,
    page_title: '',
		avatar_default: '/images/avatar-default.jpg',
  },
  methods: {
    setAuth(auth) { // Gán thông tin của 1 user
      if (auth) {
        this.auth = auth;
        localStorage.setItem('yl_token', auth.token);
        this.api.defaults.headers.common['Authorization'] = 'Bearer ' + auth.token;
        if (this.$route.meta.guest) {
          this.$router.replace({name: 'dashboard'});
        }
      } else {
        this.auth = null;
        localStorage.removeItem('yl_token');
        this.api.defaults.headers.common['Authorization'] = '';

        if (this.$route.meta.auth) {
          this.$router.replace({name: 'login'});
        }
      }
    },
    getConfig() {
      let token = localStorage.getItem('yl_token') || '';
      this.api.get('config', {
        headers: {
          'User-Token': token
        }
      }).then(res => {
        if (res.data.status == 'OK') {
          this.config = res.data.data.config;
          if (res.data.data.auth) {
            this.setAuth(res.data.data.auth);
          } else {
            this.setAuth(null);
          }
        }

        this.loading = false;
      }).catch(err => this.loading = false);
    },
    alert(message, title, type) {
			return new Promise((resolve, reject) => {
				if (typeof title == 'undefined') {
					title = '';
				}

				if (typeof type == 'undefined') {
					type = '';
				}

				let confirm = false;
				let prompt = false;
				if (type == 'confirm') {
					confirm = true;
					type = 'question';
				} else if (type == 'prompt') {
					prompt = true;
					type = '';
				}

				let _alert = document.createElement('div');
				_alert.className = 'm-alert';

				if (type != '' || title != '') {
					let _header = document.createElement('div');
					_header.className = 'm-alert-header';

					if (type != '') {
						let _icon = document.createElement('div');
						_icon.className = `m-alert-icon m-alert-icon-${type}`;
						_header.appendChild(_icon);
					}

					if (title != '') {
						let _title = document.createElement('div');
						_title.className = 'm-alert-title';
						_title.innerHTML = title;

						_header.appendChild(_title);
					}

					_alert.appendChild(_header);
				}

				let _content;
				if (prompt) {
					_content = document.createElement('input');
					_content.type = 'text';
					_content.className = 'form-control m-alert-input';
					_content.value = message;
				} else {
					_content = document.createElement('div');
					_content.className = 'm-alert-content';
					_content.innerHTML = message;
				}

				_alert.appendChild(_content);

				let _action = document.createElement('div');
				_action.className = 'm-alert-action';

				let _ok = document.createElement('button');
				_ok.type = 'button';
				_ok.className = 'btn btn-primary';
				_ok.innerHTML = 'OK';
				_ok.onclick = () => {
					_alert.classList.add('m-alert-fade-out');
					setTimeout(() => {
						_alert.remove();
						this.alert_container.child.pop();
						if (this.alert_container.child.length == 0) {
							this.alert_container.el.remove();
							this.alert_container.el = null;
						} else {
							let len = this.alert_container.child.length;
							this.alert_container.child[len - 1].style.display = 'block';
						}
					}, 300);

					if (prompt) {
						resolve(_content.value);
					} else {
						resolve('');
					}
				}

				_action.appendChild(_ok);

				if (confirm || prompt) {
					let _close = document.createElement('button');
					_close.type = 'button';
					_close.className = 'btn btn-secondary';
					_close.innerHTML = 'Đóng';
					_close.onclick = () => {
						_alert.classList.add('m-alert-fade-out');
						setTimeout(() => {
							_alert.remove();
							this.alert_container.child.pop();
							if (this.alert_container.child.length == 0) {
								this.alert_container.el.remove();
								this.alert_container.el = null;
							} else {
								let len = this.alert_container.child.length;
								this.alert_container.child[len - 1].style.display = 'block';
							}
						}, 300);

						reject();
					}

					_action.appendChild(_close);
				}

				_alert.appendChild(_action);

				if (this.alert_container.el == null) {
					let _container = document.createElement('div');
					_container.className = 'm-alert-container';

					document.body.appendChild(_container);
					this.alert_container.el = _container;
				} else {
					let len = this.alert_container.child.length;
					this.alert_container.child[len - 1].style.display = 'none';

					let _index = len + 1;
					_alert.style.zIndex = _index;
				}

				this.alert_container.el.appendChild(_alert);
				this.alert_container.child.push(_alert);

				if (prompt) {
					_content.focus();
				}

				_alert.classList.add('m-alert-fade-in');
				setTimeout(() => {
					_alert.classList.remove('m-alert-fade-in');
				}, 300);
			})
		},
		confirm(message, title) {
			if (typeof title == 'undefined') {
				title = '';
			}

			return this.alert(message, title, 'confirm');
		},
		prompt(title, content) {
			if (typeof content == 'undefined') {
				content = '';
			}

			return this.alert(content, title, 'prompt');
		},
		notify(message, type) {
			let _getIcon = (name) => {
				let svg = {
					color: '',
					path: ''
				};

				if (name == 'info') {
					svg.color = '#1CADF2';
					svg.path = 'M512,72C269,72,72,269,72,512s197,440,440,440s440-197,440-440S755,72,512,72z M581,673.9 c-33.2,49.9-67,88.3-123.8,88.3c-38.8-6.3-54.7-34.1-46.3-62.4L484,457.6c1.8-5.9-1.2-12.3-6.6-14.2c-5.4-1.9-15.9,5.1-25.1,15.1 l-44.2,53.2c-1.2-8.9-0.1-23.7-0.1-29.6c33.2-49.9,87.8-89.2,124.8-89.2c35.2,3.6,51.8,31.7,45.7,62.6l-73.6,243.3 c-1,5.5,1.9,11.1,6.9,12.8c5.4,1.9,16.8-5.1,26-15.1l44.2-53.1C583,652.3,581,667.9,581,673.9z M571.2,357.6 c-28,0-50.6-20.4-50.6-50.4c0-30,22.7-50.3,50.6-50.3c28,0,50.6,20.4,50.6,50.3C621.8,337.3,599.1,357.6,571.2,357.6z';
				} else if (name == 'success') {
					svg.color = '#17B77E';
					svg.path = 'M512,72C269,72,72,269,72,512s197,440,440,440s440-197,440-440S755,72,512,72L512,72z M758.9,374 c-48.5,48.6-81.2,76.9-172.3,186.8c-52.6,63.4-102.3,131.5-102.7,132L462.1,720c-4.6,6.1-13.5,6.8-19.1,1.6L267.9,558.9 c-17.8-16.5-18.8-44.4-2.3-62.2s44.4-18.8,62.2-2.3l104.9,97.5c5.5,5.1,14.1,4.5,18.9-1.3c16.2-20.1,38.4-44.5,62.4-68.6 c90.2-90.9,145.6-139.7,175.2-161.3c36-26.2,77.3-48.6,87.3-36.2C792,343.9,782.5,350.3,758.9,374L758.9,374z';
				} else if (name == 'warning') {
					svg.color = '#FFC603';
					svg.path = 'M512,952C269,952,72,755,72,512S269,72,512,72s440,197,440,440S755,952,512,952z M510,770.8 c30.4,0,55-24.6,55-55s-24.6-55-55-55s-55,24.6-55,55S479.6,770.8,510,770.8z M509.8,255.3c-39.3,0-71.2,31.9-71.2,71.2 c0,3.1,0.2,6.2,0.6,9.3L472.4,588c2.5,19.3,18.9,33.7,38.4,33.7c19.4,0,35.8-14.4,38.2-33.7l31.8-252.2c5-39.2-22.8-75-62-79.9 C515.9,255.5,512.8,255.3,509.8,255.3z';
				} else if (name == 'error') {
					svg.color = '#F56C6C';
					svg.path = 'M512,952C269,952,72,755,72,512S269,72,512,72s440,197,440,440S755,952,512,952z M579.7,512l101.6-101.6 c18.7-18.7,18.7-49,0-67.7c-18.7-18.7-49-18.7-67.7,0l0,0L512,444.3L410.4,342.7c-18.7-18.7-49-18.7-67.7,0s-18.7,49,0,67.7 L444.3,512L342.7,613.6c-18.7,18.7-18.7,49,0,67.7c18.7,18.7,49,18.7,67.7,0L512,579.7l101.6,101.6c18.7,18.7,49,18.7,67.7,0 c18.7-18.7,18.7-49,0-67.7L579.7,512z';
				} else {
					return '';
				}

				let str = `
							<svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1024 1024" style="color: ${svg.color}">
							    <path fill="currentColor" d="${svg.path}" />
							</svg>
						`;

				return str;
			}

			let list_type = ['info', 'success', 'warning', 'error'];

			if (list_type.indexOf(type) == -1) {
				type = '';
			}

			let position = 'bottom-center';
			let duration = 2000;

			let _icon = '';
			let _html = '';

			if (type != '') {
				_icon = _getIcon(type);
			}

			if (_icon != '') {
				_html += '<div class="m-notify-icons">' + _icon + '</div>';
			}

			let _content = document.createElement('div');
			_html += '<div class="m-notify-content">';
			_html += '	<div class="m-notify-description">' + message + '</div>';
			_html += '</div>'

			let _wrap = document.createElement('div');
			_wrap.className = 'm-notify-wrapper';
			_wrap.innerHTML = '<div class="m-notify">' + _html + '</div>';

			if (!this.notify_container[position]) {
				let _container = document.createElement('div');
				_container.className = 'm-notify-container is-' + position;

				document.body.appendChild(_container);

				this.notify_container[position] = {el: _container, child: []};
			}

			if (this.notify_container[position].child.length == 0) {
				this.notify_container[position].el.appendChild(_wrap);
				this.notify_container[position].child.push(_wrap);
			} else {
				let firstChild = this.notify_container[position].child[0];
				this.notify_container[position].el.insertBefore(_wrap, firstChild);
				this.notify_container[position].child.unshift(_wrap);
			}

			_wrap.classList.add('m-notify-fade-in');
			setTimeout(() => {
				_wrap.classList.remove('m-notify-fade-in');
			}, 300);

			setTimeout(() => {
				_wrap.classList.add('m-notify-fade-out');

				setTimeout(() => {
					let len = this.notify_container[position].child.length;
					this.notify_container[position].child[len - 1].remove();
					this.notify_container[position].child.pop();

					setTimeout(() => {
						if (this.notify_container[position] && this.notify_container[position].child.length == 0) {
							this.notify_container[position].el.remove();
							this.notify_container[position] = null;
						}
					}, 3000);
				}, 290);
			}, duration);
		},
		showError401(err) {
			if (this.show_error_401) {
				return false;
			}

			this.show_error_401 = true;
			this.alert(err, '', 'error').then(() => {
				this.$router.replace({name: 'login'});
			});

			return true;
		},
		showError(err) {
			if (!err) {
				this.alert('Error', '', 'error');
				return;
			}

			let error = err;
			let status = 200;

			if (err.response) {
				if (err.response.status) {
					status = err.response.status;
				}

				if (err.response.data) {
					if (err.response.data.message) {
						error = err.response.data.message;
					}
				}
			}

			if (status == 401) {
				this.setAuth(null);

				return this.showError401(error);
			}

			this.alert(error, '', 'error');
		},
		checkEmail(str) {
			let re = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
			return re.test(str);
		},
		checkPhone(str) {
			let re = /^0[35789][0-9]{8}$/;
			return re.test(str);
		},
		isAdmin() {
			if (!this.auth) return false;
			return (this.auth.role.level == 1 || this.auth.role.level == 2);
		},
		isSuperAdmin() {
			if (!this.auth) return false;
			return this.auth.role.level == 1;
		},
		isManager() {
			if (!this.auth) return false;
			return (this.auth.role.level == 1 || this.auth.role.level == 2 || this.auth.role.level == 3);
		},
		getStatusTaskName($num_status) {
			if ($num_status == 0) return 'Đã giao';
			if ($num_status == 1) return 'Đã tiếp nhận';
			if ($num_status == 2) return 'Chờ duyệt';
			if ($num_status == 3) return 'Đã duyệt';
			if ($num_status == 4) return 'Từ chối duyệt';
			if ($num_status == 5) return 'Từ chối nhận';
		},
		checkDeadline(_param) {
			let today = new Date();
			let date = today.getFullYear() +'-0'+(today.getMonth()+1)+'-'+today.getDate();

			date = new Date(date).getTime();
			let end_time_param = new Date(_param.end_time).getTime();

			let check = date - end_time_param;
			if (check > 0) {
				check = check / 86400000;
				return 'Trễ ' + check + ' ngày';
			} else {
				return 'Chưa tới hạn'	
			}
		}
  },
  created() {
    this.page_title = document.title;
  },
  mounted() {
    Vue.prototype.$alert = this.alert;
		Vue.prototype.$confirm = this.confirm;
		Vue.prototype.$prompt = this.prompt;
		Vue.prototype.$notify = this.notify;
		
		$(document).on('keypress', 'input[type="tel"]', function(e){
			let keys = ['0','1','2','3','4','5','6','7','8','9'];
			if (keys.indexOf(e.key) == -1) {
				e.preventDefault();
			}
		});

    this.getConfig();

    if (this.$route.meta && this.$route.meta.title) {
      document.title = this.$route.meta.title + ' | ' + this.page_title;
    }
  },
  watch: {
    '$route': function(_new, _old) {
      if (_new.meta && _new.meta.title) {
        document.title = _new.meta.title + ' | ' + this.page_title;
      } else {
        document.title = this.page_title;
      }
    }
  }
}).$mount('#app')