<template>
  <div class="shopOut">
    <div class="shop-book" ref="book">
      <van-card :price="skuPrice" :origin-price="skuOriginalPrice">
        <div slot="thumb">
          <img :src="book.cover_replace" alt @click="gotoBook" />
        </div>
        <div slot="tag">
          <!--<van-tag mark type="danger" v-if="skus.length === 1">{{tag}}</van-tag>-->
          <van-tag mark style="background-color: rgb(68, 68, 68);" v-if="skus.length === 0">{{tag}}</van-tag>
        </div>
        <div slot="title" class="book-title" @click="gotoBook">{{book.name}}</div>
        <div slot="desc" class="book-info" @click="gotoBook">
          <div class="book-subtitle" v-if="book.subtitle">{{book.subtitle}}</div>
          <div class="book-author">{{book.author?book.author.trimLeft():'暂无'}}</div>
          <div class="book-rating">豆瓣评分：{{Number(book.rating_num)===0?'暂无':book.rating_num}}</div>
        </div>
        <div slot="tags" @click="gotoBook">
          <van-tag
            plain
            v-for="(tag, index) in tags"
            :key="index"
            style="margin-right: 5px;"
          >{{tag}}</van-tag>
        </div>
        <div slot="price" v-if="this.skus.length===0">
          <span class="discount">{{((skuPrice/skuOriginalPrice)*10).toFixed(1)}}折</span>
          <van-button
            round
            size="small"
            v-if="inReminder && !reminderAdding"
            @click="removeBookFromReminder({book})"
            style="color:#888888;"
          >取消到货提醒</van-button>
          <van-button
            round
            size="small"
            @click="addReminder({book})"
            v-if="!inReminder && !reminderAdding"
          >到货提醒</van-button>
          <van-button round size="small" loading v-if="reminderAdding">到货提醒</van-button>
        </div>
        <div slot="num">
          <span class="discount">{{((skuPrice/skuOriginalPrice)*10).toFixed(1)}}折</span>
        </div>
        <!--<div slot="num" v-else>-->
        <!--<van-button round size="small" @click="addSku" v-if="!inCart && !cartAdding">加入购物袋</van-button>-->
        <!--<van-button round size="small" disabled v-if="inCart && !cartAdding">已在购物袋</van-button>-->
        <!--<van-button round size="small" loading v-if="cartAdding">加入购物袋</van-button>-->
        <!--</div>-->
      </van-card>
      <div class="sale-user" v-if="skus.length>0">
        <router-link :to="`/pc/user/${skus[0].user.mp_open_id}`" v-if="skus[0].user">
          <img class="sale-user-avatar" :src="skus[0].user.avatar" alt />
        </router-link>
      </div>
      <van-sku
        v-model="show"
        :sku="SKU"
        :goods="goods"
        :goods-id="book.id"
        hide-stock="{{true}}"
        show-add-cart-btn="{{false}}"
        reset-stepper-on-hide="{{true}}"
        disable-stepper-input="{{true}}"
        close-on-click-overlay="{{true}}"
        :initial-sku="initialSku"
        @buy-clicked="onBuyClicked"
        @add-cart="onAddCartClicked"
      >
        <!-- 自定义 sku-header-price -->
        <template slot="sku-header-price" slot-scope="props">
          <div class="van-sku__goods-price">
            <span class="van-sku__price-symbol">￥</span>
            <span class="van-sku__price-num">{{ props.price }}</span>
          </div>
        </template>
        <!-- 自定义 sku actions -->
        <template slot="sku-actions" slot-scope="props">
          <div class="van-sku-actions">
            <!-- 直接触发 sku 内部事件，通过内部事件执行 onBuyClicked 回调 -->
            <van-button
              type="primary"
              bottom-action
              @click="props.skuEventBus.$emit('sku:buy')"
            >加入购物袋</van-button>
          </div>
        </template>
      </van-sku>
    </div>
  </div>
</template>

