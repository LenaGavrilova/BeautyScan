<template>
  <Header />
  <div id="app">
    <router-view></router-view>
  </div>
</template>

<script>
import Header from './components/Header-page.vue';
export default {
  name: 'App',
  components: {
    Header,
  },
};
</script>

<style>
#app {
  font-family: Avenir, Helvetica, Arial, sans-serif;
  text-align: center;
  color: #2c3e50;
}
</style>

