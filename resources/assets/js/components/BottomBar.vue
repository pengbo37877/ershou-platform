<template>
    <div>
        <div class="foot">
            <div class="foot-a" @click="iconClick(0)">
                <div class="foot-item" :class="{'foot-on':Number(index)===0}">
                    <van-icon name="shop-o"/>
                    <p class="foot-item-desc">商店</p>
                </div>
            </div>
            <div class="foot-a" @click="iconClick(1)">
                <div class="foot-item" :class="{'foot-on':Number(index)===1}">
                    <van-icon name="ellipsis"/>
                    <p class="foot-item-desc">句子迷</p>
                </div>
            </div>
            <div class="foot-a" @click="iconClick(2)">
                <div class="foot-item" :class="{'foot-on':Number(index)===2}">
                    <van-icon name="scan"/>
                    <p class="foot-item-desc">卖书</p>
                </div>
            </div>
            <div class="foot-a" @click="iconClick(3)">
                <div class="foot-item" :class="{'foot-on':Number(index)===3}">
                    <van-icon name="bag-o" v-if="items.length===0"/>
                    <van-icon name="bag-o" :info="items.length" v-else/>
                    <p class="foot-item-desc">购物袋</p>
                    <!--<div class="foot-cart-item-count" v-if="items.length>0">{{items.length}}</div>-->
                </div>
            </div>
            <div class="foot-a" @click="iconClick(4)">
                <div class="foot-item" :class="{'foot-on':Number(index)===4}">
                    <van-icon name="contact"/>
                    <p class="foot-item-desc">我的</p>
                </div>
            </div>
        </div>

        <van-popup v-model="dialogVisible">
            <p style="text-align: center;">关注回流鱼解锁</p>
            <img src="/images/qrcode.jpg" width="100%" alt="">
        </van-popup>
    </div>
</template>

<script>
    import { mapGetter, mapState, mapActions } from 'vuex';
    export default {
        data() {
            return {
                dialogVisible: false,
                device: ''
            }
        },
        props: ['index'],
        computed: {
            ...mapState({
                items: state => state.cart.items,
                user: state => state.user.user
            })
        },
        created: function() {
            this.device = window.localStorage.getItem('device');
        },
        mounted: function () {
            console.log('bottom bar index='+this.index);
            this.$store.dispatch('cart/items');
            this.$store.dispatch('cart/reminders');
        },
        methods: {
            iconClick: function(newIndex){
                if (newIndex>0 && (this.user==='' || this.user.length===0) || (this.user && this.user.subscribe===0)) {
                    this.dialogVisible = true;
                }else{
                    switch (newIndex) {
                        case 0:
                            this.$router.push('/wechat/shop');
                            break;
                        case 1:
                            this.$router.push('/wechat/jzm');
                            break;
                        case 2:
                            this.$router.push('/wechat/scan');
                            break;
                        case 3:
                            this.$router.push('/wechat/cart');
                            break;
                        case 4:
                            this.$router.push('/wechat/my');
                            break;
                        default:
                            console.log('no this route');
                            break;
                    }
                }
            },
        }
    }
</script>

<style scoped>
    a {
        text-decoration: none;
    }
    a:visited {
        text-decoration: none;
    }
    a:active {
        text-decoration: none;
    }
    a:link {
        text-decoration: none;
    }
    a:hover {
        text-decoration: none;
    }
    .foot {
        position: fixed;
        left: 0;
        bottom: 0;
        background: #fff;
        display: flex;
        flex-direction: row;
        justify-content: space-around;
        align-items: center;
        height: 44px;
        width: 100%;
        border-top: 0.5px solid #ccc;
        padding-bottom: 2px;
    }
    .foot-a {
        color: #666;
    }
    .foot-item {
        position:relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        margin-bottom: -20px;
        color: #666;
    }
    .foot-item-desc{
        font-size: 11px;
        margin-top: 3px;
        font-weight: 200;
    }
    .foot-on {
        color: #96B897;
    }
    .foot-cart-item-count{
        position:absolute;
        top: -6px;
        right: -12px;
        color: white;
        background-color: #ff4848;
        font-size: 12px;
        height: 20px;
        line-height: 20px;
        min-width: 20px;
        border-radius: 13px;
        display: flex;
        flex-direction: row;
        justify-content: center;
        align-items: center;
    }
    .van-popup {
        border-radius: 6px;
    }
</style>