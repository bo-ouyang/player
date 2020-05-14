<template>
  <el-form
    ref="configForm"
    :model="formData"
    :rules="formRules"
    label-position="top"
    novalidate
    @submit.native.prevent="handleSubmit">
    <el-form-item label="后台充值ID" prop="value">
      <el-input v-model.trim="formData.value" type="text" placeholder="多值以英文逗号,分隔" />
      <el-button :loading="isSubmitting" type="primary" native-type="submit">保存</el-button>
    </el-form-item>
  </el-form>
</template>

<script>
import {
  setConfigParam
} from '@/apis/config'

export default {
  name: 'SystemRechargeConfig',
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
        key: 'system_recharge_id',
        value: ''
      },
      formRules: {
        value: []
      }
    }
  },

  watch: {
    configValue(value) {
      this.formData.value = value
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
