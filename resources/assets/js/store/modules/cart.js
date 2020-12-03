// initial state
// shape: [{ id, quantity }]
var state = {
    items: [],
    reminders: [],
    recommends: [],
    cart_counts:[],
    checkoutStatus: null,
    order: ''
}

// getters
var getters = {
    // 用来显示无货
    soldItems: (state, getters, rootState) => {
        return state.items.filter((item) => {
            return _.isEmpty(item.book_sku) || item.book_sku.status != 1;
        })
    },
    // 用来显示可以购买的
    sellingItems: (state, getters, rootState) => {
        return state.items.filter((item) => {
            return item.book_sku && item.book_sku.status == 1;
        })
    },
    allSelected: (state, getters, rootState) => {
        return getters.sellingItems.length == getters.selectedItems.length
    },
    // 用来提示有货的
    sellingReminders: (state, getters, rootState) => {
        return state.reminders.filter((reminder) => {
            // sku数量多于1，还不在用户的购物车中
            var item = state.items.find(i => i.book_id == reminder.book_id);
            return reminder.book.for_sale_skus.length>0 && !item;
        });
    },
    selectedItems: (state, getters, rootState) => {
        return getters.sellingItems.filter((item) => {
            return item.selected==1
        })
    },                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           
    selectedPrice: (state, getters) => {
        var totalPrice = getters.selectedItems.reduce((total, item) => {
        return total + Number(item.book_sku.price);
        }, 0);
        return Number(totalPrice).toFixed(2);
    },
    userAddress: (state, getters, rootState) => {
        return rootState.user.latestAddress;
    }
}

// actions
var actions = {

    items({ commit }) {
        return new Promise(resolve => {
            axios.get('/wx-api/get_user_cart_items').then(res => {
                commit('setCartItems', { items: res.data });
                resolve(res);
            });
        });
    },
    cartItems({commit}){
        return new Promise(resolve => {
            axios.get('/wx-api/get_user_cart_books').then(res => {
                commit('setCartCounts', { cart_counts: res.data.book_ids });
                resolve(res);
            });
        });
    },
    reminders({ commit }) {
        return new Promise(resolve => {
            axios.get('/wx-api/get_user_reminders').then(res => {
                commit('setReminders', { reminders: res.data });
                resolve(res);
            });
        });
    },

    recommends({ commit }) {
        return new Promise(resolve => {
            axios.get('/wx-api/get_cart_recommends').then(res => {
                commit('setRecommends', { recommends: res.data });
                resolve(res);
            });
        })
    },

    addSkuToCart({ commit }, { sku, source }) {
        return new Promise(resolve => {
            axios.post('/wx-api/add_sku_to_cart', {
                sku: sku.id,
                source
            }).then(res => {
                console.log('获取的cart item')
                console.log(res.data)
                if(res.data.code && res.data.code == 500) {
                    console.log('添加Sku到购物袋失败：'+res.data.msg);
                }else{
                    commit('addCartItem', { item: res.data });
                    commit('addCartBooks',res.data.book_id)
                }
                resolve(res);
            });
        })
    },

    changeCartItemSelect({ commit, getters }, { item, selected }) {
        axios.post('/wx-api/select_cart_item', {
            item: item.id,
            selected: selected
        }).then(res => {
            if (res.data.code == 500){

            } else {
                commit('setCartItemSelect', { item, selected })
                console.log('changeCartItemSelect之后的selectedItems');
                console.log(getters.selectedItems);
            }
        });
    },

    updateCartItem({ commit }, { item, book_sku_id, cb }){
        console.log('update sku_id='+book_sku_id);
        axios.post('/wx-api/update_cart_item', {
            item: item.id,
            sku_id: book_sku_id
        }).then(res => {
            if(res.data.code && res.data.code == 500) {

            }else{
                commit('updateCartItem', { item: res.data })
            }
            cb(res.data);
        });
    },

    deleteCartItem({ commit }, { item }) {
        axios.post('/wx-api/delete_cart_item', {
            item: item.id
        }).then(res => {
            if(res.data.code && res.data.code == 500) {
                this.$message({
                    message: res.data.msg,
                    type: 'warning'
                });
            }else{
                commit('removeCartItem', { item })
                commit('removeCartBooks',item.book_id)
            }
        });
    },

    addBookToReminder({ commit }, { book }) {
        return new Promise(resolve => {
            axios.post('/wx-api/add_book_to_reminder', {
                book: book.id
            }).then(res => {
                console.log('获取的reminder:'+JSON.stringify(res.data));
                commit('addReminder', { reminder: res.data });
                resolve(res);
            });
        });
    },

    removeBookFromReminder({ commit }, { book }) {
        return new Promise(resolve => {
            axios.post('/wx-api/remove_book_from_reminder', {
                book: book.id
            }).then(res => {
                console.log('删除的reminder');
                console.log(res.data);
                commit('removeReminder', { reminder: res.data });
                resolve(res);
            });
        });
    }
}

// mutations
var mutations = {

    setCartItems (state, { items }) {
        state.items = items;
        console.log(items)
    },
    setCartCounts (state,{cart_counts}){
        state.cart_counts =cart_counts
    },
    setCheckoutStatus (state, status) {
        state.checkoutStatus = status
    },

    setReminders (state, { reminders }) {
        state.reminders = reminders;
        console.log(reminders)
    },

    setRecommends (state, { recommends }) {
        state.recommends = recommends;
        console.log(recommends)
    },

    addCartItem(state, { item }) {
        state.items.splice(0,0,item);
    },
    addCartBooks(state,book_id){
        var id =book_id.toString()
        state.cart_counts.splice(0,0,id);
    },
    updateCartItem(state, { item }) {
        var i = state.items.find(it => it.id == item.id)
        var index = state.items.indexOf(i);
        if (index !== -1) {
            state.items.splice(index,1,item);
        }
    },

    removeCartItem(state, { item }) {
        var index = state.items.indexOf(item);
        if (index !== -1) {
            state.items.splice(index, 1)
        }
    },
    removeCartBooks(state,book_id){
        var index =state.cart_counts.indexOf(book_id);
        if(index!==-1){
            state.cart_counts.splice(index,1);
        }
    },
    setCartItemSelect(state, { item, selected }) {
        console.log('setCartItemSelect='+selected);
        item.selected = selected;
    },

    addReminder(state, { reminder }) {
        state.reminders.splice(0,0,reminder);
    },

    removeReminder(state, { reminder }) {
        var r = state.reminders.find(item => item.id == reminder.id);
        var index = state.reminders.indexOf(r);
        if (index !== -1) {
            state.reminders.splice(index, 1);
        }
    }
}

export default {
    namespaced: true,
    state,
    getters,
    actions,
    mutations
}