<template>
  <div class="tag-groups">
    <div class="tag-group" v-for="(group,index) in groups">
      <div class="tag-title">{{group.title.slice(0,3)}}</div>
      <div class="tags-content" :style="getTagColor(group)">
        <div class="tag" v-for="(tag,i) in group.tags" @click="tagClick(tag)">
          <!--<div class="tag-name">{{tag.name}}</div>-->
          <div class="tag-status">
            <div class="tag-selected" :style="getBackgroundColor(group)" v-if="tag.selected">
              <input type="checkbox" v-model="tag.selected" />
              <!--<i class="fal fa-check"></i>-->
              {{tag.name}}
            </div>
            <div class="tag-wrap" v-else>
              <input type="checkbox" v-model="tag.selected" />
              <!--<i class="fal fa-plus"></i>-->
              {{tag.name}}
            </div>
          </div>
        </div>
      </div>
    </div>
    <bottom-bar2></bottom-bar2>
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
  padding-bottom: 60px;
  display: flex;
  flex-direction: column;
}
.tag-group {
  margin: 0 20px;
  padding: 15px 0;
  border-bottom: 0.5px solid #eee;
  display: flex;
  flex-direction: row;
  align-items: center;
}
.tag-title {
  font-size: 18px;
  height: 40px;
  line-height: 40px;
  color: #111;
  width: 20%;
}
.tags-content {
  display: flex;
  flex-direction: row;
  flex-wrap: wrap;
  width: 80%;
  justify-content: space-around;
}
.tag {
  font-size: 15px;
  margin: 5px 2px;
}
.tag-status {
  text-align: center;
  border-radius: 4px;
}
.tag-wrap {
  padding: 5px 10px;
  border: 0.5px solid #ccc;
}
.tag-selected {
  background-color: #f2ece3;
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
</style>
<script>
import { mapGetters, mapState, mapActions } from "vuex";
import BottomBar2 from "./BottomBar2";
export default {
  data() {
    return {
      msg: "hello vue",
      count: 0,
      groups: [
        {
          title: "会捕鱼",
          color: "#aed0f1",
          selectedColor: "#3498db",
          tags: [
            { name: "至少读过两遍", selected: false },
            { name: "哪儿都有TA", selected: false },
            { name: "逢人便推荐", selected: false },
            { name: "外文原版", selected: false }
          ]
        },
        {
          title: "文学酒",
          color: "#a2d5da",
          selectedColor: "#1695a3",
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
          selectedColor: "#9500b5",
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
          selectedColor: "#ffb03b",
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
          selectedColor: "#a8a37e",
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
          selectedColor: "#FF6A2C",
          tags: [
            { name: "母婴育儿", selected: false },
            { name: "绘本故事", selected: false },
            { name: "儿童文学", selected: false }
          ]
        },
        {
          title: "必杀技",
          color: "#E7EDFE",
          selectedColor: "#3E7DFE",
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
          selectedColor: "#4CC948",
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
          selectedColor: "#f13a4f",
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
          selectedColor: "#A0A000",
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
    _(this.groups).forEach(function(group) {
      _(group.tags).forEach(function(tag) {
        _(_this.tags).forEach(function(userTag) {
          if (_.isEqual(userTag, tag.name)) {
            tag.selected = true;
          }
        });
      });
    });
    document.body.scrollTop = 0;
  },
  methods: {
    tagClick: function(tag) {
      var index = this.tags.indexOf(tag.name);
      if (index !== -1) {
        // this.tags.splice(index, 1)
        tag.selected = false;
        this.deleteTag(tag.name);
      } else {
        // this.tags.push(tag.name);
        tag.selected = true;
        this.addTag(tag.name);
      }
    },
    getBackgroundColor: function(group) {
      return {
        backgroundColor: group.color,
        color: group.selectedColor,
        border: "0.5px solid " + group.color,
        padding: "5px 10px"
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
