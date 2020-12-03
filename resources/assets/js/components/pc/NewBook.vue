<template>
  <div class="newBook">
    <div class="bookBox" style="marginTop:0px">
      <van-list finished-text="没有更多了" @load="onLoad" :finished="finished" v-model="loading">
        <newBook :book="book" v-for="book in books" :key="book.id"></newBook>
      </van-list>
    </div>
    <!-- <Filters :top="0" ref="filterComment" @enterFilter="filterSearch" :hideMenu='3'></Filters> -->
  </div>
</template>

<script>
import wx from "weixin-js-sdk";
import { mapGetters, mapState, mapActions } from "vuex";
import ShopBookRecommend from "./ShopBookRecommend";
import { Icon, Overlay, Toast } from "vant";
import ShopBook2 from "./ShopBook3";
import newBook from './NewBookComponent'
import Filters from "./Filter";
export default {
  data() {
    return {
      loading: true,
      finished: false,
      page: 1,
      books: [],
      nextPageUrl: "",
      data: {
        discount: "",
        price: "",
        rating: "",
        level: ""
      }
    };
  },
  components: {
    ShopBookRecommend,
    ShopBook2,
    Filters,
    newBook
  },
//   beforeRouteLeave(to, from, next) {
//     if (to.name == "shop") {
//       console.log("返回了首页");
//       this.clearData();
//     }
//     next();
//   },
  created() {
      this.onLoad();
  },
  activated() {
    
  },
  computed: {
    ...mapState({
      userId: state => state.user.userId,
      user: state => state.user.user
    })
  },
  methods: {
    onLoad() {
      console.log("onLoad");
      this.loading = true;
      axios
        .get(
          "/wx-api/get_new_books" +
            "?page=" +
            this.page
        )
        .then(res => {
          this.loading = false;
          this.nextPageUrl = res.data.next_page_url;
          if (this.nextPageUrl) {
            this.books = this.books.concat(res.data.data);
            this.page += 1;
          }
          // 数据全部加载完成
          if (!this.nextPageUrl) {
            this.finished = true;
          }
        });
    },
    filterSearch(data) {
      this.data =data;
      this.page =1;
      this.books=[];
      this.onLoad()
    },
    clearData(){
      this.data={
        discount:'',
        price:'',
        rating:'',
        level:''
      }
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
  }
};
</script>

<style>
.newBook {
  width: 100%;
}
</style>