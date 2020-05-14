<template>
  <div class="pnl-module container">
    <div class="pnl-module-header" />
    <div class="pnl-module-content">
      <ul class="lst-stats">
        <li
          v-for="(item, index) in amountList"
          :key="index"
          :class="{ 'bod-t-1px': index > 0 }"
          class="lst-stats-item flex-box">
          <h6 class="flex-grown text-ellipsis">{{ $t(item.name) }}:</h6>
          <p class="flex-fixed fc-tabon align-r">{{ item.amount }}{{ item.unit }}</p>
        </li>
      </ul>
    </div>
    <div class="pnl-module-footer" />
  </div>
</template>

<script>
import {
  getUserDetail
} from '@/apis/common'

export default {
  name: 'WalletStats',
  props: {
    inviteCode: String
  },

  data () {
    return {
      isLoading: false,
      amountList: [
        {
          name: 'label.i01_007',
          amount: 0,
          unit: 'ETH'
        },
        {
          name: 'label.i01_008',
          amount: 0,
          unit: 'ETH'
        },
        {
          name: 'title.i01_002',
          amount: 0,
          unit: 'ETH'
        },
        {
          name: 'title.i01_004',
          amount: 0
        },
        {
          name: 'label.i01_011',
          amount: 0,
          unit: 'ETH'
        },
        {
          name: 'label.i01_010',
          amount: 0,
          unit: 'ETH'
        }
      ]
    }
  },

  watch: {
    inviteCode (code) {
      if (code) {
        this.getWalletStats()
      }
    }
  },

  created () {
    if (this.inviteCode) {
      this.getWalletStats()
    }
  },

  methods: {
    getWalletStats () {
      this.isLoading = true
      this.$mResponseHelper(
        getUserDetail({
          invite_code: this.inviteCode
        }),
        data => {
          const list = this.amountList
          ;([
            'static_amount',
            'static_profit',
            'invite_profit',
            'token_amount',
            'super_profit',
            'egg_profit'
          ]).forEach((field, index) => {
            list[index].amount = +data[field]
          })
          this.$emit('update', data)
        }
      ).finally(() => {
        this.isLoading = false
      })
    }
  }
}
</script>
