// initial state
var state = {
    hotBooks: [],
    shudanInfo:[],
    scrollTop:0
}

// getters
var getters = {
    hotBooks(state,getters){
        return state.hotBooks
    },
    shudan(state,getters){
        return state.shudanInfo
    },
    scrollTop(state,getters){
        return state.scrollTop
    }
}

// actions
var actions = {
    getHotBooks({ dispatch, commit, getters }) {
          return new Promise(resolve=>{
            axios.get("/wx-api/get_bestseller?page=1").then(res => {
                console.log(res.data);
                commit('setHotBooks',res.data.data);
                resolve(res);
              });
          })
    },
    getShudanInfo({dispatch, commit, getters }){
        return new Promise(resolve=>{
            axios.get("/wx-api/shudan_users").then(res => {
                console.log(res.data);
                that.shudanInfo = res.data.data;
                commit('setShudanInfo',res.data.data)
                resolve(res)
              });
        })
    }
}

// mutations
var mutations = {
    
    setHotBooks(state,data){
        state.hotBooks =data
    },
    setShudanInfo(state,data){
        state.shudanInfo =data
    },
    setScrollTop(state,num){
        console.log(num)
        state.scrollTop=num
    }
}

export default {
    namespaced: true,
    state,
    getters,
    actions,
    mutations
}