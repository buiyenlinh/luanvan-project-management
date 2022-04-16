<script>
export default {
  data() {
    return {
      users: [],
      search: '',
      chat: {
        receiver: null,
        messages: [],
        per: 8,
        page: 1,
        last: 1,
        loading: false
      },
      loading_data: true,
      text_message: '',
      show_left: true,
			scroll_type: 'plus',
    }
  },
  components: {
    'time-ago': require('../components/TimeAgo.vue').default,
    'm-messages': require('../components/Messages.vue').default
  },
  methods: {
    getListUser() {
      this.loading_data = true;
      this.$root.api.get('chat/user').then(res => {
        this.loading_data = false;
        if (res.data.status == "OK") {
          this.users = res.data.data;
        }
      }).catch(err => {
        this.loading_data = false;
        this.$root.showError(err);
      })
    },
    openChat(item) {
      this.$root.setHeaderAPI('Receiver', item.user.id);

      this.chat.receiver = item;
      this.chat.page = 1;
      this.chat.messages = [];
      this.getMessage();

      if (item.new) {
        this.seenMessage();
        this.$set(item, 'new', 0);
      }

      this.show_left = false;
    },
    getMessage() {
      this.scroll_type = 'plus';
      let del = this.chat.messages.length - (this.chat.page - 1) * this.chat.per;

      this.chat.loading = true;
      this.$root.api.get('chat/message', {
        params: {
          page: this.chat.page,
          per: this.chat.per
        }
      }).then(res => {
        this.chat.loading = false;
        if (res.data.status == 'OK') {
          let list = _.reverse(res.data.data.list); // đổi ngược mảng lại

          if (del > 0) {
						list.splice(list.length - del);
					}

          if (this.chat.page == 1) {
            this.chat.messages = list;  
          } else {
            this.chat.messages = list.concat(this.chat.messages); // Nối mảng
          }

          this.chat.last = res.data.data.last;
        } else {
          this.$alert(res.data.error, '', 'error');
        }
      }).catch(err => {
        this.chat.loading = false;
        this.$root.showError(err);
      })
    },
    sendMessage() {
      if (this.text_message) {
        this.$root.api.post('chat/add', {
          type: 'text',
          content: this.text_message.trim()
        }).then(res => {
          if (res.data.status == 'OK') {
            this.appendMessage(res.data.data.chat);
            this.setMessageLastest(res.data.data.code, res.data.data.lastest);
          } else {
            this.$alert(res.data.error, '', 'error');
          }
        }).catch(err => this.$root.showError(err));

        this.text_message = '';
      } else {
        this.$root.notify('Vui lòng nhập tin nhắn', 'error');
      }
    },
    seenMessage() {
      this.$root.api.post('chat/seen').then(res => {
      }).catch(err => {})
    },
    appendMessage(item) {
      let $scroll = this.$refs.body;
      if ($scroll) {
        let end = $scroll.scrollTop + $scroll.clientHeight >= $scroll.scrollHeight;
        if (end) {
          this.scroll_type = 'plus';
        } else {
          this.scroll_type = 'none';
        }
      } else {
        this.scroll_type = 'none';
      }

      let list = _.clone(this.chat.messages);

      if (this.chat.receiver) {
        list.push(item);

        if (list.length >= (this.chat.page + 1) * this.chat.per) {
          this.chat.page++;
        }
      }

      this.chat.messages = list;
    },
    showMessageNew(number) {
      return (number <= 5) ? number : '5+';
    },
    setMessageNew(code, number) {
      this.$set(this.users[code], 'new', number);
    },
    showMessageLastest(data) {
      if (data.me) {
        return 'Bạn: ' + data.message;
      }

      return data.message;
    },
    setMessageLastest(code, data) {
      this.$set(this.users[code], 'lastest', data);
    },
    checkUserOnline(uid) {
      return this.$root.users_online.indexOf(uid) >= 0;
    },
    updateHeightMessage(height) {
      let $scroll = this.$refs.body;

      if (this.scroll_type == 'plus') {
        this.$refs.body.scrollTop += height;
      }
    },
    eventScrollBody(e) {
				if (!this.chat.messages.length || this.chat.page >= this.chat.last) {
					return;
				}

				let $scroll = e.target;

				if ($scroll.scrollTop == 0) {
					this.chat.page++;
					this.getMessage();
				}
			},
  },
  mounted() {
    this.getListUser();
  },
  watch: {
    '$root.realtime'(n) {
      if (n.name == 'chat') {
        this.setMessageNew(n.code, n.new);
        this.setMessageLastest(n.code, n.lastest);
        
        if (this.chat.receiver && n.code == this.chat.receiver.code) {
          this.appendMessage(n.chat);
          this.seenMessage();
        }
      }
    }
  },
  computed: {
    list_user() {
      let users = Object.values(this.users);
      for (let i = 0; i < users.length; i++) {
        users[i].online = this.$root.users_online.indexOf(users[i].user.id) >= 0;
      }

      users.sort((a, b) => {
        if (a.online && b.online) {
          if (a.lastest && b.lastest) {
            return b.lastest.timestamp - a.lastest.timestamp;
          } else if (b.lastest) {
            return 1;
          } else if (a.lastest) {
            return -1;
          }

          return -1;
        } else if (b.online) {
          return 1;
        } else if (a.online) {
          return -1;
        } else if (a.lastest && b.lastest) {
          return b.lastest.timestamp - a.lastest.timestamp;
        } else if (b.lastest) {
          return 1;
        } else if (a.lastest) {
          return -1;
        }
      })

      if (!this.search) {
        return users;
      }

      return users.filter(a => {
        return a.user.fullname.toLowerCase().indexOf(this.search.toLowerCase()) >= 0
      });
    },
  }
}
</script>

