import VueRouter from 'vue-router';

var routes = [{
    path: '/wechat/shop',
    component: require('./components/Shop'),
    meta: {
        keepAlive: false,
        title: '回流鱼'
    },
    name: 'shop'
},
{
    path: '/wechat/shop2',
    component: require('./components/Shop2'),
    meta: {
        keepAlive: true,
        title: '回流鱼'
    },
    name: 'shop2'
},
{
    path: '/wechat/shop3',
    component: require('./components/Shop3'),
    meta: {
        keepAlive: true,
        title: '回流鱼'
    },
    name: 'shop3'
},
{
    path: '/pc/shop',
    component: require('./components/pc/shop'),
    meta: {
        keepAlive: true,
        title: '回流鱼'
    },
    name: 'pc_shop'
},
{
    path: '/wechat/cart',
    component: require('./components/Cart'),
    meta: {
        keepAlive: false,
        title: '购物袋'
    },
    name: 'cart'
},
{
    path: '/wechat/cart2',
    component: require('./components/Cart2'),
    meta: {
        keepAlive: false,
        title: '购物袋'
    },
    name: 'cart2'
},
{
    path: '/wechat/sale_invoice',
    component: require('./components/SaleInvoice'),
    meta: {
        keepAlive: true,
        title: '下单'
    },
    name: 'saleInvoice'
},
{
    path: '/wechat/sale_order/:no',
    component: require('./components/SaleOrder'),
    meta: {
        keepAlive: false,
        title: '买书订单'
    },
    name: 'saleOrder'
},
{
    path: '/wechat/sale_order_ship/:no',
    component: require('./components/SaleOrderShip'),
    meta: {
        keepAlive: false,
        title: '状态跟踪'
    },
    name: 'saleOrderShip'
},
{
    path: '/wechat/recover_order/:no',
    component: require('./components/RecoverOrder'),
    meta: {
        keepAlive: false,
        title: '卖书订单'
    },
    name: 'recoverOrder'
},
{
    path: '/wechat/recover_order_ship/:no',
    component: require('./components/RecoverOrderShip'),
    meta: {
        keepAlive: false,
        title: '状态跟踪'
    },
    name: 'recoverOrderShip'
},
{
    path: '/wechat/search',
    component: require('./components/Search'),
    meta: {
        keepAlive: true,
        title: '搜索'
    },
    name: 'search'
},
{
    path: '/wechat/search2',
    component: require('./components/Search2'),
    meta: {
        keepAlive: true,
        title: '搜索'
    },
    name: 'search2'
},
{
    path: '/wechat/scan',
    component: require('./components/Scan'),
    meta: {
        keepAlive: true,
        title: '卖书给回流鱼',
    },
    name: 'scan'
},
{
    path: '/wechat/recover_invoice',
    component: require('./components/RecoverInvoice'),
    meta: {
        keepAlive: true,
        title: '下单'
    },
    name: 'recoverInvoice'
},
{
    path: '/wechat/book/:isbn',
    component: require('./components/Book'),
    meta: {
        keepAlive: false,
        title: '回流鱼'
    },
    name: 'book'
},
{
    path: '/wechat/score/:bookId',
    component: require('./components/Score'),
    meta: {
        keepAlive: false,
        title: '评分'
    },
    name: 'score'
},
{
    path: '/wechat/book2/:isbn',
    component: require('./components/Book2'),
    meta: {
        keepAlive: false,
        title: '回流鱼'
    },
    name: 'book2'
},
{
    path: '/wechat/tags',
    component: require('./components/Tags'),
    meta: {
        keepAlive: false,
        title: '选择你感兴趣的分类'
    },
    name: 'tags'
},
{
    path: '/wechat/classify/:tag',
    component: require('./components/Classify'),
    meta: {
        keepAlive: false,
        title: ''
    },
    name: 'classify'
},
{
    path: '/wechat/bigClassify/:name',
    component: require('./components/BigClassify'),
    meta: {
        keepAlive: true,
        title: ''
    },
    name: 'bigClassify'
},
{
    path: '/wechat/fourClassify/:tags',
    component: require('./components/FourClassify'),
    meta: {
        keepAlive: true,
        title: ''
    },
    name: 'fourClassify'
},
{
    path: '/wechat/newBook',
    component: require('./components/NewBook'),
    meta: {
        keepAlive: true,
        title: '新书专区'
    },
    name: 'newBook'
},
{
    path: '/wechat/hotBook',
    component: require('./components/HotBook'),
    meta: {
        keepAlive: true,
        title: '超级畅销'
    },
    name: 'hotBook'
},
{
    path: '/wechat/level_desc',
    component: require('./components/LevelDesc'),
    meta: {
        keepAlive: false,
        title: '回流鱼'
    },
    name: 'levelDesc'
},
{
    path: '/wechat/review_standard',
    component: require('./components/ReviewStandard'),
    meta: {
        keepAlive: false,
        title: '审核标准细则'
    },
    name: 'reviewStandard'
},
{
    path: '/wechat/qa',
    component: require('./components/Qa'),
    meta: {
        keepAlive: true,
        title: '更多问题'
    },
    name: 'qa'
},
{
    path: '/wechat/my',
    component: require('./components/My'),
    meta: {
        keepAlive: true,
        title: '我的'
    },
    name: 'my'
},
{
    path: '/wechat/my2',
    component: require('./components/My2'),
    meta: {
        keepAlive: false,
        title: '我的'
    },
    name: 'my'
},
{
    path: '/wechat/myCoupons',
    component: require('./components/MyCoupons'),
    meta: {
        keepAlive: false,
        title: '我的现金券'
    },
    name: 'myCoupons'
},
{
    path: '/wechat/my_orders',
    component: require('./components/MyOrders'),
    meta: {
        keepAlive: false,
        title: '回流鱼'
    },
    name: 'myOrder'
},
{
    path: '/wechat/my_orders2',
    component: require('./components/MyOrders2'),
    meta: {
        keepAlive: false,
        title: '回流鱼'
    },
    name: 'myOrder'
},
{
    path: '/wechat/wallet',
    component: require('./components/Wallet'),
    meta: {
        keepAlive: false,
        title: '余额'
    },
    name: 'wallet'
},
{
    path: '/wechat/user/:openId',
    component: require('./components/UserProfile'),
    meta: {
        keepAlive: false,
        title: '回流鱼'
    },
    name: 'user'
},
{
    path: '/wechat/user2/:openId',
    component: require('./components/UserProfile2'),
    meta: {
        keepAlive: false,
        title: '回流鱼'
    },
    name: 'user'
},
{
    path: '/wechat/express_fee',
    component: require('./components/ExpressFee'),
    meta: {
        keepAlive: false,
        title: '快递费说明'
    },
    name: 'expressFee'
},
{
    path: '/wechat/jzm',
    component: require('./components/Jzm'),
    meta: {
        keepAlive: false,
        title: '句子迷'
    },
    name: 'jzm'
},
{
    path: '/wechat/share',
    component: require('./components/Share'),
    meta: {
        keepAlive: false,
        title: '回流鱼'
    },
    name: 'share'
},
{
    path: '/wechat/address_edit',
    component: require('./components/AddressEdit'),
    meta: {
        keepAlive: false,
        title: '地址编辑'
    },
    name: 'addressEdit'
},
{
    path: '/wechat/address_list',
    component: require('./components/AddressList'),
    meta: {
        keepAlive: false,
        title: '所有地址'
    },
    name: 'addressList'
},
{
    path: '/wechat/read_day',
    component: require('./components/ReadDay'),
    meta: {
        keepAlive: false,
        title: '庆融资活动'
    },
    name: 'readDay'
},
{
    path: '/wechat/shudanAll',
    component: require('./components/ShudanAll'),
    meta: {
        keepAlive: false,
        title: '全部书单'
    },
    name: 'shudanAll'
},
{
    path: '/wechat/shudan/:shudan',
    component: require('./components/ShudanDetail'),
    meta: {
        keepAlive: true,
        title: '书单'
    },
    name: 'shudan'
},
{
    path: '/wechat/recbooks',
    component: require('./components/RecBooks'),
    meta: {
        keepAlive: true,
        title: '我来推荐'
    },
    name: 'recbooks'
},
{
    path: '/wechat/recSearchBook',
    component: require('./components/RecSearchBook'),
    meta: {
        keepAlive: false,
        title: '选择要推荐的书'
    },
    name: 'recSearchBook'
},
{
    path: '/wechat/sdBookComment/:sdbookid',
    component: require('./components/SdBookComment'),
    meta: {
        keepAlive: false,
        title: '评论'
    },
    name: 'sdBookComment'
},
{
    path: '/wechat/zanUsers',
    component: require('./components/ZanUsers'),
    meta: {
        keepAlive: false,
        title: '觉得有趣的用户'
    },
    name: 'zanUsers'
},
{
    path: '/inbound',
    component: require('./components/Inbound'),
    meta: {
        keepAlive: true,
        title: '图书审核'
    },
    name: 'inbound'
},
{
    path: '/store_shelf',
    component: require('./components/StoreShelf'),
    meta: {
        keepAlive: true,
        title: '上架'
    },
    name: 'storeShelf'
},
{
    path: '/store_shelf2',
    component: require('./components/StoreShelf2'),
    meta: {
        keepAlive: true,
        title: '上架2'
    }
},
{
    path: '/store_shelf2',
    component: require('./components/StoreShelf2'),
    meta: {
        keepAlive: true,
        title: '上架2'
    }
},
{
    path: '/store_shelf3',
    component: require('./components/StoreShelf3'),
    meta: {
        keepAlive: true,
        title: '上架3'
    }
},
{
    path: '/zto',
    component: require('./components/Zto'),
    meta: {
        keepAlive: true,
        title: '更新中通订单号'
    },
    name: 'zto'
},
{
    path: '/book/:bookId/versions',
    component: require('./components/BookVersionList'),
    meta: {
        keepAlive: false,
        title: '版本列表'
    },
    name: 'versions'
},
{
    path: '/book/:bookId/version/:versionId/edit',
    component: require('./components/BookVersionEdit'),
    meta: {
        keepAlive: false,
        title: '版本编辑'
    },
    name: 'edit'
},
// PC路由
{
    path: '/pc/shop',
    component: require('./components/pc/shop'),
    meta: {
        keepAlive: false,
        title: '回流鱼'
    },
    name: 'shop'
},
{
    path: '/pc/cart',
    component: require('./components/pc/Cart'),
    meta: {
        keepAlive: false,
        title: '购物袋'
    },
    name: 'cart'
},
{
    path: '/pc/cart2',
    component: require('./components/pc/Cart2'),
    meta: {
        keepAlive: false,
        title: '购物袋'
    },
    name: 'cart2'
},
{
    path: '/pc/sale_invoice',
    component: require('./components/pc/SaleInvoice'),
    meta: {
        keepAlive: true,
        title: '下单'
    },
    name: 'saleInvoice'
},
{
    path: '/pc/sale_order/:no',
    component: require('./components/pc/SaleOrder'),
    meta: {
        keepAlive: false,
        title: '买书订单'
    },
    name: 'saleOrder'
},
{
    path: '/pc/sale_order_ship/:no',
    component: require('./components/pc/SaleOrderShip'),
    meta: {
        keepAlive: false,
        title: '状态跟踪'
    },
    name: 'saleOrderShip'
},
{
    path: '/pc/recover_order/:no',
    component: require('./components/pc/RecoverOrder'),
    meta: {
        keepAlive: false,
        title: '卖书订单'
    },
    name: 'recoverOrder'
},
{
    path: '/pc/recover_order_ship/:no',
    component: require('./components/pc/RecoverOrderShip'),
    meta: {
        keepAlive: false,
        title: '状态跟踪'
    },
    name: 'recoverOrderShip'
},
{
    path: '/pc/search',
    component: require('./components/pc/Search'),
    meta: {
        keepAlive: true,
        title: '搜索'
    },
    name: 'search'
},
{
    path: '/pc/search2',
    component: require('./components/pc/Search2'),
    meta: {
        keepAlive: true,
        title: '搜索'
    },
    name: 'search2'
},
{
    path: '/pc/scan',
    component: require('./components/pc/Scan'),
    meta: {
        keepAlive: true,
        title: '卖书给回流鱼',
    },
    name: 'scan'
},
{
    path: '/pc/recover_invoice',
    component: require('./components/pc/RecoverInvoice'),
    meta: {
        keepAlive: true,
        title: '下单'
    },
    name: 'recoverInvoice'
},
{
    path: '/pc/book/:isbn',
    component: require('./components/pc/Book'),
    meta: {
        keepAlive: false,
        title: '回流鱼'
    },
    name: 'book'
},
{
    path: '/pc/score/:bookId',
    component: require('./components/pc/Score'),
    meta: {
        keepAlive: false,
        title: '评分'
    },
    name: 'score'
},
{
    path: '/pc/book2/:isbn',
    component: require('./components/pc/Book2'),
    meta: {
        keepAlive: false,
        title: '回流鱼'
    },
    name: 'book2'
},
{
    path: '/pc/tags',
    component: require('./components/pc/Tags'),
    meta: {
        keepAlive: false,
        title: '选择你感兴趣的分类'
    },
    name: 'tags'
},
{
    path: '/pc/classify/:tag',
    component: require('./components/pc/Classify'),
    meta: {
        keepAlive: false,
        title: ''
    },
    name: 'classify'
},
{
    path: '/pc/bigClassify/:name',
    component: require('./components/pc/BigClassify'),
    meta: {
        keepAlive: true,
        title: ''
    },
    name: 'bigClassify'
},
{
    path: '/pc/fourClassify/:tags',
    component: require('./components/pc/FourClassify'),
    meta: {
        keepAlive: true,
        title: ''
    },
    name: 'fourClassify'
},
{
    path: '/pc/newBook',
    component: require('./components/pc/NewBook'),
    meta: {
        keepAlive: true,
        title: '新书专区'
    },
    name: 'newBook'
},
{
    path: '/pc/hotBook',
    component: require('./components/pc/HotBook'),
    meta: {
        keepAlive: true,
        title: '超级畅销'
    },
    name: 'hotBook'
},
{
    path: '/pc/level_desc',
    component: require('./components/pc/LevelDesc'),
    meta: {
        keepAlive: false,
        title: '回流鱼'
    },
    name: 'levelDesc'
},
{
    path: '/pc/review_standard',
    component: require('./components/pc/ReviewStandard'),
    meta: {
        keepAlive: false,
        title: '审核标准细则'
    },
    name: 'reviewStandard'
},
{
    path: '/pc/qa',
    component: require('./components/pc/Qa'),
    meta: {
        keepAlive: true,
        title: '更多问题'
    },
    name: 'qa'
},
{
    path: '/pc/my',
    component: require('./components/pc/My'),
    meta: {
        keepAlive: true,
        title: '我的'
    },
    name: 'my'
},
{
    path: '/pc/my2',
    component: require('./components/pc/My2'),
    meta: {
        keepAlive: false,
        title: '我的'
    },
    name: 'my'
},
{
    path: '/pc/myCoupons',
    component: require('./components/pc/MyCoupons'),
    meta: {
        keepAlive: false,
        title: '我的现金券'
    },
    name: 'myCoupons'
},
{
    path: '/pc/my_orders',
    component: require('./components/pc/MyOrders'),
    meta: {
        keepAlive: false,
        title: '回流鱼'
    },
    name: 'myOrder'
},
{
    path: '/pc/my_orders2',
    component: require('./components/pc/MyOrders2'),
    meta: {
        keepAlive: false,
        title: '回流鱼'
    },
    name: 'myOrder'
},
{
    path: '/pc/wallet',
    component: require('./components/pc/Wallet'),
    meta: {
        keepAlive: false,
        title: '余额'
    },
    name: 'wallet'
},
{
    path: '/pc/user/:openId',
    component: require('./components/pc/UserProfile'),
    meta: {
        keepAlive: false,
        title: '回流鱼'
    },
    name: 'user'
},
{
    path: '/pc/user2/:openId',
    component: require('./components/pc/UserProfile2'),
    meta: {
        keepAlive: false,
        title: '回流鱼'
    },
    name: 'user'
},
{
    path: '/pc/express_fee',
    component: require('./components/pc/ExpressFee'),
    meta: {
        keepAlive: false,
        title: '快递费说明'
    },
    name: 'expressFee'
},
{
    path: '/pc/jzm',
    component: require('./components/pc/Jzm'),
    meta: {
        keepAlive: false,
        title: '句子迷'
    },
    name: 'jzm'
},
{
    path: '/pc/share',
    component: require('./components/pc/Share'),
    meta: {
        keepAlive: false,
        title: '回流鱼'
    },
    name: 'share'
},
{
    path: '/pc/address_edit',
    component: require('./components/pc/AddressEdit'),
    meta: {
        keepAlive: false,
        title: '地址编辑'
    },
    name: 'addressEdit'
},
{
    path: '/pc/address_list',
    component: require('./components/pc/AddressList'),
    meta: {
        keepAlive: false,
        title: '所有地址'
    },
    name: 'addressList'
},
{
    path: '/pc/read_day',
    component: require('./components/pc/ReadDay'),
    meta: {
        keepAlive: false,
        title: '庆融资活动'
    },
    name: 'readDay'
},
{
    path: '/pc/shudanAll',
    component: require('./components/pc/ShudanAll'),
    meta: {
        keepAlive: false,
        title: '全部书单'
    },
    name: 'shudanAll'
},
{
    path: '/pc/shudan/:shudan',
    component: require('./components/pc/ShudanDetail'),
    meta: {
        keepAlive: true,
        title: '书单'
    },
    name: 'shudan'
},
{
    path: '/pc/recbooks',
    component: require('./components/pc/RecBooks'),
    meta: {
        keepAlive: true,
        title: '我来推荐'
    },
    name: 'recbooks'
},
{
    path: '/pc/recSearchBook',
    component: require('./components/pc/RecSearchBook'),
    meta: {
        keepAlive: false,
        title: '选择要推荐的书'
    },
    name: 'recSearchBook'
},
{
    path: '/pc/sdBookComment/:sdbookid',
    component: require('./components/pc/SdBookComment'),
    meta: {
        keepAlive: false,
        title: '评论'
    },
    name: 'sdBookComment'
},
{
    path: '/pc/zanUsers',
    component: require('./components/pc/ZanUsers'),
    meta: {
        keepAlive: false,
        title: '觉得有趣的用户'
    },
    name: 'zanUsers'
},
];
export default new VueRouter({
    mode: 'history',
    routes
    
});
