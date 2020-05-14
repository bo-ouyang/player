
export default {
  // data () {
  //   return {
  //     formRules: {
  //       mobile: [
  //         function (value) { return !value && '请输入手机号码' },
  //         function (value) { return !/^1\d{10}$/.test(value) && '请输入正确的手机号' }
  //       ],
  //       code: [
  //         function (value) { return !value && '请输入短信验证码' }
  //       ],
  //       password: [
  //         function (value) { return !value && '请输入登录密码' },
  //         function (value) { return (value.length < 6 || value.length > 20) && '密码限制 6 至 20 位字符' }
  //       ]
  //     }
  //   }
  // },

  methods: {
    // formValidator (name, value)
    fieldValidator (rules, value) {
      for (let i = 0; i < rules.length; i++) {
        const message = rules[i](value)
        if (message) return message
      }
    },
    async formValidator (ruleMap, formData) {
      const fields = Object.keys(ruleMap)
      for (const field of fields) {
        const message = this.fieldValidator(ruleMap[field], formData[field])
        if (message) {
          this.$mToast(message)
          throw new Error(`${field}: ${message}`)
        }
      }
      return { valid: true }
    }
  }
}
