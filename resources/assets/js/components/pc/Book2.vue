<template>
    <div>
        <loading :loading="bookLoading"></loading>
        <div v-if="book">
            <div class="bd-top">
                <div class="bd-cover" :style="bdStyle">
                    <img :src="coverImg" alt="" :style="bdStyle">
                </div>
                <div class="bd-name" :style="{color:book.color?book.color:'#fff2f2'}">{{book.name}}</div>
                <div class="bd-sub-name" :style="{color:book.color?book.color:'#fff2f2'}">{{book.subtitle}}</div>
                <div class="bd-activity">
                    <div class="bd-activity-title"><van-icon name="gift-o" /></div>
                    <div class="bd-activity-desc">一次卖书超过6本得一张包邮券</div>
                    <router-link tag="div" to="/pc/scan" class="bd-activity-go">去卖书 <van-icon name="arrow" /></router-link>
                </div>
            </div>
            <div class="bd-sku-group" v-if="SKUs.length>0">
                <div class="bd-sku-price">
                    ￥{{minPrice}}
                </div>
                <div class="bd-sku-qi" v-if="SKUs.length>1">起</div>
                <div class="bd-sku-discount">
                    <van-tag color="#7AD284" size="medium" plain>{{minDiscount.title+" "+minDiscount.discount}} 折</van-tag>
                </div>
            </div>
            <div class="bd-info-group">
                <div class="bd-info-item" v-if="SKUs.length===1">
                    <div class="bd-info-item-title">品相：</div>
                    <div class="bd-info-item-body">{{SKUs[0].title}}</div>
                </div>
                <div class="bd-info-item">
                    <div class="bd-info-item-title">原价：</div>
                    <div class="bd-info-item-body">{{SKUs[skuIndex]?(SKUs[skuIndex].book_version?SKUs[skuIndex].book_version.price:SKUs[skuIndex].original_price):book.price}}</div>
                </div>
                <div class="bd-info-item" v-if="book.author">
                    <div class="bd-info-item-title">作者：</div>
                    <div class="bd-info-item-body">{{book.author}}</div>
                </div>
                <div class="bd-info-item" v-if="versionPress">
                    <div class="bd-info-item-title">出版：</div>
                    <div class="bd-info-item-body">{{versionPress}} / {{book.publish_year}}</div>
                </div>
                <!--<div class="bd-info-item" v-if="book.publish_year">-->
                <!--<div class="bd-info-item-title">出版年：</div>-->
                <!--<div class="bd-info-item-body">{{book.publish_year}}</div>-->
                <!--</div>-->
                <div class="bd-info-item" v-if="book.binding">
                    <div class="bd-info-item-title">装帧：</div>
                    <div class="bd-info-item-body">{{book.binding}}</div>
                </div>
            </div>
            <van-cell-group>
                <van-cell :url="`https://m.douban.com/book/subject/${book.subjectid}`">
                    <template slot="icon">
                        <div style="display:flex;flex-direction:row;align-items:center;">
                            <van-icon name="star-o" color="#7AD284" size="16px"/>
                        </div>
                    </template>
                    <template slot="title">
                        <span class="custom-text">豆瓣评分：{{book.rating_num>0?book.rating_num:'暂无'}}</span>
                    </template>
                </van-cell>
                <van-cell>
                    <template slot="icon">
                        <div style="display:flex;flex-direction:row;align-items:center;">
                            <van-icon name="certificate" color="#7AD284" size="16px"/>
                        </div>
                    </template>
                    <template slot="title">
                        <span class="custom-text">消毒翻新、正版保证</span>
                    </template>
                </van-cell>
            </van-cell-group>
            <div class="bd-summary-title" v-if="book.summary">简介和目录</div>
            <div class="bd-summary" v-if="book.summary" :style="summaryStyle">
                <p v-if="displaySummary===0">{{bookSummary[0]}}</p>
                <p v-for="s in bookSummary" v-if="displaySummary===1">{{s}}</p>
            </div>
            <div class="bd-summary-title" v-if="book.author_intro && displaySummary===1">作者简介</div>
            <div class="bd-summary" v-if="book.author_intro && displaySummary===1" :style="summaryStyle">
                <p v-for="s in bookAuthorIntro">{{s}}</p>
            </div>
            <div class="bd-summary-title" v-if="book.catalog && displaySummary===1">目录</div>
            <div class="bd-summary" v-if="book.catalog && displaySummary===1" :style="summaryStyle">
                <p v-for="s in bookCatalog">{{s}}</p>
            </div>
            <div class="bd-summary-display" v-if="(book.summary || book.author_intro || book.catalog) && displaySummary===0" @click="displayTotal">
                <div>展开详情</div>
                <div style="margin-top: 3px;margin-left: 3px;">
                    <van-icon name="arrow-down" />
                </div>
            </div>
            <div class="bd-summary-display" v-if="(book.summary || book.author_intro || book.catalog) && displaySummary===1" @click="displaySub">
                <div>收起详情</div>
                <div style="margin-top: 3px;margin-left: 3px;">
                    <van-icon name="arrow-up" />
                </div>
            </div>

            <div class="bd-relation-title" v-if="otherBooks.length>0">相关书籍</div>
            <van-list
                    v-model="loading"
                    :finished="finished"
                    finished-text="没有更多了"
                    @load="recommends"
            >
                <div class="bd-relations">
                    <router-link :to="{ path: `/pc/book/${re.isbn}`}" class="bd-re-book" v-for="re in otherBooks" :key="re.id">
                        <div class="bd-re-book-cover" :style="reStyle">
                            <img :src="re.cover_replace" alt="" :style="reStyle">
                        </div>
                        <div class="bd-re-book-name" :style="reWidth">{{re.name}}</div>
                        <div class="bd-re-on-sale" v-if="re.for_sale_skus.length>0">有货</div>
                    </router-link>
                </div>
                <div slot="loading">
                    <loading :loading="loading"></loading>
                </div>
            </van-list>

            <div style="height:50px;width:100%"></div>

            <van-goods-action>
                <van-goods-action-mini-btn
                        icon="shop-o"
                        to="/pc/shop"
                        text="商店"
                />
                <van-goods-action-mini-btn
                        :info="items.length>0?items.length:''"
                        to="/pc/cart"
                        icon="bag-o"
                        text="购物袋"
                />
                <van-goods-action-big-btn
                        text="加入购物袋"
                        v-if="SKUs.length===1 && !inCart(SKUs[skuIndex]) && addingToCart"
                        @click="addToCart"
                        loading
                />
                <van-goods-action-big-btn
                        text="加入购物袋"
                        v-if="SKUs.length===1 && !inCart(SKUs[skuIndex]) && !addingToCart"
                        @click="addToCart"
                />
                <van-goods-action-big-btn
                        text="加入购物袋"
                        v-if="SKUs.length>1 && !inCart(SKUs[skuIndex])"
                        @click="showDialog"
                />
                <van-goods-action-big-btn
                        style="background-color: #ffb592;border: none;"
                        text="已在购物袋"
                        v-if="SKUs.length>0 && inCart(SKUs[skuIndex])"
                />
                <van-goods-action-big-btn
                        style="background-color: #0c5460;border: none;"
                        text="到货提醒"
                        v-if="SKUs.length===0 && !inReminder && !addingToReminder"
                        @click="addReminder"
                />
                <van-goods-action-big-btn
                        style="background-color: #0c5460;border: none;"
                        text="到货提醒"
                        v-if="SKUs.length===0 && !inReminder && addingToReminder"
                        @click="addReminder"
                        loading
                />
                <van-goods-action-big-btn
                        style="background-color: #3e8692;border: none;"
                        text="取消到货提醒"
                        v-if="SKUs.length===0 && inReminder && !addingToReminder"
                        @click="removeReminder"
                />
                <van-goods-action-big-btn
                        style="background-color: #3e8692;border: none;"
                        text="取消到货提醒"
                        v-if="SKUs.length===0 && inReminder && addingToReminder"
                        @click="removeReminder"
                        loading
                />
            </van-goods-action>

            <van-popup v-model="dialogVisible" position="bottom">
                <div class="pop-top">
                    <div class="pop-name">{{book.name}}</div>
                    <div class="pop-close" @click="dialogVisible=false">
                        <van-icon name="cross" size="20px"/>
                    </div>
                </div>
                <div class="pop-desc" style="">
                    <div>多个品相的书可以购买</div>
                    <div style="color:#555;" @click="goLevelDesc">
                        不同品相有何区别？<van-icon name="arrow" />
                    </div>
                </div>
                <div class="bd-dialog-items">
                    <div class="bd-dialog-item" :class="activeSkuStyle(index)" v-for="(sku,index) in SKUs" @click="chooseSku(index)">
                        <div class="bd-dialog-item-price">￥{{sku.price}}</div>
                        <div class="bd-dialog-item-desc">{{sku.title}}</div>
                    </div>
                </div>
                <div class="pop-bottom">
                    <van-button square type="warning" style="border-radius: 4px" size="large" loading v-if="addingToCart">加入购物袋</van-button>
                    <van-button square type="warning" style="border-radius: 4px" size="large" @click="addToCart" v-if="!addingToCart">加入购物袋</van-button>
                </div>
            </van-popup>
        </div>
    </div>
