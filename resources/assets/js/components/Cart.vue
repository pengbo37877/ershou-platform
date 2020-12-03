<template>
<div class="cartBox">
  <van-tabs v-model="active" @change="onChange" sticky swipeable>
    <van-tab title="全部">
      <loading :loading="loading"></loading>
      <div class="cart-empty" v-if="items.length===0 && !loading">
        <img src="/images/empty.png" :style="imgStyle" style="opacity: .6" alt />
        <br />去商店添加喜欢的书籍吧
        <br />
      </div>
      <cart-selling-item
        :item="item"
        :screen-width="screenWidth"
        v-for="item in sellingItems"
        :key="item.id"
        v-if="sellingItems.length>0"
      ></cart-selling-item>
      <div class="no-sku-title" v-if="soldItems.length>0">以下暂时无货</div>
      <cart-sold-item
        :item="item"
        :screen-width="screenWidth"
        v-for="item in soldItems"
        :key="item.id"
      ></cart-sold-item>
      <div class="no-sku-title" v-if="recommends.length>0">推荐购买</div>
      <div class="paddingBottom">
        <cart-recommend-book
          :book="book"
          :screen-width="screenWidth"
          v-for="book in recommends"
          :key="book.id"
        ></cart-recommend-book>
      </div>
      <div style="width:100%;height:78px"></div>
      <router-link tag="div" to="/wechat/myCoupons?from=newCoupon" class="cart-share">
        邀请好友，得20元买书抵扣券
        <van-icon name="arrow" />
      </router-link>
      <div class="cart-bottom-bar" :style="{width: screenWidth+'px'}">
        <div
          class="cart-select-circle"
          @click="chooseAllSellingItems"
          v-show="sellingItems.length>0"
        >
          <div class="cart-all-selected" v-show="allSelected">
            <input type="checkbox" v-model="allSelected" />
            <van-icon name="certificate" style="padding: 5px" />
          </div>
          <div style="padding-left: 5px;" v-show="!allSelected">
            <input type="checkbox" v-model="allSelected" />
            <van-icon name="circle" style="padding: 5px" />
          </div>
        </div>
        <div style="font-size:12px;color: #3D404A;" v-show="sellingItems.length>0">全选</div>
        <!--<div class="cart-selected-count">共本</div>-->
        <div class="cart-selected-price" v-if="sellingItems.length>0">{{selectedPrice}}</div>
        <div
          class="cart-selected-price"
          style="color: #aaaaaa;text-align: center;"
          v-else
        >{{selectedPrice}}</div>
        <div class="cart-selected-desc" v-show="sellingItems.length>0">
          <div class="cart-selected-express-fee" v-if="selectedPrice<99">
            快递费
            <span style="color: #ff4848">￥5元</span>
          </div>
          <div class="cart-selected-express-fee" v-if="selectedPrice<99">
            再买
            <span style="color: #ff4848">￥{{ Number(99-selectedPrice).toFixed(2) }}</span>包邮
          </div>
          <div class="cart-selected-express-fee" v-if="selectedPrice>=99">
            <span style="color: #227E2C">已包邮</span>
          </div>
        </div>
        <router-link
          to="/wechat/sale_invoice"
          class="cart-selected-pay-btn"
          v-if="selectedItems.length>0"
        >结算({{selectedItems.length}})</router-link>
        <div class="cart-selected-pay-btn cart-selected-pay-btn-disable" v-else>结算</div>
      </div>
    </van-tab>
    <van-tab title="到货提醒">
      <loading :loading="loading"></loading>
      <div class="reminder-empty" v-if="reminders.length===0">
        遇到想买的书无货可以标记到货提醒
        <br />有货了会通知你
      </div>
      <div class="no-sku-title" v-if="reminders.length>0">到货提醒 {{reminders.length}}</div>
      <cart-reminder
        :reminder="reminder"
        :screen-width="screenWidth"
        v-for="reminder in reminders"
        :key="reminder.id"
        v-if="reminders.length>0"
      ></cart-reminder>
      <div style="width:100%;height:50px"></div>
    </van-tab>
    <bottom-bar2 index="2"></bottom-bar2>
  </van-tabs>
</div>
  
