<template>
  <div style="background-color: #efefef">
    <!--<van-notice-bar-->
    <!--text="春节发货调整：1月28日下午6点后~2月10日期间的买书订单，会在2月11日（正月初七）按顺序统一发货。"-->
    <!--left-icon="volume-o"-->
    <!--/>-->
    <loading :loading="loading"></loading>
    <div class="no-user-address" v-if="!latestAddress">
      <router-link to="/pc/address_edit?from=sale_invoice" class="add-address">添加地址</router-link>
    </div>
    <div class="user-address" v-else>
      <div class="user-address-contact">
        <div class="user-address-contact-name">{{latestAddress.contact_name}}</div>
        <div class="user-address-contact-phone">{{latestAddress.contact_phone}}</div>
      </div>
      <div
        class="user-address-detail"
      >{{ latestAddress.province + latestAddress.city + latestAddress.district + latestAddress.address }}</div>
      <div class="user-address-update">
        <router-link
          to="/pc/address_list?from=sale_invoice"
          class="user-address-update-btn"
        >更改地址</router-link>
      </div>
    </div>

    <div class="sale-books">
      <div class="sale-book" v-for="item in selectedItems">
        <div class="book-name">{{item.book.name}}</div>
        <div class="book-sale-price">￥{{item.book_sku.price}}</div>
      </div>
    </div>
    <div class="sale-books">
      <div class="sale-book">
        <div class="book-name">总价</div>
        <div class="book-sale-price">￥{{selectedPrice}}</div>
      </div>
      <div class="sale-book">
        <div class="book-name">
          运费(不参与优惠)
          <router-link to="/pc/express_fee">
            <i class="el-icon-question" style="color: #ff4848; opacity: 0.6"></i>
          </router-link>
        </div>
        <div class="book-sale-price">￥{{ expressFee }}</div>
      </div>
    </div>
    <div style="margin-top: 15px;" v-if="vanSaleCoupons.length>0">
      <!-- 现金券单元格 -->
      <van-coupon-cell :coupons="coupons" :chosen-coupon="chosenCoupon" @click="showList = true" />
      <!-- 现金券列表 -->
      <van-popup v-model="showList" position="bottom">
        <van-coupon-list
          :coupons="coupons"
          :chosen-coupon="chosenCoupon"
          :disabled-coupons="disabledCoupons"
          :show-exchange-bar="false"
          @change="onChange"
        />
      </van-popup>
    </div>
    <div class="pay-price">
      <div class="pay-summary">
        <span class="pay-summary-title">下单支付：</span>
        <span class="pay-summary-body">￥{{Number(totalPriceWithCoupon).toFixed(2)}}</span>
      </div>
    </div>
    <div
      class="pay-with-balance-bar"
      :style="{width: screenWidth+'px'}"
      v-if="Number(walletBalance)>0"
    >
      <div class="pay-with-balance-price">￥{{discountWithExpressPriceWithCoupon}}</div>
      <div class="pay-with-balance-desc">折上折95折</div>
      <div
        class="pay-with-balance-btn"
        v-if="Number(walletBalance)>=Number(discountWithExpressPriceWithCoupon) && latestAddress"
        @click="payWithWallet"
      >余额支付</div>
      <div class="pay-with-balance-btn pay-with-balance-btn-disable" v-else>余额支付</div>
    </div>
    <div class="pay-with-wx-bar" :style="{width: screenWidth+'px'}">
      <div class="pay-with-wx-price">￥{{totalPriceWithCoupon}}</div>
      <div class="pay-with-wx-express-fee">{{ expressFee!==0?'包含运费':'已包邮'}}</div>
      <div class="pay-with-wx-btn" v-if="latestAddress && !paying" @click="payWithWx">微信支付</div>
      <div class="pay-with-wx-btn pay-with-wx-btn-disable" v-if="!latestAddress && !paying">微信支付</div>
      <div class="pay-with-wx-btn pay-with-wx-btn-disable" v-if="latestAddress && paying">支付中..</div>
    </div>
  </div>
