<template>
  <div
    :class="{ active: isActively }"
    class="modal-overlay container"
    @click.self="handleClose">
    <div
      :class="{ active: isActively }"
      class="modal-panel pnl-module flex-column">
      <button
        :disabled="disabled"
        class="modal-close"
        @click="handleClose">
        <i class="iconfont icon-sign-fork" />
      </button>
      <div class="pnl-module-header flex-fixed" />
      <div
        :class="{ scrollbox: !custom }"
        class="pnl-module-content flex-column flex-grown">
        <slot />
      </div>
      <div class="pnl-module-footer flex-fixed" />
    </div>
  </div>
</template>

<script>
export default {
  name: 'ModalPopup',
  props: {
    custom: {
      type: Boolean,
      default: false
    },
    disabled: {
      type: null,
      default: false
    }
  },

  data () {
    return {
      isActively: true
    }
  },

  methods: {
    handleClose () {
      if (!this.disabled) {
        this.isActively = false
        setTimeout(() => {
          this.$emit('close', false)
        }, 280)
      }
    }
  }
}
</script>
