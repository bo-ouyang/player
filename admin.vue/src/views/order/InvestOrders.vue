<template>
  <div class="app-container">
    <el-form
      ref="queryForm"
      :model="queryForm"
      :rules="queryRules"
      class="query-form"
      label-width="90px"
      @submit.native.prevent="getOrderList">
      <el-row>
        <el-col :span="10" :xs="24">
          <el-form-item prop="address" label="地址：">
            <el-input v-model.trim="queryForm.address" type="text" />
          </el-form-item>
        </el-col>
        <el-col :span="7" :xs="24">
          <el-form-item prop="type" label="模式：">
            <el-radio-group v-model="queryForm.type">
              <el-radio label="">全部</el-radio>
              <el-radio :label="1">{{ 1 | investTypeName }}</el-radio>
              <el-radio :label="2">{{ 2 | investTypeName }}</el-radio>
            </el-radio-group>
          </el-form-item>
        </el-col>
        <el-col :span="7" :xs="24">
          <el-form-item prop="system_recharge" label="充值：">
            <el-radio-group v-model="queryForm.system_recharge">
              <el-radio label="">全部</el-radio>
              <el-radio :label="1">真实</el-radio>
              <el-radio :label="2">后台</el-radio>
            </el-radio-group>
          </el-form-item>
        </el-col>
      </el-row>
      <el-form-item>
        <el-button :loading="isLoading" type="primary" native-type="submit">查询</el-button>
      </el-form-item>
    </el-form>
    <el-table v-loading="isLoading" :data="orderList">
      <el-table-column label="Order" prop="order_id" width="60px" />
      <el-table-column label="Cycle" prop="cycle_id" width="60px" />
      <el-table-column label="地址" prop="origin_address" min-width="250px" />
      <el-table-column label="支付/ETH" min-width="110px">
        <template slot-scope="scope">{{ scope.row.amount | coinReadable }}</template>
      </el-table-column>
      <el-table-column label="模式">
        <template slot-scope="scope">{{ scope.row.type | investTypeName }}</template>
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
  </div>
</template>

<script>
import {
  getInvestOrders
} from '@/apis/order'

export default {
  name: 'InvestOrders',

  data() {
    return {
      isLoading: true,
      orderList: null,
      queryForm: {
        type: '',
        address: '',
        system_recharge: ''
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
        getInvestOrders({
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
