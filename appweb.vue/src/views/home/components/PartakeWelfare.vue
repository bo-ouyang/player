<template>
  <modal-popup :disabled="isSubmitting" @close="$emit('close', $event)">
    <form novalidate @submit.prevent="handleSubmit">
      <h5 :class="`m${welfare.type}`" class="state">
        <img :src="require(`@/assets/images/icon.0102-${welfare.type}.png`)" width="119" height="85" />
        {{ welfare.type | cheerTypeName }}
        <span class="state-icon">{{ welfare.award }}<i class="fs-mins">ETH</i>/{{ welfare.total }}<i class="fs-mins">ETH</i></span>
      </h5>
      <ul class="frm-wrap">
        <li class="frm-row btn-group">
          <button
            v-for="(amount, index) in amountList"
            :key="index"
            :class="{ active: amount === formData.number }"
            class="btn-group-item"
            type="button"
            @click="handleChange(amount)">
            {{ amount }}ETH
          </button>
        </li>
        <li class="frm-row m--remote">
          <input
            v-model.number="formData.other"
            class="frm-fldtxt"
            type="number"
            :placeholder="$t('info.i01_001_1')"
            formnovalidate
            @focus="formData.number = ''"
          />
        </li>
        <li class="frm-row m--remote">
          <input
            v-model.trim="formData.invite_code"
            :readonly="!!userInfo.inviteCode"
            :placeholder="$t('info.i01_002_1')"
            class="frm-fldtxt"
            type="text"
          />
        </li>
        <li class="frm-row m--remote">
          <button
            :disabled="isSubmitting || !userInfo.sysWalletAddress"
            class="btn-page"
            type="submit">
            <i v-show="isSubmitting" class="iconfont icon-loader" />
            {{ $t('action.i01_001_2') }}
          </button>
        </li>
      </ul>
    </form>
  </modal-popup>
</template>

<script>
import FormValidate from '@/mixins/FormValidate'
import {
  verifyInviteCode,
  createETHOrder
} from '@/apis/common'
import {
  sendEthTransaction
}  from '@/utils/toolkit'

export default {
  name: 'PartakeWelfare',
  mixins: [
    FormValidate
  ],
  props: {
    welfare: Object
  },

  data () {
    const userInfo = this.$store.state.user
    return {
      isSubmitting: false,
      formData: {
        number: 1,
        other: '',
        type: 2,
        egg_type: this.welfare.type,
        invite_code: userInfo.inviteCode ? userInfo.superCode : userInfo.queryCode
      },
      formRules: {
        // number: [],
        other: [
          value => !this.formData.number && !value && this.$t('info.i01_001_1'),
          value => !this.formData.number && value < 0.001 && this.$t('info.i01_001_2', { num: 0.001 })
        ],
        invite_code: [
          value => !value && this.$t('info.i01_002_1')
        ]
      },

      amountList: [1, 5, 10]
    }
  },

  computed: {
    userInfo () {
      return this.$store.state.user
    }
  },

  methods: {
    handleSubmit () {
      this.formValidator(this.formRules, this.formData).then(() => {
        this.isSubmitting = true
        this.$mResponseHelper(
          verifyInviteCode(this.formData),
          {
            [process.env.SUCCESS_CODE]: valid => {
              if (!valid) {
                this.isSubmitting = false
                this.$mToast(this.$t('info.i01_002_2'))
                return false
              }

              const formData = {
                ...this.formData,
                amount: this.formData.number || this.formData.other
              }
              const payInfo = {
                to: (formData.receive_address = this.userInfo.sysWalletAddress[2]),
                from: (formData.address = this.userInfo.walletAddress),
                value: (formData.amount = window.web3.utils ? window.web3.utils.toWei(formData.amount + '') : window.web3.toWei(formData.amount))
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
      }).catch(() => {})
    },

    handleChange (amount) {
      this.formData.number = amount
      this.formData.other = ''
    }
  }
}
</script>

<style lang="scss" scoped>
@import '~@/styles/defines';

.state {
  padding: {
    top: 0.55rem;
    bottom: 0.58rem;
  }
  text-align: center;
  background: url(~@/assets/images/icon.0102-0.png) center 0.75rem / 1.28rem auto no-repeat;

  &.m4 {
    width: 2.71rem;
    @include margin-center;
    background: url(~@/assets/images/icon.0102-4-0.png) center 1.9rem / 100% auto no-repeat;

    img {
      width: 1.413rem;
      margin-bottom: 0.35rem;
    }
  }
  &-icon {
    display: block;
    margin-top: 0.05rem;
    color: $tabon-color;
    font-size: 0.26rem;
  }
  img {
    display: block;
    width: 0.718599rem;
    height: auto;
    margin: 0 auto 0.6rem;
  }
}
</style>
