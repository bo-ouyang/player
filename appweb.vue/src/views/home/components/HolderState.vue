<template>
  <modal-popup custom @close="$emit('close', $event)">
    <h5 class="modal-title flex-fixed">{{ $t('text.i01_002_2', { role: $options.filters.holderGradeName(type) }) }}</h5>
    <div class="modal-body m--table flex-grown scrollbox" @scroll="handleScroll($event.target, getUserList)">
      <table class="tbl-data">
        <thead>
          <tr>
            <td>{{ $t('label.i01_004') }}</td>
            <td class="align-c">{{ $t('label.i01_030') }}</td>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(order, index) in userList" :key="index" class="bod-tblt-1px">
            <td>{{ order.invite_code }}</td>
            <td class="fc-title"><span class="address">{{ order.origin_address }}</span></td>
          </tr>
        </tbody>
        <tfoot v-if="isLoading || !userList || !userList.length">
          <tr>
            <td class="fs-small fc-tipe align-c" colspan="2">
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
  getGradeUser
} from '@/apis/common'

export default {
  name: 'HolderState',
  props: {
    type: Number
  },
  mixins: [
    ListLoader
  ],

  data () {
    return {
      isLoading: false,
      userList: null,
      page: {
        index: 1,
        size: process.env.PAGE_SIZE,
        total: 0
      }
    }
  },

  created () {
    this.getUserList()
  },

  methods: {
    getUserList () {
      this.isLoading = true
      this.$mResponseHelper(
        getGradeUser({
          grade: this.type,
          page: this.page.index,
          list_rows: this.page.size
        }),
        data => {
          if (this.page.index === 1) {
            this.userList = data.list
            // this.page.total = data.total
          } else {
            this.userList.push(...data.list)
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

<style lang="scss" scoped>
@import '~@/styles/defines';

.tbl-data {
  .address {
    width: auto;
    padding-left: 0.25rem;
  }
}
</style>
