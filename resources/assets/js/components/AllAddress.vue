<template>
    <div class="all-address">
        <div class="all-no-user-address" v-if="adds.length===0">
            <div class="all-no-user-address-desc">还木有地址</div>
        </div>
        <div class="all-user-address" v-for="ad in adds" v-else>
            <div class="all-user-address-contact">
                <div class="all-user-address-contact-name">{{ad.contact_name}}</div>
                <div class="all-user-address-contact-phone">{{ad.contact_phone}}</div>
            </div>
            <div class="all-user-address-detail">{{ ad.province + ad.city + ad.district + ad.address }}</div>
            <div class="all-user-address-update">
                <div v-if="fo && order.address.id === ad.id"></div>
                <div v-else-if="latestAddress && latestAddress.id === ad.id"></div>
                <div class="all-user-address-delete-btn" @click="deleteThisAddress(ad)" v-else>删除</div>
                <div class="all-user-address-use-btn" style="opacity: 0" v-if="from==='my'"></div>
                <div class="all-user-address-use-btn" v-else-if="fo && order.address.id === ad.id">正在使用</div>
                <div class="all-user-address-use-btn" v-else-if="latestAddress && latestAddress.id === ad.id">正在使用</div>
                <div class="all-user-address-use-btn" @click="useThisAddress(ad)" v-else>使用</div>
            </div>
        </div>
        <!--<div class="all-add-wx-address" :style="{width: (screenWidth-40) + 'px'}" @click="chooseWxAddress">-->
            <!--使用微信收货地址-->
        <!--</div>-->
        <router-link :to="`/wechat/add_address?from=${from}`" class="all-add-address" :style="{width: (screenWidth-40) + 'px'}">
            添加新地址
        </router-link>
    </div>
</template>

<script>
    import wx from 'weixin-js-sdk';
    import { mapGetters, mapState, mapActions } from 'vuex';
    export default {
        data() {
            return {
                screenWidth: 0,
                fo: false,
                from: ''
            }
        },
        computed: {
            ...mapState({
                user: state => state.user.user,
                latestAddress: state => state.user.latestAddress,
                adds: state => state.user.adds,
                order: state => state.order.order
            })
        },
        created: function() {
            this.from = this.$route.query.from;
            console.log('from '+this.from);
            this.$store.dispatch('user/allUserAddress');
            this.fo = this.$route.query.fo;
            console.log(this.$route.query)
            this.wxApi.wxConfig('','');
        },
        mounted: function() {
            this.screenWidth = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
        },
        methods: {
            wxConfig: function() {
                var _this = this;
                axios.post('/wx-api/config',{
                    'url': 'all_address'
                }).then(response => {
                    console.log(response.data);
                    wx.config(response.data);
                    wx.error(res => {
                        _this.wxConfig();
                    });
                    wx.ready(()=>{
                        console.log("ready");
                    });
                });
            },
            deleteThisAddress: function(ad){
                this.deleteUserAddress(ad).then(res => {
                    if (res.data.code && res.data.code === 500) {
                        this.$dialog.alert({
                            message: res.data.msg,
                            center: true
                        });
                    } else {
                        var index = this.adds.indexOf(ad);
                        if (index !== -1) {
                            this.adds.splice(index, 1)
                        }
                    }
                })
            },
            useThisAddress: function(ad) {
                this.$store.commit('user/setUserAddress', ad);
                if (this.fo) {
                    this.$store.dispatch('order/updateOrderAddress', ad)
                }
                if (this.from === 'sale_invoice' || this.from === 'recover_invoice') {
                    this.$router.back();
                }
            },
            chooseWxAddress: function() {
                var _this = this;
                axios.get('/wx-api/share_address_config').then(response => {
                    console.log(response.data);
                    wx.config(response.data);
                });
                wx.openAddress({
                    success: function (res) {
                        var userName = res.userName; // 收货人姓名
                        var postalCode = res.postalCode; // 邮编
                        var provinceName = res.provinceName; // 国标收货地址第一级地址（省）
                        var cityName = res.cityName; // 国标收货地址第二级地址（市）
                        var countryName = res.countryName; // 国标收货地址第三级地址（区）
                        var detailInfo = res.detailInfo; // 详细收货地址信息
                        var nationalCode = res.nationalCode; // 收货地址国家码
                        var telNumber = res.telNumber; // 收货人手机号码
                        // alert(JSON.stringify(res));
                        _this.createUserAddress({
                            user_id: _this.user.id,
                            province: provinceName,
                            city: cityName,
                            district: countryName,
                            address: detailInfo,
                            contact_name: userName,
                            contact_phone: telNumber
                        }).then(res => {
                            if (res.code && res.code === 500) {
                                this.$dialog.alert({
                                    message: res.msg,
                                    center: true
                                });
                            }else {
                                if (_this.from === 'sale_invoice' || _this.from === 'recover_invoice') {
                                    _this.$router.back();
                                } else {
                                    _this.$store.dispatch('user/allUserAddress');
                                }
                            }
                        });
                    },
                    fail: function (res) {
                        alert(JSON.stringify(res));
                    }
                });
            },
            ...mapActions('user', [
                'deleteUserAddress',
                'createUserAddress'
            ])
        }
    }
</script>

<style scoped>
    body {
        background-color: white;
        margin: 0;
    }
    .all-address {
        display: flex;
        flex-direction: column;
        margin-bottom: 100px;
    }
    .all-no-user-address {
        background-color: white;
        display: flex;
        flex-direction: row;
        justify-content: center;
        align-items: center;
        padding: 20px;
    }
    .all-no-user-address-desc {
        font-size: 18px;
        color: #ccc;
        text-align: center;
    }
    .all-user-address {
        display: flex;
        flex-direction: column;
        border-bottom: 0.5px solid #ddd;
        background-color: white;
    }
    .all-user-address-contact {
        display: flex;
        flex-direction: row;
        padding-left: 20px;
        padding-top: 20px;
        font-size: 16px;
        font-weight: 600;
        color: #3D404A;
    }
    .all-user-address-contact-phone {
        margin-left: 10px;
    }
    .all-user-address-detail {
        font-size: 13px;
        color: #3D404A;
        padding: 10px 0 15px 0;
        margin: 0 20px;
        border-bottom: 0.5px solid #eee;
    }
    .all-user-address-update {
        padding: 10px 20px;
        display: flex;
        flex-direction: row;
        justify-content: flex-end;
    }
    .all-user-address-delete-btn {
        font-size: 15px;
        color: white;
        background-color: #ff4848;
        border-radius: 4px;
        text-align: center;
        padding: 5px 8px;
    }
    .all-user-address-use-btn {
        font-size: 15px;
        color: white;
        background-color: #3D404A;
        border-radius: 4px;
        text-align: center;
        padding: 5px 8px;
        margin-left: 15px;
    }
    .all-add-address {
        position: fixed;
        bottom: 20px;
        left: 20px;
        display: flex;
        flex-direction: row;
        justify-content: center;
        align-items: center;
        text-align: center;
        height: 40px;
        background-color: #3D404A;
        color: white;
        font-size: 15px;
        border-radius: 4px;
    }
    .all-add-wx-address {
        position: fixed;
        bottom: 70px;
        left: 20px;
        display: flex;
        flex-direction: row;
        justify-content: center;
        align-items: center;
        text-align: center;
        height: 40px;
        background-color: #0F9713;
        color: white;
        font-size: 15px;
        border-radius: 4px;
    }
</style>