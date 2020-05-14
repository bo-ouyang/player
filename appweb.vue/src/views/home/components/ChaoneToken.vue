<template>
  <div class="container">
    <div class="pnl-module">
      <div class="pnl-module-header" />
      <div class="pnl-module-content">
        <ul class="tab-menu">
          <li
            class="tab-menu-item"
            @click="showDialog = true">
            {{ $t('title.i01_012') }}
          </li>
        </ul>
        <p class="state">
          {{ $t('label.i01_006') }}:
          <span class="fc-tabon">{{ +totalAmount }}</span>
        </p>
        <form novalidate @submit.prevent="handleSubmit">
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
              <button
                :disabled="!userInfo.inviteCode || isSubmitting || !userInfo.sysWalletAddress"
                class="btn-page"
                type="submit">
                <i v-show="isSubmitting" class="iconfont icon-loader" />
                {{ $t(userInfo.inviteCode ? 'action.i01_004_2' : 'text.i01_000_2') }}
              </button>
            </li>
          </ul>
        </form>
      </div>
      <div class="pnl-module-footer" />
    </div>

    <chaone-record v-if="showDialog" @close="showDialog = false" />
  </div>
</template>

<script>
import FormValidate from '@/mixins/FormValidate'
import ChaoneRecord from './ChaoneRecord'
import {
  createETHOrder,
  getConfigParam
} from '@/apis/common'
import {
  sendEthTransaction
}  from '@/utils/toolkit'

export default {
  name: 'ChaoneToken',
  mixins: [
    FormValidate
  ],
  props: {
    totalAmount: {
      type: [Number, String],
      default: 0
    },
    remainNumber: {
      type: Number,
      default: 0
    }
  },
  components: {
    ChaoneRecord
  },

  data () {
    return {
      showDialog: false,
      isSubmitting: false,
      formData: {
        number: 1,
        other: '',
        type: 3,
        egg_type: ''
      },
      formRules: {
        // number: [],
        other: [
          value => !this.formData.number && !value && this.$t('info.i01_001_1'),
          value => !this.formData.number && value < 0 && this.$t('info.i01_001_2', { num: 0 }),
          value => (value || this.formData.number) * this.tokenPrice > this.remainNumber && this.$t('info.i01_003', { num: this.remainNumber })
        ]
      },

      tokenPrice: 0,
      amountList: [1, 5, 10]
    }
  },

  computed: {
    userInfo () {
      return this.$store.state.user
    }
  },

  created () {
    this.$mResponseHelper(
      getConfigParam({
        key: 'token_price'
      }),
      data => {
        this.tokenPrice = +data
      }
    )
  },

  methods: {
    handleSubmit () {
      this.formValidator(this.formRules, this.formData).then(() => {
        this.$mToast(this.$t('text.i01_009'))
        return
        const formData = {
          ...this.formData,
          amount: this.formData.number || this.formData.other,
          invite_code: this.userInfo.superCode
        }
        const payInfo = {
          to: (formData.receive_address = this.userInfo.sysWalletAddress[1]),
          from: (formData.address = this.userInfo.walletAddress),
          value: (formData.amount = window.web3.utils ? window.web3.utils.toWei(formData.amount + '') : window.web3.toWei(formData.amount))
        }

        this.isSubmitting = true
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

.logo {
  position: absolute;
  top: 0.28rem;
  right: 0.42rem;
  width: 0.4rem;
  height: auto;
}

.tab-menu {
  padding: {
    top: 0.1rem;
    // bottom: 0.3rem;
  }
}

.state {
  margin: {
    top: 0.6rem;
    bottom: 0.1rem;
  }
  font-size: 0.3rem;
  text-align: center;
}
.price {
  margin-bottom: 0.6rem;
  font-size: $small-size;
  text-align: center;
}

.frm-wrap {
  padding: {
    bottom: 0.2rem;
  }
}
</style>
