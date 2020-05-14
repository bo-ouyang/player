<template>
  <div class="app-container">
    <el-form
      ref="queryForm"
      :model="queryForm"
      :rules="queryRules"
      class="query-form"
      label-width="120px"
      @submit.native.prevent="getOrderList">
      <el-row>
        <el-col :span="12">
          <el-form-item prop="address" label="收益地址：">
            <el-input v-model.trim="queryForm.address" type="text" />
          </el-form-item>
        </el-col>
        <el-col :span="12">
          <el-form-item prop="pay_address" label="支付地址：">
            <el-input v-model.trim="queryForm.pay_address" type="text" />
          </el-form-item>
        </el-col>
      </el-row>
      <el-form-item>
        <el-button :loading="isLoading" type="primary" native-type="submit">查询</el-button>
      </el-form-item>
    </el-form>
    <el-table v-loading="isLoading" :data="orderList">
      <el-table-column label="收益地址" prop="address" min-width="250px" />
      <el-table-column label="支付地址" prop="pay_address" min-width="250px" />
      <el-table-column label="支付本金/ETH" min-width="110px">
        <template slot-scope="scope">{{ scope.row.recharge_amount | coinReadable }}</template>
      </el-table-column>
      <el-table-column label="收益/ETH" min-width="110px">
        <template slot-scope="scope">{{ scope.row.amount | coinReadable }}</template>
      </el-table-column>
      <el-table-column label="时间" width="140px">
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
  </div>
</template>

<script>
import {
  getRewardOrders
} from '@/apis/order'

export default {
  name: 'StaticIncome',

  data() {
    return {
      isLoading: true,
      orderList: null,
      queryForm: {
        address: '',
        pay_address: ''
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
        getRewardOrders({
          ...this.queryForm,
          page: this.page.index,
          page_size: this.page.size
        }),
        data => {
          this.orderList = data.list
          if (this.page.index === 1) {
            this.page.total = data.total
          }
        }
      ).finally(() => {
        this.isLoading = false
      })
    }
  }
}
</script>
