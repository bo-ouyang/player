<template>
  <div :class="{ empty: !hasImage }" :style="{ backgroundImage: hasImage && 'url(' + (imageUrl || defaultImage) + ')' }">
    <input ref="imageInput" type="file" accept="image/jpeg, image/png, image/gif" @change="handleChange">
  </div>
</template>

<script>
function checkImage(imageInput) {
  // 未选择图片或类型不正确
  if (!imageInput.value || !imageInput.files.length) return false
  // mark: IE8-不支持this.files属性，IE9-不支持Blob URLs、设置this.value=''
  // Blob URLs: window.URL.createObjectURL()
  const imageFile = imageInput.files[0]
  // 图片类型检查
  if (!imageFile.type || imageInput.accept.indexOf(imageFile.type) === -1) {
    this.$message.warning('请选择有效图片类型')
    return false
  }
  // 文件大小检查
  if (imageFile.size > 5 * 1024 * 1024) {
    this.$message.warning('允许上传图片最大尺寸：5M')
    return false
  }
  return true
}

export default {
  name: 'ImageChoose',
  props: {
    value: {
      type: null,
      default: ''
    },
    defaultImage: {
      type: String,
      default: ''
    }
  },
  data() {
    return {
      imageFile: undefined
    }
  },
  computed: {
    imageUrl() {
      return this.imageFile && URL.createObjectURL(this.imageFile)
    },
    hasImage() {
      return this.imageFile || this.defaultImage
    }
  },
  watch: {
    value(imageFile) {
      this.imageFile = imageFile
    }
  },
  beforeDestroy() {
    this.revokeImageUrl()
  },
  methods: {
    handleChange() {
      this.revokeImageUrl()

      const imageInput = this.$refs.imageInput
      if (checkImage(imageInput)) {
        this.imageFile = imageInput.files[0]
      }
      // 清除选择图片
      imageInput.value = ''

      this.$emit('input', this.imageFile)
    },
    revokeImageUrl() {
      if (this.imageFile) {
        URL.revokeObjectURL(this.imageUrl)
        this.imageFile = ''
      }
    }
  }
}
</script>
