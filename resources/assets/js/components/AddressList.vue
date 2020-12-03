<template>
  <div class="addressBox">
    <loading :loading="loading"></loading>
    <van-address-list
      v-model="chosenAddressId"
      :list="list"
      :disabled-list="disabledList"
      disabled-text
      @add="onAdd"
      @edit="onEdit"
      @select="onSelect"
    />
  </div>
</template>

<script>
import wx from "weixin-js-sdk";
import { mapGetters, mapState, mapActions } from "vuex";
import Loading from "./Loading";
export default {
  data() {
    return {
      loading: false,
      chosenAddressId: 0,
      disabledList: [],
      list: [],
      screenWidth: 0,
      fo: false,
      from: ""
    };
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
    console.log("from " + this.from);
    this.fo = this.$route.query.fo ? this.$route.query.fo : false;
    console.log(this.$route.query);
    this.initAddresses();
    this.wxApi.wxConfig("", "");
  },
  mounted: function() {
    this.screenWidth =
      window.innerWidth ||
      document.documentElement.clientWidth ||
      document.body.clientWidth;
  },
  methods: {
    initAddresses: function() {
      this.loading = true;
      this.list = [];
      this.$store.dispatch("user/allUserAddress").then(res => {
        this.loading = false;
        if (res.data.code && res.data.code === 500) {
        } else {
          res.data.forEach(add => {
            this.list.push({
              id: add.id,
              name: add.contact_name,
              tel: add.contact_phone,
              address: add.province + add.city + add.district + add.address
            });
          });
          // 初始化chosenAddressId
          if (this.fo) {
            this.chosenAddressId = this.order.address_id;
          } else {
            if (
              this.from === "sale_invoice" ||
              this.from === "recover_invoice"
            ) {
              var add = this.adds.find(add => add.id === this.latestAddress.id);
              if (add) {
                this.chosenAddressId = add.id;
              }
            } else {
              var add = this.adds.find(add => add.is_default === true);
              if (add) {
                this.chosenAddressId = add.id;
              }
            }
          }
        }
      });
    },
    wxConfig: function() {
      var _this = this;
      axios
        .post("/wx-api/config", {
          url: "all_address"
        })
        .then(response => {
          console.log(response.data);
          wx.config(response.data);
          wx.error(res => {
            _this.wxConfig();
          });
          wx.ready(() => {
            console.log("ready");
          });
        });
    },
    useThisAddress: function(ad) {
      this.$store.commit("user/setUserAddress", ad);
      if (this.fo) {
        this.$store.dispatch("order/updateOrderAddress", ad).then(res => {
          this.$router.back();
        });
      }
      if (this.from === "sale_invoice" || this.from === "recover_invoice") {
        this.$router.back();
      }
    },
    setDefaultAddress: function(ad) {
      this.$store.dispatch("user/setDefaultAddress", ad).then(res => {
        this.chosenAddressId = ad.id;
      });
    },
    onAdd() {
      this.$router.push("/wechat/address_edit");
    },
    onEdit(item, index) {
      this.$router.push("/wechat/address_edit?address=" + item.id);
    },
    onSelect(item, index) {
      if (this.fo) {
        this.$dialog
          .confirm({
            title: "确定更改为这个地址吗？",
            message: item.address
          })
          .then(() => {
            // confirm
            var add = this.adds.find(add => add.id === item.id);
            if (add) {
              this.useThisAddress(add);
            }
          })
          .catch(() => {
            this.initAddresses();
          });
      } else if (
        this.from === "sale_invoice" ||
        this.from === "recover_invoice"
      ) {
        this.$dialog
          .confirm({
            title: "确定更改为这个地址吗？",
            message: item.address
          })
          .then(() => {
            // confirm
            var add = this.adds.find(add => add.id === item.id);
            if (add) {
              this.useThisAddress(add);
            }
          })
          .catch(() => {
            this.initAddresses();
          });
      } else {
        this.$dialog
          .confirm({
            title: "设置默认地址？",
            message: item.address
          })
          .then(() => {
            // confirm
            var add = this.adds.find(add => add.id === item.id);
            if (add) {
              this.setDefaultAddress(add);
            }
          })
          .catch(() => {
            this.initAddresses();
          });
      }
    },
    ...mapActions("user", [
      "deleteUserAddress",
      "createUserAddress",
      "setDefaultAddress"
    ])
  },
  components: {
    Loading
  }
};
</script>

<style scoped lang='scss'>
.loading {
  display: flex;
  flex-direction: row;
  justify-content: center;
  align-items: center;
  padding: 10px;
}
.addressBox {
  min-height: 100vh;
  background: #f4f4f4;
}
.addressBox /deep/ .van-address-item .van-radio__icon--checked .van-icon {
  border-color: #41b0dc;
  background-color: #41b0dc;
}
.addressBox /deep/ .van-address-list__add{
    z-index: 999;
    width: 76%;
    height: 46px;
    line-height: 46px;
    text-align: center;
    background: #41b0dc;
    border-radius: 15px;
    position: fixed;
    bottom: 55px;
    left: 50%;
    -webkit-transform: translateX(-50%);
    -ms-transform: translateX(-50%);
    transform: translateX(-50%);
    font-size: 15px;
    font-family: PingFang-SC;
    font-weight: 700;
    color: #fff;
    border-color: #41b0dc;
}
</style>