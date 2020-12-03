<template>
  <div class="shop">
    <div class="bgTop">
      <div class="bgDiv box_cqh">
        <router-link tag="div" to="/wechat/search" class="shopSearch">
          <div class="searchBox">
            <div class="searchDiv box_c">
              <div class="searchIcon">
                <img src="/images/image/search.png" width="16" height="16" />
              </div>
              <van-swipe
                :autoplay="3000"
                :vertical="true"
                :touchable="false"
                :height="20"
                :width="170"
                :show-indicators="false"
              >
                <van-swipe-item>
                  <div class="searchHint">
                    <span>1折淘好书，看完还能卖</span>
                  </div>
                </van-swipe-item>
                <van-swipe-item>
                  <div class="searchHint">
                    <span>买卖二手书，就上回流鱼</span>
                  </div>
                </van-swipe-item>
              </van-swipe>
            </div>
          </div>
        </router-link>
        <router-link
          class="getCoupon"
          to="/wechat/myCoupons?from=newCoupon"
          tag="div"
          v-if="parseInt(userId)>0"
        >
          <img src="/images/image/coupon.png" alt />
        </router-link>
      </div>
      <div class="official box_cqh">
        <img src="/images/image/guanfang.png" alt />
      </div>
      <div class="shopBanner">
        <div class="bannerBox">
          <van-swipe
            :autoplay="3000"
            indicator-color="white"
            @change="onChange"
            :show-indicators="false"
            height="115"
          >
            <van-swipe-item>
              <div>
                <img
                  src="/images/image/banner1.png"
                  height="115"
                  @click="a('https://mp.weixin.qq.com/s/EHh8yBMUP26VvDBkqkghbw')"
                />
              </div>
            </van-swipe-item>
            <van-swipe-item>
              <router-link tag="div" to="/wechat/myCoupons?from=newCoupon">
                <img src="/images/image/banner2.png" height="113" />
              </router-link>
            </van-swipe-item>
            <van-swipe-item>
              <div>
                <img
                  src="/images/image/banner3.png"
                  height="113"
                  @click="a('https://mp.weixin.qq.com/s/qEF1vhPp8nfNgjfAKdQUSQ')"
                />
              </div>
            </van-swipe-item>
          </van-swipe>
          <div class="indicators">
            <div :class="current==0?'point active':'point'"></div>
            <div :class="current==1?'point active':'point'"></div>
            <div :class="current==2?'point active':'point'"></div>
          </div>
        </div>
      </div>
      <!-- 8个分类 -->
      <div class="classfiyBox">
        <router-link
          class="menu"
          v-for="(item,index) in classFiy"
          :key="index"
          tag="div"
          :to="`/wechat/bigClassify/${item.name}`"
        >
          <img :src="item.src" alt />
          <div class="name">{{item.name}}</div>
        </router-link>
      </div>
    </div>

    <!-- 热销 -->
    <div class="hotBox">
      <div class="titleBox box_cqh">
        <div class="title">超级畅销</div>
        <div class="more" @click.stop="$router.push('/wechat/hotBook')">
          全部
          <img src="/images/image/more.png" width="7" height="8" />
        </div>
      </div>
      <loading :loading="hotBooks==0"></loading>
      <div class="bookBox" ref="hotWrapper">
        <van-swipe :autoplay="5000" height="160" :show-indicators="false">
          <van-swipe-item>
            <router-link
              class="hotBook"
              v-for="(book,index) in hotBooks"
              :key="index"
              v-if="index<4"
              :to="{path: `/wechat/book/${book.isbn}?from=shop`}"
            >
              <div class="hotBookCover">
                <img :src="book.cover_replace" alt />
                <div class="meng">
                  <div class="bought">{{parseInt(book.all_sold_sku_count)+randowNums}}人买过</div>
                </div>
              </div>
              <div class="bookName">{{book.name}}</div>
              <div class="bookPrice">¥{{skuPrice(book)}}</div>
            </router-link>
          </van-swipe-item>
          <van-swipe-item>
            <router-link
              class="hotBook"
              v-for="(book,index) in hotBooks"
              :key="index"
              v-if="index<8 &&index>3"
              :to="{path: `/wechat/book/${book.isbn}?from=shop`}"
            >
              <div class="hotBookCover">
                <img :src="book.cover_replace" alt />
                <div class="meng">
                  <div class="bought">{{parseInt(book.all_sold_sku_count)+randowNums}}人买过</div>
                </div>
              </div>
              <div class="bookName">{{book.name}}</div>
              <div class="bookPrice">¥{{skuPrice(book)}}</div>
            </router-link>
          </van-swipe-item>
          <van-swipe-item>
            <router-link
              class="hotBook"
              v-for="(book,index) in hotBooks"
              :key="index"
              v-if="index<12 &&index>7"
              :to="{path: `/wechat/book/${book.isbn}?from=shop`}"
            >
              <div class="hotBookCover">
                <img :src="book.cover_replace" alt />
                <div class="meng">
                  <div class="bought">{{parseInt(book.all_sold_sku_count)+randowNums}}人买过</div>
                </div>
              </div>
              <div class="bookName">{{book.name}}</div>
              <div class="bookPrice">¥{{skuPrice(book)}}</div>
            </router-link>
          </van-swipe-item>
          <van-swipe-item>
            <router-link
              class="hotBook"
              v-for="(book,index) in hotBooks"
              :key="index"
              v-if="index<16 &&index>11"
              :to="{path: `/wechat/book/${book.isbn}?from=shop`}"
            >
              <div class="hotBookCover">
                <img :src="book.cover_replace" alt />
                <div class="meng">
                  <div class="bought">{{parseInt(book.all_sold_sku_count)+randowNums}}人买过</div>
                </div>
              </div>
              <div class="bookName">{{book.name}}</div>
              <div class="bookPrice">¥{{skuPrice(book)}}</div>
            </router-link>
          </van-swipe-item>
          <van-swipe-item>
            <router-link
              class="hotBook"
              v-for="(book,index) in hotBooks"
              :key="index"
              v-if="index<20 &&index>15"
              :to="{path: `/wechat/book/${book.isbn}?from=shop`}"
            >
              <div class="hotBookCover">
                <img :src="book.cover_replace" alt />
                <div class="meng">
                  <div class="bought">{{parseInt(book.all_sold_sku_count)+randowNums}}人买过</div>
                </div>
              </div>
              <div class="bookName">{{book.name}}</div>
              <div class="bookPrice">¥{{skuPrice(book)}}</div>
            </router-link>
          </van-swipe-item>
          <van-swipe-item>
            <router-link
              class="hotBook"
              v-for="(book,index) in hotBooks"
              :key="index"
              v-if="index<24 &&index>19"
              :to="{path: `/wechat/book/${book.isbn}?from=shop`}"
            >
              <div class="hotBookCover">
                <img :src="book.cover_replace" alt />
                <div class="meng">
                  <div class="bought">{{parseInt(book.all_sold_sku_count)+randowNums}}人买过</div>
                </div>
              </div>
              <div class="bookName">{{book.name}}</div>
              <div class="bookPrice">¥{{skuPrice(book)}}</div>
            </router-link>
          </van-swipe-item>
          <van-swipe-item>
            <router-link
              class="hotBook"
              v-for="(book,index) in hotBooks"
              :key="index"
              v-if="index<28 &&index>23"
              :to="{path: `/wechat/book/${book.isbn}?from=shop`}"
            >
              <div class="hotBookCover">
                <img :src="book.cover_replace" alt />
                <div class="meng">
                  <div class="bought">{{parseInt(book.all_sold_sku_count)+randowNums}}人买过</div>
                </div>
              </div>
              <div class="bookName">{{book.name}}</div>
              <div class="bookPrice">¥{{skuPrice(book)}}</div>
            </router-link>
          </van-swipe-item>
        </van-swipe>
      </div>
    </div>
    <!-- 书单 -->
    <!-- <div class="shudanBox">
      <div class="titleBox box_cqh">
        <div class="title">推荐书单</div>
        <div class="more" @click.stop="$router.push('/wechat/shudanAll')">
          全部
          <img src="/images/image/more.png" width="7" height="8" />
        </div>
      </div>
    </div>
    <shudan-tabs :screen-width="screenWidth"></shudan-tabs>-->
    <!-- 4个分类 -->
    <div class="fourBox">
      <router-link class="fourMenu menu1" tag="div" to="/wechat/newbook">
        <img src="/images/image/menu1.png" />
        <span>新书专区</span>
      </router-link>
      <router-link class="fourMenu menu2" tag="div" to="/wechat/fourClassify/特价市集">
        <img src="/images/image/menu2.png" />
        <span>特价专区</span>
      </router-link>
      <router-link class="fourMenu menu3" tag="div" to="/wechat/shudanAll">
        <img src="/images/image/menu3.png" />
        <span>推荐书单</span>
      </router-link>
      <router-link class="fourMenu menu4" tag="div" to="/wechat/fourClassify/豆瓣8.5+">
        <img src="/images/image/menu4.png" />
        <span>豆瓣8.5+</span>
      </router-link>
    </div>
    <div class="user-not-subscribe" v-if="userId===0 || user.subscribe===0">
      <img src="/images/logo_main.png" style="height: 40px;margin-left: 10px" alt />
      <div class="subscribe-text">
        <span>关注「回流鱼」公众号</span>
        <br />
        <span>去买书和卖书</span>
      </div>
      <div class="subscribe-btn" @click="mask">去关注</div>
    </div>
    <div class="tabBg">
      <van-tabs
        v-model="index"
        @change="change"
        @scroll="scroll"
        sticky
        swipeable
        ellipsis="false"
        color="#44B3DD"
        line-height="2"
        line-width="45"
      >
        <van-tab v-for="(tag, index) in tags" :title="tag" :key="index" class="vanTabs">
          <van-list
            v-model="loading"
            :finished="finished"
            finished-text="没有更多了"
            @load="onLoad"
            v-if="tag!=='猜你喜欢'"
          >
            <shop-book2 :book="book" v-for="book in books" :key="book.id" v-if="!changing"></shop-book2>
            <div slot="loading">
              <loading :loading="loading"></loading>
            </div>
            <div
              :style="{width: screenWidth + 'px', height: screenHeight + 'px', padding: '0 0'}"
              v-if="changing"
            >
              <loading :loading="loading"></loading>
            </div>
          </van-list>
          <van-list
            v-model="loading"
            :finished="finished"
            finished-text="没有更多了"
            @load="onLoad"
            v-if="tag==='猜你喜欢'"
          >
            <shop-book-recommend :book="book" v-for="book in books" :key="book.id" v-if="!changing"></shop-book-recommend>
            <div slot="loading">
              <loading :loading="loading"></loading>
            </div>
            <div
              :style="{width: screenWidth + 'px', height: screenHeight + 'px', padding: '0 0'}"
              v-if="changing"
            >
              <loading :loading="loading"></loading>
            </div>
          </van-list>
        </van-tab>
      </van-tabs>
      <div style="flex-grow: 1;min-height: 50px;width: 100%;"></div>
    </div>
    <bottom-bar2 index="0"></bottom-bar2>
    <router-link
      class="getCouponX"
      to="/wechat/myCoupons?from=newCoupon"
      tag="div"
      v-if="parseInt(userId)>0&&ShowscrollTop"
    >
      <img src="/images/image/coupon.gif?date=2019-10-9" alt />
    </router-link>
    <!-- <div class="getFinancing" v-show="showFinancingBtn" @click="showActivity=true">
      <img src="/images/image/rongzi.gif" width="66" height="66" />
    </div>-->
    <van-popup v-model="showMask" :close-on-click-overlay="true">
      <div style="text-align: center">
        <img src="/images/qrcode.jpg" :width="screenWidth-100+'px'" alt />
      </div>
    </van-popup>
    <!-- 新人现金券 -->
    <div v-show="showNewUserCoupon" @click.stop="showNewUserCoupon = false" class="overlay">
      <div class="newCouponModal" @click.stop="return false">
        <img src="/images/image/newUserCoupon.png" alt />
        <div class="getCoupons" @click.stop="gotoCoupon">
          <img src="/images/image/getNewUserCoupon.png" alt width="139" />
        </div>
      </div>
    </div>
    <!-- 融资现金券 -->
    <!-- <van-popup v-model="showActivity">
      <div class="activityBox">
        <div class="couponBox">
          <img src="/images/image/couponRz.png" alt />
          <router-link class="gotoCouponBtn" tag="div" to="/wechat/myCoupons"></router-link>
          <router-link class="goReadDay" tag="div" to="/wechat/read_day">活动详情</router-link>
          <div class="activityBtn">
            <img
              src="/images/image/getRzCoupon.png"
              width="139"
              v-if="!hasCouponRz"
              @click="getActivity"
            />
            <img src="/images/image/hasRzCoupon.png" v-else width="139" />
          </div>
          <div class="closeRz">
            <img src="/images/image/couponRz_close.png" width="61" @click="showActivity=false" />
          </div>
        </div>
      </div>
    </van-popup>-->
  </div>
