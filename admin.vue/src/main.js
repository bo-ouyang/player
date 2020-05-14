import Vue from 'vue'

import 'normalize.css/normalize.css' // A modern alternative to CSS resets

import ElementUI from 'element-ui'
import 'element-ui/lib/theme-chalk/index.css'
import locale from 'element-ui/lib/locale/lang/zh-CN' // lang i18n

import '@/styles/index.scss' // global css

import App from '@/App'
import router from '@/router'
import store from '@/store'

import {
  responseHelper
} from '@/utils/request'

import '@/utils/extend' // method for prototype
import '@/icons' // icon
import '@/router/permit' // permission control
import '@/filters'

Vue.use(ElementUI, { locale })

Vue.config.productionTip = false

Object.defineProperty(Vue.prototype, '$mResponseHelper', { value: responseHelper })

new Vue({
  el: '#app',
  router,
  store,
  render: h => h(App)
})
