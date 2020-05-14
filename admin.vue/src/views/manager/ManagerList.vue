<template>
  <div class="app-container">
    <el-form inline @submit.native.prevent>
      <!-- <el-row>
        <el-col :span="12">
          <el-form-item prop="address" label="支付地址：">
            <el-input v-model.trim="queryForm.address" type="text" />
          </el-form-item>
        </el-col>
        <el-col :span="12">
          <el-form-item prop="invite_code" label="邀请码：">
            <el-input v-model.trim="queryForm.invite_code" type="text" />
          </el-form-item>
        </el-col>
      </el-row> -->
      <el-form-item>
        <!-- <el-button :loading="isLoading" type="primary" native-type="submit">查询</el-button> -->
        <el-button type="primary" @click="userData = {}">添加账户</el-button>
      </el-form-item>
    </el-form>
    <el-table v-loading="isLoading" :data="userList">
      <el-table-column label="用户名" prop="username" />
      <el-table-column label="邀请码" prop="invite_code" />
      <el-table-column label="添加时间" width="140px">
        <template slot-scope="scope">{{ scope.row.create_time | dateReadable }}</template>
      </el-table-column>
      <el-table-column label="操作" width="90px">
        <template slot-scope="scope">
          <el-button type="primary" size="mini" plain @click="userData = scope.row">编辑</el-button>
        </template>
      </el-table-column>
    </el-table>

    <el-pagination
      :current-page.sync="page.index"
      :page-size="page.size"
      :total="page.total"
      layout="total,prev,pager,next"
      @current-change="getUserList"
    />
    <!-- 账户编辑 -->
    <manager-config v-if="userData" :user-info="userData" @close="handleUpdate" />
  </div>
</template>

<script>
import ManagerConfig from './components/ManagerConfig'
import {
  getManagerList
} from '@/apis/manager'

export default {
  name: 'ManagerList',
  components: {
    ManagerConfig
  },

  data() {
    return {
      isLoading: false,
      userData: null,
      userList: null,
      page: {
        index: 1,
        size: process.env.PAGE_SIZE,
        total: 0
      }
    }
  },

  created() {
    this.getUserList()
  },

  methods: {
    getUserList() {
      this.isLoading = true
      this.$mResponseHelper(
        getManagerList({
          // ...this.queryForm,
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

    handleUpdate(info) {
      this.userData = null
      if (info) {
        if (!info.admin_user_id) {
          this.page.index = 1
        }
        this.getUserList()
      }
    }
  }
}
</script>
