<template>
  <label
    :class="{ 'card-image--empty': !hasImage }"
    :style="{ backgroundImage: hasImage && 'url(' + (imageUrl || image) + ')' }"
    class="card-image">
    <i class="iconfont icon-add-circle" />
    上传您的收款二维码
    <input ref="imageInput" type="file" accept="image/jpeg, image/png, image/gif" @change="handleChange" />
  </label>
</template>

<script>
export default {
  name: 'ImageChoose',
  props: {
    image: String
  },

  data () {
    return {
      imageFile: null
    }
  },
  computed: {
    imageUrl () {
      return this.imageFile && URL.createObjectURL(this.imageFile)
    },
    hasImage () {
      return this.imageFile || this.image
    }
  },

  beforeDestroy () {
    this.revokeImageUrl()
  },

  methods: {
    checkImage (imgInput) {
      // 未选择图片或类型不正确
      if (!imgInput.value || !imgInput.files.length) return false
      // mark: IE8-不支持this.files属性，IE9-不支持Blob URLs、设置this.value=''
      // Blob URLs: window.URL.createObjectURL()
      const imageFile = imgInput.files[0]
      // 图片类型检查
      if (!imageFile.type || imgInput.accept.indexOf(imageFile.type) === -1) {
        return void this.$mToast('请选择有效图片类型')
      }
      // 文件大小检查
      if (imageFile.size > 5 * 1024 * 1024) {
        return void this.$mToast('允许上传图片最大尺寸：5M')
      }
      return true
    },
    handleChange () {
      this.revokeImageUrl()

      const imgInput = this.$refs.imageInput
      if (this.checkImage(imgInput)) {
        this.imageFile = imgInput.files[0]
      }
      // 清除选择图片
      imgInput.value = ''

      this.$emit('change', this.imageFile)
    },
    revokeImageUrl () {
      if (this.imageFile) {
        URL.revokeObjectURL(this.imageUrl)
        this.imageFile = null
      }
    }
  }
}
</script>
