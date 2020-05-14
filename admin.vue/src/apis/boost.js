import request from '@/utils/request'

export function signin({ username, password }) {
  return request({
    url: 'User/login',
    method: 'post',
    data: {
      username,
      password
    }
  })
}

export function logout() {
  return request({
    url: 'User/logout',
    method: 'post'
  })
}
