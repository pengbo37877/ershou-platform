<template>
    <div>
        <!--订单进度-->
        <div style="height: 20px;width: 100%;padding-top: 9px;" v-if="!useUser">
            <van-progress
                    :pivot-text="progressDesc"
                    color="#f2826a"
                    :percentage="percent"
                    v-if="order"
            />
        </div>
        <div style="padding-top: 10px" v-if="order">
            <van-tabs>
                <van-tab v-for="(item, index) in computedItems">
                    <div slot="title">
                        <img :src="item.book.cover_replace" style="width: 50px;height: 75px;opacity: .5" alt="" v-if="item.hly_code">
                        <img :src="item.book.cover_replace" style="width: 50px;height: 75px;opacity: .2" alt="" v-else-if="item.review_result===0">
                        <img :src="item.book.cover_replace" style="width: 50px;height: 75px" alt="" v-else>
                    </div>
                </van-tab>
            </van-tabs>
        </div>
        <van-cell-group v-if="order" style="margin-top: 55px;" @click="showOrders" >
            <van-cell title="已完成卖书订单" :value="situation.complete_count" />
        </van-cell-group>
        <van-collapse v-show="showPrevOrders" v-model="activeName">
            <van-collapse-item :title="order.no" :name="order.no" v-for="order in situation.orders">
                <van-list>
                    <van-card v-for="item in order.items">
                        <div slot="thumb">
                            <img :src="item.book.cover_replace" alt="" >
                        </div>
                        <div slot="title" class="book-title" >
                            {{item.book.name}}
                        </div>
                        <div slot="desc" class="book-info" >
                            <div class="book-subtitle" v-if="item.book.subtitle">{{item.book.subtitle}}</div>
                            <div class="book-author">{{item.book.author?item.book.author.trimLeft():'暂无'}}</div>
                            <div class="book-rating">豆瓣评分：{{Number(item.book.rating_num)===0?'暂无':item.book.rating_num}}</div>
                        </div>
                    </van-card>
                </van-list>
            </van-collapse-item>
        </van-collapse>
        <van-cell-group v-if="situation.books">
            <van-cell v-for="book in situation.books" :title='book.name' :value="book.count" :key="book"></van-cell>
        </van-cell-group>
        <!--订单信息-->
        <van-panel :title="expressDesc" :desc="orderAddress" :status="orderStatus" v-if="order">
            <div style="padding: 10px 15px;color: #555555">{{orderUser}}</div>
            <div slot="footer">
                <div style="display: flex;flex-direction: row;justify-content: flex-end;">
                    <van-button size="small" type="warning" @click="otherOrder" style="margin-right: 15px;">切换订单</van-button>
                    <van-button size="small" type="danger" @click="evilOrder" v-if="order.is_evil===0" style="margin-right: 15px;">标记恶意</van-button>
                    <van-button size="small" type="default" @click="evilOrder" v-if="order.is_evil===1" style="margin-right: 15px;">取消恶意</van-button>
                    <van-button size="small" type="primary" @click="completeOrder" v-if="!loading && !useUser">审核完毕</van-button>
                    <van-button size="small" type="primary" loading v-if="loading && !useUser">审核完毕</van-button>
                    <van-button size="small" @click="inputOrder" style="margin: 0 15px;">手动输入</van-button>
                    <van-button size="small" type="info" @click="changeOrder">扫顺丰</van-button>
                </div>
            </div>
        </van-panel>
        <van-row v-else>
            <van-col span="12">
                <van-button size="large" @click="inputOrder">手动输入</van-button>
            </van-col>
            <van-col span="12">
                <van-button size="large" type="info" @click="changeOrder">扫顺丰</van-button>
            </van-col>
        </van-row>
        <!--匹配同一用户-->
        <van-cell-group v-if="order">
            <van-switch-cell v-model="useUser" title="匹配同一用户" />
        </van-cell-group>

        <van-cell-group v-if="order && !useUser">
            <van-cell title="更新快递费" value="" :label="shipPrice" is-link @click="inputShipPrice"/>
        </van-cell-group>

        <!--书籍信息-->
        <van-row style="margin-top: 10px;" v-if="order">
            <van-col span="9">
                <div v-if="orderItem">
                    <van-uploader :after-read="onRead" name="image" accept="image/png, image/jpeg" max-size="500000" :oversize="overSize" v-if="orderItem.book.cover_replace">
                        <img :src="orderItem.book.cover_replace" alt="" style="width: 90%;">
                    </van-uploader>
                    <van-uploader :after-read="onRead" name="image" accept="image/png, image/jpeg" max-size="500000" :oversize="overSize" v-else>
                        <div style="padding: 20px">
                            <van-icon name="photograph" />
                        </div>
                    </van-uploader>
                    <br>
                    <van-button size="large" type="danger" @click="showBan" v-if="orderItem.book.can_recover===true">以后不收</van-button>
                    <van-button size="large" type="info" @click="showUnban" v-if="orderItem.book.can_recover===false && orderItem.book.admin_user_id>0">以后收</van-button>
                    <van-button size="large" @click="showIsbn">输入ISBN</van-button>
                    <van-button size="large" type="info" @click="scanIsbn">扫ISBN</van-button>
                </div>
                <div v-else>.</div>
            </van-col>
            <van-col span="15" style="padding: 10px 5px;">
                <van-row v-if="orderItem">
                    <span style="font-weight: bold;">{{orderItem.book.name}}</span><br>
                    <span style="color: darkblue">{{orderItem.book.isbn}}</span><br>
                    <span style="color: #888;">{{orderItem.book.author}}</span><br>
                    <span style="color: #888;">{{orderItem.book.press}}</span><br>
                    <span style="color: green;">{{orderItem.book.rating_num}}/{{orderItem.book.num_raters}}</span><br>
                    <span style="color: #ff7701;">价格:{{orderItem.book.price}}</span><br>
                    <span style="color: #888;font-weight: 600">{{orderItem.book.category}}</span><br>
                    <span>库存{{orderItem.storage_skus_count}} / 想要{{orderItem.remind_count}} / 已售{{orderItem.sold_skus_count}}</span><br>
                    <span style="font-weight: bold;color: #ff7701">{{hlyCode}}</span><br>
                    <van-button size="large" @click="showCode">输入hly</van-button>
                    <van-button size="large" type="danger" @click="scanLylCode">扫hly</van-button>
                </van-row>
                <van-row v-else>
                    <van-button size="large" type="primary" @click="nextItem">下一本</van-button>
                    <van-button size="large" type="danger" @click="addVisible=true">加一本</van-button>
                    <van-button size="large" @click="showIsbn">输入ISBN</van-button>
                    <van-button size="large" type="info" @click="scanIsbn">扫ISBN</van-button>
                </van-row>
            </van-col>
        </van-row>

        <van-row v-if="orderItem">
            <van-button size="large" type="danger" @click="deleteItem" v-if="orderItem.is_add">删除</van-button>
            <van-button size="large" type="warning" @click="showDeny" v-if="orderItem.review_result===1">拒收</van-button>
            <van-button size="large" type="warning" @click="reviewOk" v-if="orderItem.review_result===0">改为收取</van-button>
        </van-row>
        <!--选择版本-->
        <van-row v-if="orderItem">
            <van-button size="large" type="warning" @click="gotoAddVersion">新增版本</van-button>
        </van-row>
        <van-cell-group v-if="versions.length>0">
            <van-cell title="选择版本" value="" :label="versionDesc" is-link @click="chooseVersion"/>
        </van-cell-group>
        <!--选择品相-->
        <van-cell-group v-if="orderItem">
            <van-cell title="选择品相" value="" :label="levelDesc" is-link @click="chooseLevel"/>
        </van-cell-group>
        <!--选择描述-->
        <van-cell-group v-if="orderItem">
            <van-cell title="品相描述" value="" :label="titleDesc" is-link @click="chooseTitle"/>
        </van-cell-group>
        <!--类别描述-->
        <van-cell-group v-if="orderItem">
            <van-cell title="选择类别" value="" :label="groupsDesc" is-link @click="chooseGroups"/>
        </van-cell-group>
        <van-cell-group v-if="orderItem">
            <van-field label="册数" v-model="volumeCount" placeholder="册数" right-icon="points"/>
        </van-cell-group>

        <div style="width: 100%;height: 80px;text-align: center">.</div>

        <van-button size="large" type="primary" @click="inbound" style="position: fixed;bottom: 0;left: 0;" v-if="!loading && orderItem">入库</van-button>
        <van-button loading size="large" type="primary" style="position: fixed;bottom: 0;left: 0;" v-if="loading && orderItem">入库</van-button>

        <van-dialog
                required
                clearable
                v-model="shipPriceVisible"
                title="输入快递费"
                @confirm="updateShipPrice"
                show-cancel-button
        >
            <van-cell-group>
                <van-field v-model="shipPrice" placeholder="请输入快递费" />
            </van-cell-group>
        </van-dialog>

        <van-dialog
                required
                clearable
                v-model="dialogVisible1"
                title="输入ISBN"
                @confirm="ok1"
                show-cancel-button
        >
            <van-cell-group>
                <van-field v-model="isbn" placeholder="请输入ISBN" />
            </van-cell-group>
        </van-dialog>

        <van-dialog
                required
                clearable
                v-model="addVisible"
                title="怎样添加"
                @confirm="addByScan"
                @cancel="addInputVisible=true"
                confirmButtonText="扫码添加"
                cancelButtonText="手动输入"
                show-cancel-button
        >
        </van-dialog>

        <van-dialog
                required
                clearable
                v-model="addInputVisible"
                title="输入ISBN"
                @confirm="addByInputIsbn"
                show-cancel-button
        >
            <van-cell-group>
                <van-field v-model="addIsbn" placeholder="请输入ISBN" />
            </van-cell-group>
        </van-dialog>

        <van-dialog
                required
                clearable
                v-model="dialogVisible2"
                title="输入hly开头的条码"
                @confirm="ok2"
                show-cancel-button
        >
            <van-cell-group>
                <van-field v-model="hlyCode" placeholder="请输入回流鱼编码" />
            </van-cell-group>
        </van-dialog>

        <van-dialog
                required
                clearable
                v-model="inputOrderVisible"
                title="输入顺丰单号"
                @confirm="orderConfirm"
                show-cancel-button
        >
            <van-cell-group>
                <van-field v-model="expressNo" placeholder="请输入顺丰单号" />
            </van-cell-group>
        </van-dialog>

        <van-dialog
                required
                clearable
                v-model="denyDialogVisible"
                title="输入拒收原因"
                @confirm="denyConfirm"
                show-cancel-button
        >
            <div style="padding: 10px 15px;">
                <van-checkbox-group v-model="denyReasons">
                    <van-checkbox name="污渍严重">污渍严重</van-checkbox>
                    <van-checkbox name="泛黄严重">泛黄严重</van-checkbox>
                    <van-checkbox name="霉点严重">霉点严重</van-checkbox>
                    <van-checkbox name="笔记严重">笔记严重</van-checkbox>
                    <van-checkbox name="划线严重">划线严重</van-checkbox>
                    <van-checkbox name="磨损严重">磨损严重</van-checkbox>
                    <van-checkbox name="破损严重">破损严重</van-checkbox>
                    <van-checkbox name="折痕严重">折痕严重</van-checkbox>
                    <van-checkbox name="变形严重">变形严重</van-checkbox>
                    <van-checkbox name="褪色严重">褪色严重</van-checkbox>
                    <van-checkbox name="水渍严重">水渍严重</van-checkbox>
                    <van-checkbox name="脱胶严重">脱胶严重</van-checkbox>
                    <van-checkbox name="套装缺册">套装缺册</van-checkbox>
                    <van-checkbox name="鉴别为盗版">鉴别为盗版</van-checkbox>
                    <van-checkbox name="未收到">未收到</van-checkbox>
                </van-checkbox-group>
            </div>
            <van-cell-group>
                <van-field v-model="denyReason" placeholder="请输入拒收原因" />
            </van-cell-group>
        </van-dialog>

        <van-dialog
                required
                clearable
                v-model="addVersionVisible"
                title="新增版本"
                @confirm="versionConfirm"
                show-cancel-button
        >
            <van-cell-group>
                <van-field
                        v-model="versionPrice"
                        required
                        clearable
                        label="价格"
                        placeholder="请输入该版本RMB价格"
                />
            </van-cell-group>
        </van-dialog>

        <van-dialog
                required
                clearable
                v-model="banDialogVisible"
                title="确认以后都不收了"
                @confirm="banConfirm"
                show-cancel-button
        >
        </van-dialog>

        <van-dialog
                required
                clearable
                v-model="unBanDialogVisible"
                title="确认以后继续收取"
                @confirm="unBanConfirm"
                show-cancel-button
        >
        </van-dialog>

        <van-popup v-model="levelPopup" position="bottom">
            <van-radio-group v-model="level" @change="levelPopup = false">
                <van-row style="padding: 50px 0;text-align: center;">
                    <van-col span="8">
                        <van-radio name="60" @click="level = '60'">中等</van-radio>
                    </van-col>
                    <van-col span="8">
                        <van-radio name="80" @click="level = '80'">上好</van-radio>
                    </van-col>
                    <van-col span="8">
                        <van-radio name="100" @click="level = '100'">全新</van-radio>
                    </van-col>
                </van-row>
            </van-radio-group>
        </van-popup>

        <van-popup v-model="titlePopup" position="bottom">
            <van-checkbox-group v-model="title">
                <van-row>
                    <van-col span="8"
                             style="padding: 10px 0;text-align: center;"
                             v-for="(title, index) in titles"
                             :key="title"
                             :title="`${title}`"
                             @click="toggle(index)">
                        <van-checkbox :name="title" ref="checkboxes" shape="square">{{title}}</van-checkbox>
                    </van-col>
                </van-row>
            </van-checkbox-group>
            <van-button size="large" type="info" @click="titlePopup=false">确定</van-button>
        </van-popup>

        <!--选择类别-->
        <van-popup v-model="groupsPopup" position="bottom">
            <van-checkbox-group v-model="groups">
                <van-row>
                    <van-row v-for="(tagRow, tagIndex) in  tagsGroup">
                        <div style="font-size:22px; color: rgb(255, 119, 1);">{{tagIndex}}</div>
                        <van-col span="8"
                                 style="padding: 10px 0;text-align: center;"
                                 v-for="(tag, index) in tagRow"
                                 :key="tag"
                                 :title="`${tag}`"
                                 @click="tagToggle(tagIndex + '.' + index)"
                        >
                            <van-checkbox :name="tag" ref="tags" shape="square">{{tag}}</van-checkbox>
                        </van-col>
                    </van-row>

                </van-row>
            </van-checkbox-group>
            <van-button size="large" type="info" @click="groupsPopup=false">确定</van-button>
        </van-popup>

        <van-popup v-model="versionsPopup" position="bottom">
            <div class="version" :class="activeStyle(v.id)" v-for="v in versions" @click="pickVersion(v.id)">
                <div>
                    <img :src="v.cover" alt="" class="version-cover">
                </div>
                <div class="version-info">
                    <div class="version-name">{{orderItem.book.name}}</div>
                    <div class="version-press">{{v.press}}</div>
                    <div class="version-price">￥{{v.price}}</div>
                </div>
            </div>
            <van-button size="large" type="normal" @click="cancelVersion">使用默认版本</van-button>
        </van-popup>
    </div>