</template>
<style scoped>
body {
  background-color: white;
  margin: 0;
}
.no-user-address {
  background-color: white;
  display: flex;
  flex-direction: row;
  justify-content: center;
  align-items: center;
  padding: 20px;
}
.add-address {
  background-color: white;
  font-size: 15px;
  color: #3d404a;
  border: 0.5px solid #3d404a;
  border-radius: 4px;
  text-align: center;
  padding: 5px 15px;
}
.user-address {
  display: flex;
  flex-direction: column;
  border-bottom: 0.5px solid #ddd;
  background-color: white;
}
.user-address-contact {
  display: flex;
  flex-direction: row;
  padding-left: 20px;
  padding-top: 20px;
  font-size: 16px;
  font-weight: 600;
  color: #3d404a;
}
.user-address-contact-phone {
  margin-left: 10px;
}
.user-address-detail {
  font-size: 13px;
  color: #3d404a;
  padding: 10px 0 15px 0;
  margin: 0 20px;
  border-bottom: 0.5px solid #eee;
}
.user-address-update {
  padding: 10px 20px;
  display: flex;
  flex-direction: row;
  justify-content: flex-end;
}
.user-address-update-btn {
  font-size: 15px;
  color: #3d404a;
  border: 0.5px solid #3d404a;
  border-radius: 4px;
  text-align: center;
  padding: 5px 15px;
}
.sale-books {
  background-color: #fff;
  border-bottom: 0.5px solid #ddd;
  padding: 15px 0;
  margin-top: 10px;
}
.sale-book {
  position: relative;
  width: 100%;
  height: 28px;
}
.pay-price {
  padding: 10px 0;
  background-color: #fff;
  border-bottom: 0.5px solid #ddd;
  margin-bottom: 136px;
}
.pay-summary {
  height: 28px;
  line-height: 28px;
  padding-right: 20px;
  display: flex;
  flex-direction: row;
  justify-content: flex-end;
}
.pay-summary-title {
  font-size: 15px;
  color: #3d404a;
}
.pay-summary-body {
  color: #ff4848;
  font-size: 20px;
}
.book-name {
  position: absolute;
  left: 20px;
  top: 3px;
  font-size: 15px;
  white-space: nowrap;
  text-overflow: ellipsis;
  overflow: hidden;
  width: 75%;
  color: #333;
}
.book-sale-price {
  position: absolute;
  right: 20px;
  top: 3px;
  font-size: 16px;
  color: #333;
}
.pay-with-balance-bar {
  display: flex;
  flex-direction: row;
  justify-content: space-between;
  align-items: center;
  padding: 10px 0;
  position: fixed;
  left: 0;
  bottom: 62px;
  height: 40px;
  background-color: #fffafa;
  border-top: 0.5px solid #ddd;
}
.pay-with-balance-price {
  font-size: 22px;
  font-weight: 600;
  color: #3d404a;
  margin-left: 20px;
  flex-grow: 1;
}
.pay-with-balance-desc {
  font-size: 13px;
  color: #ff4848;
  font-weight: 500;
  text-align: left;
  margin-left: 10px;
  flex-grow: 10;
}
.pay-with-balance-btn {
  background-color: #3d404a;
  border-radius: 4px;
  color: white;
  font-size: 16px;
  padding: 8px 12px;
  margin-right: 20px;
  flex-grow: 1;
  text-align: center;
}
.pay-with-balance-btn-disable {
  opacity: 0.3;
}
.pay-with-wx-bar {
  display: flex;
  flex-direction: row;
  justify-content: space-between;
  align-items: center;
  padding: 10px 0;
  position: fixed;
  left: 0;
  bottom: 0;
  height: 40px;
  background-color: white;
  border-top: 0.5px solid #ddd;
}
.pay-with-wx-price {
  font-size: 22px;
  font-weight: 600;
  color: #3d404a;
  margin-left: 20px;
  flex-grow: 1;
}
.pay-with-wx-express-fee {
  font-size: 13px;
  color: #9b9b9b;
  text-align: left;
  margin-left: 10px;
  flex-grow: 10;
}
.pay-with-wx-btn {
  background-color: #07a612;
  border-radius: 4px;
  color: white;
  font-size: 16px;
  padding: 8px 12px;
  margin-right: 20px;
  flex-grow: 1;
  text-align: center;
}
.pay-with-wx-btn-disable {
  opacity: 0.3;
}
</style>
<style>
.van-coupon-list {
  z-index: 3000;
}
</style>
<script>
import wx from "weixin-js-sdk";
import { mapGetters, mapState, mapActions } from "vuex";
import Loading from './Loading'
export default {
  data() {
    return {
      paying: false,
      screenWidth: 0,
      wxConfig: "",
      order: "",
      orderConfig: "",
      showList: false,
      chosenCoupon: -1,
      loading:false
    };
  },
  computed: {
    coupons: function() {
      return this.vanSaleCoupons.filter(coupon => {
        // 是否可用，金额是否符合，是否在可用时间内
        // console.log('coupon.value',coupon.value)
        // console.log('Number.value',Number(coupon.value))
        console.log('coupon.order_type',coupon.order_type,'coupon.value',coupon.value,'this.expressFee',this.expressFee)
        return (
          coupon.available == 1 &&
          coupon.used == 0 &&
           (this.expressFee==0? (coupon.order_type=="sale" && Number(coupon.value)==500?false:true):true) &&
          Number(coupon.originCondition) / 100 <= Number(this.selectedPrice) &&
          dayjs().isAfter(coupon.startAt * 1000) &&
          dayjs().isBefore(coupon.endAt * 1000)
        );
      });
    },
    disabledCoupons: function() {
      return this.vanSaleCoupons.filter(coupon => {
        if (coupon.available == 0) {
          coupon.reason = "邀请的用户还没下单";
        } else if (dayjs().isBefore(coupon.startAt * 1000)) {
          coupon.reason =
            "现金券" +
            dayjs(coupon.startAt * 1000).format("YYYY.MM.DD") +
            "生效";
        } else if (dayjs().isAfter(coupon.endAt * 1000)) {
          coupon.reason = "现金券已过期";
        } else if (coupon.used == 1) {
          coupon.reason = "已使用";
        } else if(coupon.order_type=="sale" && Number(coupon.value)==500 && this.expressFee==0) {
          coupon.reason = "已包邮";
        }
        else {
          coupon.reason = "订单不满" + coupon.originCondition / 100 + "元";
        }
        return (
          coupon.available == 0 ||
          (coupon.available == 1 &&
            Number(coupon.originCondition) / 100 >
              Number(this.selectedPrice)) ||
          dayjs().isBefore(coupon.startAt * 1000) ||
          dayjs().isAfter(coupon.endAt * 1000) ||
          coupon.used == 1 ||
          (coupon.order_type=="sale" && Number(coupon.value)==500 && this.expressFee==0)
        );
      });
    },
    discountWithoutExpressPrice: function() {
      return (Number(this.selectedPrice) * 0.95).toFixed(2);
    },
    discountWithExpressPrice: function() {
      return Number(
        Number(this.discountWithoutExpressPrice) + this.expressFee
      ).toFixed(2);
    },
    discountWithExpressPriceWithCoupon: function() {
      console.log("可用的优惠券列表");
      console.log(this.coupons());
      if (this.chosenCoupon != -1) {
        return Number(
          Number(this.discountWithoutExpressPrice) +
            this.expressFee -
            this.coupons[this.chosenCoupon].value / 100
        ).toFixed(2);
      }
      return Number(
        Number(this.discountWithoutExpressPrice) + this.expressFee
      ).toFixed(2);
    },
    totalPriceWithExpress: function() {
      return Number(Number(this.selectedPrice) + this.expressFee).toFixed(2);
    },
    totalPriceWithCoupon: function() {
      console.log("this.coupons" + this.chosenCoupon);
      console.log(this.coupons);
      if (this.chosenCoupon != -1) {
        return Number(
          Number(this.selectedPrice) +
            this.expressFee -
            this.coupons[this.chosenCoupon].value / 100
        ).toFixed(2);
      }
      return this.totalPriceWithExpress;
    },
    expressFee: function() {
      var fee = 5;
      if (
        this.latestAddress.province === "西藏自治区" ||
        this.latestAddress.province === "新疆维吾尔自治区"
      ) {
        fee = 20;
        if (this.selectedItems.length > 3) {
          return fee + (this.selectedItems.length - 3) * 10;
        }
      } else if (
        this.latestAddress.province === "内蒙古自治区" ||
        this.latestAddress.province === "海南省" ||
        this.latestAddress.province === "甘肃省" ||
        this.latestAddress.province === "青海省" ||
        this.latestAddress.province === "宁夏回族自治区"
      ) {
        fee = 15;
        if (this.selectedItems.length > 3) {
          return fee + (this.selectedItems.length - 3) * 5;
        }
      } else if (this.selectedPrice >= 99) {
        return 0;
      }
      return fee;
    },
    ...mapState({
      userId: state => state.user.userId,
      user: state => state.user.user,
      latestAddress: state => state.user.latestAddress,
      walletBalance: state => state.user.walletBalance
    }),
    ...mapGetters("cart", {
      selectedItems: "selectedItems",
      selectedPrice: "selectedPrice"
    }),
    ...mapGetters("coupon", {
      vanSaleCoupons: "vanSaleCoupons"
    })
  },
  created: function() {
    this.getWxConfig();
    this.wxApi.wxConfig("", "");
    var _this = this;
    this.$store.dispatch("user/latestAddress").then(res => {
      console.log(res.data);
      if (!res.data) {
        this.$dialog
          .confirm({
            title: "你还没有地址",
            confirmButtonText: "添加新地址"
          })
          .then(() => {
            // confirm
            this.$router.push("/pc/address_edit?from=sale_invoice");
          });
      } else {
        this.getWxConfig();
      }
    });
    this.$store.dispatch("user/walletBalance");
    this.$store.dispatch("coupon/getCoupons").then(res => {
      if (_this.coupons.length > 0) {
        _this.chosenCoupon = 0;
      }
    });
    if (this.selectedItems.length === 0) {
      this.loading = true;
      this.$store.dispatch("cart/items").then(res => {
        this.loading = false;
      });
    }
  },
  mounted: function() {
    this.screenWidth =
      window.innerWidth ||
      document.documentElement.clientWidth ||
      document.body.clientWidth;
  },
  activated(){
    if (!this.latestAddress) {
      this.$store.dispatch("user/latestAddress").then(res => {
        if (!res.data) {
          this.$dialog
            .confirm({
              title: "你还没有地址",
              confirmButtonText: "添加新地址"
            })
            .then(() => {
              // confirm
              this.$router.push("/pc/address_edit?from=recover_invoice");
            });
        }
      });
    }
  },
  beforeRouteLeave: function(to, from, next) {
    if (this.showList == true) {
      this.showList = false;
      return;
    }
    if (
      to.path === "/pc/address_edit" ||
      to.path === "/pc/address_list" ||
      to.path === "/pc/express_fee" ||
      to.path.indexOf("/pc/sale_order/") == 0
    ) {
      next();
    } else {
      this.$dialog.confirm({
        message: "你还没有支付！确定返回吗？",
        beforeClose: function(action, done) {
          if (action === "confirm") {
            done();
            next();
          } else {
            done();
            next(false);
          }
        }
      });
    }
  },
  methods: {
    onChange(index) {
      this.chosenCoupon = index;
      var coupon = this.coupons[index];
      console.log("onChange " + JSON.stringify(coupon));
      this.showList = false;
    },
    getWxConfig: function() {
      var _this = this;
      return new Promise(resolve => {
        axios
          .post("/wx-api/config", {
            url: "sale_invoice"
          })
          .then(response => {
            console.log(response.data);
            this.wxConfig = response.data;
            wx.config(response.data);
            wx.ready(() => {
              console.log("ready");
              resolve(response);
            });
            // wx.error(res => {
            //   axios.post("/wx-api/create_client_error", {
            //     user_id: _this.user.id,
            //     error: JSON.stringify(response.data) + JSON.stringify(res),
            //     url: "/wechat/sale_invoice"
            //   });
            //   setTimeout(function() {
            //     _this.getWxConfig();
            //   }, 2000);
            // });
          });
      });
    },
    payWithWx: function() {
      this.paying = true;
      if (this.orderConfig) {
        this.wxPay(this.orderConfig);
      } else {
        this.createSaleOrder({
          address_id: this.latestAddress.id,
          coupon:
            this.chosenCoupon == -1 ? null : this.coupons[this.chosenCoupon]
        }).then(res => {
          if (res.data.code && res.data.code == 500) {
            this.$dialog.alert({
              message: res.data.msg,
              center: true
            });
            this.$store.dispatch("cart/items");
          } else {
            this.order = res.data;
            this.orderConfig = res.data.config;
            this.wxPay(res.data.config);
          }
        });
      }
    },
    payWithWallet: function() {
      if (this.order) {
        this.paySaleOrderWithWallet(this.order.id).then(res => {
          if (res.data.code && res.data.code == 500) {
            this.$dialog.alert({
              message: res.data.msg,
              center: true
            });
          } else {
            console.log("使用余额支付成功");
            var no = this.order.no;
            this.order = "";
            this.$router.replace("/pc/sale_order/" + no);
          }
        });
      } else {
        this.createSaleOrder({
          address_id: this.latestAddress.id,
          coupon:
            this.chosenCoupon == -1 ? null : this.coupons[this.chosenCoupon]
        }).then(res => {
          if (res.data.code && res.data.code == 500) {
            this.$dialog.alert({
              message: res.data.msg,
              center: true
            });
          } else {
            this.createSuccess();
            this.order = res.data;
            this.paySaleOrderWithWallet(res.data.id).then(res => {
              if (res.data.code && res.data.code == 500) {
                this.$dialog.alert({
                  message: res.data.msg,
                  center: true
                });
              } else {
                console.log("使用余额支付成功");
                var no = this.order.no;
                this.order = "";
                this.$router.replace("/pc/sale_order/" + no);
              }
            });
          }
        });
      }
    },
    wxPay: function(conf) {
      var _this = this;
      wx.chooseWXPay({
        timestamp: conf.timestamp,
        nonceStr: conf.nonceStr,
        package: conf.package,
        signType: conf.signType,
        paySign: conf.paySign,
        success: function() {
          _this.paying = false;
          setTimeout(function() {
            _this.$router.replace("/pc/sale_order/" + _this.order.no);
          }, 500);
        },
        fail: function() {
          _this.paying = false;
          _this.$router.replace("/pc/sale_order/" + _this.order.no);
        },
        cancel: function() {
          _this.paying = false;
          _this.$router.replace("/pc/sale_order/" + _this.order.no);
        }
      });
    },
    getSaleOrderPaymentStatus: function() {
      axios
        .get(
          "/wx-api/get_sale_order_payment_status/" + this.orderConfig.order_id
        )
        .then(res => {
          if (_.isEmpty(res.data.paid_at)) {
            alert("支付失败");
          } else {
            console.log("支付成功");
            this.createSuccess();
            this.$router.replace("/pc/sale_order/" + this.order.no);
          }
        });
    },
    createSuccess: function() {
      this.$store.dispatch("cart/items");
    },
    ...mapActions("order", [
      "createSaleOrder",
      "getSaleOrderWxConfig",
      "paySaleOrderWithWallet"
    ])
  }
};
</script>
