<template>
    <div class="cart-reminder" :style="{width: screenWidth+'px', minHeight: '100px'}">
        <router-link tag="div" :to="`/pc/book/${reminder.book.isbn}`" class="cart-book-cover">
            <img :src="reminder.book.cover_replace" style="width: 55px;max-height: 80px;"/>
        </router-link>
        <div class="cart-book-detail" :style="detailStyle">
            <div class="cart-book-name" :style="detailWidth">{{reminder.book.name}}</div>
            <div class="cart-book-author" :style="detailWidth">{{reminder.book.author}}</div>
        </div>
        <div class="cart-delete-book-btn" @click="removeBookFromReminder({book: reminder.book})">
            <van-icon name="cross" />
        </div>
        <div class="cart-right-bottom">
            <van-button round type="default" size="small" style="padding: 0 10px;color: #777777;" @click="removeBookFromReminder({book: reminder.book})">取消到货提醒</van-button>
        </div>
    </div>
</template>

<script>
    import { mapGetters, mapState, mapActions } from 'vuex';
    export default {
        data() {
            return {
            }
        },
        props: ['reminder', 'screenWidth'],
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
        methods: {
            ...mapActions('cart',[
                'removeBookFromReminder'
            ])
        }
    }
</script>

<style scoped>
    .cart-reminder {
        position: relative;
        display: flex;
        flex-direction: row;
        border-bottom: 0.5px solid #ebedf0;
    }
    input {
        position: absolute;
        clip: rect(0, 0, 0, 0);
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
    .cart-delete-book-btn {
        position: absolute;
        top: 13px;
        right: 15px;
        font-size: 18px;
        height: 19px;
        line-height: 19px;
        color: #dcdfe6;
    }
    .cart-right-bottom {
        position: absolute;
        bottom: 13px;
        right: 15px;
    }
</style>