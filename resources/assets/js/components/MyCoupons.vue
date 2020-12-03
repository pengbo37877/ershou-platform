<template>
  <div class="MyCoupons">
    <van-tabs
      v-model="active"
      swipeable
      title-active-color="#44B3DD"
      title-inactive-color="#333333"
      color="#44B3DD"
      line-height="2"
      line-width="40"
      sticky
      @change="change"
    >
      <div slot="nav-right">
        <div class="dian" v-show="showRemind"></div>
      </div>
      <van-tab title="已有">
        <div class="paddindBox">
          <div
            class="CouponsList"
            v-for="item in list"
            :key="item.id"
            v-show="item.enabled!=0 || Number(item.value)!=5"
          >
            <div class="listLeft box">
              <div
                class="conditions"
                :style="item.used==0&&isOverdue(item.not_after)==0&&item.enabled==1?background:''"
              >
                <div class="subtract">
                  ¥
                  <span>{{int(item.value)}}</span>
                </div>
                <div class="full" v-if="int(item.min_amount)==0">
                  <span>无门槛</span>
                </div>
                <div class="full" v-else>
                  <span>满{{int(item.min_amount)}}元可用</span>
                </div>
              </div>
              <div class="couponInfo">
                <div class="name">{{item.name}}</div>
                <div class="desc">{{item.description}}</div>
                <div class="date">
                  <span v-if="item.not_after">有效期至：{{createdAt(item.not_after)}}</span>
                </div>
              </div>
            </div>
            <div class="listRight">
              <div class="btn" v-if="item.used==0&&isOverdue(item.not_after)==0&&item.enabled==1">
                <router-link to="/wechat/shop" tag="div" class="can">立即使用</router-link>
              </div>
              <div class="btn" v-if="item.enabled==0" @click="showHint(item.nickname)">
                <div>
                  <img src="/images/image/wenhao.png" width="16" height="16" />
                </div>
              </div>
              <div class="btn" v-if="item.enabled==0">
                <div class="jihuo">
                  <img src="/images/image/weijihuo.png" alt width="68" />
                </div>
                <!-- <router-link to="/wechat/share_desc" tag="div" class="no">查看原因</router-link> -->
              </div>
              <div class="icon">
                <div class="icons" v-show="item.used==1 &&item.enabled==1">
                  <img src="/images/image/shiyong.png" alt width="69" height="71" />
                </div>
                <div
                  class="icons"
                  v-show="isOverdue(item.not_after)==1&&item.used==0 &&item.enabled==1"
                >
                  <img src="/images/image/guoqi.png" alt width="69" height="71" />
                </div>
              </div>
            </div>
          </div>
          <div class="noCoupons" v-if="list.length==0">
            <div class="noCouponBox">
              <img src="/images/image/noCoupon.png" width="128" height="78" />
              <div class="noCouponHint">您还没有现金券哦</div>
              <div class="goCoupon" @click="showQrCode">
                <img src="/images/image/goCoupon.png" />
                <span>去赚现金券</span>
              </div>
            </div>
          </div>
          <div class="goCoupon" @click="showQrCode" v-else>
            <img src="/images/image/goCoupon.png" />
            <span>去赚现金券</span>
          </div>
        </div>
      </van-tab>
      <van-tab title="领券">
        <div class="paddindBox">
          <div class="couponBox" v-show="showNewUserCoupon">
            <img src="/images/image/coupon0.png?date=2019-10-18" alt width="100%" />
            <div class="getCoupon" @click="getCoupon" v-if="getNewCoupon">立即领取</div>
            <div class="notCoupon" v-else>已领取</div>
          </div>
          <div class="couponBox">
            <img src="/images/image/coupon1.png?date=2019-10-18" alt width="100%" />
            <div class="getCoupon" @click="showQrCode">立即领取</div>
          </div>
          <div class="couponBox">
            <img src="/images/image/coupon2.png?date=2019-10-9" alt width="100%" />
            <router-link class="getCoupon" tag="div" to="/wechat/scan">立即领取</router-link>
          </div>
        </div>
      </van-tab>
      <van-tab title="规则">
        <div class="paddindBox">
          <div class="accessBox">
            <div class="publicList box">
              <div class="point">
                <div class="pointBox"></div>
              </div>
              <div class="content">
                <div
                  class="txt"
                >“新用户”界定：从未在回流鱼下单（买书/卖书）的用户为“新用户”，均可领取新人奖励（5元包邮券、5元增值券）。新人券有效期为领取后7天，过期则会失效。</div>
              </div>
            </div>
            <div class="publicList box">
              <div class="point">
                <div class="pointBox"></div>
              </div>
              <div class="content">
                <div
                  class="txt"
                >成功邀请一位好友，20元抵扣券会即时到账，你邀请的好友成功下单后（买书订单状态为“已出库”，卖书订单状态为“已完成”），该券方被激活可用。20元抵扣券需买书满40元可用。每位用户最多可激活10张抵扣券。有效期为激活后30天。（如发现有用户利用多账号套取优惠券，回流鱼有权对该用户所获得的券予以作废处理。）</div>
              </div>
            </div>
            <div class="publicList box">
              <div class="point">
                <div class="pointBox"></div>
              </div>
              <div class="content">
                <div
                  class="txt"
                >5元包邮券可用于买书下单时抵扣5元邮费，订单满20元可用。（新疆、西藏、青海、甘肃、内蒙、宁夏、海南等地区因邮费超出5元，可用5元包邮券抵扣部分运费）， 包邮券有效期为7天。</div>
              </div>
            </div>
            <div class="publicList box">
              <div class="point">
                <div class="pointBox"></div>
              </div>
              <div class="content">
                <div class="txt">单个订单限用一张现金券，现金券不能叠加使用。</div>
              </div>
            </div>
            <div class="publicList box">
              <div class="point">
                <div class="pointBox"></div>
              </div>
              <div class="content">
                <div class="txt">回流鱼对现金券规则具有最终解释权，如有疑问可在「回流鱼」公众号咨询客服。</div>
              </div>
            </div>
          </div>
        </div>
      </van-tab>
    </van-tabs>
    <div class="shareModal" @click.stop="showModal" v-show="showShare">
      <div class="shareIcon">
        <img src="/images/image/jt.png" width="78" height="76" />
      </div>
      <div class="shareInfo center">点击分享给好友</div>
    </div>
    <!-- 分享二维码 -->
    <van-popup v-model="show" position="top">
      <div class="qr-image" :style="{width: '100%'}">
        <div class="qr-tip">
          长按保存，然后分享给你的朋友
          <div class="qr-close" @click="show=false">
            <van-icon name="close" />
          </div>
        </div>
        <img :src="qrImage" alt :style="{width:'85%'}" />
      </div>
    </van-popup>
  </div>
