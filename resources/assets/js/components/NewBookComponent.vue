<template>
  <div class="shopOut">
    <div class="shop-book" ref="book">
      <van-card :price="book.price" :origin-price="book.original_price">
        <div slot="thumb">
          <img :src="book.book.cover_replace" alt @click="gotoBook" />
        </div>
        <div slot="title" class="book-title" @click="gotoBook">{{book.book.name}}</div>
        <div slot="desc" class="book-info" @click="gotoBook">
          <div class="book-subtitle" v-if="book.subtitle">{{book.book.subtitle}}</div>
          <div class="book-author">{{book.book.author?book.book.author.trimLeft():'暂无'}}</div>
          <div class="book-rating">
            豆瓣评分：{{Number(book.book.rating_num)===0?'暂无':book.book.rating_num}}
            <span
              class="level"
            >[{{book.title}}]</span>
          </div>
        </div>
        <div slot="tags" @click="gotoBook">
          <van-tag
            plain
            v-for="(tag, index) in tags"
            :key="index"
            style="margin-right: 5px;"
          >{{tag}}</van-tag>
        </div>
        <div slot="num">
          <span class="discount">{{getFloat((book.price/book.original_price)*10,1)}}折</span>
        </div>
        <div slot="num">
          <div class="search-add-to-cart search-added" v-if="inCart && !cartAdding">已在购物袋</div>
          <div class="search-add-to-cart" @click="addSku" v-if="!inCart && !cartAdding">加入购物袋</div>
          <div class="search-add-to-cart" @click="addSku" v-show="cartAdding">请稍后...</div>
        </div>
      </van-card>
    </div>
  </div>
</template>

<script>
import { mapGetters, mapState, mapActions } from "vuex";
import { Toast } from "vant";
export default {
  data() {
    return {
      screenWidth: 0,
      salePrice: 0,
      saleDiscount: 0,
      prevUser: "",
      skus: [],
      cartAdding: false,
      reminderAdding: false,
      show: false,
      goods: {},
      SKU: {},
      initialSku: {}
    };
  },
  props: ["book"],
  computed: {
    tags: function() {
      var tags = [];
      if (this.book.book.group1) {
        tags.push(this.book.book.group1);
      }
      if (this.book.book.group2) {
        tags.push(this.book.book.group2);
      }
      if (this.book.book.group3) {
        tags.push(this.book.book.group3);
      }
      if (tags.length === 0) {
        tags.push(this.book.book.category.split(",")[0]);
      }
      return tags;
    },
    inCart: function() {
      var _this = this;
      // console.log('this.cartCounts',this.cartCounts)
      var r = _this.cartCounts.find(function(item) {
        return item == _this.book.book_id;
      });
      return !!r;
    },
    ...mapState({
      cartCounts: state => state.cart.cart_counts,
      user: state => state.user.user,
      items: state => state.cart.items,
      reminders: state => state.cart.reminders
    })
  },
  created: function() {
    this.screenWidth =
      window.innerWidth ||
      document.documentElement.clientWidth ||
      document.body.clientWidth;
  },
  methods: {
    gotoBook: function() {
      console.log("gotoBook");
      this.$router.push(`/wechat/book/${this.book.isbn}?from=shopbook2`);
    },
    getFloat(num, n) {
      num = num.toString();
      let index = num.indexOf(".");
      if (index !== -1) {
        num = num.substring(0, n + index + 1);
      } else {
        num = num.substring(0);
      }
      return parseFloat(num).toFixed(n);
    },
    addSku: function() {
      this.cartAdding = true;
      console.log(this.book)
      this.addSkuToCart({ sku: this.book,source: "newBook" }).then(
        res => {
          if (res.data.code && res.data.code == 500) {
            Toast.fail(res.data.msg);
            this.cartAdding = false;
          } else {
            this.cartAdding = false;
          }
        }
      );
    },
    ...mapActions("cart", [
      "addSkuToCart",
      "addBookToReminder",
      "removeBookFromReminder"
    ])
  }
};
</script>
<style scoped lang="scss">
.van-card {
  font-size: 16px;
  border-top: 1px solid #fff0f0;
  padding: 10px 15px;
  min-height: 160px;
}

.van-card:not(:first-child) {
  margin-top: 2px;
}

.van-card__thumb {
  padding-top: 5px;
  width: 90px;
  max-height: 130px;
  margin-right: 15px;
}

.van-card__thumb img {
  width: 90px;
  max-height: 120px;
  transform: translateY(22px);
  border-radius: 5px;
  object-fit: cover;
}

.van-card__content {
  min-height: 140px;
  height: auto;
}

.van-card__tag {
  position: absolute;
  top: 9px;
  left: 0;
}

.van-card__bottom {
  margin-top: 20px;
  padding-bottom: 10px;
}

.van-sku-stepper-stock {
  display: none;
}
</style>
<style scoped lang="scss">
.shop-book {
  position: relative;
  width: 100%;
  height: auto;
}

.shop-book:not(:first-child) {
  margin-top: 10px;
}

.book-title {
  font-size: 1em;
  font-weight: normal;
  width: 90%;
  margin-top: 10px;
}

.book-subtitle {
  font-size: 12px;
  line-height: 12px;
  color: #aaaaaa;
}

.book-author {
  width: 90%;
  color: #888888;
  margin-top: 3px;
  text-overflow: ellipsis;
  white-space: nowrap;
  overflow: hidden;
}

.book-info {
  font-size: 11px;
}

.book-rating {
  color: green;
  opacity: 0.6;
  margin-top: 3px;
}
.level {
  color: green;
  margin-left: 3px;
}
.sale-user {
  position: absolute;
  top: 0;
  right: 0;
  width: 30px;
  height: 30px;
}

.sale-user-avatar {
  width: 28px;
  height: 28px;
  border: 1px solid #eeeeee;
  border-bottom-left-radius: 20px;
}
.search-add-to-cart {
  width: 75px;
  height: 25px;
  text-align: center;
  line-height: 25px;
  background: rgba(65, 176, 220, 1);
  border-radius: 5px;
  font-size: 12px;
  font-family: PingFang-SC;
  color: rgba(255, 255, 255, 1);
}
.search-added {
  opacity: 0.2;
}
.shopOut {
  .shop-book {
    .van-card {
      border: none;
      border-bottom: 1px solid #f8f6f6;
      padding: 0;
      min-height: 0;
      background: #ffffff;
      .van-card__header {
        background: #ffffff;
        padding: 5px 18px;
        box-sizing: border-box;
        .van-card__bottom {
          position: relative;
          .discount {
            color: #f99;
            font-size: 12px;
            margin-left: 8px;
            padding: 1px 4px;
            border: 0.5px solid #ffd6b3;
            border-radius: 4px;
            position: absolute;
            top: 0px;
            left: 100px;
          }
        }
      }
    }
  }
  .van-card__thumb img{
    width: 80px;
  }
  .van-card__thumb{
    margin-right: 10px;
  }
}
.shopOut .shop-book .van-card .van-card__header{
  padding: 5px 10px;
}
</style>