<template>
    <div>
        <div class="item">
            <div class="item-title1">回流鱼如何进行人工审核？</div>
            <div class="item-body">
                收到用户售卖的书时，我们会有人工审核，如果有图书存在盗版、破损、污迹、残缺等严重影响阅读的问题，该书将被拒绝通过，不会上架售卖。拒收图书的种类包括：
            </div>
            <div class="item-title2">盗版</div>
            <div class="item-body">
                回流鱼收到盗版后，知会用户并直接丢弃。
            </div>
            <div class="item-title2">严重破损</div>
            <div class="item-body">
                书籍各处破损、脱胶严重、品相极差。
            </div>
            <div class="item-title2">严重污迹</div>
            <div class="item-body">
                有严重泛黄、发霉、浸泡等问题已无法修复。
            </div>
            <div class="item-title2">残本书籍</div>
            <div class="item-body">
                本来是一套书，但缺少册数不全，成为残本
            </div>
            <div class="item-body">拒收图书参考图：</div>
            <div class="images">
                <div class="image">
                    <img src="/images/0.jpeg" :style="{width:(screenWidth-60)/2+'px'}">
                    <span>盗版</span>
                </div>
                <div class="right-image">
                    <img src="/images/1.JPG" :style="{width:(screenWidth-60)/2+'px'}">
                    <span>笔记杂乱</span>
                </div>
                <div class="image">
                    <img src="/images/2.JPG" :style="{width:(screenWidth-60)/2+'px'}">
                    <span>残缺不全</span>
                </div>
                <div class="right-image">
                    <img src="/images/3.JPG" :style="{width:(screenWidth-60)/2+'px'}">
                    <span>严重发霉</span>
                </div>
                <div class="image">
                    <img src="/images/4.JPG" :style="{width:(screenWidth-60)/2+'px'}">
                    <span>严重破损</span>
                </div>
                <div class="right-image">
                    <img src="/images/5.JPG" :style="{width:(screenWidth-60)/2+'px'}">
                    <span>严重脱胶</span>
                </div>
                <div class="image">
                    <img src="/images/6.JPG" :style="{width:(screenWidth-60)/2+'px'}">
                    <span>严重污迹</span>
                </div>
            </div>
        </div>
    </div>
</template>
<style>
    body{
        background-color:#fefefe;
    }
    .item {
        padding: 20px;
    }
    .item-title1 {
        font-size: 18px;
    }
    .item-title2 {
        font-size: 16px;
    }
    .item-body {
        font-size: 14px;
        font-weight: 300;
        padding-bottom: 20px;
    }
    .images {
        display: flex;
        flex-direction: row;
        flex-wrap: wrap;
    }
    .image{
        display:flex;
        flex-direction: column;
        padding-bottom: 20px;
        text-align: center;
    }
    .right-image {
        display:flex;
        flex-direction: column;
        padding-bottom: 20px;
        text-align: center;
        margin-left: 20px;
    }
</style>
<script>
    export default{
        data(){
            return{
                screenWidth: 0
            }
        },
        mounted: function(){
            this.screenWidth = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
        }
    }
</script>
