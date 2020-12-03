<template>
    <div class="recover-book">
        <div class="recover-book-cover">
            <img style="width: 50px;max-height: 75px;" :src="(item.book.cover_replace==null || item.book.cover_replace==='' )?item.book.cover_image:item.book.cover_replace"/>
        </div>
        <div class="recover-book-detail" :style="detailStyle">
            <div class="recover-book-name" :style="detailWidth" v-if="item.can_recover===1">{{item.book.name}}</div>
            <div class="recover-book-name-light" :style="detailWidth" v-if="item.can_recover===0">{{item.book.name}}</div>
            <div class="recover-book-author" :style="detailWidth">{{item.book.author}}</div>
        </div>
        <div class="recover-price" v-if="item.can_recover===1">
            <div class="recover-book-recover-title">
                <popper trigger="click" :options="{placement: 'bottom'}" @show="popShow" @hide="popHide">
                    <div class="popper">
                        <div class="pop-level-wrap">
                            <div class="pop-level" style="width: 100px;border-right: 1px solid #eee;">
                                <div class="pop-title">上好或全新可卖</div>
                                <div class="pop-price">￥{{item.book.price?(Number(item.book.price)*item.book.discount/100).toFixed(2):'10'}}</div>
                            </div>
                            <div class="pop-level" style="margin-left: 10px">
                                <div class="pop-title">中等可卖</div>
                                <div class="pop-price">￥{{item.book.price?(Number(item.book.price)*item.book.discount*0.8/100).toFixed(2):'10'}}</div>
                            </div>
                        </div>
                    </div>

                    <a href="javascript:void(0)" slot="reference" class="pop-a" v-if="popVisible">
                        <van-icon name="arrow-up" />&nbsp;品相好可以卖
                    </a>
                    <a href="javascript:void(0)" slot="reference" class="pop-a" v-else>
                        <van-icon name="arrow-down" />&nbsp;品相好可以卖
                    </a>
                </popper>
            </div>
            <div class="recover-book-recover-price">
                <div style="font-size: 10px;font-weight: 300;">￥</div>
                {{item.book.price?(Number(item.book.price)*item.book.discount/100).toFixed(2):'10'}}
            </div>
            <div class="recover-book-recover-discount">{{Number(Number(item.book.discount)/10).toFixed(1)}}折</div>
            <div class="recover-book-recover-discount" v-if="item.book.volume_count>1">套装书</div>
        </div>
        <div class="recover-price" v-if="item.can_recover===0"></div>
        <div class="recover-book-delete-btn" @click="deleteBook(item)">
            <van-icon name="cross" />
        </div>
        <div class="recover-report" v-if="item.can_recover===0 && item.book.admin_user_id===0">
            <div class="report-btn" v-if="item.recover_reports.length>0"><van-icon name="certificate" size="15px" style="margin-right: 3px"/> 已反馈</div>
            <div class="report-btn report-active" v-if="item.recover_reports.length===0" @click="dialogVisible = true">这本书该收</div>
        </div>

        <van-dialog
                v-model="dialogVisible"
                title="应该收的理由"
                show-cancel-button
                @confirm="recoverReport"
                confirm-button-text="提交"
        >
            <div class="types">
                <div class="type" :class="{'type-active': type===0}" @click="type = 0">内容好</div>
                <div class="type" :class="{'type-active': type===1}" @click="type = 1">绝版书</div>
                <div class="type" :class="{'type-active': type===2}" @click="type = 2">系列书</div>
            </div>
            <div class="reason">
                <van-cell-group>
                    <van-field
                            v-model="reason"
                            type="textarea"
                            placeholder="其他理由..."
                            rows="5"
                            autosize
                    />
                </van-cell-group>
            </div>
            <div class="report-desc">
                反馈后我们会重新审核这本书，如果收购的话会通知你，感谢你帮助我们优化审核算法。
            </div>
        </van-dialog>
    </div>
</template>

