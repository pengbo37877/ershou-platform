<template>
    <div style="margin-bottom: 65px">
        <store-sku :sku="sku" :skus="skus" :screen-width="screenWidth" v-for="sku in skus" :key="sku.id"></store-sku>
        <van-sku
                v-model="show"
                :sku="SKU"
                :goods="goods"
                :goods-id="100"
                hide-stock="{{true}}"
                show-add-cart-btn="{{false}}"
                reset-stepper-on-hide="{{true}}"
                disable-stepper-input="{{true}}"
                close-on-click-overlay="{{true}}"
                :initial-sku="initialSku"
                @buy-clicked="onBuyClicked"
                @add-cart="onAddCartClicked"
        >
            <!-- 自定义 sku-header-price -->
            <template slot="sku-header-price" slot-scope="props">
                <div class="van-sku__goods-price">
                    <span class="van-sku__price-symbol">￥</span><span class="van-sku__price-num">{{ props.price }}</span>
                </div>
            </template>
            <!-- 自定义 sku actions -->
            <template slot="sku-actions" slot-scope="props">
                <div class="van-sku-actions">
                    <!-- 直接触发 sku 内部事件，通过内部事件执行 onBuyClicked 回调 -->
                    <van-button type="primary" bottom-action @click="props.skuEventBus.$emit('sku:buy')">存入仓库</van-button>
                </div>
            </template>
        </van-sku>
        <div class="options">
            <van-button type="default" style="width: 50%;" @click="scan">扫回流鱼码({{skus.length}})</van-button>
            <van-button plain type="primary" style="width: 50%;" @click="showStores">选仓库</van-button>
        </div>
    </div>
</template>

<script>
    import wx from 'weixin-js-sdk';
    import StoreSku from './StoreSku'
    import { Toast } from 'vant';
    export default {
        name: "StoreShelf",
        data() {
            return {
                screenWidth: 0,
                skus: [],
                stores: [],
                chooseStore: '',
                show: false,
                goods: {},
                SKU: {},
                initialSku:{},
                storing: false
            }
        },
        computed: {

        },
        mounted() {
            this.screenWidth = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
            this.screenHeight = window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;
            // 获取微信配置
            axios.post('/store_shelf/config').then(response => {
                console.log(response.data);
                wx.config(response.data);
                wx.ready(()=>{
                    console.log("ready");
                });
            });
            // 获取所有仓库
            axios.get('/store_shelf/stores').then(res => {
                this.stores = res.data;
                this.build();
            })
        },
        activated: function() {
            var ss = this.copyDeep(this.skus);
            this.skus = [];
            var _this = this;
            ss.forEach(function(sku) {
                _this.getSkuByCode(sku.hly_code);
            });
        },
        methods: {
            copyDeep(templateData) {
                // templateData 是要复制的数组或对象，这样的数组或者对象就是指向新的地址的
                return JSON.parse(JSON.stringify(templateData));
            },
            build: function () {
                var _this = this;
                var url = window.localStorage.getItem('url');
                this.goods = {
                    title: '更改仓库存放',
                    picture: url+'/images/logo_main.png'
                };
                var tree = [];
                var v = [];
                var list = [];
                if (this.stores.length>0) {
                    this.stores.forEach(function (s) {
                        v.push({
                            id: s.id,
                            name: s.code,
                        });
                        list.push({
                            id: s.id,
                            price: s.capacity,
                            s1: s.id,
                            s2: '0',
                            s3: '0',
                            stock_num: 1
                        });
                    });
                }

                tree.push({
                    k: '请选择仓库',
                    v: v,
                    k_s: 's1'
                });
                this.SKU = {
                    tree,
                    list,
                    price: 0,
                    stock_num: 1,
                    collection_id: 100,
                    none_sku: false,
                    hide_stock: true,
                    messages: []
                }
            },
            showStores: function() {
                this.show = true;
            },
            removeSku: function(sku) {
                var index = this.skus.indexOf(sku);
                if (index !== -1) {
                    this.skus.splice(index, 1)
                }
            },
            scan: function() {
                var _this = this;
                wx.scanQRCode({
                    needResult: 1, // 默认为0，扫描结果由微信处理，1则直接返回扫描结果，
                    scanType: ["qrCode","barCode"], // 可以指定扫二维码还是一维码，默认二者都有
                    success: function (res) {
                        var result = res.resultStr.split(",")[1]; // 当needResult 为 1 时，扫码返回的结果
                        _this.isbn = result;
                        _this.getSkuByCode(result);
                    }
                });
                // this.getSkuByCode('hly1037282756');
            },
            getSkuByCode: function(code) {
                axios.get('/store_shelf/get_sku_by_code?code='+code).then(res => {
                    if(res.data.code && res.data.code===500) {
                        this.$dialog.alert({
                            message:res.data.msg
                        });
                    }else{
                        var sku = this.skus.find(sku => {
                            return sku.hly_code === res.data.hly_code;
                        });
                        if (sku) {
                            this.$toast('重复扫描');
                        }else {
                            this.skus.unshift(res.data);
                        }
                    }
                });
            },
            update: function() {
                if (this.skus.length===0) {
                    this.$toast('请先扫码');
                    return;
                }
                this.storing = true;
                const toast = Toast.loading({
                    duration: 0,       // 持续展示 toast
                    forbidClick: true, // 禁用背景点击
                    loadingType: 'spinner',
                    message: '上架中...'
                });
                var ids = this.skus.map(function(sku) { return sku.id; });
                axios.post('/store_shelf', {
                    ids,
                    store: this.chooseStore
                }).then(res => {
                    this.storing = false;
                    toast.clear();
                    if(res.data.code && res.data.code===500) {
                        this.$toast(res.data.msg);
                    }else{
                        this.skus  = [];
                    }
                });
            },
            onBuyClicked: function(skuData) {
                if (this.storing) {
                    return;
                }
                console.log('onBuyClicked');
                this.show = false;
                this.chooseStore = skuData.selectedSkuComb.id;
                this.update();
            }
        },
        components: {
            StoreSku
        }
    }
</script>
<style scoped>
    .options {
        position: fixed;
        left: 0;
        bottom: 0;
        width: 100%;
        display: flex;
        flex-direction: row;
        justify-content: space-between;
    }
</style>