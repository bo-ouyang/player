<template>
  <div class="app-container">
    <el-row class="query-form">
      <el-col :span="12" :xs="24">
        <invest-rate-config :config-value="configParams.interest" />
        <invest-pool-config :config-value="configParams.base_Jackpot" :stats-number="statsInfo.total_jackpot" />
        <system-amount-config :config-value="configParams.volume" />
        <uranus-amount-config :config-value="configParams.base_performance" :stats-number="statsInfo.performance" />
        <uranus-today-config :config-value="configParams.today_performance" :stats-number="statsInfo.today_performance" />
        <uranus-yester-config :config-value="configParams.base_y_performance" :stats-number="statsInfo.yesterday_performance" />
        <uranus-node-config :config-value="configParams.super_node_number" :stats-number="statsInfo.super_node_number" />
      </el-col>
      <el-col :span="12" :xs="24">
        <token-price-config :config-value="configParams.token_price" />
        <token-publish-config :config-value="configParams.total_token" />
        <token-destroy-config :config-value="configParams.token_destroy" />
        <token-limit-config :config-value="configParams.token_limit" :stats-number="statsInfo.token_number" />
        <!--<cheer-times-config :config-value="configParams.base_egg" :stats-number="statsInfo.total_egg_amount" />-->
        <system-recharge-config :config-value="configParams.system_recharge_id" />
      </el-col>
    </el-row>
  </div>
</template>

<script>
import InvestRateConfig from './components/InvestRateConfig'
import InvestPoolConfig from './components/InvestPoolConfig'
import TokenPriceConfig from './components/TokenPriceConfig'
import TokenPublishConfig from './components/TokenPublishConfig'
import TokenDestroyConfig from './components/TokenDestroyConfig'
import TokenLimitConfig from './components/TokenLimitConfig'
import SystemAmountConfig from './components/SystemAmountConfig'
import UranusAmountConfig from './components/UranusAmountConfig'
import UranusTodayConfig from './components/UranusTodayConfig'
import UranusYesterConfig from './components/UranusYesterConfig'
import UranusNodeConfig from './components/UranusNodeConfig'
import CheerTimesConfig from './components/CheerTimesConfig'
import SystemRechargeConfig from './components/SystemRechargeConfig'
import {
  getConfigParams
} from '@/apis/config'

export default {
  name: 'SystemParams',
  components: {
    InvestRateConfig,
    InvestPoolConfig,
    TokenPriceConfig,
    TokenPublishConfig,
    TokenDestroyConfig,
    TokenLimitConfig,
    SystemAmountConfig,
    UranusAmountConfig,
    UranusTodayConfig,
    UranusYesterConfig,
    UranusNodeConfig,
    CheerTimesConfig,
    SystemRechargeConfig
  },

  data() {
    return {
      isLoading: true,
      configParams: {},
      statsInfo: {}
    }
  },

  created() {
    this.$mResponseHelper(
      getConfigParams(),
      data => {
        const params = this.configParams = {}
        data.list.forEach(item => {
          params[item.key] = item.value
        })

        const stats = this.statsInfo = {}
        for (const [key, value] of Object.entries(data)) {
          if (key !== 'list') {
            stats[key] = +value
          }
        }
      }
    ).finally(() => {
      this.isLoading = false
    })
  }
}
</script>

<style lang="scss" scoped>
/deep/ .el-form-item {
  &__label {
    font-size: 18px;
    font-weight: bold;
  }
  &.m--text &__content {
    line-height: inherit;
  }
  .el-input {
    width: 320px;
  }
}
</style>