</template>
<style>
    .van-tabs--line .van-tabs__wrap {
        height: 100px;
    }
</style>
<style scoped>
    .van-checkbox__icon--round .van-icon {border-radius: 0}
    .version {
        margin: 10px;
        padding: 5px;
        display: flex;
        flex-direction: row;
        border: 2px solid #CCCCCC;
        border-radius: 4px;
    }
    .version-active {
        border: 2px solid #ff6767;
        border-radius: 4px;
    }
    .version-cover {
        width: 50px;
        height: 70px;
        margin-right: 10px;
    }
    .version-info {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .version-name {
        font-weight: bold;
    }
    .version-press {
        color: #888888;
    }
    .version-price {
        color: #ff6767;
        font-weight: bold;
    }
</style>

<script>
    import wx from 'weixin-js-sdk';
    import { Toast ,Collapse, CollapseItem} from 'vant'
    export default {
        data() {
            return{
                useUser: false,
                expressNo: '',
                shipPriceVisible: false,
                shipPrice: 0,
                inputOrderVisible: false,
                order: '',
                items: [],
                situation: '',
                loading: false,
                isbn:'',
                addIsbn: '',
                orderItem: '',
                volumeCount: 1,
                addVisible: false,
                addVersionVisible: false,
                addInputVisible: false,
                versionPrice: 0,
                hlyCode:'',
                level:'',
                title: [],
                groups: [],
                dialogVisible1: false,
                dialogVisible2: false,
                version: 0,
                versions: [],
                titlePopup: false,
                titles: [
                    '轻微污渍',
                    '轻微泛黄',
                    '轻微霉点',
                    '轻微笔记',
                    '轻微划线',
                    '轻微磨损',
                    '轻微破损',
                    '轻微折痕',
                    '轻微变形',
                    '轻微褪色',
                    '轻微水渍',
                    '轻微脱胶',
                    '贴有标签',
                    '封套丢失',
                    '盖有印章',
                    '污渍较重',
                    '泛黄较重',
                    '霉点较重',
                    '笔记较重',
                    '划线较重',
                    '磨损较重',
                    '破损较重',
                    '折痕较重',
                    '变形较重',
                    '褪色较重',
                    '水渍较重',
                    '一折收',
                    '+0.5折'
                ],
                groupsPopup: false,
                tags: [],
                tagsGroup: {
                "会捕鱼": [
                    "至少读两遍",
                    "哪儿都有TA",
                    "逢人便推荐",
                    "外文原版",
                ],
                "文学酒": [
                    "中国文学",
                    "古典文学",
                    "外国文学",
                    "日本文学",
                    "青春文学",
                    "诗词世界",
                    "散文·随笔",
                    "纪实文学",
                    "传记文学",
                    "悬疑·推理",
                    "科幻·奇幻",
                ],
                "艺术盐": [
                    "电影·摄影",
                    "艺术·设计",
                    "书法·绘画",
                    "音乐·戏剧",
                    "建筑·居住",
                ],
                "生活家": [
                    "时尚·化妆",
                    "旅游·地理",
                    "美食·健康",
                    "运动·健身",
                    "家居·宠物",
                    "手工·工艺",
                ],
                "知识面": [
                    "读点历史",
                    "懂点政治",
                    "了解经济",
                    "管理学",
                    "军事·战争",
                    "社会·人类学",
                    "哲学·宗教",
                    "科普·涨知识",
                    "国学典籍",
                ],
                "成长树": [
                    "母婴育儿",
                    "绘本故事",
                    "儿童文学",
                ],
                "必杀技": [
                    "心理学",
                    "学会沟通",
                    "技能提升",
                    "职业进阶",
                    "自我管理",
                    "理财知识",
                    "外语学习",
                    "语言·工具",
                    "爱情婚姻",
                ],
                "工作狂": [
                    "财务会计",
                    "新闻传播",
                    "市场营销",
                    "投资管理",
                    "法律法规",
                    "广告文案",
                ],
                "互联网": [
                    "科技·互联网",
                    "产品·运营",
                    "开发·编程",
                    "交互设计",
                ],
                "创业营": [
                    "创业·商业",
                    "科技·未来",
                    "企业家",
                    "管理学"
                ]},
                versionsPopup: false,
                levelPopup: false,
                denyDialogVisible: false,
                denyReasons: [],
                denyReason: '',
                banDialogVisible: false,
                unBanDialogVisible: false,
                activeName: ['1'],
                showPrevOrders: false
            }
        },
        computed: {
            computedItems: function() {
                var reviewed = [];
                var unviewed = [];
                var rejected = [];
                this.items.forEach(item => {
                    if (item.review_result===1 && !_.isEmpty(item.hly_code)) {
                        reviewed.push(item);
                    }else if(item.review_result===0) {
                        rejected.push(item);
                    }else{
                        unviewed.push(item);
                    }
                });
                return unviewed.concat(rejected).concat(rejected);
            },
            percent: function() {
                var review_count = parseInt(this.order.reviewed_items_count)+parseInt(this.order.rejected_items_count);
                return Number(review_count*100/this.order.items_count).toFixed(0)
            },
            progressDesc: function() {
                var review_count = parseInt(this.order.reviewed_items_count)+parseInt(this.order.rejected_items_count);
                var left_count = parseInt(this.order.items_count) - review_count;
                return '还剩'+ left_count + '本';
            },
            orderUser: function() {
                var user = this.order.user;
                var address = this.order.address;
                return user.nickname + ' (' + address.contact_name + ' - ' + address.contact_phone + ')';
            },
            orderAddress: function() {
                var address = this.order.address;
                return address.province+address.city+address.district+address.address;
            },
            orderStatus: function() {
                var status = this.order.recover_status;
                var is_evil = this.order.is_evil;
                var desc = '未知';
                switch (status) {
                    case 70:
                        desc = '已完成';
                        break;
                    case -1:
                        desc = '已取消';
                        break;
                    default:
                        desc = "进行中";
                        break;
                }
                if (is_evil === 1) {
                    desc = desc + ' [恶意]';
                }
                return desc;
            },
            expressDesc: function() {
                if (this.order.express === 'SF') {
                    return "顺丰："+this.order.express_no;
                }
                return this.order.express+this.order.express_no;
            },
            levelDesc: function() {
                if (this.level==='60') {
                    return '中等';
                } else if (this.level === '80') {
                    return '上好';
                } else if (this.level === '100') {
                    return '全新';
                } else {
                    return '请选择';
                }
            },
            titleDesc: function() {
                if (this.title.length>0) {
                    return this.title.join('、');
                }
                return '请选择';
            },
            groupsDesc: function() {
                if (this.groups.length>0) {
                    return this.groups.join('、');
                }
                return '请选择';
            },
            versionDesc: function() {
                var _this = this;
                if (this.version>0) {
                    var desc = '';
                    var v = this.versions.forEach(function(i) {
                        if (i.id === _this.version) {
                            desc = '￥'+i.price;
                        }
                    });
                    return desc;
                } else {
                    return '默认版本';
                }
            }
        },
        mounted: function() {
            this.screenWidth = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
            this.screenHeight = window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;
            // 获取微信配置
            axios.post('/inbound/config').then(response => {
                console.log(response.data);
                wx.config(response.data);
                wx.ready(()=>{
                    console.log("ready");
                });
            });
            // 获取所有tags
            axios.get('/inbound/tags').then(res => {
                var tags = res.data;
                var _this = this;
                tags.forEach(function(tag) {
                    if (tag.name !== '豆瓣8.5+' && tag.name !== '特价市集') {
                        _this.tags.push(tag.name);
                    }
                });
            })
        },
        activated: function() {
            if (this.orderItem) {
                this.getOrderItemByIsbn(this.orderItem.book.isbn);
            }
        },
        methods: {
            showOrders:function () {
                this.showPrevOrders = !this.showPrevOrders;
            },
            onRead: function(file, detail) {
                console.log(file);
                var toast = Toast.loading({
                    mask: true,
                    message: '上传中...'
                });
                // 上传封面
                var formData = new FormData();
                formData.append('file', file.file);
                formData.append('book',this.orderItem.book_id);

                var instance = axios.create({
                    withCredentials: true
                });
                instance.post('/wx-api/upload_cover',formData).then(res=>{
                    toast.clear();
                    if (res.data.code && res.data.code===500) {
                        this.$toast(res.data.msg);
                    } else {
                        this.orderItem.book.cover_replace = res.data;
                        this.getOrderItemByIsbn(this.orderItem.book.isbn);
                    }
                })
            },
            overSize: function(file, detail) {
                this.$toast('图片超过了 500 KB');
            },
            gotoAddVersion: function() {
                this.$router.push('/book/'+this.orderItem.book_id+"/versions");
            },
            activeStyle: function(id) {
                if (this.version === id) {
                    return 'version-active';
                }
            },
            inputOrder: function() {
                this.inputOrderVisible = true;
            },
            changeOrder: function() {
                var _this = this;
                wx.scanQRCode({
                    needResult: 1, // 默认为0，扫描结果由微信处理，1则直接返回扫描结果，
                    scanType: ["qrCode","barCode"], // 可以指定扫二维码还是一维码，默认二者都有
                    success: function (res) {
                        var result = "";
                        if(res.resultStr.indexOf(',')==-1){
                            result = res.resultStr;
                        }else{
                            result = res.resultStr.split(",")[1]; // 当needResult 为 1 时，扫码返回的结果
                        }
                        _this.expressNo = result;
                        _this.getOrderByExpressNo(result);
                    }
                });
            },
            otherOrder: function() {
                axios.get('/inbound/next_order?order='+this.order.id).then(res => {
                    if (res.data.code && res.data.code === 500) {
                        this.$dialog.alert({
                            message: res.data.msg
                        })
                    } else {
                        this.order = res.data;
                        this.shipPrice = res.data.ship_price;
                        this.orderItem = '';
                        this.isbn = '';
                        this.hlyCode = '';
                        this.level = '';
                        this.title = [];
                        this.groups = [];
                        this.version = '';
                        this.versions = [];
                        this.getUserOrderSituation();
                    }
                });
            },
            evilOrder: function() {
                axios.get('/inbound/mark_order_as_evil?order='+this.order.id).then(res => {
                    if (res.data.code && res.data.code === 500) {
                        this.$dialog.alert({
                            message: res.data.msg
                        })
                    } else {
                        this.order = res.data;
                        if (this.order.is_evil === 1) {
                            this.$dialog.alert({
                                message: '已标记为恶意订单'
                            });
                        }else{
                            this.$dialog.alert({
                                message: '已恢复为正常订单'
                            });
                        }
                    }
                });
            },
            completeOrder: function() {
                if (parseInt(this.order.ship_price) === 0) {
                    this.$dialog.alert({
                        message: '先更新快递费快递费'
                    });
                    return;
                }
                this.loading = true;
                axios.get('/inbound/complete_order?order='+this.order.id).then(res => {
                    if (res.data.code && res.data.code === 500) {
                        this.$dialog.alert({
                            message: res.data.msg
                        })
                    } else {
                        this.order = '';
                        this.shipPrice = 0;
                        this.loading = false;
                    }
                });
            },
            orderConfirm: function() {
                this.getOrderByExpressNo(this.expressNo);
            },
            denyConfirm: function() {
                if (_.isEmpty(this.denyReason)&&_.isEmpty(this.denyReasons)) {
                    this.$toast('拒收原因必填');
                    return;
                }
                axios.post('/inbound/deny', {
                    'id': this.orderItem.id,
                    'reason': this.denyReason ? this.denyReason:this.denyReasons.join(','),
                    'volume': this.volumeCount
                }).then(res => {
                    if(res.data.code && res.data.code===500) {
                        this.$dialog.alert({
                            message: res.data.msg
                        })
                    }else{
                        this.orderItem = '';
                        this.isbn = '';
                        this.hlyCode = '';
                        this.level = '';
                        this.title = [];
                        this.groups = [];
                        this.version = '';
                        this.versions = [];
                        this.$dialog.alert({
                            message: '拒收成功'
                        });
                        this.getOrderByExpressNo(this.order.express_no);
                    }
                });
            },
            pickVersion: function(id) {
                this.version = id;
                this.versionsPopup = false;
                console.log('pickVersion id='+id);
            },
            cancelVersion: function() {
                this.version = 0;
                this.versionsPopup = false;
            },
            versionConfirm: function() {
                if (this.versionPrice === 0) {
                    this.$dialog.alert({
                        message: '价格必填'
                    })
                    return;
                }
                axios.get('/inbound/add_version?price='+this.versionPrice+"&book="+this.orderItem.book.id).then(res => {
                    if (res.data.code && res.data.code === 500) {
                        this.$dialog.alert({
                            message: res.data.msg
                        })
                    } else {
                        this.version = res.data.id;
                        this.getOrderItemByIsbn(this.orderItem.book.isbn);
                    }
                })
            },
            banConfirm: function() {
                axios.get('/inbound/ban_book?ban=1&order_item='+this.orderItem.id).then(res => {
                    if(res.data.code && res.data.code === 500) {
                        this.$dialog.alert({
                            message: res.data.msg
                        })
                    }else{
                        this.orderItem = res.data;
                    }
                });
            },
            unBanConfirm: function() {
                axios.get('/inbound/ban_book?ban=0&order_item='+this.orderItem.id).then(res => {
                    if(res.data.code && res.data.code === 500) {
                        this.$dialog.alert({
                            message: res.data.msg
                        })
                    }else{
                        this.orderItem = res.data;
                    }
                });
            },
            changeLevel: function(level) {
                this.level = "'"+level+"'";
                this.levelPopup = false;
                console.log('level='+this.level);
            },
            nextItem: function() {
                if (this.useUser) {
                    axios.get('/inbound/next_item?order=' + this.order.id + '&user=' + this.order.user.id).then(res => {
                        if (res.data.code && res.data.code === 500) {
                            this.$dialog.alert({
                                message: res.data.msg,
                                type: 'warning'
                            });
                        } else {
                            this.orderItem = res.data;
                            this.isbn = this.orderItem.book.isbn;
                            this.volumeCount = this.orderItem.book.volume_count;
                            this.hlyCode = this.orderItem.hly_code;
                            this.level = this.orderItem.level+'';
                            this.title = this.orderItem.title?this.orderItem.title.split(','):[];
                            if (this.orderItem.groups) {
                                var _this = this;
                                var gs = this.orderItem.groups.split(',');
                                gs.forEach(function(g) {
                                    var find = _this.groups.find(r=>r===g);
                                    if (!find) {
                                        _this.groups.push(g);
                                    }
                                })
                            }else {
                                var gs = [];
                                if (this.orderItem.book.group1) {
                                    gs.push(this.orderItem.book.group1)
                                }
                                if (this.orderItem.book.group2) {
                                    gs.push(this.orderItem.book.group2)
                                }
                                if (this.orderItem.book.group3) {
                                    gs.push(this.orderItem.book.group3)
                                }
                                var _this = this;
                                gs.forEach(function(g) {
                                    var find = _this.groups.find(r=>r===g);
                                    if (!find) {
                                        _this.groups.push(g);
                                    }
                                })
                            }
                            this.versions = this.orderItem.book.versions;
                            this.version = 0;
                            this.versionPrice = 0;
                        }
                    });
                }else{
                    axios.get('/inbound/next_item?order=' + this.order.id).then(res => {
                        if (res.data.code && res.data.code === 500) {
                            this.$dialog.alert({
                                message: res.data.msg,
                                type: 'warning'
                            });
                        } else {
                            this.orderItem = res.data;
                            this.isbn = this.orderItem.book.isbn;
                            this.hlyCode = this.orderItem.hly_code;
                            this.volumeCount = this.orderItem.book.volume_count;
                            this.level = this.orderItem.level+'';
                            this.title = this.orderItem.title?this.orderItem.title.split(','):[];
                            if (this.orderItem.groups) {
                                this.groups = this.orderItem.groups.split(',')
                            }else {
                                if (this.orderItem.book.group1) {
                                    this.groups.push(this.orderItem.book.group1)
                                }
                                if (this.orderItem.book.group2) {
                                    this.groups.push(this.orderItem.book.group2)
                                }
                                if (this.orderItem.book.group3) {
                                    this.groups.push(this.orderItem.book.group3)
                                }
                            }
                            this.versions = this.orderItem.book.versions;
                        }
                    });
                }
            },
            addItem: function(isbn) {
                axios.post('/inbound/add_item', {
                    'isbn': isbn,
                    'order': this.order.id
                }).then(res => {
                    if (res.data.code && res.data.code === 500) {
                        this.$dialog.alert({
                            message: res.data.msg
                        })
                    }else{
                        this.orderItem = res.data;
                        this.isbn = this.orderItem.book.isbn;
                        this.volumeCount = this.orderItem.book.volume_count;
                        this.hlyCode = this.orderItem.hly_code;
                        this.level = this.orderItem.level+'';
                        this.title = this.orderItem.title?this.orderItem.title.split(','):[];
                        if (this.orderItem.groups) {
                            var _this = this;
                            var gs = this.orderItem.groups.split(',');
                            gs.forEach(function(g) {
                                var find = _this.groups.find(r=>r===g);
                                if (!find) {
                                    _this.groups.push(g);
                                }
                            })
                        }else {
                            var gs = [];
                            if (this.orderItem.book.group1) {
                                gs.push(this.orderItem.book.group1)
                            }
                            if (this.orderItem.book.group2) {
                                gs.push(this.orderItem.book.group2)
                            }
                            if (this.orderItem.book.group3) {
                                gs.push(this.orderItem.book.group3)
                            }
                            var _this = this;
                            gs.forEach(function(g) {
                                var find = _this.groups.find(r=>r===g);
                                if (!find) {
                                    _this.groups.push(g);
                                }
                            })
                        }
                        this.versions = this.orderItem.book.versions;
                        this.version = 0;
                        this.versionPrice = 0;
                    }
                });
            },
            deleteItem: function() {
                if (!this.orderItem.is_add) {
                    this.$dialog.alert({
                        message: '非运营添加的不可删除'
                    })
                    return;
                }
                axios.get('/inbound/delete_item?order_item='+this.orderItem.id).then(res => {
                    if (res.data.code && res.data.code===500) {
                        this.$dialog.alert({
                            message: res.data.msg
                        })
                    }else{
                        this.orderItem = '';
                        this.isbn = '';
                        this.volumeCount = 1;
                        this.hlyCode = '';
                        this.level = '';
                        this.title = [];
                        this.groups = [];
                        this.version = '';
                        this.versions = [];
                        this.$dialog.alert({
                            message: '已删除'
                        })
                    }
                });
            },
            scanIsbnAddItem: function() {
                this.addVisible = true;
            },
            addByScan: function() {
                var _this = this;
                wx.scanQRCode({
                    needResult: 1, // 默认为0，扫描结果由微信处理，1则直接返回扫描结果，
                    scanType: ["qrCode","barCode"], // 可以指定扫二维码还是一维码，默认二者都有
                    success: function (res) {
                        var result = '';
                        if(res.resultStr.indexOf(',')==-1){
                            result = res.resultStr;
                        }else{
                            result = res.resultStr.split(",")[1]; // 当needResult 为 1 时，扫码返回的结果
                        }
                        if (result.substr(0, 3) === 'hly' || result.length!==13) {
                            _this.$dialog.alert({
                                message: 'ISBN有误，请重新扫'
                            })
                        } else {
                            _this.isbn = result;
                            _this.addItem(result);
                        }
                    }
                });
            },
            addByInputIsbn: function() {
                this.addItem(this.addIsbn);
            },
            scanIsbn: function() {
                var _this = this;
                wx.scanQRCode({
                    needResult: 1, // 默认为0，扫描结果由微信处理，1则直接返回扫描结果，
                    scanType: ["qrCode","barCode"], // 可以指定扫二维码还是一维码，默认二者都有
                    success: function (res) {
                        var result = '';
                        if(res.resultStr.indexOf(',')==-1){
                            result = res.resultStr;
                        }else{
                            result = res.resultStr.split(",")[1]; // 当needResult 为 1 时，扫码返回的结果
                        } // 当needResult 为 1 时，扫码返回的结果
                        if (result.substr(0, 3) === 'hly' || result.length!==13) {
                            _this.$dialog.alert({
                                message: 'ISBN有误，请重新扫'
                            })
                        } else {
                            _this.isbn = result;
                            _this.getOrderItemByIsbn(result);
                        }
                    }
                });
            },
            scanLylCode: function() {
                var _this = this;
                wx.scanQRCode({
                    needResult: 1, // 默认为0，扫描结果由微信处理，1则直接返回扫描结果，
                    scanType: ["qrCode","barCode"], // 可以指定扫二维码还是一维码，默认二者都有
                    success: function (res) {
                        // _this.$toast('扫码结果:'+JSON.stringify(res));
                        var result = "";
                        if(res.resultStr.indexOf(',')==-1){
                            result = res.resultStr;
                        }else {
                            result = res.resultStr.split(",")[1]; // 当needResult 为 1 时，扫码返回的结果
                        }
                        if (result.substr(0, 3) === 'hly' && result.length === 13) {
                            _this.hlyCode = result;
                        }else{
                            _this.$dialog.alert({
                                message: '回流鱼码有误，请重扫'
                            })
                        }
                    }
                });
            },
            getOrderByExpressNo: function(no) {
                axios.get('/inbound/get_order_by_express_no?no='+no).then(res => {
                    if(res.data.code && res.data.code === 500) {
                        this.$dialog.alert({
                            message: res.data.msg
                        })
                    }else{
                        this.order = res.data;
                        this.shipPrice = res.data.ship_price;
                        if (parseInt(this.shipPrice) === 0) {
                            this.shipPriceVisible = true;
                        }
                        this.getUserOrderSituation();
                        this.getOrderItems(this.order.id);
                    }
                });
            },
            getOrderItems: function(id) {
                axios.get('/inbound/all_items?order='+id).then(res=>{
                    this.items = res.data;
                });
            },
            getUserOrderSituation: function() {
                axios.get('/inbound/user_situation?user='+this.order.user.id).then(res => {
                    if(res.data.code && res.data.code === 500) {
                        this.$dialog.alert({
                            message: res.data.msg
                        })
                    }else{
                        this.situation = res.data;
                    }
                });
            },
            getOrderItemByIsbn: function(isbn) {
                if (this.useUser) {
                    axios.get('/inbound/get_book_by_isbn?isbn=' + isbn + '&order=' + this.order.id + '&user=' + this.order.user.id).then(res => {
                        if (res.data.code && res.data.code === 500) {
                            this.$dialog.alert({
                                message: res.data.msg,
                                type: 'warning'
                            });
                        } else {
                            this.isbn = isbn;
                            this.orderItem = res.data;
                            this.hlyCode = this.orderItem.hly_code;
                            this.volumeCount = this.orderItem.book.volume_count;
                            this.level = this.orderItem.level+'';
                            this.title = this.orderItem.title?this.orderItem.title.split(','):[];
                            if (this.orderItem.groups) {
                                var _this = this;
                                var gs = this.orderItem.groups.split(',');
                                gs.forEach(function(g) {
                                    var find = _this.groups.find(r=>r===g);
                                    if (!find) {
                                        _this.groups.push(g);
                                    }
                                })
                            }else {
                                var gs = [];
                                if (this.orderItem.book.group1) {
                                    gs.push(this.orderItem.book.group1)
                                }
                                if (this.orderItem.book.group2) {
                                    gs.push(this.orderItem.book.group2)
                                }
                                if (this.orderItem.book.group3) {
                                    gs.push(this.orderItem.book.group3)
                                }
                                var _this = this;
                                gs.forEach(function(g) {
                                    var find = _this.groups.find(r=>r===g);
                                    if (!find) {
                                        _this.groups.push(g);
                                    }
                                })
                            }
                            this.versions = this.orderItem.book.versions;
                            this.versionPrice = 0;
                        }
                    });
                }else{
                    axios.get('/inbound/get_book_by_isbn?isbn=' + isbn + '&order=' + this.order.id).then(res => {
                        if (res.data.code && res.data.code === 500) {
                            this.$dialog.alert({
                                message: res.data.msg,
                                type: 'warning'
                            });
                        } else {
                            this.isbn = isbn;
                            this.orderItem = res.data;
                            this.hlyCode = this.orderItem.hly_code;
                            this.level = this.orderItem.level+'';
                            this.title = this.orderItem.title?this.orderItem.title.split(','):[];
                            if (this.orderItem.groups) {
                                var _this = this;
                                var gs = this.orderItem.groups.split(',');
                                gs.forEach(function(g) {
                                    var find = _this.groups.find(r=>r===g);
                                    if (!find) {
                                        _this.groups.push(g);
                                    }
                                })
                            }else {
                                var gs = [];
                                if (this.orderItem.book.group1) {
                                    gs.push(this.orderItem.book.group1)
                                }
                                if (this.orderItem.book.group2) {
                                    gs.push(this.orderItem.book.group2)
                                }
                                if (this.orderItem.book.group3) {
                                    gs.push(this.orderItem.book.group3)
                                }
                                var _this = this;
                                gs.forEach(function(g) {
                                    var find = _this.groups.find(r=>r===g);
                                    if (!find) {
                                        _this.groups.push(g);
                                    }
                                })
                            }
                            this.versions = this.orderItem.book.versions;
                            this.versionPrice = 0;
                        }
                    });
                }
            },
            inputShipPrice: function() {
                this.shipPriceVisible = true;
            },
            showDeny: function() {
                this.denyDialogVisible = true;
            },
            chooseLevel: function() {
                console.log('chooseLevel');
                this.levelPopup = true;
            },
            chooseTitle: function() {
                console.log('chooseTitle');
                this.titlePopup = true;
            },
            chooseGroups: function() {
                console.log('chooseGroups');
                this.groupsPopup = true;
            },
            chooseVersion: function() {
                console.log('chooseVersion');
                this.versionsPopup = true;
            },
            showBan: function() {
                this.banDialogVisible = true;
            },
            showUnban: function() {
                this.unBanDialogVisible = true;
            },
            showIsbn: function() {
                this.dialogVisible1 = true;
            },
            showCode: function() {
                this.dialogVisible2 = true;
            },
            ok1: function() {
                this.dialogVisible1 = false;
                this.getOrderItemByIsbn(this.isbn);
            },
            ok2: function() {
                this.dialogVisible2 = false;
            },
            addOk: function() {
                this.addVisible = false;
            },
            updateShipPrice: function() {
                axios.get('/inbound/update_ship_price?order='+this.order.id+"&price="+this.shipPrice).then(res => {
                    if (res.data.code && res.data.code === 500) {
                        this.$dialog.alert({
                            message: res.data.msg
                        })
                    } else {
                        this.order = res.data;
                        this.shipPriceVisible = false;
                    }
                });
            },
            toggle(index) {
                this.$refs.checkboxes[index].toggle();
                console.log('title='+JSON.stringify(this.title));
            },
            tagToggle(index) {
                this.$refs.tags[index].toggle();
                console.log('groups='+JSON.stringify(this.groups));
            },
            reviewOk: function() {
                axios.get('/inbound/review_ok?id='+this.orderItem.id).then(res => {
                    if (res.data.code && res.data.code===500) {
                        this.$dialog.alert({
                            message: res.data.msg
                        })
                    } else {
                        this.orderItem = res.data;
                        this.isbn = this.orderItem.book.isbn;
                        this.volumeCount = this.orderItem.book.volume_count;
                        this.hlyCode = this.orderItem.hly_code;
                        this.level = this.orderItem.level+'';
                        this.title = this.orderItem.title?this.orderItem.title.split(','):[];
                        if (this.orderItem.groups) {
                            var _this = this;
                            var gs = this.orderItem.groups.split(',');
                            gs.forEach(function(g) {
                                var find = _this.groups.find(r=>r===g);
                                if (!find) {
                                    _this.groups.push(g);
                                }
                            })
                        }else {
                            var gs = [];
                            if (this.orderItem.book.group1) {
                                gs.push(this.orderItem.book.group1)
                            }
                            if (this.orderItem.book.group2) {
                                gs.push(this.orderItem.book.group2)
                            }
                            if (this.orderItem.book.group3) {
                                gs.push(this.orderItem.book.group3)
                            }
                            var _this = this;
                            gs.forEach(function(g) {
                                var find = _this.groups.find(r=>r===g);
                                if (!find) {
                                    _this.groups.push(g);
                                }
                            })
                        }
                        this.versions = this.orderItem.book.versions;
                    }
                });
            },
            inbound: function() {
                this.loading = true;
                axios.post('/inbound/order_item', {
                    id: this.orderItem.id,
                    isbn: this.isbn,
                    code: this.hlyCode,
                    level: this.level,
                    title: this.title,
                    groups: this.groups,
                    version: this.version,
                    volume: this.volumeCount
                }).then(res => {
                    this.loading = false;
                    if(res.data.code && res.data.code === 500) {
                        this.$dialog.alert({
                            message: res.data.msg
                        });
                    }else{
                        this.orderItem = '';
                        this.isbn = '';
                        this.hlyCode = '';
                        this.volumeCount = 1;
                        this.level = '';
                        this.title = [];
                        this.groups = [];
                        this.version = '';
                        this.versions = [];
                        this.$dialog.alert({
                            message: '成功'
                        });
                        this.getOrderByExpressNo(this.order.express_no);
                    }
                });
            },
        }
    }
</script>
