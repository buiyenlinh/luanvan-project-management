<script>
export default {
  props: {
    url: {
      type: String,
      default: ''
    },
    text: {
      type: String,
      default: ''
    },
    variable: {
      type: String,
      default: ''
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
          this.list = response.data.data;
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
      this.val = item[this.variable];
      this.$emit('changeValue', item);
      this.show = false;
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
      <i :class="[this.show ? 'select-icon-transition' : '' , 'select-icon-bottom', 'fas fa-angle-down']"></i>
    </div>
    <div class="select-dropdown" v-if="show">
      <input type="text" :class="[size == 'sm' ? 'form-control-sm' : '', 'form-control']" v-model="keyword" @input="search()">
      <div v-if="loading" class="loading spinner-border spinner-border-sm"></div>
      <ul class="mt-2">
        <li v-for="(item, i) in list" :key="i">
          <a @click="setValue(item)">{{ item[variable] }}</a>
        </li>
        <li class="li-no-data" v-if="list.length == 0">Không có dữ liệu</li>
      </ul>
    </div>
  </div>
</template>

<style lang="scss" scoped>
.select-component {
  position: relative;
  .select-icon-bottom {
    position: absolute;
    z-index: 10;
    right: 8px;
    top: 9px;
    transition: all ease-in-out 0.4s;
  }
  .select-icon-transition {
    transform: rotate(180deg);
    transition: all ease-in-out 0.4s;
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