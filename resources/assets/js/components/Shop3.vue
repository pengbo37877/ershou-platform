<template>
    <div class="shop">
        <router-link tag="div" to="/wechat/search">
            <van-search
                    placeholder="请输入搜索关键词"
            />
        </router-link>
        <shudan-tabs :screen-width="screenWidth"></shudan-tabs>
        <coupon-bar style="margin-bottom: 10px" v-if="parseInt(userId)>0"></coupon-bar>
        <div class="user-not-subscribe" v-if="userId===0 || user.subscribe===0">
            <img src="/images/logo_main.png" style="height: 40px;margin-left: 10px" alt="">
            <div class="subscribe-text">
                <span>关注「回流鱼」公众号</span><br>
                <span>去买书和卖书</span>
            </div>
            <div class="subscribe-btn" @click="mask">
                去关注
            </div>
        </div>
        <van-tabs v-model="index" @disabled="onClickDisabled" @change="change" @scroll="scroll" sticky swipeable>
            <van-tab disabled>
                <div slot="title" style="display: flex;flex-direction: row;justify-content: center;align-items: center;color: #7d7e80">
                    全部分类<van-icon name="plus" />
                </div>
            </van-tab>
            <van-tab v-for="(tag, index) in tags" :title="tag" :key="index">
                <van-list
                        v-model="loading"
                        :finished="finished"
                        finished-text="没有更多了"
                        @load="onLoad"
                        v-if="tag!=='猜你喜欢'"
                >
                    <shop-book2 :book="book" v-for="book in books" :key="book.id" v-if="!changing"></shop-book2>
                    <div slot="loading">
                        <loading :loading="loading"></loading>
                    </div>
                    <div :style="{width: screenWidth + 'px', height: screenHeight + 'px', padding: '0 0'}" v-if="changing">
                        <loading :loading="loading"></loading>
                    </div>
                </van-list>
                <van-list
                        v-model="loading"
                        :finished="finished"
                        finished-text="没有更多了"
                        @load="onLoad"
                        v-if="tag==='猜你喜欢'"
                >
                    <shop-book-recommend :book="book" v-for="book in books" :key="book.id" v-if="!changing"></shop-book-recommend>
                    <div slot="loading">
                        <loading :loading="loading"></loading>
                    </div>
                    <div :style="{width: screenWidth + 'px', height: screenHeight + 'px', padding: '0 0'}" v-if="changing">
                        <loading :loading="loading"></loading>
                    </div>
                </van-list>
            </van-tab>
        </van-tabs>

        <div style="flex-grow: 1;min-height: 50px;width: 100%;"></div>

        <bottom-bar2 index="0"></bottom-bar2>

        <van-popup v-model="showMask" :close-on-click-overlay="true">
            <div style="text-align: center">
                <img src="/images/qrcode.jpg" :width="screenWidth-100+'px'" alt="">
            </div>
        </van-popup>

        <van-popup v-model="showCoupon" :close-on-click-overlay="false">
        <div class="read-day" style="width: 240px;height:384px;">
        <img src="/images/read-day.jpg" width="240px" alt="">
        <div class="get-coupon">
        <div class="coupon-desc" @click="gotoReadDay">活动详情</div>
        <div class="has-coupon-btn" v-if="hasCoupon">已领取</div>
        <div class="get-coupon-btn" v-else @click="readDayCoupon">点击领取</div>
        </div>
        </div>
        </van-popup>

        <div class="close-coupon" v-if="showCoupon" @click="showCoupon=false">
        <van-icon name="close" size="48px"/>
        </div>

        <div :style="readDayStyle" v-if="!showCoupon" @click="showCoupon=true">
        <img src="/images/read-day-s.png" width="68px" height="68px" alt="">
        </div>
    </div>
