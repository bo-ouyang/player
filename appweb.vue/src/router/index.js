import Vue from 'vue'
import Router from 'vue-router'

// import MainLayout from '@/views/layout/MainLayout'
// import SideLayout from '@/views/layout/SideLayout'
import HomePage from '@/views/home/HomePage'
import PlatformRules from '@/views/pages/PlatformRules'
import NotFound from '@/views/pages/NotFound'

Vue.use(Router)

export default new Router({
  // mode: 'history', // 后端支持可开
  scrollBehavior: () => ({ y: 0 }),
  routes: [
    // ***** 首页 *****
    {
      path: '/',
      name: 'HomePage',
      component: HomePage
    },

    {
      path: '/rules',
      name: 'PlatformRules',
      component: PlatformRules
      // component: () => import(/* webpackChunkName: 'page' */ '@/views/pages/PlatformRules')
    },

    {
      path: '*',
      name: 'NotFound',
      component: NotFound
    }
  ]
})
