import request from '@/utils/request'

export function getAdminDetail() {
  return request({
    url: 'User/adminDetail'
  })
}

export function getHomeData() {
  return request({
    url: 'Home/index'
  })
}