</template>
<!--<style>-->
<!--.van-tab&#45;&#45;disabled {-->
<!--color: #7d7e80;-->
<!--}-->
<!--</style>-->
<style scoped>
    body {
        background-color: white;
        margin: 0;
    }

    .read-day {
        position: relative;
    }
    .get-coupon {
        position: absolute;
        bottom: 10px;
        left: 0;
        width: 100%;
        height: 100px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }
    .get-coupon-btn {
        font-size: 16px;
        font-weight: bold;
        color: white;
        padding: 5px 15px;
        border: 3px dashed white;
    }
    .close-coupon {
        z-index: 3000;
        position: fixed;
        bottom: 50px;
        left: 0;
        width: 100%;
        height: 50px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        color: white;
    }

    .has-coupon-btn {
        font-size: 16px;
        font-weight: bold;
        color: #888888;
        padding: 5px 15px;
        border: 3px solid #888888;
    }

    .coupon-desc {
        font-size: 14px;
        color: white;
        margin-bottom: 15px;
        text-decoration: underline;
    }

    .el-loading-spinner i {
        color: #3D404A;
    }

    .el-loading-spinner .el-loading-text {
        color: #3D404A;
    }

    .shop {
        position: relative;
        display: flex;
        flex-direction: column;
    }

    .user-not-subscribe {
        position: absolute;
        left: 0;
        top: -6px;
        width: 100%;
        height: 60px;
        display: flex;
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
        background: white;
        border-bottom: 0.5px solid #eee;
    }

    .subscribe-text {
        font-size: 14px;
        font-weight: 300;
        flex-grow: 10;
        margin-left: 10px;
    }

    .subscribe-btn {
        font-size: 14px;
        color: white;
        font-weight: 300;
        background-color: #00a157;
        border-radius: 3px;
        padding: 5px 10px;
        margin-right: 15px;
    }
    .search-bar {
        display: flex;
        background-color: #EEEEEE;
        padding: 10px 20px;
        margin: 8px 10px;
        height: 20px;
        line-height: 20px;
        border-radius: 6px;
        color: #555555;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        font-weight: 300;
        font-size: 14px;
    }

    .search-icon {
        text-align: center;
        margin-right: -8px;
    }
