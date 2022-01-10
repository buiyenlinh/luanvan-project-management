<template>
  <div id="app">
    <div v-if="$root.loading">Loading...</div>
    <router-view v-else />
  </div>
</template>

<script>
  export default {
    
  }
</script>