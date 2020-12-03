<template>
    <div>
        <div class="jz-item" @click="showDialog">
            <div class="jz-image" :style="jzStyle">
                <img :src="jzPicture" alt="" :style="jzImageStyle" style="border-radius: 6px;">
            </div>
            <div class="jz-content" :style="jzStyle">
                <div class="jz-body" v-html="jz.body"></div>
                <div class="jz-author-book">{{jzAuthorBook}}</div>
            </div>
        </div>
        <van-dialog
                v-model="dialogVisible"
                title="长按图片保存"
                show-cancel-button
        >
            <img :src="jzImage" :style="dialogImage" alt="">
        </van-dialog>
    </div>
</template>

<script>
    export default {
        data() {
            return {
                dialogVisible: false,
                jzImage:''
            }
        },
        props: ['jz', 'screenWidth'],
        computed: {
            jzPicture: function () {
                if (this.jz.picture) {
                    return this.jz.picture.image;
                }
                return '/images/jz-default-image.jpg';
            },
            jzStyle: function() {
                return {
                    width: this.screenWidth - 20 + 'px',
                    height: (this.screenWidth - 20) / 2 + 'px'
                }
            },
            jzImageStyle: function () {
                return {
                    width: this.screenWidth - 20 + 'px',
                    position: 'absolute',
                    clip: 'rect(0,'+(this.screenWidth-20)+'px,'+(this.screenWidth-20)/2+'px,0)'
                }
            },
            dialogImage: function() {
                return {
                    width: this.screenWidth + 'px',
                }
            },
            jzAuthorBook: function () {
                var result = '';
                if (this.jz.author) {
                    result += this.jz.author;
                }
                if (this.jz.book) {
                    result += '-《' + this.jz.book_name + "》";
                }
                return result;
            }
        },
        methods: {
            showDialog: function(){
                this.dialogVisible = true;
                axios.get('/wx-api/get_jz_image/'+this.jz.id).then(res=>{
                    this.jzImage = res.data;
                });
            }
        }
    }
</script>
<style>
    .el-dialog--center .el-dialog__body{
        padding: 0;
    }
    .el-dialog__body {
        padding: 0;
    }
</style>
<style scoped>
    .jz-item {
        position: relative;
    }

    .jz-image {
        position: absolute;
        top: 0;
        left: 10px;
        z-index: -100;
    }

    .jz-content {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        margin: 10px 10px 0 10px;
    }

    .jz-body {
        font-size: 16px;
        font-weight: 600;
        color: white;
        padding: 0 15px;
        text-overflow:ellipsis;
        white-space:nowrap;
        overflow:hidden;
        width: 90%;
    }

    .jz-author-book {
        font-size: 15px;
        font-weight: 600;
        color: white;
        margin-top: 5px;
    }
</style>