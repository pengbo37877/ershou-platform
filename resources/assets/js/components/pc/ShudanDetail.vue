<template>
  <div style="position: relative;" v-if="shudan">
    <div
      :style="{backgroundImage: 'url('+shudan.cover+')', backgroundPosition: 'center center',backgroundSize: 'cover', opacity: 0.5, width: screenWidth + 'px', height:'250px'}"
    ></div>
    <div
      :style="{position: 'absolute', top: 0, left: 0, width: screenWidth + 'px', height: '100%'}"
    >
      <div class="shudan" :style="contentStyle">
        <div class="shudan-title" :style="titleWidth">{{shudan.title}}</div>
        <div class="shudan-desc" :style="descStyle" v-html="shudan.desc"></div>
        <div class="tuijian box_cqh" v-show="id!=1">
          <div
            class="nums"
          >{{shudanInfo[this.id]?shudanInfo[this.id].count_user:0}}人推荐了{{shudanInfo[this.id]?shudanInfo[this.id].count_book:0}}本书</div>
          <div class="userheads">
            <div
              class="head"
              v-for="(item,index) in shudanInfo[this.id]?shudanInfo[this.id].user:[]"
              :key="index"
              :style="{right:index*25+'px'}"
            >
              <img :src="item" alt />
            </div>
          </div>
        </div>
      </div>
      <van-list
        v-model="loading"
        :finished="finished"
        finished-text="没有更多了"
        @load="loadData"
        style="background-color: white"
      >
        <shudan-book
          :screen-width="screenWidth"
          :item="item"
          v-for="(item, index) in items"
          :key="index"
          :index="index"
          @changeItems="changeItems"
        ></shudan-book>
        <div slot="loading">
          <loading :loading="loading"></loading>
        </div>
      </van-list>
      <div class="gotoCecommend" v-show="id!=1">
        <span @click="gotoRecbook">我来推荐一本书</span>
      </div>
    </div>
    <router-link tag="div" to="/pc/shop" class="back-home" v-if="isNotify">回首页</router-link>
    <router-link
      tag="div"
      to="/pc/cart"
      class="back-cart"
      v-if="cartItems.length>0 && parseInt(userId)>0"
    >
      <div style="position: relative;">
        <div>购物袋</div>
        <div class="cart-count" :style="cartCountStyle">{{cartItems.length}}</div>
      </div>
    </router-link>
  </div>
</template>

