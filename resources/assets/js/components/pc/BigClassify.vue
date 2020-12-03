<template>
  <div class="bigClassify">
    <div class="tabBg">
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
        <van-tab v-for="(tag, index) in tags" :title="tag.name" :key="index" class="vanTabs">
          <div class="bookBox" style="marginTop:30px">
            <van-list finished-text="没有更多了" @load="onLoad" :finished="finished" v-model="loading">
              <shop-book2 :book="book" v-for="book in books" :key="book.id"></shop-book2>
            </van-list>
          </div>
        </van-tab>
      </van-tabs>
    </div>
    <!-- 筛选 -->
    <Filters  :top="44" ref="filterComment" @enterFilter="filterSearch"></Filters>
  </div>
</template>
 
<script>
import wx from "weixin-js-sdk";
import { mapGetters, mapState, mapActions } from "vuex";
import ShopBookRecommend from "./ShopBookRecommend";
import Loading from "./Loading";
import { Icon, Overlay, Toast } from "vant";
import ShopBook2 from "./ShopBook3";
import Filters from "./Filter";
export default {
  data() {
    return {
      loading: true,
      finished: false,
      index: 0,
      page: 1,
      cate: "", //大分类
      tags: "", //小分类
      books: [],
      nextPageUrl: "",
      data:{
        discount:'',
        price:'',
        rating:'',
        level:''
      },
      groups: [
        {
          title: "文学酒",
          tags: [
            { name: "新上架", selected: false },
            { name: "中国文学", selected: false },
            { name: "古典文学", selected: false },
            { name: "外国文学", selected: false },
            { name: "日本文学", selected: false },
            { name: "青春文学", selected: false },
            { name: "诗词世界", selected: false },
            { name: "散文·随笔", selected: false },
            { name: "纪实文学", selected: false },
            { name: "传记文学", selected: false },
            { name: "悬疑·推理", selected: false },
            { name: "科幻·奇幻", selected: false }
          ]
        },
        {
          title: "艺术盐",
          tags: [
            { name: "新上架", selected: false },
            { name: "电影·摄影", selected: false },
            { name: "艺术·设计", selected: false },
            { name: "书法·绘画", selected: false },
            { name: "音乐·戏剧", selected: false },
            { name: "建筑·居住", selected: false }
          ]
        },
        {
          title: "生活家",
          tags: [
            { name: "新上架", selected: false },
            { name: "时尚·化妆", selected: false },
            { name: "旅游·地理", selected: false },
            { name: "美食·健康", selected: false },
            { name: "运动·健身", selected: false },
            { name: "家居·宠物", selected: false },
            { name: "手工·工艺", selected: false }
          ]
        },
        {
          title: "知识面",
          tags: [
            { name: "新上架", selected: false },
            { name: "读点历史", selected: false },
            { name: "懂点政治", selected: false },
            { name: "了解经济", selected: false },
            { name: "管理学", selected: false },
            { name: "军事·战争", selected: false },
            { name: "社会·人类学", selected: false },
            { name: "哲学·宗教", selected: false },
            { name: "科普·涨知识", selected: false },
            { name: "国学典籍", selected: false }
          ]
        },
        {
          title: "成长树",
          tags: [
            { name: "新上架", selected: false },
            { name: "母婴育儿", selected: false },
            { name: "绘本故事", selected: false },
            { name: "儿童文学", selected: false }
          ]
        },
        {
          title: "必杀技",
          tags: [
            { name: "新上架", selected: false },
            { name: "心理学", selected: false },
            { name: "学会沟通", selected: false },
            { name: "技能提升", selected: false },
            { name: "职业进阶", selected: false },
            { name: "自我管理", selected: false },
            { name: "理财知识", selected: false },
            { name: "外语学习", selected: false },
            { name: "语言·工具", selected: false },
            { name: "爱情婚姻", selected: false }
          ]
        },
        {
          title: "互联网",
          tags: [
            { name: "新上架", selected: false },
            { name: "科技·互联网", selected: false },
            { name: "产品·运营", selected: false },
            { name: "开发·编程", selected: false },
            { name: "交互设计", selected: false }
          ]
        },
        {
          title: "创业营",
          tags: [
            { name: "新上架", selected: false },
            { name: "创业·商业", selected: false },
            { name: "科技·未来", selected: false },
            { name: "企业家", selected: false },
            { name: "管理学", selected: false }
          ]
        }
      ]
    };
  },
  created() {},
  mounted() {},
  activated() {
    if (this.$route.params.name != this.cate) {
      this.cate = this.$route.params.name;
      document.title = this.cate;
      let that = this;
      this.tags = this.groups.filter(function(item, index) {
        return item.title == that.cate;
      })[0].tags;
      this.index = 0;
      this.page = 1;
      this.nextPageUrl = "";
      this.finished = false;
      this.loading = true;
      this.books = [];
      this.onLoad();
      console.log("tags");
      console.log(this.tags);
    } else {
      document.title = this.cate;
    }
    let title = "";
    switch (this.cate) {
      case "文学酒":
        title = "回流鱼二手书买卖平台，文学书籍了解一下？";
        break;
      case "艺术盐":
        title = "回流鱼二手书买卖平台，艺术书籍了解一下？";
        break;
      case "生活家":
        title = "回流鱼，这些生活类书籍让你秒变成为生活达人";
        break;
      case "知识面":
        title = "回流鱼二手书平台，看完这些书你上知天文下知地理";
        break;
      case "成长树":
        title = "回流鱼，这些成长书是给孩子最好的礼物";
        break;
      case "必杀技":
        title = "技多不压身，回流鱼二手书平台“必杀技”了解一下？";
        break;
      case "互联网":
        title = "到回流鱼二手书平台，淘互联网科技类好书吧";
        break;
      case "创业营":
        title = "关于创业你必须知道的书，回流鱼会给你";
        break;
      default:
        title = "回流⻥，1折淘好书，看完还能卖";
    }
    let options = {
      title: title,
      desc: "买卖二手书，就上回流鱼。1折淘好书，看完还能卖！",
      link: window.location.href,
      imgUrl: window.localStorage.getItem("url") + "/images/image/logo.jpeg"
    };
  },
  beforeRouteLeave(to, from, next) {
    if (to.name == "shop") {
      console.log("返回了首页");
      this.clearData();
    }
    next();
  },
  methods: {
    onLoad: function() {
      console.log("onLoad");
      // 异步更新数据
      this.loading = true;
      var tag = this.tags[this.index].name;
      axios
        .get(
          "/wx-api/get_category_books?cate=" +
            this.cate +
            "&tag=" +
            tag +
            "&page=" +
            this.page+
            "&discount="+this.data.discount+
            "&price="+this.data.price+
            "&rating="+this.data.rating+
            "&level="+this.data.level
        )
        .then(res => {
          this.loading = false;
          this.books = this.books.concat(res.data.data);
          // 数据全部加载完成
          if (!res.data.next_page_url) {
            this.finished = true;
          } else {
            this.page += 1;
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
          "/wx-api/get_category_books?cate=" +
            this.cate +
            "&tag=" +
            tag +
            "&page=" +
            this.page+
            '&discount='+this.data.discount+
            "&price="+this.data.price+
            "&rating="+this.data.rating+
            "&level="+this.data.level
        )
        .then(res => {
          this.loading = false;
          this.books = res.data.data;
          // 数据全部加载完成
          if (!res.data.next_page_url) {
            this.finished = true;
          } else {
            this.page += 1;
          }
        });
    },
    filterSearch(data) {
      this.data =data;
      this.change(this.index)
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
    
  },
  components: {
    ShopBookRecommend,
    Loading,
    ShopBook2,
    Filters
  }
};
</script>

<style scoped lang='scss'>
.bigClassify {
  width: 100%;
  min-height: 100vh;
  background: #f8f8f8;
}
</style>
<style lang="scss">
.bigClassify .van-tab--active {
  font-size: 18px !important;
  font-family: PingFang-SC;
  font-weight: bold;
  color: #41b0dc !important;
}
.bigClassify .van-tab {
  font-size: 14px;
  font-family: PingFang-SC;
  font-weight: bold;
  color: rgba(102, 102, 102, 1);
  min-width: inherit;
}
.bigClassify .van-tabs__wrap{
  position: fixed;
}
</style>