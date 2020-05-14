<template>
  <div class="container">
    <p class="tipe-info" v-if="isLoading && !isAppend">
      <i class="iconfont icon-loader" />
      数据加载中...
    </p>
    <template v-else-if="dataList && dataList.length">
      <slot />
      <p class="tipe-info" v-show="isLoading">
        <i class="iconfont icon-loader" />
        更多数据加载中...
      </p>
    </template>
    <p class="tipe-info" v-else>暂无记录！</p>
  </div>
</template>

<script>
export default {
  name: 'ListLoader',
  props: {
    isLoading: null,
    isAppend: null,
    dataList: Array
  }
}
</script>
