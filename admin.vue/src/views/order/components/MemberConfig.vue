<template>
  <el-dialog
    :close-on-click-modal="false"
    :close-on-press-escape="false"
    class="form-wrap"
    title="添加用户"
    visible
    center
    @close="$emit('close')">
    <el-form
      id="configForm"
      ref="configForm"
      :model="formData"
      :rules="formRules"
      label-position="right"
      label-width="120px"
      novalidate
      @submit.native.prevent>
      <el-form-item label="钱包地址：" prop="address">
        <el-input v-model.trim="formData.address" type="text" />
      </el-form-item>
      <el-form-item label="邀请码：" prop="invite_code">
        <el-input v-model.trim="formData.invite_code" type="text" />
      </el-form-item>
      <el-form-item label="用户类型：" prop="is_true">
        <el-radio-group v-model="formData.is_true">
          <el-radio :label="1">真实</el-radio>
          <el-radio :label="2">虚拟<span class="fc-tipe">（彩蛋开奖不发奖励）</span></el-radio>
        </el-radio-group>
      </el-form-item>
    </el-form>
    <div slot="footer">
      <el-button :disabled="isSubmitting" @click="$emit('close')">取消</el-button>
      <el-button
        :loading="isSubmitting"
        type="primary"
        native-type="submit"
        form="configForm"
        @click="handleSubmit">
        保存
      </el-button>
    </div>
  </el-dialog>
</template>

<script>
import {
  addOrderMember
} from '@/apis/order'

export default {
  name: 'MemberConfig',

  data() {
    return {
      isSubmitting: false,
      formData: {
        address: '',
        invite_code: '',
        is_true: 2
      },
      formRules: {
        address: [{ required: true, message: '必填项' }],
        invite_code: [{ required: true, message: '必填项' }],
        is_true: [{ required: true, type: 'number', message: '必填项' }]
      }
    }
  },

  methods: {
    handleSubmit() {
      this.$refs.configForm.validate(valid => {
        if (valid) {
          this.isSubmitting = true
          this.$mResponseHelper(
            addOrderMember(this.formData),
            () => {
              this.$message.success('添加成功')
              this.$emit('close', this.formData)
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
