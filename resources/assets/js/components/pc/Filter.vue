<template>
  <div class="filterComponent">
    <div class="meng" v-show="showFilter" @click.stop="closeFilter"></div>
    <div class="filter" :style="{top:top+'px'}">
      <div class="filterTop box_cqh">
        <div class="condition" @click="setIndex(0)">
          <span :class="index==0?'active':''">{{discountIndex!=-1?discountValue:'折扣'}}</span>
          <img :src="index==0?'/images/image/top.png':'/images/image/bottom.png'" />
        </div>
        <div class="condition" @click="setIndex(1)">
          <span :class="index==1?'active':''">{{fractionIndex!=-1?fractionValue:'评分'}}</span>
          <img :src="index==1?'/images/image/top.png':'/images/image/bottom.png'" />
        </div>
        <div class="condition" @click="setIndex(2)">
          <span :class="index==2?'active':''">{{priceIndex!=-1?priceValue:'价格'}}</span>
          <img :src="index==2?'/images/image/top.png':'/images/image/bottom.png'" />
        </div>
        
        <div class="condition" @click="setIndex(3)" v-if="hideMenu!=3">
          <span :class="index==3?'active':''">{{phaseIndex!=-1?phaseValue:'品相'}}</span>
          <img :src="index==3?'/images/image/top.png':'/images/image/bottom.png'" />
        </div>
      </div>
      <div class="filterShow" v-show="showFilter">
        <div class="filterBox" v-show="index==0">
          <div
            :class="discountIndex==0?'filterList discountActive':'filterList'"
            @click="setDiscount(0,'由低到高')"
          >由低到高</div>
          <div
            :class="discountIndex==1?'filterList discountActive':'filterList'"
            @click="setDiscount(1,'由高到底')"
          >由高到底</div>
        </div>
        <div class="filterBox" v-show="index==1 &&hideMenu!=3">
          <div
            :class="fractionIndex==0?'filterList fractionActive fraction':'filterList fraction'"
            @click="setFraction(0,'由高到低')"
            :style="{marginLeft:priceIndex>-1?'24%':'28%'}"
          >由高到低</div>
          <div
            :class="fractionIndex==1?'filterList fractionActive fraction':'filterList fraction'"
            @click="setFraction(1,'由低到高')"
            :style="{marginLeft:priceIndex>-1?'24%':'28%'}"
          >由低到高</div>
        </div>
        <div class="filterBox" v-show="index==1 &&hideMenu==3">
          <div
            :class="fractionIndex==0?'filterList fractionActive fraction':'filterList fraction'"
            @click="setFraction(0,'由高到低')"
            :style="{marginLeft:discountIndex>-1?'45%':'40%'}"
          >由高到低</div>
          <div
            :class="fractionIndex==1?'filterList fractionActive fraction':'filterList fraction'"
            @click="setFraction(1,'由低到高')"
            :style="{marginLeft:discountIndex>-1?'45%':'40%'}"
          >由低到高</div>
        </div>
         <div class="filterBox" v-show="index==2">
          <div class="priceBox">
            <div class="priceListBox">
              <div
                :class="priceIndex==0?'priceList active':'priceList'"
                @click="setPrice(0,'0-5','0','5')"
              >0-5元</div>
            </div>
            <div class="priceListBox">
              <div
                :class="priceIndex==1?'priceList active':'priceList'"
                @click="setPrice(1,'5-10元','5','10')"
              >5-10元</div>
            </div>
            <div class="priceListBox">
              <div
                :class="priceIndex==2?'priceList active':'priceList'"
                @click="setPrice(2,'10-20元','10','20')"
              >10-20元</div>
            </div>
            <div class="priceListBox">
              <div
                :class="priceIndex==3?'priceList active':'priceList'"
                @click="setPrice(3,'20-30元','20','30')"
              >20-30元</div>
            </div>
            <div class="priceListBox">
              <div
                :class="priceIndex==4?'priceList active':'priceList'"
                @click="setPrice(4,'30-50元','30','50')"
              >30-50元</div>
            </div>
            <div class="priceListBox">
              <div
                :class="priceIndex==5?'priceList active':'priceList'"
                @click="setPrice(5,'50-100元','50','100')"
              >50-100元</div>
            </div>
            <div class="priceListBox">
              <div
                :class="priceIndex==6?'priceList active':'priceList'"
                @click="setPrice(6,'100元以上','100','')"
              >100元以上</div>
            </div>
          </div>
          <div class="customize">
            <div class="customizeTitle">价格区间（元）</div>
            <div class="customizeBox">
              <div class="minPrice" @click="keyboardShow=true">{{minPrice}}</div>
              <div class="xian"></div>
              <div class="maxPrice" @click="keyboardShow2=true">{{maxPrice}}</div>
            </div>
          </div>
        </div>
        <div class="filterBox" v-show="index==3">
          <div
            :class="phaseIndex==0?'filterList phaseActive phase right clear':'filterList right phase clear'"
            @click="setPhase(0,'全新')"
          >全新</div>
          <div
            :class="phaseIndex==1?'filterList phaseActive phase right clear':'filterList right phase clear'"
            @click="setPhase(1,'上好')"
          >上好</div>
          <div
            :class="phaseIndex==2?'filterList phaseActive phase right clear':'filterList right phase clear'"
            @click="setPhase(2,'中等')"
          >中等</div>
        </div>
        <div class="filterBottom">
          <div class="Reset" @click="reset">重置</div>
          <div class="enter" @click="enter">确定</div>
        </div>
      </div>
    </div>
    <van-number-keyboard
      :show="keyboardShow"
      :maxlength="3"
      @blur="keyboardShow = false"
      @input="keyboard"
      @delete="delNum"
    />
    <van-number-keyboard
      :show="keyboardShow2"
      :maxlength="3"
      @blur="keyboardShow2 = false"
      @input="keyboard2"
      @delete="delNum2"
    />
  </div>
