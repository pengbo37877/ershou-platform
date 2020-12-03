<template>
  <div>
    <loading :loading="loading"></loading>
    <recover-steps
      :screen-width="screenWidth"
      v-if="recoverSaleItems.length===0 && rejectSaleItems.length===0"
    ></recover-steps>
    <div class="recover-second" v-if="recoverSaleItems.length>0">
      <recover-book
        :item="item"
        :screen-width="screenWidth"
        v-for="item in recoverSaleItems"
        :key="item.id"
      ></recover-book>
    </div>
    <div
      class="recover-warning"
      :class="{'recover-more':(recoverSaleItems.length>3 && rejectSaleItems.length===0)}"
      v-if="recoverSaleItems.length>0"
    >
      <router-link to="/wechat/review_standard" style="text-decoration:underline;">回流鱼审核标准细则</router-link>
    </div>
    <div class="recover-third" v-if="rejectSaleItems.length>0">
      <div class="recover-reject-text">
        <div class="recover-reject-left">以下书回流鱼暂时不收</div>
        <div class="recover-reject-right">收取时会通知你</div>
      </div>
      <recover-book
        :item="item"
        :screen-width="screenWidth"
        v-for="item in rejectSaleItems"
        :key="item.id"
      ></recover-book>
    </div>
    <div style="width: 100%;height: 150px;"></div>
    <div class="recover-bottom-bar">
      <div
        class="recover-buttons"
        :style="{width: screenWidth-60 + 'px'}"
        :class="{'no-items':saleItems.length==0}"
      >
        <div class="scanMai" @click="scan">
          <img src="/images/image/scanIcon.png" alt width="16" />
          <span>扫码卖书</span>
        </div>
        <div class="isbnBtn" @click="showIsbn">手动输入</div>
        <!-- <div class="recover-scan-btn" @click="scan">
                    <van-icon name="scan" size="15px"/>
                    <div style="margin-left:5px;">扫码卖书</div>
                </div>
                <div class="recover-hand-btn" @click="showIsbn">
                    <van-icon name="edit" size="15px"/>
                    <div style="margin-left:5px">手动输入</div>
        </div>-->
      </div>
      <div class="recover-bar-msg" v-if="recoverSaleItems.length>0">继续扫描其他书，满8本或总价40元即可下单</div>
      <div class="scan-footer" v-if="recoverSaleItems.length>0">
        <div class="scan-foot-left">
          共
          <span class="scan-books-count">{{recoverSaleItems.length}}</span> 本，最高能卖
          <span class="scan-books-price">￥{{totalPrice}}</span>
          <br />
          <span
            style="font-size: 11px;color: #888888"
          >价格预估区间为：{{Number(Number(totalPrice)*0.8).toFixed(2)}}-{{totalPrice}}</span>
        </div>
        <div class="scan-foot-right scan-next" :style="{width:screenWidth/4+'px'}" v-if="next">
          <router-link
            to="/wechat/recover_invoice"
            style="color:white;font-weight:600;text-decoration:none;"
          >下一步</router-link>
        </div>
        <div
          class="scan-foot-right scan-foot-left-disable"
          :style="{width:screenWidth/4+'px'}"
          v-else
        >下一步</div>
      </div>
    </div>
    <van-dialog
      title="手动输入条形码"
      v-model="dialogVisible"
      confirmButtonColor="#227E2C"
      @confirm="ok"
      :closeOnClickOverlay="true"
    >
      <p style="color:#555;font-size:14px;font-weight:300;text-align:center;">13位 ISBN 码，不含 - 符号</p>
      <p style="color:#555;font-size:14px;font-weight:300;text-align:center;">或者回流鱼的 hly 开头的条码</p>
      <div class="scan-dialog-items">
        <van-cell-group>
          <van-field v-model="isbn" placeholder="请输入ISBN" clearable center />
        </van-cell-group>
      </div>
    </van-dialog>
    <van-dialog
      v-model="outerVisible"
      title="回流鱼暂时不收这本书"
      show-cancel-button
      cancel-button-text="好的"
      confirm-button-text="这本书该收"
      @confirm="innerVisible = true"
    ></van-dialog>
    <van-dialog
      v-model="innerVisible"
      title="应该收的理由"
      show-cancel-button
      confirm-button-text="提交"
      @confirm="irecoverReport"
    >
      <div class="types" :style="typesStyle">
        <div
          class="type"
          :style="typeStyle"
          :class="{'type-active': type===0}"
          @click="type = 0"
        >内容好</div>
        <div
          class="type"
          :style="typeStyle"
          :class="{'type-active': type===1}"
          @click="type = 1"
        >绝版书</div>
        <div
          class="type"
          :style="typeStyle"
          :class="{'type-active': type===2}"
          @click="type = 2"
        >系列书</div>
      </div>
      <div class="reason">
        <van-cell-group>
          <van-field
            v-model="reason"
            label
            type="textarea"
            placeholder="其他理由..."
            rows="5"
            autosize
          />
        </van-cell-group>
      </div>
      <div class="report-desc">反馈后我们会重新审核这本书，如果收购的话会通知你，感谢你帮助我们优化审核算法。</div>
    </van-dialog>

    <van-dialog v-model="cjShow" :before-close="beforeClose">
      <div style="padding: 0 20px">
        <p>春节期间可以正常买书</p>

        <p>买书：1月28日17点后~2月10日的买书订单，需要到2月11日（正月初七）统一发货。</p>
        <p>卖书：2月1日~2月10日，由于快递原因，无法上门收书，需要到2月11日（正月初七）恢复正常。</p>

        <p>祝大家新年快乐，年年有鱼~</p>
      </div>
    </van-dialog>

    <van-dialog
      v-model="currencyVisible"
      @confirm="currencyConfirm"
      :title="currencyBook.name"
      :closeOnClickOverlay="true"
    >
      <p class="currency-desc">请帮助回流鱼完善这本书的价格信息</p>
      <div class="currency-wrap">
        <div class="currency-unit-select">
          <select v-model="currencyUnit" class="currency-select">
            <option value="人民币">人民币</option>
            <option value="美元">美元</option>
            <option value="欧元">欧元</option>
            <option value="英镑">英镑</option>
            <option value="日元">日元</option>
            <option value="港币">港币</option>
            <option value="新台币">新台币</option>
          </select>
        </div>
        <div class="currency-number-input">
          <van-cell-group>
            <van-field
              v-model="currencyNumber"
              placeholder="请输入价格"
              type="number"
              clearable
              ref="currency"
            />
          </van-cell-group>
        </div>
      </div>
    </van-dialog>
    <!-- <van-popup v-model="popup" safe-area-inset-bottom round closeable position="bottom" :style="{ height: '80%' }" >
            <div class="books">
                <div class="hint">此书有多种版本，点击选择对应的版本</div>
                <div class="bookinfo" @click="addBook(books)">
                    <img :src="books.book.cover_replace" width="92" height="130">
                    <div class="bookName">{{books.book.name}}</div>
                    <div class="bookAuthor">{{books.book.author}}</div>
                </div>
                <div class="bookinfo">
                    <img :src="books.book.cover_replace" width="92" height="130">
                    <div class="bookName">{{books.book.name}}</div>
                    <div class="bookAuthor">{{books.book.author}}</div>
                </div>
                <div class="bookinfo">
                    <img :src="books.book.cover_replace" width="92" height="130">
                    <div class="bookName">{{books.book.name}}</div>
                    <div class="bookAuthor">{{books.book.author}}</div>
                </div>
                <div class="bookinfo">
                    <img :src="books.book.cover_replace" width="92" height="130">
                    <div class="bookName">{{books.book.name}}</div>
                    <div class="bookAuthor">{{books.book.author}}</div>
                </div>
                <div class="bookinfo">
                    <img :src="books.book.cover_replace" width="92" height="130">
                    <div class="bookName">{{books.book.name}}</div>
                    <div class="bookAuthor">{{books.book.author}}</div>
                </div>
                <div class="bookinfo">
                    <img :src="books.book.cover_replace" width="92" height="130">
                    <div class="bookName">{{books.book.name}}</div>
                    <div class="bookAuthor">{{books.book.author}}</div>
                </div>
                <div class="bookinfo">
                    <img :src="books.book.cover_replace" width="92" height="130">
                    <div class="bookName">{{books.book.name}}</div>
                    <div class="bookAuthor">{{books.book.author}}</div>
                </div>
                <div class="bookinfo">
                    <img :src="books.book.cover_replace" width="92" height="130">
                    <div class="bookName">{{books.book.name}}</div>
                    <div class="bookAuthor">{{books.book.author}}</div>
                </div>
                <div class="bookinfo">
                    <img :src="books.book.cover_replace" width="92" height="130">
                    <div class="bookName">{{books.book.name}}</div>
                    <div class="bookAuthor">{{books.book.author}}</div>
                </div>
            </div>
    </van-popup>-->
    <bottom-bar2 index="1"></bottom-bar2>
  </div>
