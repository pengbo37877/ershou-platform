<template>
    <div class="recover-order-book">
        <div class="recover-order-book-cover" style="width: 40px;min-height: 60px;max-height: 70px;">
            <img :src="item.book.cover_replace" style="width: 40px;max-height: 70px;"/>
        </div>
        <div class="recover-order-book-detail" :style="detailWidth">
            <div class="recover-order-book-name" style="color: #888888;text-decoration-line: line-through" v-if="item.review_result===0">{{item.book.name}}</div>
            <div class="recover-order-book-name" v-else>{{item.book.name}}</div>
            <!--<div class="recover-order-book-author">{{item.book.author}}</div>-->
            <div class="recover-order-book-price" v-if="order.recover_status===70 && item.review_result===1 && item.book_sku && item.is_add===0">
                ￥{{item.price}} <img src="/images/recover-price-arrow-right.png" style="height: 14px;margin:0 4px 0 8px;" alt=""> <span style="color: #32A14C;font-weight: bold">￥{{item.reviewed_price}}</span>
            </div>
            <div class="recover-order-book-price" v-if="order.recover_status!==70 && item.review_result===1 && item.book_sku && item.is_add===0">￥{{item.price}}
            </div>
            <div class="recover-order-book-price" v-if="order.recover_status===70 && item.review_result===1 && item.book_sku && item.is_add===1">
                ￥{{item.reviewed_price}}
            </div>
            <div class="recover-order-book-price" v-if="item.review_result===1 && !item.book_sku">
                ￥{{item.price}}
            </div>
            <div class="recover-order-book-price price-reject" v-if="item.review_result===0">￥{{item.price}}</div>
            <div class="recover-order-sku-title rejected" v-if="item.review_result===0">{{item.review}}</div>
            <div class="recover-order-sku-title reviewed" v-if="item.book_sku && item.is_add===1">{{item.book_sku.title}}(审核员添加)</div>
            <div class="recover-order-sku-title reviewed" v-if="item.book_sku && item.is_add===0 && item.book_version_id">
                {{item.book_sku.title}} (版本变化)
            </div>
            <div class="recover-order-sku-title reviewed" v-if="item.book_sku && item.book_sku.level===60 && item.is_add===0 && !item.book_version_id">
                {{item.book_sku.title}}
            </div>
        </div>
    </div>
</template>

<style scoped>
    .recover-order-book {
        position: relative;
        display: flex;
        flex-direction: row;
        border-bottom: 0.5px solid #ebedf0;
    }
    .recover-order-book-cover {
        padding: 10px 10px 10px 20px;
    }
    .recover-order-book-detail {
        position:relative;
    }
    .recover-order-book-name {
        font-size: 14px;
        color: #555555;
        text-overflow:ellipsis;
        white-space:nowrap;
        overflow:hidden;
        position: absolute;
        width: 100%;
        left: 0;
        top: 10px;
    }
    .recover-order-book-author {
        font-size: 12px;
        color: #aaaaaa;
        text-overflow:ellipsis;
        white-space:nowrap;
        overflow:hidden;
        position: absolute;
        width: 100%;
        left: 0;
        top: 30px;
    }
    .recover-order-book-price {
        font-size: 14px;
        color: #888888;
        position: absolute;
        width: 100%;
        left: 0;
        bottom: 15px;
        display: flex;
        flex-direction: row;
        align-items: center;
    }
    .price-reject {
        text-decoration-line: line-through;
        color: #888888;
    }
    .recover-order-sku-title {
        font-size: 12px;
        color: #555555;
        position: absolute;
        width: 100%;
        left: 0;
        bottom: 15px;
        text-align: right;
    }
    .reviewed {
        color: #888888;
    }
    .rejected {
        color: #ff7171;
    }
</style>

<script>
    export default {
        props:['order', 'item', 'screenWidth'],
        computed: {
            detailWidth: function() {
                return {
                    width: this.screenWidth-90+'px',
                }
            },
        }
    }
</script>