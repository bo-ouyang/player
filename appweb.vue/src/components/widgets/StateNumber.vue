<template>
  <div class="state-panel contaner">
    {{ title }}:
    <div class="state-inner">
      <img class="state-icon" src="~@/assets/images/mark.0104-1.png" width="398" height="399" />
      <img class="state-desc" src="~@/assets/images/mark.0104-2.png" width="77" height="117" />
      <span class="state-text">{{ number | coinReadable(2) }}<i>ETH</i></span>
    </div>
  </div>
</template>

<script>
export default {
  name: 'StateNumber',
  props: {
    title: {
      type: String,
      required: true
    },
    number: {
      type: [Number, String],
      default: 0
    }
  }
}
</script>

<style lang="scss" scoped>
@import '~@/styles/defines';
@import '~@/styles/animate';

.state {
  &-panel {
    @include padding-lr(0.55rem);
  }
  &-inner {
    position: relative;
    margin-top: 0.665rem;
  }
  &-icon {
    display: block;
    width: 2.4rem;
    height: auto;
    @include margin-center;
    animation: animate-shadow 2.6s infinite alternate;
  }
  &-desc {
    position: absolute;
    top: -0.385rem;
    left: 50%;
    margin-left: 0.96rem;
    width: 0.465rem;
    height: auto;
  }
  &-text {
    display: block;
    position: absolute;
    top: 0.95rem;
    left: 0;
    right: 0;
    color: $tabon-color;
    font-size: 0.5rem;
    text-align: center;
    line-height: 1;

    i {
      font-style: normal;
      font-size: 0.24rem;
    }
  }
}
</style>
