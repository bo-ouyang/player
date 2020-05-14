<template>
  <el-dialog
    :close-on-click-modal="false"
    :close-on-press-escape="false"
    class="form-wide"
    visible
    @close="$emit('close')">
    <h4 slot="title">
      当前金额：{{ totalAmount }}/{{ statsInfo.quota }}
      <span style="font-size:13px;">请先设置金额达到开奖要求</span>
    </h4>
    <el-table
      v-loading="isAccountLoading"
      :data="accountList"
      height="250px"
      @selection-change="selectAccounts = $event">
      <el-table-column label="后台地址" prop="origin_address" min-width="250px" fixed />
      <el-table-column label="支付金额/ETH" min-width="110px">
        <template slot-scope="scope">
          <el-input v-model.number="scope.row.amount" type="number" size="mini" placeholder="请输入金额" />
        </template>
      </el-table-column>
      <el-table-column type="selection" label="中奖" width="50px" />
    </el-table>
    <el-table
      v-loading="isAddressLoading"
      :data="addressList"
      height="250px"
      @selection-change="selectAddress = $event">
      <el-table-column label="用户地址" prop="origin_address" min-width="250px" fixed />
      <el-table-column label="支付金额/ETH" min-width="110px">
        <template slot-scope="scope">{{ scope.row.amount | coinReadable }}</template>
      </el-table-column>
      <el-table-column type="selection" label="中奖" width="50px" />
    </el-table>
    <div slot="footer" style="text-align:center;">
      <el-button :loading="isSubmitting" type="primary" @click="handleSubmit">确定开奖</el-button>
      <el-button :disabled="isSubmitting" @click="$emit('close')">取消</el-button>
    </div>
  </el-dialog>
</template>

<script>
import {
  getCheerOrders,
  getCheerAccount,
  drawCheerTimes
} from '@/apis/order'

export default {
  name: 'CheerConfig',
  props: {
    cheerType: {
      type: Number,
      required: true
    },
    eggId: {
      type: Number,
      required: true
    }
  },

  data() {
    return {
      isSubmitting: false,
      isAccountLoading: true,
      accountList: null,
      selectAccounts: [],
      isAddressLoading: true,
      addressList: null,
      selectAddress: [],
      statsInfo: {}
    }
  },

  computed: {
    totalAmount() {
      let amount = this.statsInfo.amount
      this.selectAccounts.forEach(item => {
        amount += item.amount || 0
      })
      return amount
    }
  },

  created() {
    this.$mResponseHelper(
      getCheerAccount({
        egg_id: this.eggId
      }),
      data => {
        this.accountList = data.list
      }
    ).finally(() => {
      this.isAccountLoading = false
    })

    this.$mResponseHelper(
      getCheerOrders({
        type: this.cheerType,
        address: '',
        status: 1,
        page: 1,
        page_size: 1000
      }),
      data => {
        this.statsInfo = data
        data.amount = +data.amount
        data.quota = +data.quota

        this.addressList = data.list
      }
    ).finally(() => {
      this.isAddressLoading = false
    })
  },

  methods: {
    handleSubmit() {
      if (this.selectAccounts.length + this.selectAddress.length > 0) {
        if (this.totalAmount < this.statsInfo.quota) {
          this.$message.error('金额未达开奖要求')
        } else if (this.selectAccounts.find(item => !item.amount || item.amount < 0)) {
          this.$message.error('输入金额须大于 0')
        } else {
          const filterIds = []
          this.isSubmitting = true
          this.$mResponseHelper(
            drawCheerTimes({
              egg_id: this.statsInfo.egg_id,
              order: this.selectAccounts.map(item => ({
                user_id: item.user_id,
                amount: item.amount
              })),
              luck_id: this.selectAddress.map(item => item.user_id).filter(id => {
                if (!filterIds.includes(id)) {
                  filterIds.push(id)
                  return true
                }
              })
            }),
            () => {
              this.$message.success('操作成功')
              this.$emit('close', true)
            }
          ).finally(() => {
            this.isSubmitting = false
          })
        }
      } else {
        this.$message.error('请选择中奖地址')
      }
    }
  }
}
</script>
