import Vue from 'vue'
import Store from '@/stores'
import Router from '@/router'
import PaceLoader from '@/utils/PaceLoader'

Vue.use(PaceLoader)

Router.beforeEach((to, from, next) => {
  PaceLoader.start()

  // 判断进入的路由是否有 meta.exclude 属性，没有则表明需要验证登录状态
  if (to.meta.exclude || Store.state.User.props.accessToken) {
    next()
  } else {
    PaceLoader.finish()
    next({ name: 'SignIn' })
  }
})

Router.afterEach(route => {
  PaceLoader.finish()
})
