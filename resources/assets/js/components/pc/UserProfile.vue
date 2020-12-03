<template>
  <div class="userBox">
    <div class="topBox" v-if="user">
      <div class="top">
        <div class="avatar">
          <img :src="user.avatar?user.avatar:'/images/avatar.jpeg'" alt />
        </div>
      </div>
      <div class="user">
        <span class="username">{{user.nickname?user.nickname:'你的名字呢？'}}</span>
        <div class="balance">卖书赚得{{ soldBooksIncome }}元</div>
      </div>
    </div>
    <div class="home" v-if="user">
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
        <van-tab title="Ta的动态">
          <van-list v-model="loading" :finished="finished" @load="getFeeds">
            <Dynamic
              :item="feed"
              v-for="(feed,index) in Feeds"
              :key="index"
              :user="user"
              :index="index"
              @changeItems="changeItems"
              :from="from"
            ></Dynamic>
            <div class="nobook" v-if="Feeds.length==0">
              <div class="msg">这条鱼还没有动态</div>
            </div>
          </van-list>
        </van-tab>
        <van-tab title="Ta的书房">
          <van-list :finished="true">
            <div class="bd-relations" v-if="shelfBooks.length>0">
              <div class="bd-re-book" v-for="book in shelfBooks" :key="book.id">
                <router-link :to="`/pc/book/${book.isbn}`" class="bd-re-book-cover">
                  <img :src="book.cover_replace" />
                </router-link>
                <div class="bd-re-book-name">{{book.name}}</div>
              </div>
            </div>
            <div class="nobook" v-else>
              <div class="msg">这条鱼的书房空空如也</div>
            </div>
          </van-list>
        </van-tab>
      </van-tabs>
    </div>
  </div>
</template>
<style scoped lang='scss'>
.userBox {
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
        margin-top: 5px;
      }
    }
    .menu {
      width: 100%;
      padding: 17px 0;
      box-sizing: border-box;
      .menuList {
        flex: 1;
        text-align: center;
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
      text-align: center;
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
    .bd-re-book-name {
      font-size: 14px;
      color: #3d404a;
      text-overflow: ellipsis;
      white-space: nowrap;
      overflow: hidden;
      margin: 5px auto 0 auto;
      text-align: center;
      width: 92px;
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
      padding-top: 45px;
      text-align: center;
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
.userBox /deep/ .van-hairline--top-bottom:after {
  border-bottom: 1px solid #ebedf0;
}
</style>
<script>
import { mapState, mapGetters, mapActions } from "vuex";
import Dynamic from "./Dynamic";
export default {
  data() {
    return {
      openId: "",
      active: 0,
      loading: false,
      finished: true,
      Feeds: [],
      page: 1,
      nav: 1,
      screenWidth: 0,
      status: 0, // 0: 正常状态，1:管理状态
      from: "user"
    };
  },
  components: {
    Dynamic
  },
  created: function() {
    var _this = this;
    this.openId = this.$route.params.openId;
    console.log(this.openId);
    this.getUser({ openId: this.openId }).then(res => {
      console.log("users");
      console.log(res);
      this.getFeeds();
    });
    this.getSoldBooksIncome({ openId: this.openId });
    this.getShelfBooks({ openId: this.openId }).then(res => {
      if (res.data.code && res.data.code === 500) {
        this.$dialog.alert({
          message: res.data.msg,
          center: true
        });
      }
    });
    this.getSoldBooks({ openId: this.openId }).then(res => {
      if (res.data.code && res.data.code === 500) {
        this.$dialog.alert({
          message: res.data.msg,
          center: true
        });
      }
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
      user: state => state.user4share.user,
      shelfBooks: state => state.user4share.shelfBooks,
      soldBooks: state => state.user4share.soldBooks,
      soldBooksIncome: state => state.user4share.soldBooksIncome
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
  },
  methods: {
    shelf: function() {
      this.nav = 0;
    },
    activity: function() {
      this.nav = 1;
    },
    manage: function() {
      this.status = 1;
    },
    done: function() {
      this.status = 0;
    },
    getFeeds() {
      this.loading = true;
      this.finished = false;
      axios
        .get("/wx-api/get_user_feeds/" + this.openId + "?page=" + this.page)
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
          that.finished = true;
          that.setShare();
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
        title = this.user.nickname + "在回流⻥的个⼈主⻚，来看Ta书房⾥的收藏吧";
      } else if (
        this.Feeds.length > 0 &&
        this.Feeds[0].no &&
        this.Feeds[0].type == 2
      ) {
        title =
          this.user.nickname +
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
          this.user.nickname +
          "在回流⻥卖书赚了" +
          this.soldBooksIncome +
          "元，你也来试试吧";
      } else if (
        this.Feeds.length > 0 &&
        this.Feeds[0].type == 1 &&
        !this.Feeds[0].no
      ) {
        title =
          this.user.nickname +
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
          this.user.nickname +
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
          window.localStorage.getItem("url") + "/wechat/user/" + this.openId,
        imgUrl: window.localStorage.getItem("url") + "/images/image/logo.jpeg"
      };
      console.log(options);
      wxApi.wxConfig(options, "");
    },
    ...mapActions("user4share", [
      "getUser",
      "getSoldBooks",
      "getSoldBooksIncome",
      "getShelfBooks"
    ])
  }
};
</script>