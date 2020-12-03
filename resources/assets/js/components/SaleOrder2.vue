<template>
  <div style="background-color: #f0f0f0">
    <!--<van-notice-bar-->
    <!--text="春节发货调整：1月28日下午6点后~2月10日期间的买书订单，会在2月11日（正月初七）按顺序统一发货。"-->
    <!--left-icon="volume-o"-->
    <!--/>-->
    <div class="sale-order-top">
      <div class="sale-order-status">
        <div class="sale-order-status-desc">{{orderSaleStatusDesc}}</div>
        <div class="sale-order-created-at">{{ createdAt }}</div>
      </div>
      <div class="sale-order-operation">
        <div class="sale-order-no">{{ order.no }}</div>
        <router-link :to="`/wechat/sale_order_ship/${order.no}`" class="sale-order-track">状态跟踪</router-link>
      </div>
      <div class="ship-traces" v-if="traces.length>0">
        <van-steps direction="vertical" :active="0" active-color="#f44">
          <van-step>
            <h4>{{traces[0].AcceptStation}}</h4>
            <p>{{traces[0].AcceptTime}}</p>
          </van-step>
        </van-steps>
      </div>
    </div>
    <div class="sale-order-books-title">
      <div class="sale-order-books-count" v-if="items">共{{ items.length }}本</div>
      <router-link to="/wechat/level_desc" class="sale-order-level-desc">
        不同品相的差别
        <i class="el-icon-arrow-right"></i>
      </router-link>
    </div>
    <div class="sale-order-books" v-if="items">
      <order-book :item="item" :screen-width="screenWidth" v-for="item in items" :key="item.id"></order-book>
    </div>
    <div class="sale-order-summary">
      <div class="sale-order-summary-i">
        <div class="sale-order-summary-left">书价</div>
        <div class="sale-order-summary-right">￥{{ orderOriginalPrice }}</div>
      </div>
      <div class="sale-order-summary-i">
        <div class="sale-order-summary-left">运费</div>
        <div class="sale-order-summary-right">￥{{ Number(expressFee).toFixed(2) }}</div>
      </div>
      <div class="sale-order-summary-i" v-if="order.coupon">
        <div class="sale-order-summary-left">{{order.coupon.name}}</div>
        <div class="sale-order-summary-right">-￥{{ order.coupon.value }}</div>
      </div>
    </div>
    <div class="sale-order-total-price" v-if="order && order.paid_at">
      实付款：
      <span class="sale-order-price-text">￥{{order.total_amount}}</span>
    </div>
    <div class="sale-order-total-price" v-if="order && order.paid_at===null">
      <div
        class="sale-order-wx-pay"
        v-if="!order.closed && order.sale_status!==-1"
        @click="payWithWx"
      >微信支付</div>待支付：
      <span class="sale-order-price-text">￥{{order.total_amount}}</span>
    </div>

    <div class="sale-order-address">
      <div class="sale-order-contact" v-if="address">
        <div class="sale-order-contact-name">{{address.contact_name}}</div>
        <div class="sale-order-contact-phone">{{address.contact_phone}}</div>
      </div>
      <div class="sale-order-address-detail" v-if="address">{{ detailAddress }}</div>
      <div class="sale-order-address-update">
        <div class="sale-order-address-update-btn" @click="changeAddress">更改地址</div>
        <div class="sale-order-del-btn" @click="delOrder" v-show="orderSaleStatusDesc=='已关闭' || orderSaleStatusDesc=='已取消'">删除订单</div>
      </div>
    </div>

    <div class="sale-order-bottom-bar" :style="{width: screenWidth+'px'}">
      <div class="sale-order-contact-us" @click="contactUs">联系客服</div>
      <div
        class="sale-order-cancel"
        @click="qxOrder"
        v-show="order.sale_status==10 ||order.sale_status==20"
      >取消订单</div>
    </div>
    <van-dialog
      v-model="dialogVisible"
      title="确定取消订单吗?"
      show-cancel-button
      cancel-button-text="我按错了"
      @confirm="cancel"
    ></van-dialog>
  </div>