<script>
import { mapGetters, mapState, mapActions } from "vuex";
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
    tag: function() {
      if (this.skus.length === 1) {
        if (this.skus[0].level === 100) {
          return "新书";
        }
        return "剩一本";
      } else if (this.skus.length === 0) {
        return "暂无货";
      } else {
        if (this.skus[0].level === 100) {
          return "新书";
        }
        return "";
      }
    },
    tags: function() {
      var tags = [];
      if (this.book.group1) {
        tags.push(this.book.group1);
      }
      if (this.book.group2) {
        tags.push(this.book.group2);
      }
      if (this.book.group3) {
        tags.push(this.book.group3);
      }
      if (tags.length === 0) {
        tags.push(this.book.category.split(",")[0]);
      }
      return tags;
    },
    skuPrice: function() {
      if (this.skus.length === 0) {
        return Number(
          (this.book.price * this.book.sale_discount) / 100
        ).toFixed(2);
      } else {
        var lowestPrice = Number(this.skus[0].price);
        this.skus.forEach(function(sku) {
          if (Number(sku.price) < lowestPrice) {
            lowestPrice = Number(sku.price);
          }
        });
        return lowestPrice;
      }
    },
    skuOriginalPrice: function() {
      if (this.skus.length === 0) {
        return this.book.price;
      } else {
        if (this.skus[0].book_version) {
          return this.skus[0].book_version.price;
        }
        return this.skus[0].original_price;
      }
    },
    inCart: function() {
      var _this = this;
      var r = this.items.find(function(item) {
        return item.book_id === _this.book.id;
      });
      return !!r;
    },
    inReminder: function() {
      var _this = this;
      var r = this.reminders.find(function(reminder) {
        return reminder.book_id === _this.book.id;
      });
      return !!r;
    },
    ...mapState({
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
    this.build();
  },
  methods: {
    build: function() {
      var _this = this;
      this.skus = this.book.for_sale_skus;
      this.goods = {
        title: this.book.name,
        picture: this.book.cover_replace
      };
      var tree = [];
      var v = [];
      var list = [];
      if (this.skus.length > 0) {
        this.skus.forEach(function(s) {
          v.push({
            id: s.id,
            name: s.title,
            imgUrl: s.book_version
              ? s.book_version.cover
                ? s.book_version.cover
                : _this.book.cover_replace
              : _this.book.cover_replace
          });
          list.push({
            id: s.id,
            price: s.price * 100,
            s1: s.id,
            s2: "0",
            s3: "0",
            stock_num: 1
          });
        });
        this.initialSku = {
          id: this.skus[0].id,
          price: this.skus[0].price * 100,
          s1: this.skus[0].id,
          s2: "0",
          s3: "0",
          stock_num: 1
        };
      }

      tree.push({
        k: "请选择品相",
        v: v,
        k_s: "s1"
      });
      this.SKU = {
        tree,
        list,
        price: Number(
          (this.book.price * this.book.sale_discount) / 100
        ).toFixed(2),
        stock_num: 1,
        collection_id: this.book.id,
        none_sku: false,
        hide_stock: true,
        messages: []
      };
    },
    onThumbClick: function() {
      console.log("onThumbClick");
      this.$router.push(`/pc/book/${this.book.isbn}?from=shopbook2`);
    },
    gotoBook: function() {
      console.log("gotoBook");
      this.$router.push(`/pc/book/${this.book.isbn}?from=shopbook2`);
    },
    addReminder: function({ book }) {
      this.reminderAdding = true;
      this.addBookToReminder({ book }).then(res => {
        if (res.data.code && res.data.code === 500) {
          Toast.fail(res.msg);
        } else {
          this.reminderAdding = false;
        }
      });
    },
    addSku: function() {
      if (this.skus.length === 1) {
        this.cartAdding = true;
        this.addSkuToCart({ sku: this.skus[0], source: "shopbook2" }).then(
          res => {
            if (res.data.code && res.data.code === 500) {
              Toast.fail(res.msg);
            } else {
              this.cartAdding = false;
            }
          }
        );
      } else if (this.skus.length > 1) {
        this.show = true;
        console.log("skus:\n" + JSON.stringify(this.skus));
      }
    },
    onBuyClicked: function(skuData) {
      console.log("onBuyClicked");
      console.log(skuData);
      this.show = false;
      var sku_id = skuData.selectedSkuComb.id;
      var sku = this.skus.find(s => s.id === sku_id);
      if (sku) {
        this.cartAdding = true;
        this.addSkuToCart({ sku, source: "shopbook2" }).then(res => {
          if (res.data.code && res.data.code === 500) {
            Toast.fail(res.msg);
          } else {
            this.cartAdding = false;
          }
        });
      } else {
        Toast.fail("加入购物车失败");
      }
    },
    onAddCartClicked: function(skuData) {
      console.log("onAddCartClicked");
      console.log(skuData);
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
.shopOut {
  padding: 0 15px;
  box-sizing: border-box;
  .shop-book {
    .van-card {
      border: none;
      padding: 0;
      min-height: 0;
      margin-bottom: 15px;
      .van-card__header {
        background: #ffffff;
        box-shadow: 0px 7px 10px 0px rgba(0, 0, 0, 0.05);
        border-radius: 10px;
        padding: 5px 18px;
        box-sizing: border-box;
        .van-card__bottom {
          position: relative;
          .discount {
            color: #f99;
            font-size: 12px;
            margin-left: 5px;
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
}
.shopOut:first-child {
  margin-top: 15px;
}
</style>