<template>
    <div style="background-color: #f0f0f0;">
        <loading :loading="loading"></loading>
        <div class="recover-info" v-if="order.recover_status<=30 && order.recover_status!=-1">
            <span>已安排顺丰上门取货，请提前用纸箱装好！书和订单要匹配。</span>
            <div v-if="items && items.length>=30">
                <span>请提醒顺丰小哥发 <span style="font-weight: 700;color: red">重货包裹</span></span>
            </div>
        </div>
        <div class="recover-order-top">
            <div class="recover-order-status">
                <div class="recover-order-status-desc">{{orderRecoverStatusDesc}}</div>
                <div class="recover-order-created-at">{{ createdAt }}</div>
            </div>
            <div class="recover-order-operation">
                <div class="recover-order-no">{{ order.no }}</div>
                <router-link :to="`/pc/recover_order_ship/${order.no}`" class="recover-order-track">状态跟踪</router-link>
            </div>
        </div>


        <div class="recover-order-books-title" v-if="items">
            <div class="recover-order-books-count">共{{ items.length }}本</div>
        </div>

        <div class="recover-order-books">
            <recover-order-book :order="order" :item="item" :screen-width="screenWidth" v-for="item in items" :key="item.id"></recover-order-book>
        </div>
        <div class="recover-order-summary">
            <div class="recover-order-summary-i" v-if="order.recover_status===70">
                <div class="recover-order-summary-left">审核通过书价</div>
                <div class="recover-order-summary-right">￥{{ recoverOrderPrice }}</div>
            </div>
            <div class="recover-order-summary-i" v-if="recoverOrderRejectPrice>0">
                <div class="recover-order-summary-left">审核失败书价</div>
                <div class="recover-order-summary-right">￥{{ recoverOrderRejectPrice }}</div>
            </div>
            <div class="recover-order-summary-i" v-if="order.coupon">
                <div class="recover-order-summary-left" style="color: #ff4848">{{order.coupon.name}}</div>
                <div class="recover-order-summary-right" style="color: #ff4848;">￥{{order.coupon.value}}</div>
            </div>
            <div class="recover-order-summary-i">
                <div class="recover-order-summary-left">运费</div>
                <div class="recover-order-summary-right">回流鱼包邮</div>
            </div>
        </div>
        <div class="recover-order-total-price">
            预计收入：<span class="recover-order-price-text">￥{{ order.total_amount }}</span>
        </div>
        <div class="recover-order-total-price" v-if="order.paid_at">
            实付款：<span class="recover-order-price-text">￥{{ recoverOrderPrice }}</span>
        </div>

        <div class="recover-order-address">
            <div class="recover-order-contact" v-if="address">
                <div class="recover-order-contact-name">{{address.contact_name}}</div>
                <div class="recover-order-contact-phone">{{address.contact_phone}}</div>
            </div>
            <div class="recover-order-address-detail" v-if="address">
                {{ address.province + address.city + address.district + address.address }}
            </div>
            <div class="recover-order-address-update">
                <div class="recover-order-time">上门时间：{{ order.recover_time }}</div>
                <div class="recover-order-address-update-btn" @click="changeAddress">更改地址</div>
            </div>
        </div>

        <div class="recover-order-bottom-bar" :style="{width: screenWidth+'px'}">
            <div class="recover-order-contact-us" @click="contactUs">联系客服</div>
            <div class="recover-order-cancel" @click="qxOrder" v-show="order.recover_status==10 ||order.recover_status==20 ||order.recover_status==30">取消订单</div>
        </div>

        <van-dialog
                v-model="dialogVisible"
                title="确定取消订单吗？"
                show-cancel-button
                @confirm="cancel"
                @cancel="dialogVisible=false">
        </van-dialog>
    </div>
</template>
<style>
    .el-message-box {
        width: 90%;
    }
    .el-dialog--center .el-dialog__body {
        padding: 0 20px 15px 20px;
    }
