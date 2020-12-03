// initial state
var state = {
    q: '',
    data:{},
    tags: [],
    books: [],
    currentPage: 0,
    nextPageUrl: '',
    book:''//推荐的书
};

// getters
var getters = {
    getBooks(state,getters){
        return state.books
    }
}

// actions
var actions = {
    getRecommendTags({ commit }) {
        axios.get('/wx-api/get_recommend_tags').then(res => {
            commit('setTags', res.data);
        });
    },
    searchBooks({ commit }, data) {
        commit('setQ', data.q);
        commit('setData', data);
        if (data.q === '') {
            commit('setBooks', {
                data: [],
                current_page: 0,
                next_page_url: ''
            });
        } else if(typeof data.q === 'object') {
            return;
        }else {
            return new Promise(resolve => {
                axios.post('/wx-api/search_book_by_str', {
                    q:data.q,
                    discount:data.discount,
                    price:data.price,
                    rating:data.rating,
                    level:data.level
                }).then(res => {
                    commit('setBooks', res.data);
                    resolve(state.books);
                });
            })
        }
    },
    searchMore({ commit, state }) {
        const data= state.data;
        return new Promise(resolve => {
            axios.post(state.nextPageUrl,{
                q:state.data.q,
                discount:data.discount,
                price:data.price,
                rating:data.rating,
                level:data.level
            }).then(res => {
                commit('addBooks', res.data);
                resolve(state.books);
            });
        })
    },
    // 书单推荐搜索选中书籍
    selectBook({commit},book){
        commit('selectBooks',book)
    },
    getBook({commit}){
        commit('getBook')
    }
}

// mutations
var mutations = {
    setTags(state, tags) {
        state.tags = tags
    },
    setQ(state, value) {
        state.q = value;
    },
    setData(state, data) {
        state.data = data;
    },
    selectBooks(state,book){
        state.book=book
    },
    setBooks(state, data) {
        if (data.data) {
            state.books = data.data;
            state.currentPage = data.current_page;
            state.nextPageUrl = data.next_page_url
        }else{
            state.books = [];
            state.currentPage = 1;
            state.nextPageUrl = '';
        }
        console.log('books',state.books)
    },
    addBooks(state, data) {
        if (data.data) {
            state.books = state.books.concat(data.data);
            state.currentPage = data.current_page;
            state.nextPageUrl = data.next_page_url
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