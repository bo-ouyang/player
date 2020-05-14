import router from '@/router'
import store from '@/store'

import {
  responseHelper
} from '@/utils/request'

import NProgress from 'nprogress' // Progress 进度条
import 'nprogress/nprogress.css'// Progress 进度条样式

router.beforeEach((to, from, next) => {
  const user = store.state.user

  NProgress.start()
  // 判断进入的路由是否有 meta.exclude 属性，没有则表明需要验证登录状态
  if (to.meta.exclude) {
    next()
  } else if (user.props.accessToken) {
    if (user.admin) {
      if (to.meta.role && user.admin.is_super !== to.meta.role) {
        next({ name: 'NotAllow' })
        NProgress.done()
      } else {
        next()
      }
    } else {
      responseHelper(
        store.dispatch('user/getInfo'),
        () => {
          next(to.fullPath || '/')
          NProgress.done()
        }
      )
    }
  } else {
    NProgress.done()
    next({ name: 'SignIn' })
  }
})

router.afterEach(() => {
  NProgress.done() // 结束Progress
})
