import {
  getUserInfo,
  setUserInfo,
  delUserInfo,
  getUserHabit,
  setUserHabit
} from '@/utils/storage'
import {
  signin,
  logout,
  varyUserRole
} from '@/apis/boost'
import {
  getUserDetail
} from '@/apis/common'
import {
  responseHelper
} from '@/utils/request'

export default {
  namespaced: true,
  state: {
    habit: getUserHabit(), // 用户上次登录账号
    props: getUserInfo(), // 用户登录信息
    login: null // 注册成功后快捷登录
  },

  mutations: {
    saveInfo ({ props }, payload) {
      for (const [key, value] of Object.entries(payload)) {
        props[key] = value
      }
      props.expireTime = setUserInfo(props)
    },
    updateTime ({ props }) {
      props.expireTime = setUserInfo(props)
    },
    clearInfo (state) {
      state.props = {}
      delUserInfo()
    },
    saveEmail ({ habit }, email) {
      habit.email = email
      setUserHabit(habit)
    },
    saveLanguage ({ habit }, language) {
      habit.language = language
      setUserHabit(habit)
    },
    saveLogin (state, payload) {
      state.login = payload && {
        email: payload.email,
        loginPassword: payload.loginPassword
      }
    }
  },

  actions: {
    signin ({ commit }, payload) {
      return responseHelper(
        signin(payload),
        data => {
          commit('saveInfo', {
            userId: data.user_id,
            accessToken: data.auth,
            userRole: 0, // 1-真实账号 2-虚拟账号
            email: payload.email
          })
          // 保存登录邮箱
          commit('saveEmail', payload.email)

          // 获取用户详情
          responseHelper(
            getUserDetail(),
            data => {
              if (!data.username) {
                data.username = /^([^@]+)@/.exec(data.email)
              }
              commit('saveInfo', {
                userName: data.username ? data.username[1] : 'USER-XXXX',
                avatar: data.avatar,
                group: data.group, // 用户分组：1-普通用户 2-代理人
                code: data.invite_code
              })
            }
          )
        }
      )
    },
    logout ({ commit }) {
      return responseHelper(
        logout(),
        () => void commit('clearInfo')
      )
    },
    fedout ({ commit }) {
      commit('clearInfo')

      return Promise.resolve({
        code: process.env.SUCCESS_CODE,
        msg: 'success'
      })
    },
    autoLogin ({ state: { login }, commit }) {
      if (login) commit('saveLogin', null)

      return Promise.resolve(login)
    },
    varyRole ({ state: { props }, commit }) {
      const role = [2, 2, 1][props.userRole]
      return responseHelper(
        varyUserRole(role),
        data => void commit('saveInfo', {
          userId: data.user_id,
          accessToken: data.auth,
          userRole: role
        })
      )
    }
  }
}
