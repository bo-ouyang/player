<template>
  <div class="container head-nav">
    <button class="head-nav-back" @click="$router.back()">
      <i class="iconfont icon-sign-back" />
      <!-- <span class="fs-page">返回</span> -->
    </button>
    <slot />
  </div>
</template>

<script>
export default {
  name: 'NavHead'
}
</script>
