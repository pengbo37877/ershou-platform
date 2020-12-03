<template>
  <div class="search-item" :length="book.for_sale_skus.length">
    <router-link
      tag="div"
      :to="{path: `/pc/book/${book.isbn}?from=search`}"
      class="search-book"
    >
      <div class="search-book-cover" style="width: 50px; min-height: 65px; max-height: 75px">
        <img :src="book.cover_replace" alt style="width: 50px; max-height: 70px" />
      </div>
      <div class="search-book-info">
        <div class="search-book-name" :style="style2">{{book.name}}</div>
        <div
          class="search-book-author"
          :style="style2"
          v-if="book.author"
        >{{book.author.trimLeft()}}</div>
        <div class="search-book-rating" :style="style2">豆瓣评分：{{book.rating_num}} <span class="level" v-for="item in level">[{{item}}]</span></div>
      </div>
    </router-link>
    <div class="search-book-sale-info" :style="saleInfo" v-if="book.sale_sku_count>0">
      <div class="search-book-price-desc">
        <div class="search-book-sale-price">
          {{salePrice}}
          <div class="search-book-many-sku" v-if="book.sale_sku_count>1">起</div>
        </div>
        <div class="search-book-sale-discount">{{saleDiscount}}折</div>
      </div>
      <!--<div class="search-book-prev-user" v-if="prevUser">-->
      <!--<img :src="prevUser.avatar" alt="" style="width: 100%;height: 100%">-->
      <!--</div>-->
      <div class="search-add-to-cart search-added" v-if="inCart">已在购物袋</div>
      <div
        class="search-add-to-cart"
        v-else-if="book.sale_sku_count==1 && book.for_sale_skus.length==1"
        @click="addSkuToCart({ sku: book.for_sale_skus[0], source:'search' })"
      >加入购物袋</div>
      <div
        class="search-add-to-cart"
        v-else-if="book.for_sale_skus.length>1"
        @click="showDialog"
      >多品相可选</div>
    </div>
    <div class="search-book-sale-info" :style="saleInfo" v-else>
      <div class="search-book-price-desc">
        <div class="search-book-sale-price">{{skuPrice(book)}}</div>
        <div class="search-book-sale-discount">{{Number(Number(book.sale_discount)/10).toFixed(1)}}折</div>
      </div>
      <div v-if="!loading">
        <vant-button
          size="small"
          class="search-add-to-reminder search-added"
          v-if="inReminder"
          @click="deleteFromReminder()"
        >取消到货提醒</vant-button>
        <vant-button
          size="small"
          class="search-add-to-reminder"
          v-if="!inReminder"
          @click="addToReminder()"
        >到货提醒</vant-button>
      </div>
      <vant-button size="small" class="search-add-to-reminder" v-if="loading" loading>loading</vant-button>
    </div>

    <van-popup v-model="dialogVisible" position="bottom">
      <div class="pop-top">
        <div class="pop-name">{{book.name}}</div>
        <div class="pop-close" @click="dialogVisible=false">
          <van-icon name="cross" size="20px" />
        </div>
      </div>
      <div class="pop-desc" style>
        <div>多个品相的书可以购买</div>
        <div style="color:#555;" @click="goLevelDesc">
          不同品相有何区别？
          <van-icon name="arrow" />
        </div>
      </div>
      <div class="bd-dialog-items">
        <div
          class="bd-dialog-item"
          :class="activeSkuStyle(index)"
          v-for="(sku,index) in book.for_sale_skus"
          @click="chooseSku(index)"
        >
          <div class="bd-dialog-item-price">￥{{sku.price}}</div>
          <div class="bd-dialog-item-desc">{{sku.title}}</div>
        </div>
      </div>
      <div class="pop-bottom">
        <van-button
          square
          type="warning"
          style="border-radius: 4px"
          size="large"
          loading
          v-if="addingToCart"
        >加入购物袋</van-button>
        <van-button
          square
          type="warning"
          style="border-radius: 4px"
          size="large"
          @click="addToCart"
          v-if="!addingToCart"
        >加入购物袋</van-button>
      </div>
    </van-popup>
  </div>
</template>

