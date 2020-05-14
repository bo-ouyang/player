<template>
  <modal-popup custom @close="$emit('close', $event)">
    <h5 class="modal-title flex-fixed">{{ $t('title.i01_011') }}</h5>
    <div class="modal-body m--table flex-grown scrollbox">
      <table class="tbl-data">
        <thead>
          <tr>
            <td>{{ $t('label.i01_020') }}</td>
            <td>{{ $t('label.i01_025') }}</td>
            <td>{{ $t('label.i01_026_2') }}</td>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(order, index) in orderList" :key="index" class="bod-tblt-1px">
            <td><span class="timer">{{ order.create_time | dateReadable }}</span></td>
            <td class="fc-tabon">{{ +order.amount }}ETH</td>
            <td class="fc-title">{{ order.grade_name }}</td>
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
import {
  getUserUpgrade
} from '@/apis/common'

export default {
  name: 'HolderRecord',

  data () {
    return {
      isLoading: false,
      orderList: null
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
        getUserUpgrade({
          invite_code: this.userInfo.inviteCode
        }),
        data => {
          this.orderList = data.list
        }
      ).finally(() => {
        this.isLoading = false
      })
    }
  }
}
</script>
