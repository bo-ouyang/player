<template>
  <iframe :src="`/rules/${userHabit.language}.html?r=${new Date().format('YYYYMMDD')}`" class="container" />
  <!-- <article class="container">
    <img class="page-title" src="~@/assets/images/rule/bg-rule.0101.jpg" width="750" height="937" />
    <h5>
      <img :src="logoPath" class="page-logo align-horz" width="684" height="162" />
    </h5>
    <p class="page-intro">我是凱文.大衛.米特尼克（Keven David Mitnick）<br/>沒錯，就是你們所說的世界第一駭客<br/>我，又回來了！<br/>我回來只有一個原因—區塊鏈！<br/>我發現這是一個很有趣的東西<br/>我可以利用區塊鏈構建一個完全無中心無監管無政府的體系<br/>這大大激發了我的好奇心<br/>因此我聯合世界最大的駭客組織—匿名者組織（ANONYMOUS）<br/>結合我最喜歡的電影《頭號玩家》<br/>打造了一個具有未來完美社會概念的遊戲烏托邦<br/>願每一位朋友都能在這個世界裡獲得自己想要的快樂和財富<br/>希望你們喜歡這份禮物</p>

    <section class="content">
      <h3 class="content-title">{{ titleList[0] }}</h3>
      <p class="content-intro">{{ textList[0] }}</p>
      <p v-html="textList[1]" class="cont-sty0101" />
    </section>

    <section class="content">
      <h3 class="content-title">{{ titleList[1] }}</h3>
      <ul class="cont-sty0102">
        <li
          v-for="(text, index) in text0102"
          :key="index"
          class="cont-sty0102-item bod-b-1px">
          {{ text }}
        </li>
      </ul>
    </section>

    <section class="content">
      <h3 class="content-title">{{ titleList[2] }}</h3>
      <ul class="cont-sty0103">
        <li
          v-for="(text, index) in text0103"
          :key="index"
          :label="`PART${index + 1}`"
          class="cont-sty0103-item">
          <p v-html="text" />
        </li>
      </ul>
    </section>

    <section class="content">
      <h3 class="content-title">{{ titleList[3] }}</h3>
      <ul class="cont-sty0104">
        <li
          v-for="(text, index) in text0104"
          :key="index"
          :label="`PART${index + 1}`"
          class="cont-sty0104-item">
          <h4 class="cont-sty0104-title">{{ text.title }}</h4>
          <p>{{ text.content }}</p>
        </li>
      </ul>
    </section>

    <section class="content">
      <h3 class="content-title">{{ titleList[4] }}</h3>
      <p v-html="textList[2]" class="cont-sty0105" />
    </section>

    <section class="content">
      <h3 class="content-title">{{ titleList[5] }}</h3>
      <ul class="cont-sty0106">
        <li
          v-for="(text, index) in text0106"
          :key="index"
          :label="`PART${index + 1}`"
          class="cont-sty0106-item">
          {{ text }}
        </li>
      </ul>
    </section>

    <section class="content">
      <h3 class="content-title">{{ titleList[6] }}</h3>
      <ul class="cont-sty0104">
        <li
          v-for="(text, index) in text0107"
          :key="index"
          :label="`PART${index + 1}`"
          class="cont-sty0104-item">
          <h4 class="cont-sty0104-title">{{ text.title }}</h4>
          <p v-html="text.content" />
        </li>
      </ul>
      <p v-html="textList[3]" class="cont-sty0105 m--sty0107" />
    </section>

    <section class="content">
      <h3 class="content-title">{{ titleList[7] }}</h3>
      <ul class="cont-sty0108">
        <li
          v-for="(text, index) in text0108"
          :key="index"
          :label="`PART${index + 1}`"
          class="cont-sty0108-item">
          <span>{{ text }}</span>
        </li>
      </ul>
      <div v-html="textList[4]" class="cont-sty0105 m--sty0108" />
    </section>

    <section class="content">
      <h3 class="content-title">{{ titleList[8] }}</h3>
      <ul class="cont-sty0109">
        <li
          v-for="(text, index) in text0109"
          :key="index"
          class="cont-sty0109-item">
          <h4 class="cont-sty0109-title">{{ text.title }}</h4>
          <p>{{ text.content }}</p>
        </li>
      </ul>
    </section>
  </article> -->