<script>
import { mapGetters, mapState, mapActions } from "vuex";
import ShudanBook from "./ShudanBook";
import Loading from "./Loading";
export default {
  data() {
    return {
      id: 0,
      shudan: "",
      loading: true,
      finished: false,
      items: [],
      nextPageUrl: "",
      currentPage: 0,
      total: 0,
      screenWidth: 0,
      isNotify: false,
      y: 0,
      pageYOffset: 0,
      bScrollTop: 0,
      dScrollTop: 0,
      from: "",
      headList: ["", "", "", "", ""],
      shudanInfo: ""
    };
  },
  computed: {
    contentStyle: function() {
      var color = this.shudan.color ? this.shudan.color : "#ffffff";
      return {
        background: color.colorRgba(1),
        background:
          "-moz-linear-gradient(top, " +
          color.colorRgba() +
          " 0%, " +
          color.colorRgba(0) +
          " 100%)",
        background:
          "-webkit-gradient(left top, left bottom, color-stop(0%, " +
          color.colorRgba() +
          "), color-stop(100%, " +
          color.colorRgba(0) +
          "))",
        background:
          "-webkit-linear-gradient(top, " +
          color.colorRgba() +
          " 0%, " +
          color.colorRgba(0) +
          " 100%)",
        background:
          "-o-linear-gradient(top, " +
          color.colorRgba() +
          " 0%, " +
          color.colorRgba(0) +
          " 100%)",
        background:
          "-ms-linear-gradient(top, " +
          color.colorRgba() +
          " 0%, " +
          color.colorRgba(0) +
          " 100%)",
        background:
          "linear-gradient(to bottom, " +
          color.colorRgba() +
          " 0%, " +
          color.colorRgba(0) +
          " 100%)"
      };
    },
    titleWidth: function() {
      return {
        width: this.screenWidth - 30 + "px"
      };
    },
    descStyle: function() {
      return {
        textShadow: this.shudan.color + " 0.1em 0.1em 0.1em"
      };
    },
    cartCountStyle: function() {
      if (this.cartItems.length < 10) {
        return {
          padding: "3px 7px"
        };
      } else {
        return {
          padding: "3px"
        };
      }
    },
    ...mapState({
      opened: state => state.shudan.opened,
      sds: state => state.shudan.sds,
      cartItems: state => state.cart.items,
      userId: state => state.user.userId,
      user: state => state.user.user,
      users: state => state.user.users
    })
  },
  created() {
    let that = this;
    this.id = this.$route.params.shudan;
    this.from = this.$route.query.from;
    if (this.from === "notify" || this.from === "menu") {
      this.isNotify = true;
    }
    // get user & cart items
    this.$store.dispatch("user/getUser").then(res => {
      this.$store.dispatch("cart/items");
      axios.get("/wx-api/shudan_users").then(res => {
        console.log(res.data);
        that.shudanInfo = res.data.data;
      });
    });

    console.log("shudan = " + this.id);
    if (parseInt(this.id) > 0 && this.items.length === 0) {
      // set shudan
      this.shudan = this.opened.find(sd => sd.id === this.id);
      if (this.shudan) {
        console.log("find shudan " + this.shudan.title);
      } else {
        this.shudan = this.sds.find(sd => sd.id === this.id);
        if (_.isEmpty(this.shudan)) {
          this.getShudan(this.id);
        }
      }
      this.loadData();
    }
  },
  mounted() {
    this.screenWidth =
      window.innerWidth ||
      document.documentElement.clientWidth ||
      document.body.clientWidth;
  },
  activated: function(to, from, next) {
    window.addEventListener("scroll", this.handleScroll);
    console.log("shudan = " + this.id);
    var currentId = this.$route.params.shudan;
    if (currentId !== this.id) {
      this.id = currentId;
      this.finished = false;
      this.shudan = "";
      this.items = [];
      this.nextPageUrl = "";
      this.currentPage = 0;
      this.total = 0;
      if (parseInt(this.id) > 0) {
        // set shudan
        this.shudan = this.opened.find(sd => sd.id === this.id);
        if (this.shudan) {
          console.log("find shudan " + this.shudan.title);
        } else {
          this.shudan = this.sds.find(sd => sd.id === this.id);
          if (_.isEmpty(this.shudan)) {
            this.getShudan(this.id);
          }
        }
        this.loadData();
      }
    } else {
      if (this.bScrollTop > 0) {
        document.body.scrollTop = this.bScrollTop;
        console.log("document.body.scrollTop=" + document.body.scrollTop);
      }
      if (this.dScrollTop > 0) {
        document.documentElement.scrollTop = this.dScrollTop;
        console.log(
          "document.documentElement.scrollTop=" +
            document.documentElement.scrollTop
        );
      }
      if (this.pageYOffset > 0) {
        window.pageYOffset = this.pageYOffset;
        console.log("window.pageYOffset=" + window.pageYOffset);
      }
    }
  },
  deactivated: function() {
    window.removeEventListener("scroll", this.handleScroll);
  },
  methods: {
    loadData: function() {
      this.loading = true;
      var nextPage = this.currentPage + 1;
      axios
        .get("/wx-api/get_shudan_books/" + this.id + "?page=" + nextPage)
        .then(res => {
          this.loading = false;
          if (nextPage === 1) {
            this.items = res.data.data;
          } else {
            this.items = this.items.concat(res.data.data);
          }
          this.nextPageUrl = res.data.next_page_url;
          this.currentPage = res.data.current_page;
          this.total = res.data.total;
          if (_.isEmpty(this.nextPageUrl)) {
            this.finished = true;
          }
          console.log("loadData");
          console.log(res.data);
        });
    },
    getShudan: function() {
      axios.get("/wx-api/get_shudan/" + this.id).then(res => {
        this.shudan = res.data;
        document.title = this.shudan.title;
      });
    },
    handleScroll: function() {
      //scrollTop是浏览器滚动条的top位置
      this.pageYOffset = window.pageYOffset;
      this.dScrollTop = document.documentElement.scrollTop;
      this.bScrollTop = document.body.scrollTop;
      console.log("pageYOffset=" + this.pageYOffset);
      console.log("dScrollTop=" + this.dScrollTop);
      console.log("bScrollTop=" + this.bScrollTop);
      //下面这句主要是获取网页的总高度，主要是考虑兼容性所以把Ie支持的documentElement也写了，这个方法至少支持IE8
      var htmlHeight = document.documentElement.scrollHeight;
      //clientHeight是网页在浏览器中的可视高度，
      var clientHeight = document.documentElement.clientHeight;
    },
    gotoRecbook() {
      let that = this;
      console.log(that.shudan);
      that.$router.push({
        path: "/pc/recbooks",
        query: { shudan: JSON.stringify(that.shudan) }
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
        this.items[data.index] = data.item;
      } else {
        data.item.shudan_zan_status.push("1");
        data.item.shudan_zan_users.push("11");
        this.items[data.index] = data.item;
      }
    }
  },
  components: {
    ShudanBook,
    Loading
  }
};
</script>

<style scoped>
.shudan {
  position: relative;
  width: 100%;
  min-height: 120px;
  display: flex;
  flex-direction: column;
}
.shudan-title {
  margin-top: 35px;
  margin-left: 20px;
  margin-right: 20px;
  font-size: 20px;
  font-weight: bold;
  color: white;
}
.shudan-desc {
  margin-left: 20px;
  margin-right: 20px;
  font-size: 14px;
  color: #fff;
  height: 150px;
  overflow: hidden;
}
.back-home {
  position: fixed;
  right: 0;
  bottom: 100px;
  background-color: rgba(0, 0, 0, 0.7);
  color: white;
  font-size: 16px;
  padding: 5px 15px 5px 20px;
  border-top-left-radius: 20px;
  border-bottom-left-radius: 20px;
  -webkit-box-shadow: -1px 1px 4px 0px rgba(204, 204, 204, 1);
  -moz-box-shadow: -1px 1px 4px 0px rgba(204, 204, 204, 1);
  box-shadow: -1px 1px 4px 0px rgba(204, 204, 204, 1);
}
.back-cart {
  position: fixed;
  right: 0;
  bottom: 60px;
  background-color: rgba(0, 0, 0, 0.7);
  color: white;
  font-size: 16px;
  padding: 5px 15px 5px 20px;
  border-top-left-radius: 20px;
  border-bottom-left-radius: 20px;
  -webkit-box-shadow: -1px 1px 4px 0px rgba(204, 204, 204, 1);
  -moz-box-shadow: -1px 1px 4px 0px rgba(204, 204, 204, 1);
  box-shadow: -1px 1px 4px 0px rgba(204, 204, 204, 1);
}
.cart-count {
  position: absolute;
  left: -22px;
  top: -10px;
  font-size: 11px;
  border-radius: 20px;
  background-color: #ff4848;
  color: white;
}
.gotoCecommend {
  position: fixed;
  bottom: 50px;
  left: 50%;
  transform: translateX(-50%);
  text-align: center;
  padding: 10px 15px;
  border-radius: 30px;
  background: #ffffff;
  font-size: 13px;
  box-shadow: 0 0px 15px rgba(0, 0, 0, 0.1);
  color: #333333;
}
.tuijian {
  width: 100%;
  padding: 0 20px;
  box-sizing: border-box;
  padding-bottom: 10px;
  text-align: justify;
}
.tuijian .nums {
  font-size: 13px;
  color: rgba(0, 0, 0, 0.5);
}
.userheads {
  position: relative;
  height: 30px;
}
.userheads .head {
  width: 30px;
  height: 30px;
  display: inline-block;
  margin: 0 5px;
  position: absolute;
}
.userheads img {
  width: 30px;
  height: 30px;
  border-radius: 50%;
  border: 1px solid #ffffff;
}
</style>