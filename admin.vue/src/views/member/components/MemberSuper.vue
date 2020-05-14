<template>
  <el-dialog
    :close-on-click-modal="false"
    :close-on-press-escape="false"
    class="form-mins"
    title="变更上级"
    visible
    center
    @close="$emit('close')">
    <el-form
      id="configForm"
      ref="configForm"
      :model="formData"
      :rules="formRules"
      label-position="right"
      label-width="138px"
      novalidate
      @submit.native.prevent>
      <el-form-item label="所属上级邀请码：" prop="invite_code">
        <el-input v-model.trim="formData.invite_code" type="text" />
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
        提交
      </el-button>
    </div>
  </el-dialog>
</template>

<script>
import {
  changeMemberParent
} from '@/apis/member'

export default {
  name: 'MemberSuper',
  props: {
    userInfo: {
      type: Object,
      required: true
    }
  },

  data() {
    return {
      isSubmitting: false,
      formData: {
        user_id: this.userInfo.user_id,
        invite_code: this.userInfo.parent_invite_code
      },
      formRules: {
        invite_code: [{ required: true, message: '必填项' }]
      }
    }
  },

  methods: {
    handleSubmit() {
      this.$refs.configForm.validate(valid => {
        if (valid) {
          this.isSubmitting = true
          this.$mResponseHelper(
            changeMemberParent(this.formData),
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
