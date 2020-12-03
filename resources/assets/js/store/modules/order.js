// initial state
var state = {
  order: ""
};

// getters
var getters = {
  items(state) {
    return state.order.items;
  },
  suborders(state){
    return state.order.suborders
  },
  itemsCount(state) {
    return state.order.length;
  },
  address(state) {
    return state.order.address;
  },
  orderOriginalPrice(state) {
    var p = 0;
    _(state.order.items).forEach(function(item) {
      p += Number(item.price);
    });
    return p.toFixed(2);
  },
  recoverOrderPrice(state) {
    var p = 0;
    _(state.order.items).forEach(function(item) {
      if (item.review_result === 1) {
        p += Number(item.reviewed_price);
      }
    });
    return p.toFixed(2);
  },
  recoverOrderRejectPrice(state) {
    var p = 0;
    _(state.order.items).forEach(function(item) {
      if (item.review_result === 0) {
        p += Number(item.price);
      }
    });
    return p.toFixed(2);
  },
  orderSaleStatusDesc(state) {
    var desc = "";
    if (state.order.closed) {
      return "已关闭";
    }
    switch (Number(state.order.sale_status)) {
      case -1:
        desc = "已取消";
        break;
      case 10:
        desc = "等待支付";
        break;
      case 20:
        desc = "已支付";
        break;
      case 30:
        desc = "已出库";
        break;
      case 35:
        desc = "已揽件";
        break;
      case 40:
        desc = "已发货";
        break;
      case 70:
        desc = "已签收";
        break;
      default:
        desc = "--::--";
        break;
    }
    return desc;
  },

  orderRecoverStatusDesc(state) {
    var desc = "";
    if (state.order.closed) {
      return "已关闭";
    }
    switch (state.order.recover_status) {
      case -1:
        desc = "已取消";
        break;
      case 10:
        desc = "已下单";
        break;
      case 20:
        desc = "审核中";
        break;
      case 30:
        desc = "已叫顺丰，已预付邮费";
        break;
      case 31:
        desc = "已审核";
        break;
      case 40:
        desc = "已取书";
        break;
      case 50:
        desc = "已收货";
        break;
      case 60:
        desc = "审核打款中";
        break;
      case 70:
        desc = "已完成(已打款)";
        break;
      default:
        desc = "--::--";
        break;
    }
    return desc;
  }
};

// actions
var actions = {
  createSaleOrder({ commit, state }, { address_id, coupon }) {
    return new Promise(resolve => {
      axios
        .post("/wx-api/create_sale_order", {
          address: address_id,
          coupon: coupon ? coupon.id : 0
        })
        .then(res => {
          resolve(res);
        });
    });
  },

  createRecoverOrder({ commit, state }, { address_id, time }) {
    console.log("createRecoverOrder time=" + time);
    return new Promise(resolve => {
      axios
        .post("/wx-api/create_recover_order", {
          address: address_id,
          time: time
        })
        .then(res => {
          resolve(res);
        });
    });
  },

  getSaleOrderWxConfig({ commit }, order_id) {
    return new Promise(resolve => {
      axios
        .post("/wx-api/get_sale_order_wx_config", {
          order: order_id
        })
        .then(res => {
          resolve(res);
        });
    });
  },

  paySaleOrderWithWallet({ commit }, order_id) {
    return new Promise(resolve => {
      axios
        .post("/wx-api/pay_sale_order_with_wallet", {
          order: order_id
        })
        .then(res => {
          resolve(res);
        });
    });
  },

  getOrder({ commit }, no) {
    return new Promise(resolve => {
      axios.get("/wx-api/get_order/" + no).then(res => {
        console.log(res.data);
        commit("setOrder", { order: res.data });
        resolve(res);
      });
    });
  },

  cancelOrder({ commit }, no) {
    return new Promise(resolve => {
      axios.get("/wx-api/cancel_order/" + no).then(res => {
        commit("setOrder", { order: res.data });
        resolve(res);
      });
    });
  },

  updateOrderAddress({ commit, state }, address) {
    axios
      .post("/wx-api/update_sale_order", {
        no: state.order.no,
        address: address.id
      })
      .then(res => {
        commit("setOrder", { order: res.data });
      });
  }
};

// mutations
var mutations = {
  setOrder(state, { order }) {
    state.order = order;
  }
};

export default {
  namespaced: true,
  state,
  getters,
  actions,
  mutations
};
