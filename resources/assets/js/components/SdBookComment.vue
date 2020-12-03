<template>
  <div class="sdBookComment">
    <loading :loading="loading" v-if="loading"></loading>
    <div v-else>
      <div :style="{backgroundImage: 'url('+shudan.cover+')', backgroundPosition: 'center center',backgroundSize: 'cover',width:'100%',height:'120px'}">
        <div
          class="top box_cqh"
          :style="contentStyle"
        >
          <div class="topLeft">
            <div class="sdNums">书单·{{shudanDetail.count_user}}人推荐了{{shudanDetail.count_book}}本书</div>
            <div class="sdName">{{shudanDetail.shudan_title}}</div>
          </div>
          <div class="topRight">
            <div class="look" @click="$router.back(-1)">去看看</div>
          </div>
        </div>
      </div>

      <div class="book">
        <div class="bookinfo">
          <div class="box">
            <div class="bookcover">
              <img :src="detailData.book.cover_replace" alt />
              <!-- <van-tag mark color="rgba(0,0,0,.7)" class="sku-tag">暂时无货</van-tag> -->
            </div>
            <div class="bookData">
              <div class="bookName">{{detailData.book.name}}</div>
              <div class="author">{{detailData.book.author}}</div>
              <div class="grade">豆瓣评分：{{detailData.book.rating_num}}</div>
            </div>
          </div>
          <div class="recUser">
            <div class="resUserHead">
              <img :src="detailData.comment_user.avatar" alt />
              <span>{{detailData.comment_user.nickname}}</span>
            </div>
          </div>
          <div class="sdComment">
            <div>{{detailData.shudan_comment.body}}</div>
          </div>
          <div class="sdBookMenu box_cqh">
            <div class="MenuLeft" @click="zan">
              <img :src="detailData.dianzan_status?'/images/zangreen.png':'/images/zanaaa.png'" alt />
              <span>{{detailData.dianzan_users.length}}</span>
            </div>
            <div class="MenuRight">{{detailData.shudan_comment.created_at}}</div>
          </div>
        </div>
        <div
          class="zanBox box_cqh"
          v-if="detailData.dianzan_users.length>0"
          @click="gotoZanList(detailData.dianzan_users)"
        >
          <div class="zanLeft box_cqh">
            <div class="userHead box">
              <img
                :src="item.user.avatar"
                v-for="(item,index) in detailData.dianzan_users"
                :key="index"
              />
            </div>
            <div class="usernum">{{detailData.dianzan_users.length}}人觉得有趣</div>
          </div>
          <div class="zanRight">
            <van-icon name="arrow" color="#aaaaaa" />
          </div>
        </div>
        <!-- <div class="comment">
        <div class="noComment">还没有人流言</div>
        <div class="commentBox">
          <div class="commentNums">8条留言</div>
          <div class="commentList box">
            <div class="commentHead">
              <img
                src="http://thirdwx.qlogo.cn/mmopen/Q3auHgzwzM7YM4G5Qm41UhhkGnD2Yw0rXDz09fW9F6eHYTWcDH2gkukkjo6o1F5YAqSXndWGKapBRH4JkwE8n4APO91BJicT2ehXQufbW3K8/132"
                alt
              />
            </div>
            <div class="commentInfo">
              <div class="username">渣渣：</div>
              <div class="userComment">评论内容啊评论内容啊评论内容啊评论内容啊评论内容啊评论内容啊评论内容啊评论内容啊</div>
              <div class="commentTime">2018-5-24</div>
            </div>
          </div>
          <div class="commentList box">
            <div class="commentHead">
              <img
                src="http://thirdwx.qlogo.cn/mmopen/Q3auHgzwzM7YM4G5Qm41UhhkGnD2Yw0rXDz09fW9F6eHYTWcDH2gkukkjo6o1F5YAqSXndWGKapBRH4JkwE8n4APO91BJicT2ehXQufbW3K8/132"
                alt
              />
            </div>
            <div class="commentInfo">
              <div class="username">渣渣：</div>
              <div class="userComment">评论内容啊评论内容啊评论内容啊评论内容啊评论内容啊评论内容啊评论内容啊评论内容啊</div>
              <div class="commentTime">2018-5-24</div>
            </div>
          </div>
        </div>
      </div> -->
      <!-- <div class="commentIpt">
        <van-cell-group clearable='true'>
          <van-field v-model="value" placeholder="请输入评论内容" >
            <van-button slot="button" size="small" type="primary">发送</van-button>
          </van-field>
        </van-cell-group>
        </div>
      </div> -->
    </div>
  </div>
</template>

