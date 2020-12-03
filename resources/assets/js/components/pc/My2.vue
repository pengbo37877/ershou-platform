<template>
    <div>
        <div class="user-info" v-if="user">
            <div class="user-avatar">
                <img class="avatar" :src="user.avatar?user.avatar:'/images/avatar.jpeg'" alt="">
            </div>
            <div class="user-info-detail">
                <div class="user-name">{{user.nickname?user.nickname:'你的名字呢？'}}</div>
                <div class="user-sale-balance">卖书共获得 {{ saleBooksBalance }} 元</div>
            </div>
        </div>
        <div class="my-buttons">
            <router-link to="/pc/wallet" class="btn-a">
                余额
            </router-link>
            <router-link to="/pc/my_orders" class="btn-a">
                订单
            </router-link>
            <!--<router-link to="/wechat/buy_sale_books" class="btn-a">-->
                <!--买卖的书-->
            <!--</router-link>-->
            <router-link to="/pc/address_list?from=my" class="btn-a">
                地址管理
            </router-link>
        </div>
        <div class="my-buttons">
            <div class="btn-a" @click="shelf" :class="{ 'btn-active': nav===0 }">
                我的书房 {{shelfBooks.length}}
            </div>
            <div class="btn-a" style="opacity: 0" :class="{ 'btn-active': nav===1 }">
                卖书动态 0
            </div>
            <div class="btn-a" v-if="status===0 && nav===0" @click="scan">
                <van-icon name="add-o" />
            </div>
            <div class="btn-a" v-if="status===0 && nav===0" @click="manage">
                <van-icon name="close" />
            </div>
            <div class="btn-a" v-if="status===1 && nav===0" style="opacity: 0">
                <van-icon name="passed" />
            </div>
            <div class="btn-a" v-if="status===1 && nav===0" @click="done" style="color: green">
                <van-icon name="passed" />
            </div>

            <div class="btn-a" v-if="nav===1" style="opacity: 0">
                <van-icon name="close" />
            </div>
            <div class="btn-a" v-if="status===0 && nav===1">
                <van-icon name="close" />
            </div>
            <div class="btn-a" v-if="status===1 && nav===1">
                <van-icon name="passed" />
            </div>
        </div>
        <div class="my-books" v-if="nav===0">
            <loading :loading="loading"></loading>
            <div class="no-book" v-if="shelfBooks.length===0">
                <div class="text1">
                    把家里书架上的书都扫上来吧
                </div>
                <div class="text2">
                    你在回流鱼买过的书也会出现在这里
                </div>
            </div>
            <div class="bd-relations">
                <div class="bd-re-book" v-for="book in shelfBooks" :key="book.id">
                    <router-link :to="`/pc/book/${book.isbn}?from=my`" class="bd-re-book-cover" :style="reStyle">
                        <img :src="book.cover_replace" alt="" :style="reStyle">
                    </router-link>
                    <div class="bd-re-book-name" :style="reWidth">{{book.name}}</div>
                    <div class="shop-mask" v-if="status===1" :style="reStyle">
                        <div class="shop-sold-out">
                            <div class="book-delete-btn" v-if="status===1" @click="remove(book)">删除</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="my-books" v-if="nav===1">
            <div class="no-book">
                <div class="text1">
                    还没有卖书动态，快去扫码卖书吧
                </div>
                <router-link to="/pc/scan">
                    <div class="add-btn">
                        <van-icon name="scan" />
                        &nbsp;扫码卖书
                    </div>
                </router-link>
            </div>
        </div>
        <bottom-bar2 index="3"></bottom-bar2>
    </div>
