<template>
  <div class="wrap-H100P scrollbox container">
    <div v-if="notDappBrowser" class="tipe-warn">Please use DApp browser to view this page.</div>
    <div class="page-body page-body--side">
      <h2 class="page-top" style="height:2rem;">
        <img class="page-top-icon align-horz" :src="logoPath" />
      </h2>
      <!--<h2 class="page-top" width="1242" height="723">
        <img :src="logoPath" class="page-top-logo" width="1242" height="723" />
      </h2>-->
      <!--<ul class="page-nav clearfix" style="height: 700rem;width: 100%" >
        &lt;!&ndash;<li class="page-nav-item">
          <router-link :to="{ name: 'PlatformRules' }" class="page-nav-mark">{{ $t('page.i01_001') }}</router-link>
        </li>&ndash;&gt;
        &lt;!&ndash;<li class="page-nav-item">
          <span class="page-nav-mark btn" @click="showActionMenu = true">{{ $t('title.i01_018') }}</span>
        </li>&ndash;&gt;
        &lt;!&ndash;<li class="page-nav-item">
          <a class="page-nav-mark btn" href="/docs/ACGG_whitepaper.html">{{ $t('title.i01_019') }}</a>
        </li>&ndash;&gt;
      </ul>-->
      <!--<ul class="page-menu clearfix">
        &lt;!&ndash;<li
          v-for="(menu, index) in menuList"
          :key="index"
          class="page-menu-node btn"
          @click="handleJumpto(menu.target)">
          {{ $t(menu.name) }}
        </li>&ndash;&gt;
      </ul>-->
      <section :ref="menuList[0].target">
        <h3 :label="$t('title.i01_001')" class="pnl-module-title" />
        <static-income :invite-code="userInfo.inviteCode" :uranus-role="walletInfo.is_super" />
      </section>
      <section :ref="menuList[1].target" >
        <h3 :label="$t('title.i01_005')" class="pnl-module-title"  />
        <cheer-times  />
      </section>
      <section :ref="menuList[2].target">
        <h3 :label="$t('title.i01_002')" class="pnl-module-title" />
        <share-income :total-amount="walletInfo.invite_profit" />
      </section>
      <section :ref="menuList[3].target">
        <h3 :label="$t('title.i01_003')" class="pnl-module-title" />
        <holder-level :user-grade="walletInfo.grade" :total-amount="walletInfo.team_profit" :grade-number="gradeNumber" />
      </section>
      <section :ref="menuList[4].target">
        <h3 :label="$t('title.i01_004')" class="pnl-module-title" />
        <chaone-token :total-amount="walletInfo.token_amount" :remain-number="remainToken" />
      </section>
      <section :ref="menuList[5].target">
        <h3 :label="$t('title.i01_006_2')" class="pnl-module-title" />
        <uranus-node :uranus-role="walletInfo.is_super" :left-super-nums="walletInfo.leftSuperNums" />
      </section>
      <!--<section>
        <h3 :label="$t('title.i01_017')" class="pnl-module-title" />
        <div class="pnl-module">
          <div class="pnl-module-header" />
          <p class="page-stats pnl-module-content">
            <img class="page-stats-icon align-horz" src="~@/assets/images/mark.0101.png" width="906" height="1007" />
            <i v-if="isLoading" class="iconfont icon-loader" />
            <span v-else class="page-stats-text">{{totalAmount}}<i class="icon">ETH</i></span>
          </p>
          <div class="pnl-module-footer" />
        </div>
      </section>-->
      <section>
        <h3 :label="$t('title.i01_015')" class="pnl-module-title" />
        <wallet-stats :invite-code="userInfo.inviteCode" @update="handleUpdate" />
      </section>
      <!--<section>
        <h3 :label="$t('title.i01_016')" class="pnl-module-title" />
        <system-stats @update="handleUpdate" />
      </section>-->
    </div>
  </div>
</template>

<script>
import StaticIncome from './components/StaticIncome'
import ShareIncome from './components/ShareIncome'
import HolderLevel from './components/HolderLevel'
import ChaoneToken from './components/ChaoneToken'
import CheerTimes from './components/CheerTimes'
import UranusNode from './components/UranusNode'
import WalletStats from './components/WalletStats'
import SystemStats from './components/SystemStats'
import {
  getWalletAddress
} from '@/apis/common'

