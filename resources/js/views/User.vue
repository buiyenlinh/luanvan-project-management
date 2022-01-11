<script>
export default {
  data() {
    return {
      list: null
    }
  },
  methods: {
    getUserList() {
      this.$root.api('user/list').then(res => {
        if (res.data.status == "OK") {
          this.list = res.data.data
        }
      })
    }
  },
  mounted() {
    this.getUserList();
  }
}
</script>

<template>
  <div>
    <h3>This is user page</h3>
    <div>{{ list }}</div>
  </div>
</template>