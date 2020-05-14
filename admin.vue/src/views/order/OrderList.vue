<template>
  <div class="app-container">
    <el-table v-loading="loading" :data="orderList">
      <el-table-column label="订单编号" prop="order_sn" />
      <el-table-column label="用户账号" prop="email" />
      <el-table-column label="开仓时间" width="160px">
        <template slot-scope="scope">{{ scope.row.create_time | dateReadable }}</template>
      </el-table-column>
      <el-table-column label="类型" prop="coin_type" width="75px" />
      <el-table-column label="方向" width="60px">
        <template slot-scope="scope">{{ scope.row.type | guessTypeName }}</template>
      </el-table-column>
      <el-table-column label="倍数" prop="multiple" width="90px" />
      <el-table-column label="开仓价格" align="right">
        <template slot-scope="scope">{{ scope.row.price | coinReadable }}</template>
      </el-table-column>
      <el-table-column label="止盈价格" align="right">
        <template slot-scope="scope">{{ scope.row.price | coinReadable }}</template>
      </el-table-column>
      <el-table-column label="止损价格" align="right">
        <template slot-scope="scope">{{ scope.row.price | coinReadable }}</template>
      </el-table-column>
      <el-table-column label="平仓价格" align="right">
        <template slot-scope="scope">{{ scope.row.end_price | coinReadable }}</template>
      </el-table-column>
      <el-table-column label="保证金金额/USDT" align="right">
        <template slot-scope="scope">{{ scope.row.amount | coinReadable }}</template>
      </el-table-column>
      <el-table-column label="盈利/USDT" align="right">
        <template slot-scope="scope">{{ scope.row.score | coinReadable }}</template>
      </el-table-column>
      <el-table-column label="手续费/USDT" align="right">
        <template slot-scope="scope">{{ scope.row.score | coinReadable }}</template>
      </el-table-column>
      <el-table-column label="状态" prop="status">
        <template slot-scope="scope">{{ scope.row.status | leverStatusName }}</template>
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
// import FormValidate from '@/mixins/FormValidate'
import {
  getOrderList
} from '@/apis/order'

export default {
  name: 'OrderList',
  // mixins: [
  //   FormValidate
  // ],

  data() {
    return {
      loading: true,
      orderList: null,
      // // 查询条件
      // queryForm: {
      //   order_sn: '',
      //   email: '',
      //   type: '',
      //   start_time: '',
      //   end_time: '',
      //   status: '',
      //   close_price_low: '',
      //   close_price_high: ''
      // },
      // // 验证规则
      // queryRules: {
      //   email: [{ trigger: 'blur', validator: this.validateEmailAddress }],
      //   close_price_low: [{ type: 'number', trigger: 'blur', validator: this.validateNumberRange('close_price') }]
      // },
      // 列表分页
      page: {
        index: 1,
        size: process.env.PAGE_SIZE,
        total: 0
      }
    }
  },

  // computed: {
  //   createDate: {
  //     get() {
  //       const fd = this.queryForm
  //       return fd.start_time && fd.end_time ? [
  //         this.convertString2Date(fd.start_time),
  //         this.convertString2Date(fd.end_time)
  //       ] : ''
  //     },
  //     set(pd) {
  //       const fd = this.queryForm
  //       fd.start_time = pd ? pd[0].format('YYYY-MM-DD 00:00:00') : ''
  //       fd.end_time = pd ? pd[1].format('YYYY-MM-DD 23:59:59') : ''
  //     }
  //   }
  // },

  created() {
    this.getOrderList()
  },

  methods: {
    getOrderList() {
      this.loading = true
      this.$mResponseHelper(
        getOrderList({
          // ...this.queryForm,
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
        this.loading = false
      })
    // },

    // handleQuery() {
    //   this.$refs.queryForm.validate(valid => {
    //     if (valid) {
    //       this.page.index = 1
    //       this.page.total = 0
    //       this.getOrderList()
    //     }
    //   })
    }
  }
}
</script>