</template>
<style scoped>
body {
  background-color: white;
  margin: 0;
}

.read-day {
  position: relative;
}
.get-coupon {
  position: absolute;
  bottom: 10px;
  left: 0;
  width: 100%;
  height: 100px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
}
.get-coupon-btn {
  font-size: 16px;
  font-weight: bold;
  color: white;
  padding: 5px 15px;
  border: 3px dashed white;
}
.close-coupon {
  z-index: 3000;
  position: fixed;
  bottom: 50px;
  left: 0;
  width: 100%;
  height: 50px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  color: white;
}

.has-coupon-btn {
  font-size: 16px;
  font-weight: bold;
  color: #888888;
  padding: 5px 15px;
  border: 3px solid #888888;
}

.coupon-desc {
  font-size: 14px;
  color: white;
  margin-bottom: 15px;
  text-decoration: underline;
}

.el-loading-spinner i {
  color: #3d404a;
}

.el-loading-spinner .el-loading-text {
  color: #3d404a;
}

.shop {
  position: relative;
  display: flex;
  flex-direction: column;
  background: #f4f4f4;
}
.bgTop {
  width: 100%;
  background: url("/images/image/bg2.png") no-repeat;
  background-size: 100% 154px;
  background-color: #ffffff;
  margin-bottom: 7px;
}
.user-not-subscribe {
  position: absolute;
  left: 0;
  top: -6px;
  width: 100%;
  height: 60px;
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: space-between;
  background: white;
  border-bottom: 0.5px solid #eee;
}

