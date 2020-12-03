<template>
    <router-link tag="div" :to="`/book/${bookVersion.book_id}/version/${bookVersion.id}/edit`" class="book-version">
        <div class="book-version-cover">
            <img :src="bookVersion.cover" style="width: 55px;max-height: 80px;" alt="">
        </div>
        <div class="book-version-detail" :style="detailStyle">
            <div class="book-version-name" :style="detailWidth">{{bookVersion.name}}</div>
            <div class="book-version-title" :style="detailWidth">{{bookVersion.title}}</div>
            <div class="book-version-press" :style="detailWidth">{{bookVersion.press}}</div>
            <div class="book-version-publish" :style="detailWidth">{{bookVersion.publish_year}}</div>
            <div class="book-version-price" :style="detailWidth"><span style="font-size: 11px">￥</span>{{bookVersion.price}}</div>
        </div>
    </router-link>
</template>

<script>
    export default {
        name: "BookVersionItem.vue",
        props: ['bookVersion', 'screenWidth'],
        computed: {
            detailStyle: function() {
                return {
                    width: this.screenWidth-100+'px',
                    minHeight: '80px'
                }
            },
            detailWidth: function() {
                return {
                    width: this.screenWidth-100+'px'
                }
            }
        }
    }
</script>

<style scoped>
    .book-version {
        display: flex;
        flex-direction: row;
        border-bottom: 0.5px solid #ebedf0;
    }
    .book-version-cover {
        padding: 10px 15px;
    }
    .book-version-detail {
        position: relative;
    }
    .book-version-name {
        font-size: 16px;
        padding-top: 10px;
    }
    .book-version-title {
        font-size: 12px;
        padding: 5px;
        color: #ff4848;
    }
    .book-version-press {
        font-size: 12px;
        color: #888888;
    }
    .book-version-publish {
        font-size: 12px;
        color: #888888;
    }
    .book-version-price {
        font-size: 17px;
        color: #f0ad4e;
    }
</style>