</template>

<script>
export default {
  props: ["top","hideMenu"],
  data() {
    return {
      show: true,
      index: -1,
      minPrice: "",
      maxPrice: "",
      discountIndex: -1,
      priceIndex: -1,
      fractionIndex: -1,
      phaseIndex: -1,
      keyboardShow: false, //最小价格键盘
      keyboardShow2: false, //最大价格键盘
      showFilter: false
    };
  },
  computed: {
    priceValue: function() {
      console.log('priceValue:'+'minPrice:'+this.minPrice+'maxPrice'+'maxPrice'+this.maxPrice)
      if (this.minPrice && this.maxPrice) {
        return this.minPrice + "-" + this.maxPrice + "元";
      } else if (this.minPrice && !this.maxPrice) {
        return this.minPrice + "元以上";
      } else if (!this.minPrice && this.maxPrice) {
        return this.maxPrice + "元以内";
      } else {
        return "价格";
      }
    }
  },
  methods: {
    // 选择筛选的分类
    setIndex(index) {
      this.index = index;
      this.showFilter = true;
    },
    // 选择折扣
    setDiscount(index, value) {
      this.discountIndex = index;
      this.fractionIndex =-1;
      this.discountValue = value;
      console.log('discountIndex'+this.discountIndex)
    },
    // 选择分数
    setFraction(index, value) {
      this.fractionIndex = index;
      this.discountIndex =-1;
      this.fractionValue = value;
    },
    // 选择价格
    setPrice(index, value, minPrice, maxPrice) {
      console.log(minPrice, maxPrice);
      this.priceIndex = index;
      this.minPrice = minPrice;
      this.maxPrice = maxPrice;
    },
    //选择品相
    setPhase(index, value) {
      this.phaseIndex = index;
      this.phaseValue = value;
    },
    //清空筛选
    setIndexs(indexs){
      this.index =indexs.index;
      this.discountIndex =indexs.discountIndex;
      this.priceIndex =indexs.priceIndex;
      this.fractionIndex =indexs.fractionValue;
      this.phaseIndex =indexs.phaseIndex;
      this.showFilter =indexs.showFilter;
    },
    keyboard(e) {
      let num = e.toString();
      if (this.minPrice.length < 3) {
        this.minPrice += num;
      }
      if (this.minPrice.length > 0) {
        this.priceIndex = 99;
      }
      console.log("minPrice" + this.minPrice);
    },
    delNum() {
      if (this.minPrice.length > 0) {
        this.minPrice = this.minPrice.substring(0, this.minPrice.length - 1);
      }
    },
    keyboard2(e) {
      console.log(e);
      let num = e.toString();
      if (this.maxPrice.length < 3) {
        this.maxPrice += num;
      }
      if (this.maxPrice.length > 0) {
        this.priceIndex = 99;
      }
      console.log("maxPrice" + this.maxPrice);
    },
    delNum2() {
      if (this.maxPrice.length > 0) {
        this.maxPrice = this.maxPrice.substring(0, this.maxPrice.length - 1);
      }
    },
    // 重置
    reset() {
      this.discountIndex = -1;
      this.priceIndex = -1;
      this.fractionIndex = -1;
      this.phaseIndex = -1;
      this.maxPrice = "";
      this.minPrice = "";
    },
    // 确定开始筛选
    enter() {
      if (this.minPrice && this.maxPrice) {
        if (this.minPrice - this.maxPrice > 0) {
          console.log("比较", this.minPrice, this.maxPrice);
          let minPrice = this.minPrice;
          let maxPrice = this.maxPrice;
          this.maxPrice = minPrice;
          this.minPrice = maxPrice;
        }
      }
      this.showFilter = false;
      let discount='',
          price =this.minPrice || this.maxPrice?this.minPrice+'-'+this.maxPrice:'',
          rating='',
          level='';
      switch(this.discountIndex){
        case 0 :
          discount=1;
          break;
        case 1 :
          discount=2;
          break;
        default:
          discount='';
      }
      switch(this.fractionIndex){
        case 0:
          rating =2;
          break;
        case 1:
          rating =1;
          break;
        default:
          rating ='';
      }
      switch(this.phaseIndex){
        case 0:
          level =100;
          break;
        case 1:
          level =80;
          break;
        case 2:
          level =60;
          break;
        default:
          level ='';
      }
      let data ={
        discount:discount,
        price:price,
        rating:rating,
        level:level
      }
      console.log(data)
      this.$emit('enterFilter',data)
    },
    // 关闭筛选条件
    closeFilter() {
      if (this.keyboardShow || this.keyboardShow2) {
        this.keyboardShow = false;
        this.keyboardShow2 = false;
      } else {
        this.showFilter = false;
      }
    }
  }
};
</script>

