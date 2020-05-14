<template>
  <div class="container">
    <h4 class="tipe-info">{{ message }}</h4>
  </div>
</template>

<script>
export default {
  name: 'HangOver',
  props: {
    message: {
      type: String,
      default: '敬请期待！'
    }
  }
}
</script>
