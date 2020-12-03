<template>
  <div class="myBox">
    <div class="topBox">
      <div class="top">
        <div class="avatar">
          <img :src="user.avatar?user.avatar:'/images/avatar.jpeg'" alt />
        </div>
      </div>
      <div class="user">
        <span class="username">{{user.nickname?user.nickname:'你的名字呢？'}}</span>
        <span class="balance">卖书赚得{{ saleBooksBalance }}元</span>
      </div>
      <div class="menu box_cqh">
        <router-link class="menuList" to="/wechat/wallet" tag="div">
          <img src="/images/image/yue.png" alt width="25" height="25" />
          <div>余额</div>
        </router-link>
        <router-link class="menuList" to="/wechat/my_orders" tag="div">
          <img src="/images/image/dingdan.png" alt width="25" height="25" />
          <div>订单</div>
        </router-link>
        <router-link class="menuList" to="/wechat/myCoupons" tag="div">
          <img src="/images/image/juan.png" alt width="25" height="25" />
          <div>现金券</div>
          <div class="dian" v-show="showRemind"></div>
        </router-link>
        <router-link class="menuList" to="/wechat/address_list?from=my" tag="div">
          <img src="/images/image/dizhi.png" alt width="25" height="25" />
          <div>地址</div>
        </router-link>
      </div>
    </div>
    <div class="home">
      <van-tabs
        v-model="active"
        swipeable
        title-active-color="#44B3DD"
        title-inactive-color="#666666"
        color="#44B3DD"
        line-height="2"
        line-width="60"
        sticky
      >
        <van-tab title="我的动态">
          <van-list v-model="loading" :finished="finished" @load="getFeeds" finished-text="没有更多了">
            <Dynamic
              :item="feed"
              v-for="(feed,index) in Feeds"
              :key="index"
              :user="user"
              :index="index"
              @changeItems="changeItems"
              :from="from"
            ></Dynamic>
          </van-list>
        </van-tab>
        <van-tab title="我的书房">
          <van-list :finished="true">
            <div class="bd-relations" v-if="shelfBooks.length>0">
              <div class="bd-re-book" v-for="book in shelfBooks" :key="book.id">
                <router-link :to="`/wechat/book/${book.isbn}?from=my`" class="bd-re-book-cover">
                  <img :src="book.cover_replace" />
                </router-link>
                <div class="bd-re-book-name" :style="reWidth">{{book.name}}</div>
                <div class="delBtn" v-if="status===1" @click.stop="remove(book)">
                  <img src="/images/image/jianqu.png" width="16" height="16" />
                </div>
              </div>
            </div>
            <div class="operation box_cqh" v-if="shelfBooks.length>0">
              <div class="hint">
                <img src="/images/image/shu.png" alt width="13" height="16" />
                <span>向书友展示你的收藏</span>
              </div>
              <div class="btns box_h">
                <div class="btn" @click="scan">扫码添加</div>
                <div class="btn" @click="manage">管理书房</div>
              </div>
            </div>
            <div class="nobook" v-else>
              <img src="/images/image/nobook.png" width="154" height="90" />
              <div class="msg">向书友展示你的图书收藏</div>
              <div class="scanBtn" @click="scan">扫码添加</div>
            </div>
          </van-list>
        </van-tab>
      </van-tabs>
    </div>
    <bottom-bar2 index="3"></bottom-bar2>
  </div>
