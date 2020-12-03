<template>
    <div>
        <div class="zto-top">
            <van-uploader :after-read="onRead" name="file" accept="application/msexcel" max-size="500000" :oversize="overSize">
                <van-icon name="description" size="48px"/><br>
                点击上传中通导出的Excel
            </van-uploader>
        </div>
        <div class="zto-bottom" v-html="msg">
        </div>
    </div>
</template>

<script>
    import { Toast } from 'vant';
    export default {
        name: "Zto.vue",
        data() {
            return {
                msg: ""
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

                var instance = axios.create({
                    withCredentials: true
                });
                instance.post('/wx-api/upload_zto',formData).then(res=>{
                    toast.clear();
                    this.msg = res.data.msg;
                })
            },
            overSize: function() {
                this.$toast('图片太大');
            }
        }
    }
</script>

<style scoped>
    .zto-top {
        text-align: center;
        padding: 30px 100px;
    }
    .zto-bottom {

    }
</style>