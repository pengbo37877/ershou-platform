<template>
    <div class="cart-sold-item" :style="{width: screenWidth+'px', minHeight: '100px'}">
        <router-link :to="`/pc/book/${book.isbn}`" class="cart-book-cover" :style="coverStyle">
            <img :src="book.cover_replace" style="width: 55px;max-height: 80px;"/>
        </router-link>
        <div class="cart-book-detail" :style="detailStyle">
            <div class="cart-book-name" :style="detailWidth">{{book.name}}</div>
            <div class="cart-book-author" :style="detailWidth">{{book.author}}</div>
        </div>
        <div class="cart-reminder-price">
            <div class="cart-reminder-sale-price">
                {{ salePrice(book) }}
            </div>
            <div class="cart-reminder-many-sku" v-if="book.for_sale_skus.length>1">起</div>
            <div class="cart-reminder-sale-discount">
                {{ saleDiscount(book) }}折
            </div>
        </div>
        <div class="cart-add-to-cart" @click="addSkuDeleteReminder()" v-if="SKUs.length===1">加入购物袋</div>
        <div class="cart-add-to-cart" @click="dialogVisible=true" v-else>加入购物袋</div>

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
</template>

<script>
    import { mapGetters, mapState, mapActions } from 'vuex';
    export default {
        data() {
            return {
                dialogVisible: false,
                addingToCart: false,
                SKUs: [],
                skuIndex: 0,
                bookVersion: '',
                selectSku: '',
            }
        },
        props: ['book', 'screenWidth'],
        computed: {
            detailStyle: function() {
                return {
                    width: this.screenWidth-95+'px',
                    maxHeight: '80px'
                }
            },
            detailWidth: function() {
                return {
                    width: this.screenWidth-95+'px',
                }
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
            this.SKUs = this.book.for_sale_skus;
            this.bookVersion = this.SKUs[this.skuIndex] ? this.SKUs[this.skuIndex].book_version : "";
            this.selectSku = this.SKUs[this.skuIndex];
            this.wxApi.wxConfig('','');
        },
        methods: {
            activeSkuStyle: function(index) {
                if(index===this.skuIndex) {
                    return 'bd-dialog-active'
                }
            },
            addToCart: function() {
                this.addingToCart = true;
                var sku = this.SKUs[this.skuIndex];
                console.log('cartRecommendBook addToCart index='+this.skuIndex);
                this.addSkuToCart({ sku, source: 'cart'}).then(res => {
                    if (res.data.code && res.data.code === 500) {
                        this.$dialog.alert({
                            message: res.data.msg,
                            center: true
                        });
                    }
                    this.dialogVisible = false;
                    this.addingToCart = false;
                    this.$store.dispatch('cart/recommends');
                });
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
            salePrice: function(book) {
                var price = 0;
                var _this = this;
                if (book.for_sale_skus.length > 0) {
                    _(book.for_sale_skus).forEach(function (sku) {
                        if (price === 0) {
                            price = Number(sku.price).toFixed(2);
                            _this.sku = sku;
                        } else if (price > Number(sku.price)) {
                            price = Number(sku.price).toFixed(2);
                            _this.sku = sku;
                        }
                    });
                }
                return Number(price).toFixed(2);
            },
            saleDiscount: function(book) {
                var price = 0;
                var discount = 0;
                if (book.for_sale_skus.length > 0) {
                    _(book.for_sale_skus).forEach(function (sku) {
                        if (price === 0) {
                            price = Number(sku.price).toFixed(2);
                            discount = Number(sku.price) * 10 / Number(book.price);
                        } else if (price > Number(sku.price)) {
                            price = Number(sku.price).toFixed(2);
                            discount = Number(sku.price) * 10 / Number(book.price);
                        }
                    });
                }
                return Number(discount).toFixed(1);
            },
            addSkuDeleteReminder: function() {
                this.addSkuToCart({ sku: this.sku, source: 'cart'}).then(res => {
                    this.$store.dispatch('cart/recommends');
                });
            },
            ...mapActions('cart',[
                'addSkuToCart',
            ])
        }
    }
</script>

<style scoped>
    .cart-sold-item {
        position: relative;
        display: flex;
        flex-direction: row;
        border-bottom: 0.5px solid #ebedf0;
    }
    .cart-book-cover {
        display: flex;
        flex-direction: row;
        justify-content: center;
        align-items: center;
        padding: 10px 10px 10px 15px;
        width: 55px;
        max-height: 80px;
    }
    .cart-book-detail {
        display:flex;
        flex-direction:column;
        justify-content: flex-start;
        margin-top: 10px;
    }
    .cart-book-name {
        font-size: 15px;
        color: #3D404A;
        text-overflow:ellipsis;
        white-space:nowrap;
        overflow:hidden;
    }
    .cart-book-author {
        font-size: 13px;
        color: #888888;
        text-overflow:ellipsis;
        white-space:nowrap;
        overflow:hidden;
    }
    .cart-reminder-price {
        position:absolute;
        left: 80px;
        bottom: 10px;
        display: flex;
        flex-direction: row;
        align-items: center;
    }
    .cart-reminder-sale-price {
        width: fit-content;
        font-size: 16px;
        color: #3D404A;
        display: flex;
        flex-direction: row;
        align-items: center;
    }
    .cart-reminder-sale-discount {
        width: fit-content;
        font-size: 10px;
        color: #ff4848;
        border:0.5px solid #ff4848;
        border-radius: 2px;
        margin-left: 6px;
        padding: 0 4px;
    }
    .cart-reminder-many-sku {
        font-size: 12px;
        color: #888888;
        margin-left: 3px;
        margin-top: 1px;
        font-weight: lighter;
    }
    .cart-add-to-cart {
        position:absolute;
        right: 15px;
        bottom: 8px;
        background-color: #ff4848;
        color: white;
        font-size: 14px;
        padding: 5px 11px;
        border-radius: 4px;
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
</style>