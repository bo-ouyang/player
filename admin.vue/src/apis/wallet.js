import request from '@/utils/request'

// 投资派息
export function getInvestRecord(formData) {
  return request({
    url: 'Order/investIncome',
    method: 'post',
    data: formData
  })
}

// 奖励派息
export function getRewardRecord(formData) {
  return request({
    url: '',
    method: 'post',
    data: formData
  })
}

// 提现订单
export function getWithdrawOrders(formData) {
  return request({
    url: 'Order/cashOrder',
    method: 'post',
    data: formData
  })
}

// 团队业绩
export function getTeamIncome(formData) {
  return request({
    url: 'Member/teamList',
    method: 'post',
    data: formData
  })
}
