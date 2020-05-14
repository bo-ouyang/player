<template>
  <div class="container">
    <img :src="imageUrl" class="thumb" @click="showModal = true">
    <div v-if="showModal" ref="modalPanel" class="modal-mask" @click="showModal = $event.target === $refs.modalImage">
      <div class="img-view">
        <img ref="modalImage" :src="imageUrl">
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ImageShowed',
  props: {
    imageUrl: {
      type: String,
      default: ''
    }
  },
  data() {
    return {
      showModal: false
    }
  }
}
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
.container {
  display: inline-block;
  .thumb {
    cursor: pointer;
    &:hover {
      opacity: 0.7;
    }
  }
}
</style>
