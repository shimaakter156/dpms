<template>
  <div id="wrapper">

    <Sidebar/>
    <div class="content-page">
      <Navbar/>
      <router-view :key="$route.path"></router-view>
      <Footer/>
    </div>
  </div>
</template>
<script>
import Navbar from './Navbar'
import Sidebar from './Sidebar'
import Footer from './Footer'

export default {
  components: {
    Navbar,
    Sidebar,
    Footer
  }
}
</script>