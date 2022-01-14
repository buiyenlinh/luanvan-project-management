<script>
  export default {}
</script>

<template>
  <div class="not-found text-center mt-5">
    <h1>Không tìm thấy trang</h1>
    <router-link :to="{ name: 'dashboard' }" class="btn btn-primary">Quay lại trang chủ</router-link>
  </div>
</template>