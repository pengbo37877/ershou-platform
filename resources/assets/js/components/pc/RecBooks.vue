<template>
  <div class="recForm">
    <div class="booksForm">
      <div class="title">想推荐的书</div>
      <div>
        <div class="resultBox" v-if="book">
          <div class="books box">
            <div class="bookCover">
              <img :src="book.cover_replace" alt />
            </div>
            <div class="bookInfo">
              <div class="bookName">{{book.name}}</div>
              <div class="author">{{book.author}}</div>
            </div>
            <div class="changeBtn">
              <router-link to="/pc/recSearchBook">修改</router-link>
            </div>
          </div>
        </div>
        <div class="searchBar" v-else>
          <router-link tag="div" to="/pc/recSearchBook">
            <van-search placeholder="请输入搜索关键词"  />
          </router-link>
        </div>
      </div>
      <div class="title">为什么推荐这本书</div>
      <div>
        <van-cell-group>
          <van-field v-model="message" type="textarea" placeholder="请输入推荐这本书的理由" rows="4" autosize />
        </van-cell-group>
      </div>
      <div>
        <div class="title">推荐到这个书单：</div>
        <div class="shudanBox box_cqh">
          <div class="shudanBoxLeft box">
            <div class="shudanCover">
              <img :src="shudan.cover" alt />
            </div>
            <div class="shudanInfo">
              <div class="shudanName">{{shudan.title}}</div>
              <div class="shudanDesc">{{this.delHtmlTag(shudan.desc)}}</div>
            </div>
          </div>
          <div class="shudanBoxRight">
            <van-icon name="success" color="#19C3A9" size="20" />
          </div>
        </div>
      </div>
      <div class="recBox">
        <div class="recBtn" @click="recBtn">推荐</div>
      </div>
    </div>
  </div>
</template>

<script>
import recSearchBook from "./RecSearchBook";
import { mapState, mapGetters, mapActions } from "vuex";
import { Toast } from "vant";
export default {
  data() {
    return {
      message: "", //推荐理由
      shudan: "", //当前书单的数据
      bookid: ""
    };
  },
  watch: {
    $route: {
      handler: function(val, oldVal) {
        console.log('路由变化啊')
        console.log(val,oldVal);
        if(oldVal.name=='shudan' &&val.name=='recbooks'){
          this.message='';
          this.selectBook("").then(() => {});
        }
      },
      // 深度观察监听
      deep: true
    }
  },
  methods: {
    recBtn() {
      let that = this;
      console.log(that.shudan.id);
      console.log(that.book.id);
      console.log(that.message);
      if (!this.book || this.book == undefined) {
        Toast("您还没有选择要推荐的书籍");
        return false;
      }
      if (this.message == "") {
        Toast("您还没有填写要推荐此书籍的理由");
        return false;
      }
      Toast.loading({
        mask: true,
        message: "加载中..."
      });
      let data = {
        shudanId: that.shudan.id,
        book_id: that.book.id,
        reason: that.message
      };
      that.$store.dispatch("shudan/submitShudan", data).then(res => {
        Toast.clear();
        if (res.data.status) {
          Toast.success(res.data.message);
          that.selectBook("").then(() => {});
          that.$router.back(-1);
          that.message = "";
        } else {
          Toast.fail(res.data.message);
        }
      });
    },
    delHtmlTag(str) {
      return str.replace(/<[^>]+>/g, ""); //去掉所有的html标记
    },
    ...mapActions("search", ["selectBook"])
  },
  components: {
    recSearchBook
  },
  computed: {
    ...mapState({
      book: state => state.search.book
    })
  },
  created() {},
  activated() {
    this.wxApi.wxConfig("", "");
    this.shudan = JSON.parse(this.$route.query.shudan);
  }
};
</script>

<style scoped lang="scss">
.recForm {
  width: 100%;
  padding-bottom: 100px;
  .van-search {
    padding: 0;
  }
  .van-hairline--top-bottom::after,
  .van-hairline-unset--top-bottom::after {
    border-width: 0;
  }
  .booksForm {
    width: 100%;
    padding: 0 12px;
    box-sizing: border-box;
    .searchBar {
      width: 100%;
      height: 40px;
      .van-cell {
        border: 1px solid #fcfcfc;
        border-radius: 10px;
      }
    }
    .resultBox {
      width: 100%;
      background: #f7f8fa;
      .books {
        width: 100%;
        padding: 20px 0;
        box-sizing: border-box;
        background: #ffffff;
        position: relative;
        .bookCover {
          width: 60px;
          height: 102px;
          margin-right: 15px;
          font-size: 0;
          img {
            width: 100%;
          }
        }
        .bookInfo {
          .bookName {
            color: #333333;
            font-size: 14px;
            font-weight: bold;
          }
          .author {
            color: #666666;
            font-size: 12px;
          }
        }
        .changeBtn {
          border: 1px solid #fcfcfc;
          padding: 5px 20px;
          border-radius: 20px;
          text-align: center;
          font-size: 13px;
          color: #555555;
          position: absolute;
          bottom: 20px;
          right: 0;
          a {
            color: #333333;
          }
        }
      }
    }
    .title {
      font-size: 16px;
      color: #333333;
      font-weight: bold;
      margin: 15px 0;
    }
    .shudanBox {
      width: 100%;
      padding: 25px 0px;
      box-sizing: border-box;
      .shudanCover {
        width: 50px;
        height: 50px;
        margin-right: 10px;
        flex: 1;
        img {
          width: 50px;
          height: 50px;
          border-radius: 5px;
          object-fit: cover;
        }
      }
      .shudanInfo {
        flex: 6;
      }
      .shudanName {
        font-size: 14px;
        color: #666666;
      }
      .shudanDesc {
        font-size: 12px;
        color: #999999;
        overflow: hidden;
        text-overflow: ellipsis;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        line-clamp: 2;
        -webkit-box-orient: vertical;
      }
    }
    .recBox {
      width: 100%;
      padding: 20px 30px;
      padding-bottom: 40px;
      box-sizing: border-box;
      position: fixed;
      bottom: 0;
      left: 0;
      border-top: 1px solid #f2f2f2;
      background: #ffffff;
      .recBtn {
        width: 100%;
        height: 40px;
        line-height: 40px;
        text-align: center;
        color: #ffffff;
        font-size: 13px;
        background: #19c3a9;
        border-radius: 50px;
      }
    }
  }
}
</style>