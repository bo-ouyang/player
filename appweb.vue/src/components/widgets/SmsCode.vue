<template>
  <button :disabled="notSendCode" type="button" @click="handleSendCode">
    <i v-show="isSending" class="iconfont icon-loader" />
    {{ countDown > 0 ? countDown + 's' : '获取验证码' }}
  </button>
</template>

<script>
import {
  sendPhoneCode
} from '@/apis/common'

export default {
  name: 'SmsCode',
  props: {
    smsAction: String,
    mobilePhone: String
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
      return this.isSending || this.countDown > 0 || !/^1\d{10}$/.test(this.mobilePhone)
    }
  },

  beforeDestroy () {
    window.clearInterval(this.downTimer)
  },

  methods: {
    handleSendCode () {
      this.$mResponseHelper(
        sendPhoneCode(this.mobilePhone, this.smsAction),
        data => {
          this.countDown = data.list.interval || 60
          this.downTimer = window.setInterval(this.downCounter, 1000)
        }
      )
    },

    downCounter () {
      if ((this.countDown -= 1) <= 0) {
        window.clearInterval(this.downTimer)
      }
    }
  }
}
</script>
