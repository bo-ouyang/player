<template>
  <div class="wrap-H100P container">
    <router-view class="page-body page-body--main" style="height:calc(100% - 0.98rem);" />
    <nav class="nav-bar flex-box">
      <li class="nav-bar-item flex-grow1" :class="{ active: categroy === 'home' }">
        <router-link :to="{ name: 'HomePage' }" replace>
          <i class="nav-bar-icon iconfont icon-home-line" />
          首页
        </router-link>
      </li>
      <li class="nav-bar-item flex-grow1" :class="{ active: categroy === 'quiz' }">
        <router-link :to="{ name: 'QuizTrade' }" replace>
          <i class="nav-bar-icon iconfont icon-star-question" />
          竞猜
        </router-link>
      </li>
      <li class="nav-bar-item flex-grow1" :class="{ active: categroy === 'pact' }">
        <router-link :to="{ name: 'PactHome' }" replace>
          <i class="nav-bar-icon iconfont icon-leaver" />
          合约
        </router-link>
      </li>
      <li class="nav-bar-item flex-grow1" :class="{ active: categroy === 'account' }">
        <router-link :to="{ name: 'Account' }" replace>
          <i class="nav-bar-icon iconfont icon-person" />
          我的
        </router-link>
      </li>
    </nav>
  </div>
</template>

<script>
export default {
  name: 'MainLayout',
  props: {
    categroy: String
  }
}
</script>
