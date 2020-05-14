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
  getSystemStats
} from '@/apis/common'

export default {
  name: 'SystemStats',

  data () {
    return {
      isLoading: true,
      amountList: [
        {
          name: 'label.i01_012',
          amount: 0
        },
        {
          name: 'label.i01_013',
          amount: 0
        },
        {
          name: 'label.i01_014',
          amount: 0,
          unit: 'ETH'
        },
        {
          name: 'label.i01_015',
          amount: 0,
          unit: 'ETH'
        },
        {
          name: 'label.i01_016',
          amount: 0,
          unit: 'ETH'
        },
        {
          name: 'label.i01_018',
          amount: 0
        },
        {
          name: 'label.i01_017',
          amount: 0
        }
      ]
    }
  },

  created () {
    this.$mResponseHelper(
      getSystemStats(),
      data => {
        const list = this.amountList
        ;([
          'token_number',
          'token_destroy',
          'total_static',
          'total_performance',
          'yesterday_performance',
          'super_user',
          'total_egg'
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
</script>
