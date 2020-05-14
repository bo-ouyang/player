<template>
  <div class="app-container">
    <el-card>
      <el-row>
        <el-col :span="2" class="title">{{ cheerType | cheerTypeName }}</el-col>
        <el-col :span="5">
          <span class="label">当前金额：</span>
          {{ statsInfo.amount }}/{{ statsInfo.quota }}
        </el-col>
        <el-col :span="4">
          <span class="label">地址数量：</span>
          {{ statsInfo.number }}
        </el-col>
        <el-col :span="7">
          <span class="label">本次开始时间：</span>
          {{ statsInfo.start_time | dateReadable }}
        </el-col>
        <el-col :span="6">
          <el-button type="success" plain @click="showMemberDialog = true">添加用户</el-button>
        </el-col>
      </el-row>
    </el-card>
    <el-form
      ref="queryForm"
      :model="queryForm"
      :rules="queryRules"
      class="query-form"
      label-width="120px"
      @submit.native.prevent="getOrderList">
      <el-row>
        <el-col :span="12">
          <el-form-item prop="address" label="参与地址：">
            <el-input v-model.trim="queryForm.address" type="text" />
          </el-form-item>
        </el-col>
        <el-col :span="12">
          <el-form-item prop="type" label="显示历史：">
            <el-radio-group v-model="queryForm.status">
              <el-radio :label="2">是</el-radio>
              <el-radio :label="1">否</el-radio>
            </el-radio-group>
          </el-form-item>
        </el-col>
      </el-row>
      <el-form-item>
        <el-button :loading="isLoading" type="primary" native-type="submit">查询</el-button>
      </el-form-item>
    </el-form>
    <el-table v-loading="isLoading" :data="orderList">
      <el-table-column label="参与地址" prop="origin_address" min-width="250px" />
      <el-table-column label="支付金额/ETH" min-width="110px">
        <template slot-scope="scope">{{ scope.row.amount | coinReadable }}</template>
      </el-table-column>
      <el-table-column label="支付时间" width="140px">
        <template slot-scope="scope">{{ scope.row.create_time | dateReadable }}</template>
      </el-table-column>
    </el-table>

    <el-pagination
      :current-page.sync="page.index"
      :page-size="page.size"
      :total="page.total"
      layout="total,prev,pager,next"
      @current-change="getOrderList"
    />
    <cheer-config
      v-if="showConfigDialog"
      :cheer-type="cheerType"
      :egg-id="statsInfo.egg_id"
      @close="handleUpdate"
    />
    <member-config v-if="showMemberDialog" @close="showMemberDialog = false" />
  </div>
</template>

<script>
import CheerConfig from './components/CheerConfig'
import MemberConfig from './components/MemberConfig'
import {
  getCheerOrders
} from '@/apis/order'

export default {
  name: 'CheerOrders',
  components: {
    CheerConfig,
    MemberConfig
  },
  props: {
    cheerType: {
      type: Number,
      required: true
    }
  },

  data() {
    return {
      isLoading: true,
      showMemberDialog: false,
      showConfigDialog: false,
      statsInfo: {},
      orderList: null,
      queryForm: {
        type: this.cheerType,
        address: '',
        status: 1
      },
      queryRules: {},
      // 列表分页
      page: {
        index: 1,
        size: process.env.PAGE_SIZE,
        total: 0
      }
    }
  },

  created() {
    this.getOrderList()
  },

  methods: {
    getOrderList() {
      this.isLoading = true
      this.$mResponseHelper(
        getCheerOrders({
          ...this.queryForm,
          page: this.page.index,
          page_size: this.page.size
        }),
        data => {
          this.statsInfo = data
          data.amount = +data.amount
          data.quota = +data.quota

          this.orderList = data.list
          if (this.page.index === 1) {
            this.page.total = data.total
          }
        }
      ).finally(() => {
        this.isLoading = false
      })
    },

    handleUpdate(bUpdate) {
      this.showConfigDialog = false
      if (bUpdate) this.getOrderList()
    }
  }
}
</script>

<style lang="scss" scoped>
.el-card {
  width: 1000px;
  margin-bottom: 25px;
  line-height: 40px;

  .title {
    font-weight: bold;
  }
  .label {
    color: #99a9bf;
    font-size: 14px;
  }
}
</style>
