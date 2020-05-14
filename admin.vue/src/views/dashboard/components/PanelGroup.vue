<template>
  <el-row :gutter="40" class="panel-group">
    <el-col v-for="(cate, index) in cateList" :key="index" :xs="12" :sm="12" :lg="6" class="card-panel-col">
      <div class="card-panel">
        <div :class="'icon-' + index" class="card-panel-icon-wrapper">
          <svg-icon :icon-class="cate.icon" class-name="card-panel-icon" />
        </div>
        <div class="card-panel-description">
          <div class="card-panel-text">{{ cate.title }}</div>
          <span v-if="cate.icon.startsWith('coin-')" class="card-panel-num">{{ cate.number | coinReadable(8) }}</span>
          <count-to v-else :start-val="0" :end-val="cate.number" :duration="durationTime[index % 4]" class="card-panel-num"/>
        </div>
      </div>
    </el-col>
  </el-row>
</template>

<script>
import CountTo from 'vue-count-to'

export default {
  name: 'PanelGroup',
  components: {
    CountTo
  },
  props: {
    cateList: {
      type: Array,
      default: () => []
    }
  },
  data() {
    return {
      durationTime: [2600, 3000, 3200, 3600]
    }
  }
}
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
.panel-group {
  margin-top: 18px;
  .card-panel-col{
    margin-bottom: 32px;
  }
  .card-panel {
    height: 108px;
    // cursor: pointer;
    font-size: 12px;
    position: relative;
    overflow: hidden;
    color: #666;
    background: #fff;
    box-shadow: 4px 4px 40px rgba(0, 0, 0, .05);
    border-color: rgba(0, 0, 0, .05);
    &:hover {
      .card-panel-icon-wrapper {
        color: #fff;
      }
      .icon-0 {
         background: #40c9c6;
      }
      .icon-1 {
        background: #36a3f7;
      }
      .icon-2 {
        background: #f4516c;
      }
      .icon-3 {
        background: #34bfa3
      }
    }
    .icon-0 {
      color: #40c9c6;
    }
    .icon-1 {
      color: #36a3f7;
    }
    .icon-2 {
      color: #f4516c;
    }
    .icon-3 {
      color: #34bfa3
    }
    .card-panel-icon-wrapper {
      position: absolute;
      top: 14px;
      left: 14px;
      padding: 16px;
      transition: all 0.38s ease-out;
      border-radius: 6px;
    }
    .card-panel-icon {
      float: left;
      font-size: 48px;
    }
    .card-panel-description {
      font-weight: bold;
      text-align: right;
      margin: 26px;
      margin-left: 0px;
      .card-panel-text {
        line-height: 18px;
        color: rgba(0, 0, 0, 0.45);
        font-size: 16px;
        margin-bottom: 12px;
      }
      .card-panel-num {
        font-size: 20px;
      }
    }
  }
}
</style>
