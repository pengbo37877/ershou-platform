// initial state
var state = {
    userId: 0,
    user:'',
    users:'',
    latestAddress: '',
    adds: [],
    walletBalance: 0,
    tags:['猜你喜欢', '新上架','豆瓣8.5+','特价市集'],
    nowTags:[],
    position: 0,
    tagIndex: 1,
};

// getters
var getters = {}

// actions
var actions = {
    getUser({ commit, state }) {
        if (state.userId===0) {
            var userId = localStorage.getItem('user_id');
            commit('setUserId', userId);
        }
        if (!state.user) {
            return new Promise(resolve => {
                axios.get('/wx-api/get_user').then(res => {
                    console.log('get user:');
                    console.log(res.data);
                    commit('setUser', res.data);
                    resolve(res);
                });
            })
        }else{
            return new Promise(resolve => {
                resolve(state.user);
            });
        }
    },
    getUsers({ commit, state }) {
        return new Promise(resolve => {
            axios.get('/wx-api/get_user').then(res => {
                console.log('get user:');
                console.log(res.data);
                commit('setUser', res.data);
                resolve(res);
            });
        })
    },
    getUserTags({ commit, state }) {
        var tags = '';
        if (state.userId===0) {
            var userId = localStorage.getItem('user_id');
            commit('setUserId', userId);
        }
        if (!tags ||tag =='') {
            return new Promise(resolve => {
                axios.get('/wx-api/get_user_tags?user=' + state.userId).then(res => {
                    commit('setUserTags', res.data);
                    resolve(res);
                });
            })
        }else{
            commit('setUserTags', tags.split(','));
        }
    },
    addUserTag({ dispatch, commit, state }, tag) {
        if (state.userId===0) {
            var userId = localStorage.getItem('user_id');
            commit('setUserId', userId);
        }
        commit('addUserTag', tag);
        dispatch('books/change', 1, { root: true })
        // axios.post('/wx-api/add_user_tag?user='+state.userId, {
        //     tag: tag
        // }).then(res => {
        //     if(res.data.code && res.data.code === 500) {
        //         this.$message({
        //             message: res.data.msg,
        //             type: 'warning'
        //         });
        //     }else {
        //         commit('addUserTag', tag);
        //         dispatch('books/change', 1, { root: true })
        //     }
        // });
    },
    deleteUserTag({ dispatch, commit, state }, tag) {
        if (state.userId===0) {
            var userId = localStorage.getItem('user_id');
            commit('setUserId', userId);
        }
        commit('deleteUserTag', tag);
        dispatch('books/change', 0, {root: true})
        // axios.post('/wx-api/delete_user_tag?user='+state.userId, {
        //     tag: tag
        // }).then(function(res) {
        //     if(res.data.code && res.data.code === 500) {
        //         this.$message({
        //             message: res.data.msg,
        //             type: 'warning'
        //         });
        //     }else {
        //         commit('deleteUserTag', tag);
        //         dispatch('books/change', 0, {root: true})
        //     }
        // });
    },
    latestAddress({ commit, state }) {
        if (state.userId===0) {
            var userId = localStorage.getItem('user_id');
            commit('setUserId', userId);
        }
        return new Promise(function(resolve) {
            axios.get('/wx-api/get_user_latest_address?user='+state.userId).then(function(res) {
                commit('setUserAddress', res.data);
                resolve(res);
            });
        })
    },
    walletBalance({ commit, state }) {
        if (state.userId===0) {
            var userId = localStorage.getItem('user_id');
            commit('setUserId', userId);
        }
        axios.get('/wx-api/get_user_wallet_balance?user='+state.userId).then(function(res) {
            commit('setWalletBalance', res.data);
        });
    },
    createUserAddress({ commit, state }, form) {
        if (state.userId===0) {
            var userId = localStorage.getItem('user_id');
            commit('setUserId', userId);
        }
        return new Promise(function(resolve) {
            axios.post('/wx-api/create_user_address', {
                user: state.userId,
                form: form
            }).then(function(res) {
                commit('setUserAddress', res.data);
                resolve(res);
            });
        });
    },
    deleteUserAddress({ commit, state }, address) {
        if (state.userId===0) {
            var userId = localStorage.getItem('user_id');
            commit('setUserId', userId);
        }
        return new Promise(function(resolve) {
            axios.post('/wx-api/delete_user_address', {
                user: state.userId,
                address: address.id
            }).then(function(res) {
                resolve(res);
            });
        })
    },
    setDefaultAddress({ commit, state }, address) {
        if (state.userId===0) {
            var userId = localStorage.getItem('user_id');
            commit('setUserId', userId);
        }
        return new Promise(function(resolve) {
            axios.post('/wx-api/set_default_address', {
                user: state.userId,
                address: address.id
            }).then(function(res) {
                resolve(res);
            });
        })
    },
    allUserAddress({ commit, state }) {
        if (state.userId===0) {
            var userId = localStorage.getItem('user_id');
            commit('setUserId', userId);
        }
        return new Promise(resolve => {
            axios.get('/wx-api/get_user_all_address?user='+state.userId).then(function(res) {
                if (res.data.code && res.data.code===500) {

                } else {
                    commit('setAllAddress', res.data);
                }
                resolve(res);
            })
        })
    }
}

// mutations
var mutations = {
    setTagIndex(state, index) {
        state.tagIndex = index;
    },
    setUserId (state, userId) {
        state.userId = parseInt(userId);
    },
    setUser (state, user) {
        state.user = user;
        state.users =user;
        console.log(state.users)
        console.log(state.user)
        if (_.isEmpty(user)) {
            state.userId = 0;
            window.localStorage.setItem('user_id', 0);
        } else {
            state.userId = parseInt(user.id);
            window.localStorage.setItem('user_id', user.id);
        }
    },
    setUserTags (state, tags) {
        state.tags = ['猜你喜欢','新上架'].concat(tags).concat(['豆瓣8.5+','特价市集']);
        state.nowTags =state.tags.concat(tags)
        console.log(tags);
        console.log('after set user tags length='+state.tags.length);
    },
    addUserTag(state, tag) {
        console.log('before add tags length='+state.tags.length);
        state.tags.splice(1,0,tag);
        state.nowTags.splice(1,0,tag);
        console.log('after add tags length='+state.tags.length);
        state.position = 1;
        state.tagIndex = 1;
    },
    deleteUserTag(state, tag) {
        console.log(tag);
        var index = state.tags.indexOf(tag);
        var index2 = state.nowTags.indexOf(tag);
        if (index !== -1) {
            state.tags.splice(index, 1)
        }
        if(index !== -1){
            state.nowTags.splice(index2, 1)
        }
        console.log('delete tags length='+state.tags.length);
        state.position = 0;
        state.tagIndex = 1;
    },
    setPosition(state, position) {
        console.log(position);
        state.position = position;
    },
    setUserAddress(state, address) {
        console.log(address);
        state.latestAddress = address;
    },
    setAllAddress(state, adds) {
        state.adds = adds;
    },
    setWalletBalance(state, balance) {
        console.log(balance);
        state.walletBalance = Number(balance).toFixed(2);
    }
}

export default {
    namespaced: true,
    state,
    getters,
    actions,
    mutations
}