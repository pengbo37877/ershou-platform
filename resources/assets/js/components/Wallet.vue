<template>
    <div>
        <div class="balance">
            <div class="balance-title">
                账户余额(元)
            </div>
            <div class="balance-text">{{balance}}</div>
            <div class="balance-btn" v-if="balance<1">余额提现</div>
            <div class="balance-btn active" @click="transferDialog" v-else>余额提现</div>
        </div>
        <loading :loading="loading"></loading>
        <div class="balance-list">
            <van-cell
                    v-for="item in wallets"
                    :key="item"
                    :title="buildType(item)"
                    :value="item.amount"
                    :label="buildLabel(item)"
            />
        </div>
        <van-dialog
                v-model="dialogVisible"
                title="提现到微信"
                show-cancel-button
                @confirm="doTransfer"
                @cancel="buyBook"
                cancel-button-text="我要去买书"
                confirm-button-text="全部提现"
                confirm-button-color="#3D404A"
                :closeOnClickOverlay="true"
        >
        </van-dialog>
    </div>
</template>
<style scoped>
    body{
        background-color: white;
    }
    .balance {
        padding: 50px 10px;
        border-bottom: 0.5px solid #eee;
        background: rgba(250,250,250,1);
background: -moz-linear-gradient(top, rgba(250,250,250,1) 0%, rgba(255,249,240,1) 47%, rgba(240,225,204,1) 100%);
background: -webkit-gradient(left top, left bottom, color-stop(0%, rgba(250,250,250,1)), color-stop(47%, rgba(255,249,240,1)), color-stop(100%, rgba(240,225,204,1)));
background: -webkit-linear-gradient(top, rgba(250,250,250,1) 0%, rgba(255,249,240,1) 47%, rgba(240,225,204,1) 100%);
background: -o-linear-gradient(top, rgba(250,250,250,1) 0%, rgba(255,249,240,1) 47%, rgba(240,225,204,1) 100%);
background: -ms-linear-gradient(top, rgba(250,250,250,1) 0%, rgba(255,249,240,1) 47%, rgba(240,225,204,1) 100%);
background: linear-gradient(to bottom, rgba(250,250,250,1) 0%, rgba(255,249,240,1) 47%, rgba(240,225,204,1) 100%);
filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#fafafa', endColorstr='#f0e1cc', GradientType=0 );
    }
    .balance-title {
        color: #d6af70;
        font-size: 14px;
        font-weight: 400;
        display: flex;
        flex-direction: row;
        justify-content: center;
    }
    .balance-text {
        color: black;
        font-size: 30px;
        text-align: center;
        font-weight: 700;
    }
    .balance-btn {
        height: 40px;
        line-height: 40px;
        border-radius: 4px;
        background-color: #EFE4D6;
        color: white;
        display: flex;
        flex-direction: row;
        justify-content: center;
        margin: 5px 60px;
    }
    .active {
        background-color: #d6af70
    }
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
</style>
<script>
    import { Toast } from 'vant';
    import Loading from './Loading'
    import { mapState, mapGetters, mapActions} from 'vuex'
    export default{
        data(){
            return{
                loading: false,
                dialogVisible: false,
                count: 0,
                transferring: false
            }
        },
        computed: {
            ...mapState({
                balance: state => state.my.balance,
                wallets: state => state.my.wallets
            }),
        },
        created: function() {
            this.wxApi.wxConfig('','');
            this.$store.dispatch('user/getUser').then(res => {
                // 如果拿不到用户，就显示一个不可关闭的对话框
                var user = res.data;
                if (user===''||user.length===0) {
                    this.$router.replace('/wechat/shop');
                } else if(user.subscribe===0) {
                    this.$router.replace('/wechat/shop')
                }
            });
            this.$store.dispatch('my/getBalance');
            this.loading = true;
            this.$store.dispatch('my/getWallets').then(res=>{
                this.loading=false;
                if (res.data.code && res.data.code===500) {
                    this.$dialog.alert(res.data.msg);
                }
            });
        },
        mounted: function() {
            this.$nextTick(() => {
                window.scrollTo(0, 1)
                window.scrollTo(0, 0)
            })
        },
        activated: function() {
            this.$store.dispatch('my/getBalance');
            this.$store.dispatch('my/getWallets').then(res=>{
                if (res.data.code && res.data.code===500) {
                    this.$dialog.alert(res.data.msg);
                }
            });
        },
        methods: {
            buildLabel: function(item) {
                if (item.status===1){
                    return item.created_at+' 成功';
                } else if(item.status===0) {
                    if (typeof(item.result) === 'string') {
                        return "失败 "+JSON.parse(item.result);
                    }
                    if (_.isEmpty(item.result)) {
                        return "失败";
                    }else if (!_.isEmpty(item.result.return_msg)) {
                        return "失败 "+item.result.return_msg;
                    }else{
                        return "失败 "+item.result.reason;
                    }
                }else{
                    if(_.isEmpty(item.result)) {
                        return "失败";
                    } else if (!_.isEmpty(item.result.return_msg)) {
                        return "失败 "+item.result.return_msg;
                    }else{
                        return "失败 "+item.result.reason;
                    }
                }
            },
            buildType: function(item) {
                // const TYPE_SALE_BOOK = 1;
                // const TYPE_BUY_BOOK = 2;
                // const TYPE_TRANSFER_OUT = 3;
                // const TYPE_TRANSFER_IN = 4;
                // const TYPE_BUY_BOOK_REFUND = 5;
                var type = item.type;
                var result = '';
                if (type===1){
                    result = '卖书收入';
                }else if (type===2){
                    result = '买书支出';
                }else if(type===3){
                    result = '提现';
                }else if(type===4) {
                    result = '充值';
                }else if(type===5) {
                    result = '买书退款';
                }
                var reg = new RegExp("[a-zA-Z]");
                var r = reg.test(item.memo);
                if (!r) {
                    result = result + ' - ' + item.memo;
                }
                return result;
            },
            transferDialog: function() {
                this.dialogVisible = true;
            },
            buyBook: function() {
                console.log("buy book");
                this.dialogVisible = false;
                this.$router.replace('/wechat/shop');
            },
            doTransfer: function() {
                if (this.transferring) {
                    return;
                }
                this.transferring = true;
                const toast = Toast.loading({
                    duration: 0,       // 持续展示 toast
                    forbidClick: true, // 禁用背景点击
                    loadingType: 'spinner',
                    message: '提现中...'
                });
                this.$store.dispatch('my/transfer').then(res => {
                    toast.clear();
                    if (res.data.code && res.data.code === 500) {
                        this.$dialog.alert({
                            message: res.data.msg,
                            center: true
                        });
                        return;
                    }
                    this.$notify({
                        message: '提现成功',
                        duration: 3000,
                        background: '#1AB32B'
                    });
                    this.$store.commit('my/setBalance', '0.00');
                    this.$store.dispatch('my/getBalance');
                    this.transferring = false;
                    this.dialogVisible = false;
                })
            },
        },
        components: {
            Loading
        }
    }
</script>