</template>

<script>
import { Toast, Dialog } from "vant";
import { mapState, mapGetters, mapActions } from "vuex";
export default {
  data() {
    return {
      list: [],
      overdue: [],
      getNewCoupon: false,
      hasCoupon: false,
      showShare: false,
      show: false,
      qrImage: "",
      loading: false,
      showNewUserCoupon: false,
      active: 0,
      background: {
        background:
          "linear-gradient(318deg,rgba(75, 187, 219, 1) 0%,rgba(39, 136, 218, 1) 100%)"
      }
    };
  },
  created() {
    console.log(this.$route);
    if (this.$route.query.from == "newCoupon") {
      this.active = 1;
    }
    if (this.active == 0 && this.showRemind) {
      this.clearRemind();
    }
    this.$store.dispatch("user/getUser").then(res => {
      // 如果拿不到用户，就显示一个不可关闭的对话框
      var user = res.data;
      console.log(user);
      if (user === "") {
        this.$router.replace("/wechat/shop");
      } else if (user.subscribe === 0) {
        this.$router.replace("/wechat/shop");
      }
    });
    axios.get("/wx-api/get_new_user_coupons").then(res => {
      console.log(res.data.new_user);
      if (res.data.new_user) {
        this.showNewUserCoupon = true;
      }
    });
    this.getAllCoupon();
    this.wxApi.wxConfig("", "");
  },
  computed: {
    ...mapState({
      showRemind: state => state.my.showRemind
    })
  },
  methods: {
    int(num) {
      return parseInt(num);
    },
    createdAt: function(date) {
      var date = dayjs(date).format("YYYY-MM-DD");
      date = date.replace(/-/g, ".");
      return date;
    },
    isOverdue: function(not_after) {
      if (not_after) {
        var afterTime = new Date(Date.parse(not_after.replace(/-/g, "/")));
        var curDate = new Date();
        // console.log(afterTime, curDate);
        // console.log(curDate - afterTime);
        if (afterTime >= curDate && not_after) {
          return 0;
        } else {
          return 1;
        }
      } else {
        return 1;
      }
    },
    getAllCoupon() {
      axios.get("/wx-api/get_coupons").then(res => {
        console.log(res.data);
        this.list = res.data;
        let getNewCoupon = this.list.filter(function(currentValue, index) {
          return (
            parseInt(currentValue.value) == 5 &&
            parseInt(currentValue.enabled) == 0
          );
        });
        let hasCoupon = this.list.filter(function(currentValue, index) {
          return parseInt(currentValue.enabled) == 1;
        });
        if (getNewCoupon.length > 0 || this.list.length == 0) {
          this.getNewCoupon = true;
        } else {
          this.getNewCoupon = false;
        }
        if (hasCoupon.length > 0) {
          this.hasCoupon = true;
        } else {
          this.hasCoupon = false;
        }
        console.log(this.getNewCoupon);
      });
    },
    getCoupon() {
      axios.get("/wechat/get_new_user_coupon").then(res => {
        console.log(res.data);
        if (res.data.status) {
          this.getAllCoupon();
          Toast.success(res.data.message);
          this.getNewCoupon = false;
        } else {
          Toast(res.data.message);
        }
      });
    },
    showModal() {
      let flag = this.showShare;
      this.showShare = !flag;
    },
    showQrCode: function() {
      if (this.qrImage) {
        this.show = true;
        return;
      }
      Toast.loading("生成图片中...");
      axios.get("/wx-api/get_share_qr_image").then(res => {
        this.qrImage = res.data;
        this.show = true;
        Toast.clear();
      });
    },
    showHint(nickname) {
      Dialog.confirm({
        message: "你所邀请的好友[ "+ nickname+" ]还未买书/卖书，可以催TA下单。",
        messageAlign:'left',
        showCancelButton:false
      });
    },
    change(index) {
      if (index == 0 && this.showRemind) {
        this.clearRemind();
      }
    },
    ...mapActions("my", ["getShowRemind", "clearRemind"])
  }
};
</script>

