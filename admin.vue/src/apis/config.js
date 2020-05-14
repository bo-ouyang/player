import request from '@/utils/request'

// 错误日志
export function getErrorReport(formData) {
  return request({
    url: 'Order/errorList',
    method: 'post',
    data: formData
  })
}

export function getConfigParams() {
  return request({
    url: 'Home/config'
  })
}
export function setConfigParam(formData) {
  return request({
    url: 'Home/configEdit',
    method: 'post',
    data: formData
  })
}

export function getWalletAddress() {
  return request({
    url: 'Order/contractAddress'
  })
}
export function submitOrder(formData) {
  return request({
    url: 'Order/create',
    method: 'post',
    data: formData
  })
}
