<template>
  <svg class="icon" aria-hidden="true">
    <use :xlink:href="`#${iconClass}`" />
  </svg>
</template>

<script>
export default {
  name: 'ColorIcon',
  props: {
    iconClass: String
  }
}
</script>

<style lang="scss" scoped>
.icon {
  // width: 1em;
  // height: 1em;
  vertical-align: -0.15em;
  line-height: 1;
  fill: currentColor;
  overflow: hidden;
}
</style>
