<template>
    <div>
        <div class="address-notify" v-if="from==='sale_invoice'">
            请填写你的收货地址
        </div>
        <div class="address-notify" v-else-if="from==='recover_invoice'">
            回流鱼会安排顺风上门，请填写你的取货地址
        </div>
        <div class="address-notify" v-else-if="parseInt(addressId)>0">
            请谨慎修改地址
        </div>
        <div class="address-notify" v-else>
            新增地址，越详细越好
        </div>
        <van-address-edit
            :area-list="areaList"
            :show-postal="false"
            :address-info="addressInfo"
            show-delete
            show-set-default
            show-search-result
            :search-result="searchResult"
            @save="onSave"
            @delete="onDelete"
            @change-detail="onChangeDetail"
         />
    </div>
</template>

<script>
    import areaList from '../area.js';
    import { mapGetters, mapState, mapActions } from 'vuex';
    export default {
        data() {
            return {
                screenWidth: 0,
                from: '',
                addressId: 0,
                address: '',
                addressInfo: '',
                areaList,
                searchResult: []
            }
        },
        computed: {
            ...mapState({
                latestAddress: state => state.user.latestAddress,
                adds: state => state.user.adds,
            })
        },
        created: function () {
            this.from = this.$route.query.from;
            this.addressId = this.$route.query.address;
            if (!_.isEmpty(this.addressId) && parseInt(this.addressId)>0){
                if (_.isEmpty(this.adds)) {
                    this.$store.dispatch('user/allUserAddress').then(res => {
                        this.address = this.adds.find(i => i.id === parseInt(this.addressId));
                        if (this.address) {
                            this.addressInfo = {
                                id: this.address.id,
                                name: this.address.contact_name,
                                tel: this.address.contact_phone,
                                province: this.address.province,
                                city: this.address.city,
                                county: this.address.district,
                                addressDetail: this.address.address,
                                areaCode: this.getAreaCode(this.address),
                                postalCode: "000000",
                                isDefault: this.address.is_default
                            };
                        }
                    });
                }else {
                    this.address = this.adds.find(i => i.id === parseInt(this.addressId));
                    if (this.address) {
                        this.addressInfo = {
                            id: this.address.id,
                            name: this.address.contact_name,
                            tel: this.address.contact_phone,
                            province: this.address.province,
                            city: this.address.city,
                            county: this.address.district,
                            addressDetail: this.address.address,
                            areaCode: this.getAreaCode(this.address),
                            postalCode: "000000",
                            isDefault: this.address.is_default
                        };
                    }
                }
            }
            console.log('from '+this.from);
            this.wxApi.wxConfig('','');
        },
        mounted: function() {
            this.screenWidth = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
        },
        methods: {
            getAreaCode: function(address) {
                if (!_.isEmpty(address.zip) && parseInt(address.zip)>0) {
                    return address.zip;
                }
                var result = "";
                for (var item in areaList.province_list) {
                    var value = areaList.province_list[item];
                    if (address.province === value) {
                        result = item;
                    }
                }
                for (var item in areaList.city_list) {
                    var value = areaList.city_list[item];
                    if (address.city === value) {
                        result = item;
                    }
                }
                for (var item in areaList.county_list) {
                    var value = areaList.county_list[item];
                    if (address.district === value) {
                        result = item;
                    }
                }
                return result;
            },
            onSave(val) {
                if (val.name.length<2 || val.name.length>3) {
                    this.$dialog.alert({
                        message: '名字最少2个字,最多3个字'
                    });
                    return;
                }
                if (val.addressDetail.length<4) {
                    this.$dialog.alert({
                        message: '详细地址不能少于4个字'
                    });
                    return;
                }
                this.createUserAddress({
                    id: this.addressId,
                    contact_name: val.name,
                    contact_phone: val.tel,
                    province: val.province,
                    city: val.city,
                    district: val.county,
                    address: val.addressDetail,
                    zip: val.areaCode,
                    default: val.isDefault
                }).then(res => {
                    if (res.data.code && res.data.code===500) {
                        this.$dialog.alert(res.data.msg);
                    }else{
                        if (this.from === 'sale_invoice') {
                            this.$router.replace('/wechat/sale_invoice')
                        } else if (this.from === 'recover_invoice') {
                            this.$router.replace('/wechat/recover_invoice')
                        }else {
                            this.$router.back();
                        }
                    }
                });
            },
            onDelete() {
                this.deleteUserAddress(this.address).then(res => {
                    if (res.data.code && res.data.code===500) {
                        this.$dialog.alert(res.data.msg);
                    }else{
                        if (this.from === 'sale_invoice') {
                            this.$router.replace('/wechat/sale_invoice')
                        } else if (this.from === 'recover_invoice') {
                            this.$router.replace('/wechat/recover_invoice')
                        }else {
                            this.$router.back();
                        }
                    }
                })
            },
            onChangeDetail(val) {
            },
            ...mapActions('user', [
                'createUserAddress',
                'deleteUserAddress'
            ])
        }
    }
</script>
<style scoped>
    .address-notify {
        display: flex;
        flex-direction: row;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        color: #f0303f;
        padding: 5px;
        background-color: #feecec;
    }
</style>