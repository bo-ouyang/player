<template>
  <div class="app-container">
    <el-form
      ref="configForm"
      :model="configForm"
      :rules="configRules"
      label-width="120px"
      style="width:520px;margin-top:20px;"
      novalidate
      @submit.native.prevent="handleSubmit">
      <el-form-item label="充值地址：" prop="address">
        <el-input v-model.trim="configForm.address" type="text" />
      </el-form-item>
      <el-form-item label="充值金额：" prop="number">
        <el-input v-model.number="configForm.number" type="number" />
      </el-form-item>
      <el-form-item label="交易HASH：" prop="hash">
        <el-input v-model.trim="configForm.hash" type="text" />
      </el-form-item>
      <el-form-item label="上级邀请码：" prop="invite_code">
        <el-input v-model.trim="configForm.invite_code" type="text" />
      </el-form-item>
      <el-form-item label="充值类型：" prop="type">
        <el-radio-group v-model="configForm.type" @change="configForm.egg_type = ''">
          <el-radio :label="1">静态投资充值</el-radio>
          <el-radio :label="2">彩蛋充值</el-radio>
          <el-radio :label="3">ACGG令牌充值</el-radio>
        </el-radio-group>
      </el-form-item>
      <el-form-item label="彩蛋类型：" prop="egg_type">
        <el-radio-group v-model="configForm.egg_type" :disabled="configForm.type !== 2">
          <el-radio v-for="type in 4" :key="type" :label="type">{{ type | cheerTypeName }}</el-radio>
        </el-radio-group>
      </el-form-item>
      <el-form-item>
        <el-button :loading="isSubmitting" type="primary" native-type="submit">保存</el-button>
      </el-form-item>
    </el-form>
  </div>
</template>

<script>
import {
  getWalletAddress,
  submitOrder
} from '@/apis/config'

export default {
  name: 'SubmitOrder',

  data() {
    return {
      isSubmitting: false,
      configForm: {
        receive_address: '',
        origin_address: '',
        number: '',
        hash: '',
        invite_code: '',
        type: 1,
        egg_type: '',
        system_recharge: 2
      },
      configRules: {
        origin_address: [{ required: true, message: '必填项' }],
        number: [
          { required: true, type: 'number', message: '必填项' },
          { type: 'number', validator: (rule, value, callback) => {
            if (value <= 0) {
              callback('须大于 0')
            } else {
              callback()
            }
          } }
        ],
        hash: [{ required: true, message: '必填项' }],
        invite_code: [{ required: true, message: '必填项' }],
        type: [{ required: true, message: '必选项' }],
        egg_type: [
          { validator: (rule, value, callback) => {
            if (value || this.configForm.type !== 2) {
              callback()
            } else {
              callback('必选项')
            }
          } }
        ]
      },

      // 收款钱包
      walletAddress: []
    }
  },

  created() {
    this.$mResponseHelper(
      getWalletAddress(),
      data => {
        // this.configForm.receive_address = data.contract_address
        this.walletAddress = [data.contract_address, data.egg_address, data.token_address]
      }
    )
  },

  methods: {
    handleSubmit() {
      this.$refs.configForm.validate(valid => {
        if (valid) {
          const formData = this.configForm
          let amount = formData.number + ''
          if (amount.includes('.')) {
            const index = amount.indexOf('.')
            amount = amount.slice(0, index) + amount.slice(index + 1).padEnd(18, '0')
          } else {
            amount += '000000000000000000'
          }

          this.isSubmitting = true
          this.$mResponseHelper(
            submitOrder({
              ...formData,
              receive_address: this.walletAddress[formData.type - 1],
              amount
            }),
            () => {
              this.$message.success('提交成功')
            }
          ).finally(() => {
            this.isSubmitting = false
          })
        }
      })
    }
  }
}
</script>
