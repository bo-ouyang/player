import request from '@/utils/request'

// 会员列表
export function getMemberList(formData) {
  return request({
    url: 'Member/list',
    method: 'post',
    data: formData
  })
}

export function changeMemberParent(formData) {
  return request({
    url: 'Member/parentChange',
    method: 'post',
    data: formData
  })
}

export function setMemberNode(formData) {
  return request({
    url: 'Member/groupEdit',
    method: 'post',
    data: formData
  })
}
