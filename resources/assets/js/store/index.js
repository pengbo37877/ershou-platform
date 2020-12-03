import Vue from 'vue'
import Vuex from 'vuex'
import order from './modules/order'
import sale2hly from './modules/sale2hly'
import cart from './modules/cart'
import books from './modules/books'
import user from './modules/user'
import shudan from './modules/shudan'
import search from './modules/search'
import search2 from './modules/search2'
import my from './modules/my'
import user4share from './modules/user4share'
import coupon from './modules/coupon'
import jzs from './modules/jzs'
import shop from './modules/shop'

Vue.use(Vuex);

export default new Vuex.Store({
    modules: {
        order,
        sale2hly,
        cart,
        books,
        user,
        shudan,
        search,
        search2,
        my,
        user4share,
        coupon,
        jzs,
        shop
    }
})