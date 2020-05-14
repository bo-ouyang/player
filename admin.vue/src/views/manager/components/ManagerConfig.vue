<template>
  <el-dialog
    :close-on-click-modal="false"
    :close-on-press-escape="false"
    :title="`${actionName}账户`"
    class="form-wrap"
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
      <el-form-item label="用户名称：" prop="username">
        <el-input v-model.trim="formData.username" type="text" />
      </el-form-item>
      <el-form-item label="邀请码：" prop="invite_code">
        <el-input v-model.trim="formData.invite_code" :readonly="formData.admin_user_id" type="text" />
      </el-form-item>
      <el-form-item label="登录密码：" prop="password">
        <el-input v-model="formData.password" type="password" />
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
  setManagerItem
} from '@/apis/manager'

export default {
  name: 'ManagerConfig',
  props: {
    userInfo: {
      type: Object,
      required: true
    }
  },

  data() {
    const user = this.userInfo
    return {
      isSubmitting: false,
      actionName: user.admin_user_id ? '编辑' : '添加',
      formData: {
        admin_user_id: user.admin_user_id,
        username: user.username || '',
        invite_code: user.invite_code || '',
        password: ''
      },
      formRules: {
        username: [{ required: true, message: '必填项' }],
        invite_code: [{ required: true, message: '必填项' }],
        password: user.admin_user_id ? [] : [{ required: true, message: '必填项' }]
      }
    }
  },

  methods: {
    handleSubmit() {
      this.$refs.configForm.validate(valid => {
        if (valid) {
          this.isSubmitting = true
          this.$mResponseHelper(
            setManagerItem(this.formData),
            () => {
              this.$message.success('操作成功')
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
