<template>
    <div class="book-version">
        <van-row>
            <van-col span="10" offset="4" v-if="bookVersion">
                <van-uploader :after-read="onRead" name="image" accept="image/png, image/jpeg" max-size="500000" :oversize="overSize">
                    <img :src="bookVersion.cover" style="width: 80px;max-height: 110px" alt="">
                </van-uploader>
            </van-col>
            <van-col span="10" offset="4" v-else>
                <van-uploader :after-read="onRead" name="image" accept="image/png, image/jpeg" max-size="500000" :oversize="overSize">
                    <img :src="bookVersion.book.cover_replace" style="width: 80px;max-height: 110px" alt="">
                </van-uploader>
            </van-col>
        </van-row>
        <van-cell-group>
            <van-field v-model="bookVersion.title" placeholder="请输入新版本说明" label="新版本说明"/>
            <van-field v-model="bookVersion.name" placeholder="请输入书名" label="书名"/>
            <van-field v-model="bookVersion.press" placeholder="请输入出版社" label="出版社"/>
            <van-field v-model="bookVersion.publish_year" placeholder="请输入出版时间" label="出版时间"/>
            <van-field v-model="bookVersion.price" placeholder="请输入价格" type="number" label="价格(￥)"/>
        </van-cell-group>

        <van-button size="large" type="danger" @click="onSave">保存</van-button>
    </div>
</template>

<script>
    import { Toast } from 'vant';
    export default {
        name: "BookVersionEdit.vue",
        data() {
            return {
                bookId: 0,
                versionId: 0,
                bookVersion: {}
            }
        },
        created() {
            this.bookId = this.$route.params.bookId;
            this.versionId = this.$route.params.versionId;
            this.wxApi.wxConfig('','');
            if (this.versionId>0) {
                axios.get('/wx-api/get_book_version?id='+this.versionId).then(res => {
                    if (res.data.code && res.data.code===500) {
                        this.$toast(res.data.msg);
                    } else {
                        this.bookVersion = res.data;
                    }
                });
            }else{
                axios.get('/wx-api/get_book_by_id?id='+this.bookId).then(res => {
                    if (res.data.code && res.data.code===500) {
                        this.$toast(res.data.msg);
                    } else {
                        this.bookVersion = {
                            book_id: res.data.id,
                            cover: res.data.cover_replace,
                            name: res.data.name,
                            press: res.data.press,
                            publish_year: res.data.publish_year,
                            price: ''
                        }
                    }
                });
            }
        },
        methods: {
            onRead: function(file, detail) {
                var toast = Toast.loading({
                    mask: true,
                    message: '上传中....'
                });
                var formData = new FormData();
                formData.append('file', file.file);
                formData.append('version',this.versionId);

                var instance = axios.create({
                    withCredentials: true
                });
                instance.post('/wx-api/upload_version_cover',formData).then(res=>{
                    toast.clear();
                    if (res.data.code && res.data.code===500) {
                        this.$toast(res.data.msg);
                    } else {
                        this.bookVersion.cover = res.data;
                    }
                })
            },
            overSize: function() {
                this.$toast('图片太大');
            },
            onSave: function() {
                console.log(this.bookVersion);
                if (!this.bookVersion.price) {
                    this.$toast('价格必填');
                }else if(!this.bookVersion.title){
                    this.$toast('新版本说明必填');
                }else{
                    axios.post('/wx-api/create_book_version', this.bookVersion).then(res => {
                        if (res.data.code && res.data.code===500) {
                            this.$toast(res.data.msg);
                        } else {
                            this.$router.back();
                        }
                    });
                }
            }
        }
    }
</script>

<style scoped>

</style>