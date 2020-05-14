import request from '@/utils/request'

// 投资订单
export function getSendLog(formData) {
  return request({
    url: 'Order/SendLog',
    method: 'post',
    data: formData
  })
}

// 投资订单
export function getInvestOrders(formData) {
  return request({
    url: 'Order/investOrder',
    method: 'post',
    data: formData
  })
}
export function getInvestIncome(formData) {
  return request({
    url: 'Order/investIncome',
    method: 'post',
    data: formData
  })
}

// 奖励订单
export function getRewardOrders(formData) {
  return request({
    url: 'Order/rewardIncome',
    method: 'post',
    data: formData
  })
}

// X1令牌
export function getTokenOrders(formData) {
  return request({
    url: 'Order/tokenList',
    method: 'post',
    data: formData
  })
}

// 股东收益
export function getHolderIncome(formData) {
  return request({
    url: 'Order/getTeamAmount',
    method: 'post',
    data: formData
  })
}

// 彩蛋
export function getCheerOrders(formData) {
  return request({
    url: 'Order/eggOrder',
    method: 'post',
    data: formData
  })
}
export function getCheerAccount(formData) {
  return request({
    url: 'Order/drawSetting',
    method: 'post',
    data: formData
  })
}
export function drawCheerTimes(formData) {
  return request({
    url: 'Order/draw',
    method: 'post',
    data: formData
  })
}

// 节点收益
export function getNurausIncome(formData) {
  return request({
    url: 'Order/superReward',
    method: 'post',
    data: formData
  })
}

// 添加用户
export function addOrderMember(formData) {
  return request({
    url: 'Member/memberAdd',
    method: 'post',
    data: formData
  })
}
