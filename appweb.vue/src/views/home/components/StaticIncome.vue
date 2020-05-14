<template>
  <div class="container">
    <div class="pnl-module">
      <div class="pnl-module-header" />
      <div class="pnl-module-content">
        <ul class="tab-menu">
          <li
            class="tab-menu-item"
            @click="showDialog = 2">
            {{ $t('title.i01_007') }}
          </li>
          <li
            class="tab-menu-item"
            @click="showDialog = 3">
            {{ $t('title.i01_008') }}
          </li>
        </ul>
        <div class="user-state flex-box">
          <div class="flex-equal">
            <h5>{{ $t('text.i01_001_1') }}</h5>
            <p>
              {{ $t('label.i01_001') }}:
              <i v-if="isLoading" class="iconfont icon-loader fc-tabon" />
              <span v-else class="fc-tabon">{{ +walletInfo[1].profit }}ETH</span>
            </p>
            <p>
              {{ $t('label.i01_002') }}:
              <i v-if="isLoading" class="iconfont icon-loader fc-tabon" />
              <span v-else class="fc-tabon">{{ +walletInfo[1].amount }}ETH</span>
            </p>
             <!--<p v-if="walletInfo[1].cash_amount > 0">
              <button
                class="user-state-action"
                @click="handleWithdraw(walletInfo[1])">
                {{ $t('action.i01_003_2') }}&gt;
              </button>
            </p>-->
          </div>
          <div class="flex-equal">
            <h5>{{ $t('text.i01_001_2') }}</h5>
            <p>
              {{ $t('label.i01_001') }}:
              <i v-if="isLoading" class="iconfont icon-loader fc-tabon" />
              <span class="fc-tabon">{{ +walletInfo[2].profit }}ETH</span>
            </p>
            <p>
              {{ $t('label.i01_002') }}:
              <i v-if="isLoading" class="iconfont icon-loader fc-tabon" />
              <span class="fc-tabon">{{ +walletInfo[2].amount }}ETH</span>
            </p>
             <!--<p v-if="walletInfo[2].cash_amount > 0">
              <button
                class="user-state-action"
                @click="handleWithdraw(walletInfo[2])">
                {{ $t('action.i01_003_2') }}&gt;
              </button>
            </p>-->
          </div>
        </div>
        <form novalidate @submit.prevent="handleSubmit">
          <ul class="frm-wrap">
            <span class="icon" style="color: #0ceaf3;font-size: 0.27rem">{{$t('info.i01_005')}}ETH</span>
            <li class="frm-row">
              <input
                v-model.number="formData.other"
                class="frm-fldtxt"
                type="number"
                :placeholder="$t('info.i01_001_1')"
                formnovalidate
                @focus="formData.amount = ''"
              />
            </li>
            <li class="frm-row m--remote">
              <button
                class="btn-page"
                type="submit">
                {{ $t('action.i01_001_2') }}
              </button>
            </li>
          </ul>
          <p>
          </p>
        </form>
      </div>
      <div class="pnl-module-footer" />
    </div>

    <partake-activity
      v-if="showDialog === 1"
      :form-data="configForm"
      @close="showDialog = 0"
    />
    <withdraw-confirm
      v-if="showDialog === 4"
      :form-data="walletData"
      @close="showDialog = 0"
    />
    <static-record v-if="showDialog === 2" @close="showDialog = 0" />
    <static-reward v-if="showDialog === 3" @close="showDialog = 0" />
  </div>
</template>

<script>
import FormValidate from '@/mixins/FormValidate'
import PartakeActivity from './PartakeActivity'
import WithdrawConfirm from './WithdrawConfirm'
import StaticRecord from './StaticRecord'
import StaticReward from './StaticReward'
import {
  getUserInvest
} from '@/apis/common'

export default {
  name: 'StaticIncome',
  mixins: [
    FormValidate
  ],
  props: {
    inviteCode: String,
    uranusRole: Number
  },
  components: {
    PartakeActivity,
    WithdrawConfirm,
    StaticRecord,
    StaticReward
  },

  data () {
    return {
      isLoading: false,
      showDialog: 0,
      configForm: {
        number: '',
        type: 1,
        egg_type: ''
      },
      formData: {
        amount: 1,
        other: ''
      },
      formRules: {
        // amount: [],
        other: [
          value => !this.formData.amount && !value && this.$t('info.i01_001_1'),
          value => !this.formData.amount && !Number.isInteger(value) && this.$t('info.i01_006'),
          value => !this.formData.amount && value < 1 && this.$t('info.i01_001_2', { num: 1 })
        ]
      },

      walletInfo: {
        1: {
          profit: 0,
          amount: 0
        },
        2: {
          profit: 0,
          amount: 0
        }
      },
      walletData: null,
      amountList: [1, 10, 30]
    }
  },

  watch: {
    inviteCode (code) {
      if (code) {
        this.getUserIncome()
      }
    }
  },

  created () {
    if (this.inviteCode) {
      this.getUserIncome()
    }
  },

  methods: {
    getUserIncome () {
      this.isLoading = true
      this.$mResponseHelper(
        getUserInvest({
          invite_code: this.inviteCode
        }),
        data => {
          this.walletInfo = data
        }
      ).finally(() => {
        this.isLoading = false
      })
    },

    handleSubmit () {
      this.formValidator(this.formRules, this.formData).then(() => {
        console.log(this.formData)
        this.configForm.number = this.formData.amount || this.formData.other
        this.showDialog = 1
      }).catch(() => {})
    },

    handleChange (amount) {
      this.formData.amount = amount
      this.formData.other = ''
    },
    handleWithdraw (info) {
      this.showDialog = 4
      this.walletData = info
    }
  }
}
</script>

<style lang="scss" scoped>
@import '~@/styles/defines';

.pnl-module {
  font-size: 0.3rem;
}

.tab-menu {
  padding: {
    top: 0.1rem;
    bottom: 0.06rem;
  }
}

.user-state {
  padding: {
    left: 0.36rem;
    bottom: 0.2rem;
  }
  color: #20d5ff;

  h5 {
    @include margin-tb(0.36rem);
    color: $title-color;
  }
  p {
    @include margin-tb(0.26rem);
    font-size: 0.26rem;
  }
  &-action {
    color: #cccccc;
    font-size: $small-size;
  }
}
</style>
