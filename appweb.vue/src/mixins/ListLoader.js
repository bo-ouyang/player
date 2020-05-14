// import ListLoader from '@/components/widgets/ListLoader'

export default {
  // components: {
  //   ListLoader
  // },

  data () {
    return {
      isLoading: true,
      loadDone: false,
      lastScrollTop: 0
    }
  },

  methods: {
    handleScroll (elem, func) {
      console.log(this.isLoading)
      console.log(this.loadDone)
      console.log(elem.scrollHeight - elem.clientHeight - elem.scrollTop)
      if (elem.scrollHeight - elem.clientHeight - elem.scrollTop <= 0 && this.isLoading) {
        this.isLoading = false
        this.loadDone = true
        func()
      }
    }
  }
}
