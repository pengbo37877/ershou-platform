// initial state
var state = {
    books: [],
    nextPageUrl: '',
    currentPage: 1
}

// getters
var getters = {
    userId(state, getters, rootState) {
        return rootState.user.userId;
    },
    userTags(state, getters, rootState) {
        return rootState.user.tags;
    },
    userTagIndex(state, getters, rootState) {
        return rootState.user.tagIndex;
    },
    userPosition(state, getters, rootState) {
        return rootState.user.position;
    }
}

// actions
var actions = {
    change({ commit, getters }, index) {
        console.log('books/change index='+index);
        console.log('books/change user tagIndex='+getters.userTagIndex);
        console.log('books/change user position='+getters.userPosition);
        var tag = getters.userTags[index];
        axios.get('/wx-api/get_books_by_tag/'+tag+'?user='+getters.userId).then(res => {
            commit('setBooks', res.data);
        });
    },

    banBook({ dispatch, commit, getters }, book) {
        if (getters.userId === 0) {
            dispatch('user/getUser').then(res => {
                return new Promise(resolve => {
                    axios.get('/wx-api/ban_book?user=' + getters.userId + "&book=" + book.id).then(res => {
                        if (res.data.code && res.data.code === 500) {

                        } else {
                            commit('removeBook', book);
                        }
                        resolve(res);
                    });
                });
            });
        }else {
            return new Promise(resolve => {
                axios.get('/wx-api/ban_book?user=' + getters.userId + "&book=" + book.id).then(res => {
                    if (res.data.code && res.data.code === 500) {

                    } else {
                        commit('removeBook', book);
                    }
                    resolve(res);
                });
            });
        }
    }
}

// mutations
var mutations = {
    setBooks(state, data) {
        state.books = data.data;
        state.currentPage = data.current_page;
        state.nextPageUrl = data.next_page_url
    },
    addBooks(state, data) {
        state.books = state.books.concat(data.data);
        state.currentPage = data.current_page;
        state.nextPageUrl = data.next_page_url
    },
    removeBook(state, book) {
        var index = state.books.indexOf(book);
        if (index !== -1) {
            state.books.splice(index, 1)
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