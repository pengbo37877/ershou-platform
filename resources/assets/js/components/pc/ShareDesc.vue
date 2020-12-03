<template>
    <div>
        <div class="share-wrap">
            <div class="share-title">邀请新用户，得买书折扣券20元</div>
            <div class="share-body">
                <div class="body-li">好友通过你的二维码领取买书券和卖书券并成功下单，你将获得 20 元买书折扣券</div>
                <div class="body-li">受邀请的好友也将获得 10 元买书折扣券和 5 元卖书增值券</div>
                <img class="body-img" src="/images/coupon-hly.png" alt="">
                <van-button style="width: 100%" round loading type="danger" v-if="loading">发邀请，得买书券</van-button>
                <van-button style="width: 100%" round type="danger" @click="showQrCode" v-else>发邀请，得买书券</van-button>
            </div>
        </div>
        <div class="share-wrap">
            <div class="coupon-title">活动说明</div>
            <div class="coupon-li">
                20元现金券的激活时间为你邀请的用户的买书状态为[已出库]，卖书状态为[已完成]
            </div>
            <div class="coupon-li">
                一个新用户对应一张现金券，最多可以激活10张
            </div>
            <div class="coupon-li">
                一个订单可以使用一张现金券，不能叠加使用
            </div>
            <div class="coupon-li">
                20元现金券为满减现金券，订单金额满40元后可用
            </div>
            <div class="coupon-li">
                10元新人买书现金券为满减现金券，订单金额满30元后可用
            </div>
            <div class="coupon-li">
                5元新人卖书增值券无使用限制
            </div>
        </div>
        <!-- <div class="share-bar">
            <div class="bar-avatar">
                <img :src="user.avatar" class="bar-avatar-img" alt="">
            </div>
            <div class="bar-info">
                <div class="bar-invite-text">邀请到 {{saleCoupons.length}} 人</div>
                <div class="bar-invite-text">已获得买书券 <span style="color: #ff4848;font-weight: 600">{{inviteCouponSum}}</span> 元</div>
            </div>
        </div> -->
        <van-popup v-model="show" position="top">
            <div class="qr-image" :style="{width: screenWidth+'px'}">
                <div class="qr-tip">
                    长按保存，然后分享给你的朋友
                    <div class="qr-close" @click="show=false">
                        <van-icon name="close" />
                    </div>
                </div>
                <img :src="qrImage" alt="" :style="{width: screenWidth-40+'px'}">
            </div>
        </van-popup>
    </div>
</template>

<script>
    import { mapState, mapGetters, mapActions} from 'vuex'
    export default {
        data() {
            return {
                screenWidth: 0,
                show: false,
                qrImage:'',
                loading: false
            }
        },
        created: function() {
            this.wxApi.wxConfig('','');
            this.screenWidth = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
            this.$store.dispatch('user/getUser').then(res => {
                // 如果拿不到用户，就显示一个不可关闭的对话框
                var user = res.data;
                if (user===''||user.length===0) {
                    this.$router.replace('/pc/shop');
                } else if(user.subscribe===0) {
                    this.$router.replace('/pc/shop')
                }
            });
            this.$store.dispatch('coupon/getCoupons');
        },
        computed: {
            inviteCouponSum: function() {
                var totalPrice = this.saleCoupons.reduce((total, item) => {
                    return total + Number(item.value);
                }, 0);
                return Number(totalPrice).toFixed(2);
            },
            ...mapState({
                userId: state => state.user.userId,
                user: state => state.user.user,
            }),
            ...mapGetters('coupon',{
                saleCoupons: 'saleCoupons',
            })
        },
        methods: {
            showQrCode: function() {
                if (this.qrImage) {
                    this.show = true;
                    return;
                }
                this.loading = true;
                axios.get('/wx-api/get_share_qr_image').then(res=>{
                    this.qrImage = res.data;
                    this.show = true;
                    this.loading=false;
                });
            }
        }
    }
</script>

<style scoped>
    .share-wrap {
        padding: 15px;
        background-color: #ffeeee;
    }

    .share-title {
        font-size: 20px;
        font-weight: 700;
    }

    .share-body {
        background-color: white;
        border-radius: 6px;
        margin-top: 10px;
        padding: 10px;
    }

    .body-li {
        font-size: 15px;
        line-height: 22px;
        margin: 10px 0;
        color: #444444;
    }

    .body-li:before {
        content: '●   ';
        color: darkgoldenrod;
        font-size: 12px;
    }

    .body-img {
        width: 100%;
        margin: 15px 0;
    }

    .share-btn {
        font-size: 16px;
        width: 100%;
        padding: 10px 0;
        color: white;
        background-color: red;
        border-radius: 20px;
        text-align: center;
        margin-bottom: 10px;
    }

    .coupon-title {
        width: 100%;
        text-align: center;
        font-weight: 400;
    }
    .coupon-title:before {
        content: '●   ';
        color: darkgoldenrod;
        font-size: 12px;
    }
    .coupon-title:after {
        content: '   ●';
        color: darkgoldenrod;
        font-size: 12px;
    }
    .coupon-li {
        font-size: 16px;
        line-height: 24px;
        margin: 15px 0;
        color: #333333;
    }
    .coupon-li:before {
        content: '●   ';
        color: darkgoldenrod;
        font-size: 12px;
    }
    .share-bar {
        position: fixed;
        left: 0;
        bottom: 0;
        width: 100%;
        height: 70px;
        display: flex;
        flex-direction: row;
        align-items: center;
        background-color: white;
    }
    .bar-avatar-img {
        width: 48px;
        height: 48px;
        border-radius: 24px;
        margin: 0 15px;
    }
    .bar-info {
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    .bar-invite-text {
        font-size: 12px;
        color: #555555;
    }
    .qr-image {
        text-align: center;
    }
    .qr-tip {
        background-color: #ffe1e1;
        color: black;
        font-size: 14px;
        line-height: 40px;
        height: 40px;
        text-align: center;
        position: relative;
    }
    .qr-close {
        position: absolute;
        right: 15px;
        top: 0;
    }
</style>