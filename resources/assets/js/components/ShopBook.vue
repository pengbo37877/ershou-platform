<template>
    <div class="shop-item">
        <router-link tag="div" :to="{path: `/wechat/book/${book.isbn}?from=shopbook`}" class="shop-book">
            <div class="shop-book-cover" :style="style">
                <img :src="book.cover_replace" alt="" class="shop-book-image" :style="imgStyle">
                <div class="shop-mask" v-if="skus.length===0" :style="maskStyle">
                    <div class="shop-sold-out">卖光了</div>
                </div>
            </div>
            <div class="shop-book-info">
                <div class="shop-book-name" :style="style2">{{book.name}}</div>
                <div class="shop-book-author" :style="style2">{{book.author}}</div>
                <div class="shop-book-rating" :style="style2" v-if="Number(book.rating_num)>0">豆瓣评分：{{book.rating_num}}</div>
                <div class="shop-book-rating" :style="style2" v-else></div>
                <div class="shop-book-sale-info" :style="saleInfo" v-if="skus.length===1">
                    <div class="shop-book-price-desc">
                        <div class="shop-book-sale-price">
                            <span style="font-size: 11px;font-weight: 200">￥</span>{{salePrice}}
                        </div>
                        <div class="shop-book-sale-discount">{{book.for_sale_skus[0].level===100?'全新 ':''}}{{Number(saleDiscount).toFixed(1)}}折</div>
                        <div class="shop-book-sale-discount" v-if="book.type===2">套装</div>
                    </div>
                    <router-link :to="`/wechat/user/${prevUser.mp_open_id}`" class="shop-book-prev-user" v-if="prevUser">
                        <img :src="prevUser.avatar" alt="" style="width: 100%;height: 100%">
                    </router-link>
                </div>
                <div class="shop-book-sale-info" :style="saleInfo" v-else-if="skus.length>1">
                    <div class="shop-book-price-desc">
                        <div class="shop-book-sale-price">
                            <span style="font-size: 11px;font-weight: 200">￥</span>{{salePrice}}
                            <div class="shop-book-many-sku">起</div>
                        </div>
                        <div class="shop-book-sale-discount">{{Number(saleDiscount).toFixed(1)}}折</div>
                        <div class="shop-book-sale-discount" v-if="book.type===2">套装</div>
                    </div>
                    <router-link :to="`/wechat/user/${prevUser.mp_open_id}`" class="shop-book-prev-user" v-if="prevUser">
                        <img :src="prevUser.avatar" alt="" style="width: 100%;height: 100%">
                    </router-link>
                </div>
                <div class="shop-book-sale-info" :style="saleInfo" v-else>
                    <div class="shop-book-price-desc">
                        <div class="shop-book-sale-price"><span style="font-size: 11px;font-weight: 200">￥</span>{{book.price}}</div>
                        <div class="shop-book-sale-discount">{{Number(Number(book.sale_discount)/10).toFixed(1)}}折</div>
                    </div>
                    <!--<div class="shop-add-to-reminder shop-added" v-if="hasReminder" @click="removeFromReminder">取消到货提醒</div>-->
                    <!--<div class="shop-add-to-reminder" v-else @click="addToReminder">到货提醒</div>-->
                </div>
            </div>
        </router-link>
    </div>
</template>