</template>

<script>
export default {
  name: 'PlatformRules',

  // data () {
  //   return {
  //     logoPath: this.$store.state.userHabit.language === 'zh-cn'
  //       ? require('@/assets/images/rule/logo-zh.0101.png')
  //       : require('@/assets/images/rule/logo-en.0101.png'),
  //     titleList: [
  //       '頭號玩家',
  //       '頭號玩家-區塊鏈的尖端技術',
  //       '區塊鏈特性：安全透明',
  //       '安全性',
  //       '如何參與',
  //       '遊戲收益',
  //       '靜態收益',
  //       '動態收益',
  //       '股東收益'
  //     ],
  //     textList: [
  //       '真正去中心化的玩家社區又一造富神話',
  //       '所有資源來自暗網，無專案方<br/>智慧幣值維護機器人（智慧合約清晰可查）<br/>社區100%撥比，真正去中心化<br/>清晰的遊戲開始時間和明確的遊戲結束機制<br/>所有資料即操作採用智慧合約，無人為幹預',
  //       '全球去中心化ETH錢包即可參與；<br/>無需額外註冊，交易所購買ETH或錢包存有ETH即可；<br/>登錄入口(各大去中心化錢包的區塊流覽器)',
  //       '每筆流入資金的75%自動進入靜態分紅池；<br/><span class="fc-tabon">月化：15%-300%，月平均60%左右；</span><br/><span class="fc-tabon">日化：0.5%-10%，日平均2%左右；</span><br/>區塊結算，合約根據不同時間戳記錄的每個區塊的流入資金量智慧計算出日化收益。',
  //       '<p>直推1人拿上下各一代、直推2人拿上下各2代、直推3人拿上下各3代。直推4人及以上拿上3代及下7代全部收益；</p><p class="phrase">燒傷：傘下成員參與額度最高按自身參與額度計算動態收益<p>'
  //     ],
  //     text0102: [
  //       '智能合約',
  //       '區塊時間',
  //       '燃燒機制',
  //       '無項目方',
  //       '零延遲',
  //       '權杖升值'
  //     ],
  //     text0103: [
  //       '智能合約+DAPP(全智能化執行、無中心、不可篡改)',
  //       '無需下載APP,無需帳號註冊,自動識別冷錢包地址全程操作；<br/>無需收取任何驗證碼,直接進入DAPP區塊鏈專用瀏覽器登錄',
  //       '無伺服器,無操盤手,無需升級,無中心帳號,無法篡改,不可逆轉',
  //       '所有智能合約內容鏈上執行,透明、公開、公平'
  //     ],
  //     text0104: [
  //       {
  //         title: '技術安全',
  //         content: '　　無伺服器,無域名,無APP,無後台,無操盤手，所有程序架構在ETH+IPFS上,採用智能合約和分布式存儲，不用通過頁面一樣可以提現'
  //       },
  //       {
  //         title: '資訊安全',
  //         content: '　　不必用手機郵箱或身份證註冊,只要有去中心化的ETH系錢包地址即可參與遊戲完全不泄露任何私人信息,無安全隱患'
  //       },
  //       {
  //         title: '資金安全',
  //         content: '　　所有資金全部在合約裡(非個人位址),任何個人或組織也不能私自動用此資金，只有觸碰並達成合約條件時,才能按合約自動分發各項收益，並且所有的資訊上鏈,完全公平,公正,公開透明,不可篡改'
  //       },
  //       {
  //         title: '提現安全',
  //         content: '　　隨時提現秒到,鏈上智慧合約處理,無需任何人、任何組織批准'
  //       }
  //     ],
  //     text0106: [
  //       '靜態收益',
  //       '動態收益',
  //       '股東收益',
  //       'X1權杖收益',
  //       '彩蛋遊戲收益',
  //       '全球節點收益'
  //     ],
  //     text0107: [
  //       {
  //         title: '沙漠',
  //         content: '<span class="fc-tabon">1-29ETH</span><br/>靜態收益+動態收益；5倍出局'
  //       },
  //       {
  //         title: '綠洲',
  //         content: '<span class="fc-tabon">30ETH及以上</span><br/>靜態收益+動態收益；10倍出局'
  //       }
  //     ],
  //     text0108: [
  //       '3代10%',
  //       '2代20%',
  //       '1代30%',
  //       '我獲得上级級收益',
  //       '我',
  //       '我獲得下級收益',
  //       '1代100%',
  //       '2代50%',
  //       '3代25%',
  //       '4代10%',
  //       '5代10%',
  //       '6代10%',
  //       '7代10%'
  //     ],
  //     text0109: [
  //       {
  //         title: '初級玩家：2%',
  //         content: '總業績達到200ETH，加權平分全球總業績的2%，每日結算'
  //       },
  //       {
  //         title: '中級玩家：3%',
  //         content: '總業績達到500ETH，加權平分全球總業績的3%，每日結算'
  //       },
  //       {
  //         title: '高級玩家：4%',
  //         content: '總業績達到1000ETH，加權平分全球總業績的4%，每日結算'
  //       },
  //       {
  //         title: '超級玩家：5%',
  //         content: '總業績達到2000ETH，加權平分全球總業績的5%，每日結算'
  //       },
  //       {
  //         title: '頭號玩家：6%',
  //         content: '總業績達到5000ETH，加權平分全球總業績的6%，每日結算'
  //       }
  //     ]
  //   }
  // },

  computed: {
    userHabit () {
      return this.$store.state.userHabit
    }
  }
}
</script>