</template>
<style scoped>
    body {
        padding: 0;
        margin: 0;
        background-color: white;
    }
    .user-info {
        display: flex;
        flex-direction: row;
        margin: 0 20px;
        padding: 20px 0;
        border-bottom: 0.5px solid #ddd;
    }
    .user-avatar {
        width: 80px;
        height: 80px;
        border-radius: 40px;
        border: 2px solid white;
        -webkit-box-shadow: 2px 2px 5px 0px rgba(204,204,204,0.5);
        -moz-box-shadow: 2px 2px 5px 0px rgba(204,204,204,0.5);
        box-shadow: 2px 2px 5px 0px rgba(204,204,204,0.5);
    }
    .avatar {
        width: 80px;
        height: 80px;
        border-radius: 40px;
    }
    .user-info-detail {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        margin-left: 20px;
        padding: 20px 0;
    }
    .user-name {
        font-size: 22px;
        font-weight: 600;
        color: #3D404A;
    }
    .user-sale-balance {
        font-size: 15px;
        color: #ff4848;
        opacity: 0.5;
    }
    .my-buttons {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        color: #3D404A;
        font-weight: 600;
        margin: 0 20px;
        border-bottom: 0.5px solid #ddd;
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
    .btn-a{
        font-size: 15px;
        color: #3D404A;
        font-weight: 600;
        text-align: center;
        padding: 15px;
    }
    .btn-active {
        border-bottom: 3px solid #ff4848;
        padding-bottom: 12px;
    }
    .my-books {
        padding: 10px 0;
    }
    .no-book {
        text-align: center;
        margin-top: 20px;
    }
    .text1 {
        font-size: 16px;
        color: #555;
    }
    .text2 {
        font-size: 13px;
        color: #777;
    }
    .add-btn {
        height: 40px;
        line-height: 40px;
        border-radius: 4px;
        margin: 10px 25px;
        font-size: 14px;
        background-color: white;
        color: #10A4C2;
        border: 0.5px solid #10A4C2;
        display: flex;
        flex-direction: row;
        align-items: center;
        justify-content: center;
    }
    .all-books {
        display: flex;
        flex-direction: row;
        flex-wrap: wrap;
    }
    .book {
        position: relative;
    }
    .book-cover {

    }
    .book-name {
        white-space: nowrap;
        text-overflow:ellipsis;
        overflow:hidden;
        width:100%;
        color:#3D404A;
        text-align:center;
        font-size: 15px;
    }
    .book-m {
        position: absolute;
        top: 0;
        left: 0;
        height: 100%;
        width: 100%;
        background-color:#6c757d;
        filter:Alpha(Opacity=60);
        opacity:0.6;
        text-align: center;
    }
    .book-delete-btn {
        position: absolute;
        bottom: 24px;
        left: 0px;
        width: 70%;
        border-radius: 3px;
        margin: 0 15px;
        background-color: white;
        color: black;
        text-align: center;
        height: 40px;
        line-height: 40px;
        font-size: 14px;
        font-weight: 700;
    }
    .bd-relations {
        display: flex;
        flex-direction: row;
        flex-wrap: wrap;
        margin-bottom: 110px;
    }
    .bd-re-book {
        display: flex;
        flex-direction: column;
        align-items: center;
        margin-left: 20px;
        margin-bottom: 10px;
        position: relative;
    }
    .bd-re-book-cover {
        border-radius: 4px;
        border: 2px solid white;
        -webkit-box-shadow: 2px 2px 5px 0px rgba(204,204,204,0.5);
        -moz-box-shadow: 2px 2px 5px 0px rgba(204,204,204,0.5);
        box-shadow: 2px 2px 5px 0px rgba(204,204,204,0.5);
    }
    .bd-re-book-name {
        font-size: 14px;
        color: #3D404A;
        text-overflow:ellipsis;
        white-space:nowrap;
        overflow:hidden;
        margin-top: 5px;
        text-align: center;
    }
    .shop-mask {
        position: absolute;
        top: 2px;
        left: 2px;
        background-color: black;
        opacity: 0.6;
        display: flex;
        flex-direction: row;
        justify-content: center;
        align-items: center;
    }
    .gotoCecommend{
        padding: 15px 25px;
        border-radius: 20px;
        background: #ffffff;
        text-align: center;
        position: fixed;
        bottom: 60px;
        left: 50%;
        transform: translateX(-50%);
    }
</style>
<script>
    import wx from 'weixin-js-sdk';
    import BottomBar2 from './BottomBar2'
    import Loading from './Loading'
    import { mapState, mapGetters, mapActions } from 'vuex'
    export default {
        data() {
            return{
                loading: false,
                nav: 0,
                screenWidth: 0,
                status: 0, // 0: 正常状态，1:管理状态
                tryTime: 0
            }
        },
        created: function() {
            var _this = this;
            this.$store.dispatch('user/getUser').then(res => {
                // 如果拿不到用户，就显示一个不可关闭的对话框
                var user = res.data;
                if (user===''||user.length===0) {
                    this.$router.replace('/pc/shop');
                } else if(user.subscribe===0) {
                    this.$router.replace('/pc/shop')
                }
                let options = {
            title:
              this.users.nickname +
              '在回流⻥的个⼈主⻚，来看Ta书房⾥的收藏吧',
            desc:
              '回流⻥⼆⼿循环书店，让好书流动起来',
            link: window.localStorage.getItem("url")+'/wechat/user/'+this.users.mp_open_id,
            imgUrl:
              window.localStorage.getItem("url") + "/images/image/logo.jpeg"
          };
          console.log(options);
          this.wxApi.wxConfig(options, "my");
            })
            this.$store.dispatch('my/getSaleBalance');
            this.loading = true;
            this.getShelfBooks().then(res => {
                this.loading = false;
                if (res.data.code && res.data.code === 500) {
                    _this.$dialog.alert({
                        message: res.data.msg,
                        center: true
                    });
                }
            let options = {
            title:
              this.users.nickname +
              '在回流⻥的个⼈主⻚，来看Ta书房⾥的收藏吧',
            desc:
              '回流⻥⼆⼿循环书店，让好书流动起来',
            link: window.localStorage.getItem("url")+'/wechat/user/'+this.users.mp_open_id,
            imgUrl:
              window.localStorage.getItem("url") + "/images/image/logo.jpeg"
          };
          console.log(options);
          wxApi.wxConfig(options, "my");
            });
        },
        computed: {
            reStyle: function() {
                return {
                    width: (this.screenWidth-98)/3 + 'px',
                    height: (this.screenWidth-98)*1.43/3 + 'px',
                }
            },
            reWidth: function() {
                return {
                    width: (this.screenWidth-98)/3 + 'px'
                }
            },
            maskStyle: function() {
                return {
                    width: this.screenWidth*140/375 + 'px',
                    height: this.screenWidth*1.43*140/375 + 'px',
                    left: this.screenWidth*235/750 + 'px'
                }
            },
            ...mapState({
                user: state => state.user.user,
                shelfBooks: state => state.my.shelfBooks,
                saleBooksBalance: state => state.my.saleBooksBalance,
                users:state=>state.user.users
            })
        },
        mounted: function() {
            this.$nextTick(() => {
                window.scrollTo(0, 1)
                window.scrollTo(0, 0)
            })
            this.screenWidth = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
            // 获取微信配置
            this.wxConfig();
        },
        methods: {
            wxConfig: function() {
                var _this = this;
                axios.post('/wx-api/config',{
                    'url': 'my'
                }).then(response => {
                    console.log(response.data);
                    wx.config(response.data);
                    wx.error(res => {
                        axios.post('/wx-api/create_client_error', {
                            'user_id': _this.user.id,
                            'error': JSON.stringify(response.data)+JSON.stringify(res),
                            'url': '/wechat/my'
                        });
                    });
                });
            },
            scan: function() {
                var _this = this;
                wx.scanQRCode({
                    needResult: 1, // 默认为0，扫描结果由微信处理，1则直接返回扫描结果，
                    scanType: ["qrCode","barCode"], // 可以指定扫二维码还是一维码，默认二者都有
                    success: function (res) {
                        var result = res.resultStr.split(",")[1]; // 当needResult 为 1 时，扫码返回的结果
                        _this.addBookToShelf(result).then(res => {
                            if (res.data.code && res.data.code===500) {
                                _this.$dialog.alert({
                                    message: res.data.msg,
                                    center: true
                                });
                            }
                        });
                    }
                });
            },
            shelf: function() {
                this.nav = 0;
            },
            activity: function() {
                this.nav = 1;
            },
            manage: function() {
                this.status = 1;
            },
            done: function() {
                this.status = 0;
            },
            remove: function(book) {
                this.removeBookFromShelf(book).then(res => {
                    if (res.data.code && res.data.code === 500) {
                        this.$dialog.alert({
                            message: res.data.msg,
                            center: true
                        });
                    }
                });
            },
            ...mapActions('my', [
                'getShelfBooks',
                'addBookToShelf',
                'removeBookFromShelf'
            ])
        },
        components: {
            BottomBar2,
            Loading
        }
    }
</script>
