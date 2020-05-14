const USER_KEY     = process.env.STORAGE_USER // 用户信息本地存储键值
const EXPIRE_TIME  = 2 * 60 * 60 * 1000       // 用户信息存储有效期：2H
const USER_HABIT   = 'user_habit'
const REPORT_QUEUE = 'queue_report'

const storage      = window.localStorage

/**
 * 获取本地存储的用户信息
 */
export function getUserInfo () {
  try {
    const userInfo = JSON.parse(storage.getItem(USER_KEY) || '{}')
    if (!userInfo.expriedTime || userInfo.expriedTime < Date.now()) {
      throw new Error('User-info is expried.')
    } else {
      userInfo.expriedTime = Date.now() + EXPIRE_TIME
      storage.setItem(USER_KEY, JSON.stringify(userInfo))

      return userInfo
    }
  } catch (e) {
    storage.clear()
  }

  return {}
}

/**
 * 保存用户信息到本地，并更新expriedTime值
 * @param {Object} userInfo 用户信息
 * @return {timestamp} 更新后的时间戳
 */
export function setUserInfo (userInfo) {
  userInfo = {
    ...userInfo,
    expriedTime: Date.now() + EXPIRE_TIME
  }
  storage.setItem(USER_KEY, JSON.stringify(userInfo))

  return userInfo.expriedTime
}

/**
 * 清除本地存储的用户信息
 */
export function delUserInfo () {
  storage.removeItem(USER_KEY)
}

// 支持语言
const LANGUAGE_MAP = {
  'zh': 'zh-cn',
  'en': 'en-us',
  ja: 'ja',
  de: 'de',
  ru: 'ru'
}

export function getUserHabit () {
  try {
    const habit = JSON.parse(storage.getItem(USER_HABIT) || '{}')
    if (!habit.language) {
      const locale = /[?|&]locale=([^&]+)/.exec(location.search)
      // 默认中文语言
      habit.language = (locale && LANGUAGE_MAP[/^([^-]*)/.exec(locale[1])[1]]) || 'zh-cn'
    }

    return habit
  } catch (e) {
    storage.removeItem(USER_HABIT)
  }

  return { language: 'zh-cn' }
}

export function setUserHabit (data) {
  storage.setItem(USER_HABIT, JSON.stringify(data))
}

// 支付成功提交失败订单
export function getReportQueue () {
  try {
    return JSON.parse(storage.getItem(REPORT_QUEUE) || '[]')
  } catch (e) {
    storage.removeItem(REPORT_QUEUE)
  }

  return []
}
export function setReportQueue (queue) {
  storage.setItem(REPORT_QUEUE, JSON.stringify(queue))
}