</style>
<style scoped>
    body {
        background-color: white;
        margin: 0;
    }
    .coupon {
        padding: 20px 15px 10px 15px;
    }
    .recover-info {
        background-color: #ff9999;
        color: #1a2226;
        font-weight: 300;
        font-size: 14px;
        padding: 5px 0;
        text-align: center;
    }
    .recover-order-top {
        display: flex;
        flex-direction: column;
        background-color: white;
        border-bottom: 0.5px solid #ddd;
    }
    .recover-order-status {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        margin: 0 20px;
        padding: 20px 0;
        border-bottom: 0.5px solid #eee;
    }
    .recover-order-status-desc {
        font-size: 16px;
        font-weight: 600;
        color: #3D404A;
    }
    .recover-order-created-at {
        font-size: 13px;
        color: #3D404A;
    }
    .recover-order-operation {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        margin: 10px 20px;
        align-items: center;
    }
    .recover-order-no {
        font-size: 13px;
        color: #3D404A;
    }
    .recover-order-track {
        font-size: 15px;
        padding: 5px 15px;
        color: #3D404A;
        background-color: white;
        border: 0.5px solid #3D404A;
        border-radius: 4px;
    }
    .recover-order-dialog-items {
        display:flex;
        flex-direction: row;
        margin-top: 30px;
        justify-content: space-around;
        align-items: center;
    }
    .recover-order-ok-btn {
        padding: 0 30px;
        background-color: white;
        color: #9b9b9b;
        font-size: 14px;
        height: 40px;
        line-height: 40px;
        border-radius: 4px;
        border:0.5px solid #ddd;
        text-align: center
    }
    .recover-order-cancel-btn {
        padding: 0 30px;
        background-color: #1DB89C;
        color: white;
        font-size: 14px;
        height: 40px;
        line-height: 40px;
        border-radius: 4px;
        text-align: center
    }
    .recover-order-books-title {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        margin-top: 10px;
        border-top: 0.5px solid #ddd;
        border-bottom: 0.5px solid #eee;
        background-color: white;
    }
    .recover-order-books-count {
        padding: 15px 20px;
        color: #3D404A;
        font-size: 15px;
    }
    .recover-order-level-desc {
        padding: 15px;
        color: #FF4848;
        font-size: 15px;
        opacity: 0.5;
    }
    .recover-order-books {
        background-color: #fff;
    }
    .recover-order-summary {
        border-bottom:0.5px solid #eee;
        background-color: white;
    }
    .recover-order-summary-i{
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        padding: 15px 20px;
        border-bottom: 0.5px solid #eee;
    }
    .recover-order-summary-left {
        font-size: 14px;
        color: #555555;
    }
    .recover-order-summary-right {
        font-size: 14px;
        font-weight: bold;
        color: #555555;
    }
    .recover-order-total-price {
        background-color: white;
        padding: 15px 20px;
        display: flex;
        flex-direction: row;
        align-items: center;
        justify-content: flex-end;
        border-bottom: 0.5px solid #ebedf0;
        font-size: 14px;
        color: #555555;
    }
    .recover-order-price-text {
        color:#ff4848;
        font-size: 16px;
        font-weight: bold;
    }
    .recover-order-address {
        display: flex;
        flex-direction: column;
        border-bottom: 0.5px solid #ebedf0;
        background-color: white;
        border-top: 0.5px solid #ebedf0;
        margin-top: 10px;
        margin-bottom: 80px;
    }
    .recover-order-contact {
        display: flex;
        flex-direction: row;
        align-items: center;
        padding-left: 20px;
        padding-top: 20px;
        font-size: 16px;
        color: #3D404A;
    }
    .recover-order-contact-phone {
        margin-left: 10px;
    }
    .recover-order-address-detail {
        font-size: 13px;
        color: #888888;
        padding: 10px 0 15px 0;
        margin: 0 20px;
        border-bottom: 0.5px solid #ebedf0;
    }
    .recover-order-address-update {
        padding: 10px 20px;
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center
    }
    .recover-order-time {
        font-size: 13px;
        color: #ff4848;
    }
    .recover-order-address-update-btn {
        font-size: 15px;
        color: #3D404A;
        border: 0.5px solid #3D404A;
        border-radius: 4px;
        text-align: center;
        padding: 5px 15px;
    }
    .recover-order-bottom-bar {
        position: fixed;
        left: 0;
        bottom: 0;
        height: 60px;
        display: flex;
        flex-direction: row;
        justify-content: flex-end;
        align-items: center;
        background-color: white;
        border-top: 0.5px solid #ebedf0;
    }
    .recover-order-contact-us {
        font-size: 15px;
        color: #3D404A;
        border: 0.5px solid #3D404A;
        border-radius: 4px;
        text-align: center;
        padding: 5px 15px;
        margin: 0 20px;
    }
    .recover-order-cancel {
        font-size: 15px;
        color: #3D404A;
        border: 0.5px solid #3D404A;
        border-radius: 4px;
        text-align: center;
        padding: 5px 15px;
        margin: 0 20px;
    }
