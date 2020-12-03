<template>
    <div class="coupon">
        <router-link :tag="div" to="/wechat/share_desc" class="coupon-btn">
            <div>免费领取 ￥20 现金券</div>
            <div class="coupon-icon">
                <van-icon name="arrow" size="14px" color="white"/>
            </div>
        </router-link>
    </div>
</template>

<script>
    export default {
        name: "CouponBar.vue",
        props: ['screenWidth'],
        computed: {
            couponWidth: function() {
                return {
                    width: this.screenWdith-30+'px'
                }
            }
        }
    }
</script>

<style scoped>
    .coupon {
        position: relative;
        display: flex;
        flex-direction: row;
        justify-content: center;
        align-items: center;
        height: 48px;
        width: 100%;
    }

    .coupon-btn {
        display: flex;
        flex-direction: row;
        justify-content: center;
        align-items: center;
        font-size: 11px;
        color: white;
        font-weight: bold;
        background-color: #372D20;
        height: 24px;
        line-height: 24px;
        padding: 4px 6px 4px 12px;
        border-radius: 25px;
        width: 130px;
        -webkit-box-shadow: 1px 1px 5px 1px rgba(204,204,204,1);
        -moz-box-shadow: 1px 1px 5px 1px rgba(204,204,204,1);
        box-shadow: 1px 1px 5px 1px rgba(204,204,204,1);
    }
    .coupon-icon {
        display: flex;
        flex-direction: row;
        justify-content: center;
        align-items: center;
    }
</style>