</template>
<script>
    import {mapState} from 'vuex';
    import BottomBar2 from './BottomBar2';
    import Loading from './Loading';
    export default{
        data(){
            return{
                screenWidth: 0,
                isbn: '',
                book:'',
                soldSku: '',
                SKUs: [],
                bookLoading: false,
                loading: false,
                finished: false,
                otherBooks: [],
                page: 1,
                lastPage: 1000,
                nextPageUrl: '',
                dialogVisible: false,
                subscribeDialogVisible: false,
                skuIndex: 0,
                bookVersion: null,
                selectSku: null,
                displaySummary: 0,
                displayAuthor: 0,
                displayCatalog: 0,
                from: 0,
                timer: '',
                viewSecond: 1,
                addingToCart: false,
                addingToReminder: false
            }
        },
        computed: {
            blankStyle: function() {
                return {
                    width: this.screenWidth + 'px',
                    height: this.screenHeight - 106 + 'px'
                }
            },
            minPrice: function() {
                var price = 100000;
                this.SKUs.forEach((sku) => {
                    if (Number(sku.price) < price) {
                        price = Number(sku.price).toFixed(2);
                    }
                });
                return price;
            },
            minDiscount: function() {
                var discount = 100000;
                var w_sku = {};
                this.SKUs.forEach((sku) => {
                    var bookVersion = sku.book_version;
                    if (bookVersion) {
                        var dis = sku.price * 10 / bookVersion.price;
                        if (dis < discount) {
                            w_sku = sku;
                            discount = Number(dis).toFixed(1);
                        }
                    }else{
                        var dis = sku.price * 10 / sku.original_price;
                        if (dis < discount) {
                            w_sku = sku;
                            discount = Number(dis).toFixed(1);
                        }
                    }
                });
                var title = "二手";
                if (w_sku.level===100) {
                    title = "全新";
                }
                return { discount, title};
            },
            coverImg: function() {
                if (this.bookVersion && this.bookVersion.cover) {
                    return this.bookVersion.cover;
                }
                if (this.book) {
                    return this.book.cover_replace;
                }
                console.log('book='+JSON.stringify(this.book));
                return '';
            },
            versionPress: function() {
                if (this.bookVersion && this.bookVersion.press) {
                    return this.bookVersion.press;
                }
                return this.book.press;
            },
            versionDiscount: function() {
                if (this.bookVersion && this.bookVersion.price) {
                    return Number(Number(this.SKUs[this.skuIndex].price)*10/this.bookVersion.price).toFixed(1);
                }
                return Number(Number(this.SKUs[this.skuIndex].price)*10/Number(this.SKUs[this.skuIndex].original_price)).toFixed(1);
            },
            bdStyle: function() {
                return {
                    width: this.screenWidth/2.5 + 'px'
                }
            },
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
            summaryStyle: function() {
                return {
                    width: (this.screenWidth-40) + 'px'
                }
            },
            chooseSkuStyle: function() {
                return {
                    width: this.screenWidth-265 + 'px',
                }
            },
            skuTitleStyle: function() {
                return {
                    width: this.screenWidth-285 + 'px',
                }
            },
            bottomBarStyle: function() {
                return {
                    backgroundColor: this.book.color?this.book.color:'#fff0f0'
                }
            },
            inReminder: function() {
                var in_reminder = false;
                var _this = this;
                _(this.reminders).forEach(function (reminder) {
                    if(reminder.book_id === _this.book.id) {
                        in_reminder = true;
                    }
                })
                console.log('in reminder '+in_reminder)
                return in_reminder;
            },
            bookSummary: function() {
                if (_.isEmpty(this.book.summary)){
                    return [];
                }
                return this.book.summary.split('\n');
            },
            bookAuthorIntro: function() {
                if (_.isEmpty(this.book.author_intro)){
                    return [];
                }
                return this.book.author_intro.split('\n');
            },
            bookCatalog: function() {
                if (_.isEmpty(this.book.catalog)){
                    return [];
                }
                return this.book.catalog.split('\n');
            },
            ...mapState({
                userId: state => state.user.userId,
                user: state => state.user.user,
                reminders: state => state.cart.reminders,
                items: state => state.cart.items,
                books: state => state.books.books,
                searchBooks: state => state.search.books
            })
        },
        created: function() {
            this.$store.dispatch('user/getUser');
            var isbn = this.$route.params.isbn;
            this.isbn = isbn;
            this.from = this.$route.query.from;
            console.log('isbn='+this.isbn);
            console.log('from='+this.from);
            if (this.from==='search' && this.searchBooks.length>0) {
                this.book = this.searchBooks.find(b => {
                    return b.isbn === isbn;
                });
            }

            if(this.from!=='search' && this.books.length>0) {
                this.book = this.books.find(b => {
                    return b.isbn === isbn;
                });
            }

            if (this.book) {
                this.SKUs = this.book.for_sale_skus;
                this.bookVersion = this.SKUs[this.skuIndex] ? this.SKUs[this.skuIndex].book_version : "";
                this.selectSku = this.SKUs[this.skuIndex];
            }else {
                this.bookLoading = true;
                axios.get('/wx-api/get_book/' + isbn).then(res => {
                    this.bookLoading=false;
                    this.book = res.data;
                    this.SKUs = this.book.for_sale_skus;
                    this.soldSku = this.book.latest_sold_sku;
                    this.bookVersion = this.SKUs[this.skuIndex] ? this.SKUs[this.skuIndex].book_version : "";
                    this.selectSku = this.SKUs[this.skuIndex];
                    console.log(this.SKUs);
                    console.log('sold sku:' + JSON.stringify(this.soldSku));
                });
            }
            this.$store.dispatch('cart/items');
            this.$store.dispatch('cart/reminders');
            var _this = this;
            setTimeout(function() {
                if ((parseInt(_this.from)===1 || _this.from==='notify') && _this.SKUs.length===0) {
                    var message = "这本书刚刚被买走";
                    console.log('sold sku='+JSON.stringify(_this.soldSku));
                    if (_this.soldSku.length>0) {
                        message = "来晚一步，"+dayjs(_this.soldSku[0].sold_at).fromNow()+"被人买走了";
                    }
                    _this.$notify({
                        message,
                        duration: 4000,
                        background: 'rgba(0,0,0,.5)'
                    });
                }
            }, 1000);
            // 按秒增加阅读时间
            this.timer = setInterval(function() {
                _this.viewSecond=_this.viewSecond+2;
                _this.viewBook();
            }, 2000);
            // 5分钟后不用上报了
            setTimeout(function(){
                clearInterval(_this.timer);
            }, 5*60*100)
        },
        mounted: function() {
            this.screenWidth = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
            document.body.scrollTop = 0;
        },
        beforeRouteLeave: function(to, from, next) {
            clearInterval(this.timer);
            next();
        },
        beforeRouteUpdate: function(to, from, next) {
            var isbn = to.params.isbn;
            this.from = isbn;
            this.viewSecond = 1;
            console.log(isbn);
            this.bookLoading=true;
            axios.get(`/wx-api/get_book/${isbn}`).then(res => {
                this.bookLoading=false;
                this.book = res.data;
                this.SKUs = this.book.for_sale_skus;
                console.log(this.SKUs);
                document.body.scrollTop = 0;
                document.documentElement.scrollTop = 0;
            });
            this.loading=true;
            axios.get('/wx-api/get_book_relations/'+isbn).then(res => {
                this.otherBooks = res.data.data;
                this.page = 1;
                this.lastPage = res.data.last_page;
                this.nextPageUrl = res.data.next_page_url;
                this.finished = false;
                this.loading = false;
            });
            this.displaySummary = 0;
            this.displayAuthor = 0;
            this.displayCatalog = 0;
            this.skuIndex = 0;
            next();
        },
        methods: {
            recommends: function() {
                if (this.finished) {
                    return;
                }
                this.loading = true;
                axios.get('/wx-api/get_book_relations/' + this.isbn + '?page=' + this.page).then(res => {
                    this.loading = false;
                    this.otherBooks = this.otherBooks.length===0?res.data.data:this.otherBooks.concat(res.data.data);
                    this.page = this.page+1;
                    this.lastPage = parseInt(res.data.last_page);
                    this.nextPageUrl = res.data.next_page_url;
                    if (!this.nextPageUrl) {
                        this.finished = true;
                    }
                });
            },
            viewBook: function() {
                axios.post('/wx-api/view_book', {
                    book: this.book.id,
                    user: this.userId,
                    source: this.from,
                    second: this.viewSecond
                }).then(res => {
                });
            },
            inCart: function(sku) {
                var in_cart = false;
                _(this.items).forEach(function (item) {
                    if (item.book_sku_id === sku.id){
                        in_cart = true;
                    }
                })
                console.log('in cart '+in_cart);
                return in_cart;
            },
            addToCart: function() {
                this.addingToCart = true;
                if (this.user===''||this.user.length===0) {
                    this.subscribeDialogVisible = true;
                    return;
                } else if(this.user.subscribe===0) {
                    this.subscribeDialogVisible = true;
                    return;
                }
                var sku = this.SKUs[this.skuIndex];
                var _this = this;
                this.$store.dispatch('cart/addSkuToCart', { sku, source:'book' }).then(res => {
                    if (res.data.code && res.data.code === 500) {
                        _this.$dialog.alert({
                            message: res.data.msg,
                            center: true
                        });
                    }
                    _this.dialogVisible = false;
                    _this.addingToCart = false;
                })
            },
            addReminder: function() {
                if (this.user===''||this.user.length===0) {
                    this.subscribeDialogVisible = true;
                    return;
                } else if(this.user.subscribe===0) {
                    this.subscribeDialogVisible = true;
                    return;
                }
                this.addingToReminder = true;
                var book = this.book;
                var _this = this;
                this.$store.dispatch('cart/addBookToReminder', { book }).then(res => {
                    _this.addingToReminder = false;
                });
            },
            removeReminder: function() {
                this.addingToReminder = true;
                var book = this.book;
                var _this = this;
                this.$store.dispatch('cart/removeBookFromReminder', { book }).then(res =>{
                    _this.addingToReminder = false;
                });
            },
            activeSkuStyle: function(index) {
                if(index===this.skuIndex) {
                    return 'bd-dialog-active'
                }
            },
            showDialog: function() {
                this.dialogVisible = true;
            },
            goLevelDesc: function() {
                this.dialogVisible = false;
                this.$router.push('/pc/level_desc')
            },
            chooseSku: function(index) {
                this.skuIndex = index;
                this.bookVersion = this.SKUs[this.skuIndex].book_version;
                this.selectSku = this.SKUs[index];
            },
            displayAuthorTotal: function() {
                this.displayAuthor = 1;
            },
            displayAuthorSub: function() {
                this.displayAuthor = 0;
            },
            displayCatalogTotal: function() {
                this.displayCatalog = 1;
            },
            displayCatalogSub: function() {
                this.displayCatalog = 0;
            },
            displayTotal: function() {
                this.displaySummary = 1;
            },
            displaySub: function() {
                this.displaySummary = 0;
            }
        },
        components: {
            BottomBar2,
            Loading
        }
    }
