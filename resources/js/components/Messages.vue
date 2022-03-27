<script>
export default {
  props: ['list', 'receiver'],
  data() {
    return {
      height: 0
    }
  },
  beforeUpdate() {
    this.height = this.$refs.body.offsetHeight;
  },
  updated() {
    let height = this.$refs.body.offsetHeight;
    height -= this.height;

    this.$emit('height', height);
  }
}
</script>

<template>
  <ul ref="body">
    <li v-for="(item, index) in list" :key="index" class="message-item" :class="{
      'me': item.sender == $root.auth.id
    }">
      <div v-if="item.sender == $root.auth.id" class="bg">
        <div class="content-message">
          <div>{{ item.content }}</div>
          <span style="font-size: 11px">{{ item.time }}</span>
        </div>
        <img :src="$root.getAvatar()" class="avt">
      </div>
      <div v-else class="bg">
        <img :src="$root.getAvatar(receiver.user.avatar)" class="avt">
        <div class="content-message">
          <div>{{ item.content }}</div>
          <span style="font-size: 11px">{{ item.time }}</span>
        </div>
      </div>
    </li>
  </ul>
</template>