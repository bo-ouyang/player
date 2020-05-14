<template>
  <div class="login-container">
    <el-form
      ref="loginForm"
      :model="loginForm"
      :rules="loginRules"
      class="login-form"
      @submit.native.prevent="handleLogin">
      <h3 class="title">后台管理系统</h3>
      <el-form-item prop="username">
        <el-input v-model.trim="loginForm.username" type="text" placeholder="username">
          <svg-icon slot="prefix" icon-class="user" />
        </el-input>
      </el-form-item>
      <el-form-item prop="password">
        <el-input v-model="loginForm.password" type="password" placeholder="password" show-password>
          <svg-icon slot="prefix" icon-class="password" />
        </el-input>
      </el-form-item>
      <el-form-item>
        <el-button
          :loading="isLoading"
          :disabled="isLoaded"
          type="primary"
          native-type="submit"
          style="width:100%;">
          登 录
        </el-button>
      </el-form-item>
    </el-form>
  </div>
</template>

<script>
export default {
  name: 'SignIn',

  data() {
    return {
      isLoading: false,
      isLoaded: false,
      loginForm: {
        username: '',
        password: ''
      },
      loginRules: {
        username: [{ required: true, trigger: 'blur', message: '请输入账号名称' }],
        password: [{ required: true, trigger: 'blur', message: '请输入登录密码' }]
      }
    }
  },

  created() {
    if (this.$store.state.user.props.accessToken) {
      this.$router.replace({ name: 'HomePage' })
    }
  },

  methods: {
    handleLogin() {
      this.$refs.loginForm.validate(valid => {
        if (!valid) return false

        this.isLoading = true
        this.$mResponseHelper(
          this.$store.dispatch('user/SignIn', this.loginForm),
          () => {
            this.isLoaded = true
            this.loginForm.password = ''
            if (history.length > 1) {
              this.$router.back()
            } else {
              this.$router.replace(this.$route.query.redirect || { name: 'SignIn' })
            }
          }
        ).finally(() => {
          this.isLoading = false
        })
      })
    }
  }
}
</script>

<style rel="stylesheet/scss" lang="scss">
  $dark_gray:#889aa4;
  $light_gray:#eeeeee;
  $block-height: 52px;

  .login-container {
    position: fixed;
    height: 100%;
    width: 100%;
    background-color: #2d3a4b;

    .el-form-item {
      border: 1px solid hsla(0, 0%, 100%, 0.1);
      border-radius: 5px;
      color: #454545;
      background: rgba(0, 0, 0, 0.1);
    }
    .el-input {
      input {
        -webkit-appearance: none;
        height: $block-height;
        border: 0;
        color: $light_gray;
        background-color: transparent;

        &:-webkit-autofill {
          -webkit-box-shadow: 0 0 0 $block-height #2d3a4b inset !important;
          -webkit-text-fill-color: #ffffff !important;
        }
      }
      &--prefix input {
        padding-left: 45px;
      }
      &--suffix input {
        padding-right: 45px;
      }
      &__prefix {
        left: 15px;
        line-height: $block-height;
      }
      &__suffix {
        right: 10px;
        line-height: $block-height;
      }
    }
    .login-form {
      position: absolute;
      left: 0;
      right: 0;
      width: 520px;
      max-width: 100%;
      padding: 35px 35px 15px 35px;
      margin: 120px auto;
    }
    .title {
      margin: 0px auto 40px auto;
      color: $light_gray;
      font-size: 26px;
      font-weight: bold;
      text-align: center;
    }
  }
</style>
