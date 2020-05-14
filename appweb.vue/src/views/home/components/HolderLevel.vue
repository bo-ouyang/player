<template>
  <div class="container">
    <div class="pnl-module">
      <div class="pnl-module-header" />
      <div class="pnl-module-content" style="text-align: center">
        <span class="icon" style="color: #0ceaf3;font-size: 0.28rem">({{ $t('text.i01_008') }})</span>
        <ul class="tab-menu">
          <li
            class="tab-menu-item"
            @click="showDialog = 1">
            {{ $t('title.i01_011') }}
          </li>
          <li
            class="tab-menu-item"
            @click="showDialog = 2">
            {{ $t('title.i01_008') }}
          </li>
        </ul>
        <state-number :title="$t('label.i01_005')" :number="totalAmount" />
        <ul class="medal clearfix">
          <li
            v-for="grade in 5"
            :key="grade"
            class="medal-item"
            width="335"
            height="250"
            @click="handleState(grade)">
            <img :src="require(`@/assets/images/icon.0101-${grade}.png`)" />
            {{ grade | holderGradeName }} ({{ gradeNumber[grade - 1] || 0 }})
          </li>
        </ul>
        <p class="align-c">{{ $t('text.i01_002', { role: $options.filters.holderGradeName(userGrade) }) }}</p>
      </div>
      <div class="pnl-module-footer" />
    </div>

    <holder-record v-if="showDialog === 1" @close="showDialog = 0" />
    <holder-reward v-if="showDialog === 2" @close="showDialog = 0" />
    <holder-state v-if="showDialog === 3" :type="showType" @close="showDialog = 0" />
  </div>
</template>

<script>
import HolderRecord from './HolderRecord'
import HolderReward from './HolderReward'
import HolderState from './HolderState'

export default {
  name: 'HolderLevel',
  props: {
    totalAmount: {
      type: [Number, String],
      default: 0
    },
    gradeNumber: {
      type: Array,
      default: () => []
    },
    userGrade: {
      type: Number,
      default: 0
    }
  },
  components: {
    HolderRecord,
    HolderReward,
    HolderState
  },

  data () {
    return {
      isLoading: true,
      showDialog: 0,
      showType: 0
    }
  },

  methods: {
    handleState (grade) {
      this.showType = grade
      this.showDialog = 3
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

.medal {
  padding: {
    top: 0.3rem;
    bottom: 0.6rem;
  }

  &-item {
    float: left;
    width: 2.02rem;
    padding-top: 0.3rem;
    margin-left: 0.12rem;
    text-align: center;

    &:first-child {
      margin-left: 1.18rem;
    }
    &:nth-child(2) {
      margin-left: 0.98rem;
    }
    &:nth-child(3) {
      clear: left;
      margin-left: 0.32rem;
    }
    img {
      display: block;
      width: 100%;
      height: auto;
    }
  }
}
</style>
