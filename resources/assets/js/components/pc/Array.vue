<template>
    <div style="padding-bottom: 60px;">
        <div class="tag-group" v-for="(group,index) in groups" :style="{color:group.color}">
            <div class="tag-title">
                {{group.title}}
            </div>
            <div class="tags-content">
                <div class="tag" v-for="(tag,i) in group.tags" @click="tagClick(group,index,tag,i)">
                    <div class="tag-name">{{tag.name}}</div>
                    <div class="tag-status">
                        <div class="tag-selected" v-if="tag.selected">
                            <input type="checkbox" v-model="tag.selected"/>
                            <van-icon name="certificate" />
                        </div>
                        <div v-else>
                            <input type="checkbox" v-model="tag.selected"/>
                            <van-icon name="add-o" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<style>
    body{
        background-color: white;
    }
    input {
        position: absolute;
        clip: rect(0, 0, 0, 0);
    }
    .tag-group {
        padding: 20px 0;
        width: 100%;
    }
    .tag-title {
        font-size: 20px;
        font-weight: 200;
        height: 40px;
        line-height: 40px;
        margin: 0 15px;
        color: black;
        border-bottom: 0.5px solid #eee;
    }
    .tags-content {
        display: flex;
        flex-direction: row;
        flex-wrap: wrap;
    }
    .tag {
        width: 50%;
        position: relative;
        height: 40px;
        line-height: 40px;
        font-size: 16px;
        font-weight: 200;
    }
    .tag-name {
        position: absolute;
        top: 5px;
        left: 15px;
    }
    .tag-status {
        position: absolute;
        top: 10px;
        right: 25px;
        line-height: 30px;
    }
    .tag-selected {
        background-color:#F2ECE3;
        border-radius:4px;
        line-height: 30px;
        padding: 0 8px;
        margin-right: -8px;
    }