<style lang="scss" scoped>
@import '~@/styles/defines';

.container {
  width: 100%;
  height: 100%;
  border: 0;
  // background:
  //   url(~@/assets/images/rule/bg-rule.0103.jpg) center 18.08rem / 7.5rem auto no-repeat,
  //   url(~@/assets/images/rule/bg-rule.0104.jpg) center 30.66rem / 7.5rem auto no-repeat,
  //   url(~@/assets/images/rule/bg-rule.0105.jpg) center 44.54rem / 7.5rem auto no-repeat,;
}

// .page {
//   &-title {
//     display: block;
//     width: 100%;
//     height: auto;
//   }
//   &-logo {
//     width: 6.84rem;
//     height: 1.62rem;

//     &.align-horz {
//       top: 7.1rem;
//     }
//   }
//   &-intro {
//     box-sizing: border-box;
//     min-height: 8.71rem;
//     padding-top: 3.36rem;
//     color: #ffe762;
//     font-size: $small-size;
//     text-align: center;
//     background: url(~@/assets/images/rule/bg-rule.0102.jpg) center top / 100% auto no-repeat;
//   }
// }

// .content {
//   margin-top: 0.68rem;

//   &-title {
//     position: relative;
//     padding-left: 0.4rem;
//     color: $font-color;
//     font-size: 0.36rem;

//     &::before {
//       content: "";
//       position: absolute;
//       top: 50%;
//       left: 0.24rem;
//       width: 0.06rem;
//       height: 0.32rem;
//       background-image: linear-gradient(90deg, #66e6ff, #2a7dfc);
//       @include transform(translateY(-50%));
//     }
//   }
//   &-intro {
//     padding-left: 0.38rem;
//     @include margin-tb(0.08rem);
//     color: #47def5;
//     font-size: 0.27rem;
//   }
// }

// .cont {
//   &-sty0101 {
//     width: 6.54rem;
//     padding: {
//       top: 0.55rem;
//       bottom: 0.4rem;
//     }
//     @include margin-center;
//     border: 0.03rem solid rgba($color: #40c9ea, $alpha: 0.5);
//     color: #f8edbd;
//     font-size: 0.3rem;
//     line-height: 0.56rem;
//     font-weight: 600;
//     text-align: center;
//     @include text-gradient(90deg, #c2a24f, #efe1a7, #b79540, #f9f1be, #a98629, #a98629);

