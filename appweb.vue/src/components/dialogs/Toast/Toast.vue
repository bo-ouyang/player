<template>
  <div
    v-if="showDialog"
    :class="{ active: isActively }"
    class="modal-toast container">
    {{ message }}
  </div>
</template>

<script>
export default {
  name: 'Toast',
  props: {
    value: {
      type: Boolean,
      default: false
    },
    message: {
      type: String,
      required: true
    }
  },

  data () {
    return {
      showDialog: this.value,
      isActively: true,
      clearTimer: null
    }
  },

  watch: {
    value (val) {
      if (val) {
        if (!this.isActively) {
          clearTimeout(this.clearTimer)
          this.showDialog = this.isActively = true
        }
      } else {
        this.isActively = false
        this.clearTimer = setTimeout(() => {
          this.showDialog = false
        }, 280)
      }
    }
  }
}
</script>
