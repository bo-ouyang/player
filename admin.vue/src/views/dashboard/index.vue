<template>
  <div class="dashboard-container">
    <panel-group v-for="(list, index) of statsNumber" :key="index" :cate-list="list" />
  </div>
</template>

<script>
import PanelGroup from './components/PanelGroup'

import {
  getHomeData
} from '@/apis/common'

export default {
  name: 'Dashboard',
  components: {
    PanelGroup
  },

  data() {
    return {
      statsNumber: [
        [
          { icon: 'member', title: '会员总数', number: 0 },
          { icon: 'coin-eth', title: '入金总额', number: 0 },
          { icon: 'coin-eth', title: '出金总额', number: 0 },
          { icon: 'coin-eth', title: '后台充值', number: 0 }
        ],
        [
          { icon: 'coin-eth', title: '今日入金', number: 0 },
          { icon: 'coin-eth', title: '今日出金', number: 0 },
          { icon: 'coin-eth', title: '昨日入金', number: 0 },
          { icon: 'coin-eth', title: '昨日出金', number: 0 }
        ]
      ]
    }
  },
  created() {
    this.$mResponseHelper(
      getHomeData(),
      data => {
        const stats = this.statsNumber
        ;([
          [
            'user_number',
            'total_in',
            'total_out',
            'system_recharge'
          ],
          [
            'today_in',
            'today_out',
            'yesterday_in',
            'yesterday_out'
          ]
        ]).forEach((list, i) => {
          list.forEach((field, j) => {
            stats[i][j].number = +data[field]
          })
        })
      }
    )
  }
}
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
.dashboard {
  &-container {
    background-color: #f0f2f5;
    min-height: calc(100vh - 50px);
    padding: 32px;
  }
}
</style>
