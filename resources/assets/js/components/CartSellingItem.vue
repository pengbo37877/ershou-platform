<template>
    <div class="cart-selling-item" :style="{width: screenWidth+'px', minHeight: '100px'}">
        <div class="cart-select-circle" @click="chooseCartItem(item)" style="width: 40px;">
            <div class="cart-sku-selected" v-show="item.selected==1">
                <input type="checkbox" v-model="item.selected==1"/>
                <van-icon name="certificate" />
            </div>
            <div v-show="item.selected==0">
                <input type="checkbox" v-model="item.selected==1"/>
                <van-icon name="circle" />
            </div>
        </div>
        <router-link :to="`/wechat/book/${item.book.isbn}`" class="cart-book-cover">
            <img :src="item.book.cover_replace" style="width: 55px;max-height: 80px;"/>
        </router-link>
        <div class="cart-book-detail" :style="detailStyle">
            <div class="cart-book-name" :style="detailWidth">{{item.book.name}}</div>
            <div class="cart-book-author" :style="detailWidth">{{item.book.author}}</div>
        </div>
        <div class="cart-sku-title" v-if="item.book.for_sale_skus.length==1" :style="chooseWidth">{{item.book_sku.title}}</div>
        <div class="cart-sku-choose" v-else @click="showDialog()">
            <div class="cart-many-sku" :style="skuTitleWidth">{{item.book_sku.title}}</div>
            <van-icon name="arrow-down" style="margin-left: 3px"/>
        </div>
        <div class="cart-book-sale-price">{{item.book_sku.price}}</div>
        <div class="cart-delete-book-btn" @click="deleteCartItemAndReRecommend(item)">
            <van-icon name="cross" />
        </div>

        <van-popup v-model="dialogVisible" position="bottom">
            <div class="pop-top">
                <div class="pop-name">{{item.book.name}}</div>
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
                <div class="bd-dialog-item" :class="activeSkuStyle(index)" v-for="(sku,index) in item.book.for_sale_skus" @click="chooseSku(index)">
                    <div class="bd-dialog-item-price">￥{{sku.price}}</div>
                    <div class="bd-dialog-item-desc">{{sku.title}}</div>
                </div>
            </div>
            <div class="pop-bottom">
                <van-button square type="warning" style="border-radius: 4px" size="large" loading v-if="addingToCart">加入购物袋</van-button>
                <van-button square type="warning" style="border-radius: 4px" size="large" @click="okClick" v-if="!addingToCart">加入购物袋</van-button>
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
                skuIndex: 0,
                addingToCart: false
            }
        },
        props: ['item', 'screenWidth'],
        computed: {
            detailStyle: function() {
                return {
                    width: this.screenWidth-158+'px',
                    maxHeight: '80px'
                }
            },
            detailWidth: function() {
                return {
                    width: this.screenWidth-158+'px',
                }
            },
            chooseWidth: function() {
                return {
                    maxWidth: this.screenWidth-158+'px',
                }
            },
            skuTitleWidth: function () {
                return {
                    maxWidth: this.screenWidth-158+'px',
                }
            }
        },
        mounted: function() {
            var sku = this.item.book.for_sale_skus.find(s => s.id == this.item.book_sku.id)
            this.skuIndex = this.item.book.for_sale_skus.indexOf(sku);
            console.log('cart selling items skuIndex = '+this.skuIndex);
        },
        methods: {
            deleteCartItemAndReminder: function(item) {
                this.deleteCartItem({item});
                this.removeBookFromReminder({book: item.book});
                this.$store.dispatch('cart/recommends');
            },
            deleteCartItemAndReRecommend: function(item) {
                this.deleteCartItem({item});
                this.$store.dispatch('cart/recommends');
            },
            chooseCartItem: function(item) {
                console.log('chooseItem');
                if(item.book_sku.status!==4){
                    this.changeCartItemSelect({ item, selected: !item.selected})
                }
            },
            showDialog: function() {
                this.dialogVisible = true;
            },
            goLevelDesc: function() {
                this.dialogVisible = false;
                this.$router.push('/wechat/level_desc')
            },
            chooseSku: function(index) {
                this.skuIndex = index;
            },
            activeSkuStyle: function(index) {
                if(index==this.skuIndex) {
                    return 'bd-dialog-active'
                }
            },
            okClick: function() {
                // 更新item的book_sku_id
                var _this = this;
                this.updateCartItem({ item: this.item, book_sku_id: this.item.book.for_sale_skus[this.skuIndex].id , cb: function (data) {
                        if (data.code && data.code == 500) {
                            _this.$dialog.alert({
                                message:data.msg,
                                center:true
                            });
                        }
                    }});
                this.dialogVisible = false;
            },
            ...mapActions('cart',[
                'updateCartItem',
                'deleteCartItem',
                'removeBookFromReminder',
                'changeCartItemSelect'
            ])
        }
    }
