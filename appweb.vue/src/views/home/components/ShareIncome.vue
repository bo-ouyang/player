<template>
  <div class="container">
    <div class="pnl-module">
      <div class="pnl-module-header" />
      <div class="pnl-module-content">
        <ul class="tab-menu">
          <li
            class="tab-menu-item"
            @click="showDialog = 1">
            {{ $t('title.i01_009') }}
          </li>
          <li
            class="tab-menu-item"
            @click="showDialog = 2">
            {{ $t('title.i01_010') }}
          </li>
        </ul>
        <state-number :title="$t('title.i01_002')" :number="totalAmount" />
        <p class="invite">
          {{ $t('label.i01_004') }}
          <i v-if="isLoading" class="iconfont icon-loader" />
          <span class="invite-code">{{ userInfo.inviteCode }}</span>
        </p>
        <p class="footer">
          <button
            v-clipboard="shareLink"
            :disabled="!userInfo.inviteCode"
            class="btn-page"
            @success="$mToast($t('tipe.i01_003'))">
            {{ $t(userInfo.inviteCode ? 'action.i01_006' : 'text.i01_000') }}
          </button>
        </p>
      </div>
      <div class="pnl-module-footer" />
    </div>

    <share-record v-if="showDialog === 1" @close="showDialog = 0" />
    <share-reward v-if="showDialog === 2" @close="showDialog = 0" />
  </div>
</template>

<script>
import ShareRecord from './ShareRecord'
import ShareReward from './ShareReward'
import {
  queryInviteCode
} from '@/apis/common'

export default {
  name: 'ShareIncome',
  props: {
    totalAmount: {
      type: [Number, String],
      default: 0
    }
  },
  components: {
    ShareRecord,
    ShareReward
  },

  data () {
    return {
      isLoading: true,
      showDialog: 0
      // totalAmount: 0
    }
  },

  computed: {
    userInfo () {
      return this.$store.state.user
    },
    shareLink () {
      return `${location.protocol}//${location.host + location.pathname}?c=${this.userInfo.inviteCode}`
    }
  },

  created () {
    // 用户邀请码
    if (this.$store.state.walletAddress) {
      this.isLoading = false
    } else if (window.ethereum) {
      // Modern dapp browsers...
      // DOC: https://docs.token.im/dapp-sdk/
      window.web3 = new window.Web3(window.ethereum)
      window.ethereum.enable().then(accounts => {
        this.$store.commit('saveUser', { walletAddress: accounts[0] })
        this.getUserCode(accounts[0])
      })
    } else if (window.web3) {
      // Legacy dapp browsers...
      window.web3 = new window.Web3(window.web3.currentProvider)
      window.web3.eth.getAccounts((err, accounts) => {
        if (!err) {
          this.$store.commit('saveUser', { walletAddress: accounts[0] })
          this.getUserCode(accounts[0])
        }
      })
    } else {
      this.isLoading = false
    }
  },

  methods: {
    getUserCode (address) {
      this.$mResponseHelper(
        queryInviteCode({ address }),
        {
          [process.env.SUCCESS_CODE]: data => {
            this.$store.commit('saveUser', {
              inviteCode: data.invite_code,
              superCode: data.super_code || ''
            })
            // this.$emit('update', data.invite_code)
          },
          '11005': () => {
            // 邀请码错误
          }
        }
      ).finally(() => {
        this.isLoading = false
      })
    }
  }
}
</script>

<style lang="scss" scoped>
@import '~@/styles/defines';

.tab-menu {
  padding: {
    top: 0.2rem;
    bottom: 0.3rem;
  }
}

.invite {
  margin: {
    top: 0.65rem;
    bottom: 0.42rem;
  }
  font-size: 0.26rem;
  text-align: center;

  &-code {
    display: block;
    color: $title-color;
    font-size: 0.6rem;
    line-height: 1rem;
    // text-shadow: 0 0.06rem rgba($color: #050b25, $alpha: 0.7);

    background-image: -webkit-linear-gradient(90deg, #45e1fd, #4a9de0);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;

    &:empty {
      width: 2.1rem;
      height: 0.5rem;
      margin: 0.25rem auto 0.67rem;
      background: $tipe-color;
    }
  }
}

.footer {
  padding: 0 0.5rem 0.07rem;
}
</style>
