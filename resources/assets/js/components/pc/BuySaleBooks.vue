<template>
    <div>
        <div class="top-nav">
            <div class="top-nav-item" :class="{active:nav==0}" @click="changeNav(0)">卖过的书</div>
            <div class="top-nav-item" :class="{active:nav==1}" @click="changeNav(1)">买过的书</div>
        </div>
        <div class="summary" v-if="nav==0">
            <div class="summary1">
                <div class="count">共卖书&nbsp;<span style="font-size:18px;font-weight:700;color:#EE8385">{{saleCount}}</span>&nbsp;本</div>
            </div>
            <div class="summary2">
                <div class="all-price">卖书获得￥{{salePrice}}</div>
            </div>
        </div>
        <div class="summary" v-if="nav==1">
            <div class="summary1">
                <div class="count">共买书&nbsp;<span style="font-size:18px;font-weight:700;color:#EE8385">{{buyCount}}</span>&nbsp;本</div>
            </div>
            <div class="summary2">
                <div class="all-price">总花费￥{{buyPrice}}</div>
                <div class="save-price">&nbsp;·&nbsp;比原价节省￥{{Number(buyOriginalPrice)-Number(buyPrice)}}</div>
            </div>
        </div>
        <div class="books" v-if="sales.length>0 && nav==0">
            <div class="book" v-for="(item, index) in sales">
                <div class="book-cover">
                    <img :src="item.book.cover_replace" width="49" height="70">
                </div>
                <div class="book-info">
                    <div class="book-name">{{item.book.name}}</div>
                    <div class="book-author">{{item.book.author}}</div>
                </div>
                <div class="book-user" v-if="item.book_sku && item.book_sku.curr_user.length>0">
                    <div class="user-avatar">
                        <img :src="item.book_sku.curr_user[0].avatar" width="32px" style="border-radius:16px;">
                    </div>
                    <div class="user-nickname">{{item.book_sku.curr_user[0].nickname}}</div>
                    <div class="user-belong">现主人</div>
                </div>
            </div>
        </div>
        <div class="books" v-if="buys.length>0 && nav==1">
            <div class="book" v-for="(item, index) in buys">
                <div class="book-cover">
                    <img :src="item.book.cover_replace" width="49" height="70">
                </div>
                <div class="book-info">
                    <div class="book-name">{{item.book.name}}</div>
                    <div class="book-author">{{item.book.author}}</div>
                </div>
                <div class="book-user" v-if="item.book_sku && item.book_sku.prev_user.length>0">
                    <div class="user-avatar">
                        <img :src="item.book_sku.prev_user[0].avatar" width="32px" style="border-radius:16px;">
                    </div>
                    <div class="user-nickname">{{item.book_sku.prev_user[0].nickname}}</div>
                    <div class="user-belong">前主人</div>
                </div>
            </div>
        </div>
        <!--<div class="footer fa-lg">-->
            <!--<a href="/wechat/shop" class="footer-a">-->
                <!--<div class="foot-item">-->
                    <!--<i class="fal fa-home"></i>-->
                    <!--<p class="foot-item-desc">首页</p>-->
                <!--</div>-->
            <!--</a>-->
            <!--<a href="/wechat/scan" class="footer-a">-->
                <!--<div class="foot-item">-->
                    <!--<i class="fal fa-books"></i>-->
                    <!--<p class="foot-item-desc">卖书</p>-->
                <!--</div>-->
            <!--</a>-->
            <!--<a href="/wechat/cart" class="footer-a">-->
                <!--<div class="foot-item">-->
                    <!--<i class="fal fa-shopping-cart"></i>-->
                    <!--<p class="foot-item-desc">购物车</p>-->
                    <!--<div class="cart-item-count" v-if="count>0">{{count}}</div>-->
                <!--</div>-->
            <!--</a>-->
            <!--<a href="/wechat/my" class="footer-a">-->
                <!--<div class="foot-item">-->
                    <!--<i class="fal fa-user"></i>-->
                    <!--<p class="foot-item-desc">我的</p>-->
                <!--</div>-->
            <!--</a>-->
        <!--</div>-->
    </div>
