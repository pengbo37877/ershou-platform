<template>
  <div class="FourClassify">
    <div class="bookBox" style="marginTop:30px">
      <van-list finished-text="没有更多了" @load="onLoad" :finished="finished" v-model="loading">
        <shop-book2 :book="book" v-for="book in books" :key="book.id"></shop-book2>
      </van-list>
    </div>
    <Filters :top="0" ref="filterComment" @enterFilter="filterSearch"></Filters>
  </div>
</template>

<script>
import wx from "weixin-js-sdk";
import { mapGetters, mapState, mapActions } from "vuex";
import ShopBookRecommend from "./ShopBookRecommend";
import { Icon, Overlay, Toast } from "vant";
import ShopBook2 from "./ShopBook3";
import Filters from "./Filter";
export default {
  data() {
    return {
      loading: true,
      finished: false,
      page: 1,
      tag: "",
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
    Filters
  },
  beforeRouteLeave(to, from, next) {
    if (to.name == "shop") {
      console.log("返回了首页");
      this.clearData();
    }
    next();
  },
  created() {},
  activated() {
    console.log(this.$router);
    if(this.$route.params.tags=="豆瓣8.5+"){
      document.title ="豆瓣8.5+";
    }else{
      document.title="特价专区"
    }
    
    if (this.tag != this.$route.params.tags) {
      this.tag = this.$route.params.tags;
      this.books =[];
      this.page =1;
      this.finished = false;
      this.loading =true;
      if (!this.userId) {
        this.$store.dispatch("user/getUser").then(res => {
          this.onLoad();
        });
      }else{
          this.onLoad();
      }
    }
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
      console.log(this.tag)
      axios
        .get(
          "/wx-api/get_category_books?cate=其他&tag=" +
            this.tag +
            "&user=" +
            this.userId +
            "&page=" +
            this.page+
            "&discount="+this.data.discount+
            "&price="+this.data.price+
            "&rating="+this.data.rating+
            "&level="+this.data.level
        )
        .then(res => {
          this.loading = false;
          this.nextPageUrl = res.data.next_page_url;
          // 数据全部加载完成
          if (!this.nextPageUrl) {
            this.finished = true;
          }else{
            this.books = this.books.concat(res.data.data);
            this.page += 1;
          }
        });
    },
    filterSearch(data) {
      this.data =data;
      this.books=[];
      this.page =1;
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
.FourClassify {
  width: 100%;
}
</style>