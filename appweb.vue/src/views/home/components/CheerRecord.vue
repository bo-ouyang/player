<template>
  <modal-popup custom @close="$emit('close', $event)">
    <h5 class="modal-title flex-fixed">{{ $t('title.i01_014') }}</h5>
    <div class="btn-group flex-fixed">
      <button
        v-for="menu in 4"
        :key="menu"
        :class="{ active: menu === menuType }"
        class="btn-group-item"
        type="button"
        @click="handleSwitch(menu)">
        {{ menu | cheerTypeName }}
      </button>
    </div>
    <div class="modal-body m--table flex-grown scrollbox" @scroll="handleScroll($event.target, getOrderList)">
      <table class="tbl-data">
        <thead>
          <tr>
            <td>{{ $t('label.i01_020') }}</td>
            <td>{{ $t('label.i01_030_2') }}</td>
            <td>{{ $t('label.i01_029') }}</td>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(order, index) in orderList" :key="index" class="bod-tblt-1px">
            <td><span class="timer">{{ order.create_time | dateReadable }}</span></td>
            <td class="fc-title"><span class="address">{{ order.origin_address }}</span></td>
            <td class="fc-tabon">{{ +order.amount }}ETH</td>
          </tr>
        </tbody>
        <tfoot v-if="isLoading || !orderList || !orderList.length">
          <tr>
            <td class="fs-small fc-tipe align-c" colspan="3">
              <span v-if="isLoading">
                <i class="iconfont icon-loader" />
                {{ $t('tipe.i01_004') }}
              </span>
              <span v-else>{{ $t('tipe.i01_005') }}</span>
            </td>
          </tr>
        </tfoot>
      </table>
    </div>
  </modal-popup>
</template>

<script>
import ListLoader from '@/mixins/ListLoader'
import {
  getCheerRecord
} from '@/apis/common'

export default {
  name: 'CheerRecord',
  mixins: [
    ListLoader
  ],

  data () {
    return {
      isLoading: false,
      orderList: null,
      menuType: 1,
      page: {
        index: 1,
        size: process.env.PAGE_SIZE,
        total: 0
      }
    }
  },

  computed: {
    userInfo () {
      return this.$store.state.user
    }
  },

  created () {
    this.getOrderList()
  },

  methods: {
    getOrderList () {
      this.isLoading = true
      this.$mResponseHelper(
        getCheerRecord({
          type: this.menuType,
          page: this.page.index,
          list_rows: this.page.size
        }),
        data => {
          if (this.page.index === 1) {
            this.orderList = data.list
            // this.page.total = data.total
          } else {
            this.orderList.push(...data.list)
          }
          if (data.total) {
            this.loadDone = true
            this.page.index += 1
          } else {
            this.isLoading = false
          }
        }
      ).finally(() => {
      })
    },

    handleSwitch (type) {
      this.menuType = type
      this.page.index = 1
      // this.page.total = 0
      this.orderList = null
      this.loadDone = false
      this.lastScrollTop = 0
      this.getOrderList()
    }
  }
}
</script>
