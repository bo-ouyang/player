<template>
  <modal-popup custom @close="$emit('close', $event)">
    <h5 class="modal-title flex-fixed">{{ $t('title.i01_012') }}</h5>
    <div class="modal-body m--table flex-grown scrollbox" @scroll="handleScroll($event.target, getOrderList)">
      <table class="tbl-data">
        <thead>
          <tr>
            <td>{{ $t('label.i01_020') }}</td>
            <td>{{ $t('label.i01_027') }}</td>
            <td>{{ $t('label.i01_019') }}</td>
            <td>{{ $t('label.i01_028') }}</td>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(order, index) in orderList" :key="index" class="bod-tblt-1px">
            <td><span class="timer">{{ order.create_time | dateReadable }}</span></td>
            <td class="fc-title">{{ order.type_name }}</td>
            <td class="fc-tabon">{{ +order.price }}ETH</td>
            <td class="fc-tabon">{{ +order.amount }}</td>
          </tr>
        </tbody>
        <tfoot v-if="isLoading || !orderList || !orderList.length">
          <tr>
            <td class="fs-small fc-tipe align-c" colspan="4">
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
  getTokenRecord
} from '@/apis/common'

export default {
  name: 'ChaoneRecord',
  mixins: [
    ListLoader
  ],

  data () {
    return {
      isLoading: false,
      orderList: null,
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
    if (this.userInfo.inviteCode) {
      this.getOrderList()
    }
  },

  methods: {
    getOrderList () {
      this.isLoading = true
      this.$mResponseHelper(
        getTokenRecord({
          invite_code: this.userInfo.inviteCode,
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
          if (this.page.index * this.page.size >= data.total) {
            this.loadDone = true
          } else {
            this.page.index += 1
          }
        }
      ).finally(() => {
        this.isLoading = false
      })
    }
  }
}
</script>
