<template>
  <div class="resultDiv">
    <div class="searchBar">
      <form action="/">
        <van-search
          v-model="str"
          placeholder="书名/作者/分类"
          show-action
          @search="search"
          @cancel="clear"
          @focus="onchange"
        />
      </form>
    </div>
    <div class="resultBox" v-for="(item,index) in books" :key="index" @click="selectBooks(item)">
      <div class="books box">
        <div class="bookCover">
          <img :src="item.cover_replace" alt />
        </div>
        <div class="bookInfo">
          <div class="bookName">{{item.name}}</div>
          <div class="author">{{item.author}}</div>
        </div>
      </div>
    </div>
    <loading :loading="true" v-if="nextPageUrl"></loading>
  </div>
</template>

<script>
import Loading from "./Loading";
import { mapState, mapGetters, mapActions } from "vuex";
import { Toast } from "vant";
export default {
  data() {
    return {
      str: "", //输入的值
      loading: "",
      pageYOffset: 0,
      bScrollTop: 0,
      dScrollTop: 0,
      screenWidth: 0
    };
  },
  mounted: function() {
    this.screenWidth =
      window.innerWidth ||
      document.documentElement.clientWidth ||
      document.body.clientWidth;
      window.addEventListener("scroll", this.handleScroll);
  },
  activated: function() {
    window.addEventListener("scroll", this.handleScroll);
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
  },
  deactivated: function() {
    window.removeEventListener("scroll", this.handleScroll);
  },
  destroyed: function() {
    window.removeEventListener("scroll", this.handleScroll);
  },
  computed: {
    ...mapState({
      tags: state => state.search.tags,
      books: state => state.search.books,
      currentPage: state => state.search.currentPage,
      nextPageUrl: state => state.search.nextPageUrl
    })
  },

  methods: {
    //监听滚动加载
    handleScroll() {
      //scrollTop是浏览器滚动条的top位置
      let that = this;
      that.pageYOffset = window.pageYOffset;
      that.dScrollTop = document.documentElement.scrollTop;
      that.bScrollTop = document.body.scrollTop;
      //下面这句主要是获取网页的总高度，主要是考虑兼容性所以把Ie支持的documentElement也写了，这个方法至少支持IE8
      var htmlHeight = document.documentElement.scrollHeight;
      //clientHeight是网页在浏览器中的可视高度，
      var clientHeight = document.documentElement.clientHeight;
      //通过判断滚动条的top位置与可视网页之和与整个网页的高度是否相等来决定是否加载内容；
      var scrollTop = 0;
      console.log(
        "scrollTop:" + scrollTop,
        "clientHeight:" + clientHeight,
        "htmlHeight:" + htmlHeight
      );
      if (that.pageYOffset > scrollTop) {
        scrollTop = that.pageYOffset;
      }
      if (that.dScrollTop > scrollTop) {
        scrollTop = that.dScrollTop;
      }
      if (that.bScrollTop > scrollTop) {
        scrollTop = that.bScrollTop;
      }
      if (scrollTop + clientHeight === htmlHeight) {
        that.searchMore();
      }
    },
    ...mapActions("search", ["searchBooks", "searchMore", "selectBook"]),
    search: function() {
      Toast.loading("请稍后");
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
      this.searchBooks(data).then(res => {
        console.log('res',res);
        _this.loading = false;
        Toast.clear();
      });
    },
    onchange: function(e) {},
    clear() {
      let that = this;
      that.$router.back(-1);
      that.$store.commit("search/setQ", "");
      that.$store.commit("search/setBooks", {
        data: [],
        next_page: 0,
        next_page_url: ""
      });
    },
    selectBooks(book) {
      let that = this;
      that.$store.commit("search/setQ", "");
      that.$store.commit("search/setBooks", {
        data: [],
        next_page: 0,
        next_page_url: ""
      });
      this.selectBook(book).then(() => {
        that.$router.back(-1);
      });
    }
  }
};
</script>

<style scoped lang="scss">
.resultDiv {
  width: 100%;
  min-height: 100vh;
  background: #f7f8fa;
  .searchBar {
    width: 100%;
    padding: 10px 12px;
    box-sizing: border-box;
    border-bottom: 1px solid #f2f2f2;
    background: #ffffff;
  }
  .resultBox {
    width: 100%;
    background: #f7f8fa;
    .books {
      width: 100%;
      padding: 10px 12px;
      box-sizing: border-box;
      background: #ffffff;
      margin-top: 15px;
      .bookCover {
        width: 60px;
        margin-right: 15px;
        img {
          width: 100%;
        }
      }
      .bookInfo {
        max-width: 74%;
        .bookName {
          color: #333333;
          font-size: 14px;
        }
        .author {
          color: #666666;
          font-size: 13px;
        }
      }
    }
  }
}
</style>