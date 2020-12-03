<template>
    <div class="book-version-list">
        <van-list
                v-model="loading"
                :finished="finished"
                finished-text="没有更多了"
                @load="onLoad"
                style="margin-bottom: 60px"
                >
            <book-version-item :book-version="bookVersion" :screen-width="screenWidth" v-for="bookVersion in bookVersions" :key="bookVersion.id"></book-version-item>
            <div slot="loading">
                <loading :loading="loading"></loading>
            </div>
        </van-list>
        <van-button size="large" type="danger" style="position: fixed;left: 0;bottom: 0;width: 100%" @click="newVersion">新增版本</van-button>
    </div>
</template>

<script>
    import BookVersionItem from './BookVersionItem'
    import Loading from './Loading'
    export default {
        name: "BookVersionList.vue",
        data() {
            return {
                loading: false,
                finished: false,
                screenWidth: 0,
                bookId: 0,
                bookVersions: []
            }
        },
        created: function() {
            this.bookId = this.$route.params.bookId;
            this.wxApi.wxConfig('','');
        },
        mounted: function () {
            this.screenWidth = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
            this.screenHeight = window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;
        },
        methods: {
            onLoad: function() {
                this.loading = true;
                axios.get('/wx-api/get_book_versions/' + this.bookId).then(res => {
                    this.bookVersions = res.data;
                    this.loading = false;
                    this.finished = true;
                });
            },
            newVersion: function() {
                this.$router.push('/book/'+this.bookId+'/version/0/edit');
            }
        },
        components: {
            BookVersionItem,
            Loading
        }
    }
</script>

<style scoped>

</style>