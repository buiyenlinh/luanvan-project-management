<script>
export default {
  props: {
    url: {
      type: String,
      default: ''
    },
    show_icon_x: {
      type: Boolean,
      default: true
    },
    text: {
      type: String,
      default: ''
    },
    variable: {
      type: Object,
      default: {
        first: '',
        second: ''
      }
    },
    size: {
      type: String,
      default: ''
    },
    statusReset: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      val: this.text,
      keyword: '',
      list: {},
      show: false,
      loading: false
    }
  },
  methods: {
    search() {
      if (this.keyword != '') {
        this.loading = true;
        this.$root.api.post(this.url, {keyword: this.keyword}).then(response => {
          if (response.data.status == "OK") {
            this.list = response.data.data;
          } else {
            this.loading = false;
            this.list = [];
          }
        }).catch(() => {
          this.loading = false;
        }).finally(() => {
          this.loading = false;
        })
      } else {
        this.list = [];
      }
    },
    setValue(item) {
      this.val = item[this.variable.first] || item[this.variable.second];
      this.$emit('changeValue', item);
      this.show = false;
    },
    removeValue() {
      this.cancel();
      this.show = false;
      this.$emit('remove');
    },
    showDropdown() {
      this.show = !this.show;
    },
    cancel() {
      this.val = this.text,
      this.keyword = '',
      this.list = {},
      this.show = false,
      this.loading = false
    }
  },
  watch: {
    statusReset() {
      this.cancel();
    },
    text(newText, oldText) {
      this.val = newText;
    }
  }
}
</script>


<template>
  <div class="select-component">
    <div :class="[size == 'sm' ? 'form-control-sm' : '', 'form-control']" @click="showDropdown">
      <span>{{ val }}</span>
      <i class="fas fa-times icon-x" @click.stop="removeValue" v-if="show_icon_x"></i>
      <i :class="[this.show ? 'select-icon-transition' : '' , 'select-icon-bottom', 'fas fa-angle-down']"></i>
    </div>
    <div class="select-dropdown" v-if="show">
      <input type="text" :class="[size == 'sm' ? 'form-control-sm' : '', 'form-control']" v-model="keyword" @input="search()">
      <div v-if="loading" class="loading spinner-border spinner-border-sm"></div>
      <ul class="mt-2">
        <li v-for="(item, i) in list" :key="i">
          <a @click="setValue(item)">{{ item[variable.first] || item[variable.second] }}</a>
        </li>
        <li class="li-no-data" v-if="list.length == 0">Không có dữ liệu</li>
      </ul>
    </div>
  </div>
</template>

<style lang="scss" scoped>
.select-component {
  position: relative;
  .form-control {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    padding-right: 35px;
  }
  .select-icon-bottom {
    position: absolute;
    z-index: 5;
    right: 8px;
    top: 9px;
    transition: all ease-in-out 0.4s;
  }
  .select-icon-transition {
    transform: rotate(180deg);
    transition: all ease-in-out 0.4s;
  }

  .icon-x {
    position: absolute;
    z-index: 5;
    right: 25px;
    top: 9px;
    cursor: pointer;
  }
}

.select-dropdown {
  border: 1px solid #ddd;
  border-top: none;
  padding: 10px;
  position: absolute;
  width: 100%;
  top: 31px;
  left: 0px;
  z-index: 10;
  background: #fff;
  box-shadow: 0px 2px 4px 1px #d8d8d8;

  .loading {
    position: absolute;
    z-index: 10;
    right: 15px;
    top: 17px;
  }

  ul {
    overflow-y: auto;
    max-height: 230px;
    list-style-type: none;
    padding: 0px;
    margin: 0;
    li {
      a {
        padding: 5px 10px;
        display: inline-block;
        width: 100%;
      }
      cursor: pointer;
    }

    .li-no-data:hover {
      background: #fff;
      cursor: auto;
    }
    li:hover {
      background: #117a8b;
      a {
        color: #fff;
      }
    }
  }
}


</style>