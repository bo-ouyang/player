<template>
  <div class="wrap-H100P container">
    <nav-head>
      <button v-if="link" class="head-nav-more">
        <router-link :to="link">
          <i v-if="icon || !more" :class="icon || 'icon-more-horiz'" class="iconfont" />
          <span v-else class="fs-page">{{ $t(more) }}</span>
        </router-link>
      </button>
      <div class="head-nav-title text-ellipsis">{{ $t(viewTitle || title) }}</div>
    </nav-head>
    <router-view class="page-body page-body--side scrollbox" style="height:calc(100% - 0.88rem);" @settitle="viewTitle = $event" />
  </div>
</template>

<script>
import NavHead from '@/components/widgets/NavHead'

export default {
  name: 'SideLayout',
  components: {
    NavHead
  },
  props: {
    title: String,
    icon: String,
    more: String,
    link: [String, Object]
  },

  data () {
    return {
      viewTitle: ''
    }
  },

  watch: {
    title () {
      this.viewTitle = ''
    }
  }
}
</script>
