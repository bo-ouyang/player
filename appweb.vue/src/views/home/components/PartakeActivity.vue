<template>
  <modal-popup :disabled="isSubmitting" @close="$emit('close', $event)">
    <h3 class="modal-header">{{ $t('action.i01_001') }}</h3>
    <form novalidate @submit.prevent="handleSubmit">
      <ul class="modal-body">
        <li class="state">
          {{ $t('label.i01_019') }}:
          <span class="state-icon">{{ formData.number }}ETH</span>
        </li>
        <li>
          <input
            v-model.trim="superInviteCode"
            :readonly="!!userInfo.inviteCode"
            :placeholder="$t('info.i01_002_1')"
            class="frm-fldtxt"
            type="text"
          />
        </li>
      </ul>
      <p class="modal-footer">
        <button
          :disabled="isSubmitting || !userInfo.sysWalletAddress"
          class="btn-page"
          type="submit"
          tabindex="13">
          <i class="iconfont icon-loader" v-show="isSubmitting" />
          {{ $t('action.i01_002') }}
        </button>
      </p>
    </form>
  </modal-popup>
</template>

<script>
import {
  verifyInviteCode,
  createETHOrder
} from '@/apis/common'
import {
  sendEthTransaction
}  from '@/utils/toolkit'

export default {
  name: 'PartakeActivity',
  props: {
    formData: Object
  },

  data () {
    const userInfo = this.$store.state.user
    return {
      isSubmitting: false,
      superInviteCode: userInfo.inviteCode ? userInfo.superCode : userInfo.queryCode
    }
  },

  computed: {
    userInfo () {
      return this.$store.state.user
    }
  },

  methods: {
    handleSubmit () {
      if (this.superInviteCode) {
        this.isSubmitting = true
        this.$mResponseHelper(
          verifyInviteCode({
            invite_code: this.superInviteCode
          }),
          {
            [process.env.SUCCESS_CODE]: valid => {
              if (!valid) {
                this.isSubmitting = false
                this.$mToast(this.$t('info.i01_002_2'))
                return false
              }

              const formData = {
                ...this.formData,
                invite_code: this.superInviteCode
              }
              const payInfo = {
                to: (formData.receive_address = this.userInfo.sysWalletAddress[0]),
                from: (formData.address = this.userInfo.walletAddress),
                value: (formData.amount = window.web3.utils ? window.web3.utils.toWei(formData.number + '') : window.web3.toWei(formData.number))
              }
              sendEthTransaction(payInfo).then(txHash => {
                this.$mToast(this.$t('tipe.i01_001'))
                formData.hash = txHash

                return this.$mResponseHelper(
                  createETHOrder(formData).catch(reason => {
                    this.$store.dispatch('reportError', {
                      payInfo: JSON.stringify(payInfo),
                      formData: JSON.stringify(formData),
                      reason: reason + ''
                    })

                    return Promise.reject(reason)
                  }),
                  {
                    [process.env.SUCCESS_CODE]: data => {
                      this.$mToast(this.$t('tipe.i01_002'))

                      // 刷新页面重新请求数据
                      setTimeout(() => {
                        location.reload()
                      }, 300)
                    },
                    remain: (code, msg, json) => {
                      this.$store.dispatch({
                        payInfo: JSON.stringify(payInfo),
                        formData: JSON.stringify(formData),
                        result: JSON.stringify(json)
                      })
                    }
                  }
                )
              }).catch(() => {}).finally(() => {
                this.isSubmitting = false
              })
            },
            remain: () => {
              this.isSubmitting = false
            }
          }
        ).catch(() => {
          this.isSubmitting = false
        })
      } else {
        this.$mToast(this.$t('info.i01_002_1'))
      }
    }
  }
}
</script>
