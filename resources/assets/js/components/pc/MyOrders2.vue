<template>
    <div>
        <loading :loading="loading"></loading>
        <div class="sale-order-top" v-for="order in orders" :key="order.id">
            <div class="sale-order-status">
                <div class="sale-order-status-desc" v-if="order.type===2">买书订单：{{getSaleOrderStatus(order)}}</div>
                <div class="sale-order-status-desc" v-if="order.type===1">卖书订单：{{getRecoverOrderStatus(order)}}</div>
                <div class="sale-order-status-info">
                    <div class="sale-order-created-at">{{order.no}}</div>
                    <div class="sale-order-created-at">{{ createdAt(order) }}</div>
                </div>
            </div>
            <div class="sale-order-operation">
                <div class="sale-order-summary">
                    <div>共{{ order.items.length }}本书</div>
                    <div>￥{{ order.total_amount }}</div>
                </div>
                <router-link :to="`/pc/sale_order/${order.no}`" class="sale-order-detail" v-if="order.type===2">订单详情</router-link>
                <router-link :to="`/pc/recover_order/${order.no}`" class="sale-order-detail" v-if="order.type===1">订单详情</router-link>
            </div>
        </div>
    </div>
</template>
<style scoped>
    .sale-order-top {
        display: flex;
        flex-direction: column;
        background-color: white;
        border-bottom: 0.5px solid #9b9b9b;
    }
    .sale-order-status {
        display: flex;
        flex-direction: column;
        margin: 0 20px;
        padding: 20px 0 10px 0;
        border-bottom: 0.5px solid #f0f0f0;
    }
    .sale-order-status-desc {
        font-size: 16px;
        font-weight: 600;
        color: #3D404A;
    }
    .sale-order-status-info {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        margin-top: 5px;
    }
    .sale-order-created-at {
        font-size: 13px;
        color: #3D404A;
    }
    .sale-order-operation {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        margin: 10px 20px 15px 20px;
        align-items: center;
    }
    .sale-order-summary {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        font-size: 13px;
        color: #3D404A;
    }
    .sale-order-detail {
        font-size: 15px;
        padding: 5px 15px;
        color: white;
        background-color: #3D404A;
        border: 0.5px solid #3D404A;
        border-radius: 4px;
    }
</style>
<script>
    import Loading from './Loading'
    import { mapState, mapGetters, mapActions } from 'vuex'
    export default{
        data(){
            return{
                loading: false
            }
        },
        computed: {
            ...mapState({
                orders: state => state.my.orders
            })
        },
        created: function() {
            this.loading=true;
            this.wxApi.wxConfig('','');
            this.$store.dispatch('my/getMyOrders').then(res => {
                this.loading = false;
            });
        },
        mounted: function() {
        },
        methods: {
            createdAt: function(order) {
                return dayjs(order.created_at).format("YYYY-MM-DD HH:mm");
            },
            getRecoverOrderStatus: function(o) {
                var desc = '';
                if (o.closed === true) {
                    desc =  '已关闭'
                } else {
                    switch(o.recover_status) {
                        case -1:
                            desc = '已取消';
                            break;
                        case 10:
                            desc = '已下单';
                            break;
                        case 20:
                            desc = '回流鱼线上审核';
                            break;
                        case 30:
                            desc = '安排快递上门';
                            break;
                        case 40:
                            desc = '快递已取书';
                            break;
                        case 50:
                            desc = '回流鱼收货';
                            break;
                        case 60:
                            desc = '回流鱼打款';
                            break;
                        case 70:
                            desc = '已完成';
                            break;
                        default:
                            desc = '--::--';
                            break;
                    }
                }
                return desc;
            },
            getSaleOrderStatus: function(o) {
                var desc = '';
                if (o.closed === true) {
                    desc = '已关闭';
                } else {
                    switch(o.sale_status) {
                        case -1:
                            desc = '已取消';
                            break;
                        case 10:
                            desc = '待支付';
                            break;
                        case 20:
                            desc = '已付款';
                            break;
                        case 30:
                            desc = '已出库';
                            break;
                        case 35:
                            desc = '已揽件';
                            break;
                        case 40:
                            desc = '已发货';
                            break;
                        case 70:
                            desc = '已签收';
                            break;
                        default:
                            desc = '--::--';
                            break;
                    }
                }
                return desc;
            },
        },
        components: {
            Loading
        }
    }
</script>
