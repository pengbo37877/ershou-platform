<template>
  <div class="commentListBox" v-if="showAll || index<1">
    <div class="listTop box_cqh">
      <div class="topLeft box">
        <div class="user">
          <img :src="item.comment.user.avatar" alt />
          <span class="username">{{item.comment.user.nickname}}</span>
        </div>
        <div class="userscore box" v-if="item.type==2">
          <span>点评</span>
          <van-rate v-model="item.star" :count="item.star" disabled="true" size="12" disabled-color="#999999" />
        </div>
      </div>
      <div class="topRight">
        <div class="userZan" @click="zan(item)">
          <img :src="item.shudan_zan_status.length>0?'/images/image/zan1.png':'/images/image/zan.png'" alt />
          <span>{{item.shudan_zan_users.length}}</span>
        </div>
      </div>
    </div>
    <div class="listBottom">
      <div class="content">
        {{item.comment.body}}
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: ["item",'key','index','showAll'],
  data() {
    return {
    };
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

<style scoped lang='scss'>
.commentListBox {
  width: 100%;
  padding: 21px 15px;
  box-sizing: border-box;
  margin-bottom: 11px;
  background: #ffffff;
  .listTop {
    width: 100%;
    margin-bottom: 13px;
    .topLeft {
      .user {
        display: flex;
        align-items: center;
        margin-right: 8px;
        img {
          width: 26px;
          height: 26px;
          border-radius: 50%;
          margin-right: 5px;
        }
        .username {
          font-size: 13px;
          font-family: PingFang-SC;
          font-weight: bold;
          color: rgba(102, 102, 102, 1);
        }
      }
      .userscore {
        font-size: 13px;
        font-family: PingFang-SC;
        color: rgba(153, 153, 153, 1);
        line-height: 29px;
      }
    }
    .topRight {
      .userZan {
        font-size: 13px;
        font-family: PingFang-SC;
        font-weight: bold;
        color: rgba(102, 102, 102, 1);
        display: flex;
        align-items: center;
        img {
          width: 14px;
          height: 14px;
          margin-right: 5px;
          vertical-align: baseline;
        }
      }
    }
  }
  .listBottom {
    width: 100%;
    font-size: 15px;
    font-family: PingFang-SC;
    color: rgba(51, 51, 51, 1);
    line-height: 21px;
  }
}
.commentListBox:last-child {
  margin-bottom: 0;
}
</style>