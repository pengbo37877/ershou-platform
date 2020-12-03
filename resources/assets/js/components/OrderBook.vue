<template>
  <!-- <div class="order-book">
        <router-link :to="`/wechat/book/${item.book.isbn}`" class="order-book-cover" style="width: 40px;min-height: 60px;max-height: 70px;">
            <img :src="item.book.cover_replace" style="width: 40px;max-height: 70px;"/>
        </router-link>
        <div class="order-book-detail" :style="detailWidth">
            <div class="order-book-name">{{item.book.name}}</div>
            <div class="order-book-author" v-if="item.book.author">{{item.book.author.trimLeft()}}</div>
            <div class="order-book-sold-price">￥{{item.book_sku.price}}</div>
            <div class="order-sku-title">{{item.book_sku.title}}</div>
        </div>
  </div>-->
  <div class="orderItem">
    <div class="itemTop box_cqh">
      <div class="bookNum">共{{order.items.length}}本</div>
      <div class="bookStatus">{{orderSaleStatusDesc}}</div>
    </div>
    <div class="itemBooks">
      <div class="books box_cqh" v-for="item in order.items" :key="item.id">
        <div class="booksLeft box">
          <div class="bookCover">
            <img :src="item.book.cover_replace" alt />
          </div>
          <div class="bookInfo">
            <div class="bookName">{{item.book.name}}</div>
            <div class="bookAuthor">{{item.book.author.trimLeft()}}</div>
            <div class="level">{{item.book_sku.title}}</div>
          </div>
        </div>
        <div class="booksRight">
          <div class="bookPrice">￥{{item.book_sku.price}}</div>
          <div class="bookNum">x1</div>
        </div>
      </div>
    </div>
    <div class="itemBottom">
      <div class="itemFreight box_cqh">
        <div>运费</div>
        <div>￥{{order.ship_price}}</div>
      </div>
      <div class="itemPrice box_cqh">
        <div>总价</div>
        <div>￥{{order.total_amount}}</div>
      </div>
    </div>
    <div class="itemLogistics">
      <router-link :to="`/wechat/sale_order_ship/${order.no}`" class="btn" tag="div">查看物流</router-link>
    </div>
  </div>
</template>

<script>
export default {
  props: ["item", "screenWidth", "order"],
  computed: {
    detailWidth: function() {
      return {
        width: this.screenWidth - 90 + "px"
      };
    },
    orderSaleStatusDesc() {
      var desc = "";
      if (this.order.closed) {
        return "已关闭";
      }
      switch (this.order.sale_status) {
        case -1:
          desc = "已取消";
          break;
        case 10:
          desc = "等待支付";
          break;
        case 20:
          desc = "已支付";
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
      return desc;
    }
  }
};
</script>
<style scoped lang="scss">
.orderItem {
  width: 100%;
  padding: 11px 15px 14px;
  box-sizing: border-box;
  margin-bottom: 8px;
  background: #ffffff;
  .itemTop {
    width: 100%;
    .bookNum {
      font-size: 16px;
      font-family: PingFang-SC;
      font-weight: bold;
      color: rgba(51, 51, 51, 1);
    }
    .bookStatus {
      font-size: 12px;
      font-family: PingFangSC;
      font-weight: 400;
      color: rgba(237, 151, 79, 1);
    }
  }
  .itemBooks {
    width: 100%;
    border-bottom: 1px solid #ededed;
    padding: 7px 0 9px 0px;
    box-sizing: border-box;
    .books {
      margin-bottom: 5px;
      .booksLeft {
        .bookCover {
          margin-right: 11px;
          img {
            width: 40px;
            height: 59px;
            object-fit: cover;
          }
        }
        .bookInfo {
          .bookName {
            font-size: 13px;
            font-family: PingFangSC-Regular, PingFangSC;
            font-weight: 400;
            color: rgba(51, 51, 51, 1);
            line-height: 18px;
            margin-bottom: 2px;
            text-overflow: ellipsis;
            white-space: nowrap;
            overflow: hidden;
            max-width: 200px;
          }
          .bookAuthor {
            font-size: 12px;
            font-family: PingFangSC-Regular, PingFangSC;
            font-weight: 400;
            color: rgba(153, 153, 153, 1);
            line-height: 17px;
            margin-bottom: 4px;
          }
          .level {
            font-size: 12px;
            font-family: PingFangSC-Regular, PingFangSC;
            font-weight: 400;
            color: rgba(243, 110, 110, 1);
            line-height: 17px;
          }
        }
      }
      .booksRight {
        .bookPrice {
          font-size: 12px;
          font-family: PingFang-SC;
          font-weight: bold;
          color: rgba(51, 51, 51, 1);
          line-height: 17px;
        }
        .bookNum {
          font-size: 12px;
          font-family: PingFang-SC;
          color: rgba(102, 102, 102, 1);
          line-height: 17px;
          text-align: right;
        }
      }
    }
    .books:last-child {
      margin-bottom: 0;
    }
  }
  .itemBottom {
    font-size: 12px;
    font-family: PingFang-SC;
    color: rgba(102, 102, 102, 1);
    line-height: 17px;
    margin: 9px 0 17px 0;
    .itemFreight {
      margin-bottom: 6px;
    }
  }
  .itemLogistics {
    display: flex;
    justify-content: flex-end;
    .btn {
      width: 70px;
      height: 26px;
      line-height: 26px;
      border-radius: 15px;
      border: 1px solid rgba(153, 153, 153, 1);
      text-align: center;
      font-size: 12px;
      font-family: PingFang-SC;
      color: rgba(102, 102, 102, 1);
    }
  }
}
</style>
<style scoped>
.order-book {
  position: relative;
  border-bottom: 0.5px solid #ebedf0;
  display: flex;
  flex-direction: row;
}
.order-book-cover {
  padding: 10px 10px 10px 20px;
}
.order-book-detail {
  position: relative;
}
.order-book-name {
  font-size: 14px;
  color: #3d404a;
  text-overflow: ellipsis;
  white-space: nowrap;
  overflow: hidden;
  position: absolute;
  width: 100%;
  left: 0px;
  top: 10px;
}
.order-book-author {
  font-size: 12px;
  color: #888888;
  text-overflow: ellipsis;
  white-space: nowrap;
  overflow: hidden;
  position: absolute;
  width: 100%;
  left: 0px;
  top: 30px;
}
.order-sku-title {
  font-size: 12px;
  color: #ff4848;
  opacity: 0.7;
  position: absolute;
  width: 100%;
  left: 0px;
  bottom: 10px;
}
.order-book-sold-price {
  font-size: 14px;
  color: #3d404a;
  position: absolute;
  width: 100%;
  left: 0px;
  bottom: 10px;
  text-align: right;
}
</style>