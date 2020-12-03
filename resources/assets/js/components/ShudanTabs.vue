<template>
  <div class="sd-wrap" :style="{width:this.screenWidth+'px'}" ref="wrapper">
    <loading :loading="loading" style="margin: 84.5px 0;"></loading>
    <div class="sd-tabs" ref="sdTabs">
      <div
        class="sd-content"
        :style="contentBg(sd)"
        v-for="(sd, index) in displayedSDs"
        :key="index"
        @click="onClick(sd)"
        v-if="sd.id==1"
      >
        <img :src="sd.cover" class="sd-cover" alt />
        <div class="sd-mask" :style="contentStyle(sd)" style="width: 150px;height: 145px"></div>
        <div class="sd-title">{{sd.title}}</div>
      </div>
      <div
        class="sd-content"
        :style="contentBg(sd)"
        v-for="(sd, index) in displayedSDs"
        :key="index"
        @click="onClick(sd)"
        v-if="sd.id!=1 &&index<5"
      >
        <img :src="sd.cover" class="sd-cover" style="width: 150px;" alt />
        <div class="sd-mask" :style="contentStyle(sd)" style="width: 150px;height: 145px"></div>
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
<style scoped lang="scss">
body {
  background-color: white;
}
a {
  text-decoration: none;
}
a:visited {
  text-decoration: none;
}
a:link {
  text-decoration: none;
}
a:hover {
  text-decoration: none;
}
.sd-wrap {
  overflow: hidden;
  background: #ffffff;
  margin-bottom: 7px;
}
.sd-tabs {
  padding-bottom: 20px;
}
.sd-content {
  position: relative;
  width: 150px;
  height: 145px;
  display: inline-block;
  overflow: hidden;
  box-shadow:0px 1px 2px 0px rgba(0,0,0,0.15);
  border-radius:10px;
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
.sd-content:first-child {
  margin-left: 15px;
  margin-right: 12px;
  border-radius: 10px;
  overflow: hidden;
  box-shadow:0px 1px 2px 0px rgba(0,0,0,0.15);
}
.sd-content:not(:first-child) {
  margin-right: 15px;
  border-radius: 10px;
  overflow: hidden;
  box-shadow:0px 1px 2px 0px rgba(0,0,0,0.15);
}
.sd-cover {
  position: absolute;
  left: 0;
  bottom: 0;
  border-radius: 10px;
  z-index: 20;
  width: 150px;
  height: 145px;
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
  bottom: 40px;
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
  position: relative;
}
.sd-all .title {
  font-size: 18px;
  font-family: PingFang-SC;
  color: rgba(255, 255, 255, 1);
}
.sd-all .sd-num {
  font-size: 12px;
  font-family: PingFang-SC;
  font-weight: 500;
  color: rgba(255, 255, 255, 1);
}
.sd-all .cover {
  margin-bottom: 10px;
}
.sd-all .cover img {
  width: 49px;
  height: 44px;
}
.sd-all .allBox {
  width: 100%;
  position: absolute;
  top: 40px;
  text-align: center;
}
</style>
<script>
import { mapGetters, mapState, mapActions } from "vuex";
import BScroll from "better-scroll";
import Loading from "./Loading";
export default {
  data() {
    return {
      displayedSDs: [],
      loading: false,
      headList: ["", "", ""],
      shudanInfo: "",
      t: "",
      x: 0
    };
  },
  props: ["screenWidth"],
  computed: {
    ...mapState({
      sds: state => state.shudan.opened
    })
  },
  created: function() {
    this.wxApi.wxConfig("", "");
    if (this.sds.length === 0) {
      this.loading = true;
      this.$store.dispatch("shudan/getOpenedShudan").then(res => {
        this.displayedSDs = this.sds;
        this.loading = false;
      });
    } else {
      this.displayedSDs = this.sds;
    }
  },
  activated: function() {
    this.$nextTick(() => {
      this.sdScroll();
    });
  },
  mounted: function() {
    let that = this;
    this.$nextTick(() => {
      this.sdScroll();
    });
    axios.get("/wx-api/shudan_users").then(res => {
      console.log(res.data);
      that.shudanInfo = res.data.data;
    });
  },
  watch: {
    displayedSDs() {
      this.$nextTick(() => {
        this.sdScroll();
      });
    }
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
    bookCoverStyle: function(index) {
      var height = 120 - index * 7;
      var width = (height * 7) / 10;
      var opacity = 1 - 0.2 * index;
      return {
        position: "absolute",
        bottom: 0,
        left: index * 9 + 1 + "px",
        width: width + "px",
        height: height + "px",
        borderRadius: "4px",
        zIndex: 39 - index * 2
      };
    },
    bookImageStyle: function(index) {
      var height = 120 - index * 7;
      var width = (height * 7) / 10;
      return {
        position: "absolute",
        bottom: 0,
        left: index * 9 + 1 + "px",
        width: width + "px",
        height: height + "px",
        borderRadius: "4px",
        zIndex: 39 - index * 2,
        "-webkit-box-shadow": "5px 0px 5px 0px rgba(70,70,70,0.1)",
        "-moz-box-shadow": "5px 0px 5px 0px rgba(70,70,70,0.1)",
        "box-shadow": "5px 0px 5px 0px rgba(70,70,70,0.1)"
      };
    },
    bookMaskStyle: function(index) {
      if (index === 0) {
        return {
          display: "none"
        };
      }
      var height = 120 - index * 7;
      var width = (height * 7) / 10;
      return {
        position: "absolute",
        bottom: 0,
        left: index * 9 + 1 + "px",
        borderRadius: "4px",
        width: width + "px",
        height: height + "px",
        zIndex: 39 - index * 2 + 1,
        background: "rgba(0,0,0,.2)"
      };
    },
    onClick: function(sd) {
      this.$router.push("/wechat/shudan/" + sd.id);
    },
    sdScroll: function() {
      if (this.displayedSDs.length > 0) {
        this.$nextTick(() => {
          if (!this.scroll) {
            var width = 5 * 150 + 130;
            this.$refs.sdTabs.style.width = width + "px";
            this.scroll = new BScroll(this.$refs.wrapper, {
              startX: 0,
              click: true,
              scrollX: true,
              scrollY: false,
              eventPassthrough: "vertical",
              bounce: {
                top: false,
                bottom: false,
                left: true,
                right: true
              }
            });
            this.scroll.on("scroll", function(e) {
              this.x = e.x;
              console.log("scroll" + this.x);
            });
            this.scroll.on("scrollEnd", function(e) {
              console.log("scrollEnd" + e.x);
              window.clearInterval(this.t);
            });
            console.log(this.scroll);
            // this.scrollTo(true);
          } else {
            this.scroll.refresh();
          }
        });
      }
    },
    scrollTo(flag) {
      let that = this;
      that.t = window.setInterval(function() {
        console.log("this.x" + that.x);
        that.x = that.x - 1;
        that.scroll.scrollTo(that.x, 0, 100, "bounce");
      }, 100);
    }
  },
  components: {
    Loading
  }
};
</script>
