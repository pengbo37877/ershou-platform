<template>
  <div class="score">
    <div class="book box_h">
      <div class="bookCover">
        <img :src="bookInfo.cover" >
      </div>
      <div class="bookInfo">
        <div class="bookName">{{bookInfo.name}}</div>
        <div class="author">{{bookInfo.author}}</div>
      </div>
    </div>
    <div class="scoreBox">
      <div class="title">*根据该书的内容来打分哦*</div>
      <div class="score">
        <van-rate v-model="value" void-color="#41B0DC" color="#41B0DC" size="26" />
      </div>
      <div class="textarea">
        <van-cell-group>
          <van-field v-model="reason" type="textarea" placeholder="谈谈你的阅读感受……" rows="6" autosize />
        </van-cell-group>
      </div>
      <div class="hint">温馨提示：如购买的书品相不符，疑似盗版等问题，请联系回流鱼客服处理。</div>
      <div class="submitBox">
        <div class="enter" @click='submit'>提交</div>
      </div>
    </div>
  </div>
</template>

<script>
import { Toast } from 'vant';
export default {
  data() {
    return {
      value: 0,//分数
      reason:'',//评论内容
      bookId:'',
      bookInfo:''
    };
  },
  created(){
      console.log(this.$route)
      this.wxApi.wxConfig('','');
      this.bookId =this.$route.params.bookId;
      this.bookInfo =JSON.parse(this.$route.query.bookInfo)
  },
  methods:{
      submit(){
          if(this.value==0){
              Toast('您还没有评分')
              return
          }
          if(this.reason.trim().length==0){
              Toast('评论内容不能为空')
              return
          }
          Toast.loading('正在提交...')
          let that =this;
          axios.get('/wx-api/add_book_to_shudan/-1?book_id='+this.bookId+'&reason='+this.reason+'&star='+this.value+'&type=2').then((res)=>{
              console.log(res.data)
              Toast.clear()
              if(res.data.status){
                  Toast.success(res.data.message)
                  setTimeout(()=>{
                      that.$router.back()
                  },1000)
              }else{
                  Toast.fail(res.data.message)
              }
          })
      }
  }
};
</script>

<style scoped lang='scss'>
.score {
  width: 100%;
  .book {
    width: 100%;
    padding: 21px 15px;
    box-sizing: border-box;
    background: rgba(255, 255, 255, 1);
    box-shadow: 0px 2px 4px 0px rgba(0, 0, 0, 0.1);
    .bookCover {
      margin-right: 15px;
      img {
        width: 70px;
        height: 88px;
        border-radius: 5px;
      }
    }
    .bookInfo {
      .bookName {
        font-size: 15px;
        font-family: PingFang-SC;
        font-weight: bold;
        color: rgba(3, 3, 3, 1);
        line-height: 21px;
        margin-bottom: 5px;
      }
      .author {
        font-size: 14px;
        font-family: PingFang-SC;
        font-weight: 500;
        color: rgba(102, 102, 102, 1);
        line-height: 20px;
      }
    }
  }
  .scoreBox {
    width: 100%;
    padding: 0 14px;
    box-sizing: border-box;
    .title {
      margin-top: 35px;
      text-align: center;
      font-size: 14px;
      font-family: PingFang-SC;
      color: rgba(65, 176, 220, 1);
      line-height: 20px;
    }
    .score {
      margin: 9px auto 39px auto;
      text-align: center;
    }
    .textarea {
      width: 100%;
      border-radius: 15px;
      border: 1px solid rgba(217, 217, 217, 1);
      padding: 16px 18px;
      box-sizing: border-box;
      font-size: 15px;
      font-family: PingFang-SC;
      color: rgba(51, 51, 51, 1);
      line-height: 21px;
      margin-bottom: 13px;
      .van-hairline--top-bottom:after {
        border-width: 0;
      }
      .van-cell {
        padding: 0;
      }
    }
    .hint {
      font-size: 12px;
      font-family: PingFang-SC;
      color: rgba(153, 153, 153, 1);
      line-height: 17px;
      margin-bottom: 30px;
    }
    .submitBox {
      width: 100%;
      padding: 0 30px;
      box-sizing: border-box;
      .enter {
        width: 100%;
        height: 46px;
        background: rgba(65, 176, 220, 1);
        box-shadow: 0px 6px 10px 0px rgba(65, 176, 220, 0.3);
        border-radius: 15px;
        color: #ffffff;
        line-height: 46px;
        text-align: center;
      }
    }
  }
}
</style>