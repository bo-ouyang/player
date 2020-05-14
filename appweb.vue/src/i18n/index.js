import Vue from 'vue'
import VueI18n from 'vue-i18n'
import store from '@/stores'

import ChineseMessages from './locales/zh-cn'
Vue.use(VueI18n)

export default new VueI18n({
  locale: store.state.userHabit.language,
  messages: {
    'zh-cn': ChineseMessages
  }
})