</template>
<style scoped lang='scss'>
.myBox {
  width: 100%;
  .topBox {
    background: #ffffff;
    border-bottom: 7px solid #eeeeee;
    .top {
      width: 100%;
      height: 120px;
      background: url("/images/image/bj.png");
      background-size: 100% 100%;
      display: flex;
      align-items: flex-end;
      justify-content: center;
      .avatar {
        width: 65px;
        height: 65px;
        img {
          width: 65px;
          height: 65px;
          border: 2px solid rgba(238, 238, 238, 1);
          border-radius: 50%;
        }
      }
    }
    .user {
      margin: 17px auto;
      text-align: center;
      .username {
        font-size: 15px;
        font-family: PingFang-SC;
        color: rgba(3, 3, 3, 1);
        line-height: 21px;
        margin-right: 10px;
      }
      .balance {
        font-size: 13px;
        font-family: PingFang-SC;
        color: rgba(42, 141, 218, 1);
        line-height: 18px;
      }
    }
    .menu {
      width: 100%;
      padding: 17px 0;
      box-sizing: border-box;
      .menuList {
        flex: 1;
        text-align: center;
        position: relative;
        div {
          margin-top: 7px;
          font-size: 12px;
          font-family: PingFang-SC;
          color: rgba(51, 51, 51, 1);
          line-height: 17px;
        }
        img {
          width: 25px;
        }
        .dian{
          width: 5px;
          height: 5px;
          border-radius: 50%;
          background: red;
          position: absolute;
          top: -4px;
          right: 15px;
        }
      }
    }
  }
  .home {
    .book-name {
      white-space: nowrap;
      text-overflow: ellipsis;
      overflow: hidden;
      width: 100%;
      color: #3d404a;
      text-align: center;
      font-size: 15px;
    }
    .book-m {
      position: absolute;
      top: 0;
      left: 0;
      height: 100%;
      width: 100%;
      background-color: #6c757d;
      filter: Alpha(Opacity=60);
      opacity: 0.6;
      text-align: center;
    }
    .delBtn {
      position: absolute;
      top: -8px;
      left: -8px;
    }
    .bd-relations {
      width: 100%;
      padding: 24px 15px;
      padding-bottom: 110px;
      box-sizing: border-box;
      display: flex;
      flex-direction: row;
      flex-wrap: wrap;
    }
    .bd-re-book {
      width: 33.3%;
      position: relative;
      margin-bottom: 10px;
      .bd-re-book-cover {
        width: 92px;
        height: 130px;
        margin: 0 auto;
        img {
          width: 92px;
          height: 130px;
          object-fit: cover;
        }
      }
    }
    // .bd-re-book-cover {
    //   border-radius: 4px;
    //   border: 2px solid white;
    //   -webkit-box-shadow: 2px 2px 5px 0px rgba(204, 204, 204, 0.5);
    //   -moz-box-shadow: 2px 2px 5px 0px rgba(204, 204, 204, 0.5);
    //   box-shadow: 2px 2px 5px 0px rgba(204, 204, 204, 0.5);
    // }
    .bd-re-book-name {
      font-size: 14px;
      color: #3d404a;
      text-overflow: ellipsis;
      white-space: nowrap;
      overflow: hidden;
      margin-top: 5px;
      text-align: center;
    }
    .shop-mask {
      position: absolute;
      top: 2px;
      left: 2px;
      background-color: black;
      opacity: 0.6;
      display: flex;
      flex-direction: row;
      justify-content: center;
      align-items: center;
    }
    .operation {
      width: 100%;
      height: 60px;
      position: fixed;
      bottom: 50px;
      left: 0;
      background: rgba(255, 239, 226, 0.95);
      border-radius: 15px;
      padding: 0 21px;
      box-sizing: border-box;
      border-bottom-right-radius: 0;
      border-bottom-left-radius: 0;
      .hint {
        font-size: 13px;
        font-family: PingFang-SC;
        color: rgba(217, 116, 41, 1);
        line-height: 18px;
        img {
          margin-right: 5px;
          vertical-align: sub;
        }
      }
      .btns {
        .btn {
          width: 70px;
          height: 28px;
          line-height: 28px;
          text-align: center;
          border-radius: 15px;
          border: 1px solid rgba(242, 153, 84, 1);
          font-size: 12px;
          font-family: PingFang-SC;
          color: rgba(229, 126, 49, 1);
        }
        .btn:first-child {
          margin-right: 15px;
        }
      }
    }
    .nobook {
      width: 100%;
      padding-top: 20px;
      text-align: center;
      padding-bottom: 60px;
      .msg {
        font-size: 15px;
        font-family: PingFang-SC;
        color: rgba(153, 153, 153, 1);
        line-height: 21px;
        margin-bottom: 15px;
      }
      img {
        margin-bottom: 11px;
      }
      .scanBtn {
        width: 140px;
        height: 46px;
        line-height: 46px;
        text-align: center;
        background: rgba(65, 176, 220, 1);
        border-radius: 15px;
        color: #ffffff;
        margin: 0 auto;
      }
    }
  }
}
</style>
<style scoped lang='scss'>
.myBox /deep/  .van-hairline--top-bottom:after {
  border-bottom: 1px solid #ebedf0;
}
</style>
<script>
import wx from "weixin-js-sdk";
import BottomBar2 from "./BottomBar2";
import Loading from "./Loading";
import { mapState, mapGetters, mapActions } from "vuex";
import wxApi from "../share.js";
import Dynamic from "./Dynamic";
export default {
  data() {
    return {
      loading: false,
      loading2: false,
      page: 1, //动态页码
      nav: 0,
      screenWidth: 0,
      status: 0, // 0: 正常状态，1:管理状态
      tryTime: 0,
      active: 0,
      tab: ["我的动态", "我的书房"],
      finished: true,
      finished2: true,
      Feeds: [],
      from: "my"
    };
  },
  created: function() {
    var _this = this;
    this.$store.dispatch("user/getUser").then(res => {
      // 如果拿不到用户，就显示一个不可关闭的对话框
      var user = res.data;
      console.log(user);
      if (user === "") {
        this.$router.replace("/wechat/shop");
      } else if (user == undefined) {
      } else if (user.subscribe === 0) {
        this.$router.replace("/wechat/shop");
      }
    });
    this.$store.dispatch("user/getUsers").then(res => {
      this.getFeeds();
    });
    this.$store.dispatch("my/getSaleBalance");
    this.loading2 = true;
    this.finished2 = false;
    this.getShelfBooks()
      .then(res => {
        this.loading2 = false;
        this.finished2 = true;
        if (res.data.code && res.data.code === 500) {
          _this.$dialog.alert({
            message: res.data.msg,
            center: true
          });
        }
      })
      .catch(function() {
        let that = this;
        that.finished2 = true;
      });
  },
  computed: {
    reStyle: function() {
      return {
        width: (this.screenWidth - 98) / 3 + "px",
        height: ((this.screenWidth - 98) * 1.43) / 3 + "px"
      };
    },
    reWidth: function() {
      return {
        width: (this.screenWidth - 98) / 3 + "px"
      };
    },
    maskStyle: function() {
      return {
        width: (this.screenWidth * 140) / 375 + "px",
        height: (this.screenWidth * 1.43 * 140) / 375 + "px",
        left: (this.screenWidth * 235) / 750 + "px"
      };
    },
    ...mapState({
      user: state => state.user.user,
      shelfBooks: state => state.my.shelfBooks,
      saleBooksBalance: state => state.my.saleBooksBalance,
      users: state => state.user.users,
      showRemind:state =>state.my.showRemind
    })
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
    // 获取微信配置
    this.wxConfig();
  },
  methods: {
    wxConfig: function() {
      var _this = this;
      axios
        .post("/wx-api/config", {
          url: "my"
        })
        .then(response => {
          console.log(response.data);
          wx.config(response.data);
          wx.error(res => {
            axios.post("/wx-api/create_client_error", {
              user_id: _this.user.id,
              error: JSON.stringify(response.data) + JSON.stringify(res),
              url: "/wechat/my"
            });
          });
        });
    },
    scan: function() {
      var _this = this;
      wx.scanQRCode({
        needResult: 1, // 默认为0，扫描结果由微信处理，1则直接返回扫描结果，
        scanType: ["qrCode", "barCode"], // 可以指定扫二维码还是一维码，默认二者都有
        success: function(res) {
          var result = '';
          if(res.resultStr.indexOf(',')==-1){
            result = res.resultStr; // 当needResult 为 1 时，扫码返回的结果
          }else{
            result = res.resultStr.split(",")[1]; // 当needResult 为 1 时，扫码返回的结果
          }
            _this.addBookToShelf(result).then(res => {
            if (res.data.code && res.data.code === 500) {
              _this.$dialog.alert({
                message: res.data.msg,
                center: true
              });
            }
          });
        }
      });
    },
    shelf: function() {
      this.nav = 0;
    },
    activity: function() {
      this.nav = 1;
    },
    manage: function() {
      if (this.status == 0) {
        this.status = 1;
      } else {
        this.status = 0;
      }
    },
    done: function() {
      this.status = 0;
    },
    remove: function(book) {
      this.removeBookFromShelf(book).then(res => {
        if (res.data.code && res.data.code === 500) {
          this.$dialog.alert({
            message: res.data.msg,
            center: true
          });
        }
      });
    },
    getFeeds() {
      this.loading = true;
      this.finished = false;
      axios
        .get(
          "/wx-api/get_user_feeds/" +
            this.users.mp_open_id +
            "?page=" +
            this.page
        )
        .then(res => {
          console.log(res.data);
          this.loading = false;
          if (res.data.length > 0) {
            this.Feeds = this.Feeds.concat(res.data);
            this.page += 1;
            if (res.data.length < 10) {
              this.finished = true;
            }
          } else {
            this.finished = true;
          }
          this.setShare();
        })
        .catch(function() {
          let that = this;
          that.setShare();
          that.finished = true;
        });
    },
    changeItems(data) {
      console.log(data);
      if (data.item.shudan_zan_status.length > 0) {
        data.item.shudan_zan_status.splice(0);
        data.item.shudan_zan_users.splice(
          data.item.shudan_zan_users.length - 1,
          1
        );
        this.Feeds[data.index] = data.item;
      } else {
        data.item.shudan_zan_status.push("1");
        data.item.shudan_zan_users.push("11");
        this.Feeds[data.index] = data.item;
      }
    },
    setShare() {
      let title = "";
      if (this.Feeds.length == 0) {
        title =
          this.users.nickname + "在回流⻥的个⼈主⻚，来看Ta书房⾥的收藏吧";
      } else if (
        this.Feeds.length > 0 &&
        this.Feeds[0].no &&
        this.Feeds[0].type == 2
      ) {
        title =
          this.users.nickname +
          "在回流鱼淘到好书" +
          "《" +
          this.Feeds[0].books[0].name +
          "》，" +
          "你也快来看看吧";
      } else if (
        this.Feeds.length > 0 &&
        this.Feeds[0].no &&
        this.Feeds[0].type == 1
      ) {
        title =
          this.users.nickname +
          "在回流⻥卖书赚了" +
          this.saleBooksBalance +
          "元，你也来试试吧";
      } else if (
        this.Feeds.length > 0 &&
        this.Feeds[0].type == 1 &&
        !this.Feeds[0].no
      ) {
        title =
          this.users.nickname +
          "在回流鱼推荐了" +
          "《" +
          this.Feeds[0].book.name +
          "》" +
          "，快来看看Ta怎么说";
      } else if (
        this.Feeds.length > 0 &&
        this.Feeds[0].type == 2 &&
        !this.Feeds[0].no
      ) {
        title =
          this.users.nickname +
          "在回流鱼评论了" +
          "《" +
          this.Feeds[0].book.name +
          "》" +
          "，快来看看Ta怎么说";
      }
      let options = {
        title: title,
        desc: "回流⻥⼆⼿循环书店，让好书流动起来",
        link:
          window.localStorage.getItem("url") +
          "/wechat/user/" +
          this.users.mp_open_id,
        imgUrl: window.localStorage.getItem("url") + "/images/image/logo.jpeg"
      };
      console.log(options);
      wxApi.wxConfig(options, "");
    },
    ...mapActions("my", [
      "getShelfBooks",
      "addBookToShelf",
      "removeBookFromShelf"
    ])
  },
  components: {
    BottomBar2,
    Loading,
    Dynamic
  }
};
</script>
