<template>
  <div>
    <div class="foot" :style="footStyle">
      <div class="foot-a" @click="iconClick(0)">
        <div class="foot-item" :class="{'foot-on':Number(index)===0}">
          <!-- <van-icon name="shop-o" size="24px"/> -->
          <div class="tabIcon">
            <img :src='Number(index)===0?"/images/image/shop2.png":"/images/image/shop.png"' alt="">
          </div>
          <p class="foot-item-desc">商店</p>
        </div>
      </div>
      <div class="foot-a" @click="iconClick(1)">
        <div class="foot-item" :class="{'foot-on':Number(index)===1}">
          <!-- <van-icon name="scan" size="24px"/> -->
          <div class="tabIcon">
            <img :src='Number(index)===1?"/images/image/scan2.png":"/images/image/scan.png"' alt />
          </div>
          <p class="foot-item-desc">卖书</p>
        </div>
      </div>
      <div class="foot-a" @click="iconClick(2)">
        <div class="foot-item" :class="{'foot-on':Number(index)===2}">
          <!-- <van-icon name="bag-o" v-if="items.length===0" size="24px"/>
          <van-icon name="bag-o" :info="items.length" size="24px" v-else/>-->
          <div class="tabIcon">
            <img :src='Number(index)===2?"/images/image/cart2.png":"/images/image/cart.png"' alt />
          </div>
          <p class="foot-item-desc">购物袋</p>
          <div class="foot-cart-item-count" v-if="cartCounts.length>0">{{cartCounts.length}}</div>
        </div>
      </div>
      <div class="foot-a" @click="iconClick(3)">
        <div class="foot-item" :class="{'foot-on':Number(index)===3}">
          <!-- <van-icon name="contact" size="24px"/> -->
          <div class="tabIcon">
            <img :src='Number(index)===3?"/images/image/mine2.png":"/images/image/mine.png"' alt />
          </div>
          <p class="foot-item-desc">我的</p>
          <div class="dian" v-show="showRemind"></div>
        </div>
      </div>
    </div>

    <van-popup v-model="dialogVisible" :close-on-click-overlay="true">
      <div style="text-align: center">
        <img src="/images/qrcode.jpg" :width="250+'px'" alt />
      </div>
    </van-popup>
  </div>
</template>

<script>
import { mapGetter, mapState, mapActions } from "vuex";
import wx from "weixin-js-sdk";
export default {
  data() {
    return {
      miniprogramEnv: false,
      device: "",
      dialogVisible: false,
      screenWidth: 0,
      screenHeight: 0
    };
  },
  props: ["index"],
  computed: {
    footStyle: function() {
      if (
        this.miniprogramEnv &&
        (this.device === "iphonex" ||
          this.device === "iphonexmax" ||
          this.device === "iphonexr")
      ) {
        return {
          paddingBottom: "32px"
        };
      }
    },
    ...mapState({
      cartCounts:state => state.cart.cart_counts,
      items: state => state.cart.items,
      user: state => state.user.user,
      showRemind: state => state.my.showRemind
    })
  },
  created: function() {
    this.screenWidth =
      window.innerWidth ||
      document.documentElement.clientWidth ||
      document.body.clientWidth;
    this.screenHeight =
      window.innerHeight ||
      document.documentElement.clientHeight ||
      document.body.clientHeight;
    this.device = window.localStorage.getItem("device");
    var _this = this;
    wx.miniProgram.getEnv(function(res) {
      console.log("miniprogram env? " + res.miniprogram);
      _this.miniprogramEnv = res.miniprogram;
    });
    this.$store.dispatch("user/getUser").then(res => {
      console.log('bottom',this.user.email)
      this.getShowRemind(this.user.email)
    });
    
  },
  mounted: function() {
    console.log("bottom bar index=" + this.index);
    // this.$store.dispatch("cart/items");
    // this.$store.dispatch("cart/reminders");
    this.$store.dispatch("cart/cartItems");
  },
  methods: {
    iconClick: function(newIndex) {
      if (
        (newIndex > 0 && (this.user === "" || this.user.length === 0)) ||
        (this.user && this.user.subscribe === 0)
      ) {
        this.dialogVisible = true;
      } else {
        switch (newIndex) {
          case 0:
            this.$router.push("/pc/shop");
            break;
          case 1:
            this.$router.push("/pc/scan");
            break;
          case 2:
            this.$router.push("/pc/cart");
            break;
          case 3:
            this.$router.push("/pc/my");
            break;
          default:
            console.log("no this route");
            break;
        }
      }
    },
    ...mapActions("my", ["getShowRemind", "clearRemind"])
  }
};
</script>

<style scoped>
a {
  text-decoration: none;
}
a:visited {
  text-decoration: none;
}
a:active {
  text-decoration: none;
}
a:link {
  text-decoration: none;
}
a:hover {
  text-decoration: none;
}
.foot {
  position: fixed;
  left: 0;
  bottom: 0;
  background: #fff;
  display: flex;
  flex-direction: row;
  justify-content: space-around;
  align-items: center;
  height: 50px;
  width: 100%;
  border-top: 0.5px solid #eee;
  padding-bottom: 2px;
  z-index: 100;
}
.foot-a {
  color: #333333;
}
.foot-item {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  margin-bottom: -20px;
  color: #333333;
}
.foot-item .tabIcon {
  font-size: 0;
}
.foot-item .tabIcon img {
  width: 24px;
  height: 24px;
}
.foot-item-desc {
  font-size: 11px;
  margin-top: 3px;
}
.foot-on {
  color: #41b0dc;
}
.foot-cart-item-count {
  position: absolute;
  top: -6px;
  right: -12px;
  color: white;
  background-color: #ff4848;
  font-size: 12px;
  height: 20px;
  line-height: 20px;
  min-width: 20px;
  border-radius: 13px;
  display: flex;
  flex-direction: row;
  justify-content: center;
  align-items: center;
}
.dian {
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: red;
  position: absolute;
  top: 0px;
  right: -10px;
}
.van-popup {
  border-radius: 6px;
}
</style>