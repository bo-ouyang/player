import Vue from 'vue'
import {
  reportPayError
} from '@/apis/common'

export function sendEthTransaction (payInfo) {
  return window.imToken ? new Promise((resolve, reject) => {
    payInfo.orderInfo = 'Kang Fu Ren Jia'
    payInfo.feeCustomizable = true
    window.imToken.callAPI('transaction.tokenPay', payInfo, (err, txHash) => {
      if (err) {
        const errCode = err.errorCode || err.code
        if (+errCode !== 1001) {
          // 用户取消操作
          Vue.prototype.$mToast(`${errCode}: ${err.message}`)
          reportPayError({
            payInfo: JSON.stringify(payInfo),
            formData: '[NULL]',
            reason: JSON.stringify({
              code: errCode,
              message: err.message
            })
          })
        }

        reject(err)
      } else {
        resolve(txHash)
      }
    })
  }) : new Promise((resolve, reject) => {
    window.web3.eth.sendTransaction(payInfo, (err, txHash) => {
      if (err) {
        if (+err.errorCode !== 1001) {
          const message = err.message || err + ''
          Vue.prototype.$mToast(message)
          reportPayError({
            payInfo: JSON.stringify(payInfo),
            formData: '[NULL]',
            reason: JSON.stringify({
              code: err.code || err.errorCode,
              message
            })
          })
        }

        reject(err)
      } else {
        resolve(txHash)
      }
    })
  })
}
