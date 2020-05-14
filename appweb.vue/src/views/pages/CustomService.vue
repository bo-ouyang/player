<template>
  <div class="container">
    <form @submit.prevent="handleSubmit">
      <div class="row-info">
        <textarea v-model.trim="formData.content" rows="5" placeholder="请输入您的意见"></textarea>
      </div>
      <p class="btn-page-row">
        <button :disabled="isSubmitting" class="btn-page btn--hold btn--primary btn--circle">
          <i v-show="isSubmitting" class="iconfont icon-loader" />
          提交
        </button>
      </p>
    </form>
  </div>
</template>

<script>
import FormValidate from '@/mixins/FormValidate'
import {
  giveFeedback
} from '@/apis/common'

export default {
  name: 'CustomService',
  mixins: [ FormValidate ],

  data () {
    return {
      isSubmitting: false,
      formData: {
        content: ''
      },
      formRules: {
        content: [
          value => !value && '请输入反馈意见'
        ]
      }
    }
  },

  methods: {
    handleSubmit () {
      if (this.formValidator(this.formRules, this.formData).valid) {
        this.isSubmitting = true
        this.$mResponseHelper(
          giveFeedback(this.formData),
          () => {
            this.$mToast('提交成功')
            this.$router.back()
          }
        ).finally(() => {
          this.isSubmitting = false
        })
      }
    }
  }
}
</script>
