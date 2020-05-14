import request from '@/utils/request'

export function getManagerList(formData) {
  return request({
    url: 'User/userList',
    method: 'post',
    data: formData
  })
}
export function setManagerItem(formData) {
  return request({
    url: formData.admin_user_id ? 'User/updateUser' : 'User/register',
    method: 'post',
    data: formData
  })
}