//     .content-intro + & {
//       margin-top: 0.32rem;
//     }
//   }

//   &-sty0102 {
//     position: relative;
//     height: 4.3rem;
//     background: url(~@/assets/images/rule/mark.0101.png) center 1.12rem / auto 2.62rem no-repeat;

//     &-item {
//       position: absolute;
//       @include padding-tb(0.02rem);
//       font-size: 0.24rem;

//       &.bod-b-1px::after {
//         background-color: #2c93aa;
//       }
//       &:nth-child(1) {
//         top: 0.6rem;
//         left: 50%;
//         @include transform(translateX(-50%));
//       }
//       &:nth-child(2) {
//         top: 1.52rem;
//         left: 50%;
//         @include transform(translateX(1.9rem));
//       }
//       &:nth-child(3) {
//         top: 2.92rem;
//         left: 50%;
//         @include transform(translateX(1.9rem));
//       }
//       &:nth-child(4) {
//         top: 3.84rem;
//         left: 50%;
//         @include transform(translateX(-50%));
//       }
//       &:nth-child(5) {
//         top: 2.92rem;
//         right: 50%;
//         @include transform(translateX(-1.9rem));
//       }
//       &:nth-child(6) {
//         top: 1.52rem;
//         right: 50%;
//         @include transform(translateX(-1.9rem));
//       }
//     }
//   }

//   &-sty0103 {
//     padding: 00.65rem 0.42rem 0.1rem;

//     &-item {
//       position: relative;
//       padding: 0.05rem 0.12rem;
//       border: 0.02rem solid rgba($color: #40c9ea, $alpha: 0.5);
//       font-size: $small-size;

//       & + & {
//         margin-top: 0.3rem;
//       }
//       &:nth-child(odd)::before,
//       &:nth-child(even)::after {
//         content: attr(label);
//         position: absolute;
//         top: 0;
//         color: #4addf5;
//         font-size: 0.3rem;
//         font-style: italic;
//       }
//       &:nth-child(odd) {
//         margin-left: 1.06rem;

//         &::before {
//           left: -1.06rem;
//         }
//       }
//       &:nth-child(even) {
//         margin-right: 1rem;

//         &::after {
//           right: -1rem;
//         }
//       }
//     }
//   }

//   &-sty0104 {
//     padding: 0.24rem 0.52rem 0 0.24rem;

//     &-item {
//       font-size: $small-size;

//       & + & {
//         margin-top: 0.35rem;
//       }
//     }
//     &-title {
//       color: #3eb3c8;
//       font-size: 0.3rem;
//     }
//   }

//   &-sty0105 {
//     @include padding-tb(0.68rem);
//     margin: 0.65rem 0.42rem 1.2rem 0.48rem;
//     border: 0.03rem solid rgba($color: #40c9ea, $alpha: 0.5);
//     font-size: 0.26rem;
//     text-align: center;

//     &.m--sty0107 {
//       padding: 0.3rem 0.4rem 0.52rem;
//     }
//     &.m--sty0108 {
//       padding: 0.28rem 0.15rem 0.25rem;
//       text-align: left;
//     }
//     /deep/ .phrase {
//       padding-left: 3em;
//       text-indent: -3em;
//     }
//   }

//   &-sty0106 {
//     position: relative;
//     height: 6.74rem;

//     &-item {
//       position: absolute;
//       width: 1.6rem;
//       font-size: $small-size;
//       text-align: center;

