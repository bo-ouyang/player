<template>
  <button :disabled="notSendCode" type="button" @click="handleSendCode">
    <i v-show="isSending" class="iconfont icon-loader" />
    {{ countDown > 0 ? countDown + 's' : '获取验证码' }}
  </button>
</template>

<script>
import {
  sendEmailCode
} from '@/apis/common'

export default {
  name: 'EmailCode',
  props: {
    email: String,
    action: String
  },

  data () {
    return {
      isSending: false,
      countDown: 0,
      downTimer: null
    }
  },

  computed: {
    notSendCode () {
      return this.isSending || this.countDown > 0 || !this.email.includes('@')
    }
  },

  beforeDestroy () {
    window.clearInterval(this.downTimer)
  },

  methods: {
    handleSendCode () {
      this.isSending = true
      this.$mResponseHelper(
        sendEmailCode(this.email, this.action),
        data => {
          this.countDown = (data && data.interval) || 60
          this.downTimer = window.setInterval(this.downCounter, 1000)
        }
      ).finally(() => {
        this.isSending = false
      })
    },

    downCounter () {
      if ((this.countDown -= 1) <= 0) {
        window.clearInterval(this.downTimer)
      }
    }
  }
}
</script>