</template>
<style scoped>
.cartBox{
  /* max-width: 600px; */
}
.cart-empty {
  text-align: center;
  font-size: 12px;
  color: #aaaaaa;
  margin-top: 60px;
}
.no-sku-title {
  font-size: 13px;
  padding: 10px 15px;
  border-bottom: 1px solid #f5f5f5;
  color: #555555;
}
.reminder-empty {
  font-size: 12px;
  color: #aaaaaa;
  width: 100%;
  height: 300px;
  display: flex;
  flex-direction: row;
  justify-content: center;
  align-items: center;
  text-align: center;
}
.cart-share {
  position: fixed;
  left: 0;
  bottom: 100px;
  height: 28px;
  line-height: 28px;
  color: #ff1111;
  background-color: #ffe1e1;
  text-align: center;
  width: 100%;
  font-size: 12px;
  border-top: 0.5px solid #ebedf0;
  display: flex;
  flex-direction: row;
  justify-content: center;
  align-items: center;
}
.cart-bottom-bar {
  display: flex;
  flex-direction: row;
  justify-content: space-between;
  align-items: center;
  position: fixed;
  left: 0;
  bottom: 50px;
  height: 50px;
  background-color: #fffcfc;
}
input {
  position: absolute;
  clip: rect(0, 0, 0, 0);
}
.cart-select-circle {
  color: #eee;
  font-size: 22px;
  display: flex;
  flex-direction: row;
  align-items: center;
  padding-left: 5px;
}
.cart-all-selected {
  color: #3d404a;
  padding-left: 5px;
}
.cart-selected-count {
  font-size: 16px;
  color: #4a4a4a;
  padding-left: 20px;
  flex-grow: 1;
}
.cart-selected-price {
  font-size: 20px;
  font-weight: bold;
  color: black;
  flex-grow: 1;
  margin-left: 10px;
  margin-bottom: 3px;
}
.cart-selected-desc {
  display: flex;
  flex-direction: column;
  flex-grow: 10;
  text-align: right;
  margin-right: 10px;
}
.cart-selected-express-fee {
  font-size: 11px;
  font-weight: 300;
  color: #9b9b9b;
}
.cart-selected-pay-btn {
  background-color: #ff6767;
  color: white;
  font-size: 16px;
  text-align: center;
  width: 100px;
  height: 50px;
  line-height: 50px;
}
.cart-selected-pay-btn-disable {
  opacity: 0.3;
}
.paddingBottom {
  padding-bottom: 50px;
}
</style>
<script>
import wx from "weixin-js-sdk";
import { mapGetters, mapState, mapActions } from "vuex";
import Loading from "./Loading";
import CartSellingItem from "./CartSellingItem";
import CartSoldItem from "./CartSoldItem";
import CartSellingReminder from "./CartSellingReminder";
import CartReminder from "./CartReminder";
import CartRecommendBook from "./CartRecommendBook";
import BottomBar2 from "./BottomBar2";
import routes from "../routes";
export default {
  data() {
    return {
      active: 0,
      loading: false,
      screenWidth: 0,
      screenHeight: 0
    };
  },
  created: function() {
    var isbn = this.$route.query.isbn;
    this.$store.dispatch("user/getUser").then(res => {});
    this.wxApi.wxConfig("", "");
    if (isbn) {
      axios.get("/wx-api/get_book_sale_sku?isbn=" + isbn).then(res => {
        if (res.data.code && res.data.code === 500) {
        } else if (res.data.length === 0) {
          this.$router.replace("/wechat/book/" + isbn + "?from=notify");
        }
      });
    }
    if (this.reminders.length === 0) {
      this.$store.dispatch("cart/reminders");
    }
    this.loading = true;
    this.$store.dispatch("cart/items").then(res => {
      this.loading = false;
    });
    if (this.recommends.length === 0) {
      this.$store.dispatch("cart/recommends").then(res => {
        this.loading = false;
      });
    }
  },
  mounted: function() {
    this.screenWidth =
      window.innerWidth ||
      document.documentElement.clientWidth ||
      document.body.clientWidth;
    this.screenHeight =
      window.innerHeight ||
      document.documentElement.clientHeight ||
      document.body.clientHeight;
    var _this = this;
    // 卖出去的状态改一下
    _(this.items).forEach(function(item) {
      if (item.book_sku.status == 4 || item.book_sku.status == 8) {
        _this.changeCartItemSelect({ item, selected: 0 });
      }
    });
    // 微信分享
    // this.wxConfig();
  },
  computed: {
    imgStyle: function() {
      return {
        width: this.screenWidth / 2.5 + "px",
        height: this.screenWidth / 2.5 + "px"
      };
    },
    ...mapState({
      user: state => state.user.user,
      items: state => state.cart.items,
      reminders: state => state.cart.reminders,
      recommends: state => state.cart.recommends
    }),
    ...mapGetters("cart", {
      soldItems: "soldItems",
      sellingItems: "sellingItems",
      sellingReminders: "sellingReminders",
      selectedItems: "selectedItems",
      selectedPrice: "selectedPrice",
      allSelected: "allSelected"
    })
  },
  methods: {
    onClick(index, title) {
      if (index == 0) {
        this.$store.dispatch("cart/items").then(res => {
          this.loading = false;
        });
      }
    },
    onChange(index, title) {
      this.onClick(index, title);
    },
    wxConfig: function() {
      var _this = this;
      var url = window.localStorage.getItem("url");
      axios
        .post("/wx-api/config", {
          url: "cart"
        })
        .then(response => {
          console.log(response.data);
          wx.config(response.data);
          wx.ready(() => {
            console.log("ready");
            wx.onMenuShareAppMessage({
              title: "回流鱼 - 二手循环书店", // 分享标题
              desc: "阅读不孤读", // 分享描述
              link: url + "/wechat/shop", // 分享链接，该链接域名或路径必须与当前页面对应的公众号JS安全域名一致
              imgUrl: url + "/images/logo_main.png", // 分享图标
              type: "", // 分享类型,music、video或link，不填默认为link
              dataUrl: "", // 如果type是music或video，则要提供数据链接，默认为空
              success: function() {
                // 用户点击了分享后执行的回调函数
                console.log("分享成功");
              }
            });
            wx.onMenuShareTimeline({
              title: "回流鱼 - 二手循环书店", // 分享标题
              link: url + "/wechat/shop", // 分享链接，该链接域名或路径必须与当前页面对应的公众号JS安全域名一致
              imgUrl: url + "/images/logo_main.png", // 分享图标
              success: function() {
                // 用户点击了分享后执行的回调函数
                console.log("分享成功");
              }
            });
          });
        });
    },
    chooseAllSellingItems: function() {
      console.log("chooseAllSellingItems");
      var _this = this;
      if (this.allSelected) {
        _(this.sellingItems).forEach(function(item) {
          _this.changeCartItemSelect({ item, selected: 0 });
        });
      } else {
        _(this.sellingItems).forEach(function(item) {
          _this.changeCartItemSelect({ item, selected: 1 });
        });
      }
    },
    ...mapActions("cart", ["changeCartItemSelect"])
  },
  components: {
    Loading,
    CartSellingItem,
    CartSoldItem,
    CartSellingReminder,
    CartReminder,
    CartRecommendBook,
    BottomBar2
  }
};
</script>