<script>
import { Toast, Icon, Field } from "vant";
import Loading from "./Loading";
export default {
  data() {
    return {
      loading: true,
      value: "", //输入的评论内容
      id: "", //书单中书的id
      shudan: "",
      detailData: {
        book: {
          cover_image: "",
          author: "",
          name: "",
          rating_num: ""
        },
        dianzan_status: false,
        dianzan_users: [],
        comment_user: {
          nickname: "",
          avatar: ""
        }, //推荐人信息
        shudan_comment: {
          body: "",
          shudan_id: "",
          comment_id: ""
        }
      }, //页面数据
      shudanDetail: {}
    };
  },
  components: {
    Loading
  },
  created() {
    let that = this;
    this.wxApi.wxConfig('','');
    that.id = that.$route.params.sdbookid;
    axios.get("/wx-api/shudan_comment/"+that.id).then(res => {
      console.log(res.data);
      that.detailData = res.data;
      that.getShudan(res.data.shudan_comment.shudan_id);
      that.getShudanDetail(res.data.shudan_comment.shudan_id);
    });
  },
  mounted() {},
  computed:{
    contentStyle: function() {
      var color = this.shudan.color ? this.shudan.color : "#ffffff";
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
    }
  },
  methods: {
    getDetail() {
      let that = this;
      axios.get("/wx-api/shudan_comment/"+that.id).then(res => {
        console.log(res.data);
        that.detailData = res.data;
      });
    },
    getShudan(id) {
      let that = this;
      axios.get("/wx-api/shudan_users").then(res => {
        console.log(res.data);
        that.loading = false;
        if (res.data.status) {
          that.shudanDetail = res.data.data[id];
        }
      });
    },
    getShudanDetail(id) {
      let that = this;
      axios.get("/wx-api/get_shudan/" + id).then(res => {
        console.log(res.data);
        that.shudan = res.data;
      });
    },
    zan() {
      let that = this;
      axios
        .get(
          "/wx-api/shudan_dianzan/" + that.detailData.shudan_comment.comment_id
        )
        .then(res => {
          console.log(res.data);
          that.getDetail();
          Toast.clear();
        });
    },
    gotoZanList(list) {
      var list = JSON.stringify(list);
      this.$router.push({ path: "/wechat/zanUsers", query: { list: list } });
    }
  }
};
</script>

<style scoped lang="scss">
.sdBookComment {
  width: 100%;
  min-height: 100vh;
  padding-bottom: 0px;
  .top {
    width: 100%;
    height: 120px;
    background: #042932;
    color: #ffffff;
    padding: 0 12px;
    box-sizing: border-box;
    .sdNums {
      font-size: 12px;
      margin-bottom: 5px;
    }
    .sdName {
      font-size: 16px;
      font-weight: bold;
    }
    .look {
      border: 1px solid #ffffff;
      padding: 5px;
      border-radius: 5px;
      font-size: 12px;
    }
  }
  .book {
    width: 100%;
    padding: 0 12px;
    box-sizing: border-box;
    .bookinfo {
      width: 100%;
      padding: 10px;
      box-sizing: border-box;
      .bookcover {
        width: 80px;
        position: relative;
        margin-right: 10px;
        img {
          width: 100%;
        }
        .sku-tag {
          position: absolute;
          top: 0;
          left: 0;
        }
      }
      .bookData {
        .bookName {
          font-size: 16px;
          font-weight: bold;
          margin-bottom: 5px;
        }
        .author {
          color: #666666;
          font-size: 13px;
          margin-bottom: 5px;
        }
        .grade {
          color: #d8bf94;
          font-size: 13px;
        }
      }
      .recUser {
        .resUserHead {
          margin: 10px 0;
          img {
            width: 25px;
            height: 25px;
            border-radius: 50%;
            vertical-align: middle;
            margin-right: 10px;
          }
          span {
            font-size: 14px;
            color: #333333;
          }
        }
      }
      .sdComment {
        color: #313131;
        font-size: 15px;
        margin-bottom: 10px;
      }
      .sdBookMenu {
        color: #aaaaaa;
        font-size: 13px;
        img {
          width: 15px;
          height: 15px;
          vertical-align: sub;
        }
      }
    }
    .zanBox {
      width: 100%;
      padding: 10px;
      box-sizing: border-box;
      border: 1px solid #e5e5e5;
      border-left: none;
      border-right: none;
      .zanLeft {
        .userHead {
          margin-right: 10px;
          position: relative;
          min-height: 27px;
          img {
            width: 25px;
            height: 25px;
            border-radius: 50%;
            border: 1px solid #ffffff;
          }
        }
        .usernum {
          font-size: 13px;
          color: #aaaaaa;
        }
      }
    }
    .comment {
      width: 100%;
      padding: 20px 10px;
      box-sizing: border-box;
      border-bottom: 1px solid #e5e5e5;
      .noComment {
        text-align: center;
        padding: 20px 0;
        color: #aaaaaa;
        font-size: 13px;
      }
      .commentBox {
        .commentNums {
          color: #aaaaaa;
          font-size: 13px;
        }
        .commentList {
          width: 100%;
          margin-top: 20px;
          .commentHead {
            flex: 1;
            padding-right: 10px;
            box-sizing: border-box;
            img {
              width: 25px;
              height: 25px;
              border-radius: 50%;
              border: 1px solid #ffffff;
            }
          }
          .commentInfo {
            flex: 10;
            border-bottom: 1px solid #e5e5e5;
            padding-bottom: 20px;
            .username {
              color: #333333;
              font-size: 13px;
              margin-bottom: 5px;
            }
            .userComment {
              color: #333333;
              font-size: 13px;
              line-height: 20px;
            }
            .commentTime {
              font-size: 12px;
              color: #aaaaaa;
              margin-top: 5px;
            }
          }
        }
      }
    }
    .commentIpt {
      width: 100%;
      position: fixed;
      bottom: 0;
      left: 0;
    }
  }
}
</style>