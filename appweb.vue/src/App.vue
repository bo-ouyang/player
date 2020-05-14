<template>
  <div :class="{ 'wrap--ios': isMacosBrowser }" id="app">
    <router-view />
  </div>
</template>

<script>
export default {
  name: 'App',

  data () {
    return {
      isMacosBrowser: /Mac OS X/i.test(navigator.userAgent)
    }
  }
}
</script>
