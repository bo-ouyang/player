import Vue from 'vue'
import Router from 'vue-router'

// in development-env not use lazy-loading, because lazy-loading too many pages will cause webpack hot update too slow. so only in production use lazy-loading;
// detail: https://panjiachen.github.io/vue-element-admin-site/#/lazy-loading

Vue.use(Router)

/* Layout */
import Layout from '@/views/layout/Layout'
import SignIn from '@/views/boost/SignIn'
import NotFound from '@/views/NotFound'
import NotAllow from '@/views/NotAllow'
import Dashboard from '@/views/dashboard'
import MemberList from '@/views/member/MemberList'
import InvestOrders from '@/views/order/InvestOrders'
import StaticIncome from '@/views/order/StaticIncome'
import DynamicIncome from '@/views/order/DynamicIncome'
import HolderIncome from '@/views/order/HolderIncome'
import TokenOrders from '@/views/order/TokenOrders'
import CheerOrders from '@/views/order/CheerOrders'
import UranusIncome from '@/views/order/UranusIncome'
import WithdrawOrders from '@/views/wallet/WithdrawOrders'
import SystemParams from '@/views/config/SystemParams'
import ManagerList from '@/views/manager/ManagerList'
import ErrorReport from '@/views/config/ErrorReport'
import SubmitOrder from '@/views/config/SubmitOrder'
import SendLog from '@/views/order/SendLog'
/**
* hidden: true                   if `hidden:true` will not show in the sidebar(default is false)
* alwaysShow: true               if set true, will always show the root menu, whatever its child routes length
*                                if not set alwaysShow, only more than one route under the children
*                                it will becomes nested mode, otherwise not show the root menu
* redirect: noredirect           if `redirect:noredirect` will no redirect in the breadcrumb
* name:'router-name'             the name is used by <keep-alive> (must set!!!)
* meta : {
    title: 'title'               the name show in submenu and breadcrumb (recommend set)
    icon: 'svg-name'             the icon show in the sidebar,
  }
**/
export default new Router({
  // mode: 'history', //后端支持可开
  scrollBehavior: () => ({ y: 0 }),
  routes: [
    {
      path: '/',
      component: Layout,
      children: [
        {
          path: '',
          name: 'HomePage',
          meta: {
            title: '数据总览',
            icon: 'stats'
          },
          component: Dashboard
        }
      ]
    },
    {
      path: '/login',
      name: 'SignIn',
      meta: {
        exclude: true
      },
      component: SignIn,
      hidden: true
    },

    {
      path: '/member',
      component: Layout,
      children: [
        {
          path: '',
          name: 'MemberList',
          meta: {
            title: '会员管理',
            icon: 'member'
          },
          component: MemberList
        }
      ]
    },

    {
      path: '/order',
      name: 'OrderList',
      redirect: {
        name: 'InvestOrders'
      },
      hidden: true
    },
    {
      path: '/order/invest',
      component: Layout,
      children: [
        {
          path: '',
          name: 'InvestOrders',
          meta: {
            title: '理财订单',
            icon: 'order'
          },
          component: InvestOrders
        }
      ]
    },
    {
      path: '/order/static',
      component: Layout,
      children: [
        {
          path: '',
          name: 'StaticIncome',
          meta: {
            title: '静态列表',
            icon: 'order'
          },
          component: StaticIncome
        }
      ]
    },
    {
      path: '/order/dynamic',
      component: Layout,
      children: [
        {
          path: '',
          name: 'DynamicIncome',
          meta: {
            title: '动态列表',
            icon: 'order'
          },
          component: DynamicIncome
        }
      ]
    },
    {
      path: '/order/sendLog',
      component: Layout,
      children: [
        {
          path: '',
          name: 'sendLog',
          meta: {
            title: '出币日志',
            icon: 'order'
          },
          component: SendLog
        }
      ]
    },
    {
      path: '/wallet',
      name: 'WalletRecord',
      redirect: {
        name: 'WithdrawRecord'
      },
      hidden: true
    },
    {
      path: '/wallet/withdraw',
      component: Layout,
      children: [
        {
          path: '',
          name: 'WithdrawRecord',
          meta: {
            title: '提现订单',
            icon: 'wallet'
          },
          component: WithdrawOrders
        }
      ]
    },

    {
      path: '/order/holder',
      component: Layout,
      children: [
        {
          path: '',
          name: 'HolderIncome',
          meta: {
            title: '股东收益',
            icon: 'order'
          },
          component: HolderIncome
        }
      ]
    },
    {
      path: '/order/token',
      component: Layout,
      children: [
        {
          path: '',
          name: 'TokenOrders',
          meta: {
            title: 'ACGG列表',
            icon: 'order'
          },
          component: TokenOrders
        }
      ]
    },
    {
      path: '/order/eggs',
      name: 'CheerOrders',
      meta: {
        title: '彩蛋管理',
        icon: 'order',
        role: 1
      },
      redirect: {
        name: 'CheerOrders1'
      },
      component: Layout,
      children: [
        {
          path: '1',
          name: 'CheerOrders1',
          meta: {
            title: '铜钥匙',
            icon: 'nested',
            role: 1
          },
          props: {
            cheerType: 1
          },
          component: CheerOrders
        },
        {
          path: '2',
          name: 'CheerOrders2',
          meta: {
            title: '翡翠钥匙',
            icon: 'nested',
            role: 1
          },
          props: {
            cheerType: 2
          },
          component: CheerOrders
        },
        {
          path: '3',
          name: 'CheerOrders3',
          meta: {
            title: '水晶钥匙',
            icon: 'nested',
            role: 1
          },
          props: {
            cheerType: 3
          },
          component: CheerOrders
        },
        {
          path: '4',
          name: 'CheerOrders4',
          meta: {
            title: '超级彩蛋',
            icon: 'example',
            role: 1
          },
          props: {
            cheerType: 4
          },
          component: CheerOrders
        }
      ]
    },

    {
      path: '/order/node',
      component: Layout,
      children: [
        {
          path: '',
          name: 'UranusIncome',
          meta: {
            title: '节点收益',
            icon: 'order'
          },
          component: UranusIncome
        }
      ]
    },

    {
      path: '/config',
      component: Layout,
      children: [
        {
          path: '',
          name: 'SystemParams',
          meta: {
            title: '参数设置',
            icon: 'wheel-gear'
          },
          component: SystemParams
        }
      ]
    },

    {
      path: '/admin',
      component: Layout,
      children: [
        {
          path: '',
          name: 'ManagerList',
          meta: {
            title: '管理员列表',
            icon: 'manager',
            role: 1
          },
          component: ManagerList
        }
      ]
    },

    {
      path: '/report',
      component: Layout,
      children: [
        {
          path: '',
          name: 'ErrorReport',
          meta: {
            title: '错误报告',
            icon: 'report-error',
            role: 1
          },
          component: ErrorReport
        }
      ]
    },

    {
      path: '/submit',
      component: Layout,
      children: [
        {
          path: '',
          name: 'SubmitOrder',
          meta: {
            title: '人工充值',
            icon: 'money-fill',
            role: 1
          },
          component: SubmitOrder
        }
      ]
    },

    {
      path: '/401',
      hidden: true,
      component: Layout,
      children: [
        {
          path: '',
          name: 'NotAllow',
          meta: {
            exclude: true
          },
          component: NotAllow
        }
      ]
    },
    { path: '*', component: NotFound, hidden: true }
  ]
})
