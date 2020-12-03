// initial state
var state = {
    user:'',
    soldBooksIncome: 0,
    shelfBooks: [],
    soldBooks: []
};

// getters
var getters = {}

// actions
var actions = {
    getUser({ commit }, { openId }) {
        return new Promise(resolve => {
            axios.get('/wx-api/get_user/'+ openId).then(res => {
                commit('setUser', res.data);
                resolve(res);
            });
        })
    },
    getSoldBooksIncome({ commit }, { openId }) {
        axios.get('/wx-api/get_user_sold_books_income/' + openId).then(res => {
            commit('setSoldBooksIncome', res.data);
        });
    },
    getShelfBooks({ commit }, { openId }) {
        return new Promise(resolve => {
            axios.get('/wx-api/get_user_shelf_books/' + openId).then(res => {
                if (res.data.code && res.data.code === 500) {
                    console.log(res.data.msg)
                } else {
                    commit('setShelfBooks', { books: res.data});
                }
                resolve(res);
            });
        })
    },
    getSoldBooks({ commit }, { openId }) {
        return new Promise(resolve => {
            axios.get('/wx-api/get_user_sold_books/' + openId).then(res => {
                if (res.data.code && res.data.code === 500) {
                    console.log(res.data.msg)
                } else {
                    commit('setSoldBooks', { books: res.data});
                }
                resolve(res);
            });
        })
    },
}

// mutations
var mutations = {
    setUser (state, user) {
        state.user = user
    },
    setSoldBooksIncome(state, income) {
        console.log(income);
        state.soldBooksIncome = Number(income).toFixed(2);
    },
    setShelfBooks(state, { books }) {
        state.shelfBooks = books;
    },
    setSoldBooks(state, { books }) {
        state.soldBooks = books;
    }
}

export default {
    namespaced: true,
    state,
    getters,
    actions,
    mutations
}