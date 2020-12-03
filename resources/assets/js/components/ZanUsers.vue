<template>
  <div class="zanUsers">
    <div class="list box_cqh" v-for="item in list" :key='item'>
      <div class="user">
        <img
          :src="item.user.avatar"
          alt
        />
        <span>{{item.user.nickname}}</span>
      </div>
      <div class="more">
        <van-icon name="arrow" color="#aaaaaa" />
      </div>
    </div>
  </div>
</template>

<script>
export default {
  data(){
    return{
      list:[]
    }
  },
  created(){
    let that =this;
    this.wxApi.wxConfig('','');
    console.log(that.$route.query)
    that.list=JSON.parse(that.$route.query.list);
  }
};
</script>

<style scoped lang="scss">
.zanUsers {
  width: 100%;
  .list {
    width: 100%;
    padding: 15px 12px;
    box-sizing: border-box;
    border-bottom: 1px solid #F9F9F9;
    .user{
        color: #333333;
        font-size: 14px;
        img{
            width: 30px;
            height: 30px;
            border-radius: 50%;
            margin-right: 10px;
            vertical-align: middle;
        }
    }
  }
}
</style>