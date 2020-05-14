// The Vue build version to load with the `import` command
// (runtime-only or standalone) has been set in webpack.base.conf with an alias.
import Vue from 'vue'
import App from '@/App'

import i18n from '@/i18n' // must before store
import store from '@/stores'
import router from '@/router'
// import '@/router/permits'

import {
  responseHelper
} from '@/utils/request'

import VueClipboards from 'vue-clipboards'

import '@/utils/extend'
import '@/vextend'
import '@/components'
import '@/styles/iconfont' // 彩色字体图标

Vue.config.productionTip = false

Vue.use(VueClipboards)

Object.defineProperty(Vue.prototype, '$mResponseHelper', { value: responseHelper })

/* eslint-disable no-new */
new Vue({
  el: '#app',
  i18n,
  store,
  router,
  render: h => h(App)
  // components: { App },
  // template: '<App/>'
})