</template>

<style scoped lang='scss'>
body {
  background-color: white;
  margin: 0;
}
a {
  text-decoration: none;
  color: #ff4848;
}
a:visited {
  text-decoration: none;
  color: #ff4848;
}
a:active {
  text-decoration: none;
  color: #ff4848;
}
a:link {
  text-decoration: none;
  color: #ff4848;
}
a:hover {
  text-decoration: none;
  color: #ff4848;
}
.types {
  display: inline-block;
  white-space: nowrap;
  margin: 10px 20px;
}
.type {
  display: inline-block;
  text-align: center;
  padding: 5px 0;
  border: 0.5px solid #eee;
  border-radius: 20px;
  font-size: 15px;
}
.type-active {
  border: 0.5px solid #3d404a;
}
.reason {
  margin: 10px 20px;
}
.report-desc {
  font-size: 15px;
  font-weight: 300;
  margin: 10px 25px;
  text-align: center;
}
.suite-icon {
  text-align: center;
  margin: 10px 20px;
  color: #bbbbbb;
}
.suite-desc {
  font-size: 15px;
  font-weight: 300;
  margin: 10px 25px;
  text-align: center;
}
.scan-dialog-items {
  margin: 10px 0 0 0;
}
.scan-cancel-btn {
  margin: 20px;
  background-color: white;
  color: #888888;
  font-size: 14px;
  width: 50%;
  height: 40px;
  line-height: 40px;
  border: 0.5px solid #cccccc;
  border-radius: 4px;
  text-align: center;
}
.scan-ok-btn {
  margin: 20px;
  background-color: #16a467;
  color: white;
  font-size: 14px;
  width: 50%;
  height: 40px;
  line-height: 40px;
  border-radius: 4px;
  text-align: center;
}
.recover-second {
  display: flex;
  flex-direction: column;
}
.recover-warning {
  background: #ffe4e4;
  color: #555555;
  text-align: center;
  font-size: 14px;
  margin-bottom: 10px;
  padding: 10px 5px;
}
.recover-more {
  margin-bottom: 150px;
}
.recover-third {
  display: flex;
  flex-direction: column;
}
.recover-reject-text {
  display: flex;
  flex-direction: row;
  justify-content: space-between;
  align-items: center;
  padding: 5px 20px;
  border-bottom: 0.5px solid #ddd;
}
.recover-reject-left {
  font-size: 0.875em;
  color: #333;
  text-align: left;
}
.recover-reject-right {
  font-size: 0.875em;
  color: #ff4848;
  opacity: 0.8;
  text-align: right;
}
.has-reject-items {
  padding-bottom: 50px;
}
.recover-bottom-bar {
  position: fixed;
  left: 0;
  bottom: 50px;
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 15px 0;
  background: #ffffff;
}
.recover-buttons {
  text-align: center;
//   display: flex;
//   flex-direction: row;
//   justify-content: space-around;
//   padding: 10px 20px;
//   align-items: center;
    margin-bottom: 10px;
  .scanMai {
    width: 274px;
    height: 46px;
    line-height: 46px;
    text-align: center;
    background: #41b0dc;
    border-radius: 15px;
    font-size: 15px;
    font-family: PingFang-SC;
    font-weight: 700;
    color: #fff;
    border-color: #41b0dc;
    margin: 0 auto;
    display: flex;
    justify-content: center;
    align-items: center;
    img{
        margin-right: 12px;
    }
  }
  .isbnBtn{
    font-size:15px;
    font-family:PingFangSC-Regular,PingFangSC;
    font-weight:400;
    color:rgba(65,176,220,1);
    line-height:21px;
    margin-top: 18px;
    text-align: center;
    text-decoration: underline;
  }
}
.no-items {
  margin-bottom: 20px;
}
.recover-scan-btn {
  display: flex;
  flex-direction: row;
  justify-content: space-around;
  align-items: center;
  border-radius: 20px;
  background-color: #3d404a;
  color: #fff;
  font-size: 14px;
  padding: 10px 20px;
  -webkit-box-shadow: 3px 3px 10px 1px rgba(221, 221, 221, 1);
  -moz-box-shadow: 3px 3px 10px 1px rgba(221, 221, 221, 1);
  box-shadow: 3px 3px 10px 1px rgba(221, 221, 221, 1);
}
.recover-hand-btn {
  display: flex;
  flex-direction: row;
  justify-content: space-around;
  align-items: center;
  border-radius: 20px;
  border: 0.5px solid #fcfcfc;
  color: #3d404a;
  background-color: #fff;
  font-size: 14px;
  padding: 10px 20px;
  -webkit-box-shadow: 3px 3px 10px 1px rgba(221, 221, 221, 1);
  -moz-box-shadow: 3px 3px 10px 1px rgba(221, 221, 221, 1);
  box-shadow: 3px 3px 10px 1px rgba(221, 221, 221, 1);
}
.recover-bar-msg {
  height: 26px;
  line-height: 26px;
  width: 100%;
  background-color: #feecec;
  color: #f08f91;
  padding-left: 20px;
  font-size: 13px;
  font-weight: 300;
}
.scan-footer {
  height: 60px;
  width: 100%;
  display: flex;
  flex-direction: row;
  justify-content: space-around;
  align-items: center;
  background-color: #fff;
  padding: 0 10px;
  border-top: 0.5px solid #eee;
}
.scan-foot-left {
  color: #3d404a;
  font-size: 14px;
  font-weight: 300;
}
.scan-books-count {
  color: #555;
}
.scan-books-price {
  font-size: 18px;
  color: #ff4848;
  font-weight: 500;
}
.scan-foot-left-disable {
  opacity: 0.3;
}
.scan-foot-right {
  background-color: #3d404a;
  text-align: center;
  margin-left: 20px;
  font-size: 14px;
  border-radius: 3px;
  color: white;
  height: 38px;
  line-height: 38px;
}
.scan-next {
  opacity: 1;
  background-color: #3d404a;
  color: white;
}
.currency-wrap {
  display: flex;
  flex-direction: row;
  justify-content: flex-start;
  align-items: center;
  padding: 0 5px;
  width: 100%;
}
.currency-desc {
  width: 100%;
  padding: 10px 0;
  font-size: 14px;
  text-align: center;
  color: #555555;
}
.currency-unit-select {
  width: 40%;
  text-align: right;
}
.currency-select {
  font-size: 16px;
}
.currency-number-input {
  width: 60%;
}
.books {
  width: 100%;
  padding: 20px;
  box-sizing: border-box;
  display: flex;
  flex-wrap: wrap;
  .hint {
    width: 100%;
    text-align: center;
    font-family: PingFang-SC;
    font-size: 16px;
    color: #111111;
    margin-bottom: 10px;
  }
  .bookinfo {
    width: 50%;
    text-align: center;
    margin-bottom: 10px;
    img {
      object-fit: cover;
    }
    .bookName {
      width: 100%;
      font-family: PingFang-SC;
      font-size: 14px;
      color: #333333;
      text-overflow: ellipsis;
      white-space: nowrap;
      overflow: hidden;
    }
    .bookAuthor {
      width: 100%;
      font-family: PingFang-SC;
      font-size: 14px;
      color: #666666;
      text-overflow: ellipsis;
      white-space: nowrap;
      overflow: hidden;
    }
  }
}
</style>
<script>
import wx from "weixin-js-sdk";
import { mapState, mapGetters, mapActions } from "vuex";
import RecoverSteps from "./RecoverSteps";
import RecoverBook from "./RecoverBook";
import Loading from "./Loading";
import ShudanTabs from "./ShudanTabs";
import BottomBar2 from "./BottomBar2";
export default {
  data() {
    return {
      loading: false,
      screenWidth: 0,
      isbn: "",
      dialogVisible: false,
      outerVisible: false,
      innerVisible: false,
      suitDialogVisible: false,
      type: -1,
      book: 0,
      reason: "",
      cjShow: false,
      currencyVisible: false,
      currencyBook: "",
      currencyUnit: "人民币",
      currencyNumber: "",
      popup: false,
      bol: true,
      books: {
        book: {
          cover_replace: "",
          name: "",
          author: ""
        }
      } //扫码后显示的书
    };
  },
  props: ["user"],
  computed: {
    typesStyle: function() {
      return {
        width: this.screenWidth * 0.95 - 40 + "px"
      };
    },
    typeStyle: function() {
      return {
        width: (this.screenWidth * 0.95 - 55) / 3 + "px"
      };
    },
    next: function() {
      return this.recoverSaleItems.length >= 8 || this.totalPrice >= 40;
    },
    ...mapGetters("sale2hly", {
      totalPrice: "totalPrice",
      recoverSaleItems: "recoverSaleItems",
      rejectSaleItems: "rejectSaleItems"
    }),
    ...mapState({
      user: state => state.user.user,
      saleItems: state => state.sale2hly.saleItems
    })
  },
  created: function() {
    this.$store.dispatch("user/getUser").then(res => {
      // 如果拿不到用户，就显示一个不可关闭的对话框
      var user = res.data;
      if (user === "") {
        this.$router.replace("/wechat/shop");
      } else if (user.subscribe === 0) {
        this.$router.replace("/wechat/shop");
      }
    });
    this.loading =true;
    this.$store.dispatch("sale2hly/getBooksForRecover").then(res => {
      // this.$store.dispatch('sale2hly/getBooksWithoutCounting');
      this.loading =false;
    });
  },
  mounted: function() {
    this.$nextTick(() => {
      window.scrollTo(0, 1);
      window.scrollTo(0, 0);
    });
    this.screenWidth =
      window.innerWidth ||
      document.documentElement.clientWidth ||
      document.body.clientWidth;
    // this.wxConfig();
  },
  activated: function() {
    this.wxApi.wxConfig("", "scan");
  },
  methods: {
    wxConfig: function() {
      var _this = this;
      var url = window.localStorage.getItem("url");
      axios
        .post("/wx-api/config", {
          url: "scan"
        })
        .then(response => {
          console.log(response);
          wx.config(response.data);
          wx.error(res => {
            axios.post("/wx-api/create_client_error", {
              user_id: _this.user.id,
              error: JSON.stringify(reponse.data) + JSON.stringify(res),
              url: "/wechat/scan"
            });
            setTimeout(function() {
              _this.wxConfig();
            }, 2000);
          });
          wx.ready(() => {
            console.log("ready");
            wx.onMenuShareAppMessage({
              title: "回流鱼 - 二手循环书店", // 分享标题
              desc: "阅读不孤读", // 分享描述
              link: url + "/wechat/scan", // 分享链接，该链接域名或路径必须与当前页面对应的公众号JS安全域名一致
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
              link: url + "/wechat/scan", // 分享链接，该链接域名或路径必须与当前页面对应的公众号JS安全域名一致
              imgUrl: url + "/images/logo_main.png", // 分享图标
              success: function() {
                // 用户点击了分享后执行的回调函数
                console.log("分享成功");
              }
            });
          });
        });
    },
    scan: function() {
      var _this = this;
      this.loading = true;
      wx.scanQRCode({
        needResult: 1, // 默认为0，扫描结果由微信处理，1则直接返回扫描结果，
        scanType: ["qrCode", "barCode"], // 可以指定扫二维码还是一维码，默认二者都有
        success: function(res) {
          if(res.resultStr.indexOf(',')==-1){
              _this.isbn = res.resultStr; // 当needResult 为 1 时，扫码返回的结果
          }else{
              _this.isbn = res.resultStr.split(",")[1]; // 当needResult 为 1 时，扫码返回的结果
          }
          _this.addBookForSale(_this.isbn).then(res => {
            _this.loading = false;
            console.log(res.data);
            if (res.data.code && res.data.code === 501) {
              _this.currencyVisible = true;
              _this.currencyBook = res.data.book;
              _this.$refs.currency.focus();
            } else if (res.data.code && res.data.code === 500) {
              _this.$dialog.alert({
                message: res.data.msg
              });
            } else if (res.data.show === 0) {
              _this.$dialog.alert({
                title: "回流鱼不收这本书"
              });
            } else if (res.data.can_recover === 0) {
              _this.book = res.data.book;
              _this.$dialog.alert({
                title: "回流鱼暂时不收这本书"
              });
            } else if (
              res.data.can_recover === 1 &&
              res.data.book.volume_count > 1
            ) {
              // _this.suitDialogVisible = true;
              _this.$dialog.alert({
                title: "提醒",
                message:
                  "请在打包时将套装书的所有单册绑放在一起或放在一个袋子里寄出"
              });
            } else {
              _this.isbn = "";
            }
          });
          document.body.scrollTop = 0;
          document.documentElement.scrollTop = 0;
        }
      });
    },
    showIsbn: function() {
      this.dialogVisible = true;
    },
    ok: function() {
      var _this = this;
      _this.loading = true;
      this.addBookForSale(this.isbn).then(res => {
        console.log(res.data);
        _this.loading = false;
        if (res.data.code && res.data.code === 501) {
          _this.currencyVisible = true;
          _this.currencyBook = res.data.book;
          _this.$refs.currency.focus();
        } else if (res.data.code && res.data.code === 500) {
          _this.$dialog.alert({
            message: res.data.msg
          });
        } else if (res.data.can_recover === 0) {
          _this.book = res.data.book;
          // _this.outerVisible = true;
          _this.$dialog.alert({
            title: "回流鱼暂时不收这本书"
          });
        } else if (
          res.data.can_recover === 1 &&
          res.data.book.volume_count > 1
        ) {
          // _this.suitDialogVisible = true;
          _this.$dialog.alert({
            title: "提醒",
            message:
              "请在打包时将套装书的所有单册绑放在一起或放在一个袋子里寄出"
          });
        } else {
          _this.isbn = "";
          // _this.books =res.data;
          // _this.popup =true;
        }
      });
      this.dialogVisible = false;
      document.body.scrollTop = 0;
      document.documentElement.scrollTop = 0;
    },
    // addBook:function(book){
    //     this.addBookForSaleArray(book).then(()=>{
    //         this.popup=false;
    //     })
    // },
    addBookForRecoverByIsbn: function(isbn) {
      var _this = this;
      _this.loading = true;
      this.addBookForSale(isbn).then(res => {
        _this.loading = false;
        if (res.data.code && res.data.code === 501) {
          _this.currencyVisible = true;
          _this.currencyBook = res.data.book;
          _this.$refs.currency.focus();
        } else if (res.data.code && res.data.code === 500) {
          _this.$dialog.alert({
            message: res.data.msg
          });
        } else if (
          res.data.can_recover === 0 &&
          res.data.book.admin_user_id === 0 &&
          res.data.recover_reports.length === 0
        ) {
          _this.book = res.data.book;
          // _this.outerVisible = true;
          _this.$dialog.alert({
            message: "抱歉！回流鱼暂时不收这本书"
          });
        } else if (
          res.data.can_recover === 1 &&
          res.data.book.volume_count > 1
        ) {
          // _this.suitDialogVisible = true;
          _this.$dialog.alert({
            title: "提醒",
            message:
              "请在打包时将套装书的所有单册绑放在一起或放在一个袋子里寄出"
          });
        } else {
          _this.currencyBook = "";
          _this.currencyNumber = "";
          _this.currencyUnit = "人民币";
          _this.currencyVisible = false;
        }
      });
    },
    recoverReport: function() {
      if (this.type === -1) {
        return;
      }
      axios
        .post("/wx-api/add_recover_report", {
          type: this.type,
          book_id: this.book.id,
          reason: this.reason
        })
        .then(res => {
          this.$store.dispatch("sale2hly/getBooksForRecover");
        });
      this.type = -1;
      this.book = "";
      this.reason = "";
      this.innerVisible = false;
      this.outerVisible = false;
    },
    cj: function() {
      this.cjShow = true;
    },
    beforeClose: function(action, done) {
      done();
    },
    currencyConfirm: function() {
      console.log("current unit=" + this.currencyUnit);
      console.log("current number=" + this.currencyNumber);
      axios
        .post("/wx-api/update_book_price", {
          book: this.currencyBook.id,
          price: this.currencyNumber,
          currency: this.currencyUnit
        })
        .then(res => {
          if (res.data.code && res.data.code === 500) {
            this.$dialog.alert({
              message: res.data.msg
            });
          } else {
            this.addBookForRecoverByIsbn(this.currencyBook.isbn);
          }
        });
    },
    ...mapActions("sale2hly", [
      "addBookForSale",
      "removeBookFromSale",
      "addBookForSaleArray"
    ])
  },
  components: {
    RecoverSteps,
    RecoverBook,
    Loading,
    BottomBar2
  }
};
</script>