</template>
<style>
    body{
        background-color: white;
    }
    .top-nav {
        height: 48px;
        line-height: 48px;
        display: flex;
        flex-direction: row;
        justify-content: space-around;
        font-size: 16px;
        font-weight: 400;
        color: #555;
        border-bottom: 0.5px solid #eee;
        position: fixed;
        top:0;
        left:0;
        width:100%;
        background: white;
        z-index: 1000;
    }
    .active {
        border-bottom: 3px solid #35C1AA;
        color: #35C1AA;
    }
    .summary {
        display: flex;
        flex-direction: column;
        align-items:center;
        margin-top:48px;
    }
    .summary1 {
        display: flex;
        flex-direction: row;
        justify-content:center;
        color:#555;
        font-weight:300;
        font-size: 16px;
        padding-top: 15px;
    }
    .summary2 {
        display: flex;
        flex-direction: row;
        justify-content: center;
        border-bottom:0.5px solid #eee;
        font-weight:300;
        font-size:16px;
        padding-bottom: 20px;
    }
    .books {
        padding-bottom: 60px;
    }
    .book {
        padding: 20px 15px;
        display: flex;
        flex-direction: row;
        position: relative;
        border-bottom: 0.5px solid #efefef;
    }
    .book-info {
        margin-left: 10px;
        display: flex;
        flex-direction: column;
    }
    .book-name {
        font-size: 16px;
        font-weight: 400;
        color: #333;
    }
    .book-author {
        font-size: 14px;
        font-weight: 300;
        color: #888;
    }
    .book-user {
        position: absolute;
        top: 20px;
        right: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        width: 25%;
        border-left:0.5px solid #efefef;
    }
    .user-nickname {
        font-size: 13px;
        font-weight: 400;
        color: #555;
    }
    .user-belong {
        font-size: 12px;
        font-weight: 300;
        color: #888;
    }
    .footer {
        position: fixed;
        left: 0px;
        bottom: 0px;
        background: #fff;
        display: flex;
        flex-direction: row;
        justify-content: space-around;
        align-items: center;
        height: 44px;
        width: 100%;
        border-top: 0.5px solid #ccc;
    }
    .footer-a {
        color: #666;
    }
    .foot-item {
        position:relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        margin-bottom: -20px;
        color: #666;
    }
    .foot-item-desc{
        font-size: 11px;
        margin-top: 3px;
        font-weight: 200;
    }
    .on {
        color: #96B897;
    }
    .cart-item-count{
        position:absolute;
        top: -5px;
        right: -8px;
        color: white;
        background-color: #EE8285;
        font-size: 12px;
        height: 20px;
        line-height: 20px;
        min-width: 20px;
        text-align: center;
        border-radius: 13px;
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
    export default{
        data(){
            return{
                count: 0,
                nav:0,
                buys: [],
                currentBuyPage:1,
                nextBuyPageUrl:'',
                sales: [],
                currentSalePage:1,
                nextSalePageUrl:'',
                fetching: false
            }
        },
        props: ['buyOriginalPrice','buyPrice','saleOriginalPrice','salePrice','buyCount','saleCount'],
        mounted: function() {
            console.log(this.buyOriginalPrice);
            console.log(this.buyPrice);
            console.log(this.saleOriginalPrice);
            console.log(this.salePrice);
            window.addEventListener('scroll', this.handleScroll)
            this.saleOrder(1);
            this.getCartItemsCount();
            setInterval(this.getCartItemsCount, 1500);
        },
        methods: {
            getCartItemsCount: function() {
                axios.get('/wechat/get_cart_items_count').then(res => {
                    this.count = res.data;
                });
            },
            changeNav: function(index) {
                this.nav=index;
                if(index==0 && this.sales.length==0) {
                    this.saleOrder(1);
                }
                if(index==1 && this.buys.length==0) {
                    this.buyOrder(1)
                }
            },
            buyOrder: function(page) {
                axios.get('/wechat/get_my_buy_orders?page='+page).then(res => {
                    this.buys = this.buys.concat(res.data.data);
                    this.currentBuyPage = res.data.current_page;
                    this.nextBuyPageUrl = res.data.next_page_url;
                });
            },
            saleOrder: function(page) {
                axios.get('/wechat/get_my_sale_orders?page='+page).then(res => {
                    this.sales = this.sales.concat(res.data.data);
                    this.currentSalePage = res.data.current_page;
                    this.nextSalePageUrl = res.data.next_page_url;
                });
            },
            loadMore: function() {
                console.log('load more');
                if(this.nav==0 && !this.fetching && this.nextSalePageUrl) {
                    var nextPage = Number(this.currentSalePage)+1
                    this.saleOrder(nextPage);
                }
                if(this.nav==1 && !this.fetching && this.nextBuyPageUrl) {
                    var nextPage = Number(this.currentBuyPage)+1
                    this.buyOrder(nextPage);
                }
            },
            handleScroll: function() {
                //scrollTop是浏览器滚动条的top位置
                var scrollTop = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop
                //下面这句主要是获取网页的总高度，主要是考虑兼容性所以把Ie支持的documentElement也写了，这个方法至少支持IE8
                var htmlHeight = document.documentElement.scrollHeight;
                //clientHeight是网页在浏览器中的可视高度，
                var clientHeight= document.documentElement.clientHeight;
                this.y = scrollTop;
                //通过判断滚动条的top位置与可视网页之和与整个网页的高度是否相等来决定是否加载内容；
                if(scrollTop+clientHeight==htmlHeight){
                    this.loadMore();
                }
            },
        }
    }
</script>