<script>
    import { mapGetters, mapState, mapActions } from 'vuex'
    export default {
        data() {
            return {
                screenWidth: 0,
                salePrice:0,
                saleDiscount:0,
                prevUser:'',
                skus: []
            }
        },
        props: ['book'],
        computed: {
            style: function() {
                return {
                    width: this.screenWidth/4.5+'px',
                    height: this.screenWidth*1.43/5+'px'
                }
            },
            imgStyle: function() {
                return {
                    width: Number(Number(this.screenWidth/4.5)+5)+'px',
                    position: 'absolute',
                    clip: 'rect(0,'+(this.screenWidth/4.5)+'px,'+(this.screenWidth*1.43/4.5)+'px,3px)'
                }
            },
            style2: function() {
                return {
                    width: this.screenWidth-this.screenWidth/4.5-44+'px'
                }
            },
            maskStyle: function() {
                return {
                    width: this.screenWidth/4.5+'px',
                    height: '24px'
                }
            },
            saleInfo: function() {
                return {
                    width: this.screenWidth-this.screenWidth/4.5-49+'px',
                }
            },
            ...mapState({
                user: state => state.user.user
            }),
        },
        created: function() {
            this.wxApi.wxConfig('','');
            this.screenWidth = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
            this.build();
        },
        methods: {
            build: function () {
                var _this = this;
                if (this.book.for_sale_skus.length > 0) {
                    this.skus = this.book.for_sale_skus;
                    _(this.book.for_sale_skus).forEach(function (sku) {
                        var version = sku.book_version;
                        var originalPrice = sku.original_price;
                        if (version) {
                            originalPrice = version.price;
                        }
                        if (_this.salePrice === 0) {
                            _this.salePrice = Number(sku.price).toFixed(2);
                            _this.saleDiscount = Number(sku.price) * 10 / Number(originalPrice);
                            _this.prevUser = sku.user;
                        } else if (_this.salePrice > Number(sku.price)) {
                            _this.salePrice = Number(sku.price).toFixed(2);
                            _this.saleDiscount = Number(sku.price) * 10 / Number(originalPrice);
                            _this.prevUser = sku.user;
                        }
                    });
                }
            },
            addToReminder: function() {
                axios.post('/wechat/add_book_to_reminder', {
                    book: this.book.id
                }).then(res => {
                    if (res.data.code === 500) {
                        this.$dialog.alert({
                            message: res.data.msg,
                            center: true
                        });
                    } else {
                        this.book.reminders.concat(res.data);
                        this.hasReminder = true;
                    }
                })
            },
            removeFromReminder: function() {
                axios.post('/wechat/remove_book_from_reminder', {
                    book: this.book.id
                }).then(res => {
                    if (res.data.code === 500) {
                        this.$dialog.alert({
                            message: res.data.msg,
                            center: true
                        });
                    } else {
                        var index = this.book.reminders.indexOf(res.data)
                        if (index !== -1) {
                            this.book.reminders.splice(index, 1)
                        }
                        this.hasReminder = false;
                    }
                })
            }
        }
    }
</script>

<style scoped>
    .shop-item {
        position: relative;
    }
    .shop-book {
        display: flex;
        flex-direction: row;
        padding: 20px 15px 30px 15px;
        border-bottom: 0.5px solid #eee;
    }
    .shop-book-cover {
        position: relative;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }
    .shop-book-image {
        position: absolute;
        top: 0;
        left: -3px;
        z-index: -100;
    }
    .shop-book-info {
        display: flex;
        flex-direction: column;
        margin-left: 14px;
        margin-top: -4px;
        margin-bottom: -10px;
        position: relative;
        justify-content: space-between;
    }
    .shop-book-name {
        font-size: 16px;
        color: #3D404A;
        text-overflow:ellipsis;
        white-space:nowrap;
        overflow:hidden;
        padding: 2px;
    }
    .shop-book-author {
        font-size: 12px;
        font-weight: 300;
        color: #888888;
        text-overflow:ellipsis;
        white-space:nowrap;
        overflow:hidden;
        padding: 2px;
        opacity: 0.8;
    }
    .shop-book-rating {
        font-size: 12px;
        font-weight: 300;
        color: #227E2C;
        text-overflow:ellipsis;
        white-space:nowrap;
        overflow:hidden;
        padding: 2px;
        opacity: 0.5;
        margin-bottom: 14px;
    }
    .shop-book-sale-info{
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        padding: 2px;
    }
    .shop-book-price-desc {
        display: flex;
        flex-direction: row;
        align-items: center;
    }
    .shop-book-sale-price {
        width: fit-content;
        font-size: 16px;
        color: #3D404A;
        display: flex;
        flex-direction: row;
        align-items: center;
    }
    .shop-book-many-sku {
        font-size: 14px;
        color: #9b9b9b;
        margin-left: 3px;
        font-weight: 300;
    }
    .shop-book-sale-discount {
        width: fit-content;
        font-size: 10px;
        color: #ff4848;
        border:0.5px solid #ff4848;
        border-radius: 2px;
        margin-left: 6px;
        padding: 0 4px;
    }
    .shop-book-prev-user {
        width: 32px;
        height: 32px;
        border-radius: 1px;
        border: 1px solid white;
        -webkit-box-shadow: 2px 2px 5px 0px rgba(204,204,204,0.5);
        -moz-box-shadow: 2px 2px 5px 0px rgba(204,204,204,0.5);
        box-shadow: 2px 2px 5px 0px rgba(204,204,204,0.5);
    }
    .shop-add-to-reminder {
        display: flex;
        flex-direction: row;
        align-items: center;
        padding: 0 12px;
        height: 30px;
        border-radius: 4px;
        border: 1px solid #3D404A;
        background-color: #3D404A;
        color: white;
        font-size: 14px;
        text-align: center;
    }
    .shop-added {
        background-color: white;
        color: #3D404A;
        border: 1px solid #3D404A;
        opacity: 0.2;
    }
    .shop-mask {
        background-color: black;
        opacity: 0.6;
        display: flex;
        flex-direction: row;
        justify-content: center;
        align-items: center;
        margin-top: -29px;
    }
    .shop-sold-out {
        font-size: 14px;
        color: white;
    }
</style>