<template>
    <div>
        <div class="ld-item">
            <div class="ld-level">
                <div class="ld-level-logo">
                    <img src="/images/100.png" alt="" :style="levelLogoStyle">
                </div>
                <div class="ld-level-detail">
                    <div class="ld-level-title">品相全新</div>
                    <div class="ld-level-body">我们回收到的全新未拆封的图书，或新书封膜有小破损我们重新塑封的书。</div>
                </div>
            </div>
            <div class="ld-image">
                <img src="/images/level100.JPG" :style="{width:(screenWidth-40)+'px'}">
                <!--<span class="ld-image-desc">品相全新</span>-->
            </div>
            <div class="ld-level">
                <div class="ld-level-logo">
                    <img src="/images/95.png" alt="" :style="levelLogoStyle">
                </div>
                <div class="ld-level-detail">
                    <div class="ld-level-title">品相上好</div>
                    <div class="ld-level-body">八九成新，使用痕迹很轻或不明显。</div>
                </div>
            </div>
            <div class="ld-image">
                <img src="/images/level80.JPG" :style="{width:(screenWidth-40)+'px'}">
                <!--<span class="ld-image-desc">品相上好</span>-->
            </div>
            <div class="ld-level">
                <div class="ld-level-logo">
                    <img src="/images/85.png" alt="" :style="levelLogoStyle">
                </div>
                <div class="ld-level-detail">
                    <div class="ld-level-title">品相中等</div>
                    <div class="ld-level-body">
                        稍微有折痕、标注、磨损等小问题，可以正常阅读。
                        <p>包含:有笔记涂划，封套丢失，轻微霉点、污渍、破损、磨损、变形、折痕、泛黄等之类小问题的书籍。</p>
                    </div>
                </div>
            </div>
            <div class="ld-item-body">图片示例：</div>
            <div class="ld-image">
                <img src="/images/封套丢失.JPG" :style="{width:(screenWidth-40)+'px'}">
                <span class="ld-image-desc">封套丢失</span>
            </div>
            <div class="ld-images">
                <div class="ld-image">
                    <img src="/images/有旧标签.JPG" :style="{width:(screenWidth-60)/2+'px'}">
                    <span class="ld-image-desc">有旧标签</span>
                </div>
                <div class="ld-right-image">
                    <img src="/images/轻微笔记.JPG" :style="{width:(screenWidth-60)/2+'px'}">
                    <span class="ld-image-desc">轻微笔记</span>
                </div>
                <div class="ld-image">
                    <img src="/images/轻微破损.JPG" :style="{width:(screenWidth-60)/2+'px'}">
                    <span class="ld-image-desc">轻微破损</span>
                </div>
                <div class="ld-right-image">
                    <img src="/images/轻微霉点.JPG" :style="{width:(screenWidth-60)/2+'px'}">
                    <span class="ld-image-desc">轻微霉点</span>
                </div>
                <div class="ld-image">
                    <img src="/images/轻微污渍.JPG" :style="{width:(screenWidth-60)/2+'px'}">
                    <span class="ld-image-desc">轻微污渍</span>
                </div>
                <div class="ld-right-image">
                    <img src="/images/轻微磨损.JPG" :style="{width:(screenWidth-60)/2+'px'}">
                    <span class="ld-image-desc">轻微磨损</span>
                </div>
                <div class="ld-image">
                    <img src="/images/轻微变形.JPG" :style="{width:(screenWidth-60)/2+'px'}">
                    <span class="ld-image-desc">轻微变形</span>
                </div>
                <div class="ld-right-image">
                    <img src="/images/轻微泛黄.JPG" :style="{width:(screenWidth-60)/2+'px'}">
                    <span class="ld-image-desc">轻微泛黄</span>
                </div>
                <div class="ld-image">
                    <img src="/images/轻微折痕.JPG" :style="{width:(screenWidth-60)/2+'px'}">
                    <span class="ld-image-desc">轻微折痕</span>
                </div>
            </div>
        </div>
    </div>
</template>
<style>
    body{
        background-color:#fefefe;
        margin: 0;
    }
    .ld-item {
        padding: 20px;
    }
    .ld-level {
        display:flex;
        flex-direction: row;
        margin-bottom: 5px;
    }
    .ld-level:not(:first-child) {
        margin-top: 10px;
    }
    .ld-level-logo {
        display: flex;
        margin-left: -15px;
    }
    .ld-level-detail {
        display:flex;
        flex-direction: column;
        padding-top: 8px;
    }
    .ld-level-title {
        font-size: 16px;
    }
    .ld-level-body {
        font-size: 14px;
        font-weight: 200;
    }
    .ld-item-title1 {
        font-size: 18px;
    }
    .ld-item-title2 {
        font-size: 16px;
    }
    .ld-item-body {
        font-size: 14px;
        font-weight: 200;
        padding-bottom: 20px;
    }
    .ld-images {
        display: flex;
        flex-direction: row;
        flex-wrap: wrap;
    }
    .ld-image{
        display:flex;
        flex-direction: column;
        padding-bottom: 20px;
        text-align: center;
    }
    .ld-right-image {
        display:flex;
        flex-direction: column;
        padding-bottom: 20px;
        text-align: center;
        margin-left: 20px;
    }
    .ld-image-desc {
        color: #888888;
    }
</style>
<script>
    export default{
        data(){
            return{
                screenWidth: 0
            }
        },
        computed: {
            levelLogoStyle() {
                return {
                    width:'80px',
                    height: '80px'
                };
            }
        },
        mounted: function(){
            this.screenWidth = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
        }
    }
</script>