<style scoped lang='scss'>
.MyCoupons {
  width: 100%;
  background: #f4f4f4;
  min-height: 100vh;
  position: relative;
  .dian {
    width: 5px;
    height: 5px;
    border-radius: 50%;
    background: red;
    position: absolute;
    left: 24%;
    top: 20%;
  }
  .paddindBox {
    width: 100%;
    padding: 13px 15px;
    box-sizing: border-box;
    .couponBox {
      margin-bottom: 7px;
      width: 100%;
      padding: 0 8px;
      box-sizing: border-box;
      position: relative;
      .getCoupon {
        position: absolute;
        bottom: 23px;
        left: 50%;
        transform: translateX(-50%);
        width: 136px;
        height: 35px;
        line-height: 35px;
        border-radius: 10px;
        background: #44b3dd;
        font-size: 14px;
        font-family: PingFang-SC;
        font-weight: bold;
        color: rgba(255, 255, 255, 1);
        text-align: center;
      }
      .notCoupon {
        position: absolute;
        bottom: 23px;
        left: 50%;
        transform: translateX(-50%);
        width: 136px;
        height: 35px;
        line-height: 35px;
        border-radius: 10px;
        background: #b9b9b9;
        font-size: 14px;
        font-family: PingFang-SC;
        font-weight: bold;
        color: rgba(255, 255, 255, 1);
        text-align: center;
      }
    }
    .accessBox {
      width: 100%;
      background: rgba(255, 255, 255, 1);
      border-radius: 8px;
      padding: 26px 22px;
      box-sizing: border-box;
      margin-bottom: 10px;
      .publicList {
        width: 100%;
        margin-bottom: 25px;
        .point {
          flex: 1;
          display: flex;
          justify-content: center;
          .pointBox {
            width: 6px;
            height: 6px;
            background: rgba(68, 179, 221, 1);
            border-radius: 50%;
            margin-top: 5px;
          }
        }
        .content {
          flex: 10;
          font-size: 14px;
          font-family: PingFang-SC;
          color: rgba(51, 51, 51, 1);
          line-height: 20px;
          .title {
            font-weight: bold;
            color: #333333;
          }
          .txt {
            font-weight: 400;
          }
          .btnBox {
            width: 100%;
            display: flex;
            justify-content: flex-end;
          }
        }
      }
      .publicList:last-child {
        margin-bottom: 0;
      }
    }
    .accessBox:first-child {
      margin-top: 14px;
    }
    .classfiyBox {
      width: 100%;
      padding: 17px 20px;
      box-sizing: border-box;
      background: rgba(255, 255, 255, 1);
      border-radius: 8px;
      margin-bottom: 10px;
      .txtTop {
        font-size: 14px;
        font-family: PingFang-SC;
        color: rgba(51, 51, 51, 1);
        line-height: 20px;
        margin-bottom: 25px;
        font-weight: 400;
      }
      .publicList {
        width: 100%;
        margin-bottom: 25px;
        .point {
          flex: 1;
          display: flex;
          justify-content: flex-start;
          .pointBox {
            width: 6px;
            height: 6px;
            background: rgba(68, 179, 221, 1);
            border-radius: 50%;
            margin-top: 5px;
          }
        }
        .content {
          flex: 17;
          font-size: 14px;
          font-family: PingFang-SC;
          color: rgba(51, 51, 51, 1);
          line-height: 20px;
          .title {
            font-weight: bold;
            color: #333333;
          }
          .txt {
            font-weight: 400;
          }
        }
      }
      .publicList:last-child {
        margin-bottom: 0;
      }
    }
    .classfiyBox:first-child {
      margin-top: 14px;
    }
  }
  .CouponsTop {
    width: 100%;
    font-size: 14px;
    font-family: PingFang-SC;
    color: rgba(153, 153, 153, 1);
    span {
      color: #47b4de;
    }
    .instructions {
      img {
        vertical-align: sub;
        margin-left: 5px;
      }
    }
  }
  .CouponsList {
    width: 100%;
    padding: 11px 16px;
    box-sizing: border-box;
    background: rgba(255, 255, 255, 1);
    border-radius: 10px;
    margin-bottom: 15px;
    display: flex;
    justify-content: space-between;
    position: relative;
    overflow: hidden;
    .conditions {
      width: 85px;
      height: 80px;
      text-align: center;
      padding: 13px 0;
      box-sizing: border-box;
      border-radius: 5px;
      background: #b0afb1;
      .subtract {
        font-size: 12px;
        font-family: PingFang-SC;
        color: rgba(255, 255, 255, 1);
        span {
          font-size: 30px;
          font-family: PingFang-SC;
          font-weight: bold;
          color: rgba(255, 255, 255, 1);
          margin-left: 5px;
        }
      }
      .full {
        font-size: 12px;
        font-family: PingFang-SC;
        color: rgba(255, 255, 255, 1);
        line-height: 17px;
      }
    }
    .effective {
      background: linear-gradient(
        318deg,
        rgba(75, 187, 219, 1) 0%,
        rgba(39, 136, 218, 1) 100%
      );
    }
    .couponInfo {
      margin-left: 13px;
      .name {
        font-size: 15px;
        font-family: PingFang-SC;
        font-weight: bold;
        color: rgba(51, 51, 51, 1);
      }
      .desc {
        font-size: 12px;
        font-family: PingFang-SC;
        color: rgba(153, 153, 153, 1);
        margin: 13px 0;
      }
      .date {
        font-size: 12px;
        font-family: PingFang-SC;
        color: rgba(153, 153, 153, 1);
      }
    }
    .listRight {
      display: flex;
      justify-content: flex-end;
      align-items: flex-end;
      .btn {
        .can {
          width: 70px;
          height: 26px;
          text-align: center;
          line-height: 26px;
          border-radius: 15px;
          border: 1px solid rgba(71, 180, 222, 1);
          color: rgba(71, 180, 222, 1);
          font-size: 12px;
          font-family: PingFang-SC;
        }
        .no {
          width: 70px;
          height: 26px;
          text-align: center;
          line-height: 26px;
          border-radius: 15px;
          font-size: 12px;
          font-family: PingFang-SC;
          border: 1px solid rgba(152, 152, 152, 1);
          color: rgba(152, 152, 152, 1);
        }
        .jihuo {
          position: absolute;
          top: 0;
          right: 0;
        }
      }
      .icons {
        position: absolute;
        right: 0;
        top: 50%;
        transform: translateY(-50%);
      }
    }
  }
  .goCoupon {
    width: 150px;
    height: 46px;
    background: rgba(65, 176, 220, 1);
    box-shadow: 0px 6px 10px 0px rgba(65, 176, 220, 0.3);
    border-radius: 15px;
    margin: 0 auto;
    text-align: center;
    display: flex;
    justify-content: center;
    align-items: center;
    span {
      font-size: 15px;
      font-family: PingFang-SC-Bold, PingFang-SC;
      font-weight: bold;
      color: rgba(255, 255, 255, 1);
    }
    img {
      width: 16px;
      height: 16px;
      margin-right: 5px;
    }
  }
  .noCoupons {
    width: 100%;
    margin-top: 50%;
    transform: translateY(-50%);
    color: #666666;
    text-align: center;
    font-size: 15px;
    font-family: PingFang-SC;
    .noCouponBox {
      .noCouponHint {
        font-size: 15px;
        font-family: PingFang-SC-Medium, PingFang-SC;
        color: rgba(153, 153, 153, 1);
        line-height: 21px;
        text-align: center;
        margin: 15px auto 20px auto;
      }
      .goCoupon {
        width: 150px;
        height: 46px;
        background: rgba(65, 176, 220, 1);
        box-shadow: 0px 6px 10px 0px rgba(65, 176, 220, 0.3);
        border-radius: 15px;
        margin: 0 auto;
        text-align: center;
        display: flex;
        justify-content: center;
        align-items: center;
        span {
          font-size: 15px;
          font-family: PingFang-SC-Bold, PingFang-SC;
          font-weight: bold;
          color: rgba(255, 255, 255, 1);
        }
        img {
          width: 16px;
          height: 16px;
          margin-right: 5px;
        }
      }
    }
  }
  .shareModal {
    position: fixed;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    z-index: 99;
    background: rgba($color: #000000, $alpha: 0.7);
    .shareIcon {
      position: absolute;
      top: 0;
      right: 20px;
    }
    .shareInfo {
      margin-top: 30%;
      color: #ffffff;
      font-size: 15px;
      font-family: PingFang-SC;
    }
  }
  /deep/ .van-tab span {
    font-weight: bold;
  }
  /deep/ .van-tabs__nav {
    background: #eeeeee;
  }
  .qr-image {
    text-align: center;
  }
  .qr-tip {
    background-color: #ffe1e1;
    color: black;
    font-size: 14px;
    line-height: 40px;
    height: 40px;
    text-align: center;
    position: relative;
  }
  .qr-close {
    position: absolute;
    right: 15px;
    top: 0;
  }
}
.MyCoupons /deep/ .van-notify {
  text-align: left;
  padding: 6px 10px;
}
</style>
