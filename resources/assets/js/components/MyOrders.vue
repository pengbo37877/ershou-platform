<template>
  <div class="myOrders">
    <loading :loading="loading"></loading>
    <van-tabs sticky v-model="active"  background='#eeeeee' line-width='60' line-height='1' color='#44B3DD' title-inactive-color='#333333' title-active-color='#47B4DE' swipeable>
      <van-tab title='买书订单'>
          <div class="list">
              <Order v-for="order in orders" :key="order.id" :item='order' v-if='order.type==2'></Order>
              <div class="noOrder" v-show="listType2.length==0">
                还没有买书相关的订单
              </div>
          </div>
      </van-tab>
      <van-tab title='卖书订单'>
          <div class="list">
              <Order v-for="order in orders" :key="order.id" :item='order' v-if='order.type==1'></Order>
              <div class="noOrder" v-show="listType1.length==0">
                还没有卖书相关的订单
              </div>
          </div>
      </van-tab>
    </van-tabs>
  </div>
</template>
<style scoped lang='scss'>
.myOrders{
    width: 100%;
    min-height: 100vh;
    background: #f4f4f4;
    .list{
        width: 100%;
        padding: 20px 15px;
        box-sizing: border-box;
        .noOrder{
          text-align: center;
          font-size: 14px;
          font-family:PingFang-SC;
          color: #666666;
        }
    }
}

</style>
<script>
import Loading from "./Loading";
import { mapState, mapGetters, mapActions } from "vuex";
import Order from './Order'
export default {
  data() {
    return {
      loading: false,
      active:0,
      listType1:[],
      listType2:[]
    };
  },
  computed: {
    ...mapState({
      orders: state => state.my.orders
    })
  },
  created: function() {
    this.loading = true;
    this.wxApi.wxConfig("", "");
    this.$store.dispatch("my/getMyOrders").then(res => {
      let list =res.data;
      let list1 =[];
      let list2 =[];
      for(var i in list){
        if(list[i].type==1){
          list1.push(list[i])
        }else{
          list2.push(list[i])
        }
      }
      this.listType1 =list1;
      this.listType2 =list2;
      console.log(this.listType1.length,this.listType2.length)
      this.loading = false;
    });
  },
  mounted: function() {},
  methods: {
    createdAt: function(order) {
      return dayjs(order.created_at).format("YYYY-MM-DD HH:mm");
    },
    getRecoverOrderStatus: function(o) {
      var desc = "";
      if (o.closed === true) {
        desc = "已关闭";
      } else {
        switch (o.recover_status) {
          case -1:
            desc = "已取消";
            break;
          case 10:
            desc = "已下单";
            break;
          case 20:
            desc = "回流鱼线上审核";
            break;
          case 30:
            desc = "安排快递上门";
            break;
          case 40:
            desc = "快递已取书";
            break;
          case 50:
            desc = "回流鱼收货";
            break;
          case 60:
            desc = "回流鱼打款";
            break;
          case 70:
            desc = "已完成";
            break;
          default:
            desc = "--::--";
            break;
        }
      }
      return desc;
    },
    getSaleOrderStatus: function(o) {
      var desc = "";
      if (o.closed === true) {
        desc = "已关闭";
      } else {
        switch (o.sale_status) {
          case -1:
            desc = "已取消";
            break;
          case 10:
            desc = "待支付";
            break;
          case 20:
            desc = "已付款";
            break;
          case 30:
            desc = "已出库";
            break;
          case 35:
            desc = "已揽件";
            break;
          case 40:
            desc = "已发货";
            break;
          case 70:
            desc = "已签收";
            break;
          default:
            desc = "--::--";
            break;
        }
      }
      return desc;
    }
  },
  components: {
    Loading,
    Order
  }
};
</script>
