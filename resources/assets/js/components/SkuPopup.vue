<template>
    <div>
        <van-popup v-model="dialogVisible" position="bottom">
            <div class="pop-top">
                <div class="pop-name">{{book.name}}</div>
                <div class="pop-close" @click="dialogVisible=false">
                    <van-icon name="cross" size="20px"/>
                </div>
            </div>
            <div class="pop-desc">
                <div>多个品相的书可以购买</div>
                <div style="color:#555;" @click="goLevelDesc">
                    不同品相有何区别？<van-icon name="arrow" />
                </div>
            </div>
            <div class="pop-items">
                <div class="pop-item" :class="activeSkuStyle(index)" v-for="(sku,index) in items" @click="chooseSku(index)">
                    <div class="pop-item-price">￥{{sku.price}}</div>
                    <div class="pop-item-desc">{{sku.title}}</div>
                </div>
            </div>
            <div class="pop-bottom">
                <van-button square type="warning" style="border-radius: 4px" size="large" loading v-if="addingToCart">加入购物袋</van-button>
                <van-button square type="warning" style="border-radius: 4px" size="large" @click="addToCart" v-if="!addingToCart">加入购物袋</van-button>
            </div>
        </van-popup>
    </div>
</template>

<script>
    import {mapState, mapActions} from 'vuex';
    export default {
        data() {
            return {
                dialogVisible: false,
                addingToCart: false,
            }
        },
        props: ['book', 'items', 'selectedIndex', 'version', 'selectedSku', 'source'],
        computed: {
            ...mapState({
                user: state => state.user.user,
            })
        },
        methods: {
            goLevelDesc: function() {
                this.dialogVisible = false;
                this.$router.push('/wechat/level_desc')
            },
            chooseSku: function(index) {
                this.selectedIndex = index;
                this.version = this.items[index].book_version;
                this.selectedSku = this.items[index];
            },
            activeSkuStyle: function(index) {
                if(index===this.selectedIndex) {
                    return 'pop-item-active'
                }
            },
            addToCart: function() {
                this.addingToCart = true;
                if (this.user===''||this.user.length===0) {
                    return;
                }
                var sku = this.items[this.selectedIndex];
                var _this = this;
                this.addSkuToCart({ sku, source:this.source }).then(res => {
                    if (res.data.code && res.data.code === 500) {
                        this.$dialog.alert({
                            message: res.data.msg,
                            center: true
                        });
                    }
                    _this.dialogVisible = false;
                    _this.addingToCart = false;
                })
            },
            ...mapActions('cart', [
                'addSkuToCart'
            ])
        }
    }
</script>

<style scoped>
    .pop-top {
        display: flex;
        flex-direction: row;
        align-items: center;
        padding: 15px 20px;
        position: relative;
    }
    .pop-name {
        font-size: 18px;
        text-overflow:ellipsis;
        white-space:nowrap;
        overflow:hidden;
        width: 80%;
    }
    .pop-close {
        position: absolute;
        top: 15px;
        right: 20px;
        color: #aaaaaa;
    }
    .pop-desc {
        display:flex;
        flex-direction:row;
        justify-content:space-between;
        color:#ccc;
        font-size:13px;
        padding: 0 20px 10px 20px;
    }

    .pop-items {
        display:flex;
        flex-direction: column;
        padding: 5px 20px 10px 20px;
    }
    .pop-item {
        border:2px solid #eee;
        border-radius:4px;
        margin-bottom: 10px;
        height: 60px;
        line-height: 60px;
        padding: 0 10px;
        display: flex;
        flex-direction: row;
        align-items: center;
    }

    .pop-bottom {
        padding: 0 20px 20px 20px;
    }
    .pop-item-active {
        border:2px solid #ff4848;
    }
    .pop-item-price {
        display: flex;
        flex-direction: row;
        align-items: center;
        font-size: 22px;
        font-weight: 500;
        color: #3D404A;
    }
    .pop-item-desc {
        font-size: 14px;
        font-weight: 300;
        color: #888;
        margin-left: 10px;
        line-height: 18px;
    }
    .bd-level-dialog {
        position:fixed;
        bottom:0;
        left:0;
        margin-bottom: -1px;
    }
</style>