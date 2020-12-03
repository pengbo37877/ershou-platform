<template>
    <div>
        <div class="item">
            <div class="item-title1">
                庆融资<br>
                回流鱼全场包邮
            </div>
            <div class="item-body">
                即刻起至本周五（9.27日）晚24:00，下单买书满20元（≥20）即可免运费。<br>
                <br>
                活动说明：<br>
                1、新疆、西藏、甘肃、青海、内蒙、宁夏、海南等地不在活动包邮之列，但依然可以领取5元券减免部分运费。<br>
                2、包邮券领取后，可在下单支付页面使用。（单个订单只能使用一张券，多张券不能叠加使用）<br>
                3、此次包邮活动截止为9.27日晚24点，过期则失效，运费回归正常计算。<br>
                4、活动最终解释权归回流鱼所有。<br>
            </div>
        </div>
        <!-- <div class="item">
            <div class="item-title1">
                周年庆<br>
                回流鱼全场包邮
            </div>
            <div class="item-body">
                在8月24日当天，下单买书满20元（≥20）即可免运费。<br>
                <br>
                活动说明：<br>
                1、新疆、西藏等部分偏远地区不在包邮之列，但依然可领取5元券减免部分运费。<br>
                2、包邮券领取后，在下单支付页面可使用。<br>
                3、包邮券仅限8月24日当天使用，过期则失效，运费回归正常计算。<br>
                4、活动最终解释权归回流鱼所有。<br>
            </div>
        </div> -->
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
        },
        mounted() {
            this.$nextTick(() => {
                window.scrollTo(0, 1)
                window.scrollTo(0, 0)
            })
        }
    }
</script>