</style>
<script>
    export default{
        data(){
            return{
                msg:'hello vue',
                groups: [
                    {
                        title:"回流鱼推荐",
                        color:"#D5B991",
                        selectedColor:"#F2ECE3",
                        tags: [ {name:"豆瓣8.5+",selected:false},
                                {name:"越贵越省钱",selected:false},
                                {name:"只在回流鱼有",selected:false},
                                {name:"新出版",selected:false},
                                {name:"特价区",selected:false},
                                {name:"大作家",selected:false},
                                {name:"手慢无",selected:false},
                                {name:"有年头的书",selected:false}
                                ]
                    },
                    {
                        title:"孩子的书架",
                        color:"#E19DA4",
                        selectedColor:"#F2ECE3",
                        tags: [{name:"0-3岁童书",selected:false},
                                {name:"3-6岁童书",selected:false},
                                {name:"7-10岁童书",selected:false},
                                {name:"10岁+童书",selected:false}]
                    },
                    {
                        title:"艺术的边界",
                        color:"#AC84AE",
                        selectedColor:"#F2ECE3",
                        tags: [{name:"电影",selected:false},
                                {name:"摄影",selected:false},
                                {name:"艺术",selected:false},
                                {name:"设计",selected:false},
                                {name:"绘本",selected:false},
                                {name:"音乐",selected:false},
                                {name:"绘画",selected:false},
                                {name:"戏剧",selected:false},
                                {name:"建筑",selected:false}]
                    },
                    {
                        title:"文字的张力",
                        color:"#B9958C",
                        selectedColor:"#F2ECE3",
                        tags: [{name:"中国文学",selected:false},
                                {name:"古籍",selected:false},
                                {name:"外国文学",selected:false},
                                {name:"阅读与写作",selected:false},
                                {name:"诗歌",selected:false},
                                {name:"散文",selected:false},
                                {name:"随笔",selected:false},
                                {name:"纪实",selected:false},
                                {name:"回忆录",selected:false},
                                {name:"人物传记",selected:false},
                                {name:"推理",selected:false},
                                {name:"悬疑",selected:false},
                                {name:"幻想力",selected:false},
                                {name:"科幻",selected:false}]
                    },
                    {
                        title:"认知世界的维度",
                        color:"#8990BE",
                        selectedColor:"#F2ECE3",
                        tags: [{name:"政治",selected:false},
                                {name:"军事与战争",selected:false},
                                {name:"经济",selected:false},
                                {name:"历史",selected:false},
                                {name:"社会学",selected:false},
                                {name:"人类学",selected:false},
                                {name:"哲学",selected:false},
                                {name:"宗教",selected:false}]
                    },
                    {
                        title:"打开新地图",
                        color:"#69A9BA",
                        selectedColor:"#F2ECE3",
                        tags: [{name:"旅行",selected:false},
                                {name:"Lonely Planet",selected:false},
                                {name:"英国",selected:false},
                                {name:"美国",selected:false},
                                {name:"北京",selected:false},
                                {name:"上海",selected:false},
                                {name:"香港",selected:false},
                                {name:"台湾",selected:false}]
                    },
                    {
                        title:"东瀛爱好者",
                        color:"#E7A67C",
                        selectedColor:"#F2ECE3",
                        tags: [{name:"日本文化",selected:false},
                                {name:"日本文学",selected:false},
                                {name:"日系推理",selected:false},
                                {name:"日语学习",selected:false},
                                {name:"漫画",selected:false},
                                {name:"轻小说",selected:false}]
                    },
                    {
                        title:"37度半的生活指南",
                        color:"#9AB89D",
                        selectedColor:"#F2ECE3",
                        tags: [{name:"美食",selected:false},
                                {name:"宠物",selected:false},
                                {name:"吸猫",selected:false},
                                {name:"撸狗",selected:false},
                                {name:"咖啡",selected:false},
                                {name:"杂志",selected:false},
                                {name:"时尚",selected:false},
                                {name:"手工",selected:false},
                                {name:"家居",selected:false},
                                {name:"植物园艺",selected:false},
                                {name:"情感",selected:false},
                                {name:"青春",selected:false}]
                    },
                    {
                        title:"大于等于二人世界",
                        color:"#D9878D",
                        selectedColor:"#F2ECE3",
                        tags: [{name:"婚姻",selected:false},
                                {name:"教育",selected:false},
                                {name:"育儿",selected:false},
                                {name:"童书",selected:false},
                                {name:"健康",selected:false},
                                {name:"医疗",selected:false}]
                    },
                    {
                        title:"我变强了",
                        color:"#E9C46F",
                        selectedColor:"#F2ECE3",
                        tags: [{name:"职业规划",selected:false},
                                {name:"自我管理",selected:false},
                                {name:"时间管理",selected:false},
                                {name:"理财",selected:false},
                                {name:"沟通",selected:false},
                                {name:"英语",selected:false}]
                    },
                    {
                        title:"互联网聊天必备",
                        color:"#93C9C7",
                        selectedColor:"#F2ECE3",
                        tags: [{name:"互联网",selected:false},
                                {name:"创业",selected:false},
                                {name:"产品·运营",selected:false},
                                {name:"算法·编程",selected:false},
                                {name:"交互设计",selected:false},
                                {name:"科技",selected:false},
                                {name:"计算机",selected:false},
                                {name:"投资",selected:false}]
                    },
                    {
                        title:"好好工作",
                        color:"#84A9D9",
                        selectedColor:"#F2ECE3",
                        tags: [{name:"企业管理",selected:false},
                                {name:"法律",selected:false},
                                {name:"财务·会计",selected:false},
                                {name:"新闻传播",selected:false},
                                {name:"广告·文案",selected:false},
                                {name:"市场营销",selected:false}]
                    },
                    {
                        title:"进口书，说实话读这个很高端",
                        color:"#D4B890",
                        selectedColor:"#F2ECE3",
                        tags: [{name:"英文进口",selected:false},
                                {name:"日文进口",selected:false},
                                {name:"港台进口",selected:false},
                                {name:"其他语种",selected:false}]
                    },
                ],
                userTags: ['豆瓣8.5+']
            }
        },
        mounted: function() {
            var _this = this;
            // get user tags
            _(this.groups).forEach(function(group){
                _(group.tags).forEach(function(tag){
                    _(_this.userTags).forEach(function(userTag){
                        if(_.isEqual(userTag, tag.name)) {
                            tag.selected = true;
                        }
                    })
                })
            });
        },
        methods: {
            tagClick(group, index, tag, i) {
                var index = this.userTags.indexOf(tag.name)
                if (index !== -1) {
                    this.userTags.splice(index, 1)
                }else{
                    this.userTags.push(tag.name);
                }
                // 更新UI
                tag.selected = true;
                var _this = this;
                _(this.groups).forEach(function(group){
                    _(group.tags).forEach(function(tag){
                        _(_this.userTags).forEach(function(userTag){
                            if(_.isEqual(userTag, tag.name)) {
                                tag.selected = true;
                            }
                        })
                    })
                });
            }
        }
    }
</script>