</style>
<script>
    import wx from 'weixin-js-sdk';
    import {mapGetters, mapState, mapActions} from 'vuex'
    import ShopBook2 from './ShopBook2'
    import ShopBookRecommend from './ShopBookRecommend'
    import BottomBar2 from './BottomBar2'
    import Loading from './Loading'
    import CouponBar from './CouponBar'
    import ShudanTabs from './ShudanTabs'

    export default {
        data() {
            return {
                selected: 0,
                device: '',
                screenHeight: 0,
                screenWidth: 0,
                bookWidth: 0,
                y: 0,
                pageYOffset: 0,
                bScrollTop: 0,
                dScrollTop: 0,
                fixed: false,
                showMask: false,
                loading: false,
                finished: false,
                changing: false,
                showCoupon: false,
                hasCoupon: false
            }
        },
        computed: {
            readDayStyle: function() {
                return {
                    position: 'fixed',
                    top: this.screenHeight-200+'px',
                    right: '10px',
                    width: '68px',
                    height: '68px'
                }
            },
            popupStyle: function() {
                return {
                    width: this.screenHeight - 100 + 'px'
                }
            },
            blankStyle: function() {
                var gap = 0;
                if(this.user.subscribe===0) {
                    gap += 60;
                }
                gap += 106;
                return {
                    width: this.screenWidth + 'px',
                    height: this.screenHeight - gap + 'px'
                }
            },
            qrcodeImgStyle: function() {
                return {
                    width: this.screenWidth/2.5 + 'px'
                }
            },
            content: function() {
                return {
                    width: (this.bookWidth + 26) * this.books.length + 20 + 'px'
                }
            },
            lyWidth: function() {
                return {
                    width: this.screenWidth - 105 + 'px'
                }
            },
            ...mapState({
                index: state => state.user.tagIndex,
                position: state => state.user.position,
                books: state => state.books.books,
                currentPage: state => state.books.currentPage,
                nextPageUrl: state => state.books.nextPageUrl,
                userId: state => state.user.userId,
                user: state => state.user.user,
                tags: state => state.user.tags,
                sds: state => state.shudan.all
            }),
        },
        created: function() {
            this.screenWidth = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
            this.screenHeight = window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;
            this.device = window.localStorage.getItem('device');
            this.bookWidth = (this.screenWidth - 60) / 2.5;
            this.$store.dispatch('user/getUser').then(res => {
                this.$store.dispatch('user/getUserTags');
            });
            this.wxConfig();
            this.showCoupon = window.localStorage.getItem('coupon') !== 1;
            this.hasCoupon = window.localStorage.getItem('coupon') !== 1;
        },
        mounted: function () {
            this.screenWidth = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
            this.screenHeight = window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;
        },
        activated: function () {
            window.addEventListener('scroll', this.handleScroll);
            if (this.bScrollTop>0) {
                document.body.scrollTop = this.bScrollTop;
                console.log('document.body.scrollTop='+document.body.scrollTop);
            }
            if (this.dScrollTop>0) {
                document.documentElement.scrollTop = this.dScrollTop;
                console.log('document.documentElement.scrollTop='+document.documentElement.scrollTop);
            }
            if (this.pageYOffset>0) {
                window.pageYOffset = this.pageYOffset;
                console.log('window.pageYOffset='+window.pageYOffset);
            }
        },
        deactivated: function () {
            window.removeEventListener('scroll', this.handleScroll);
        },
        methods: {
            onClickDisabled: function() {
                this.$router.push('/wechat/tags')
            },
            coupon: function() {
                this.$router.push('/wechat/share_desc');
            },
            onLoad: function() {
                console.log('onLoad');
                // 异步更新数据
                this.loading = true;
                var tag = this.tags[this.position];
                var nextPage = 1;
                if (this.nextPageUrl==='' && this.currentPage===1){
                    nextPage = 1;
                } else {
                    nextPage = Number(this.currentPage) + 1;
                }
                console.log('onLoad position='+this.position);
                console.log('onLoad userId='+this.userId);
                console.log('onLoad tags='+JSON.stringify(this.tags));
                console.log('onLoad tag='+tag);
                console.log('onLoad page='+nextPage);
                axios.get('/wx-api/get_books_by_tag/' + tag + '?user='+this.userId+"&page=" + nextPage).then(res => {
                    // this.books = this.books.concat(res.data.data);
                    // this.currentPage = res.data.current_page;
                    // this.nextPageUrl = res.data.next_page_url;
                    this.$store.commit('books/addBooks', res.data);
                    this.loading = false;
                    // 数据全部加载完成
                    if (!this.nextPageUrl) {
                        this.finished = true;
                    }
                });
            },
            wxConfig: function() {
                var url = window.localStorage.getItem('url');
                axios.post('/wx-api/config', {
                    'url': 'shop'
                }).then(response => {
                    console.log(response.data);
                    wx.config(response.data);
                    wx.ready(() => {
                        console.log("ready");
                        wx.onMenuShareAppMessage({
                            title: '回流鱼 - 二手循环书店', // 分享标题
                            desc: '阅读不孤读', // 分享描述
                            link: url+'/wechat/shop', // 分享链接，该链接域名或路径必须与当前页面对应的公众号JS安全域名一致
                            imgUrl: url+'/images/logo_main.png', // 分享图标
                            type: '', // 分享类型,music、video或link，不填默认为link
                            dataUrl: '', // 如果type是music或video，则要提供数据链接，默认为空
                            success: function () {
                                // 用户点击了分享后执行的回调函数
                                console.log('分享成功');
                            }
                        });
                        wx.onMenuShareTimeline({
                            title: '回流鱼 - 二手循环书店', // 分享标题
                            link: url+'/wechat/shop', // 分享链接，该链接域名或路径必须与当前页面对应的公众号JS安全域名一致
                            imgUrl: url+'/images/logo_main.png', // 分享图标
                            success: function () {
                                // 用户点击了分享后执行的回调函数
                                console.log('分享成功')
                            }
                        });
                    });
                });
            },
            change: function(index=1, title) {
                this.changing = true;
                this.loading = true;
                this.finished = false;
                var newIndex = index-1;
                this.$store.commit('user/setPosition', newIndex);
                this.$store.commit('user/setTagIndex', index);
                var tag = this.tags[newIndex];
                console.log('shop2 change index='+index);
                console.log('shop2 change title='+title);
                console.log('shop2 change tags='+JSON.stringify(this.tags));
                console.log('shop2 change newIndex='+newIndex);
                console.log('shop2 change tag='+tag);
                axios.get('/wx-api/get_books_by_tag/' + tag + '?user='+this.userId).then(res => {
                    this.$store.commit('books/setBooks', res.data);
                    this.loading = false;
                    this.changing = false;
                    // 数据全部加载完成
                    if (!this.nextPageUrl) {
                        this.finished = true;
                    }
                });
            },
            scroll: function(info) {
                // console.log('scrollTop='+info.scrollTop);
                this.fixed = info.isFixed;
                // console.log('fixed='+this.fixed);
            },
            handleScroll: function() {
                //scrollTop是浏览器滚动条的top位置
                this.pageYOffset = window.pageYOffset;
                this.dScrollTop = document.documentElement.scrollTop;
                this.bScrollTop = document.body.scrollTop;
                console.log('pageYOffset='+this.pageYOffset);
                console.log('dScrollTop='+this.dScrollTop);
                console.log('bScrollTop='+this.bScrollTop);
                //下面这句主要是获取网页的总高度，主要是考虑兼容性所以把Ie支持的documentElement也写了，这个方法至少支持IE8
                var htmlHeight = document.documentElement.scrollHeight;
                //clientHeight是网页在浏览器中的可视高度，
                var clientHeight = document.documentElement.clientHeight;
            },
            mask: function() {
                this.showMask = true;
            },
            readDayCoupon: function() {
                axios.get('/wx-api/read_day_coupon?user='+this.userId).then(res => {
                    this.hasCoupon = true;
                    localStorage.setItem('coupon', 1);
                });
            },
            gotoReadDay: function() {
                this.$router.push('/wechat/read_day');
            }
        },
        components: {
            ShopBook2,
            ShopBookRecommend,
            BottomBar2,
            Loading,
            ShudanTabs,
            CouponBar
        }
    }
</script>
