<template>
  <el-scrollbar wrap-class="scrollbar-wrapper">
    <el-menu
      :show-timeout="200"
      :default-active="$route.path"
      :collapse="isCollapse"
      mode="vertical"
      background-color="#304156"
      text-color="#bfcbd9"
      active-text-color="#409EFF"
    >
      <sidebar-item v-for="route in routes" :key="route.path" :item="route" :base-path="route.path"/>
    </el-menu>
  </el-scrollbar>
</template>

<script>
import { mapGetters } from 'vuex'
import SidebarItem from './SidebarItem'

export default {
  components: { SidebarItem },
  computed: {
    ...mapGetters([
      'sidebar'
    ]),
    routes() {
      const list = []
      const admin = this.$store.state.user.admin || {}
      this.$router.options.routes.forEach(route => {
        if (this.verifyRouteRole(route.meta, admin)) {
          if (!route.children) {
            list.push(route)
          } else {
            const child = route.children.filter(route => this.verifyRouteRole(route.meta, admin))
            if (child.length) {
              list.push({
                ...route,
                children: child
              })
            }
          }
        }
      })

      return list
    },
    isCollapse() {
      return !this.sidebar.opened
    }
  },

  methods: {
    verifyRouteRole(meta, admin) {
      return !meta || !meta.role || meta.role === admin.is_super
    }
  }
}
</script>
