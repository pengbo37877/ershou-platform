<template>
    <div class="x">
        <div class="top-bar">
            <div class="menu-item" :class="{'active': section===0}" @click="section=0">买书</div>
            <div class="menu-item" :class="{'active': section===1}" @click="section=1">卖书</div>
        </div>
        <div>
            <div v-if="section===0">
                <div class="item">
                    <div class="item-title1">买书的快递费由谁承担？</div>
                    <div class="item-body">
                        订单总额满99元，免快递费，不足99元，需付5元快递费。<br><br>
                        极偏远地区 (新疆、西藏)：<br>
                        不包邮。 基础运费 20 元，超过 3 本，每本增加 10 元运费。<br><br>
                        偏远地区 (甘肃、内蒙古、宁夏、青海、海南)：<br>
                        不包邮。 基础运费 15 元，超过 3 本，每本增加 5 元运费。<br>
                    </div>
                </div>
                <div class="item">
                    <div class="item-title1">回流鱼上架图书有哪几种品相？</div>
                    <div class="item-body">图书被分成3个品相：全新、上好、中等。
                        全新品相：未拆封或封膜破损回流鱼重新包装的图书。
                        上好品相：八九成新，略有使用痕迹。
                        中等品相：稍微有折痕、标注、磨损等小问题，可以正常阅读。
                        <div style="background-color: #ff4848;color: white;opacity: 0.8;width: fit-content;">
                            <router-link to="/pc/level_desc" style="color: white">品相详情说明</router-link>
                        </div>
                    </div>
                </div>
                <div class="item">
                    <div class="item-title1">回流鱼和传统二手书有何区别？</div>
                    <div class="item-body">改变大众对传统二手书不卫生的印象，我们会对回收上来的图书进行人工翻新、臭氧消毒、重新包装，再上架售卖。</div>
                </div>
                <div class="item">
                    <div class="item-title1">回流鱼上的图书都是正版吗？</div>
                    <div class="item-body">经我们人工审核为正版图书方能上架售卖，如有漏网之鱼被你买到，可以联系客服，确认属实我们会对相应图书全额退款。</div>
                </div>
                <div class="item">
                    <div class="item-title1">买书订单发货时间？</div>
                    <div class="item-body">下午15:00 前下单，我们可以当天发货。下午15:00 后下单，我们会第二天发货。物流信息会通过回流鱼公众号反馈。</div>
                </div>
                <div class="item">
                    <div class="item-title1">订单可以中途取消吗？</div>
                    <div class="item-body">只要订单图书还没出库，您都可以取消订单，若图书已出库则无法取消。取消订单后，买书的费用将原路退回，视您支付方式而定。</div>
                </div>
                <div class="item">
                    <div class="item-title1">收到书后可以退/换货吗？</div>
                    <div class="item-body">除非是图书品相或质量严重的问题，否则不可以退/换货。如发现书籍寄错、盗版图书、图书不能使用等体验问题，可以联系我们客服退/换货。</div>
                </div>
            </div>
            <div v-if="section===1">
                <div class="item">
                    <div class="item-title1">卖书的价格如何确定？</div>
                    <div class="item-body">扫码卖书时会显示这本书当前的预估收购价，该价格会随着图书的库存量和市场需求量综合变化。</div>
                </div>
                <div class="item">
                    <div class="item-title1">为什么有些书回流鱼不收？</div>
                    <div class="item-body">套装缺册、破损、污渍、泛黄、发霉严重等品相不合格的书我们是不收的。盗版和非法出版物也不收。请留意下方「审核标准」。
                    </div>
                </div>
                <div class="item">
                    <div class="item-title1">回流鱼人工审核标准是什么？</div>
                    <div class="item-body">收到用户售卖的书时，我们会有人工审核，如果有图书存在套装缺册、破损、污渍、泛黄、发霉等严重影响阅读的问题，该书将被拒收，不会上架售卖。
                        <div style="background-color: #ff4848;color: white;opacity: 0.8;width: fit-content;">
                            <router-link to="/pc/review_standard" style="color: white">审核标准</router-link>
                        </div>
                    </div>
                </div>
                <div class="item">
                    <div class="item-title1">如何把闲置图书卖给回流鱼？</div>
                    <div class="item-body">关注微信公众号「回流鱼」，使用菜单栏“扫码卖书”功能，扫描书籍背面ISBN条形码，回流鱼系统会自动反馈回收预估价格，最后提交卖书订单即可。</div>
                </div>
                <div class="item">
                    <div class="item-title1">如何收书及快递运费由谁承担？</div>
                    <div class="item-body">回流鱼系统会根据你的卖书订单信息，预约快递上门取书，运费由回流鱼承担，快递上门时，请选择运费到付。</div>
                </div>
                <div class="item">
                    <div class="item-title1">卖书的费用何时到账？</div>
                    <div class="item-body">我们收到书后，会在1-2个工作日进行人工审核，剔除不合格的图书后，完成结算打款。 款项会打到你的回流鱼账户，书款可以提现（路径：商店-我的-余额）到微信钱包，也可以用来购买回流鱼上的书。</div>
                </div>
                <div class="item">
                    <div class="item-title1">审核未通过的书怎么办？</div>
                    <div class="item-body">寄过来的书，如因盗版、品相不合格（缺册、污渍、泛黄、破损、发霉严重等）等原因被拒收，可自付运费取回，请在收到审核消息后3天内通过公号后台联系我们。不想取回或者超过3天仍未联系回流鱼取回的书，默认授权由回流鱼统一处理掉，我们不会将问题书籍上架售卖。
                    </div>
                </div>
                <div class="item">
                    <div class="item-title1">怎样的订单会被拒付运费拒收？</div>
                    <div class="item-body">如果卖书订单中盗版或品相不合格差（缺册、污渍、泛黄、破损、发霉严重等）的书「数量超过总数一半」，该订单将被拒付运费拒收，如需取回须向顺丰支付运费，如不想取回，书归顺丰自行处理。所以，大家千万别寄送盗版及品相不合格（缺册、污渍、泛黄、破损、发霉严重）以致无法再次售卖的书「数量超过总数一半」。</div>
                </div>
                <div class="item">
                    <div class="item-title1">卖书的订单可以取消吗？</div>
                    <div class="item-body">卖书下单成功后，顺丰会在上门之前联系你，如需取消，请和顺丰说“下错订单，不用上门取件”，该订单则自动作废取消。如果顺丰已把书取走，则无法取消。公号后台回复“取消订单”，可以获取详细说明。</div>
                </div>
                <div class="item">
                    <div class="item-title1">卖书的实际收入和预估收入的区别？</div>
                    <div class="item-body">扫码给出的是假定这本书品相为「上好、全新」时的预估最高价。收到书后，如果书的品相为「上好、全新」，则按预估价执行，如果品相为「中等」，系统则会适当调低价格。盗版及品相不合格的书则会被拒收，还有多寄、少寄、寄错等，这些都可能造成卖书实际收入和预估收入不相符。另外：如果发现利用多个订单卖相同书籍的倾销行为，系统也会调低收购价。</div>
                </div>
            </div>
        </div>
    </div>
