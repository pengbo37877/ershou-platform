<template>
  <div class="tag-groups">
    <div class="tag-group" v-for="(group,index) in groups">
      <div class="tag-title">
        <img :src="group.icon" :alt="group.icon" />
        <span>{{group.title.slice(0,3)}}</span>
      </div>
      <div class="tags-content" :style="getTagColor(group)">
        <div class="tag" v-for="(tag,i) in group.tags">
          <!--<div class="tag-name">{{tag.name}}</div>-->
          <div class="tag-status">
            <div class="tag-selected" :style="getBackgroundColor(group)" v-if="tag.selected">
              <!--<i class="fal fa-check"></i>-->
              <div @click="gotoClassfiy(tag.name)">{{tag.name}}</div>
              <div class="checkbox" @click="tagClick(tag)">
                <!-- <input type="checkbox" v-model="tag.selected" /> -->
                <van-icon name="success" color="#2291BC" size="12px" />
              </div>
            </div>
            <div class="tag-wrap" v-else>
              <!--<i class="fal fa-plus"></i>-->
              <div @click="gotoClassfiy(tag.name)">{{tag.name}}</div>
              <div class="checkbox" @click="tagClick(tag)">
                <!-- <input type="checkbox" v-model="tag.selected" /> -->
                <van-icon name="plus" color="#1b1b1b" size="12px" />
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="enter" @click="complete" v-show="showBtn">完成</div>
    </div>
  </div>
