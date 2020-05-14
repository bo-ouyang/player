<template>
  <el-form
    ref="configForm"
    :model="formData"
    :rules="formRules"
    label-position="top"
    novalidate
    @submit.native.prevent="handleSubmit">
    <el-form-item label="静态收益率" prop="value">
      <el-input v-model.number="formData.value" type="number">
        <span slot="suffix">%</span>
      </el-input>
      <el-button :loading="isSubmitting" type="primary" native-type="submit">保存</el-button>
    </el-form-item>
  </el-form>
</template>

<script>
import {
  setConfigParam
} from '@/apis/config'

export default {
  name: 'InvestRateConfig',
  props: {
    configValue: {
      type: null,
      required: true
    }
  },

  data() {
    return {
      isSubmitting: false,
      formData: {
        key: 'interest',
        value: ''
      },
      formRules: {
        value: [
          // { required: true, type: 'number', message: '必填项' },
          // { type: 'number', validator: (rule, value, callback) => {
          //   if (value < 0) {
          //     callback('不能小于 0')
          //   } else {
          //     callback()
          //   }
          // } }
        ]
      }
    }
  },

  watch: {
    configValue(value) {
      this.formData.value = value * 100
    }
  },

  methods: {
    handleSubmit() {
      this.$refs.configForm.validate(valid => {
        if (valid) {
          this.isSubmitting = true
          this.$mResponseHelper(
            setConfigParam({
              ...this.formData,
              value: +(this.formData.value / 100).toFixed(5)
            }),
            () => {
              this.$message.success('保存成功')
            }
          ).finally(() => {
            this.isSubmitting = false
          })
        }
      })
    }
  }
}
</script>