</script>
<style scoped>
    body {
        background-color: white;
        margin: 0;
    }
    p {
        font-size: 0.938em;
        line-height: 1.6em;
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
    .bd-top {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        padding: 20px 0 15px 0;
        background-color: #3D404A;
        position: relative;
    }
    .bd-msg {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        color: white;
        background: black;
        height: 24px;
        line-height: 24px;
        opacity: .7;
        text-align: center;
        font-weight: 200;
        font-size: 15px;
        padding: 2px 0;
    }
    .bd-cover {
        display: flex;
        flex-direction: row;
        justify-content: center;
        align-items: center;
    }
    .bd-mask {
        position: absolute;
        top: 23px;
        background-color: black;
        opacity: 0.6;
        display: flex;
        flex-direction: row;
        justify-content: center;
        align-items: center;
    }
    .bd-sold-out {
        font-size: 16px;
        color: white;
        font-weight: 600;
    }
    .bd-name {
        font-size: 20px;
        font-weight: 600;
        margin-top: 15px;
        padding: 0 20px;
        text-align: center;
    }
    .bd-sub-name {
        font-size: 16px;
        margin-top: 5px;
        margin-bottom: 36px;
        text-align: center;
    }
    .bd-activity {
        position: absolute;
        left: 0;
        bottom: 0;
        width: 100%;
        height: 36px;
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        background: #f1e1d9;
    }
    .bd-activity-title {
        display: flex;
        flex-direction: row;
        align-items: center;
        padding-left: 15px;
        color: #f39874;
    }
    .bd-activity-desc {
        font-size: 14px;
        color: #f39874;
    }
    .bd-activity-go {
        display: flex;
        flex-direction: row;
        align-items: center;
        padding-right: 10px;
        font-size: 14px;
        color: #f39874;
    }
    .bd-sku-group{
        display: flex;
        flex-direction: row;
        align-items: center;
        padding: 15px 15px 0 15px;
    }
    .bd-sku-price {
        font-size: 22px;
        font-weight: bold;
    }
    .bd-sku-qi {
        font-size: 14px;
        color: #888888;
        margin-left: 3px;
        margin-bottom: -3px;
    }
    .bd-sku-discount {
        margin-left: 5px;
        margin-bottom: 2px;
    }
    .bd-info-group {
        display: flex;
        flex-direction: column;
        padding: 10px 15px 15px 15px;
    }
    .bd-info-item {
        display: flex;
        flex-direction: row;
        position: relative;
        font-size: 13px;
    }
    .bd-info-item:not(:first-child) {
        margin-top: 5px;
    }
    .bd-info-item-title {
        color: #9b9b9b;
        width: 56px;
        text-align: justify;
        height: 18px;
        overflow: hidden;
    }
    .bd-info-item-title:after {
        display: inline-block;
        width: 100%;
        content: '';
    }
    .bd-info-item-body {
        color: #414a4a;
    }
    .custom-text {
        color: #1AB32B;
        margin-left: 3px;
        opacity: .6;
    }
    .bd-summary-title {
        height: 18px;
        padding: 15px 0 0 15px;
        font-size: 17px;
    }
    .bd-summary {
        color: #556666;
        padding: 0 15px;
        font-size: 15px;
    }
    .bd-summary-display {
        text-align: center;
        font-size: 14px;
        color: #1AB32B;
        opacity: 0.6;
        display:flex;
        flex-direction: row;
        justify-content: center;
        align-items: center;
        margin-top: -3px;
    }
    .bd-relation-title {
        height: 18px;
        padding: 20px;
        font-size: 17px;
    }
    .bd-relations {
        display: flex;
        flex-direction: row;
        flex-wrap: wrap;
    }
    .bd-re-book {
        display: flex;
        flex-direction: column;
        align-items: center;
        margin-left: 20px;
        margin-bottom: 10px;
        position: relative;
    }
    .bd-re-on-sale {
        position: absolute;
        top: 2px;
        right: 0;
        padding: 3px 8px;
        font-size: 12px;
        font-weight: 300;
        background-color: #ff4848;
        color: white;
    }
    .bd-re-book-cover {
        border-radius: 4px;
        border: 2px solid white;
        -webkit-box-shadow: 2px 2px 5px 0px rgba(204,204,204,0.5);
        -moz-box-shadow: 2px 2px 5px 0px rgba(204,204,204,0.5);
        box-shadow: 2px 2px 5px 0px rgba(204,204,204,0.5);
    }
    .bd-re-book-name {
        font-size: 13px;
        color: #3D404A;
        text-overflow:ellipsis;
        white-space:nowrap;
        overflow:hidden;
        margin-top: 5px;
        text-align: center;
    }
    .pop-top {
        display: flex;
        flex-direction: row;
        align-items: center;
        padding: 15px 20px;
        position: relative;
    }
    .pop-name {
        font-size: 18px;
        text-overflow:ellipsis;
        white-space:nowrap;
        overflow:hidden;
        width: 80%;
    }
    .pop-close {
        position: absolute;
        top: 15px;
        right: 20px;
        color: #aaaaaa;
    }
    .pop-desc {
        display:flex;
        flex-direction:row;
        justify-content:space-between;
        color:#ccc;
        font-size:13px;
        padding: 0 20px 10px 20px;
    }

    .bd-dialog-items {
        display:flex;
        flex-direction: column;
        padding: 5px 20px 10px 20px;
    }
    .bd-dialog-item {
        border:2px solid #eee;
        border-radius:4px;
        margin-bottom: 10px;
        height: 60px;
        line-height: 60px;
        padding: 0 10px;
        display: flex;
        flex-direction: row;
        align-items: center;
    }

    .pop-bottom {
        padding: 0 20px 20px 20px;
    }
    .bd-dialog-active {
        border:2px solid #ff4848;
    }
    .bd-dialog-item-price {
        display: flex;
        flex-direction: row;
        align-items: center;
        font-size: 22px;
        font-weight: 500;
        color: #3D404A;
    }
    .bd-dialog-item-desc {
        font-size: 14px;
        font-weight: 300;
        color: #888;
        margin-left: 10px;
        line-height: 18px;
    }
    .bd-level-dialog {
        position:fixed;
        bottom:0;
        left:0;
        margin-bottom: -1px;
    }
    .hly-notify {
        opacity: .7;
    }
</style>
