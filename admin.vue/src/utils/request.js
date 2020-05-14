import axios from 'axios'
import store from '@/store'

import {
  Message,
  MessageBox
} from 'element-ui'

// 11001-用户未登录
// 14013-用户IP或设备异常
// 14014-用户身份异常
// 16006-用户已经在其他设备登录
const LOSE_CODES = [11001, 14008]

// 创建axios实例
const service = axios.create({
  baseURL: process.env.API_HOST, // api 的 base_url
  timeout: 5 * 60 * 1000 // 请求超时时间
})

// request拦截器
service.interceptors.request.use(
  config => {
    config.headers['Authorization'] = store.state.user.props.accessToken || ''

    return config
  },
  error => {
    // Do something with request error
    console.log('request error:', error) // for debug
    return Promise.reject(error)
  }
)

// response 拦截器
service.interceptors.response.use(
  ({ data }) => {
    data.code = +data.code
    return data
  },
  error => {
    console.log('response error:', error) // for debug
    return Promise.reject(error)
  }
)

export default service

export function fetchFormData(config) {
  if (typeof FormData === 'undefined') {
    return Promise.reject(new Error('Your browser does not support FormData.'))
  }

  const formData = new FormData()
  for (const [key, value] of Object.entries(config.data)) {
    if (value instanceof Array) {
      const field = `${key}[]`
      for (const val of value) {
        formData.append(field, val)
      }
    } else if (value !== undefined) {
      formData.append(key, value)
    }
  }
  config.data = formData

  return service(config)
}

/**
 * 接口请求辅助工具：错误码处理及信息提示
 * @param {Promise} apiPromise axios实例
 * @param {Function | Object} handleMap 成功状态码处理函数，或各错误码处理函数映射
 */
export function responseHelper(apiPromise, handleMap = {}) {
  if (typeof handleMap === 'function') {
    handleMap = {
      [process.env.SUCCESS_CODE]: handleMap
    }
  }

  return apiPromise.then((json = {}) => {
    if (Object.keys(json).length) {
      const { code, msg, data } = json
      if (LOSE_CODES.includes(code)) {
        MessageBox.confirm(
          '你已被登出，可以取消继续留在该页面，或者重新登录',
          '确定登出',
          {
            confirmButtonText: '重新登录',
            cancelButtonText: '取消',
            type: 'warning'
          }
        ).then(() => {
          store.dispatch('user/FedOut').then(() => {
            location.reload() // 为了重新实例化vue-router对象 避免bug
          })
        })
      } else if (handleMap[code]) {
        handleMap[code](data)
      } else if (code !== process.env.SUCCESS_CODE) {
        Message.error(msg)
      }

      return json
    }
  }).catch(reason => void Message.error(reason + ''))
}
