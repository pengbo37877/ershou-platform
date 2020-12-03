<template>
    <div style="margin-bottom: 65px">
        <store-sku :sku="sku" :skus="skus" :screen-width="screenWidth" v-for="sku in skus" :key="sku.id"></store-sku>
        <van-popup
                v-model="show"
                closeable
                close-icon="close"
                position="bottom"
                :style="{ height: '70%' }"
        >
            <van-button color="#cccccc" plain @click="goBack">返回</van-button><br>
            <button class="row" @click="showShelves(item)" v-if="step==1" v-for="item in stores">{{ item }}</button>
            <button class="shelf" @click="showBoxes(item)" v-if="step==2" v-for="item in stores2">书架{{ item }}</button>
            <button class="box" @click="chooseBox(item)" v-if="step==3" v-for="item in stores3">{{ item.code }}</button>
        </van-popup>
        <div class="options">
            <van-button type="default" style="width: 40%;" @click="scan">扫回流鱼码({{skus.length}})</van-button>
            <van-button plain type="primary" style="width: 20%;" @click="showStores">选仓库</van-button>
            <van-button plain type="primary" style="width: 20%;" @click="scanCode">扫仓库条码</van-button>
        </div>
    </div>
</template>
<style>
    .row,.shelf {
        border: 1px solid #bbb;
        color: #000;
        padding: 6px 10px;
        background: #fff;
        width: 80%;
        margin-left: 10%;
        margin-top: 16px;
        text-align: center;
    }
    .box {
        margin: 5px;
        border: solid 1px #bbbbbb;
        color: #000;
        padding: 5px 10px;
        background: #fff;
        min-width: 50px;
        display:inline-block;
    }
</style>
<script>
    import wx from 'weixin-js-sdk';
    import StoreSku from './StoreSku'
    import { Toast,Popup } from 'vant';
    export default {
        name: "StoreShelf",
        data() {
            return {
                screenWidth: 0,
                skus: [],
                stores: [],
                chooseStore: '',
                chooseCode:'',
                show: false,
                goods: {},
                SKU: {},
                initialSku:{},
                storing: false,
                row: null,
                shelf: null,
                step: 1,
                shelf_data:null,
                stores2: [],
                stores3: [],
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
            // 获取所有仓库排列
            axios.get('/store_shelf/stores2').then(res => {
                this.shelf_data = res.data;
                this.stores = Object.keys(this.shelf_data);
                // this.build();
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
            goBack(){
                if(this.step > 1){
                    this.step = this.step - 1;
                }
            },
            showShelves(item){
                this.row = item;
                this.stores2 = this.shelf_data[item];
                this.step = 2;
            },
            showBoxes(item){
                let that = this;
                that.shelf = item;
                axios.get('/store_shelf/boxes?row='+that.row+"&shelf="+item).then(function (res) {
                    that.stores3 = res.data;
                    that.step = 3;
                });
            },
            chooseBox(item){
                if (this.storing) {
                    return;
                }
                if (this.skus.length===0) {
                    this.$toast('请先扫码');
                    return;
                }
		        this.$dialog.confirm({
                    title: "确认",
                    message: "共扫码"+this.skus.length+"本，存放于 "+item.code+"？"
                }).then(() => {
                    // confirm
                    console.log('choose');
                    this.show = false;
                    this.chooseStore = item.id;
                    this.update();
                }).catch(()=>{
                    console.log('取消存放');
                });
            },
            copyDeep(templateData) {
                // templateData 是要复制的数组或对象，这样的数组或者对象就是指向新的地址的
                return JSON.parse(JSON.stringify(templateData));
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
                if(this.skus.length >= 16){
                    this.$toast('最多同时上架16本');
                }else {
                    wx.scanQRCode({
                        needResult: 1, // 默认为0，扫描结果由微信处理，1则直接返回扫描结果，
                        scanType: ["qrCode", "barCode"], // 可以指定扫二维码还是一维码，默认二者都有
                        success: function (res) {
                            var result = res.resultStr.split(",")[1]; // 当needResult 为 1 时，扫码返回的结果
                            _this.isbn = result;
                            _this.getSkuByCode(result);
                        }
                    });
                }
            },
            scanCode: function(){
                var _this = this;
                if (this.storing) {
                    return;
                }
                if(_this.skus.length >= 16){
                    this.$toast('最多同时上架16本');
                    return;
                }
                if(_this.skus.length == 0){
                    this.$toast('先扫回流鱼码');
                    return;
                }
                wx.scanQRCode({
                    needResult: 1, // 默认为0，扫描结果由微信处理，1则直接返回扫描结果，
                    scanType: ["qrCode", "barCode"], // 可以指定扫二维码还是一维码，默认二者都有
                    success: function (res) {
                        var result = res.resultStr.split(",")[1]; // 当needResult 为 1 时，扫码返回的结果
                        _this.chooseCode = result;
                        _this.$dialog.confirm({
                            title: "确认",
                            message: "共扫码"+_this.skus.length+"本，存放于 "+result+"？"
                        }).then(() => {
                            // confirm
                            console.log('choose');
                            _this.update();
                        }).catch(()=>{
                            console.log('取消存放');
                        });
                    }
                });
            },
            getSkuByCode: function(code) {
                axios.get('/store_shelf/get_sku_by_code?code=' + code).then(res => {
                    if (res.data.code && res.data.code === 500) {
                        this.$dialog.alert({
                            message: res.data.msg
                        });
                    } else {
                        var sku = this.skus.find(sku => {
                            return sku.hly_code === res.data.hly_code;
                        });
                        if (sku) {
                            this.$toast('重复扫描');
                        } else {
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
                    store: this.chooseStore,
                    code: this.chooseCode
                }).then(res => {
                    this.storing = false;
                    this.chooseStore = '';
                    this.chooseCode = '';
                    toast.clear();
                    if(res.data.code && res.data.code===500) {
                        this.$dialog.confirm({
                            title:"上架失败",
                            message:res.data.msg,
                            confirmButtonText:"下一步",
                            showCancelButton:false
                        }).then(()=>{
                            console.log('重新上架');
                        })
                    }else{
                        this.skus  = [];
                        this.$toast("上架完成");
                    }
                });
            },
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
