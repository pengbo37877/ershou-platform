<template>
    <div>
        <div class="address-notify" v-if="from==='sale_invoice'">
            请填写你的收货地址
        </div>
        <div class="address-notify" v-else-if="from==='recover_invoice'">
            回流鱼会安排顺风上门，请填写你的取货地址
        </div>
        <div class="address-notify" v-else>
            新增地址，越详细越好
        </div>
        <el-form ref="form" :model="form" :rules="rules" label-width="60px">
            <el-form-item label="姓名" prop="contact_name">
                <el-input v-model="form.contact_name"></el-input>
            </el-form-item>
            <el-form-item label="电话" prop="contact_phone">
                <el-input v-model="form.contact_phone"></el-input>
            </el-form-item>
            <el-form-item label="地址" prop="address">
                <div id="trigger5" v-model="addressDetail" class="form-address">{{addressDetail}}</div>
                <el-input type="textarea" v-model="form.address" placeholder="街道门牌号" style="margin-top:5px;"></el-input>
            </el-form-item>
            <el-form-item>
                <div class="add-btn" @click="submit('form')">添加</div>
            </el-form-item>
        </el-form>
    </div>
</template>

<script>
    import MobileSelect from 'mobile-select';
    import {districts} from '../saleDistrict.js';
    import { mapGetters, mapState, mapActions } from 'vuex';
    export default {
        data() {
            var validateName = (rule, value, callback) => {
                if(this.form.contact_name === '') {
                    callback(new Error('请填写姓名'));
                }else if(this.form.contact_name.length < 2) {
                    callback(new Error('姓名至少两个字'));
                }else{
                    callback();
                }
            };
            var validateAddress = (rule, value, callback) => {
                if(this.form.province === '') {
                    callback(new Error('请选择省市区'));
                }else if(this.form.address === '') {
                    callback(new Error('请输入详细地址'));
                }else if(this.form.address.length < 4) {
                    callback(new Error('详细地址至少4个字'));
                }else{
                    callback();
                }
            };
            var validatePhone = (rule, value, callback) => {
                var re = /^1\d{10}$/
                if (re.test(value)) {
                    callback();
                } else {
                    callback(new Error('请输入有效的手机号'));
                }
            };
            return {
                screenWidth: 0,
                from: '',
                form: {
                    contact_name: '',
                    contact_phone: '',
                    province: '',
                    city: '',
                    district: '',
                    address: '',
                },
                rules: {
                    contact_name: [
                        { validator: validateName, trigger: 'blur' }
                    ],
                    contact_phone: [
                        { validator: validatePhone, trigger: 'change' }
                    ],
                    address: [
                        { validator: validateAddress, trigger: 'change' }
                    ],
                    time: [
                        { required: true, message: '请选择上门时间', trigger: 'change' }
                    ]
                }
            }
        },
        computed: {
            addressDetail: function() {
                return this.form.province + this.form.city + this.form.district;
            }
        },
        created: function () {
            this.from = this.$route.query.from;
            this.wxApi.wxConfig('','');
            console.log('from '+this.from);
        },
        mounted: function() {
            this.screenWidth = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
            var _this = this;
            var mobileSelect5 = new MobileSelect({
                trigger: "#trigger5",
                title: "地区选择",
                wheels: [
                    {data:districts}
                ],
                callback:function(indexArr, data){
                    _this.form.province = data[0].value;
                    _this.form.city = data[1].value;
                    _this.form.district = data[2].value;
                    console.log('获取的地址数据是：'+JSON.stringify(_this.form.province+' '+_this.form.city+' '+_this.form.district));
                }
            });
        },
        methods: {
            submit: function(formName) {
                this.$refs[formName].validate((valid) => {
                    if (valid) {
                        console.log('form:'+JSON.stringify(this.form));
                        this.createUserAddress(this.form);
                        if (this.from === 'sale_invoice') {
                            this.$router.replace('/wechat/sale_invoice')
                        } else if( this.from === 'recover_invoice') {
                            this.$router.replace('/wechat/recover_invoice')
                        }else {
                            this.$router.back();
                        }
                    } else {
                        console.log('error submit!!');
                        return false;
                    }
                });
            },
            ...mapActions('user', [
                'createUserAddress'
            ])
        }
    }
</script>
<style>
    .el-form {
        padding: 20px 20px 20px 5px;
    }
</style>
<style scoped>
    .address-notify {
        display: flex;
        flex-direction: row;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        color: #f08f91;
        padding: 5px;
        background-color: #feecec;
        font-weight: 300;
    }
    .form-address {
        -webkit-appearance: none;
        background-color: #fff;
        background-image: none;
        border-radius: 4px;
        border: 1px solid #dcdfe6;
        -webkit-box-sizing: border-box;
        box-sizing: border-box;
        color: #606266;
        display: inline-block;
        font-size: inherit;
        height: 40px;
        line-height: 40px;
        outline: 0;
        padding: 0 15px;
        -webkit-transition: border-color .2s cubic-bezier(.645,.045,.355,1);
        transition: border-color .2s cubic-bezier(.645,.045,.355,1);
        width: 100%;
    }
    .add-btn {
        font-size: 14px;
        color: white;
        text-align: center;
        background-color: #3D404A;
        border-radius: 4px;
    }
</style>