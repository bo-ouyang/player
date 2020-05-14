<template>
  <mt-popup
    v-model="showPicker"
    class="lst-menu-popup"
    position="top"
    @click.native="$emit('input', false)"
    @input="$emit('input', $event)">
    <ul class="lst-menu">
      <li
        v-for="(menu, index) of list"
        :key="index"
        :class="{ 'bod-t-1px': index > 0 }"
        class="lst-menu-item"
        @click="$emit('change', menu.value)">
        {{ $t(menu.title) }}
      </li>
    </ul>
  </mt-popup>
</template>

<script>
export default {
  name: 'MenuPicker',
  props: {
    value: Boolean,
    list: Array
  },

  data () {
    return {
      showPicker: this.value
    }
  },

  watch: {
    value (val) {
      this.showPicker = val
    }
  }
}
</script>
