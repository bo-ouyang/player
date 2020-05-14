<template>
  <el-form
    ref="configForm"
    :model="formData"
    :rules="formRules"
    label-position="top"
    novalidate
    @submit.native.prevent="handleSubmit">
    <el-form-item label="当前ACGG销毁数量" prop="value">
      <el-input v-model.number="formData.value" type="number" />
      <el-button :loading="isSubmitting" type="primary" native-type="submit">保存</el-button>
    </el-form-item>
  </el-form>
</template>

<script>
import {
  setConfigParam
} from '@/apis/config'

export default {
  name: 'TokenDestroyConfig',
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
        key: 'token_destroy',
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
      this.formData.value = +value
    }
  },

  methods: {
    handleSubmit() {
      this.$refs.configForm.validate(valid => {
        if (valid) {
          this.isSubmitting = true
          this.$mResponseHelper(
            setConfigParam(this.formData),
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
