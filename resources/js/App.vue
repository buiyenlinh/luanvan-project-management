<template>
  <div id="app">
    <div v-if="$root.loading">
      <m-loading title="Loading" :full="true" />
    </div>
    <router-view v-else />
  </div>
</template>