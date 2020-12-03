<template>
  <div style="background-color: #efefef">
    <div class="no-user-address" v-if="!latestAddress">
      <router-link to="/wechat/address_edit?from=recover_invoice" class="add-address">添加地址</router-link>
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
          to="/wechat/address_list?from=recover_invoice"
          class="user-address-update-btn"
        >更改地址</router-link>
      </div>
    </div>

    <div id="trigger6" v-model="time" class="recover-time">请选择快递上门时间</div>

    <div class="recover-books">
      <div class="recover-book" v-for="(item, index) in recoverSaleItems">
        <div class="recover-book-name">{{item.book.name}}</div>
        <div
          class="recover-book-price"
        >￥{{item.book.price?(item.book.price*item.book.discount/100).toFixed(2):'10'}}</div>
      </div>
    </div>
    <div class="recover-books">
      <div class="recover-book" style="border-bottom: 0.5px solid #eee;">
        <div class="recover-book-name">总价</div>
        <div class="recover-book-sale-price">￥{{totalPrice}}</div>
      </div>
      <div
        class="recover-book"
        style="border-bottom: 0.5px solid #eee;"
        v-if="recoverCoupons.length>0"
      >
        <div class="recover-book-name" style="color: #ff4848">{{recoverCoupons[0].name}}</div>
        <div class="recover-book-price" style="color: #ff4848;">￥{{recoverCoupons[0].value}}</div>
      </div>
      <div class="recover-book">
        <div class="recover-book-name">运费</div>
        <div class="recover-book-price">回流鱼包邮</div>
      </div>
    </div>
    <!--<div style="margin-top: 20px;" v-if="vanRecoverCoupons.length>0">-->
    <!--&lt;!&ndash; 现金券单元格 &ndash;&gt;-->
    <!--<van-coupon-cell-->
    <!--:coupons="coupons"-->
    <!--:chosen-coupon="chosenCoupon"-->
    <!--@click="showList = true"-->
    <!--/>-->
    <!--&lt;!&ndash; 现金券列表 &ndash;&gt;-->
    <!--<van-popup v-model="showList" position="bottom">-->
    <!--<van-coupon-list-->
    <!--:coupons="coupons"-->
    <!--:chosen-coupon="chosenCoupon"-->
    <!--:disabled-coupons="disabledCoupons"-->
    <!--:show-exchange-bar="false"-->
    <!--@change="onChange"-->
    <!--/>-->
    <!--</van-popup>-->
    <!--</div>-->
    <div class="recover-books" style="padding-bottom:64px;">
      <div style="text-align:right;padding:5px 0 5px 0;">
        <span style="font-size:16px;color:#333;">预计收入：</span>
        <span style="color: #F86C1B;font-size:20px">￥{{totalPriceWithCoupon}}</span>
      </div>
      <router-link to="/wechat/review_standard" class="recover-review">
        实际收入以回流鱼收书审核后为准&nbsp;
        <van-icon name="arrow" />
      </router-link>
    </div>
    <div class="recover-submit-bar" @click="submit()">提交订单</div>
  </div>
