// initial state
var state = {
    saleItems: []
}

// getters
var getters = {
    recoverSaleItems: (state, getters, rootState) => {
        return state.saleItems.filter((item) => {
            return item.can_recover === 1;
        })
    },
    rejectSaleItems: (state) => {
        return state.saleItems.filter((item) => {
            return item.can_recover === 0 && item.show === 1;
        })
    },
    totalPrice(state, getters) {
        var result = 0;
        _(getters.recoverSaleItems).forEach(function(n){
            result += Number(n.book.price*n.book.discount/100);
        });
        return result.toFixed(2);
    }
}

// actions
var actions = {
    getBooksWithoutCounting({ commit }) {
        return new Promise(resolve => {
            axios.get('/wx-api/get_recover_books_without_counting').then(res => {
                commit('setSaleItems', res.data);
                resolve(res);
            });
        });
    },
    getBooksForRecover({ commit }) {
        return new Promise(resolve => {
            axios.get('/wx-api/get_books_for_recover').then(res => {
                commit('setSaleItems', res.data);
                resolve(res);
            });
        });
    },
    addBookForSale({ commit }, isbn) {
        return new Promise(resolve => {
            axios.post('/wx-api/add_book_for_recover', {
                'isbn': isbn,
            }).then(res => {
                if(res.data.code && res.data.code === 500) {
                }else{
                    commit('addSaleItem', res.data);
                }
                resolve(res);
            });
        })
    },
    addBookForSaleArray({ commit }, data) {
        commit('addSaleItem', data);
    },
    removeBookFromSale({ commit }, saleItem) {
        return new Promise(resolve => {
            axios.post('/wx-api/remove_book_from_recover', {
                'isbn': saleItem.book.isbn,
            }).then(res => {
                if(res.data.code && res.data.code === 500) {
                } else {
                    commit('removeSaleItem', saleItem);
                }
                resolve(res);
            });
        })
    }
}

// mutations
var mutations = {
    setSaleItems(state, items) {
        state.saleItems = items;
    },
    addSaleItem(state, item) {
        state.saleItems.unshift(item)
        console.log('state.saleItems:')
        console.log(state.saleItems)
    },
    removeSaleItem(state, item) {
        var index = state.saleItems.indexOf(item);
        if (index !== -1) {
            state.saleItems.splice(index, 1)
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