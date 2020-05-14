import Vue from 'vue'
import Vuex from 'vuex'
import app from './modules/app'
import user from './modules/user'
import getters from './getters'

Vue.use(Vuex)

const store = new Vuex.Store({
  state: {
    routes: []
  },
  modules: {
    app,
    user
  },
  getters,
  mutations: {
    SAVE_ROUTES(state, payload) {
      state.routes.push(...payload)
    }
  }
})

export default store