</script>

<style scoped>
    .cart-selling-item {
        position: relative;
        display: flex;
        flex-direction: row;
        border-bottom: 0.5px solid #ebedf0;
    }
    input {
        position: absolute;
        clip: rect(0, 0, 0, 0);
    }
    .cart-select-circle {
        display: flex;
        flex-direction: row;
        justify-content: center;
        align-items: center;
        color: #eee;
        font-size: 20px;
        padding: 0 4px;
    }
    .cart-sku-selected {
        color: #3D404A;
    }
    .cart-book-cover {
        display: flex;
        flex-direction: row;
        justify-content: center;
        align-items: center;
        padding: 10px 0;
        width: 55px;
        max-height: 80px;
    }
    .cart-book-detail {
        display:flex;
        flex-direction:column;
        justify-content: flex-start;
        margin-left: 10px;
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
    .cart-sku-title {
        font-size: 13px;
        color: #ff4848;
        opacity: 0.8;
        display: flex;
        flex-direction: row;
        position:absolute;
        left: 115px;
        bottom: 11px;
    }
    .cart-sku-choose {
        font-size: 11px;
        color: #ff4848;
        opacity: 0.8;
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        border: 0.5px solid #FD9293;
        padding: 2px 5px;
        border-radius: 4px;
        position:absolute;
        left: 115px;
        bottom: 10px;
    }
    .cart-many-sku {
        text-overflow:ellipsis;
        white-space:nowrap;
        overflow:hidden;
    }
    .cart-book-sale-price {
        position:absolute;
        right: 15px;
        bottom: 8px;
        color: #333333;
    }
    .cart-delete-book-btn {
        position: absolute;
        top: 13px;
        right: 15px;
        font-size: 18px;
        height: 19px;
        line-height: 19px;
        color: #dcdfe6;
    }
    .cart-dialog-items {
        display:flex;
        flex-direction: column;
    }
    .cart-dialog-item {
        border:0.5px solid #eee;
        border-radius:4px;
        margin-bottom: 10px;
        height: 60px;
        line-height: 60px;
        padding: 0 10px;
        display: flex;
        flex-direction: row;
        align-items: center;
    }
    .cart-dialog-active {
        border:2px solid #ff4848;
    }
    .cart-ok-button {
        font-size: 16px;
        border-radius:4px;
        margin-bottom: 10px;
        height: 40px;
        line-height: 40px;
        text-align: center;
        color:white;
        background-color: #ff4848;
    }
    .cart-dialog-item-price {
        display: flex;
        flex-direction: row;
        align-items: center;
        font-size: 22px;
        font-weight: 500;
        color: #3D404A;
    }
    .cart-dialog-item-desc {
        font-size: 14px;
        font-weight: 300;
        color: #888;
        margin-left: 10px;
        line-height: 18px;
    }
    .cart-level-dialog {
        position:fixed;
        bottom:0;
        left:0;
        margin-bottom: -1px;
    }
    .cart-ok-button {
        font-size: 16px;
        font-wight: 200;
        border-radius:4px;
        margin-bottom: 10px;
        height: 40px;
        line-height: 40px;
        text-align: center;
        color:white;
        background-color: #ff4848;
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