<template>
    <div class="jzm">
        <jz :jz="jz" v-for="jz in jzs" :key="jz.id" :screen-width="screenWidth"></jz>
        <bottom-bar index="1"></bottom-bar>
    </div>
</template>

<script>
    import {mapGetters, mapState, mapActions} from 'vuex'
    import Jz from './Jz'
    import BottomBar from "./BottomBar"
    export default {
        data() {
            return {
                screenWidth: 0,
                fetching: false
            }
        },
        computed: {
            ...mapState({
                jzs: state => state.jzs.jzs,
                currentPage: state => state.jzs.currentPage,
                nextPageUrl: state => state.jzs.nextPageUrl,
            }),
        },
        created: function() {
            this.screenWidth = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
            this.$store.dispatch('jzs/getJzs');
            window.addEventListener('scroll', this.handleScroll);
            this.wxApi.wxConfig('','');
        },
        mounted: function () {
            this.screenWidth = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
        },
        destroyed: function() {
            window.removeEventListener('scroll', this.handleScroll);
        },
        methods: {
            loadMore: function() {
                console.log('load more');
                if (this.nextPageUrl && !this.fetching) {
                    this.fetching = true;
                    var nextPage = Number(this.currentPage) + 1;
                    axios.get('/wx-api/get_jzs?page=' + nextPage).then(res => {
                        // this.books = this.books.concat(res.data.data);
                        // this.currentPage = res.data.current_page;
                        // this.nextPageUrl = res.data.next_page_url;
                        this.$store.commit('jzs/addJzs', res.data);
                        this.fetching = false;
                    });
                }
            },
            handleScroll: function() {
                //scrollTop是浏览器滚动条的top位置
                var scrollTop = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop
                //下面这句主要是获取网页的总高度，主要是考虑兼容性所以把Ie支持的documentElement也写了，这个方法至少支持IE8
                var htmlHeight = document.documentElement.scrollHeight;
                //clientHeight是网页在浏览器中的可视高度，
                var clientHeight = document.documentElement.clientHeight;
                this.y = scrollTop;
                //通过判断滚动条的top位置与可视网页之和与整个网页的高度是否相等来决定是否加载内容；
                if (scrollTop + clientHeight === htmlHeight) {
                    this.loadMore();
                }
            },
        },
        components: {
            Jz,
            BottomBar
        }
    }
</script>

<style scoped>
    .jzm {
        display: flex;
        flex-direction: column;
        padding-bottom: 60px;
    }
</style>