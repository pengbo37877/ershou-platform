<template>
  <div>
    <div class="search-input">
      <form action="/">
        <van-search
          v-model="str"
          placeholder="书名/作者/分类"
          show-action
          @search="search"
          @cancel="clear"
          autofocus
        />
      </form>
    </div>
    <div class="search-suggestion" v-if="books.length===0 && !loading">
      <div class="s-title">热门搜索</div>
      <div class="s-content">
        <div class="s-tag" v-for="tag in tops" @click="tagClick(tag)">{{tag}}</div>
      </div>
    </div>
    <div
      class="search-suggestion"
      v-if="books.length===0 && !loading && historyList.length>0 &&tops.length>0"
      style="marginTop:20px"
    >
      <div class="s-title left">历史搜索</div>
      <div class="s-history right" @click="clearHistory">清除历史记录</div>
      <div class="clear"></div>
      <div class="s-content">
        <div class="s-tag" v-for="history in historyList" @click="tagClick(history)">{{history}}</div>
      </div>
    </div>
    <loading :loading="loading" v-if="books.length===0" style="margin-top: 60px"></loading>
    <div class="search-result" v-if="books.length>0">
      <search-book :book="book" :screen-width="screenWidth" v-for="book in books" :key="book.id"></search-book>
      <loading :loading="loading" v-if="nextPageUrl"></loading>
      <div class="bottom-loading" style="font-size:13px;" v-else>已全部加载</div>
    </div>
    <!-- 筛选 -->
    <Filters v-show="books.length>0" :top="54" @enterFilter="filterSearch" ref="filterComment"></Filters>
  </div>
