<template>
  <div class="app-container">
    <el-form
      ref="queryForm"
      :model="queryForm"
      :rules="queryRules"
      class="query-form"
      label-width="120px"
      @submit.native.prevent="getUserList">
      <el-row>
        <el-col :span="10">
          <el-form-item prop="address" label="支付地址：">
            <el-input v-model.trim="queryForm.address" type="text" />
          </el-form-item>
        </el-col>
        <el-col :span="6">
          <el-form-item prop="invite_code" label="邀请码：" label-width="90px">
            <el-input v-model.trim="queryForm.invite_code" type="text" />
          </el-form-item>
        </el-col>
        <el-col :span="12">
          <el-form-item prop="type" label="显示：" label-width="80px">
            <el-radio-group v-model="queryForm.is_super">
              <el-radio label="">全部</el-radio>
              <el-radio :label="1">普通会员</el-radio>
              <el-radio :label="2">超级节点</el-radio>
              <el-radio :label="3">真实</el-radio>
            </el-radio-group>
          </el-form-item>
        </el-col>
      </el-row>
      <el-form-item>
        <el-button :loading="isLoading" type="primary" native-type="submit">查询</el-button>
      </el-form-item>
    </el-form>
    <el-table v-loading="isLoading" :data="userList">
      <el-table-column type="expand">
        <template slot-scope="props">
          <el-form label-width="80px">
            <el-row>
              <el-col :span="6">
                <el-form-item label="沙漠入金">{{ props.row.desert_amount | coinReadable }}ETH</el-form-item>
                <el-form-item label="沙漠收益">{{ props.row.desert_profit | coinReadable }}ETH</el-form-item>
                <el-form-item label="彩蛋支付">{{ props.row.egg_amount | coinReadable }}ETH</el-form-item>
                <el-form-item label="彩蛋收益">{{ props.row.egg_profit | coinReadable }}ETH</el-form-item>
              </el-col>
              <el-col :span="6">
                <el-form-item label="绿洲入金">{{ props.row.oasis_amount | coinReadable }}ETH</el-form-item>
                <el-form-item label="绿洲收益">{{ props.row.oasis_profit | coinReadable }}ETH</el-form-item>
                <el-form-item label="分享人数">{{ +props.row.share_number }}人</el-form-item>
                <el-form-item label="分享收益">{{ props.row.invite_profit | coinReadable }}ETH</el-form-item>
              </el-col>
              <el-col :span="6">
                <el-form-item label="玩家等级">{{ props.row.grade | holderGradeName }}</el-form-item>
                <el-form-item label="玩家收益">{{ props.row.team_profit | coinReadable }}ETH</el-form-item>
                <el-form-item label="ACGG数量">{{ +props.row.token_amount }}</el-form-item>
                <el-form-item label="ACGG支付">{{ props.row.token_cost | coinReadable }}ETH</el-form-item>
              </el-col>
              <el-col :span="6">
                <el-form-item label="全球节点">
                  {{ props.row.is_super === 2 ? '是' : '否' }}
                  <template v-if="adminInfo.is_super === 1">
                    <el-button
                      v-if="props.row.is_super === 2"
                      :loading="props.row.mLoading"
                      type="danger"
                      size="mini"
                      plain
                      @click="handleUranus(props.row, 1)">
                      取消
                    </el-button>
                    <el-button
                      v-else
                      :loading="props.row.mLoading"
                      type="primary"
                      size="mini"
                      plain
                      @click="handleUranus(props.row, 2)">
                      升级
                    </el-button>
                  </template>
                </el-form-item>
                <el-form-item label="节点收益">{{ props.row.super_profit | coinReadable }}ETH</el-form-item>
              </el-col>
            </el-row>
          </el-form>
        </template>
      </el-table-column>
      <el-table-column label="支付地址" prop="origin_address" min-width="250px" />
      <el-table-column label="邀请码" prop="invite_code" width="95px" />
      <el-table-column label="上级邀请码" prop="parent_invite_code" width="95px">
        <template slot-scope="scope">
          <span>{{ scope.row.parent_invite_code }}</span>
          <!-- <el-button type="text" icon="el-icon-edit" @click="userData = scope.row" /> -->
        </template>
      </el-table-column>
      <el-table-column label="入金总额/ETH" min-width="110px" align="right">
        <template slot-scope="scope">{{ scope.row.total_invest | coinReadable }}</template>
      </el-table-column>
      <el-table-column label="收入总额/ETH" min-width="110px" align="right">
        <template slot-scope="scope">{{ scope.row.total_reward | coinReadable }}</template>
      </el-table-column>
      <el-table-column label="总业绩/ETH" min-width="110px" align="right">
        <template slot-scope="scope">{{ scope.row.total_performance | coinReadable }}</template>
      </el-table-column>
      <el-table-column label="注册时间" width="140px">
        <template slot-scope="scope">{{ scope.row.create_time | dateReadable }}</template>
      </el-table-column>
    </el-table>

    <el-pagination
      :current-page.sync="page.index"
      :page-size="page.size"
      :total="page.total"
      layout="total,prev,pager,next"
      @current-change="getUserList"
    />
    <!-- 变更上级 -->
    <member-super v-if="userData" :user-info="userData" @close="handleUpdate" />
  </div>
</template>

<script>
import MemberSuper from './components/MemberSuper'
import {
  getMemberList,
  setMemberNode
} from '@/apis/member'

export default {
  name: 'MemberList',
  components: {
    MemberSuper
  },

  data() {
    return {
      isLoading: false,
      userData: null,
      userList: null,
      queryForm: {
        address: '',
        invite_code: '',
        is_super: ''
      },
      queryRules: {},
      page: {
        index: 1,
        size: process.env.PAGE_SIZE,
        total: 0
      }
    }
  },

  computed: {
    adminInfo() {
      return this.$store.state.user.admin
    }
  },

  created() {
    this.getUserList()
  },

  methods: {
    getUserList() {
      this.isLoading = true
      this.$mResponseHelper(
        getMemberList({
          ...this.queryForm,
          page: this.page.index,
          page_size: this.page.size
        }),
        data => {
          this.userList = data.list
          if (this.page.index === 1) {
            this.page.total = data.total
          }
        }
      ).finally(() => {
        this.isLoading = false
      })
    },

    handleUpdate(bUpdate) {
      this.userData = null
      if (bUpdate) this.getUserList()
    },
    handleUranus(user, status) {
      this.$confirm(
        `确定${status === 2 ? '升级' : '取消'}【${user.invite_code}】超级节点吗？`
      ).then(() => {
        this.$set(user, 'mLoading', true)
        this.$mResponseHelper(
          setMemberNode({
            user_id: user.user_id,
            is_super: status
          }),
          () => {
            this.$set(user, 'is_super', status)
            this.$message.success('操作成功')
          }
        ).finally(() => {
          this.$set(user, 'mLoading', false)
        })
      }).catch(() => {})
    }
  }
}
</script>
