<template>
  <div class="recover-first">
    <div class="box_cqh recoverTop">
      <div class="imgsBox">
        <img src="/images/image/scanImgs.png" width="76" />
      </div>
      <div class="recoverRight">
        <div class="recover-first-item">
          <div class="title">手机扫码，看收购价</div>
          <div class="hint" @click="step1click">品相说明？</div>
        </div>
        <div class="recover-first-item">
          <div class="title">快递上门，卖书包邮</div>
          <div class="hint" @click="moreRules">卖书规则？</div>
        </div>
        <div class="recover-first-item">
          <div class="title">审核通过，书款到账</div>
          <div class="hint" @click="step3click">如何审核？</div>
        </div>
      </div>
    </div>
    <div class="moreMai" @click="moreRules">
      <span>了解更多卖书规则</span>
      <img src="/images/image/scanMore.png" alt width="10" />
    </div>
    <!-- <div class="scanBtn">
      <span>扫码卖书</span>
      <img src="/images/image/scanIcon.png" alt width="16" />
    </div>
    <div class="isbnBtn">手动输入</div> -->
  </div>
</template>

<script>
export default {
  name: "RecoverSteps",
  props: ["screenWidth"],
  computed: {
    imgStyle: function() {
      return {
        width: this.screenWidth / 2 - 40 + "px",
        height: this.screenWidth / 2 - 40 + "px"
      };
    }
  },
  methods: {
    step1click: function() {
      this.$router.push("/pc/level_desc");
    },
    step3click: function() {
      this.$router.push("/pc/review_standard");
    },
    moreRules: function() {
      this.$router.push("/pc/qa");
    }
  }
};
</script>

<style scoped lang='scss'>
a {
  text-decoration: none;
  color: #ff4848;
  opacity: 0.5;
}
a:visited {
  text-decoration: none;
  color: #ff4848;
  opacity: 0.5;
}
a:active {
  text-decoration: none;
  color: #ff4848;
  opacity: 0.5;
}
a:link {
  text-decoration: none;
  color: #ff4848;
  opacity: 0.5;
}
a:hover {
  text-decoration: none;
  color: #ff4848;
  opacity: 0.5;
}
.recover-first {
  width: 100%;
  padding: 35px 21px 19px;
  box-sizing: border-box;
  border-bottom: 1px solid #F0F0F0;
  margin-bottom: 150px;
}
.recoverTop{
    width: 100%;
    justify-content: center;
}
.recoverRight {
    display: flex;
    flex-direction: column;
    height: 300px;
  .recover-first-item {
    margin-left: 24px;
    margin-bottom: 70px;
    .title {
      font-size: 17px;
      font-family: PingFang-SC;
      font-weight: bold;
      color: rgba(51, 51, 51, 1);
      line-height: 24px;
      margin-bottom: 5px;
    }
    .hint {
      font-size: 15px;
      font-family: PingFangSC;
      font-weight: 400;
      color: rgba(220, 109, 65, 1);
      line-height: 21px;
    }
  }
  .recover-first-item:last-child{
      margin-bottom: 0;
  }
}

.moreMai {
  font-size: 13px;
  font-family: PingFangSC;
  font-weight: 400;
  color: rgba(102, 102, 102, 1);
  line-height: 18px;
  text-align: center;
  margin-top: 40px;
}
.scanImgs {
}
.isbnBtn {
  font-size: 15px;
  font-family: PingFangSC;
  font-weight: 400;
  color: rgba(65, 176, 220, 1);
  line-height: 21px;
}
.scanBtn {
}
</style>