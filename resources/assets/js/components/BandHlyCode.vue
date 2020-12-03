<template>
    <div class="band-hly-code">
        <div class="input-isbn">
            <van-cell-group>
                <van-field
                        v-model="isbn"
                        placeholder="请输入内容"
                        @blur="getSkuByIsbn"
                        @keyup.enter.native="getSkuByIsbn"
                        :clearable="true"
                />
            </van-cell-group>
        </div>
        <div class="scan-isbn">
            <van-radio-group v-model="id">
                <van-radio :name="sku.id" v-for="sku in skus">{{sku.user.nickname+' 的 '+sku.book.name}}</van-radio>
            </van-radio-group>
            <div class="scan-isbn-btn" @click="scanIsbn">扫 ISBN</div>
        </div>
        <div class="scan-hly-code">
            <div class="hly-code" v-if="hlyCode">{{hlyCode}}</div>
            <div class="scan-hly-code-btn" @click="scanHlyCode">扫回流鱼码</div>
        </div>
        <div class="band">
            <div class="band-btn" @click="band">绑定</div>
        </div>
    </div>
</template>

<script>
    import wx from 'weixin-js-sdk';
    export default {
        data() {
            return {
                skus: [],
                id: '',
                isbn: '',
                hlyCode: ''
            }
        },
        mounted: function() {
            this.screenWidth = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
            this.screenHeight = window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;
            // 获取微信配置
            this.getWxConfig();
            this.wxApi.wxConfig('','');
        },
        methods: {
            getWxConfig: function() {
                var _this = this;
                axios.post('/inbound/config', {
                    url: 'band_hly_code'
                }).then(response => {
                    console.log(response.data);
                    wx.config(response.data);
                    wx.ready(()=>{
                        console.log("ready");
                    });
                    wx.error(() => {
                        _this.getWxConfig();
                    })
                });
            },
            scanIsbn: function() {
                var _this = this;
                wx.scanQRCode({
                    needResult: 1, // 默认为0，扫描结果由微信处理，1则直接返回扫描结果，
                    scanType: ["qrCode","barCode"], // 可以指定扫二维码还是一维码，默认二者都有
                    success: function (res) {
                        _this.isbn = res.resultStr.split(",")[1]; // 当needResult 为 1 时，扫码返回的结果
                        _this.getSkuByIsbn();
                    }
                });
            },
            scanHlyCode: function() {
                var _this = this;
                wx.scanQRCode({
                    needResult: 1, // 默认为0，扫描结果由微信处理，1则直接返回扫描结果，
                    scanType: ["qrCode","barCode"], // 可以指定扫二维码还是一维码，默认二者都有
                    success: function (res) {
                        _this.hlyCode = res.resultStr.split(",")[1]; // 当needResult 为 1 时，扫码返回的结果
                    }
                });
            },
            getSkuByIsbn: function() {
                axios.get('/inbound/get_sku_by_isbn?isbn='+this.isbn).then(res => {
                    this.skus = res.data;
                });
            },
            band: function() {
                axios.post('/inbound/band', {
                    sku_id: this.id,
                    hly_code: this.hlyCode
                }).then(res => {
                    if (res.data.code && res.data.code === 500) {
                        this.$dialog.alert({
                            message: res.data.msg,
                            center: true
                        });
                    } else {
                        this.$dialog.alert({
                            message: '绑定成功',
                            center: true
                        });
                    }
                    this.id = '';
                    this.isbn = '';
                    this.hlyCode = '';
                    this.skus = [];
                })
            }
        }
    }
</script>

<style scoped>
    .band-hly-code {
        background-color: white;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
    }
    .scan-isbn {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
    }
    .scan-isbn-btn {
        padding: 5px 15px;
        border-radius: 4px;
        border: 1px solid black;
        background-color: white;
        color: black;
        font-size: 16px;
    }
    .scan-hly-code {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        margin-top: 20px;
    }
    .scan-hly-code-btn {
        padding: 5px 15px;
        border-radius: 4px;
        border: 1px solid black;
        background-color: white;
        color: black;
        font-size: 16px;
    }
    .band {
        position: fixed;
        left: 0;
        bottom: 0;
        width: 100%;
        height: 80px;
        display: flex;
        flex-direction: row;
        justify-content: center;
        align-items: center;
    }
    .band-btn {
        padding: 5px 15px;
        border-radius: 4px;
        background-color: black;
        color: white;
        font-size: 16px;
        font-weight: 700;
    }
</style>