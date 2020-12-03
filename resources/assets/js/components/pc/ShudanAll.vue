<template>
  <div :class="{allShudan:true,paddingBig:screenWidth>375,paddingSmall:screenWidth<350}">
    <loading :loading="loading" style="margin: 24.5px 0;"></loading>
    <div class="sd-tabs" v-if="displayedSDs.length>0">
      <div
        class="sd-content"
        :style="contentBg(sd)"
        v-for="(sd, index) in displayedSDs"
        :key="index"
        @click.stop="onClick(sd)"
        v-if="sd.id==1"
      >
        <img :src="sd.cover" class="sd-cover" alt />
        <div class="sd-mask" :style="contentStyle(sd)" style="width: 150px;height: 190px"></div>
        <div class="sd-title">{{sd.title}}</div>
      </div>
      <div
        class="sd-content"
        :style="contentBg(sd)"
        v-for="(sd, index) in displayedSDs"
        :key="index"
        @click.stop="onClick(sd)"
        v-if="sd.id!=1"
      >
        <img :src="sd.cover" class="sd-cover" style="width: 150px;" alt />
        <div class="sd-mask" :style="contentStyle(sd)" style="width: 150px;height: 190px"></div>
        <div class="sd-title">{{sd.title}}</div>
        <div class="sd-head">
          <div
            class="head"
            v-for="(item,index) in shudanInfo[sd.id]?shudanInfo[sd.id].user:[]"
            :key="index"
            :style="{right:25*index+'px'}"
            v-if="index<3"
          >
            <img :src="item" alt />
          </div>
        </div>
        <div class="sd-recommend">
          <span>{{shudanInfo[sd.id]?shudanInfo[sd.id].count_user:0}}</span>人推荐了
          <span>{{shudanInfo[sd.id]?shudanInfo[sd.id].count_book:0}}</span>本书
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { mapGetters, mapState, mapActions } from "vuex";
import Loading from "./Loading";
export default {
  data() {
    return {
      displayedSDs: [],
      loading: true,
      headList: ["", "", ""],
      shudanInfo: "",
      screenWidth: ""
    };
  },
  components: {
    Loading
  },
  computed: {
    ...mapState({
      sds: state => state.shudan.opened
    })
  },
  created: function() {
    this.wxApi.wxConfig('','');
    let that = this;
    that.loading = true;
    this.screenWidth =
      window.innerWidth ||
      document.documentElement.clientWidth ||
      document.body.clientWidth;
    console.log(this.screenWidth);
    axios.get("/wx-api/get_opened_shudan").then(res => {
      this.displayedSDs = res.data;
      this.loading = false;
    });
  },
  mounted: function() {
    let that = this;
    axios.get("/wx-api/shudan_users").then(res => {
      console.log(res.data);
      that.shudanInfo = res.data.data;
    });
  },
  methods: {
    contentStyle: function(sd) {
      var color = sd.color ? sd.color : "#ffffff";
      return {
        background: color.colorRgba(1),
        background:
          "-moz-linear-gradient(top, " +
          color.colorRgba() +
          " 0%, " +
          color.colorRgba(0) +
          " 100%)",
        background:
          "-webkit-gradient(left top, left bottom, color-stop(0%, " +
          color.colorRgba() +
          "), color-stop(100%, " +
          color.colorRgba(0) +
          "))",
        background:
          "-webkit-linear-gradient(top, " +
          color.colorRgba() +
          " 0%, " +
          color.colorRgba(0) +
          " 100%)",
        background:
          "-o-linear-gradient(top, " +
          color.colorRgba() +
          " 0%, " +
          color.colorRgba(0) +
          " 100%)",
        background:
          "-ms-linear-gradient(top, " +
          color.colorRgba() +
          " 0%, " +
          color.colorRgba(0) +
          " 100%)",
        background:
          "linear-gradient(to bottom, " +
          color.colorRgba() +
          " 0%, " +
          color.colorRgba(0) +
          " 100%)"
      };
    },
    contentBg: function(sd) {
      var color = sd.color ? sd.color : "#ffffff";
      return {
        background: color.colorRgba(1)
      };
    },
    onClick: function(sd) {
      this.$router.push("/pc/shudan/" + sd.id);
    }
  }
};
</script>

<style lang="scss" scoped>
.paddingBig {
  padding: 25px 35px !important;
}
.paddingSmall {
  padding: 25px 7px !important;
}
.allShudan {
  width: 100%;
  min-height: 100%;
  padding: 25px;
  box-sizing: border-box;
  background: #fdfdfd;
  .sd-tabs {
    width: 100%;
    display: flex;
    flex-wrap: wrap;
    -ms-flex-wrap: wrap;
  }
  .sd-content {
    position: relative;
    width: 150px;
    height: 180px;
    padding: 5px 0;
    display: block;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0px 30px 20px -15px rgba(0, 0, 0, 0.15);
    margin: 0px 10px 25px 10px;
  }
  .sd-mask {
    position: absolute;
    top: 0;
    left: 0;
    height: 80px;
    border-top-left-radius: 2px;
    border-top-right-radius: 2px;
    z-index: 30;
  }
  .sd-cover {
    position: absolute;
    left: 0;
    bottom: 0;
    border-radius: 10px;
    z-index: 20;
    width: 150px;
    height: 190px;
    object-fit: cover;
  }
  .sd-desc {
    width: 100%;
    height: 60px;
    text-overflow: ellipsis;
    white-space: nowrap;
    overflow: hidden;
    font-size: 16px;
    color: #555555;
  }
  .sd-title {
    position: absolute;
    left: 17px;
    top: 17px;
    font-size: 17px;
    width: 110px;
    z-index: 40;
    font-family: PingFang-SC;
    font-weight: 500;
    color: rgba(255, 255, 255, 1);
  }
  .sd-head {
    position: absolute;
    bottom: 50px;
    right: 10px;
    z-index: 20;
    height: 30px;
  }
  .sd-head .head {
    width: 30px;
    height: 30px;
    display: inline-block;
    margin: 0 5px;
    position: absolute;
  }
  .sd-head img {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    border: 1px solid #ffffff;
  }
  .sd-recommend {
    width: 100%;
    position: absolute;
    text-align: center;
    bottom: 10px;
    left: 0;
    color: #ffffff;
    font-size: 12px;
    z-index: 20;
  }
  .sd-tag {
    position: absolute;
    left: -5px;
    bottom: 10px;
    z-index: 40;
  }
  .sd-all {
    background: rgba(85, 201, 219, 1);
  }
  .sd-all .title {
    font-size: 18px;
    font-family: PingFang-SC;
    font-weight: bold;
    color: rgba(255, 255, 255, 1);
  }
  .sd-all .sd-num {
    font-size: 12px;
    font-family: PingFang-SC;
    font-weight: 500;
    color: rgba(255, 255, 255, 1);
  }
  .sd-all .cover {
    margin: 47px auto 13px auto;
  }
  .sd-all .cover img {
    width: 49px;
    height: 44px;
  }
}
</style>
