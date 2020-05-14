
export default {
  methods: {
    resetQueryForm() {
      for (const key of Object.keys(this.queryForm)) {
        this.queryForm[key] = ''
      }

      // 清除表单验证
      this.$refs.queryForm.validate()
      // this.$refs.queryForm.resetFields()
    },

    convertString2Date(date) {
      return new Date(
        +date.slice(0, 4),
        +date.slice(5, 7) - 1,
        +date.slice(8, 10)
      )
    },

    validateMobilePhone(rule, value, callback, source, options) {
      if (!value || /^1\d{10}$/.test(value)) {
        callback()
      } else {
        callback('请输入正确的手机号码')
      }
    },
    validateEmailAddress(rule, value, callback, source, options) {
      if (!value || value.includes('@')) {
        callback()
      } else {
        callback('请输入正确的邮箱地址')
      }
    },
    validateNumberRange(field, formField = 'queryForm') {
      return (rules, value, callback) => {
        const formData = this[formField]
        const startNum = formData[field + '_low']
        const endNum = formData[field + '_high']
        // v-model.number非法数值返回空字符串
        if (startNum !== '' && endNum !== '' && startNum > endNum) {
          callback(new Error('起始值不能大于终止值'))
        } else {
          callback()
        }
      }
    },
    validatePercent(rule, value, callback, source, options) {
      if (!value || value <= +value.toFixed(3)) {
        callback()
      } else {
        callback('最多允许输入 3 位小数')
      }
    }
  }
}