</template>
<style scoped>
body {
  background-color: white;
  margin: 0;
}
.coupon {
  padding: 20px 20px 10px 20px;
}
.sale-order-top {
  display: flex;
  flex-direction: column;
  background-color: white;
  border-bottom: 0.5px solid #ebedf0;
}
.sale-order-status {
  display: flex;
  flex-direction: row;
  justify-content: space-between;
  align-items: center;
  padding: 15px 20px;
  border-bottom: 0.5px solid #ebedf0;
}
.sale-order-status-desc {
  font-size: 16px;
  font-weight: 600;
  color: #3d404a;
}
.sale-order-created-at {
  font-size: 13px;
  color: #3d404a;
}
.sale-order-operation {
  display: flex;
  flex-direction: row;
  justify-content: space-between;
  padding: 10px 20px;
  align-items: center;
}
.sale-order-no {
  font-size: 13px;
  color: #3d404a;
}
.sale-order-track {
  font-size: 15px;
  padding: 5px 15px;
  color: #3d404a;
  background-color: white;
  border: 0.5px solid #3d404a;
  border-radius: 4px;
}
.ship-traces {
  margin-top: 10px;
  border-top: 0.5px solid #ebedf0;
  display: flex;
  flex-direction: column;
  padding: 10px 5px;
  background-color: white;
}
.sale-order-dialog-items {
  display: flex;
  flex-direction: row;
  margin-top: 30px;
  justify-content: space-around;
  align-items: center;
}
.sale-order-ok-btn {
  padding: 0 30px;
  background-color: white;
  color: #9b9b9b;
  font-size: 14px;
  height: 40px;
  line-height: 40px;
  border-radius: 4px;
  border: 0.5px solid #ebedf0;
  text-align: center;
}
.sale-order-cancel-btn {
  padding: 0 30px;
  background-color: #1db89c;
  color: white;
  font-size: 14px;
  height: 40px;
  line-height: 40px;
  border-radius: 4px;
  text-align: center;
}
.sale-order-books-title {
  display: flex;
  flex-direction: row;
  justify-content: space-between;
  align-items: center;
  margin-top: 10px;
  border-top: 0.5px solid #ebedf0;
  border-bottom: 0.5px solid #ebedf0;
  background-color: white;
}
.sale-order-books-count {
  padding: 15px 20px;
  color: #3d404a;
  font-size: 15px;
}
.sale-order-level-desc {
  padding: 15px;
  color: #ff4848;
  font-size: 14px;
  opacity: 0.5;
}
.sale-order-books {
  background-color: #fff;
}
.sale-order-summary {
  border-bottom: 0.5px solid #ebedf0;
  background-color: white;
}
.sale-order-summary-i {
  display: flex;
  flex-direction: row;
  justify-content: space-between;
  padding: 15px 20px;
  border-bottom: 0.5px solid #ebedf0;
}
.sale-order-summary-left {
  font-size: 14px;
  color: #555555;
}
.sale-order-summary-right {
  font-size: 14px;
  font-weight: bold;
  color: #555555;
}
.sale-order-total-price {
  background-color: white;
  padding: 15px 20px;
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: flex-end;
  border-bottom: 0.5px solid #ebedf0;
  font-size: 14px;
}
.sale-order-wx-pay {
  background-color: #07a612;
  border-radius: 4px;
  color: white;
  font-size: 16px;
  padding: 8px 12px;
  margin-right: 20px;
  flex-grow: 1;
  text-align: center;
}
.sale-order-price-text {
  color: #ff4848;
  font-size: 16px;
  font-weight: bold;
}
.sale-order-address {
  display: flex;
  flex-direction: column;
  border-bottom: 0.5px solid #ebedf0;
  background-color: white;
  border-top: 0.5px solid #ebedf0;
  margin-top: 10px;
  margin-bottom: 80px;
}
.sale-order-contact {
  display: flex;
  flex-direction: row;
  align-items: center;
  padding-left: 20px;
  padding-top: 20px;
  font-size: 16px;
  color: #3d404a;
}
.sale-order-contact-phone {
  margin-left: 10px;
}
.sale-order-address-detail {
  font-size: 13px;
  color: #888888;
  padding: 10px 0 15px 0;
  margin: 0 20px;
  border-bottom: 0.5px solid #ebedf0;
}
.sale-order-address-update {
  padding: 10px 20px;
  display: flex;
  flex-direction: row;
  justify-content: flex-end;
}
.sale-order-address-update-btn {
  font-size: 15px;
  color: #3d404a;
  border: 0.5px solid #3d404a;
  border-radius: 4px;
  text-align: center;
  padding: 5px 15px;
}
.sale-order-del-btn {
  font-size: 15px;
  color: red;
  border: 0.5px solid red;
  border-radius: 4px;
  text-align: center;
  padding: 5px 15px;
  margin-left: 10px;
}
.sale-order-bottom-bar {
  position: fixed;
  left: 0;
  bottom: 0;
  height: 60px;
  display: flex;
  flex-direction: row;
  justify-content: flex-end;
  align-items: center;
  background-color: white;
  border-top: 0.5px solid #ebedf0;
}
.sale-order-contact-us {
  font-size: 15px;
  color: #3d404a;
  border: 0.5px solid #3d404a;
  border-radius: 4px;
  text-align: center;
  padding: 5px 15px;
  margin: 0 20px;
}
.sale-order-cancel {
  font-size: 15px;
  color: #3d404a;
  border: 0.5px solid #3d404a;
  border-radius: 4px;
  text-align: center;
  padding: 5px 15px;
  margin: 0 20px;
}
</style>
<script>
import wx from "weixin-js-sdk";
import { mapGetters, mapState, mapActions } from "vuex";
import { Toast ,Dialog} from "vant";
import OrderBook from "./OrderBook";
export default {
  data() {
    return {
      no: "",
      screenWidth: 0,
      orderConfig: "",
      dialogVisible: false,
      interval: "",
      traces: []
    };
  },
  created: function() {
    this.wxApi.wxConfig("", "");
    this.$store.dispatch("user/getUser").then(res => {
      // 如果拿不到用户，就显示一个不可关闭的对话框
      var user = res.data;
      if(user==undefined){
          
      }
      else if (user === "" ) {
        this.$router.replace("/wechat/shop");
      } else if (user.subscribe === 0) {
        this.$router.replace("/wechat/shop");
      }
    });
    var _this = this;
    this.no = this.$route.params.no;
    this.getOrder(this.no).then(res => {
      _this.getSaleOrderPaymentStatusByNo();
      if (!_.isEmpty(this.order.ship_data)) {
        if (typeof this.order.ship_data === "string") {
          this.traces = JSON.parse(this.order.ship_data).Traces.reverse();
        } else {
          this.traces = this.order.ship_data.Traces.reverse();
        }
      }
    });
  },
  computed: {
    createdAt: function() {
      return dayjs(this.order.created_at).format("YYYY-MM-DD HH:mm");
    },
    paidAt: function() {
      return dayjs(this.order.paid_at).format("YYYY-MM-DD HH:mm");
    },
    expressFee: function() {
      var fee = 5;
      if (_.isEmpty(this.address)) {
        return fee;
      }
      var province = this.address.province;
      if (province === "西藏自治区" || province === "新疆维吾尔自治区") {
        fee = 20;
        if (this.items.length > 3) {
          return fee + (this.items.length - 3) * 10;
        }
      } else if (
        province === "内蒙古自治区" ||
        province === "海南省" ||
        province === "甘肃省" ||
        province === "青海省" ||
        province === "宁夏回族自治区"
      ) {
        fee = 15;
        if (this.items.length > 3) {
          return fee + (this.items.length - 3) * 5;
        }
      } else if (this.orderOriginalPrice >= 99) {
        return 0;
      }
      return fee;
    },
    detailAddress: function() {
      var detail = "";
      if (this.address) {
        if (!_.isEmpty(this.address.province)) {
          detail += this.address.province;
        }
        if (!_.isEmpty(this.address.city)) {
          detail += this.address.city;
        }
        if (!_.isEmpty(this.address.district)) {
          detail += this.address.district;
        }
        if (!_.isEmpty(this.address.address)) {
          detail += this.address.address;
        }
      }
      return detail;
    },
    ...mapGetters("order", {
      items: "items",
      address: "address",
      orderOriginalPrice: "orderOriginalPrice",
      orderSaleStatusDesc: "orderSaleStatusDesc"
    }),
    ...mapState({
      user: state => state.user.user,
      order: state => state.order.order
    })
  },
  mounted: function() {
    this.screenWidth =
      window.innerWidth ||
      document.documentElement.clientWidth ||
      document.body.clientWidth;
    // 获取微信配置
    this.getWxConfig();
    this.wxApi.wxConfig("", "");
  },
  destroyed: function() {
    clearInterval(this.interval);
  },
  deactivated: function() {
    clearInterval(this.interval);
  },
  methods: {
    coupon: function() {
      this.$router.push();
    },
    getWxConfig: function() {
      var _this = this;
      axios
        .post("/wx-api/config", {
          url: "sale_order/" + _this.no
        })
        .then(response => {
          console.log(response.data);
          wx.config(response.data);
          wx.ready(() => {
            console.log("ready");
            _this.interval = setInterval(function() {
              _this.getSaleOrderPaymentStatusByNo();
            }, 10000);
          });
        });
    },
    showDialog: function() {
      this.dialogVisible = true;
    },
    hideDialog: function() {
      this.dialogVisible = false;
    },
    cancel: function() {
      var _this = this;
      const toast = Toast.loading({
        duration: 0, // 持续展示 toast
        forbidClick: true, // 禁用背景点击
        loadingType: "spinner",
        message: "取消中..."
      });
      this.cancelOrder(this.no).then(res => {
        if (res.data.code && res.data.code === 500) {
          this.$dialog.alert({
            message: res.data.msg,
            center: true
          });
        } else {
          toast.clear();
          this.$notify({
            message: "订单已取消",
            duration: 3000,
            background: "#1AB32B"
          });
          clearInterval(this.interval);
        }
        this.hideDialog();
      });
    },
    qxOrder: function() {
      if (
        this.order.sale_status >= 30 ||
        this.order.sale_status === -1 ||
        this.order.closed
      ) {
        this.$dialog.alert({
          message: "不能取消了",
          center: true
        });
      } else {
        this.showDialog();
      }
    },
    changeAddress: function() {
      if (
        this.order.sale_status >= 30 ||
        this.order.sale_status === -1 ||
        this.order.closed
      ) {
        this.$dialog.alert({
          message: "不能更换地址了",
          center: true
        });
      } else {
        this.$router.push({
          path: "/wechat/address_list",
          query: { fo: true }
        });
      }
    },
    contactUs: function() {
      this.$dialog.alert({
        message: "请在公众号中询问",
        center: true
      });
    },
    payWithWx: function() {
      this.getSaleOrderWxConfig(this.order.id).then(res => {
        if (res.data.code && res.data.code === 500) {
          this.$dialog.alert({
            message: res.data.msg,
            center: true
          });
        } else {
          this.orderConfig = res.data;
          this.wxPay();
        }
      });
    },
    wxPay: function() {
      var _this = this;
      wx.chooseWXPay({
        timestamp: _this.orderConfig.timestamp,
        nonceStr: _this.orderConfig.nonceStr,
        package: _this.orderConfig.package,
        signType: _this.orderConfig.signType,
        paySign: _this.orderConfig.paySign,
        success: function() {
          // 支付成功后查新订单状态
          _this.getSaleOrderPaymentStatusByNo();
          setTimeout(function() {
            window.location.reload();
          }, 1000);
        }
      });
    },
    getSaleOrderPaymentStatusByNo: function() {
      var _this = this;
      if (this.order) {
        axios
          .get("/wx-api/get_sale_order_payment_status_by_no/" + _this.no)
          .then(res => {
            if (_.isEmpty(res.data.paid_at)) {
              console.log("支付失败");
            } else {
              console.log("支付成功");
            }
          });
      }
    },
    // 删除订单

    delOrder() {
      let that = this;
      Dialog.confirm({
        title: "提示",
        message: "您确定删除该订单吗？"
      })
        .then(() => {
          // on confirm
          axios
            .post("/wx-api/delete_order", { order: that.order.id })
            .then(res => {
              console.log(res);
              if(res.data.code==200){
                  Toast.success(res.data.msg)
                  that.$router.back(-1)
              }else{
                  Toast.fail(res.data.msg)
              }
            }).catch(()=>{
                Toast.clear()
                Toast.fail('删除失败!请稍后再试')
            })
        })
        .catch(() => {
          // on cancel
          
        });
    },
    ...mapActions("order", ["getOrder", "cancelOrder", "getSaleOrderWxConfig"])
  },
  components: {
    OrderBook
  }
};
</script>
