import Toast from './Toast'

export default {
  install (Vue) {
    const ToastConstructor = Vue.extend(Toast)

    Vue.prototype.$mToast = function (message) {
      const toast = new ToastConstructor({
        el: document.createElement('div'),
        propsData: {
          value: true,
          message
        }
      })
      // toast.$on('input', function (event) {})

      document.body.appendChild(toast.$el)
      setTimeout(() => {
        toast.value = false
      }, 3000)
    }
  }
}
