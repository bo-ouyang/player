import {
  getUserInfo,
  setUserInfo,
  delUserInfo
} from '@/utils/storage'
import {
  signin,
  logout
} from '@/apis/boost'
import {
  getAdminDetail
} from '@/apis/common'
import {
  responseHelper
} from '@/utils/request'

const user = {
  namespaced: true,

  state: {
    props: getUserInfo(),
    admin: null
  },

  mutations: {
    SAVE({ props }, payload) {
      for (const key of Object.keys(payload)) {
        props[key] = payload[key]
      }
      props.expriedTime = setUserInfo({ ...props })
    },
    UPDATE({ props }) {
      props.expriedTime = setUserInfo({ ...props })
    },
    CLEAR(state) {
      state.props = {}
      delUserInfo()
    },
    SAVE_ADMIN(state, payload) {
      state.admin = payload
    }
  },

  actions: {
    // 登录
    SignIn({ commit }, formData) {
      return responseHelper(
        signin(formData),
        data => {
          commit('SAVE', {
            accessToken: data.auth,
            userId: data.user_id,
            userName: formData.username
          })
        }
      )
    },

    getInfo({ commit }) {
      return responseHelper(
        getAdminDetail(),
        data => {
          commit('SAVE_ADMIN', data)
        }
      )
    },

    // 接口 登出
    LogOut({ commit }) {
      return responseHelper(
        logout()
      ).finally(() => void commit('CLEAR'))
    },

    // 前端 登出
    FedOut({ commit }) {
      commit('CLEAR')

      return Promise.resolve({
        code: process.env.SUCCESS_CODE,
        msg: 'success',
        data: null
      })
    }
  }
}

export default user
