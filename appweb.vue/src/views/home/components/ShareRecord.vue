<template>
  <modal-popup custom @close="$emit('close', $event)">
    <h5 class="modal-title flex-fixed">{{ $t('title.i01_009') }}</h5>
    <div class="modal-body m--table flex-grown scrollbox"><!-- @scroll="handleScroll($event.target, getUserList)" -->
      <table class="tbl-data">
        <thead>
          <tr>
            <td>{{ $t('label.i01_020') }}</td>
            <td>{{ $t('label.i01_023') }}</td>
          </tr>
        </thead>
        <tbody>
          <template v-for="(user, index) in userList">
            <tr
              v-if="user.parent_id === userId || expandUserIds.includes(user.parent_id)"
              :key="index"
              :class="{
                super: user.child && user.child.length,
                child: user.parent_id !== userId,
                active: expandStatusMap[user.parent_id] === user.user_id
              }"
              class="bod-tblt-1px"
              @click="handleExpand(user)">
              <td>{{ user.create_time | dateReadable }}</td>
              <td>
                <span class="bedge fs-mins">{{ user.level }}</span>
                <span class="fc-title">{{ user.invite_code }}</span>
              </td>
            </tr>
          </template>
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
  getUserTeams
} from '@/apis/common'

export default {
  name: 'ShareRecord',
  mixins: [
    ListLoader
  ],

  data () {
    return {
      isLoading: false,
      userList: null,
      // page: {
      //   index: 1,
      //   size: process.env.PAGE_SIZE,
      //   total: 0
      // },
      userId: 0,
      expandUserIds: [],
      expandStatusMap: {}
    }
  },

  computed: {
    userInfo () {
      return this.$store.state.user
    }
  },

  created () {
    if (this.userInfo.inviteCode) {
      this.getUserList()
    }
  },

  methods: {
    getUserList () {
      this.isLoading = true
      this.$mResponseHelper(
        getUserTeams({
          invite_code: this.userInfo.inviteCode
          // page: this.page.index,
          // list_rows: this.page.size
        }),
        data => {
          this.userList = this.flattenUserTree(data.list)
          // if (this.page.index === 1) {
          //   this.userList = this.flattenUserTree(data.list)
          //   // this.page.total = data.total
          // } else {
          //   this.userList.push(...this.flattenUserTree(data.list))
          // }
          // if (this.page.index * this.page.size >= data.total) {
          //   this.loadDone = true
          // } else {
          //   this.page.index += 1
          // }
          if (this.userList.length) {
            this.userId = this.userList[0].parent_id
          }
        }
      ).finally(() => {
        this.isLoading = false
      })
    },
    flattenUserTree (tree = [], level = 1) {
      const list = []
      tree.forEach(user => {
        user.level = level
        list.push(user)
        if (user.child && user.child.length) {
          list.push(...this.flattenUserTree(user.child, level + 1))
        }
      })

      return list
    },
    handleExpand (user) {
      const ids = this.expandUserIds
      ids.splice(user.level - 1, ids.length - user.level + 1)
      if (this.expandStatusMap[user.parent_id] === user.user_id) {
        this.$set(this.expandStatusMap, user.parent_id, -1)
      } else {
        ids.push(user.user_id)
        this.$set(this.expandStatusMap, user.parent_id, user.user_id)
      }
    }
  }
}
</script>
