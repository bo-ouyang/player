import Vue from 'vue'
import i18n from '@/i18n'

Vue.filter('coinStock', function (coin) {
  return coin && coin.split('_')[0].toUpperCase()
})
Vue.filter('coinMoney', function (coin) {
  return coin && coin.split('_')[1].toUpperCase()
})
Vue.filter('coinSymbol', function (coin) {
  return coin && coin.replace('_', ' / ').toUpperCase()
})
Vue.filter('coinReadable', function (money, number = 4) {
  return (money || money === 0) ? (+money).toFixed(number) : 0
})
Vue.filter('coinPrecision', function (money) {
  // 数值格式化：最少显示6位数字，不足小数补全；小数位最少2位
  if (money || money === 0) {
    money = (+money).toFixed(6)
    return money.slice(0, money.length >= 11 ? -4 : 7 - money.length)
  } else {
    return 0
  }
})

Vue.filter('signReadable', function (number, sign) {
  if (!number) {
    return number
  }

  if (number.toString()[0] !== '-') {
    number = '+' + number
  }

  if (sign) {
    number = number[0] + sign + number.slice(1)
  }

  return number
})

Vue.filter('mobileReadable', function (mobile) {
  return mobile && `${mobile.slice(0, 3)} ${mobile.slice(3, 7)} ${mobile.slice(7)}`
})
Vue.filter('mobileEnsecret', function (mobile) {
  return mobile && `${mobile.slice(0, 3)} **** ${mobile.slice(7)}`
})

Vue.filter('dateReadable', function (date, wei) {
  switch (typeof date) {
    case 'string': break
    case 'number':
      date = new Date(date * 1000).toUnified()

      break
    default:
      return date
  }

  return date.slice(0, wei || 16)
})
Vue.filter('timeReadable', function (time) {
  const sign = time < 0 ? '-' : ''
  time = Math.abs(time)
  time = time >= 3600
    ? Math.round(time / 3600) + '小时'
    : time > 60
      ? Math.round(time / 60) + '分钟'
      : time + '秒'
  return sign + time
})
Vue.filter('timeCounter', function (time) {
  // 946656000000: 2000-01-01 00:00:00
  return new Date((time * 1000 || 0) + 946656000000).format('hh : mm : ss')
})

Vue.filter('moneyReadable', function (money, num) {
  return (+money).toReadable(num)
})

Vue.filter('fontTrendColor', function (number) {
  return number > 0 ? 'fc-rise' : number < 0 ? 'fc-fall' : ''
})
Vue.filter('signTrendClass', function (number) {
  return number > 0 ? 'icon-direct-up' : number < 0 ? 'icon-direct-dn' : ''
})
Vue.filter('iconTrendClass', function (number) {
  return number > 0 ? 'color-trend-up' : number < 0 ? 'color-trend-dn' : 'color-trend-no'
})
Vue.filter('signMinusClass', function (number) {
  return number > 0 ? 'icon-sign-add' : number < 0 ? 'icon-sign-sub' : ''
})

const cheerTypeName = [
  'name.i01_002_3',
  'name.i01_002_1',
  'name.i01_002_2',
  'name.i01_002_0'
]
Vue.filter('cheerTypeName', function (type) {
  return i18n.t(cheerTypeName[type - 1])
})

const holderGradeName = [
  'name.i01_001_0',
  'name.i01_001_1',
  'name.i01_001_2',
  'name.i01_001_3',
  'name.i01_001_4',
  'name.i01_001_5'
]
Vue.filter('holderGradeName', function (grade) {
  return i18n.t(holderGradeName[grade])
})
