<template>
    <div class="order-ship">
        <div class="express">
            <div class="express-desc" v-if="order.express==='ZTO'">中通快递：{{order.express_no}}</div>
            <div class="express-desc" v-if="order.express==='SF'">顺丰快递：{{order.express_no}}</div>
            <div class="express-desc" v-if="order.express==='EMS'">邮政：{{order.express_no}}</div>
            <div class="express-desc">{{ address.province + address.city + address.district + address.address }}</div>
        </div>
        <!--<div class="ship-traces">-->
            <!--<div class="ship-trace" v-for="(trace, index) in traces" v-if="traces.length>0">-->
                <!--<div class="trace-time">-->
                    <!--<div class="trace-day-desc">{{day(trace.AcceptTime)}}</div>-->
                    <!--<div class="trace-time-desc">{{time(trace.AcceptTime)}}</div>-->
                <!--</div>-->
                <!--<i class="fal fa-check-circle flag-active" style="color: #ff4848" v-if="index===0"></i>-->
                <!--<i class="fal fa-genderless flag" style="color: #9b9b9b" v-else></i>-->
                <!--<div class="trace-station">{{trace.AcceptStation}}</div>-->
            <!--</div>-->
            <!--<div class="ship-trace" v-else>-->
                <!--<div class="trace-none">暂无物流信息</div>-->
            <!--</div>-->
        <!--</div>-->
        <div class="ship-traces">
            <van-steps direction="vertical" :active="0" active-color="#f44" v-if="traces.length>0">
                <van-step v-for="(trace, index) in traces">
                    <h4>{{trace.AcceptStation}}</h4>
                    <p>{{trace.AcceptTime}}</p>
                </van-step>
            </van-steps>
            <div class="ship-trace" v-else>
                <div class="trace-none">暂无物流信息</div>
            </div>
        </div>
    </div>
</template>

<style scoped>
    .order-ship {
        background-color: #f0f0f0;
    }
    .express {
        padding: 20px;
        border-bottom: 0.5px solid #ddd;
        font-size: 15px;
        color: #3D404A;
        background-color: white;
    }
    .ship-traces {
        margin-top: 10px;
        border-top: 0.5px solid #ddd;
        display: flex;
        flex-direction: column;
        padding: 15px 20px;
        background-color: white;
    }
    .ship-trace {
        display: flex;
        flex-direction: row;
        padding: 10px 0;
        border-bottom: 0.5px solid #eee;
        position: relative;
    }
    .trace-none {
        text-align: center;
        font-size: 14px;
        font-weight: 300;
        color: #3D404A;
        opacity: 0.5;
    }
    .trace-time {
        display: flex;
        flex-direction: column;
        font-size: 11px;
        width: 40px;
    }
    .flag-active {
        position: absolute;
        top: 10px;
        left: 40px;
    }
    .flag {
        position: absolute;
        top: 10px;
        left: 42px;
    }
    .trace-day-desc {
        white-space: nowrap;
    }
    .trace-time-desc {
        white-space: nowrap;
    }
    .trace-station {
        font-size: 13px;
        font-weight: 300;
        color: #3D404A;
        margin-left: 30px;
    }
</style>

<script>
    import { mapState, mapGetters, mapActions} from 'vuex'
    export default {
        data() {
            return {
                traces: []
            }
        },
        computed: {
            ...mapState({
                order: state => state.order.order
            }),
            ...mapGetters('order', {
                address: 'address'
            })
        },
        mounted: function() {
            console.log(this.order)
            if (typeof(this.order.ship_data) === 'string') {
                this.traces = JSON.parse(this.order.ship_data).Traces.reverse();
            }else{
                this.traces = this.order.ship_data.Traces.reverse();
            }
        },
        methods: {
            day: function(time) {
                return dayjs(time).format("MM-DD");
            },
            time: function (time) {
                return dayjs(time).format("HH:mm");
            }
        }
    }
</script>