<script>
    import { mapState, mapGetters, mapActions} from 'vuex'
    import Popper from 'vue-popperjs';
    import 'vue-popperjs/dist/vue-popper.css';
    export default {
        data() {
            return {
                type: -1,
                book: '',
                reason: '',
                dialogVisible: false,
                popVisible: false
            }
        },
        props: ['item', 'screenWidth'],
        computed: {
            typesStyle: function() {
                return {
                    width: this.screenWidth*0.95-40+'px'
                }
            },
            typeStyle: function() {
                return {
                    width: (this.screenWidth*0.95-55)/3+'px'
                }
            },
            detailStyle: function() {
                return {
                    width: this.screenWidth-100+'px',
                    maxHeight: '80px'
                }
            },
            detailWidth: function() {
                return {
                    width: this.screenWidth-100+'px',
                }
            },
        },
        mounted: function() {
            this.book = this.item.book;
        },
        methods: {
            deleteBook: function(item) {
                var _this = this;
                this.removeBookFromSale(item).then(res => {
                    if (res.data.code && res.data.code === 500) {
                        this.$dialog.alert({
                            message: res.data.msg,
                            center: true
                        });
                    }
                });
            },
            recoverReport: function() {
                if (this.type===-1) {
                    return;
                }
                axios.post('/wx-api/add_recover_report', {
                    'type': this.type,
                    'book_id': this.book.id,
                    'reason': this.reason
                }).then(res => {
                    this.$store.dispatch('sale2hly/getBooksForRecover');
                });
                this.type=-1;
                this.reason='';
                this.dialogVisible = false;
            },
            popShow: function() {
                this.popVisible = true;
            },
            popHide: function() {
                this.popVisible = false;
            },
            ...mapActions('sale2hly', [
                'removeBookFromSale'
            ])
        },
        components: {
            'popper': Popper
        }
    }
</script>

<style>
    .el-dialog--center .el-dialog__body {
        padding: 0
    }
    .el-dialog__body {
        padding: 0
    }
    .el-input {
        width: 80%;
    }
    .el-textarea__inner {
        padding: 5px 8px;
    }
</style>
<style scoped>
    .types {
        display: flex;
        flex-direction: row;
        justify-content: space-around;
        align-items: center;
        margin: 10px 20px;
    }
    .type {
        display: inline-block;
        text-align: center;
        padding: 5px 15px;
        border: 0.5px solid #eee;
        border-radius: 20px;
        font-size: 15px;
    }
    .type-active {
        border: 0.5px solid #3D404A;
    }
    .reason {
        margin: 10px 20px;
    }
    .recover-report {
        display: flex;
        flex-direction: row;
        justify-content: flex-end;
        position: absolute;
        right: 15px;
        bottom: 10px;
    }
    .report-btn {
        padding: 4px 11px;
        border: 0.5px solid #eee;
        border-radius: 20px;
        font-size: 13px;
        color: #888888;
        display: flex;
        flex-direction: row;
        align-items: center;
    }
    .report-active {
        color: #555555;
    }
    .report-desc {
        font-size: 15px;
        font-weight: 300;
        margin: 10px 25px;
        text-align: center;
    }
    .recover-book {
        position: relative;
        display: flex;
        flex-direction: row;
        align-items: center;
        border-bottom: 0.5px solid #ddd;
    }
    .recover-book-cover {
        width: 70px;
        height: 80px;
        padding: 10px 15px;
    }
    .recover-book-detail {
        position: absolute;
        left: 75px;
        top: 10px;
    }
    .recover-book-name {
        font-size: 15px;
        color: #3D404A;
        text-overflow:ellipsis;
        white-space:nowrap;
        overflow:hidden;
    }
    .recover-book-name-light {
        font-size: 15px;
        color: #888888;
        text-overflow:ellipsis;
        white-space:nowrap;
        overflow:hidden;
        flex-grow: 1;
    }
    .recover-book-author {
        font-size: 11px;
        color: #888888;
        text-overflow:ellipsis;
        white-space:nowrap;
        overflow:hidden;
    }
    .recover-book-delete-btn {
        position: absolute;
        top: 13px;
        right: 15px;
        font-size: 18px;
        height: 19px;
        line-height: 19px;
        color: #dddddd;
    }
    .recover-price {
        display: flex;
        flex-direction: row;
        justify-content: flex-end;
        align-items: center;
        position: absolute;
        right: 15px;
        bottom: 10px;
    }
    .recover-book-recover-title {
        font-size: 13px;
        color: #bbbbbb;
        margin-right: 3px;
    }
    .recover-book-recover-price {
        width: fit-content;
        font-size: 18px;
        font-weight: 500;
        color: #ff4848;
        display: flex;
        flex-direction: row;
        align-items: center;
    }
    .recover-book-recover-discount {
        width: fit-content;
        font-size: 10px;
        color: #FD9293;
        border:0.5px solid #ffe0e0;
        border-radius: 4px;
        margin-left: 6px;
        padding: 1px 4px;
    }
    .popper {
        background-color: white;
        -moz-box-shadow: #CCCCCC 0 0 6px 0;
        -webkit-box-shadow: #CCCCCC 0 0 6px 0;
        box-shadow: 0 0 6px 0 #CCCCCC;
    }
    .pop-level-wrap {
        display: flex;
        flex-direction: row;
        padding: 10px;
    }
    .pop-level {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 5px;
    }
    .pop-a {
        color: #888888;
        font-size: 11px;
        display: flex;
        flex-direction: row;
        align-items: center;
    }
    .pop-title {
        font-size: 11px;
        color: #888888;
    }
    .pop-price {
        font-size: 20px;
    }
</style>