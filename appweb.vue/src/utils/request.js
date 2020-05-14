import Vue from 'vue'
import Axios from 'axios'
import Store from '@/stores'

// 创建axios实例
const service = Axios.create({
  baseURL: process.env.API_HOST, // api的base_url
  timeout: 5 * 60 * 1000 // 请求超时时间（毫秒）
  // withCredentials: true // 跨域请求携带cookie
})

// request拦截器
service.interceptors.request.use(config => {
  // const userStore = Store.state.User
  // config.headers['Authorization'] = userStore.props.accessToken || ''

  if (!config.params) config.params = {}
  config.params['lang'] = Store.state.userHabit.language

  return config
}, error => {
  // Do something with request error
  console.log(`request error: ${error}`) // for debug
  return Promise.reject(error)
})

// response拦截器
service.interceptors.response.use(
  ({ data }) => {
    data.code = +data.code
    return data
  },
  reason => {
    console.log(`response error: ${reason}`) // for debug
    return Promise.reject(reason)
  }
)

// 11001-用户未登录（过期）
// 14013-用户IP或设备异常
// 11017-用户已在其他设备登录
// const LOSE_CODE = [11001, 14013, 11017]

const navigator = window.navigator
const isAndroid = /Android/i.test(navigator.userAgent)

function networkPrompt () {
  const tipe = '网络不给力，请检查网络状态！'
  if (!navigator.onLine) {
    Vue.prototype.$mToast(tipe)
    return true
  } else if (isAndroid) {
    var networkImg = new Image()
    networkImg.onerror = function () {
      Vue.prototype.$mToast(tipe)
    }
    networkImg.src = `/favcion.ico?r=${Date.now()}`
    return true
  }
}

export default service
export function responseHelper (apiPromise, handleMap = {}) {
  if (typeof handleMap === 'function') {
    handleMap = {
      [process.env.SUCCESS_CODE]: handleMap
    }
  }

  return apiPromise.then((json = {}) => {
    if (Object.keys(json).length) {
      const { code, msg, data } = json
      // if (LOSE_CODE.includes(code)) {
      //   MessageBox.alert('请重新登录！', '登录失效').then(() => {
      //     Store.dispatch('User/fedout').then(() => {
      //       window.location.reload() // 为了重新实例化vue-router对象 避免bug
      //     })
      //   })
      // } else
      if (handleMap[code]) {
        handleMap[code](data, msg, json)
      } else if (code !== process.env.SUCCESS_CODE) {
        if (!handleMap.remain || !handleMap.remain(code, msg, json)) {
          Vue.prototype.$mToast(msg)
        }
      }

      return json
    }
  }).catch(reason => {
    if (reason.request && reason.request.readyState === 4) {
      switch (reason.request.status) {
        case 0:
          if (networkPrompt()) return

          break
        case 504:
          return void Vue.prototype.$mToast('网络连接超时，请稍后重试！')

        default:
      }
    }

    Vue.prototype.$mToast(reason.response ? '智能合约定时自动优化中，马上回来' : reason + '')
  })
}