</template>
<style scoped>
body {
  background-color: white;
}
input {
  position: absolute;
  clip: rect(0, 0, 0, 0);
}
.tag-groups {
  background: #f4f4f4;
  min-height: 100%;
  padding: 0 15px;
  box-sizing: border-box;
  position: relative;
  padding-bottom: 60px;
}
.tag-group {
  padding: 12px 0;
  padding-bottom: 3px;
}
.tag-title {
  font-size: 15px;
  height: 40px;
  line-height: 40px;
  color: #1b1b1b;
  margin-bottom: 16px;
  display: flex;
  align-items: center;
}
.tag-title img {
  width: 6px;
  height: 14px;
  vertical-align: sub;
  margin-right: 4px;
}
.tags-content {
  width: 100%;
  /* display: flex;
        flex-direction: row;
        flex-wrap: wrap;
        justify-content: space-around; */
}
.tag {
  width: 30%;
  font-size: 12px;
  margin-bottom: 15px;
  margin-right: 5%;
  display: inline-block;
}
.tags-content .tag:nth-child(3n) {
  margin-right: 0;
}
.tag-status {
  text-align: center;
  border-radius: 4px;
}
.tags-content .tag-wrap {
  padding: 14px 10px;
  background: rgba(255, 255, 255, 1);
  border-radius: 5px;
  color: #1b1b1b;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.tag-selected {
  background-color: #c9e6f2;
  border-radius: 5px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
a {
  text-decoration: none;
}
a:visited {
  text-decoration: none;
}
a:active {
  text-decoration: none;
}
a:link {
  text-decoration: none;
}
a:hover {
  text-decoration: none;
}
.tag-groups .enter {
  width: 76%;
  height: 46px;
  line-height: 46px;
  text-align: center;
  background: rgba(65, 176, 220, 1);
  /* box-shadow:0px 6px 10px 0px rgba(65,176,220,0.3); */
  border-radius: 15px;
  position: fixed;
  bottom: 20px;
  left: 50%;
  transform: translateX(-50%);
  font-size: 15px;
  font-family: PingFang-SC;
  font-weight: bold;
  color: rgba(255, 255, 255, 1);
}
</style>
<script>
import { mapGetters, mapState, mapActions } from "vuex";
import BottomBar2 from "./BottomBar2";
import { Icon ,Toast} from "vant";
export default {
  data() {
    return {
      msg: "hello vue",
      count: 0,
      showBtn:false,
      tagList:[],
      groups: [
        {
          title: "会捕鱼",
          color: "#aed0f1",
          selectedColor: "#2291BC",
          icon: "/images/image/1.png",
          tags: [
            { name: "至少读两遍", selected: false },
            { name: "哪儿都有TA", selected: false },
            { name: "逢人便推荐", selected: false },
            { name: "外文原版", selected: false }
          ]
        },
        {
          title: "文学酒",
          color: "#a2d5da",
          selectedColor: "#2291BC",
          icon: "/images/image/2.png",
          tags: [
            { name: "中国文学", selected: false },
            { name: "古典文学", selected: false },
            { name: "外国文学", selected: false },
            { name: "日本文学", selected: false },
            { name: "青春文学", selected: false },
            { name: "诗词世界", selected: false },
            { name: "散文·随笔", selected: false },
            { name: "纪实文学", selected: false },
            { name: "传记文学", selected: false },
            { name: "悬疑·推理", selected: false },
            { name: "科幻·奇幻", selected: false }
          ]
        },
        {
          title: "艺术盐",
          color: "#d599e1",
          selectedColor: "#2291BC",
          icon: "/images/image/3.png",
          tags: [
            { name: "电影·摄影", selected: false },
            { name: "艺术·设计", selected: false },
            { name: "书法·绘画", selected: false },
            { name: "音乐·戏剧", selected: false },
            { name: "建筑·居住", selected: false }
          ]
        },
        {
          title: "生活家",
          color: "#ffdfb1",
          selectedColor: "#2291BC",
          icon: "/images/image/4.png",
          tags: [
            { name: "时尚·化妆", selected: false },
            { name: "旅游·地理", selected: false },
            { name: "美食·健康", selected: false },
            { name: "运动·健身", selected: false },
            { name: "家居·宠物", selected: false },
            { name: "手工·工艺", selected: false }
          ]
        },
        {
          title: "知识面",
          color: "#dcdacb",
          selectedColor: "#2291BC",
          icon: "/images/image/5.png",
          tags: [
            { name: "读点历史", selected: false },
            { name: "懂点政治", selected: false },
            { name: "了解经济", selected: false },
            { name: "管理学", selected: false },
            { name: "军事·战争", selected: false },
            { name: "社会·人类学", selected: false },
            { name: "哲学·宗教", selected: false },
            { name: "科普·涨知识", selected: false },
            { name: "国学典籍", selected: false }
          ]
        },
        {
          title: "成长树",
          color: "#FDE6E0",
          selectedColor: "#2291BC",
          icon: "/images/image/1.png",
          tags: [
            { name: "母婴育儿", selected: false },
            { name: "绘本故事", selected: false },
            { name: "儿童文学", selected: false }
          ]
        },
        {
          title: "必杀技",
          color: "#E7EDFE",
          selectedColor: "#2291BC",
          icon: "/images/image/2.png",
          tags: [
            { name: "心理学", selected: false },
            { name: "学会沟通", selected: false },
            { name: "技能提升", selected: false },
            { name: "职业进阶", selected: false },
            { name: "自我管理", selected: false },
            { name: "理财知识", selected: false },
            { name: "外语学习", selected: false },
            { name: "语言·工具", selected: false },
            { name: "爱情婚姻", selected: false }
          ]
        },
        {
          title: "工作狂",
          color: "#E3F8E3",
          selectedColor: "#2291BC",
          icon: "/images/image/3.png",
          tags: [
            { name: "财务会计", selected: false },
            { name: "新闻传播", selected: false },
            { name: "市场营销", selected: false },
            { name: "投资管理", selected: false },
            { name: "法律法规", selected: false },
            { name: "广告文案", selected: false }
          ]
        },
        {
          title: "互联网",
          color: "#f9b0b9",
          selectedColor: "#2291BC",
          icon: "/images/image/4.png",
          tags: [
            { name: "科技·互联网", selected: false },
            { name: "产品·运营", selected: false },
            { name: "开发·编程", selected: false },
            { name: "交互设计", selected: false }
          ]
        },
        {
          title: "创业营",
          color: "rgb(253, 253, 188)",
          selectedColor: "#2291BC",
          icon: "/images/image/5.png",
          tags: [
            { name: "创业·商业", selected: false },
            { name: "科技·未来", selected: false },
            { name: "企业家", selected: false },
            { name: "管理学", selected: false }
          ]
        }
      ]
    };
  },
  computed: {
    ...mapState({
      user: state => state.user.user,
      tags: state => state.user.tags
    })
  },
  mounted: function() {
    var _this = this;
    this.wxApi.wxConfig('','');
    console.log(this.tags);
      this.$store.dispatch("user/getUser").then(res => {
        this.$store.dispatch("user/getUserTags").then(() => {
          _this.tagList =_this.tags;
          _(this.groups).forEach(function(group) {
            _(group.tags).forEach(function(tag) {
              _(_this.tagList).forEach(function(userTag) {
                if (_.isEqual(userTag, tag.name)) {
                  tag.selected = true;
                }
              });
            });
          });
        });
      });
    document.body.scrollTop = 0;
  },
  methods: {
    tagClick: function(tag) {
      var index = this.tagList.indexOf(tag.name);
      this.showBtn=true;
      if (index !== -1) {
        this.tagList.splice(index, 1);
        tag.selected = false;
        // this.deleteTag(tag.name);
      } else {
        this.tagList.push(tag.name);
        tag.selected = true;
        // this.addTag(tag.name);
      }
    },
    gotoClassfiy(tag) {
      console.log("tags:" + tag);
      this.$router.push("/wechat/classify/" + tag);
    },
    complete() {
      Toast.loading('请稍后...')
      let tagArray =[]
      this.tagList.map((value)=>{
        if(value!=='猜你喜欢' &&value!=='新上架' && value!=='豆瓣8.5+' &&value !=='特价市集'){
          tagArray.push(value)
        }
      })
      if(tagArray.length>0){
        tagArray=tagArray.join(',');
      }else{
        tagArray='';
      }
      console.log(tagArray)
      axios.get('/wx-api/modify_user_tag?tag='+tagArray).then((res)=>{
        console.log(res.data.msg)
        
        if(res.data.msg='success'){
          this.$store.dispatch("user/getUser").then(()=>{
            Toast.clear()
            this.$router.back()
          })
        }else{
          Toast.clear()
          Toast.fail('设置失败，请检查网络')
        }
      })
    },
    getBackgroundColor: function(group) {
      return {
        padding: "14px 10px"
      };
    },
    getTagColor: function(group) {
      return {
        color: group.selectedColor,
        opacity: 0.8
      };
    },
    ...mapActions({
      addTag: "user/addUserTag",
      deleteTag: "user/deleteUserTag"
    })
  },
  components: {
    BottomBar2
  }
};
</script>