.subscribe-text {
  font-size: 14px;
  font-weight: 300;
  flex-grow: 10;
  margin-left: 10px;
}

.subscribe-btn {
  font-size: 14px;
  color: white;
  font-weight: 300;
  background-color: #00a157;
  border-radius: 3px;
  padding: 5px 10px;
  margin-right: 15px;
}
.search-bar {
  display: flex;
  background-color: #eeeeee;
  padding: 10px 20px;
  margin: 8px 10px;
  height: 20px;
  line-height: 20px;
  border-radius: 6px;
  color: #555555;
  flex-direction: row;
  justify-content: space-between;
  align-items: center;
  font-weight: 300;
  font-size: 14px;
}

.search-icon {
  text-align: center;
  margin-right: -8px;
}
.shop .shopSearch .van-search__content {
  border-radius: 15px;
}

.shop .bgDiv {
  width: 100%;
  position: relative;
  padding: 0 16px;
  box-sizing: border-box;
}
.shop .shopSearch {
  flex: 10;
}
.shop .bgDiv .searchDiv {
  width: 100%;
  height: 37px;
  background: rgba(95, 222, 238, 0.45);
  border-radius: 15px;
  padding: 0 31px 0 17px;
  -webkit-box-sizing: border-box;
  box-sizing: border-box;
  margin: 15px auto;
}
.shop .bgDiv .searchDiv .van-swipe {
  height: 20px;
}
.shop .bgDiv .searchDiv .searchHint {
  font-size: 14px;
  font-family: PingFang-SC;
  color: rgba(195, 238, 255, 1);
  line-height: 20px;
}
.searchDiv .searchIcon img {
  vertical-align: text-bottom;
  margin-right: 5px;
}
</style>
<style lang="scss" >
.shop .van-tab--active {
  font-size: 18px !important;
  font-family: PingFang-SC;
  font-weight: bold;
  color: #41b0dc !important;
}
.shop .van-tab {
  font-size: 14px;
  font-family: PingFang-SC;
  font-weight: bold;
  color: rgba(102, 102, 102, 1);
  min-width: inherit;
  margin-right: 10px;
}
.shop .van-tab:nth-child(2) {
  // padding-left: 22px;
}
.shop .van-tabs--line .van-tabs__wrap {
  width: 100%;
  padding-right: 10%;
  padding-left: 15px;
  box-sizing: border-box;
  background: #ffffff;
}
.shop .van-popup {
  background: none;
  overflow-y: inherit;
  top: 43%;
}
.shop .allBtn {
  position: absolute;
  right: 10px;
  top: 0px;
  line-height: 44px;
  width: 7%;
  height: 100%;
  img {
    width: 16px;
    height: 16px;
    margin-top: 12px;
  }
}
.shop .leftHidden {
  width: 22px;
  background: #f8f8f8;
  position: absolute;
  left: 0px;
  top: 0px;
  height: 100%;
}
.tabBg {
  width: 100%;
  background: #ffffff;
}
.shop .tabTop {
  display: block;
  width: 100%;
  height: 30px;
  border-top-right-radius: 50px;
  border-top-left-radius: 50px;
  background: #f8f8f8;
  position: absolute;
  bottom: 0;
  left: 0;
}
.shop .getCoupon {
  flex: 2;
  text-align: right;
  align-items: center;
  font-size: 0;
  img {
    width: 45px;
  }
}
.shop .getCouponX {
  position: fixed;
  top: 65%;
  right: 0;
  z-index: 100;
  img {
    width: 71px;
  }
}
.getFinancing {
  position: fixed;
  top: 65%;
  right: 0;
  z-index: 100;
}
.shop .van-hairline--top-bottom:after {
  border-top: none;
}
.overlay {
  position: fixed;
  width: 100%;
  height: 100%;
  top: 0;
  left: 0;
  background: rgba(0, 0, 0, 0.7);
  z-index: 999;
  .newCouponModal {
    width: 257px;
    position: absolute;
    top: 40%;
    left: 50%;
    transform: translate(-50%, -50%);
    img {
      width: 100%;
    }
    .getCoupons {
      text-align: center;
      position: absolute;
      bottom: -60px;
      left: 50%;
      transform: translateX(-50%);
      img {
        width: 216px;
      }
    }
  }
}
.activityBox {
  .couponBox {
    position: relative;
    width: 280px;
    img {
      width: 100%;
    }
    .gotoCouponBtn {
      position: absolute;
      width: 70%;
      height: 80px;
      bottom: 135px;
      left: 50%;
      transform: translateX(-50%);
    }
    .goReadDay {
      font-size: 16px;
      font-family: PingFangSC-Regular, PingFangSC;
      font-weight: 400;
      color: #41b0dc;
      line-height: 22px;
      width: 68px;
      position: absolute;
      bottom: 90px;
      left: 50%;
      transform: translateX(-50%);
      text-decoration: underline;
    }
    .activityBtn {
      position: absolute;
      bottom: 19px;
      left: 50%;
      transform: translateX(-50%);
      img {
        width: 250px;
      }
    }
    .closeRz {
      position: absolute;
      bottom: -60px;
      left: 50%;
      transform: translateX(-50%);
      img {
        width: 30px;
      }
    }
  }
}
.official {
  width: 100%;
  padding: 0 14px;
  box-sizing: border-box;
  margin-bottom: 5px;
  img {
    width: 100%;
  }
}
.shopBanner {
  width: 100%;
  padding: 0 13px;
  box-sizing: border-box;
  margin-bottom: 13px;
  .bannerBox {
    width: 100%;
    position: relative;
    img {
      width: 100%;
      height: 113px;
      background: rgba(255, 255, 255, 1);
      box-shadow: 0px 1px 2px 0px rgba(0, 0, 0, 0.2);
      border-radius: 10px;
    }
    .indicators {
      position: absolute;
      bottom: 10px;
      left: 50%;
      transform: translateX(-50%);
      width: 50%;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 5px;
      .point {
        width: 3px;
        height: 3px;
        border-radius: 50%;
        background: rgba(0, 0, 0, 0.12);
        margin-right: 2px;
      }
      .active {
        width: 9px;
        height: 3px;
        background: rgba(0, 0, 0, 0.12);
        border-radius: 2px;
      }
    }
  }
}
.classfiyBox {
  width: 100%;
  padding: 0 10px;
  padding-bottom: 5px;
  box-sizing: border-box;
  display: flex;
  justify-content: space-around;
  flex-wrap: wrap;
  .menu {
    width: 25%;
    text-align: center;
    margin-bottom: 14px;
    img {
      width: 53px;
      height: 53px;
    }
    .name {
      font-size: 13px;
      font-family: PingFang-SC;
      color: rgba(51, 51, 51, 1);
      line-height: 7px;
    }
  }
}
.shudanBox {
  width: 100%;
  padding: 0 15px;
  box-sizing: border-box;
  padding-top: 15px;
  background: #ffffff;
}
.fourBox {
  width: 100%;
  padding: 17px 14px;
  box-sizing: border-box;
  display: flex;
  justify-content: space-between;
  flex-wrap: wrap;
  background: #ffffff;
  margin-bottom: 7px;
  .fourMenu {
    width: 47%;
    // max-width: 164px;
    min-height: 72px;
    box-shadow: 0px 1px 2px 0px rgba(182, 182, 182, 0.6);
    border-radius: 5px;
    padding: 0 21px;
    box-sizing: border-box;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 15px;
    font-family: PingFang-SC;
    font-weight: bold;
    color: rgba(51, 51, 51, 1);
    img {
      width: 42px;
      height: 36px;
    }
  }
  .menu1 {
    background: linear-gradient(
      226deg,
      rgba(247, 215, 215, 1) 0%,
      rgba(250, 235, 232, 1) 100%
    );
    margin-bottom: 13px;
  }
  .menu2 {
    background: linear-gradient(
      226deg,
      rgba(188, 233, 251, 1) 0%,
      rgba(231, 243, 246, 1) 100%
    );
    margin-bottom: 13px;
  }
  .menu3 {
    background: linear-gradient(
      225deg,
      rgba(254, 233, 209, 1) 0%,
      rgba(250, 243, 230, 1) 100%
    );
  }
  .menu4 {
    background: linear-gradient(
      225deg,
      rgba(212, 241, 200, 1) 0%,
      rgba(234, 244, 228, 1) 100%
    );
  }
}
.hotBox {
  width: 100%;
  padding: 0 15px;
  box-sizing: border-box;
  background-color: #ffffff;
  padding-top: 15px;
  margin-bottom: 7px;
  .bookBox {
    width: 100%;
    .van-swipe-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .hotBook {
      width: 76px;
      .hotBookCover {
        width: 100%;
        height: 110px;
        background: linear-gradient(
          180deg,
          rgba(0, 0, 0, 0) 0%,
          rgba(0, 0, 0, 0.4) 100%
        );
        border-radius: 5px;
        overflow: hidden;
        position: relative;
        margin-bottom: 6px;
        border-radius: 5px;
        border: 1px solid rgba(235, 235, 235, 1);
        img {
          width: 100%;
          height: 110px;
          object-fit: cover;
        }
        .meng {
          position: absolute;
          left: 0;
          bottom: 0;
          height: 50%;
          width: 100%;
          width: 76px;
          height: 60px;
          background: linear-gradient(
            180deg,
            rgba(0, 0, 0, 0) 0%,
            rgba(0, 0, 0, 0.4) 100%
          );
          border-bottom-right-radius: 5px;
          border-bottom-left-radius: 5px;
          .bought {
            position: absolute;
            bottom: 6px;
            left: 6px;
            font-size: 10px;
            font-family: PingFang-SC-Medium, PingFang-SC;
            color: rgba(255, 255, 255, 1);
            line-height: 14px;
          }
        }
      }
      .bookName {
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
        font-size: 12px;
        font-family: PingFang-SC-Bold, PingFang-SC;
        font-weight: bold;
        color: rgba(51, 51, 51, 1);
        line-height: 17px;
      }
      .bookPrice {
        font-size: 12px;
        font-family: PingFang-SC-Medium, PingFang-SC;
        color: #f44;
        line-height: 17px;
        margin-top: 2px;
      }
    }
  }
}
.titleBox {
  margin-bottom: 7px;
  .title {
    font-size: 18px;
    font-family: PingFang-SC-Bold, PingFang-SC;
    font-weight: bold;
    color: rgba(51, 51, 51, 1);
    line-height: 25px;
  }
  .more {
    font-size: 12px;
    font-family: PingFang-SC-Medium, PingFang-SC;
    color: rgba(102, 102, 102, 1);
    line-height: 17px;
  }
}
</style>
<script>
import wx from "weixin-js-sdk";
import { mapGetters, mapState, mapActions } from "vuex";
import ShopBook2 from "./ShopBook3";
import ShopBookRecommend from "./ShopBookRecommend2";
import BottomBar2 from "./BottomBar2";
import Loading from "./Loading";
import ShudanTabs from "./ShudanTabs";
import BScroll from "better-scroll";
import { Icon, Overlay, Toast } from "vant";
import $ from "jquery";