<style scoped lang='scss'>
.filterComponent {
  width: 100%;
}
.filter {
  width: 100%;
  background: #ffffff;
  position: fixed;
  left: 0;
  z-index: 999;
  border-radius: 0 0 10px 10px;
  overflow: hidden;
  .filterTop {
    width: 100%;
    padding: 5px 15px;
    box-sizing: border-box;
    .condition {
      font-size: 14px;
      font-family: PingFangSC;
      font-weight: 400;
      color: rgba(102, 102, 102, 1);
      line-height: 20px;
      .active {
        font-size: 14px;
        font-family: PingFang-SC;
        font-weight: bold;
        color: rgba(51, 51, 51, 1);
      }
      img {
        width: 5px;
        height: 3px;
        vertical-align: middle;
        margin-left: 2px;
      }
    }
    // .condition:nth-child(3),.condition:nth-child(4){
    //     text-align: right;
    // }
  }
  .filterBox {
    width: 100%;
    padding: 20px 15px;
    box-sizing: border-box;
    .filterList {
      font-size: 14px;
      font-family: PingFangSC;
      font-weight: 400;
      color: rgba(102, 102, 102, 1);
      line-height: 20px;
      margin-bottom: 26px;
    }
    .discountActive,
    .fractionActive,
    .phaseActive {
      color: #41b0dc;
      font-weight: bold;
    }
    .fraction {
      margin-left: 28%;
      float: left;
      clear: both;
      min-width: 45px;
      text-align: center;
    }
    .phase {
      margin-right: 12px;
    }
    .tab2 {
      margin-left: 58%;
    }
    .priceBox {
      width: 100%;
      display: flex;
      flex-wrap: wrap;
      box-sizing: border-box;
      .priceListBox {
        width: 33.3%;
        margin-bottom: 18px;
        .priceList {
          width: 93px;
          height: 30px;
          line-height: 30px;
          background: rgba(239, 239, 239, 1);
          border-radius: 15px;
          font-size: 13px;
          font-family: PingFangSC-Regular, PingFangSC;
          font-weight: 400;
          color: rgba(102, 102, 102, 1);
          text-align: center;
        }
        .priceList:last-child {
          margin-bottom: 0;
        }
        .active {
          border: 1px solid rgba(65, 176, 220, 1);
          color: rgba(65, 176, 220, 1);
          background: #ffffff;
        }
      }
    }
    .customize {
      width: 100%;
      .customizeTitle {
        font-size: 14px;
        font-family: PingFang-SC;
        color: rgba(102, 102, 102, 1);
        line-height: 20px;
      }
      .customizeBox {
        width: 100%;
        display: flex;
        align-items: center;
        margin-top: 10px;
        .minPrice,
        .maxPrice {
          width: 137px;
          height: 38px;
          background: rgba(239, 239, 239, 1);
          border-radius: 19px;
          color: #333333;
          text-align: center;
          line-height: 38px;
        }
        .xian {
          margin: 0 10px;
          width: 26px;
          height: 2px;
          background: #979797;
        }
      }
    }
  }
  .filterBottom {
    width: 100%;
    display: flex;
    .Reset {
      flex: 1;
      height: 47px;
      line-height: 47px;
      text-align: center;
      background: #ffffff;
      font-size: 18px;
      font-family: PingFangSC;
      font-weight: 400;
      color: rgba(51, 51, 51, 1);
      border-top: 1px solid #eeeeee;
    }
    .enter {
      flex: 1;
      height: 47px;
      line-height: 47px;
      text-align: center;
      background: #41b0dc;
      font-size: 18px;
      font-family: PingFangSC;
      font-weight: 400;
      color: #ffffff;
      border-top: 1px solid #41b0dc;
    }
  }
}
.meng {
  position: fixed;
  width: 100%;
  height: 100vh;
  overflow: hidden;
  top: 0;
  left: 0;
  background: rgba(0, 0, 0, 0.7);
  transition: all 0.5s;
}
/deep/ .van-key {
  height: 40px;
  line-height: 40px;
}
</style>