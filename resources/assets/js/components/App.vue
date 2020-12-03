<template>
  <div>
    <shop></shop>
  </div>
</template>

<script>
import shop from "./Shop";
import wxApi from "../share.js";
import store from "../store";
export default {
  components: {
    shop
  },
  data(){
    return{
      isPC:false
    }
  },
  created() {
    if (/Android|webOS|iPhone|iPod|BlackBerry/i.test(navigator.userAgent)) {
      console.log("手机");

    } else {
      console.log("PC");
    }
  }
};
</script>

<style scoped>
</style>