//       &::before {
//         content: attr(label);
//         display: block;
//         width: 1.19rem;
//         height: 0.67rem;
//         margin: 0 auto 0.1rem;
//         font-size: 0.3rem;
//         line-height: 0.67rem;
//         background: url(~@/assets/images/rule/mark.0102.png) center / cover no-repeat;
//       }
//       &:nth-child(1) {
//         top: 0.6rem;
//         left: 0.48rem;
//       }
//       &:nth-child(2) {
//         top: 0.6rem;
//         right: 0.32rem;
//       }
//       &:nth-child(3) {
//         top: 3.08rem;
//         right: 0.32rem;
//       }
//       &:nth-child(4) {
//         top: 3.08rem;
//         left: 0.48rem;
//       }
//       &:nth-child(5) {
//         top: 5.62rem;
//         left: 0.48rem;
//       }
//       &:nth-child(6) {
//         top: 5.62rem;
//         right: 0.32rem;
//       }
//       &:nth-child(2),
//       &:nth-child(4),
//       &:nth-child(6) {
//         &::after {
//           content: "";
//           position: absolute;
//           top: 0.32rem;
//           left: -3.65rem;
//           width: 3.79rem;
//           height: 1px;
//           background: url(~@/assets/images/rule/desc.0101.h.png) center / 100% auto no-repeat;
//         }
//       }
//       &:nth-child(4)::after {
//         left: 1.45rem;
//       }
//       &:nth-child(3),
//       &:nth-child(5) {
//         &::after {
//           content: "";
//           position: absolute;
//           top: -1.28rem;
//           left: 0.76rem;
//           width: 1px;
//           height: 1.09rem;
//           background: url(~@/assets/images/rule/desc.0101.v.png) center / auto 100% no-repeat;
//         }
//       }
//     }
//   }

//   &-sty0108 {
//     position: relative;
//     margin: {
//       top: 0.72rem;
//       bottom: 0.38rem;
//     }

//     &-item {
//       @include padding-tb(0.17rem);
//       font-size: 0.36rem;
//       background: url(~@/assets/images/rule/mark.0103.png) center center / 0.88rem auto no-repeat;

//       & + & {
//         margin-top: 0.2rem;
//       }
//       &:nth-child(1),
//       &:nth-child(2),
//       &:nth-child(3) {
//         padding-left: 50%;

//         span {
//           display: inline-block;
//           margin-left: 0.84rem;
//           @include text-gradient(138deg, #f6ebb6, #c19e4d 50%, #f6ebb6 96%);
//         }
//       }
//       &:nth-child(5) {
//         width: 0.88rem;
//         @include margin-center;
//         border-radius: 50%;
//         text-align: center;
//         background: rgba($color: #092437, $alpha: 0.8);

//         span {
//           @include text-gradient(138deg, #f6ebb6, #c19e4d 50%, #f6ebb6 96%);
//         }
//       }
//       &:nth-child(4),
//       &:nth-child(6) {
//         position: absolute;
//         margin: 0;
//         padding: 0;
//         color: #33a2ed;
//         text-align: center;
//         writing-mode: vertical-lr;
//         background-image: none;
//       }
//       &:nth-child(4) {
//         top: 3.15rem;
//         left: 50%;
//         transform: translateX(1rem);

//         &::before {
//           transform: rotate(180deg);
//         }
//       }
//       &:nth-child(6) {
//         bottom: 7.48rem;
//         right: 50%;
//         transform: translateX(-1.15rem);
//       }
//       &:nth-child(4)::before,
//       &:nth-child(6)::after {
//         content: "";
//         display: inline-block;
//         width: 0.78rem;
//         height: 1.6rem;
//         background: url(~@/assets/images/rule/mark.0104.png) center / cover no-repeat;
//       }
//       &:nth-child(7),
//       &:nth-child(8),
//       &:nth-child(9),
//       &:nth-child(10),
//       &:nth-child(11),
//       &:nth-child(12),
//       &:nth-child(13) {
//         padding-right: 50%;
//         text-align: right;

//         span {
//           display: inline-block;
//           margin-right: 0.76rem;
//           @include text-gradient(138deg, #f6ebb6, #c19e4d 50%, #f6ebb6 96%);
//         }
//       }
//     }
//   }

//   &-sty0109 {
//     margin: {
//       top: 0.75rem;
//       bottom: 1.1rem;
//     }

//     &-title {
//       @include margin-tb(0.12rem);
//       font-size: 0.3rem;
//     }
//     &-item {
//       padding-left: 1.7rem;
//       font-size: 0.26rem;

//       & + & {
//         margin-top: 0.48rem;
//       }
//     }
//   }
// }
</style>
