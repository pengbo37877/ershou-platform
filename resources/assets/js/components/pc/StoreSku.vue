<template>
    <div class="sku">
        <van-card
                :price="sku.price"
                :origin-price="sku.original_price"
        >
            <div slot="thumb">
                <img :src="sku.book_version.cover" alt="" v-if="sku.book_version">
                <van-uploader :after-read="onRead" name="image" accept="image/png, image/jpeg" max-size="500000" :oversize="overSize" v-else-if="sku.book.cover_replace">
                    <img :src="sku.book.cover_replace" alt="" >
                </van-uploader>
                <van-uploader :after-read="onRead" name="image" accept="image/png, image/jpeg" max-size="500000" :oversize="overSize" v-else>
                    <van-icon name="photograph" />
                </van-uploader>
            </div>
            <div slot="title" class="book-title" v-if="sku.book_version">{{sku.book_version.name}}</div>
            <div slot="title" class="book-title" v-else>{{sku.book.name}}</div>
            <div slot="desc" class="book-info">
                <div class="book-author">{{sku.book.author?sku.book.author:''}}</div>
                <div class="book-rating" v-if="sku.book_version">{{sku.book_version.press}} / {{sku.book_version.publish_year}}</div>
                <div class="book-rating" v-else>{{sku.book.press}} / {{sku.book.publish_year}}</div>
            </div>
            <div slot="tags" class="card__tags">
                <van-tag plain type="primary">{{sku.hly_code}}</van-tag>
                <van-tag plain type="danger" v-if="sku.store_shelf">当前位于{{sku.store_shelf.code}}</van-tag>
            </div>
            <div slot="num">
                <van-button round size="small" @click="versionsVisible=true" type="danger" v-if="sku.book.versions.length>0">选版本</van-button>
                <van-button round size="small" @click="addVersion">增版本</van-button>
            </div>
        </van-card>
        <div class="remove-sku" @click="removeSku">
            <van-icon name="cross" size="16px"/>
        </div>

        <van-popup v-model="versionsVisible" position="bottom" :overlay="true">
            <div class="book-version" :class="activeClass({id:0})" @click="chooseVersion({id:0})">
                <div class="book-version-cover">
                    <img :src="sku.book.cover_replace" style="width: 50px;max-height: 75px;" alt="">
                    <div style="position: absolute;left: 0;top: 0;">
                        <van-tag mark type="danger">默认版本</van-tag>
                    </div>
                </div>
                <div class="book-version-detail" :style="detailStyle">
                    <div class="book-version-name" :style="detailWidth">{{sku.book.name}}</div>
                    <div class="book-version-press" :style="detailWidth">{{sku.book.press}}</div>
                    <div class="book-version-publish" :style="detailWidth">{{sku.book.publish_year}}</div>
                    <div class="book-version-price" :style="detailWidth"><span style="font-size: 11px">￥</span>{{sku.book.price}}</div>
                </div>
            </div>
            <div class="book-version" :class="activeClass(bookVersion)" v-for="bookVersion in versions" :key="bookVersion.id" @click="chooseVersion(bookVersion)">
                <div class="book-version-cover">
                    <img :src="bookVersion.cover" style="width: 50px;max-height: 75px;" alt="">
                </div>
                <div class="book-version-detail" :style="detailStyle">
                    <div class="book-version-name" :style="detailWidth">{{bookVersion.name}}</div>
                    <div class="book-version-press" :style="detailWidth">{{bookVersion.press}}</div>
                    <div class="book-version-publish" :style="detailWidth">{{bookVersion.publish_year}}</div>
                    <div class="book-version-price" :style="detailWidth"><span style="font-size: 11px">￥</span>{{bookVersion.price}}</div>
                </div>
            </div>
        </van-popup>
    </div>
</template>

<script>
    export default {
        name: "StoreSku.vue",
        props: ['sku', 'skus', 'screenWidth'],
        data() {
            return {
                versions: [],
                versionsVisible: false
            }
        },
        computed: {
            detailStyle: function() {
                return {
                    width: this.screenWidth-100+'px',
                    maxHeigth: '70px'
                }
            },
            detailWidth: function() {
                return {
                    width: this.screenWidth-100+'px'
                }
            }
        },
        created: function() {
            this.versions = this.sku.book.versions;
        },
        methods: {
            activeClass: function(version) {
                if (version.id===this.sku.book_version_id) {
                    return 'book-version-active';
                }
            },
            removeSku: function() {
                var sku = this.skus.find((sku) => sku.id===this.sku.id);
                var index = this.skus.indexOf(sku);
                if (index !== -1) {
                    this.skus.splice(index, 1)
                }
            },
            chooseVersion: function(bookVersion) {
                // update sku version
                axios.post('/wx-api/update_sku_version',{
                    sku: this.sku.id,
                    version: bookVersion.id
                }).then(res=>{
                    this.versionsVisible=false;
                    if (res.data.code && res.data.code===500) {
                        this.$toast(res.data.msg);
                    } else {
                        this.sku = res.data;
                    }
                });
            },
            showVersions: function() {
                this.versionsVisible = true;
            },
            addVersion: function() {
                this.$router.push('/book/'+this.sku.book_id+'/versions')
            },
            onRead: function(file, detail) {
                console.log(file);
                // 上传封面
                var formData = new FormData();
                formData.append('file', file.file);
                formData.append('book',this.sku.book_id);

                var instance = axios.create({
                    withCredentials: true
                });
                instance.post('/wx-api/upload_cover',formData).then(res=>{
                    if (res.data.code && res.data.code===500) {
                        this.$toast(res.data.msg);
                    } else {
                        this.sku.book.cover_replace = res.data;
                    }
                })
            },
            overSize: function(file, detail) {
                this.$toast('图片超过了 500 KB');
            }
        }
    }
</script>
<style>
    .van-card {
        background-color: white;
        font-size: 16px;
        border-top: 1px solid #fff0f0;
    }

    .van-card:not(:first-child) {
        margin-top: 2px;
    }

    .van-card__thumb {
        position: relative;
        width: 80px;
        max-height: 110px;
        margin-right: 10px;
        flex: none;
        align-items: center;
        justify-content: center;
        padding-top: 20px;
    }

    .van-card__thumb img {
        width: 80px;
        max-height: 110px;
    }

    .card__tags .van-tag {
        margin-right: 5px;
    }

    .van-card__tag {
        top: 10px;
    }

    .van-card__content {
        height: 100%;
        padding-top: 10px;
    }

    .van-card__bottom, .van-card__desc {
        margin-top: 10px;
    }

    .van-sku-stepper-stock {
        display: none;
    }
</style>
<style scoped>
    .book-version {
        margin: 5px;
        display: flex;
        flex-direction: row;
        border: 2px solid #eeeeee;
        border-radius: 4px;
    }
    .book-version-active {
        border: 2px solid #ff5656;
    }
    .book-version-cover {
        padding: 5px;
        position: relative;
    }
    .book-version-detail {
        position: relative;
    }
    .book-version-name {
        font-size: 16px;
        padding-top: 5px;
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
    .sku{
        position: relative;
        width: 100%;
        height: auto;
        border-bottom: 0.5px solid #ebedf0;
    }

    .sku:not(:first-child) {
        margin-top: 10px;
    }

    .remove-sku {
        position: absolute;
        top: 18px;
        right: 15px;
        color: #888888;
    }

    .book-title {
        font-size: 1em;
        font-weight: normal;
        width: 90%;
    }

    .book-author {
        width: 90%;
    }

    .book-info {
        font-size: 11px;
        font-weight: lighter;
    }

    .book-rating {
        color: green;
    }
</style>