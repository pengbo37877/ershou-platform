
/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');
window.Vue = require('vue');

/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

Vue.component('app', require('./components/App.vue'));

Vue.component('inbound', require('./components/Inbound.vue'));
Vue.component('band-hly-code', require('./components/BandHlyCode.vue'));
Vue.component('store-shelf', require('./components/StoreShelf.vue'));

import router from './routes';
import store from './store';
import wxApi from "./share.js";
Vue.prototype.wxApi=wxApi;
router.beforeEach((to, from, next) => {
    /* 路由发生变化修改页面title */
    console.log(to)
    // if (
    //     to.name != "shudan" &&
    //     to.name != "book" &&
    //     to.name != "my" &&
    //     to.name != "user"
    //   ) {
    //       console.log('route:')
    //       console.log(to.name)
    //     wxApi.wxConfig("", "");
    //   }
    if(to.params.tag){
        document.title=to.params.tag;
    }else{
        document.title = to.meta.title  
    }
    next()
});

var app = new Vue({
    el: '#app',
    router,
    store
});
