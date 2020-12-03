<template>
  <div
    :style="{width: screenWidth+'px', minHeight: '136px'}"
    v-if="(item.comment_id==0)||(item.comment.open)"
  >
    <div class="cart-reminder">
      <router-link
        tag="div"
        :to="`/wechat/book/${item.book.isbn}?from=shudan`"
        class="cart-book-cover"
      >
        <img :src="item.book.cover_replace" style="width: 80px;max-height: 136px;" />
        <van-tag mark color="rgba(0,0,0,.7)" v-if="SKUs.length===0" class="sku-tag">暂时无货</van-tag>
      </router-link>
      <router-link
        tag="div"
        :to="`/wechat/book/${item.book.isbn}?from=shudan`"
        class="cart-book-detail"
        :style="detailStyle"
      >
        <div class="cart-book-name" :style="detailWidth">{{item.book.name}}</div>
        <div
          class="cart-book-author"
          :style="detailWidth"
          v-show="item.book.author"
        >{{item.book.author.trimLeft()}}</div>
        <div
          class="cart-book-douban"
          :style="detailWidth"
          v-show="parseFloat(item.book.rating_num)>0"
        >豆瓣评分：{{item.book.rating_num}}</div>
        <div class="cart-book-price-info" v-if="SKUs.length>0">
          <div class="cart-book-price">¥ {{price}}</div>
          <div class="cart-book-discount">{{discount}}折</div>
          <div class="cart-book-discount" v-if="isNew">新书</div>
        </div>
      </router-link>
    </div>
    <router-link
      tag="div"
      :to="`/wechat/sdBookComment/${item.id}`"
      class="tuijian"
    >{{item.comment_id==0?'':item.comment.body}}</router-link>
    <div class="rechead box_cqh" v-if="item.comment">
      <div>
        <img :src="item.comment?item.comment.user.avatar:''" alt />
        <span>{{item.comment?item.comment.user.nickname:''}}</span>的推荐
      </div>
      <div class="sd-menu">
        <!-- <router-link class="commentNums" tag="div" :to="`/wechat/sdBookComment/${item.id}`">
        <img src="/images/comment666.png" alt="">
        <span>15</span>
        </router-link>-->
        <div class="zanNums" @click="zan(item)">
          <img :src="item.shudan_zan_status.length>0?'/images/zangreen.png':'/images/zan666.png'" alt />
          <span>{{item.shudan_zan_users.length}}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { mapGetters, mapState, mapActions } from "vuex";
export default {
  data() {
    return {
      loading: false,
      SKUs: [],
      price: 100000,
      discount: 100,
      isNew: false
    };
  },
  props: ["item", "screenWidth", "index"],
  created: function() {
    this.SKUs = this.item.book.for_sale_skus
      ? this.item.book.for_sale_skus
      : "";
    // build price & discount
    var _this = this;
    this.SKUs.forEach(function(sku) {
      if (parseFloat(sku.price) < _this.price) {
        _this.price = parseFloat(sku.price);
        _this.discount = Number(
          (parseFloat(sku.price) * 10) / parseFloat(sku.original_price)
        ).toFixed(1);
      }
      if (sku.level === 100) {
        _this.isNew = true;
      }
    });
  },
  computed: {
    detailStyle: function() {
      return {
        width: this.screenWidth - 140 + "px",
        minHeight: "136px"
      };
    },
    detailWidth: function() {
      return {
        width: this.screenWidth - 140 + "px"
      };
    },
    chooseWidth: function() {
      return {
        maxWidth: this.screenWidth - 140 + "px"
      };
    }
  },
  methods: {
    // 点赞操作
    zan(item) {
      let that = this;
      let data ={
        item:item,
        index:that.index
      }
      axios.get("/wx-api/shudan_dianzan/" + item.comment_id).then(res => {
        console.log(res.data);
        if (res.data.status) {
          this.$emit('changeItems',data)
        }
      });
    }
  }
};
</script>

<style scoped>
.cart-reminder {
  position: relative;
  display: flex;
  flex-direction: row;
  border-top: 0.5px solid #ebedf0;
  background: white;
}
.cart-reminder:not(first-child) {
  margin-top: 10px;
}
input {
  position: absolute;
  clip: rect(0, 0, 0, 0);
}
.cart-book-cover {
  position: relative;
  display: flex;
  flex-direction: row;
  justify-content: center;
  align-items: center;
  padding: 20px;
  width: 80px;
  max-height: 136px;
  flex-wrap: wrap;
}
.sku-tag {
  position: absolute;
  left: 20px;
  top: 20px;
}
.cart-book-detail {
  position: relative;
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
  margin-top: 20px;
}
.cart-book-name {
  font-size: 16px;
  color: #3d404a;
}
.cart-book-author {
  font-size: 12px;
  color: #aaaaaa;
  text-overflow: ellipsis;
  white-space: nowrap;
  overflow: hidden;
}
.cart-book-douban {
  margin-top: 5px;
  font-size: 12px;
  color: rgba(34, 139, 34, 0.4);
  text-overflow: ellipsis;
  white-space: nowrap;
  overflow: hidden;
}
.cart-book-price-info {
  position: absolute;
  left: 1px;
  bottom: 15px;
  width: 100%;
  display: flex;
  flex-direction: row;
  align-items: center;
}
.cart-book-price {
  color: #555555;
  font-size: 17px;
}

.cart-book-discount {
  color: #ff9999;
  font-size: 12px;
  margin-left: 8px;
  padding: 1px 8px;
  border: 0.5px solid #ffd6b3;
  border-radius: 4px;
}
.tuijian {
  width: 100%;
  padding: 0 20px;
  box-sizing: border-box;
  color: #333333;
  font-size: 13px;
}
.rechead {
  width: 100%;
  padding: 0 20px;
  box-sizing: border-box;
  color: #666666;
  font-size: 12px;
  margin-top: 10px;
}
.rechead img {
  width: 25px;
  height: 25px;
  border-radius: 50%;
  vertical-align: middle;
  margin-right: 5px;
}
.rechead .sd-menu {
  display: flex;
}
.rechead .sd-menu img {
  width: 15px;
  height: 15px;
  vertical-align: sub;
}
.rechead .commentNums {
  margin-right: 20px;
}
.rechead .commentNums span {
  vertical-align: top;
}
</style>