<script>
export default {
  props: {
    url: {
      type: String,
      default: ''
    },
    variable: {
      type: String,
      default: ''
    },
    text: {
      type: String,
      default: ''
    }
  },
  data() {
    return {
      show_dropdown: false,
      list: [],
      keyword: this.text,
      loading: false,
      time_out: null
    }
  },
  methods: {
    getData() {
      this.loading = true;
      this.$root.api.post(this.url, { keyword: this.keyword }).then(response => {
        this.loading = false;
        if (response.data.status == "OK") {
          this.list = response.data.data;
        } else {
          this.list = [];
        }
      }).catch(() => {
        this.loading = false;
      })
    },
    getItem(item) {
      console.log(item.name)
      this.keyword = item.name;
      this.show_dropdown = false;
    },
    openDropdown() {
      this.show_dropdown = true;
      if (!this.list.length) {
        this.getData();
      }
    },
    closeDropDown() {
      setTimeout(() => {
        this.show_dropdown = false;
      }, 500);
    },
    removeValue() {
      this.keyword = '';
    }
  },
  watch: {
    text(newText) {
      this.keyword = newText;
    },
    keyword() {
      if (this.time_out) {
        clearTimeout(this.time_out);
        this.time_out = null;
      }

      this.time_out = setTimeout(() => {
        this.getData();
      }, 800);
      
      this.$emit('changeValue', this.keyword);
    }
  }
}
</script>

<template>  
  <div id="component_input">
    <input type="text" class="form-control form-control-sm" v-model="keyword" @focus="openDropdown()" @blur="closeDropDown()">
    <i class="fas fa-times icon-x" @click.stop="removeValue"></i>
    <div v-if="loading" class="loading spinner-border spinner-border-sm"></div>
    <div class="input-dropdown" v-show="show_dropdown">
      <ul v-if="list && list.length" class="scrollbar">
        <li v-for="(item, index) in list" :key="index">
          <a @click="getItem(item)">{{ item[variable] }}</a>
        </li>
      </ul>
    </div>
  </div>
</template>

<style lang="scss">
#component_input {
  position: relative;
  .icon-x {
    position: absolute;
    right: 10px;
    top: 9px;
    cursor: pointer;
  }

  .loading {
    position: absolute;
    right: 23px;
    top: 9px;
  }

  .input-dropdown ul {
    border: 1px solid #ddd;
    max-height: 100px;
    overflow: auto;
    padding: 0;
    margin: 0;
    list-style-type: none;

    li a {
      display: block;
      padding: 6px 10px;
      cursor: pointer;
    }

    li a:hover {
      background: #117a8b;
      color: #fff;
    }
  }
}
</style>