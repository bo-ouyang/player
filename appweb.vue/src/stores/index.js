import Vue from 'vue'
import Vuex from 'vuex'
import i18n from '@/i18n'

import {
  getUserHabit,
  setUserHabit,

  getReportQueue,
  setReportQueue
} from '@/utils/storage'

import {
  reportPayError
} from '@/apis/common'

Vue.use(Vuex)

const queryObject = {}
{
  const paramPairs = location.search.slice(1).split('&')
  for (let param of paramPairs) {
    if (!param) continue
    param = param.split('=')
    queryObject[param[0]] = decodeURIComponent(param[1] || '')
  }
}

export default new Vuex.Store({
  state: {
    reportQueue: getReportQueue(),
    user: {
      sysWalletAddress: null,
      queryCode: queryObject.c, // || process.env.INVITE_CODE,
      superCode: '',
      inviteCode: '',
      walletAddress: null
    },
    userHabit: getUserHabit() // 保存用户选择语言
  },
  mutations: {
    saveUser ({ user }, payload) {
      for (const key of Object.keys(payload)) {
        user[key] = payload[key]
      }
    },
    saveHabit ({ userHabit }, payload) {
      for (const [key, value] of Object.entries(payload)) {
        userHabit[key] = value
      }
      setUserHabit(userHabit)
    },
    saveReport ({ reportQueue }, payload) {
      reportQueue.push(payload)
      setReportQueue(reportQueue)
    },
    removeReport ({ reportQueue }, payload) {
      if (reportQueue.includes(payload)) {
        reportQueue.splice(reportQueue.indexOf(payload), 1)
        setReportQueue(reportQueue)
      }
    }
  },
  actions: {
    async changeLanguage ({ commit }, language) {
      commit('saveHabit', { language })
      i18n.locale = language

      return {
        code: process.env.SUCCESS_CODE,
        msg: 'success'
      }
    },
    async checkReport ({ state: { reportQueue }, commit }) {
      for (const report of [...reportQueue]) {
        const { code } = await reportPayError(report)
        if (code === process.env.SUCCESS_CODE) {
          commit('removeReport', report)
        } else {
          return
        }
      }
    },
    async reportError ({ commit }, payload) {
      return reportPayError(
        payload
      ).then(({ code }) => {
        if (code !== process.env.SUCCESS_CODE) {
          throw new Error('Report Error.')
        }
      }).catch(() => {
        payload.timeStamp = Math.floor(Date.now() / 1000)
        commit('saveReport', payload)
      })
    }
  },
  strict: true
})
