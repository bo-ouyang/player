<template>
  <modal-popup :disabled="isSubmitting" @close="$emit('close', $event)">
    <div class="modal-body">
       <p>{{ $t('text.i01_004') }}</p>
      <p class="state">
        {{ $t('label.i01_033') }}:
        <span class="state-icon">{{ +formData.cash_amount }}ETH</span>
      </p>
      <p class="fc-text">{{ $t('text.i01_005') }}</p>
    </div>
    <p class="modal-footer">
      <button
        :disabled="isSubmitting"
        class="btn-page"
        type="submit"
        tabindex="13"
        @click="handleSubmit">
        <i class="iconfont icon-loader" v-show="isSubmitting" />
        {{ $t('action.i01_002') }}
      </button>
    </p>
  </modal-popup>
</template>

<script>
import {
  submitWithdraw
} from '@/apis/common'

export default {
  name: 'WithdrawConfirm',
  props: {
    formData: Object
  },

  data () {
    return {
      isSubmitting: false
    }
  },

  computed: {
    userInfo () {
      return this.$store.state.user
    }
  },

  methods: {
    handleSubmit () {
      this.isSubmitting = true
      this.$mResponseHelper(
        submitWithdraw({
          invite_code: this.userInfo.inviteCode,
          cycle_id: this.formData.cycle_id
        }),
        () => {
          this.$mToast(this.$t('tipe.i01_002'))

          // 刷新页面重新请求数据
          setTimeout(() => {
            location.reload()
          }, 300)
        }
      ).finally(() => {
        this.isSubmitting = false
      })
    }
  }
}
</script>

<style lang="scss" scoped>
@import '~@/styles/defines';

.modal-body {
  padding-top: 0.24rem;
}

.state {
  padding-top: 0.3rem;
}
</style>
