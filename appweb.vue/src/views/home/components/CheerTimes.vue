<template>
  <div class="container">
    <div class="pnl-module">
      <div class="pnl-module-header" />
      <div class="pnl-module-content" style="text-align: center">
        <span class="icon" style="color: #0ceaf3;font-size: 0.28rem">({{ $t('text.i01_007') }})</span>
        <ul class="tab-menu">
          <li
            class="tab-menu-item"
            @click="showDialog = 3">
            {{ $t('title.i01_013') }}
          </li>
          <li
            class="tab-menu-item"
            @click="showDialog = 2">
            {{ $t('title.i01_014') }}
          </li>
        </ul>
        <ul class="medal clearfix">
          <li
            v-for="(item, index) in awardList"
            :key="index"
            class="medal-item"
            @click="handleConfirm(item)">
            <img :src="require(`@/assets/images/icon.0102-${item.type}.png`)" width="119" height="85" />
            {{ item.type | cheerTypeName }}
            <span class="icon">{{ item.award }}<i class="fs-mins">ETH</i>/{{ item.total }}<i class="fs-mins">ETH</i></span>
            <span class="mark">({{ $t('name.i01_004_1',{ num : item.num }) }})</span>
          </li>
        </ul>
      </div>
      <div class="pnl-module-footer" />
    </div>

    <partake-welfare
      v-if="showDialog === 1"
      :welfare="welfareData"
      @close="showDialog = 0"
    />
    <cheer-record v-if="showDialog === 2" @close="showDialog = 0" />
    <cheer-reward v-if="showDialog === 3" @close="showDialog = 0" />
  </div>
</template>

<script>
import PartakeWelfare from './PartakeWelfare'
import CheerRecord from './CheerRecord'
import CheerReward from './CheerReward'
import {
  getCheerTimes
} from '@/apis/common'

export default {
  name: 'CheerTimes',
  components: {
    PartakeWelfare,
    CheerRecord,
    CheerReward
  },

  data () {
    return {
      isLoading: true,
      showDialog: 0,
      welfareData: null,
      awardList: [
        {
          type: 1,
          award: 0,
          total: 0,
          num: 0
        },
        {
          type: 2,
          award: 0,
          total: 0,
          num: 0
        },
        {
          type: 3,
          award: 0,
          total: 0,
          num: 0
        },
        {
          type: 4,
          award: 0,
          total: 0,
          num: 0
        }
      ]
    }
  },

  created () {
    const today = new Date().format('YYYYMMDD')
    if (this.$store.state.userHabit.today !== today) {
      this.showDialog = 3
      this.$store.commit('saveHabit', { today })
    }

    this.$mResponseHelper(
      getCheerTimes(),
      data => {
        this.awardList.forEach((item, index) => {
          const info = data[index + 1]
          item.total = +info.quota
          item.award = +info.amount
          item.num   = +info.num
        })
      }
    ).finally(() => {
      this.isLoading = true
    })
  },

  methods: {
    handleConfirm (info) {
      this.welfareData = info
      this.showDialog = 1
    }
  }
}
</script>

<style lang="scss" scoped>
@import '~@/styles/defines';

.tab-menu {
  padding: {
    top: 0.2rem;
    bottom: 0.4rem;
  }
}

.medal {
  padding: {
    bottom: 0.32rem;
  }

  &-item {
    float: left;
    width: 2.1rem;
    padding-top: 0.25rem;
    // margin: {
    //   left: 0.3rem;
    // }
    text-align: center;
    background: url(~@/assets/images/icon.0102-0.png) center 0.45rem / 1.28rem auto no-repeat;
    overflow: hidden;

    &:first-child {
      margin: {
        top: 0.8rem;
        left: 0.4rem;
      }
    }
    &:nth-child(3) {
      margin: {
        top: 0.8rem;
      }
    }
    &:nth-child(1),
    &:nth-child(2),
    &:nth-child(3) {
      img {
        animation: anime-rotate 3.8s linear infinite;
        transform-origin: center;
        transform-style: preserve-3d;
        backface-visibility: visible;
      }
    }
    &:nth-child(4) {
      clear: left;
      float: none;
      width: 2.71rem;
      padding: {
        top: 0.65rem;
      }
      @include margin-center;
      background: url(~@/assets/images/icon.0102-4-0.png) center 1.9rem / 100% auto no-repeat;

      img {
        width: 1.413rem;
        margin-bottom: 0.35rem;
        animation: anime-swing 3s 0s infinite;
        transform-origin: bottom;
      }
    }
    .icon {
      display: block;
      margin-top: 0.06rem;
      color: $tabon-color;
      font-size: 0.26rem;
    }
    .mark {
      color: $text-color;
      font-size: $small-size;
    }
    img {
      display: block;
      width: 0.718599rem;
      height: auto;
      margin: 0 auto 0.6rem;
    }
  }
}

@keyframes anime-rotate {
  0% {
    transform: rotate3d(0, 1, 0, 0);
  }
  100% {
    transform: rotate3d(0, 1, 0, 1turn);
  }
}

@keyframes anime-swing {
  0%, 65%{
    transform: rotate3d(0, 0, 1, 0deg);
  }
  70% {
    transform: rotate3d(0, 0, 1, 6deg);
  }
  75% {
    transform: rotate3d(0, 0, 1, -6deg);
  }
  80% {
    transform: rotate3d(0, 0, 1, 6deg);
  }
  85% {
    transform: rotate3d(0, 0, 1, -6deg);
  }
  90% {
    transform: rotate3d(0, 0, 1, 6deg);
  }
  95% {
    transform: rotate3d(0, 0, 1, -6deg);
  }
  100% {
    transform: rotate3d(0, 0, 1, 0deg);
  }
}
</style>
