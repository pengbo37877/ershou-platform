<template>
  <div class="hotBooks">
    <van-tabs
      v-model="index"
      @change="change"
      @scroll="scroll"
      sticky
      swipeable
      title-active-color="#44B3DD"
      title-inactive-color="#666666"
      color="#44B3DD"
      ellipsis="false"
    >
      <van-tab v-for="(tag, index) in tags" :title="tag" :key="index" class="vanTabs">
        <div class="bookBox" style="marginTop:30px">
          <van-list v-model="loading" :finished="finished" finished-text="没有更多了" @load="getBooks">
            <shop-book2 :book="book" v-for="book in books" :key="book.id"></shop-book2>
          </van-list>
        </div>
      </van-tab>
    </van-tabs>
    <!-- 筛选 -->
    <Filters ref="filterComment" @enterFilter="filterSearch" :top="44"></Filters>
  </div>
</template>

<script>
import ShopBook2 from "./ShopBook3";
import Filters from "./Filter";
export default {
  data() {
    return {
      index: 0,
      page: 1,
      books: [],
      tags: ["新上架", "至少读两遍", "哪儿都有TA", "逢人便推荐"],
      loading: false,
      finished: false,
      nextPageUrl: "",
      data: {
        discount: "",
        price: "",
        rating: "",
        level: ""
      }
    };
  },
  created() {},
  activated() {
    let options = {
      title: "回流鱼这些书，都是超级畅销耐读的",
      desc: "买卖二手书，就上回流鱼。1折淘好书，看完还能卖！",
      link: window.location.href,
      imgUrl: window.localStorage.getItem("url") + "/images/image/logo.jpeg"
    };
    this.wxApi.wxConfig(options, "hotBook");
  },
  beforeRouteLeave(to, from, next) {
    if (to.name == "shop") {
      console.log("返回了首页");
      this.clearData();
    }
    next();
  },
  methods: {
    getBooks() {
      this.loading = true;
      this.finished = false;
      axios
        .get(
          "/wx-api/get_bestseller?tag=" +
            this.tags[this.index] +
            "&page=" +
            this.page +
            "&discount=" +
            this.data.discount +
            "&price=" +
            this.data.price +
            "&rating=" +
            this.data.rating +
            "&level=" +
            this.data.level
        )
        .then(res => {
          console.log(res.data);
          this.books = this.books.concat(res.data.data);
          this.loading = false;
          if (res.data.data.length > 0) {
            this.page += 1;
          }
          if (!res.data.next_page_url) {
            this.finished = true;
          }
        });
    },
    change(index) {
      this.page = 1;
      this.index = index;
      this.books = [];
      this.loading = true;
      this.finished = false;
      var tag = this.tags[this.index].name;
      axios
        .get(
          "/wx-api/get_bestseller?tag=" +
            this.tags[this.index] +
            "&page=" +
            this.page+
            "&discount="+this.data.discount+
            "&price="+this.data.price+
            "&rating="+this.data.rating+
            "&level="+this.data.level
        )
        .then(res => {
          console.log(res.data);
          this.books = res.data.data;
          this.loading = false;
          if (res.data.data.length > 0) {
            this.page += 1;
          }
          if (!res.data.next_page_url) {
            this.finished = true;
          }
        });
    },
    filterSearch(data) {
      this.data = data;
      this.change(this.index);
    },
    clearData() {
      this.data = {
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
    }
  },
  components: {
    ShopBook2,
    Filters
  }
};
</script>

<style scoped lang='scss'>
.hotBooks {
  width: 100%;
  min-height: 100vh;
  background: #f8f8f8;
  .shopOut {
    margin-top: 0;
  }
}
</style>
<style lang="scss">
.hotBooks .van-tab--active {
  font-size: 18px !important;
  font-family: PingFang-SC;
  font-weight: bold;
  color: #41b0dc !important;
}
.hotBooks .van-tab {
  font-size: 14px;
  font-family: PingFang-SC;
  font-weight: bold;
  color: rgba(102, 102, 102, 1);
  min-width: inherit;
}
.hotBooks .van-tabs__wrap {
  position: fixed;
}
</style>