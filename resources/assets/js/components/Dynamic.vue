<template>
  <div class="dynamicBox">
    <div class="listTop box_cqh">
      <div class="topLeft box">
        <div class="user">
          <img :src="user.avatar?user.avatar:'/images/avatar.jpeg'" alt />
          <span class="username">{{user.nickname?user.nickname:''}}</span>
        </div>
        <div class="userscore" v-if="item.type==1 && !item.no">推荐</div>
        <div class="userscore box" v-if="item.type==2 &&!item.no">
          <span>点评</span>
          <van-rate
            v-model="item.star"
            :count="item.star"
            disabled="true"
            size="12"
            disabled-color="#999999"
          />
        </div>
        <div class="userscore" v-if="item.type==2 &&item.no">买了{{item.books.length}}本</div>
        <div class="userscore" v-if="item.type==1 &&item.no">卖了{{item.books.length}}本</div>
      </div>
    </div>
    <div class="bookInfo box" v-if="!item.no" @click="gotoBook(item.book.isbn)">
      <div class="infoCover">
        <img :src="item.book.cover_replace" alt />
      </div>
      <div class="info">
        <div class="bookName">{{item.book.name}}</div>
        <div class="bookAuthor">{{item.book.author}}</div>
        <div class="rating_num">豆瓣评分{{item.book.rating_num}}</div>
      </div>
    </div>
    <div class="bookBox" v-if="item.no">
      <div class="bookCover" v-for="(book,index) in item.books" :key="index" v-if="index<10||!more">
        <img :src="book.cover_replace" @click.stop="gotoBook(book.isbn)"/>
        <div class="more" v-if="more &&index==9 &&item.books.length>10" @click.stop="showMore">剩余{{item.books.length-10}}本</div>
      </div>
    </div>
    <div class="listBottom" v-if="!item.no">
      <div class="content">{{item.comment.body}}</div>
    </div>
    <div class="dynamicBottom box_cqh">
      <div class="date">{{item.created_at}}</div>
      <div class="dynamicRight">
        <div class="userZan" @click="zan(item)">
          <img :src="item.shudan_zan_status.length>0?'/images/image/zan1.png':'/images/image/zan.png'" alt />
          <span>{{item.shudan_zan_users.length}}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: ["item", "user","index",'from'],
  data(){
    return{
      more:true
    }
  },
  methods:{
    // 点赞操作
    zan(item) {
      let that = this,
      type ='',
      comment_id='',
      data ={
        item:item,
        index:that.index
      }
      if(item.no){
        type =2;
        comment_id=item.id;
      }else{
        type=1;
        comment_id =item.comment_id;
      }
      axios.get("/wx-api/shudan_dianzan/"+comment_id+'&type='+type).then(res => {
        console.log(res.data);
        if (res.data.status) {
          this.$emit('changeItems',data)
        }
      });
    },
    showMore(){
      this.more=false
    },
    gotoBook(isbn){
      console.log(this.from)
      this.$router.push(`/wechat/book/${isbn}?from=${this.from}`);
    }
  }
};
</script>

<style scoped lang='scss'>
.dynamicBox {
  width: 100%;
  padding: 25px 15px 20px 15px;
  box-sizing: border-box;
  border-bottom: 1px solid #ebedf0;
  background: #ffffff;
  .listTop {
    width: 100%;
    .topLeft {
      .user {
        display: flex;
        align-items: center;
        margin-right: 8px;
        img {
          width: 26px;
          height: 26px;
          border-radius: 50%;
          margin-right: 2px;
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
        line-height: 30px;
      }
    }
  }
  .bookInfo {
    width: 100%;
    margin-top: 11px;
    .infoCover {
      width: 60px;
      margin-right: 10px;
      img {
        width: 60px;
        height: 77px;
        background: rgba(216, 216, 216, 1);
        border-radius: 5px;
      }
    }
    .info {
      .bookName {
        font-size: 15px;
        font-family: PingFang-SC;
        font-weight: bold;
        color: rgba(51, 51, 51, 1);
        line-height: 21px;
      }
      .bookAuthor {
        font-size: 12px;
        font-family: PingFang-SC;
        color: rgba(102, 102, 102, 1);
        line-height: 17px;
        margin: 5px 0;
      }
      .rating_num {
        font-size: 12px;
        font-family: PingFang-SC;
        color: rgba(102, 102, 102, 1);
        line-height: 17px;
      }
    }
  }
  .bookBox {
    width: 100%;
    display: flex;
    flex-wrap: wrap;
    margin: 12px auto 22px auto;
    font-size: 0;
    .bookCover {
      min-width: 20%;
      height: auto;
      max-width: 20%;
      position: relative;
      img {
        width: 100%;
        height: 101px;
        border: 1px solid #fff;
        border-bottom: none;
        object-fit: cover;
      }
      .more{
        position: absolute;
        width: 100%;
        height: 100%;
        top: 0;
        left: 0;
        background:rgba(0,0,0,0.5);
        font-size:13px;
        font-family:PingFang-SC;
        color:rgba(255,255,255,1);
        display: flex;
        align-items: center;
        justify-content: center;
      }
    }
  }
  .listBottom {
    width: 100%;
    font-size: 15px;
    font-family: PingFang-SC;
    color: rgba(51, 51, 51, 1);
    line-height: 21px;
    margin: 8px auto 12px auto;
    .content{
      font-size:15px;
      font-family:PingFang-SC;
      color:rgba(102,102,102,1);
      line-height:21px;
    }
  }
  .dynamicBottom {
    width: 100%;
    .date {
      font-size: 12px;
      font-family: PingFang-SC;
      color: rgba(153, 153, 153, 1);
      line-height: 17px;
    }
    .dynamicRight {
      .userZan {
        font-size: 13px;
        font-family: PingFang-SC;
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
}
</style>