</template>
<style scoped>
    .x {
        background-color: white;
        position: relative;
        margin-top: 44px;
    }
    .top-bar {
        position: fixed;
        top: 0;
        right: 0;
        background-color: #fffefe;
        height: 48px;
        line-height: 48px;
        display: flex;
        flex-direction: row;
        justify-content: space-around;
        width: 100%;
        z-index: 1000;
        border-bottom: 0.5px solid #eee;
        padding-left: 0;
        margin: 0;
    }
    .menu-item {
        text-align: center;
        font-size: 17px;
        font-weight: 300;
        color: #555;
    }
    .active {
        color: #ff4848;
        font-weight: 700;
        border-bottom: 2px solid #ff4848;
        opacity: 0.7;
    }
    .item {
        padding: 10px 20px 20px 20px;
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
        color: #222222;
    }
</style>
<script>

    export default{
        data(){
            return{
                section: 1
            }
        },
        created() {
            var a = this.$route.query.index;
            this.wxApi.wxConfig('','');
            if (_.isNumber(a)) {
                this.section = a;
            }
            this.$store.dispatch('user/getUser').then(res => {
                // 如果拿不到用户，就显示一个不可关闭的对话框
                var user = res.data;
                if (user===''||user.length===0) {
                    this.$router.replace('/pc/shop');
                } else if(user.subscribe===0) {
                    this.$router.replace('/pc/shop')
                }
            });
        },
        mounted() {
            this.$nextTick(() => {
                window.scrollTo(0, 1)
                window.scrollTo(0, 0)
            })
        }
    }
</script>
