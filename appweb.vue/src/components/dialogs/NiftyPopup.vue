<template>
  <div
    :class="{ active: isActively }"
    class="modal-overlay container"
    @click.self="handleClose">
    <div
      :class="[ isActively && 'active', modalClass ]"
      class="modal-panel">
      <!-- <button
        v-if="!custom"
        :disabled="disabled"
        class="modal-close"
        @click="handleClose">
        <i class="iconfont icon-sign-fork" />
      </button> -->
      <slot />
    </div>
  </div>
</template>

<script>
export default {
  name: 'NiftyPopup',
  props: {
    // custom: {
    //   type: Boolean,
    //   default: false
    // },
    disabled: {
      type: null,
      default: false
    },
    modalClass: String
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
