<template>
  <div style="background-color: #f0f0f0" class="saleOrderBox">
    <!--<van-notice-bar-->
    <!--text="春节发货调整：1月28日下午6点后~2月10日期间的买书订单，会在2月11日（正月初七）按顺序统一发货。"-->
    <!--left-icon="volume-o"-->
    <!--/>-->
    <div class="hint" v-if="suborders.length>0">
      <img src="/images/image/orderHint.png" width="12" height="12" />由于部分新书由不同地区的仓库发货，故物流信息需分开显示。
    </div>
    <div class="bg">
      <div class="orderStatus">
        <img src="/images/image/orderGou.png" width="19" height="19" v-show="order.sale_status==70" />
        {{orderSaleStatusDesc}}
      </div>
    </div>
    <div class="orderAddress">
      <div class="addressBox box_cqh">
        <div class="addressLeft box">
          <div class="addrIcon">
            <img src="/images/image/orderAddr.png" width="12" height="10" />
          </div>
          <div class="addrInfo">
            <div class="addrTop">
              <span class="addrName" v-if="address">{{address.contact_name}}</span>
              <span class="addrPhone" v-if="address">{{address.contact_phone}}</span>
            </div>
            <div class="addrDetail">{{detailAddress}}</div>
          </div>
        </div>
        <div class="addressRight">
          <div @click="changeAddress" v-if="order.sale_status==10 || order.sale_status==20">
            <img src="/images/image/more.png" width="8" height="13" />
          </div>
        </div>
      </div>
    </div>

    <div class="orderItem" v-if="items.length>0">
      <div class="itemTop box_cqh">
        <div class="bookNum">共{{ items.length }}本</div>
        <div class="bookStatus">{{orderSaleStatusDesc}}</div>
      </div>
      <div class="itemBooks">
        <div class="books box_cqh" v-for="item in items" :key="item.id">
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
          <div>￥{{ Number(expressFee).toFixed(2) }}</div>
        </div>
        <div class="itemPrice box_cqh">
          <div>总价</div>
          <div>￥{{ orderOriginalPrice }}</div>
        </div>
      </div>
      <div class="itemLogistics">
        <router-link :to="`/wechat/sale_order_ship/${order.no}`" class="btn" tag="div">查看物流</router-link>
      </div>
    </div>
    <order-book
      :order="item"
      :screen-width="screenWidth"
      v-for="item in suborders"
      :key="item.id"
      v-else
    ></order-book>
    <div class="totalOrder">
      <div class="totalBox">
        <div class="totalList box_cqh">
          <div class="title">商品总金额</div>
          <div class="value" v-if="items.length>0">￥{{orderOriginalPrice}}</div>
          <div class="value" v-else>￥{{order.total_amount}}</div>
        </div>
        <div class="totalList box_cqh">
          <div class="title">总运费</div>
          <div class="value" v-if="items.length>0">￥{{ Number(expressFee).toFixed(2) }}</div>
          <div class="value" v-else>￥{{order.ship_price}}</div>
        </div>
        <div class="totalList box_cqh" v-if="order.coupon">
          <div class="title">{{order.coupon.name}}</div>
          <div class="value">-￥{{ order.coupon.value }}</div>
        </div>
      </div>
      <div class="totalPrice">
        <div class="payment" v-show="order && order.paid_at">
          实付款:
          <span>￥{{order.total_amount}}</span>
        </div>
        <div class="payment box" v-show="order && !order.closed && order.sale_status!=-1 &&order.paid_at==null">
          <div class="sale-order-wx-pay" @click="payWithWx">微信支付</div>
          <div>待支付:<span>￥{{order.total_amount}}</span></div>
        </div>
      </div>
    </div>
    <div class="orderInfo">
      <div class="orderInfoTop">
        <div class="tiao"></div>
        <div class="title">订单信息</div>
      </div>
      <div class="orderInfoBtoom">
        <div>总订单编号：{{ order.no }}</div>
        <div>总下单时间：{{ createdAt }}</div>
      </div>
    </div>
    <div class="orderFixed box_cqh">
      <div class="fixedLeft">
        <div
          class="delOrder"
          @click="delOrder"
          v-show="orderSaleStatusDesc=='已关闭' || orderSaleStatusDesc=='已取消'"
        >删除订单</div>
      </div>
      <div class="fixedright box">
        <div class="btn999" @click="contactUs">联系客服</div>
        <div
          class="btnBlue"
          v-show="order.sale_status==10 ||order.sale_status==20"
          @click="qxOrder"
        >取消订单</div>
      </div>
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
<style scoped lang='scss'>
.saleOrderBox {
  width: 100%;
  padding-bottom: 57px;
  .hint {
    width: 100%;
    height: 33px;
    line-height: 30px;
    background: rgba(244, 239, 215, 1);
    text-align: center;
    font-size: 12px;
    font-family: PingFang-SC;
    font-weight: 400;
    color: rgba(216, 129, 36, 1);
    display: flex;
    align-items: center;
    justify-content: center;
    img {
      margin-right: 2px;
    }
  }
  .bg {
    width: 100%;
    height: 99px;
    padding: 0 18px;
    box-sizing: border-box;
    display: flex;
    align-items: center;
    background: #47b4de;
    background: url("/images/image/orderBg.png") no-repeat;
    background-size: 100% 100%;
    .orderStatus {
      font-size: 18px;
      font-family: PingFang-SC;
      font-weight: bold;
      color: rgba(255, 255, 255, 1);
      line-height: 25px;
      img {
        vertical-align: sub;
        margin-right: 10px;
      }
    }
  }
  .orderAddress {
    width: 100%;
    background: #ffffff;
    padding: 0px 20px;
    box-sizing: border-box;
    .addressBox {
      width: 100%;
      background: rgba(255, 255, 255, 1);
      box-shadow: 0px 2px 8px 0px rgba(0, 0, 0, 0.11);
      border-radius: 9px;
      padding: 14px 20px;
      box-sizing: border-box;
      transform: translateY(-20px);
      .addressLeft {
        .addrIcon {
          margin-right: 10px;
        }
        .addrInfo {
          .addrTop {
            margin-bottom: 4px;
            .addrName {
              font-size: 14px;
              font-family: PingFang-SC;
              font-weight: bold;
              color: rgba(51, 51, 51, 1);
              margin-right: 8px;
            }
            .addrPhone {
              font-size: 12px;
              font-family: PingFang-SC;
              font-weight: 400;
              color: rgba(102, 102, 102, 1);
            }
          }
          .addrDetail {
            font-size: 12px;
            font-family: PingFang-SC-Regular, PingFang-SC;
            font-weight: 400;
            color: rgba(102, 102, 102, 1);
          }
        }
      }
    }
  }
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
  .totalOrder {
    width: 100%;
    padding: 14px 15px;
    box-sizing: border-box;
    margin-bottom: 8px;
    background: #ffffff;
    .totalBox {
      width: 100%;
      font-size: 14px;
      font-family: PingFang-SC;
      font-weight: bold;
      color: rgba(51, 51, 51, 1);
      line-height: 20px;
      padding-bottom: 5px;
      border-bottom: 1px solid #ededed;
      margin-bottom: 9px;
      .totalList {
        margin-bottom: 9px;
      }
    }
    .totalPrice {
      display: flex;
      justify-content: flex-end;
      .payment {
        font-size: 12px;
        font-family: PingFang-SC;
        font-weight: bold;
        color: rgba(51, 51, 51, 1);
        align-items: center;
        .sale-order-wx-pay {
          background-color: #07a612;
          border-radius: 4px;
          color: white;
          font-size: 12px;
          padding: 6px;
          margin-right: 20px;
          flex-grow: 1;
          text-align: center;
        }
        span {
          color: #cc0202;
        }
      }
    }
  }
  .orderInfo {
    width: 100%;
    background: #ffffff;
    padding: 9px 15px;
    box-sizing: border-box;
    .orderInfoTop {
      width: 100%;
      margin-bottom: 8px;
      display: flex;
      align-items: center;
      .tiao {
        width: 2px;
        height: 12px;
        background: rgba(71, 180, 222, 1);
        margin-right: 5px;
      }
    }
    .orderInfoBtoom {
      font-size: 12px;
      font-family: PingFangSC;
      font-weight: 400;
      color: rgba(102, 102, 102, 1);
      line-height: 17px;
      div {
        margin-bottom: 4px;
      }
    }
  }
  .orderFixed {
    width: 100%;
    position: fixed;
    bottom: 0;
    left: 0;
    height: 55px;
    background: rgba(255, 255, 255, 1);
    box-shadow: 0px 2px 5px 0px rgba(0, 0, 0, 0.5);
    padding: 15px 20px;
    box-sizing: border-box;
    .delOrder {
      height: 26px;
      font-size: 13px;
      font-family: PingFangSC;
      font-weight: 400;
      color: rgba(51, 51, 51, 1);
    }
    .btn999 {
      width: 70px;
      text-align: center;
      height: 26px;
      line-height: 26px;
      border-radius: 15px;
      border: 1px solid rgba(153, 153, 153, 1);
      font-size: 12px;
      font-family: PingFang-SC;
      color: rgba(102, 102, 102, 1);
      margin-left: 17px;
    }
    .btnBlue {
      width: 70px;
      height: 26px;
      line-height: 26px;
      text-align: center;
      border-radius: 15px;
      border: 1px solid rgba(71, 180, 222, 1);
      font-size: 12px;
      font-family: PingFang-SC;
      color: rgba(71, 180, 222, 1);
      margin-left: 17px;
    }
  }
}
</style>

<script>
import wx from "weixin-js-sdk";
import { mapGetters, mapState, mapActions } from "vuex";
import { Toast, Dialog } from "vant";
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
      if (user == undefined) {
      } else if (user === "") {
        this.$router.replace("/wechat/shop");
      } else if (user.subscribe === 0) {
        this.$router.replace("/wechat/shop");
      }
    });
    var _this = this;
    this.no = this.$route.params.no;
    Toast.loading("请稍后...");
    this.getOrder(this.no).then(res => {
      _this.getSaleOrderPaymentStatusByNo();
      if (!_.isEmpty(this.order.ship_data)) {
        if (typeof this.order.ship_data === "string") {
          this.traces = JSON.parse(this.order.ship_data).Traces.reverse();
        } else {
          this.traces = this.order.ship_data.Traces.reverse();
        }
      }
      Toast.clear();
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
      suborders: "suborders",
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
              if (res.data.code == 200) {
                Toast.success(res.data.msg);
                that.$router.back(-1);
              } else {
                Toast.fail(res.data.msg);
              }
            })
            .catch(() => {
              Toast.clear();
              Toast.fail("删除失败!请稍后再试");
            });
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
