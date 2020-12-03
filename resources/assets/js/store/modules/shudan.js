// initial state
var state = {
    opened: [],
    sds: [],
    currentPage: 0,
    lastPage: 0,
    nextPageUrl: '',
    total: 0
}

// getters
var getters = {}

// actions
var actions = {
    getShudans({ commit, state }) {
        return new Promise(resolve => {
            var nextPage = parseInt(state.currentPage)+1;
            axios.get('/wx-api/get_shudan_list?page='+nextPage).then(res => {
                commit('setShudan', res.data);
                resolve(res);
            });
        });
    },
    getOpenedShudan({ commit, state }) {
        return new Promise(resolve => {
            axios.get('/wx-api/get_opened_shudan').then(res => {
                commit('setOpenedShudan', res.data);
                resolve(res);
            });
        });
    },
    submitShudan({ commit, state },data){
        console.log(data)
        return new Promise(resolve => {
            axios.get('/wx-api/add_book_to_shudan/'+data.shudanId+'?book_id='+data.book_id+'&reason='+data.reason).then(res => {
                resolve(res);
            });
        });
    }
}

// mutations
var mutations = {
    setShudan(state, data) {
        state.sds = data.data;
        state.currentPage = data.current_page;
        state.lastPage = data.last_page;
        state.nextPageUrl = data.next_page_url;
        state.total = data.total;
    },
    setOpenedShudan(state, data) {
        state.opened = data;
    },
    submitShudan(state,data){

    }
}

export default {
    namespaced: true,
    state,
    getters,
    actions,
    mutations
}