</style>
<script>
    import { mapGetters, mapState, mapActions } from 'vuex';
    import RecoverOrderBook from './RecoverOrderBook'
    import Loading from './Loading'
    import { Toast } from 'vant';
    export default {
        data() {
            return{
                url: '',
                no: '',
                loading: true,
                screenWidth: 0,
                wxConfig: '',
                orderConfig: '',
                dialogVisible: false
            }
        },
        created: function() {
            this.no = this.$route.params.no;
            this.url = window.localStorage.getItem('url');
            this.wxApi.wxConfig('','');
            console.log('order no='+this.no);
            this.$store.dispatch('user/getUser').then(res => {
                // 如果拿不到用户，就显示一个不可关闭的对话框
                var user = res.data;
                if (user===''||user.length===0) {
                    this.$router.replace('/pc/shop');
                } else if(user.subscribe===0) {
                    this.$router.replace('/pc/shop')
                }
            });
            this.loading=true;
            this.$store.dispatch('order/getOrder', this.no).then(res => {
                this.loading = false;
                console.log('order get.');
            });
        },
        computed: {
            createdAt: function() {
                return dayjs(this.order.created_at).format("YYYY-MM-DD HH:mm");
            },
            paidAt: function() {
                return dayjs(this.order.paid_at).format("YYYY-MM-DD HH:mm");
            },
            ...mapState({
                order: state => state.order.order
            }),
            ...mapGetters('order',{
                items: 'items',
                address: 'address',
                recoverOrderPrice: 'recoverOrderPrice',
                recoverOrderRejectPrice: 'recoverOrderRejectPrice',
                orderRecoverStatusDesc: 'orderRecoverStatusDesc'
            })
        },
        mounted: function() {
            this.screenWidth = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
        },
        methods: {
            showDialog: function() {
                this.dialogVisible = true;
            },
            hideDialog: function() {
                this.dialogVisible = false;
            },
            cancel: function() {
                const toast = Toast.loading({
                    duration: 0,       // 持续展示 toast
                    forbidClick: true, // 禁用背景点击
                    loadingType: 'spinner',
                    message: '取消中...'
                });
                this.cancelOrder(this.no).then(res => {
                    if (res.data.code && res.data.code === 500) {
                        this.$dialog.alert({
                            message: res.data.msg,
                            center: true
                        });
                    }
                    toast.clear();
                    this.hideDialog();
                    this.$dialog.alert({
                        message: '顺丰如果电话预约，请回复已取消',
                    });
                });
            },
            qxOrder: function() {
                if (this.order.recover_status>30 || this.order.recover_status===-1 || this.order.closed) {
                    // 无法取消
                    this.$dialog.alert({
                        message: '无法取消，请在服务号中联系客服'
                    })
                } else {
                    this.dialogVisible = true;
                }
            },
            changeAddress: function() {
                if (this.order.recover_status>=30 || this.order.recover_status===-1 || this.order.closed) {
                    this.$dialog.alert({
                        message:'不能更换地址了',
                        center: true
                    });
                }else {
                    this.$router.push({ path:'/pc/address_list', query:{ fo: true} });
                }
            },
            contactUs: function() {
                this.$dialog.alert({
                    message: '请在公众号中询问',
                    center: true
                });
            },
            ...mapActions('order', [
                'getOrder',
                'cancelOrder'
            ])
        },
        components: {
            RecoverOrderBook,
            Loading
        }
    }
</script>