<script>
import { mapState, mapGetters, mapActions } from "vuex";
export default {
  data() {
    return {
      loading: false,
      screenWidth: 0,
      salePrice: 0,
      saleDiscount: 0,
      SKUs: [],
      dialogVisible: false,
      skuIndex: 0,
      addingToCart: false,
      newBook: ""
    };
  },
  props: ["book", "screenWidth"],
  computed: {
    style2: function() {
      return {
        width: this.screenWidth - 100 + "px"
      };
    },
    saleInfo: function() {
      return {
        width: this.screenWidth - 100 + "px",
        marginLeft: "84px"
      };
    },
    inReminder: function() {
      var _this = this;
      var r = this.reminders.find(function(reminder) {
        return reminder.book_id == _this.book.id;
      });
      return !!r;
    },
    inCart: function() {
      var _this = this;
      var r = this.items.find(function(item) {
        return item.book_id == _this.book.id;
      });
      return !!r;
    },
    // 品相
    level: function() {
      var _this = this;
      var level = [];
      _this.book.for_sale_skus.map(book => {
        level.push(_this.levelText(book.level));
      });
      level =_this.unique(level);
      // console.log('level',level)
      return level
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
  mounted: function() {},
  activated: function() {
    this.SKUs = this.book.for_sale_skus;
  },
  methods: {
    build: function() {
      var _this = this;
      if (this.book.for_sale_skus.length > 0) {
        _(this.book.for_sale_skus).forEach(function(sku) {
          if (_this.salePrice === 0) {
            _this.salePrice = Number(sku.price).toFixed(2);
            _this.saleDiscount =
            _this.getFloat((sku.price/sku.original_price)*10,1);
            _this.prevUser = sku.user;
          } else if (_this.salePrice > Number(sku.price)) {
            _this.salePrice = Number(sku.price).toFixed(2);
            _this.saleDiscount =
              _this.getFloat((sku.price/sku.original_price)*10,1);
            _this.prevUser = sku.user;
          }
        });
      }
    },
    skuPrice: function(books) {
      if (books.for_sale_skus.length === 0) {
        return Number((Number(books.price) * Number(books.sale_discount)) / 100).toFixed(2);
      } else {
        var lowestPrice = Number(books.for_sale_skus[0].price);
        books.for_sale_skus.forEach(function(sku) {
          if (lowestPrice-Number(sku.price) >0 ) {
            lowestPrice = Number(sku.price);
          }
        });
        return lowestPrice;
      }
    },
    activeSkuStyle: function(index) {
      if (index === this.skuIndex) {
        return "bd-dialog-active";
      }
    },
    chooseSku: function(index) {
      this.skuIndex = index;
    },
    showDialog: function() {
      this.dialogVisible = true;
    },
    goLevelDesc: function() {
      this.dialogVisible = false;
      this.$router.push("/pc/level_desc");
    },
    addToCart: function() {
      this.addingToCart = true;
      if (this.user === "" || this.user.length === 0) {
        this.subscribeDialogVisible = true;
        return;
      } else if (this.user.subscribe === 0) {
        this.subscribeDialogVisible = true;
        return;
      }
      var sku = this.book.for_sale_skus[this.skuIndex];
      var _this = this;
      this.$store
        .dispatch("cart/addSkuToCart", { sku, source: "search" })
        .then(res => {
          if (res.data.code && res.data.code === 500) {
            _this.$dialog.alert({
              message: res.data.msg,
              center: true
            });
          }
          _this.dialogVisible = false;
          _this.addingToCart = false;
        });
    },
    addToReminder: function() {
      this.loading = true;
      var _this = this;
      this.addBookToReminder({ book: this.book }).then(res => {
        _this.loading = false;
      });
    },
    deleteFromReminder: function() {
      this.loading = true;
      var _this = this;
      this.removeBookFromReminder({ book: this.book }).then(res => {
        _this.loading = false;
      });
    },
    levelText(level) {
      let text = "";
      if (level == 60) {
        text = "中等";
      } else if (level == 80) {
        text = "上好";
      } else {
        text = "全新";
      }
      return text;
    },
    // 数组去重
    unique(arr) {
      if (!Array.isArray(arr)) {
        console.log("type error!");
        return;
      }
      var array = [];
      for (var i = 0; i < arr.length; i++) {
        if (array.indexOf(arr[i]) === -1) {
          array.push(arr[i]);
        }
      }
      return array;
    },
    // 不四舍五入，保留一位小数
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
    ...mapActions("cart", [
      "addSkuToCart",
      "addBookToReminder",
      "removeBookFromReminder"
    ])
  }
};
</script>
<style scoped>
.search-item {
  position: relative;
}
.search-book {
  display: flex;
  flex-direction: row;
  padding: 20px 20px;
  border-bottom: 0.5px solid #eee;
}
.search-book-cover {
  display: flex;
  flex-direction: row;
  justify-content: center;
  align-items: center;
}
.search-book-info {
  display: flex;
  flex-direction: column;
  margin-left: 14px;
  position: relative;
}
.search-book-name {
  font-size: 14px;
  color: #3d404a;
  text-overflow: ellipsis;
  white-space: nowrap;
  overflow: hidden;
}
.search-book-author {
  font-size: 11px;
  color: #888888;
  text-overflow: ellipsis;
  white-space: nowrap;
  overflow: hidden;
}
.search-book-rating {
  font-size: 11px;
  color: #008000;
  -o-text-overflow: ellipsis;
  text-overflow: ellipsis;
  white-space: nowrap;
  overflow: hidden;
  /* padding: 2px; */
  margin: 0px auto;
}
.search-book-sale-info {
  display: flex;
  flex-direction: row;
  justify-content: space-between;
  align-items: center;
  position: absolute;
  left: 0;
  bottom: 10px;
}
.search-book-price-desc {
  display: flex;
  flex-direction: row;
  align-items: center;
}
.search-book-sale-price {
  font-size: 16px;
  color: #3d404a;
  display: flex;
  flex-direction: row;
  align-items: center;
}
.search-book-many-sku {
  font-size: 11px;
  color: #555555;
  margin-left: 2px;
  font-weight: lighter;
}
.search-book-sale-discount {
  width: fit-content;
  font-size: 9px;
  color: #ff4848;
  border: 0.5px solid #ffaaaa;
  border-radius: 4px;
  margin-left: 6px;
  padding: 2px 6px;
}
.search-book-prev-user {
  width: 32px;
  height: 32px;
  border-radius: 2px;
  border: 2px solid white;
  -webkit-box-shadow: 2px 2px 5px 0px rgba(204, 204, 204, 0.5);
  -moz-box-shadow: 2px 2px 5px 0px rgba(204, 204, 204, 0.5);
  box-shadow: 2px 2px 5px 0px rgba(204, 204, 204, 0.5);
}
.search-add-to-reminder {
  display: flex;
  flex-direction: row;
  align-items: center;
  padding: 0 12px;
  height: 30px;
  border-radius: 4px;
  border: 1px solid #3d404a;
  background-color: #3d404a;
  color: white;
  font-size: 13px;
  text-align: center;
}
.search-added {
  background-color: white;
  color: #3d404a;
  border: 1px solid #3d404a;
  opacity: 0.2;
}
.search-add-to-cart {
  display: flex;
  flex-direction: row;
  align-items: center;
  padding: 0 12px;
  height: 30px;
  border-radius: 4px;
  border: 1px solid #ff4848;
  background-color: #ff4848;
  color: white;
  font-size: 13px;
  text-align: center;
}
.search-mask {
  position: absolute;
  top: 18px;
  left: 23px;
  background-color: black;
  opacity: 0.3;
  display: flex;
  flex-direction: row;
  justify-content: center;
  align-items: center;
}
.search-sold-out {
  font-size: 16px;
  color: white;
  font-weight: 600;
}
.search-dialog-items {
  display: flex;
  flex-direction: column;
}
.search-dialog-item {
  border: 0.5px solid #eee;
  border-radius: 4px;
  margin-bottom: 10px;
  height: 60px;
  line-height: 60px;
  padding: 0 10px;
  display: flex;
  flex-direction: row;
  align-items: center;
}
.search-dialog-active {
  border: 2px solid #ff4848;
}
.search-ok-btn {
  font-size: 16px;
  border-radius: 4px;
  margin-bottom: 15px;
  height: 40px;
  line-height: 40px;
  text-align: center;
  color: white;
  background-color: #ff4848;
}
.search-dialog-item-price {
  display: flex;
  flex-direction: row;
  align-items: center;
  font-size: 22px;
  font-weight: 500;
  color: #3d404a;
}
.search-dialog-item-desc {
  font-size: 14px;
  font-weight: 300;
  color: #888;
  margin-left: 10px;
  line-height: 18px;
}
.search-level-dialog {
  position: fixed;
  bottom: 0;
  left: 0;
  margin-bottom: -1px;
}
.pop-top {
  display: flex;
  flex-direction: row;
  align-items: center;
  padding: 15px 20px;
  position: relative;
}
.pop-name {
  font-size: 18px;
  text-overflow: ellipsis;
  white-space: nowrap;
  overflow: hidden;
  width: 80%;
}
.pop-close {
  position: absolute;
  top: 15px;
  right: 20px;
  color: #aaaaaa;
}
.pop-desc {
  display: flex;
  flex-direction: row;
  justify-content: space-between;
  color: #ccc;
  font-size: 13px;
  padding: 0 20px 10px 20px;
}

.bd-dialog-items {
  display: flex;
  flex-direction: column;
  padding: 5px 20px 10px 20px;
}
.bd-dialog-item {
  border: 2px solid #eee;
  border-radius: 4px;
  margin-bottom: 10px;
  height: 60px;
  line-height: 60px;
  padding: 0 10px;
  display: flex;
  flex-direction: row;
  align-items: center;
}

.pop-bottom {
  padding: 0 20px 20px 20px;
}
.bd-dialog-active {
  border: 2px solid #ff4848;
}
.bd-dialog-item-price {
  display: flex;
  flex-direction: row;
  align-items: center;
  font-size: 22px;
  font-weight: 500;
  color: #3d404a;
}
.bd-dialog-item-desc {
  font-size: 14px;
  font-weight: 300;
  color: #888;
  margin-left: 10px;
  line-height: 18px;
}
.bd-level-dialog {
  position: fixed;
  bottom: 0;
  left: 0;
  margin-bottom: -1px;
}
.level{
  color:green;
  margin-left: 3px;
}
</style>