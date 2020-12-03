// initial state
var state = {
    shelfBooks: [],
    saleBooks: [],
    saleBooksBalance: 0,
    balance: 0,
    orders: [],
    wallets: [],
    showRemind:false //是否显示红点
}

// getters
var getters = {
    userId(state, getters, rootState) {
        return rootState.user.userId;
    }
}

// actions
var actions = {
    getWallets({ commit, getters }) {
        return new Promise(resolve => {
            axios.get('/wx-api/get_wallets?user='+getters.userId).then(res => {
                if (res.data.code && res.data.code===500) {
                    console.log('getWallets '+res.data.msg);
                } else {
                    commit('setWallets', res.data);
                }
                resolve(res);
            })
        });
    },

    getBalance({ commit }) {
        axios.get('/wx-api/get_user_balance').then(res => {
            commit('setBalance', res.data)
        })
    },

    getSaleBalance({ commit }) {
        axios.get('/wx-api/get_user_sale_balance').then(res => {
            commit('setSaleBalance', res.data)
        })
    },

    getShelfBooks({ commit }) {
        return new Promise(resolve => {
            axios.get('/wx-api/get_books_from_shelf').then(res => {
                if (res.data.code && res.data.code === 500) {
                    console.log(res.data.msg)
                } else {
                    commit('setShelfBooks', { books: res.data});
                }
                resolve(res);
            });
        })
    },

    getMyOrders({ commit }) {
        return new Promise(resolve => {
            axios.get('/wx-api/get_my_orders').then(res => {
                console.log(res.data);
                commit('setMyOrders', res.data);
                resolve(res);
            });
        })
    },

    addBookToShelf({commit}, isbn) {
        return new Promise(resolve => {
            axios.post('/wx-api/add_book_to_shelf', {
                'isbn': isbn,
            }).then(res => {
                if (res.data.code && res.data.code === 500) {
                    console.log(res.data.msg)
                } else {
                    commit('addShelfBook', { book: res.data});
                }
                resolve(res);
            });
        })
    },

    removeBookFromShelf({ commit }, book) {
        return new Promise(resolve => {
            axios.post('/wx-api/remove_book_from_shelf', {
                'isbn': book.isbn,
            }).then(res => {
                if(res.data.code && res.data.code === 500) {
                    console.log(res.data.msg)
                } else {
                    commit('removeShelfBook', { book });
                }
                resolve(res);
            });
        })
    },
    transfer() {
        return new Promise(resolve => {
            axios.get('/wx-api/wallet_transfer').then(res => {
                if(res.data.code && res.data.code === 500) {
                    console.log(res.data.msg)
                }
                resolve(res);
            })
        })
    },
    getShowRemind({ commit },email){
        console.log('my.js',email)
        if(email ==1){
            commit('setRemind',true)
        }else{
            commit('setRemind',false)
        }
    },
    clearRemind({commit}){
        return new Promise(resolve =>{
            axios.get('/wx-api/update_coupon_tip').then(res => {
                if(res.data.code && res.data.code === 500) {
                    console.log(res.data.msg)
                }
                commit('setRemind',false)
                resolve(res);
            })
        })
    }
}

// mutations
var mutations = {
    setWallets(state, wallets) {
        state.wallets = wallets;
    },
    setShelfBooks(state, { books }) {
        state.shelfBooks = books;
    },
    setBalance(state, balance) {
        state.balance = balance;
    },
    setSaleBalance(state, balance) {
        state.saleBooksBalance = balance;
    },
    setMyOrders(state, orders) {
        state.orders = orders;
    },
    addShelfBook(state, { book }) {
        state.shelfBooks.unshift(book);
    },
    removeShelfBook(state, { book }) {
        var index = state.shelfBooks.indexOf(book)
        if (index !== -1) {
            state.shelfBooks.splice(index, 1)
        }
    },
    setRemind(state,flag){
        state.showRemind =flag
    }
}

export default {
    namespaced: true,
    state,
    getters,
    actions,
    mutations
}