export default {
  data() {
    return {
      selected: 0,
      current: 0, //banner索引
      device: "",
      screenHeight: 0,
      screenWidth: 0,
      bookWidth: 0,
      y: 0,
      pageYOffset: 0,
      bScrollTop: 0,
      dScrollTop: 0,
      fixed: false,
      showMask: false,
      loading: false,
      finished: true,
      changing: false,
      showCoupon: false,
      hasCoupon: false,
      // scrollTop: 0, //滚动条到顶部的距离
      ShowscrollTop: false, //是否显示悬浮的现金券按钮
      hasCouponRz: false, //是否已经领取融资包邮券
      showActivity: false, //庆融资包邮券弹框
      showNewUserCoupon: false, //新人优惠券弹窗
      showFinancingBtn: false, //庆融资包邮券悬浮按钮
      classFiy: [
        { src: "/images/image/wen1.png", name: "文学酒" },
        { src: "/images/image/yishu2.png", name: "艺术盐" },
        { src: "/images/image/sheng3.png", name: "生活家" },
        { src: "/images/image/zhi4.png", name: "知识面" },
        { src: "/images/image/cheng5.png", name: "成长树" },
        { src: "/images/image/bi6.png", name: "必杀技" },
        { src: "/images/image/hu7.png", name: "互联网" },
        { src: "/images/image/chuang8.png", name: "创业营" }
      ],
      hotScroll: ""
      // hotBooks: [] //热销书籍
    };
  },
  watch: {},
  computed: {
    randowNums() {
      let newNums = 100;
      newNums = newNums + this.randomNum(10, 50);
      console.log("randow" + this.randomNum(10, 50), "newNums" + newNums);
      return newNums;
    },
    readDayStyle: function() {
      return {
        position: "fixed",
        top: this.screenHeight - 200 + "px",
        right: "10px",
        width: "68px",
        height: "68px"
      };
    },
    popupStyle: function() {
      return {
        width: this.screenHeight - 100 + "px"
      };
    },
    blankStyle: function() {
      var gap = 0;
      if (this.user.subscribe === 0) {
        gap += 60;
      }
      gap += 106;
      return {
        width: this.screenWidth + "px",
        height: this.screenHeight - gap + "px"
      };
    },
    qrcodeImgStyle: function() {
      return {
        width: this.screenWidth / 2.5 + "px"
      };
    },
    content: function() {
      return {
        width: (this.bookWidth + 26) * this.books.length + 20 + "px"
      };
    },
    lyWidth: function() {
      return {
        width: this.screenWidth - 105 + "px"
      };
    },
    ...mapState({
      index: state => state.user.tagIndex,
      position: state => state.user.position,
      books: state => state.books.books,
      currentPage: state => state.books.currentPage,
      nextPageUrl: state => state.books.nextPageUrl,
      userId: state => state.user.userId,
      user: state => state.user.user,
      tags: state => state.user.tags,
      sds: state => state.shudan.all,
      hotBooks: state => state.shop.hotBooks
    }),
    ...mapGetters("shop", {
      scrollTop: "scrollTop"
    })
  },
  created: function() {
    this.screenWidth =
      window.innerWidth ||
      document.documentElement.clientWidth ||
      document.body.clientWidth;
    this.screenHeight =
      window.innerHeight ||
      document.documentElement.clientHeight ||
      document.body.clientHeight;
    this.device = window.localStorage.getItem("device");
    if (this.hotBooks.length == 0 || !this.hotBooks) {
      this.$store.dispatch("shop/getHotBooks").then(res => {});
    }
    this.wxApi.wxConfig("", "");
    console.log("scrollTop:" + this.scrollTop);
    this.bookWidth = (this.screenWidth - 60) / 2.5;
    this.$store.dispatch("user/getUser").then(res => {
      console.log("getUSer");
      console.log(res.data);
      let isShowModal = window.localStorage.getItem("newCoupon"); // 1为已经显示过
      if (isShowModal == 1) {
        //是否是新用户
      } else {
        axios.get("/wx-api/get_new_user_coupons").then(res => {
          console.log(res.data.new_user);
          if (res.data.new_user) {
            this.showNewUserCoupon = true;
            window.localStorage.setItem("newCoupon", 1);
          }
        });
      }
      // if (res.data && res.data!=undefined) {
      //   let nowDate = new Date();
      //   let createDate = new Date(res.data.created_at.replace(/-/g, "/"));
      //   // let activityDate = new Date("2019/09/28 00:00:00");
      //   let s = parseInt((nowDate - createDate) / 1000); //当前时间距离第一次登陆间隔的秒数
      //   if (s < 30) {
      //     this.showNewUserCoupon = true;
      //   } else {
      //     this.showNewUserCoupon = false;
      //   }
      //   console.log(s);
      // }
      this.$store.dispatch("user/getUserTags");
    });
    this.showCoupon =
      parseInt(window.localStorage.getItem("coupon")) !== 1 && this.userId > 0;
    this.hasCoupon = parseInt(window.localStorage.getItem("coupon")) === 1;
  },
  mounted: function() {
    this.handleScroll();
    this.screenWidth =
      window.innerWidth ||
      document.documentElement.clientWidth ||
      document.body.clientWidth;
    this.screenHeight =
      window.innerHeight ||
      document.documentElement.clientHeight ||
      document.body.clientHeight;

    var dom = `
        <div class="allBtn">
        <div
          style="display: flex;flex-direction: row;justify-content: center;align-items: center;color: #7d7e80"
        >
          <img src='/images/image/all.png'>
        </div>
      </div>`;
    $(
      ".van-tabs__wrap,.van-tabs__wrap--scrollable,.van-hairline--top-bottom"
    ).append(dom);
    let that = this;
    $(".allBtn").on("click", function() {
      that.$router.push("/wechat/tags");
    });
  },
  activated: function() {
    // window.addEventListener("scroll", this.handleScroll);
    if (this.bScrollTop > 0) {
      document.body.scrollTop = this.bScrollTop;
      // console.log('document.body.scrollTop='+document.body.scrollTop);
    }
    if (this.dScrollTop > 0) {
      document.documentElement.scrollTop = this.dScrollTop;
      // console.log('document.documentElement.scrollTop='+document.documentElement.scrollTop);
    }
    if (this.pageYOffset > 0) {
      window.pageYOffset = this.pageYOffset;
      // console.log('window.pageYOffset='+window.pageYOffset);
    }
  },
  //进入该页面时，用之前保存的滚动位置赋值
  beforeRouteEnter(to, from, next) {
    next(vm => {
      document.body.scrollTop = document.documentElement.scrollTop =
        vm.scrollTop;
      console.log("vm.scrollTop" + vm.scrollTop);
    });
  },

  //在页面离开时记录滚动位置
  beforeRouteLeave(to, from, next) {
    var scrollTop = $(window).scrollTop();
    this.$store.commit("shop/setScrollTop", scrollTop);
    next();
  },

  deactivated: function() {
    $(window).unbind();
    window.removeEventListener("scroll", this.handleScroll);
  },

  methods: {
    // onClickDisabled: function() {
    //   this.$router.push("/wechat/tags");
    // },
    coupon: function() {
      this.$router.push("/wechat/share_desc");
    },
    onLoad: function() {
      console.log("onLoad");
      // 异步更新数据
      this.loading = true;
      var tag = this.tags[this.position];
      var nextPage = 1;
      if (this.nextPageUrl === "" && this.currentPage === 1) {
        nextPage = 1;
      } else {
        nextPage = Number(this.currentPage) + 1;
      }
      axios
        .get(
          "/wx-api/get_books_by_tag/" +
            tag +
            "?user=" +
            this.userId +
            "&page=" +
            nextPage
        )
        .then(res => {
          // this.books = this.books.concat(res.data.data);
          // this.currentPage = res.data.current_page;
          // this.nextPageUrl = res.data.next_page_url;
          this.$store.commit("books/addBooks", res.data);
          this.loading = false;
          // 数据全部加载完成
          if (!this.nextPageUrl) {
            this.finished = true;
          }
        });
    },
    change: function(index = 0, title) {
      this.changing = true;
      this.loading = true;
      this.finished = false;
      var newIndex = index;
      this.$store.commit("user/setPosition", newIndex);
      this.$store.commit("user/setTagIndex", index);
      var tag = this.tags[newIndex];
      console.log("shop2 change index=" + index);
      console.log("shop2 change title=" + title);
      console.log("shop2 change tags=" + JSON.stringify(this.tags));
      console.log("shop2 change newIndex=" + newIndex);
      console.log("shop2 change tag=" + tag);
      axios
        .get("/wx-api/get_books_by_tag/" + tag + "?user=" + this.userId)
        .then(res => {
          this.$store.commit("books/setBooks", res.data);
          this.loading = false;
          this.changing = false;
          // 数据全部加载完成
          if (!this.nextPageUrl) {
            this.finished = true;
          }
        });
    },
    scroll: function(info) {
      // console.log('scrollTop='+info.scrollTop);
      this.fixed = info.isFixed;
      // console.log('fixed='+this.fixed);
    },
    handleScroll: function() {
      let that = this;
      $(window).scroll(function() {
        // Toast($(window).scrollTop());
        // console.log('scroll',$(window).scrollTop())
        if ($(window).scrollTop() > 810) {
          that.ShowscrollTop = true;
        } else {
          that.ShowscrollTop = false;
        }
        if (that.finished == true && $(window).scrollTop() > 60) {
          that.finished = false;
        }
      });
      //scrollTop是浏览器滚动条的top位置
      // this.pageYOffset = window.pageYOffset;
      // var dScrollTop =
      //   window.pageYOffset ||
      //   document.body.scrollTop ||
      //   document.documentElement.scrollTop;
      // this.bScrollTop = document.body.scrollTop;
      // //下面这句主要是获取网页的总高度，主要是考虑兼容性所以把Ie支持的documentElement也写了，这个方法至少支持IE8
      // var htmlHeight = document.documentElement.scrollHeight;
      // //clientHeight是网页在浏览器中的可视高度，
      // var clientHeight = document.documentElement.clientHeight;
    },
    mask: function() {
      this.showMask = true;
    },
    readDayCoupon: function() {
      axios.get("/wx-api/read_day_coupon?user=" + this.userId).then(res => {
        this.hasCoupon = true;
        localStorage.setItem("coupon", 1);
      });
    },
    gotoReadDay: function() {
      this.$router.push("/wechat/read_day");
    },
    gotoCoupon() {
      this.showNewUserCoupon = false;
      this.$router.push("/wechat/myCoupons?from=newCoupon");
    },
    getActivity() {
      axios.get("/wechat/get_special_coupon").then(res => {
        console.log(res.data);
        if (res.data.status) {
          Toast.success(res.data.message);
          this.hasCouponRz = true;
          this.$router.push("/wechat/myCoupons");
        } else {
          Toast(res.data.message);
        }
      });
    },
    getAllCoupon() {
      axios.get("/wx-api/get_coupons").then(res => {
        console.log(res.data);
        let hasCoupon = res.data.filter(function(currentValue, index) {
          return currentValue.name == "庆融资包邮券";
        });
        if (hasCoupon.length > 0) {
          this.hasCouponRz = true;
          this.showFinancingBtn = true;
        } else {
          this.hasCouponRz = false;
          this.showActivity = true;
          this.showFinancingBtn = true;
        }
      });
    },
    getHotBook() {
      axios.get("/wx-api/get_bestseller?page=1").then(res => {
        console.log(res.data);
        this.hotBooks = res.data.data;
      });
    },
    // bannerChange
    onChange(index) {
      this.current = index;
    },
    a(href) {
      window.location.href = href;
    },
    skuPrice: function(books) {
      if (books.for_sale_skus.length === 0) {
        return Number((books.price * books.sale_discount) / 100).toFixed(2);
      } else {
        var lowestPrice = Number(books.for_sale_skus[0].price);
        books.for_sale_skus.forEach(function(sku) {
          if (Number(sku.price) < lowestPrice) {
            lowestPrice = Number(sku.price);
          }
        });
        return lowestPrice;
      }
    },
    randomNum(minNum, maxNum) {
      switch (arguments.length) {
        case 1:
          return parseInt(Math.random() * minNum + 1, 10);
          break;
        case 2:
          return parseInt(Math.random() * (maxNum - minNum + 1) + minNum, 10);
          break;
        default:
          return 0;
          break;
      }
    }
  },
  components: {
    ShopBook2,
    ShopBookRecommend,
    BottomBar2,
    Loading,
    ShudanTabs
  }
};
</script>
