import request from '@/utils/request'

// 用户钱包
export function getUserDetail (formData) {
  return request({
    url: 'User/detail',
    method: 'post',
    data: formData
  })
}
export function getUserInvest (formData) {
  return request({
    url: 'User/investDetail',
    method: 'post',
    data: formData
  })
}
export function getInvestRecord (formData) {
  return request({
    url: 'User/rechargeList',
    method: 'post',
    data: formData
  })
}
export function getInvestIncome (formData) {
  return request({
    url: 'User/staticProfit',
    method: 'post',
    data: formData
  })
}
export function getUserTeams (formData) {
  return request({
    url: 'User/teamList',
    method: 'post',
    data: formData
  })
}
export function getInviteIncome (formData) {
  return request({
    url: 'User/inviteProfit',
    method: 'post',
    data: formData
  })
}
export function getUserUpgrade (formData) {
  return request({
    url: 'User/upgradeLog',
    method: 'post',
    data: formData
  })
}
export function getGradeReward (formData) {
  return request({
    url: 'User/teamProfit',
    method: 'post',
    data: formData
  })
}
export function getTokenRecord (formData) {
  return request({
    url: 'User/tokenList',
    method: 'post',
    data: formData
  })
}
export function getNodeReward (formData) {
  return request({
    url: 'User/superReward',
    method: 'post',
    data: formData
  })
}
export function getCheerRecord (formData) {
  return request({
    url: 'Home/eggOrder',
    method: 'post',
    data: formData
  })
}
export function getCheerReward (formData) {
  return request({
    url: 'Home/eggReward',
    method: 'post',
    data: formData
  })
}

// 系统钱包地址
export function getWalletAddress () {
  return request({
    url: 'Home/contractAddress'
  })
}
export function getSystemStats () {
  return request({
    url: 'Home/statistic'
  })
}
export function getCheerTimes () {
  return request({
    url: 'Home/eggDetail'
  })
}

export function getConfigParam (formData) {
  return request({
    url: 'Home/config',
    method: 'post',
    data: formData
  })
}

export function queryInviteCode (formData) {
  return request({
    url: 'Home/queryCode',
    method: 'post',
    data: formData
  })
}
export function verifyInviteCode (formData) {
  return request({
    url: 'User/codeExists',
    method: 'post',
    data: formData
  })
}

export function getGradeUser (formData) {
  return request({
    url: 'Home/gradeList',
    method: 'post',
    data: formData
  })
}

// 支付参与
export function createETHOrder (formData) {
  return request({
    url: 'Order/create',
    method: 'post',
    data: formData
  })
}

// 订单提现
export function submitWithdraw (formData) {
  return request({
    url: 'User/cash',
    method: 'post',
    data: formData
  })
}

// 错误报告
export function reportPayError (formData) {
  return request({
    url: 'order/saveInfo',
    method: 'post',
    data: formData
  })
}

// 语言列表
// export function getLanguageList () {
//   return request({
//     url: 'Home/languageList'
//   })
// }

// 意见反馈
// export function giveFeedback (formData) {
//   return request({
//     url: 'User/feedback',
//     method: 'post',
//     data: formData
//   })
// }

// 短信验证码
// export function sendPhoneCode (phone, action) {
//   return request({
//     url: `code/${action}`,
//     method: 'post',
//     data: {
//       phone
//     }
//   })
// }
// 邮箱验证码
// export function sendEmailCode (email, action) {
//   return request({
//     url: `code/${action}`,
//     method: 'post',
//     data: {
//       email
//     }
//   })
// }
