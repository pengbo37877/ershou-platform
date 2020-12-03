// initial state
var state = {
    jzs: [],
    nextPageUrl: '',
    currentPage: 1
}

// getters
var getters = {

}

// actions
var actions = {
    getJzs({ commit, getters }) {
        axios.get('/wx-api/get_jzs').then(res => {
            commit('setJzs', res.data);
        });
    }
}

// mutations
var mutations = {
    setJzs(state, data) {
        state.jzs = data.data;
        state.currentPage = data.current_page;
        state.nextPageUrl = data.next_page_url
    },
    addJzs(state, data) {
        state.jzs = state.jzs.concat(data.data);
        state.currentPage = data.current_page;
        state.nextPageUrl = data.next_page_url
    }
}

export default {
    namespaced: true,
    state,
    getters,
    actions,
    mutations
}