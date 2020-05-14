<template>
  <modal-popup custom @close="$emit('close', $event)">
    <h5 class="modal-title flex-fixed">{{ $t('title.i01_008') }}</h5>
    <div class="modal-body m--table flex-grown scrollbox" @scroll="handleScroll($event.target, getOrderList)">
      <table class="tbl-data">
        <thead>
          <tr>
            <td>{{ $t('label.i01_020') }}</td>
            <td>{{ $t('label.i01_026') }}</td>
            <td>{{ $t('label.i01_022') }}</td>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(order, index) in orderList" :key="index" class="bod-tblt-1px">
            <td><span class="timer">{{ order.create_time | dateReadable }}</span></td>
            <td class="fc-title">{{ order.grade_name }}</td>
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
  getGradeReward
} from '@/apis/common'

export default {
  name: 'HolderReward',
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
        getGradeReward({
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
          if (data.total) {
            this.loadDone = true
            this.page.index += 1
          } else {
            this.isLoading = false
          }
        }
      ).finally(() => {

      })
    }
  }
}
</script>
