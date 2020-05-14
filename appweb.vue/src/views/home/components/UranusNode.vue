<template>
  <div class="container">
    <div class="pnl-module">
      <div class="pnl-module-header" />
      <div class="pnl-module-content">
        <ul class="tab-menu">
          <li
            class="tab-menu-item"
            @click="showDialog = 2">
            {{ $t('title.i01_008') }}
          </li>
        </ul>
        <p class="state">
          <img class="state-mark" src="~@/assets/images/icon-earth.0101-0.png" width="904" height="310" />
          <img class="state-icon" src="~@/assets/images/icon-earth.0101-1.png" width="492" height="505" />
          {{ $t('text.i01_003', { num: 100 }) }}
        </p>
        <p class="footer">
          <button
            :disabled="uranusRole === 2"
            class="btn-page"
            @click="checkSuperNum()" >
            {{ $t(uranusRole === 2 ? 'text.i01_003_2' : 'action.i01_001_3') }}
          </button>
        </p>
      </div>
      <div class="pnl-module-footer" />
    </div>

    <partake-activity
      v-if="showDialog === 1"
      :form-data="formData"
      @close="showDialog = 0"
    />
    <uranus-record v-if="showDialog === 2" @close="showDialog = 0" />
  </div>
</template>

<script>
import PartakeActivity from './PartakeActivity'
import UranusRecord from './UranusRecord'

export default {
  name: 'UranusNode',
  props: {
    uranusRole: Number,
    leftSuperNums: Number
  },
  components: {
    PartakeActivity,
    UranusRecord
  },

  data () {
    return {
      showDialog: 0,
      formData: {
        number: 100,
        type: 1,
        egg_type: ''
      }
    }
  },
  methods: {
    checkSuperNum () {
      console.log(this.leftSuperNums)
      if (this.leftSuperNums > 0) {
        this.showDialog = 1
      } else {
        this.$mToast(this.$t('info.i01_004'))
      }
    }
  }
}
</script>

<style lang="scss" scoped>
@import '~@/styles/defines';
@import '~@/styles/animate';

.tab-menu {
  padding: {
    top: 0.2rem;
    // bottom: 0.3rem;
  }
}

.state {
  position: relative;
  margin: {
    top: 0.48rem;
    bottom: 0.48rem;
  }
  text-align: center;

  &::before {
    content: "";
    display: block;
    width: 5.45rem;
    height: 3.7rem;
    margin: 0.28rem auto;
    // background: url(~@/assets/images/icon-earth.0101.png) left center / 100% auto no-repeat;
  }
  &-mark {
    position: absolute;
    top: 1.82rem;
    left: 0.74rem;
    width: 5.45rem;
    height: auto;
    animation: animate-shadow 2.6s infinite alternate;
  }
  &-icon {
    position: absolute;
    top: 0;
    left: 1.96rem;
    width: 2.971rem;
    height: auto;
  }
}

.footer {
  padding: 0 0.46rem 0.12rem;
}
</style>