export default {
  name: 'HomePage',
  components: {
    StaticIncome,
    ShareIncome,
    HolderLevel,
    ChaoneToken,
    CheerTimes,
    UranusNode,
    WalletStats,
    SystemStats
  },

  data () {
    return {
      isLoading: true,
      notDappBrowser: !(window.ethereum || window.web3),
      menuList: [
        {
          name: 'title.i01_001_2'
        },
        {
          name: 'title.i01_005'
        },
        {
          name: 'title.i01_002_2'
        },
        {
          name: 'title.i01_003_2'
        },
        {
          name: 'title.i01_004_2'
        },
        {
          name: 'title.i01_006'
        }
      ],

      totalAmount: 0,
      remainToken: 0,
      gradeNumber: [],
      walletInfo: {},
      showRemindDialog: false,
      showActionMenu: false
    }
  },

  computed: {
    userInfo () {
      return this.$store.state.user
    },
    logoPath () {
      return this.$store.state.userHabit.language === 'zh-cn'
        ? require('@/assets/images/bg-home.0101_1.png')
        : require('@/assets/images/bg-home.0101_1.png')
    }
  },

  created () {
    if (this.$store.state.userHabit.remind !== '190821') {
      this.showRemindDialog = true
      this.$store.commit('saveHabit', { remind: '190821' })
    }

    this.$store.dispatch('checkReport').catch(() => {})

    // 收款地址
    this.$mResponseHelper(
      getWalletAddress(),
      data => {
        const walletAddress = [data.contract_address, data.token_address, data.egg_address]
        const validWallet = [
          '0x3eCfBCE17C0F8eDbE9BbE003A355e66D7cfF8593', // 默认
          '0x3eCfBCE17C0F8eDbE9BbE003A355e66D7cfF8593', // X1令牌
          '0x3eCfBCE17C0F8eDbE9BbE003A355e66D7cfF8593'  // 超级彩蛋
        ]
        if (walletAddress.every(address => validWallet.includes(address))) {
          this.$store.commit('saveUser', { sysWalletAddress: walletAddress })
        } else {
          this.$mToast(this.$t('tipe.i01_000'))
        }
      }
    )
  },

  methods: {
    handleJumpto (target) {
      this.$refs[target].scrollIntoView()
    },
    handleRedirect (url) {
      location.href = url
    },
    handleUpdate (stats) {
      this.isLoading = false
      if (stats.user_detail) {
        this.walletInfo = stats
        this.totalAmount = stats.total_static
        console.log(this.walletInfo)
      }
      if (stats.sys_detail) {
        this.remainToken = stats.token_limit - stats.today_token_number
        this.gradeNumber = stats.grade
        this.totalAmount = stats.total_static
      }
    }
  }
}
</script>

<style lang="scss" scoped>
@import '~@/styles/defines';

.page {
  &-body {
    position: relative;
    padding-bottom: 0.88rem;
    background:
      // url(~@/assets/images/bg-home.0101.jpg) center 0 / 7.5rem auto no-repeat,
      url(~@/assets/images/@2x/bg-home.0102.jpg) center 9.68rem / 7.5rem auto no-repeat,
      url(~@/assets/images/@2x/bg-home.0103.jpg) center 22.22rem / 7.5rem auto no-repeat,
      url(~@/assets/images/@2x/bg-home.0104.jpg) center 36.1rem / 7.5rem auto no-repeat,
      url(~@/assets/images/@2x/bg-home.0105.jpg) center 45.25rem / 7.5rem auto no-repeat,
      url(~@/assets/images/@2x/bg-home.0106.jpg) center 55.78rem / 7.5rem auto no-repeat,
      url(~@/assets/images/@2x/bg-home.0107.jpg) center 67.65rem / 7.5rem auto no-repeat,
      url(~@/assets/images/@2x/bg-home.0108.jpg) center 79.49rem / 7.5rem auto no-repeat,
      url(~@/assets/images/@2x/bg-home.0109.jpg) center 92.93rem / 7.5rem auto no-repeat;
  }

  &-top {
    position: relative;
    padding-top: 2.7rem;

    &-icon {
      width: 7.5rem;
      height: auto;

      &.align-horz {
        top: 0;
        z-index: -1;
      }
    }
    &-logo {
      display: block;
      width: 100%;
      height: auto;
    }
  }

  &-nav {
    position: absolute;
    top: 0.5rem;
    right: 0;

    &-item {
      clear: right;
      float: right;
      border-radius: 0.28rem 0 0 0.28rem;
      @include background-gradient(0, #42175e, #2d2364);

      & + & {
        margin-top: 0.2173913rem;
      }
    }
    &-mark {
      display: block;
      height: 0.56rem;
      padding: {
        left: 0.3rem;
        right: 0.2rem;
      }
      color: $tabon-color;
      font-size: 0.3rem;
      line-height: 0.56rem;
      @include text-gradient(180deg, #ffdf74, #e0a231);
    }
  }

  &-stats {
    height: 5.98rem;

    &-icon {
      width: 5.471rem;
      height: auto;

      &.align-horz {
        top: 0.06rem;
      }
    }
    .iconfont,
    &-text {
      position: absolute;
      top: 1.65rem;
      left: 0;
      right: 0;
      color: #e0a231;
      font-size: 0.76rem;
      font-weight: 500;
      text-align: center;
    }
    &-text {
      background-image: -webkit-linear-gradient(-90deg, #ffdf74, #e0a231);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;

      .icon {
        font-size: 0.3334rem;
        font-style: normal;
      }
    }
  }

  &-menu {
    position: relative;
    margin: {
      top: -0.9rem;
      left: 0.5rem;
    }

    &-node {
      float: left;
      box-sizing: border-box;
      width: 1.88rem;
      height: 0.71rem;
      padding: {
        top: 0.2rem;
      }
      margin: {
        top: 0.32rem;
        left: 0.4rem;
      }
      font-size: 0.3rem;
      line-height: 1;
      text-align: center;
      background: url(~@/assets/images/mark.0103.png) left center / auto 100% no-repeat;

      &:nth-child(3n+1) {
        margin-left: 0;
      }
    }
  }
}
</style>