</template>
<style>
.el-message-box {
  width: 90%;
}
</style>
<style scoped>
body {
  background-color: #f5f8fa;
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
.recover-review {
  display: flex;
  flex-direction: row;
  justify-content: flex-end;
  align-items: center;
  font-size: 14px;
  font-weight: 300;
  color: #35c1aa;
}
a {
  text-decoration: none;
}
a:visited {
  text-decoration: none;
}
a:link {
  text-decoration: none;
}
a:hover {
  text-decoration: none;
}
.user-address {
  background-color: #fff;
  padding-bottom: 10px;
}
.recover-time {
  -webkit-appearance: none;
  background-color: #fff;
  background-image: none;
  border: 1px solid #dcdfe6;
  -webkit-box-sizing: border-box;
  box-sizing: border-box;
  color: #ff4848;
  display: inline-block;
  font-size: inherit;
  height: 60px;
  line-height: 60px;
  outline: 0;
  padding: 0 15px;
  -webkit-transition: border-color 0.2s cubic-bezier(0.645, 0.045, 0.355, 1);
  transition: border-color 0.2s cubic-bezier(0.645, 0.045, 0.355, 1);
  width: 100%;
  margin-top: 15px;
}
.recover-submit-bar {
  margin-top: 20px;
  text-align: center;
  height: 44px;
  width: 100%;
  font-size: 18px;
  line-height: 44px;
  background-color: #3d404a;
  color: #fff;
}
.recover-books {
  background-color: #fff;
  border-bottom: 0.5px solid #eee;
  margin-top: 15px;
  padding: 10px 20px;
}
.recover-book {
  display: flex;
  flex-direction: row;
  justify-content: space-between;
  padding: 5px 0;
}
.recover-book-name {
  font-size: 16px;
  white-space: nowrap;
  text-overflow: ellipsis;
  overflow: hidden;
  width: 75%;
  color: #3d404a;
}
.recover-book-price {
  font-size: 16px;
  color: #3d404a;
}
</style>
<script>
import MobileSelect from "mobile-select";
import { mapState, mapGetters, mapActions } from "vuex";
export default {
  data() {
    return {
      screenWidth: 0,
      time: "",
      showList: false,
      chosenCoupon: -1
    };
  },
  computed: {
    coupons: function() {
      return this.vanRecoverCoupons.filter(coupon => {
        return coupon.available === 1;
      });
    },
    disabledCoupons: function() {
      return this.vanRecoverCoupons.filter(coupon => {
        return coupon.available === 0;
      });
    },
    totalPriceWithCoupon: function() {
      if (this.recoverCoupons.length > 0) {
        return Number(
          Number(this.recoverCoupons[0].value) + Number(this.totalPrice)
        ).toFixed(2);
      }
      return this.totalPrice;
    },
    ...mapState({
      saleItems: state => state.sale2hly.saleItems,
      latestAddress: state => state.user.latestAddress
    }),
    ...mapGetters("sale2hly", {
      totalPrice: "totalPrice",
      recoverSaleItems: "recoverSaleItems",
      rejectSaleItems: "rejectSaleItems"
    }),
    ...mapGetters("coupon", {
      vanRecoverCoupons: "vanRecoverCoupons",
      recoverCoupons: "recoverCoupons"
    })
  },
  created: function() {
    var _this = this;
    this.wxApi.wxConfig("", "");
    if (this.recoverSaleItems.length == 0) {
      this.$store.dispatch("user/getUser").then(res => {
        // 如果拿不到用户，就显示一个不可关闭的对话框
        var user = res.data;
        if (user === "") {
          this.$router.replace("/wechat/shop");
        } else if (user.subscribe === 0) {
          this.$router.replace("/wechat/shop");
        }
      });
      this.$store.dispatch("sale2hly/getBooksForRecover").then(res => {});
    }
    this.$store.dispatch("user/latestAddress").then(res => {
      if (!res.data) {
        this.$dialog
          .confirm({
            title: "你还没有地址",
            confirmButtonText: "添加新地址"
          })
          .then(() => {
            // confirm
            this.$router.push("/wechat/address_edit?from=recover_invoice");
          });
      }
    });
    this.$store.dispatch("coupon/getCoupons");
  },
  mounted: function() {
    this.screenWidth =
      window.innerWidth ||
      document.documentElement.clientWidth ||
      document.body.clientWidth;
    //build time config
    this.buildTimeOptions();
  },
  activated() {
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
              this.$router.push("/wechat/address_edit?from=recover_invoice");
            });
        }
      });
    }
  },
  methods: {
    buildTimeOptions: function() {
      var timeData = [];
      var weekday = [
        "星期日",
        "星期一",
        "星期二",
        "星期三",
        "星期四",
        "星期五",
        "星期六"
      ];
      var child = [
        {
          id: "09:00",
          value: "9:00~10:00"
        },
        {
          id: "10:00",
          value: "10:00~11:00"
        },
        {
          id: "11:00",
          value: "11:00~12:00"
        },
        {
          id: "12:00",
          value: "12:00~13:00"
        },
        {
          id: "13:00",
          value: "13:00~14:00"
        },
        {
          id: "14:00",
          value: "14:00~15:00"
        },
        {
          id: "15:00",
          value: "15:00~16:00"
        },
        {
          id: "16:00",
          value: "16:00~17:00"
        },
        {
          id: "17:00",
          value: "17:00~18:00"
        }
      ];
      var current = new Date();
      var currentHour = current.getHours();
      var gap = 17 - currentHour; // 3
      var todayChild = [];
      if (gap > 0) {
        for (var i = 9 - gap; i < 9; i++) {
          todayChild.push(child[i]);
        }
        timeData.push({
          id:
            current.getFullYear() +
            "-" +
            (current.getMonth() + 1) +
            "-" +
            current.getDate(),
          value:
            current.getMonth() +
            1 +
            "月" +
            current.getDate() +
            "日 " +
            weekday[current.getDay()],
          childs: todayChild
        });
      } else {
        current.setTime(current.getTime() + 24 * 60 * 60 * 1000);
        for (var i = 0; i < 9; i++) {
          todayChild.push(child[i]);
        }
        timeData.push({
          id:
            current.getFullYear() +
            "-" +
            (current.getMonth() + 1) +
            "-" +
            current.getDate(),
          value:
            current.getMonth() +
            1 +
            "月" +
            current.getDate() +
            "日 " +
            weekday[current.getDay()],
          childs: todayChild
        });
      }
      current.setTime(current.getTime() + 24 * 60 * 60 * 1000);
      timeData.push({
        id:
          current.getFullYear() +
          "-" +
          (current.getMonth() + 1) +
          "-" +
          current.getDate(),
        value:
          current.getMonth() +
          1 +
          "月" +
          current.getDate() +
          "日 " +
          weekday[current.getDay()],
        childs: child
      });
      current.setTime(current.getTime() + 24 * 60 * 60 * 1000);
      timeData.push({
        id:
          current.getFullYear() +
          "-" +
          (current.getMonth() + 1) +
          "-" +
          current.getDate(),
        value:
          current.getMonth() +
          1 +
          "月" +
          current.getDate() +
          "日 " +
          weekday[current.getDay()],
        childs: child
      });
      current.setTime(current.getTime() + 24 * 60 * 60 * 1000);
      timeData.push({
        id:
          current.getFullYear() +
          "-" +
          (current.getMonth() + 1) +
          "-" +
          current.getDate(),
        value:
          current.getMonth() +
          1 +
          "月" +
          current.getDate() +
          "日 " +
          weekday[current.getDay()],
        childs: child
      });
      current.setTime(current.getTime() + 24 * 60 * 60 * 1000);
      timeData.push({
        id:
          current.getFullYear() +
          "-" +
          (current.getMonth() + 1) +
          "-" +
          current.getDate(),
        value:
          current.getMonth() +
          1 +
          "月" +
          current.getDate() +
          "日 " +
          weekday[current.getDay()],
        childs: child
      });
      current.setTime(current.getTime() + 24 * 60 * 60 * 1000);
      timeData.push({
        id:
          current.getFullYear() +
          "-" +
          (current.getMonth() + 1) +
          "-" +
          current.getDate(),
        value:
          current.getMonth() +
          1 +
          "月" +
          current.getDate() +
          "日 " +
          weekday[current.getDay()],
        childs: child
      });
      current.setTime(current.getTime() + 24 * 60 * 60 * 1000);
      timeData.push({
        id:
          current.getFullYear() +
          "-" +
          (current.getMonth() + 1) +
          "-" +
          current.getDate(),
        value:
          current.getMonth() +
          1 +
          "月" +
          current.getDate() +
          "日 " +
          weekday[current.getDay()],
        childs: child
      });
      current.setTime(current.getTime() + 24 * 60 * 60 * 1000);
      timeData.push({
        id:
          current.getFullYear() +
          "-" +
          (current.getMonth() + 1) +
          "-" +
          current.getDate(),
        value:
          current.getMonth() +
          1 +
          "月" +
          current.getDate() +
          "日 " +
          weekday[current.getDay()],
        childs: child
      });
      console.log(timeData);
      var _this = this;
      var mobileSelect6 = new MobileSelect({
        trigger: "#trigger6",
        title: "快递上门时间",
        wheels: [{ data: timeData }],
        callback: function(indexArr, data) {
          _this.time = data[0].id + " " + data[1].id;
          console.log(
            "获取的时间是：" + JSON.stringify(data[0].id + " " + data[1].id)
          );
        }
      });
    },
    onChange(index) {
      this.showList = false;
      this.chosenCoupon = index;
    },
    submit: function() {
      console.log("submit time=" + this.time);
      console.log(
        "submit time is before now=" + dayjs(this.time).isBefore(new Date())
      );
      if (this.time === "") {
        this.$dialog.alert({
          message: "请选择快递上门时间"
        });
      } else if (dayjs(this.time).isBefore(new Date())) {
        this.buildTimeOptions();
        this.$dialog.alert({
          message: "请重新选择快递上门时间"
        });
      } else {
        if (this.canRecover()) {
          this.createRecoverOrder({
            address_id: this.latestAddress.id,
            time: this.time
          }).then(res => {
            if (res.data.code && res.data.code === 500) {
              this.$dialog.alert({
                message: res.data.msg,
                center: true
              });
            } else {
              this.$router.replace("/wechat/recover_order/" + res.data.no);
            }
          });
        } else {
          this.$dialog.alert({
            message: "你所在的地区暂不收书",
            center: true
          });
        }
      }
    },
    canRecover: function() {
      return !(
        this.latestAddress.province === "内蒙古自治区" ||
        this.latestAddress.province === "海南省" ||
        this.latestAddress.province === "西藏自治区" ||
        this.latestAddress.province === "甘肃省" ||
        this.latestAddress.province === "青海省" ||
        this.latestAddress.province === "宁夏回族自治区" ||
        this.latestAddress.province === "新疆维吾尔自治区"
      );
    },
    ...mapActions("order", ["createRecoverOrder"])
  }
};
</script>
