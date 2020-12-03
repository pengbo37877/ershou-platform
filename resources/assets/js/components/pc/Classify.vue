<template>
  <div class="classifyBox">
    <van-list v-model="loading" :finished="finished" finished-text="没有更多了" @load="onLoad">
      <shop-book2 :book="book" v-for="book in books" :key="book.id"></shop-book2>
      <div slot="loading">
        <loading :loading="loading"></loading>
      </div>
    </van-list>
  </div>
</template>

<script>
import ShopBook2 from "./ShopBook2";
import Loading from "./Loading";
import { mapGetters, mapState, mapActions } from "vuex";
export default {
  components: {
    ShopBook2,
    Loading
  },
  data() {
    return {
      finished: false,
      loading: false,
      nextPageUrl: "",
      currentPage: 1,
      tag: "",
      books: []
    };
  },
  computed: {
    ...mapState({
      userId: state => state.user.userId,
      user: state => state.user.user
    })
  },
  created() {
    this.tag = this.$route.params.tag;
    console.log(this.$route);
    this.onLoad();
    this.wxApi.wxConfig('','');
  },
  methods: {
    onLoad: function() {
      console.log("onLoad");
      // 异步更新数据
      this.loading = true;
      var nextPage = 1;
      if (this.nextPageUrl === "" && this.currentPage === 1) {
        nextPage = 1;
      } else {
        nextPage = Number(this.currentPage) + 1;
      }
      axios
        .get(
          "/wx-api/get_books_by_tag/" +
            this.tag +
            "?user=" +
            this.userId +
            "&page=" +
            nextPage
        )
        .then(res => {
          this.books = this.books.concat(res.data.data);
          this.currentPage = res.data.current_page;
          this.nextPageUrl = res.data.next_page_url;
          this.loading = false;
          console.log("this.nextPageUrl" + this.nextPageUrl);
          // 数据全部加载完成
          if (!this.nextPageUrl) {
            this.finished = true;
          }
        });
    }
  }
};
</script>

<style scoped lang="scss">
.classifyBox {
  width: 100%;
  min-height: 100vh;
  background: #f8f8f8;
  padding-top: 15px;
  box-sizing: border-box;
  .shopOut:first-child{
    margin-top: 0;
  }
}
.classifyBox .van-tab--active {
  font-size: 18px !important;
  font-family: PingFang-SC;
  font-weight: bold;
  color: #41b0dc !important;
}
.classifyBox .van-tab {
  font-size: 14px;
  font-family: PingFang-SC;
  font-weight: bold;
  color: rgba(102, 102, 102, 1);
  min-width: inherit;
  margin-right: 10px;
}
.classifyBox .van-tab:nth-child(2) {
  padding-left: 22px;
}
.classifyBox .van-tabs__line {
  background-color: #ffffff;
  background: none;
}
.classifyBox .van-tabs--line .van-tabs__wrap {
  width: 100%;
  padding-right: 15%;
  box-sizing: border-box;
  background: #f8f8f8;
}

.classifyBox .van-tabs__nav {
  background-color: #f8f8f8;
}
.van-hairline--top-bottom:after {
  border-width: 0;
}
</style>