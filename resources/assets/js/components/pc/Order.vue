<template>
  <div class="orderBox">
    <div class="orderTop box_cqh">
      <div class="orderNum">订单：{{item.no}}</div>
      <div class="orderStatus">{{item.type==1?getRecoverOrderStatus(item):getSaleOrderStatus(item)}}</div>
    </div>
    <div class="books">
      <div class="bookCover" v-for="book in item.items" :key="book.id" v-if="item.items.length>0">
        <img :src="book.book.cover_replace" />
      </div>
      <div class="bookCover" v-for="book in item.items" :key="book.id" v-else>
        <img :src="book.book.cover_replace" />
      </div>
    </div>
    <div class="orderDate box_cqh">
      <div class="date">
        <span class="num" v-if="item.items.length>0">共{{item.items.length}}件商品</span>
        <span class="num" v-else>包含多个订单</span>
        <span class="date">{{createdAt(item)}}</span>
      </div>
      <router-link class="btn" :to="`/pc/sale_order/${item.no}`" tag="div" v-if="item.type==2">订单详情</router-link>
      <router-link class="btn" :to="`/pc/recover_order/${item.no}`" tag="div" v-if="item.type==1">订单详情</router-link>
    </div>
  </div>
</template>

<script>
export default {
  props: ["item"],
  methods: {
    createdAt: function(order) {
      return dayjs(order.created_at).format("YYYY-MM-DD HH:mm");
    },
    // 卖书
    getRecoverOrderStatus: function(o) {
      var desc = "";
      if (o.closed === true) {
        desc = "已关闭";
      } else {
        switch (parseInt(o.recover_status)) {
          case -1:
            desc = "用户取消";
            break;
          case 10:
            desc = "下单成功";
            break;
          case 20:
            desc = "线上审核";
            break;
          case 30:
            desc = "快递上门";
            break;
          case 40:
            desc = "快递上门";
            break;
          case 50:
            desc = "回流鱼审核打款中";
            break;
          case 60:
            desc = "回流鱼审核打款中";
            break;
          case 70:
            desc = "订单完成";
            break;
          default:
            desc = "--::--";
            break;
        }
      }
      return desc;
    },
    // 买书
    getSaleOrderStatus: function(o) {
      var desc = "";
      if (o.closed === true) {
        desc = "已关闭";
      } else {
        switch (parseInt(o.sale_status)) {
          case -1:
            desc = "用户取消";
            break;
          case 10:
            desc = "待支付";
            break;
          case 20:
            desc = "下单成功";
            break;
          case 30:
            desc = "已出库";
            break;
          case 35:
            desc = "快递打包";
            break;
          case 40:
            desc = "已发货";
            break;
          case 70:
            desc = "订单完成";
            break;
          default:
            desc = "--::--";
            break;
        }
      }
      return desc;
    }
  },
  computed:{
    Multiple(){

    }
  }
};
</script>

<style lang='scss' scoped>
.orderBox {
  width: 100%;
  padding: 16px 20px;
  box-sizing: border-box;
  background: rgba(255, 255, 255, 1);
  border-radius: 10px;
  margin-bottom: 9px;
  .orderTop {
    width: 100%;
    .orderNum {
      font-size: 13px;
      font-family: PingFang-SC;
      font-weight: bold;
      color: rgba(51, 51, 51, 1);
    }
    .orderStatus {
      font-size: 13px;
      font-family: PingFang-SC;
      color: rgba(234, 106, 106, 1);
    }
  }
  .books {
    width: 100%;
    display: flex;
    flex-wrap: wrap;
    margin: 14px 0;
    .bookCover {
      min-width: 14%;
      max-width: 14%;
      font-size: 0;
      img {
        width: 44px;
        height: 63px;
        border-right: 1px solid #ffffff;
        border-bottom: 1px solid #ffffff;
        object-fit: cover;
      }
    }
  }
  .orderDate {
    margin-bottom: 12px;
    .date {
      margin-left: 5px;
      font-size: 12px;
      font-family: PingFang-SC;
      color: rgba(153, 153, 153, 1);
      line-height: 17px;
    }
    .btn {
      width: 70px;
      height: 26px;
      line-height: 26px;
      border-radius: 15px;
      font-size:12px;
      font-family:PingFang-SC;
      border: 1px solid rgba(71, 180, 222, 1);
      text-align: center;
      color: #47B4DE;
    }
  }
  .orderDetail {
    display: flex;
    justify-content: flex-end;
  }
}
</style>