</template>
<style scoped lang='scss'>
body {
  background-color: #ffffff;
}
a {
  text-decoration: none;
  color: #fff;
}
a:visited {
  text-decoration: none;
  color: #fff;
}
a:link {
  text-decoration: none;
  color: #fff;
}
a:hover {
  text-decoration: none;
  color: #fff;
}
.search-input {
  background-color: white;
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  z-index: 100;
  border-bottom: 0.5px solid #eeeeee;
}
.search-suggestion {
  margin-top: 60px;
  background-color: white;
  padding: 10px 20px;
}
.s-title {
  font-size: 14px;
  color: #666666;
}
.s-history {
  font-size: 14px;
  color: rgb(68, 179, 221);
}
.s-content {
  display: flex;
  flex-direction: row;
  flex-wrap: wrap;
}
.s-tag {
  font-size: 13px;
  color: #aaaaaa;
  border: 0.5px solid #e6e6e6;
  border-radius: 4px;
  padding: 5px 10px;
  margin: 15px 15px 0 0;
}
.search-result {
  margin-top: 90px;
  margin-bottom: 60px;
  background-color: white;
}
.bottom-loading {
  display: flex;
  flex-direction: row;
  justify-content: center;
  color: #3d404a;
  padding: 10px;
  margin-bottom: 46px;
  opacity: 0.5;
}
</style>
<script>
import SearchBook from "./SearchBook";
import Loading from "./Loading";
import { mapState, mapGetters, mapActions } from "vuex";
import Filters from "./Filter";
import { Toast } from "vant";
export default {
  data() {
    return {
      str: "",
      screenWidth: 0,
      loading: false,
      book: "",
      skuIndex: 0,
      fetching: false,
      pageYOffset: 0,
      bScrollTop: 0,
      dScrollTop: 0,
      historyList: [], //搜索历史
      nowPageUrl: ""
    };
  },
  created: function() {
    this.$store.dispatch("search2/getTop");
  },
  mounted: function() {
    this.screenWidth =
      window.innerWidth ||
      document.documentElement.clientWidth ||
      document.body.clientWidth;
    window.addEventListener("scroll", this.handleScroll);
  },
  activated: function() {
    this.wxApi.wxConfig("", "");
    window.addEventListener("scroll", this.handleScroll);
    if (this.bScrollTop > 0) {
      document.body.scrollTop = this.bScrollTop;
      // console.log("document.body.scrollTop=" + document.body.scrollTop);
    }
    if (this.dScrollTop > 0) {
      document.documentElement.scrollTop = this.dScrollTop;
      // console.log(
      //   "document.documentElement.scrollTop=" +
      //     document.documentElement.scrollTop
      // );
    }
    if (this.pageYOffset > 0) {
      window.pageYOffset = this.pageYOffset;
      // console.log("window.pageYOffset=" + window.pageYOffset);
    }
    this.historyList = this.getHistory();
  },
  deactivated: function() {
    window.removeEventListener("scroll", this.handleScroll);
  },
  destroyed: function() {
    window.removeEventListener("scroll", this.handleScroll);
  },
  beforeRouteLeave(to, from, next) {
    if (to.name == "shop") {
      console.log("返回了首页");
      this.clearBooks();
    }
    next();
  },
  computed: {
    ...mapState({
      tags: state => state.search2.tags,
      tops:state => state.search2.tops,
      books: state => state.search2.books,
      currentPage: state => state.search2.currentPage,
      nextPageUrl: state => state.search2.nextPageUrl
    })
  },
  methods: {
    clearBooks: function() {
      this.str = "";
      this.$store.commit("search2/setQ", "");
      this.$store.commit("search2/setData", "");
      this.$store.commit("search2/setBooks", {
        data: [],
        next_page: 0,
        next_page_url: ""
      });
    },
    activeSkuStyle: function(index) {
      if (index === this.skuIndex) {
        return "dialog-active";
      }
    },
    enter: function() {
      // this.search(this.str);
      console.log("enter " + this.str);
      this.$refs.str.blur();
    },
    filterSearch(data) {
      data.q = this.str;
      Toast.loading("筛选中...");
      this.searchBooks(data).then(res => {
        console.log("search", res);
        Toast.clear();
        if (!res || res.length == 0) {
          Toast("没有找到相关的书籍");
        }
      });
    },
    getHistory() {
      let history = window.localStorage.getItem("historyList");
      if (history) {
        let nowHistory = JSON.parse(history);
        if (nowHistory.length > 9) {
          nowHistory.splice(-(nowHistory.length - 1), nowHistory.length - 9);
        }
        return nowHistory;
      } else {
        return [];
      }
    },
    search: function() {
      this.loading = true;
      var _this = this;
      window.pageYOffset = 0;
      document.documentElement.scrollTop = 0;
      document.body.scrollTop = 0;
      let data = {
        q: this.str,
        discount: "",
        price: "",
        rating: "",
        level: ""
      };
      let indexs = {
        index: -1,
        discountIndex: -1,
        priceIndex: -1,
        fractionValue: -1,
        phaseIndex: -1,
        showFilter: false
      };
      this.$refs.filterComment.setIndexs(indexs);
      console.log("data", data);
      this.searchBooks(data).then(res => {
        _this.loading = false;
        console.log("search", res);
        if (!res || res.length == 0) {
          Toast("没有找到相关的书籍");
        }
      });
      if (this.str.trim().length > 0) {
        if (window.localStorage.getItem("historyList")) {
          let oldHistory = JSON.parse(
            window.localStorage.getItem("historyList")
          );
          if (oldHistory.indexOf(this.str.trim()) == -1) {
            oldHistory.unshift(this.str.trim());
            if (oldHistory.length > 9) {
              oldHistory.splice(
                -(oldHistory.length - 1),
                oldHistory.length - 9
              );
            }
            this.historyList = oldHistory;
            window.localStorage.setItem(
              "historyList",
              JSON.stringify(oldHistory)
            );
          }
        } else {
          let newList = [];
          newList.unshift(this.str.trim());
          window.localStorage.setItem("historyList", JSON.stringify(newList));
          this.historyList = newList;
        }
      }
    },
    clearHistory() {
      this.historyList = [];
      window.localStorage.setItem("historyList", "[]");
    },
    clear: function() {
      if (this.books.length === 0) {
        this.$router.back();
        return;
      }
      this.str = "";
      this.clearBooks();
      window.pageYOffset = 0;
      document.body.scrollTop = 0;
      document.documentElement.scrollTop = 0;
    },
    tagClick: function(tag) {
      this.str = tag;
      this.search();
      let indexs = {
        index: -1,
        discountIndex: -1,
        priceIndex: -1,
        fractionValue: -1,
        phaseIndex: -1,
        showFilter: false
      };
      this.$refs.filterComment.setIndexs(indexs);
    },
    input: function(value) {
      console.log(value);
      this.str = value;
      this.$store.commit("search2/setQ", value);
      if (value === "") {
        this.$store.commit("search2/setBooks", {
          data: [],
          next_page: 0,
          next_page_url: ""
        });
      }
    },
    handleScroll: function() {
      //scrollTop是浏览器滚动条的top位置
      this.pageYOffset = window.pageYOffset;
      this.dScrollTop = document.documentElement.scrollTop;
      this.bScrollTop = document.body.scrollTop;
      // console.log("pageYOffset=" + this.pageYOffset);
      // console.log("dScrollTop=" + this.dScrollTop);
      // console.log("bScrollTop=" + this.bScrollTop);
      //下面这句主要是获取网页的总高度，主要是考虑兼容性所以把Ie支持的documentElement也写了，这个方法至少支持IE8
      var htmlHeight = document.documentElement.scrollHeight;
      //clientHeight是网页在浏览器中的可视高度，
      var clientHeight = document.documentElement.clientHeight;
      //通过判断滚动条的top位置与可视网页之和与整个网页的高度是否相等来决定是否加载内容；
      var scrollTop = 0;
      if (this.pageYOffset > scrollTop) {
        scrollTop = this.pageYOffset;
      }
      if (this.dScrollTop > scrollTop) {
        scrollTop = this.dScrollTop;
      }
      if (this.bScrollTop > scrollTop) {
        scrollTop = this.bScrollTop;
      }
      if (scrollTop + clientHeight === htmlHeight) {
        //防止多次请求
        if (this.nowPageUrl !== this.nextPageUrl) {
          if (this.str.trim().length > 0) {
            this.loading =true;
            this.searchMore().then(res=>{
            this.loading =false;
            })
          }
        }
        this.nowPageUrl = this.nextPageUrl;
      }
    },
    ...mapActions("search2", ["searchBooks", "searchMore"])
  },
  components: {
    SearchBook,
    Loading,
    Filters
  }
};
</script>