<template>
  <div id="chat" :class="show_left ? 'show-left' : ''">
    <div class="left">
      <div class="search">
        <input type="text" class="form-control form-control-sm" v-model="search" placeholder="Tìm kiếm...">
      </div>
      <div class="users scrollbar">
        <m-spinner v-if="loading_data" />
        <ul v-else>
          <li v-for="(item, index) in list_user" :key="index">
            <a :class="{
              'active': chat.receiver && item.code == chat.receiver.code
            }" @click="openChat(item)">
              <div class="image">
                <img :src="$root.getAvatar(item.user.avatar)" alt="">
                <span v-if="item.online" class="online"></span>
              </div>
              <div class="info">
                <b class="name">{{ item.user.fullname }}</b>
                <div v-if="item.lastest" class="latest-message" :class="{
                  'font-weight-bold': item.new
                }">{{ showMessageLastest(item.lastest) }}</div>
                <time-ago v-if="item.lastest" class="time" :timestamp="item.lastest.timestamp" />
                <span v-if="item.new" class="new">{{ showMessageNew(item.new) }}</span>
              </div>
            </a>
          </li>
        </ul>
      </div>
    </div>
    <div class="right">
      <div class="bg-fff"><button class="btn btn-sm show-left-btn" @click="show_left = true">
        <i class="fas fa-angle-double-left"></i>
        Danh sách
      </button></div>
      <template v-if="chat.receiver">
        <div class="info">
          <div class="image">
            <img :src="$root.getAvatar(chat.receiver.user.avatar)" alt=""/>
            <span v-if="chat.receiver.online" class="online"></span>
          </div>
          <div><b>{{ chat.receiver.user.fullname }}</b></div>
        </div>
        <div class="messages scrollbar" ref="body" @scroll="eventScrollBody">
          <m-messages :list="chat.messages" :receiver="chat.receiver" @height="updateHeightMessage" />
        </div>
        <div class="form">
          <form @submit.prevent="sendMessage()">
            <input type="text" class="form-control form-control-sm" placeholder="Nhập tin nhắn ..." v-model="text_message">
            <button type="submit" class="btn btn-sm btn-info">Gửi</button>
          </form>
        </div>
      </template>
      <div v-else class="d-flex align-items-center justify-content-center w-100 h-100">
        <div>Chọn tài khoản từ danh sách bên trái để nhắn tin</div>
      </div>
    </div>
  </div>
</template>