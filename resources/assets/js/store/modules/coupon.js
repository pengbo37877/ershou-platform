// initial state
var state = {
    coupons: []
}

// getters
var getters = {
    recoverCoupons: (state, getters, rootState) => {
        return state.coupons.filter((coupon) => {
            return coupon.order_type == 'recover';
        })
    },
    saleCoupons: (state, getters, rootState) => {
        return state.coupons.filter((coupon) => {
            return coupon.order_type == 'sale';
        })
    },
    vanRecoverCoupons: (state, getters, rootState) => {
        var coupons = state.coupons.filter((coupon) => {
            return coupon.order_type == 'recover';
        });
        var result = [];
        coupons.forEach((coupon) => {
            result.push({
                'id': coupon.id,
                'name': coupon.name,
                'available': coupon.enabled,
                'discount': coupon.type=='fixed'?0:coupon.value,
                'denominations': Number(coupon.value)*100,
                'originCondition': Number(coupon.min_amount)*100,
                'startAt': coupon.not_before?(new Date(coupon.not_before).getTime())/1000:(new Date().getTime())/1000,
                'endAt': coupon.not_after?(new Date(coupon.not_after).getTime())/1000:(new Date().getTime())/1000,
                'description': '',
                'reason': '已过期',
                'value': Number(coupon.value)*100,
            });
        });
        console.log('vanRecoverCoupons:\n'+JSON.stringify(result));
        return result;
    },
    vanSaleCoupons: (state, getters, rootState) => {
        var coupons = state.coupons.filter((coupon) => {
            return coupon.order_type == 'sale';
        });
        var result = [];
        coupons.forEach((coupon) => {
            result.push({
                'id': coupon.id,
                'name': coupon.name,
                'available': coupon.enabled,
                'discount': coupon.type=='fixed'?0:coupon.value,
                'denominations': Number(coupon.value)*100,
                'originCondition': Number(coupon.min_amount)*100,
                'condition': '满'+Number(coupon.min_amount)+'可用',
                'startAt': coupon.not_before?(dayjs(coupon.not_before).toDate().getTime())/1000:(new Date())/1000,
                'endAt': coupon.not_after?(dayjs(coupon.not_after).toDate().getTime())/1000:(new Date())/1000,
                'description': '',
                'reason': '邀请的用户还没下单或者订单不满'+coupon.min_amount,
                'value': Number(coupon.value)*100,
                'valueDesc': Number(coupon.value)+'',
                'unitDesc': '元',
                'used':coupon.used,
                'order_type':coupon.order_type
            });
        });
        // console.log('vanSaleCoupons:\n'+JSON.stringify(result));
        return result;
    }
}

// actions
var actions = {
    getCoupons({ commit, getters }) {
        return new Promise(resolve => {
            axios.get('/wx-api/get_coupons').then(res => {
                console.log('coupons==');
                console.log(res.data);
                commit('setCoupons', res.data);
                resolve(res);
            });
        })
    }
}

// mutations
var mutations = {
    setCoupons(state, data) {
        state.coupons = data;
    }
}

export default {
    namespaced: true,
    state,
    getters,
    actions,
    mutations
}
