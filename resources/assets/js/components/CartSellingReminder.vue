<template>
    <div class="cart-sold-item">
        <router-link :to="`/wechat/book/${reminder.book.isbn}`" class="cart-book-cover" :style="coverStyle">
            <img :src="reminder.book.cover_replace" :style="imgStyle"/>
        </router-link>
        <div class="cart-book-detail" :style="detailStyle">
            <div class="cart-book-name" :style="detailWidth">{{reminder.book.name}}</div>
            <div class="cart-book-author" :style="detailWidth">{{reminder.book.author}}</div>
            <div class="cart-reminder-price">
                <div class="cart-reminder-sale-price">
                    {{ salePrice(reminder.book) }}
                </div>
                <div class="cart-reminder-many-sku" v-if="reminder.book.for_sale_skus.length>1">起</div>
                <div class="cart-reminder-sale-discount">
                    {{ saleDiscount(reminder.book) }}折
                </div>
            </div>
        </div>
        <div class="cart-add-to-cart" @click="addSkuDeleteReminder()">加入购物袋</div>
        <div class="cart-delete-book-btn" @click="removeBookFromReminder({ book: reminder.book })">
            <van-icon name="close" />
        </div>
    </div>
</template>

<script>
    import { mapGetters, mapState, mapActions } from 'vuex';
    export default {
        data() {
            return {
                sku:''
            }
        },
        props: ['reminder', 'screenWidth'],
        computed: {
            coverStyle: function() {
                return {
                    width: this.screenWidth/6+'px',
                    height: this.screenWidth*1.43/6+'px'
                }
            },
            imgStyle: function() {
                return {
                    width: this.screenWidth/6+'px',
                }
            },
            detailStyle: function() {
                return {
                    width: (this.screenWidth*5/6-35)+'px',
                    height: this.screenWidth*1.43/6+'px'
                }
            },
            detailWidth: function() {
                return {
                    width: (this.screenWidth*5/6-35)+'px',
                }
            },
            ...mapGetters('cart',{
                sellingItems: 'sellingItems'
            })
        },
        methods: {
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
                var _this = this;
                this.addSkuToCart({ sku: this.sku, cb: function (data) {
                        if (data.code && data.code===500) {
                            _this.$dialog.alert({
                                message: data.msg,
                                center: true
                            });
                        }
                    } });
                this.removeBookFromReminder({ book: this.reminder.book });
            },
            ...mapActions('cart',[
                'addSkuToCart',
                'removeBookFromReminder',
            ])
        }
    }
</script>

<style scoped>
    .cart-sold-item {
        position: relative;
        display: flex;
        flex-direction: row;
        align-items: center;
        border-bottom: 0.5px solid #ddd;
    }
    .cart-book-cover {
        display: flex;
        flex-direction: row;
        justify-content: center;
        align-items: center;
        padding: 10px 10px 10px 15px;
    }
    .cart-book-detail {
        display:flex;
        flex-direction:column;
        justify-content:space-between;
        padding: 10px 10px 10px 0;
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
        font-size: 14px;
        color: #9b9b9b;
        margin-left: 3px;
        font-weight: 300;
    }
    .cart-add-to-cart {
        position:absolute;
        right: 20px;
        bottom: 8px;
        background-color: #ff4848;
        color: white;
        font-size: 14px;
        padding: 5px 11px;
        border-radius: 4px;
    }
    .cart-delete-book-btn {
        position: absolute;
        top: 13px;
        right: 20px;
        font-size: 18px;
        height: 19px;
        line-height: 19px;
        color: #dcdfe6;
    }
</style>