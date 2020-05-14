<template>
  <el-form
    ref="configForm"
    :model="formData"
    :rules="formRules"
    label-position="top"
    novalidate
    @submit.native.prevent="handleSubmit">
    <el-form-item label="节点数量" prop="value">
      <el-input v-model.number="formData.value" type="number">
        <span slot="suffix">人</span>
      </el-input>
      <el-button :loading="isSubmitting" type="primary" native-type="submit">保存</el-button>
    </el-form-item>
    <el-form-item class="m--text">实际人数：{{ statsNumber }}</el-form-item>
  </el-form>
</template>

<script>
import {
  setConfigParam
} from '@/apis/config'

export default {
  name: 'UranusNodeConfig',
  props: {
    statsNumber: {
      type: Number,
      default: 0
    },
    configValue: {
      type: null,
      required: true
    }
  },

  data() {
    return {
      isSubmitting: false,
      formData: {
        key: 'super_node_number',
        value: ''
      },
      formRules: {
        value: [
          // { required: true, type: 'number', message: '必填项' },
          // { type: 'number', validator: (rule, value, callback) => {
          //   if (value < 0) {
          //     callback('不能小于 0')
          //   } else if (value !== Math.round(value)) {
          //     callback('限制为整数')
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
