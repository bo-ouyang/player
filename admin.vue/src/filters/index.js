import Vue from 'vue'

Vue.filter('coinReadable', function(price, bits = 6) {
  return (price || price === 0) ? (+price).toFixed(bits) : price
})
Vue.filter('coinSymbol', function(coin) {
  return coin && coin.replace('_', '/').toUpperCase()
})

Vue.filter('dateReadable', function(date, endPos = 16, startPos = 0) {
  switch (typeof date) {
    case 'string': break

    case 'number':
      date = new Date(date * 1000).toUnified()

      break
    default:
      return date
  }

  return date.slice(startPos, endPos)
})

const investTypeName = ['1-20', '21-']
Vue.filter('investTypeName', function(type) {
  return investTypeName[type - 1]
})

const holderGradeName = ['普通', '银钻', '金钻', '翡翠', '宝石', '皇冠']
Vue.filter('holderGradeName', function(grade) {
  return holderGradeName[grade]
})

const cheerTypeName = ['铜钥匙', '翡翠钥匙', '水晶钥匙', '超级彩蛋']
Vue.filter('cheerTypeName', function(type) {
  return cheerTypeName[type - 1]
})

const tokenTypeName = ['购买', '彩蛋']
Vue.filter('tokenTypeName', function(type) {
  return tokenTypeName[type - 1]
})
