<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::any('/wechat', 'WeChatController@serve');
Route::any('/wechat/users', 'WeChatController@users');
Route::any('/wechat/images', 'WeChatController@images');
Route::any('/wechat/user/{openId}', 'WeChatController@user');
Route::any('/wechat/user2/{openId}', 'WeChatController@user');
Route::any('/wechat/material', 'WeChatController@material');
Route::any('/wechat/buttons', 'WeChatController@buttons');
Route::any('/wechat/get_industry', 'WeChatController@getIndustry');
Route::get('/wechat/get_shudans', 'WeChatController@getShudans');
Route::any('/wechat/transfer_to_wx', 'PaymentController@transferToWx');
Route::any('/wechat/payment_notify', 'WeChatController@paymentNotify');
Route::get('/wechat/review_standard', 'WeChatController@reviewStandard');
Route::post('/wechat/oauth_callback', 'WeChatController@oauthCallback');
Route::get('/wechat/qa', 'WeChatController@qa');
Route::get('/wechat/level_desc', 'WeChatController@levelDesc');
Route::post('/ship/callback', 'WeChatController@shipCallBack');
Route::post('/ship/zto_callback', 'WeChatController@ztoShipCallBack');
Route::post('/ship/kdgj_callback', 'WeChatController@kdgjCallBack');
Route::get('/test/jz/{id}', 'WeChatController@testJz');
Route::get('/test/qrcode/{code}', 'WeChatController@qrcode');
Route::get('/test/qrcode2/{code}', 'WeChatController@qrcode2');
Route::get('/test/recover_price/{isbn}', 'WeChatController@recoverPrice');
Route::get('/test/can_recover/{isbn}', 'WeChatController@canRecover3');
Route::get('/query_balance_order/{no}', 'WeChatController@queryBalanceOrder');
Route::get('/wechat/get_new_user_coupon', 'WeChatController@getNewUserCoupon');     // 领取 5 元新用户优惠券
Route::get('/wechat/get_special_coupon', 'WeChatController@getSpecialCoupon');      // 活动优惠券

// 两组一样的接口，一组是需要鉴权的，一组是不需要的
Route::group(['middleware' => ['wechat.oauth:snsapi_userinfo']], function () {
    Route::get('/wechat/shop', 'WeChatController@ui');
    Route::get('/wechat/shop2', 'WeChatController@ui');
    Route::get('/wechat/shop3', 'WeChatController@ui');
    Route::get('/wechat/cart', 'WeChatController@ui');
    Route::get('/wechat/cart2', 'WeChatController@ui');
    Route::get('/wechat/sale_order/{no}', 'WeChatController@ui');
    Route::get('/wechat/recover_order/{no}', 'WeChatController@ui');
    Route::get('/wechat/book/{isbn}', 'WeChatController@ui');
    Route::get('/wechat/book2/{isbn}', 'WeChatController@ui');
    Route::get('/wechat/scan', 'WeChatController@ui');
    Route::get('/wechat/my', 'WeChatController@ui');
    Route::get('/wechat/level_desc', 'WeChatController@ui');
    Route::get('/wechat/review_standard', 'WeChatController@ui');
    Route::get('/wechat/qa', 'WeChatController@ui');
    Route::get('/wechat/search', 'WeChatController@ui');
    Route::get('/wechat/search2', 'WeChatController@ui');//搜索2
    Route::get('/wechat/my_orders', 'WeChatController@ui');
    Route::get('/wechat/my_orders2', 'WeChatController@ui');//我的订单
    Route::get('/wechat/user/{openId}', 'WeChatController@ui');
    Route::get('/wechat/user2/{openId}', 'WeChatController@ui');
    Route::get('/wechat/express_fee', 'WeChatController@ui');
    Route::get('/wechat/all_address', 'WeChatController@ui');
    Route::get('/wechat/jzm', 'WeChatController@ui');
    Route::get('/wechat/share_desc', 'WeChatController@ui');
    Route::get('/wechat/share', 'WeChatController@ui');
    Route::get('/wechat/address_edit', 'WeChatController@ui');
    Route::get('/wechat/address_list', 'WeChatController@ui');
    Route::get('/wechat/recbooks', 'WeChatController@ui');
    Route::get('/wechat/recSearchBook', 'WeChatController@ui');
    Route::get('/wechat/sdBookComment/{sdbookid}', 'WeChatController@ui');
    Route::get('/wechat/zanUsers', 'WeChatController@ui');
    Route::get('/wechat/shudanAll', 'WeChatController@ui');
    Route::get('/wechat/shudan/{shudan}', 'WeChatController@ui');
    Route::get('/wechat/classify/{tag}', 'WeChatController@ui');
    Route::get('/wechat/comment/{bookId}', 'WeChatController@ui');
    Route::get('/wechat/tags', 'WeChatController@ui');
    Route::get('/wechat/my2', 'WeChatController@ui'); 
    Route::get('/wechat/myCoupons', 'WeChatController@ui'); //优惠券页
    Route::get('/wechat/bigClassify/{name}', 'WeChatController@ui'); //8个分类内页
    Route::get('/wechat/hotBook', 'WeChatController@ui'); //畅销书籍
    Route::get('/wechat/sale_invoice', 'WeChatController@ui');//买书订单页
    Route::get('/wechat/recover_invoice', 'WeChatController@ui');//卖书订单页
    Route::get('/wechat/fourClassify/{tags}', 'WeChatController@ui');//四个分类
    Route::get('/wechat/newbook', 'WeChatController@ui');//新书专区

    // 下面是api
    Route::get('/wx-api/get_user_cart_items', 'WeChatController@getUserCartItems');     // 购物车详情
    Route::get('/wx-api/get_user_cart_books', 'HuiliuController@getUserCartBooks');     // 购物车数量
    Route::get('/wx-api/get_user_reminders', 'WeChatController@getUserReminders');
    Route::get('/wx-api/get_cart_recommends', 'WeChatController@getCartRecommends');
    Route::get('/wx-api/get_user', 'WeChatController@getUser');
    Route::get('/wx-api/get_user/{openId}', 'WeChatController@getUserByOpenId');
    Route::get('/wx-api/get_user_tags', 'WeChatController@getUserTags');
    //Route::post('/wx-api/add_user_tag', 'WeChatController@addUserTag');
    //Route::post('/wx-api/delete_user_tag', 'WeChatController@deleteUserTag');
    Route::get('/wx-api/modify_user_tag', 'WeChatController@modifyUserTag');    # 修改用户标签
    Route::post('/wx-api/add_sku_to_cart', 'WeChatController@addSkuToCart');
    Route::post('/wx-api/add_book_to_reminder', 'WeChatController@addBookToReminder');
    Route::post('/wx-api/remove_book_from_reminder', 'WeChatController@removeBookFromReminder');
    Route::post('/wx-api/select_cart_item', 'WeChatController@selectCartItem');
    Route::post('/wx-api/delete_cart_item', 'WeChatController@deleteCartItem');
    Route::post('/wx-api/update_cart_item', 'WeChatController@updateCartItem');
    Route::post('/wx-api/create_sale_order', 'WeChatController@createSaleOrder');
    Route::post('/wx-api/test_create_sale_order', 'TestsController@createSaleOrder');
    Route::post('/wx-api/get_sale_order_wx_config', 'WeChatController@getSaleOrderWxConfig');
    Route::get('/wx-api/get_sale_order_payment_status/{id}', 'WeChatController@getSaleOrderPaymentStatus');
    Route::get('/wx-api/get_sale_order_payment_status_by_no/{no}', 'WeChatController@getSaleOrderPaymentStatusByNo');
    Route::post('/wx-api/pay_sale_order_with_wallet', 'WeChatController@paySaleOrderWithWallet');
    Route::get('/wx-api/get_order/{no}', 'WeChatController@getOrder');
    Route::post('/wx-api/update_sale_order', 'WeChatController@updateSaleOrder');
    Route::get('/wx-api/cancel_order/{no}', 'WeChatController@cancelOrder');
    Route::post('/wx-api/delete_order', 'WeChatController@deleteOrder');
    Route::get('/wx-api/get_user_all_address', 'WeChatController@getUserAllAddress');
    Route::get('/wx-api/get_user_latest_address', 'WeChatController@getUserLatestAddress');
    Route::get('/wx-api/get_user_wallet_balance', 'WeChatController@getUserWalletBalance');
    Route::post('/wx-api/create_user_address', 'WeChatController@createUserAddress');
    Route::post('/wx-api/delete_user_address', 'WeChatController@deleteUserAddress');
    Route::post('/wx-api/set_default_address', 'WeChatController@setDefaultAddress');
    Route::get('/wx-api/get_new_books', 'WeChatController@getNewBooks');

    Route::get('/wx-api/get_books_for_recover', 'WeChatController@getBooksForRecover');
    Route::get('/wx-api/get_recover_books_without_counting', 'WeChatController@getRecoverBooksWithoutCounting');
    Route::post('/wx-api/remove_book_from_recover', 'WeChatController@removeBookFromRecover');
    Route::post('/wx-api/add_book_for_recover', 'WeChatController@addBookForRecover');
    Route::post('/wx-api/create_recover_order', 'WeChatController@createRecoverOrder');
    Route::post('/wx-api/add_recover_report', 'WeChatController@addRecoverReport');

    Route::get('/wx-api/get_recommend_tags', 'WeChatController@getRecommendTags');
    Route::post('/wx-api/search_book_by_str', 'HuiliuController@searchBookByStr');  // 搜索
    Route::get('/wx-api/get_top_q', 'HuiliuController@getTopQ');                    //
    Route::any('/wx-api/update_coupon_tip', 'HuiliuController@UpdateCouponTip');   // 优惠券小红点提醒

    Route::get('/wx-api/get_books_from_shelf', 'WeChatController@getBooksFromShelf');
    Route::get('/wx-api/get_user_balance', 'WeChatController@getUserBalance');
    Route::get('/wx-api/get_user_sale_balance', 'WeChatController@getUserSaleBalance');
    Route::get('/wx-api/wallet_transfer', 'WeChatController@walletTransfer');
    Route::post('/wx-api/add_book_to_shelf', 'WeChatController@addBookToShelf');
    Route::post('/wx-api/remove_book_from_shelf', 'WeChatController@removeBookFromShelf');

    Route::get('/wx-api/get_my_orders', 'WeChatController@getMyOrders');
    Route::get('/wx-api/get_my_buy_orders', 'WeChatController@getMyBuyOrders');

    Route::get('/wx-api/get_user_sold_books_income/{openId}', 'WeChatController@getUserSoldBooksIncome');
    Route::get('/wx-api/get_user_shelf_books/{openId}', 'WeChatController@getUserShelfBooks');
    Route::get('/wx-api/get_user_sold_books/{openId}', 'WeChatController@getUserSoldBooks');
    Route::get('/wx-api/get_user_feeds/{openId}', 'WeChatController@getUserFeeds');     // 个人动态
    Route::get('/wx-api/share_address_config', 'WeChatController@shareAddressConfig');

    Route::get('/wx-api/get_coupons', 'WeChatController@getCoupons');

    Route::post('/wx-api/create_client_error', 'WeChatController@createClientError');

    Route::get('/wx-api/get_jz_image/{id}', 'WeChatController@getJzImage');
    Route::get('/wx-api/get_share_qr_image', 'WeChatController@getQrImage');
});

Route::any('/wx-api/config', 'WeChatController@config');
Route::get('/wx-api/get_book/{isbn}', 'WeChatController@getBook');
Route::get('/wx-api/get_book_comments/{isbn}', 'WeChatController@getBookComments');
Route::get('/wx-api/get_book_by_id', 'WeChatController@getBookById');
Route::get('/wx-api/get_book_relations/{isbn}', 'WeChatController@getBookRelations');
Route::get('/wx-api/get_books_by_tag/{tag}', 'WeChatController@getBooksByTag');
Route::get('/wx-api/get_shudan_list', 'WeChatController@getShudanList');
Route::get('/wx-api/get_opened_shudan', 'WeChatController@getOpenedShudan');
Route::get('/wx-api/get_shudan/{shudan}', 'WeChatController@getShudan');
Route::get('/wx-api/get_shudan_books/{shudan}', 'WeChatController@getShudanBooks');				// 书单列表页
Route::get('/wx-api/add_book_to_shudan/{shudan_id}', 'WeChatController@addBookToShudan');       // 书单：推荐一本书
Route::get('/wx-api/shudan_users', 'WeChatController@shudanUsers');       						// 书单：虚拟推荐用户
Route::get('/wx-api/shudan_dianzan/{comment_id}', 'WeChatController@commitShudanDianzan');		// 书单留言点赞
Route::get('/wx-api/shudan_comment/{sd_comment_id}', 'WeChatController@getShudanComment');		// 书单推荐详情页
Route::get('/wx-api/get_bestseller', 'HuiliuController@getBestseller');                         // 超级畅销
Route::get('/wx-api/get_category_books', 'HuiliuController@getCategoryBooks');                  // 首页 8 个分类
Route::get('/wx-api/get_new_user_coupons', 'HuiliuController@getNewUserCoupons');               // 获取新人礼优惠券
Route::get('/wx-api/get_jzs', 'WeChatController@getJzs');
Route::post('/wx-api/view_book', 'WeChatController@viewBook');
Route::get('/wx-api/ban_book', 'WeChatController@banBook');
Route::get('/wx-api/read_day_coupon', 'WeChatController@readDayCoupon');
Route::post('/wx-api/update_book_price', 'WeChatController@updateBookPrice');
Route::get('/wx-api/get_wallets', 'WeChatController@getWallets');
Route::get('/wx-api/get_book_sale_sku', 'WeChatController@getBookSaleSku');
Route::get('/wx-api/get_book_versions/{bookId}', 'WeChatController@getBookVersions');
Route::get('/wx-api/get_book_version', 'WeChatController@getBookVersion');
Route::get('/wx-api/open_times', 'WeChatController@openTimes');
Route::get('/wx-api/send_share_image/{id}', 'WeChatController@sendShareImage');

Route::post('/wx-api/upload_cover', 'StoreShelfController@uploadCover');
Route::post('/wx-api/upload_version_cover', 'StoreShelfController@uploadVersionCover');
Route::post('/wx-api/update_sku_version', 'StoreShelfController@updateSkuVersion');
Route::post('/wx-api/create_book_version', 'StoreShelfController@createBookVersion');
Route::post('/wx-api/upload_zto', 'HomeController@uploadZto');

// 下面是抽奖的路由
Route::get('/lotteries', 'LotteryController@index');
Route::any('/lottery/show', 'LotteryController@show');
Route::any('/lottery/fake_show', 'LotteryController@fakeShow');
Route::any('/lottery/get_lottery_by_uuid', 'LotteryController@getLotteryByUuid');
Route::any('/lottery/login', 'LotteryController@login');
Route::any('/lottery/user', 'LotteryController@user');
Route::any('/lottery/user_lottery_info', 'LotteryController@userLotteryInfo');
Route::get('/lottery/addresses', 'LotteryController@lotteryAddresses');
Route::get('/lottery/user_addresses', 'LotteryController@userAddresses');
Route::any('/lottery/get_user_by_unionid', 'LotteryController@getUserByUnionId');
Route::any('/lottery/decrypted_data', 'LotteryController@decryptedData');
Route::any('/lottery/join', 'LotteryController@join');
Route::any('/lottery/send', 'LotteryController@send');

// 下面是回流鱼小程序
Route::any('/hly-mini/login', 'MiniController@login');
Route::any('/hly-mini/user', 'MiniController@user');
Route::any('/hly-mini/decrypted_data', 'MiniController@decryptedData');
Route::any('/hly-mini/give_share_coupons', 'MiniController@giveShareCoupons');
Route::any('/hly-mini/get_user_tags', 'MiniController@getUserTags');
Route::any('/hly-mini/get_books_by_tag', 'MiniController@getBooksByTag');
Route::any('/hly-mini/search_books', 'MiniController@searchBooks');
Route::any('/hly-mini/get_user_cart_items', 'MiniController@getUserCartItems');
Route::any('/hly-mini/get_user_reminder_items', 'MiniController@getUserReminderItems');
Route::any('/hly-mini/add_sku_to_cart', 'MiniController@addSkuToCart');
Route::any('/hly-mini/remove_cart_item', 'MiniController@removeCartItem');
Route::any('/hly-mini/add_book_to_reminder', 'MiniController@addBookToReminder');
Route::any('/hly-mini/remove_reminder_item', 'MiniController@removeReminderItem');
Route::any('/hly-mini/get_opened_shudans', 'MiniController@getOpenedShudans');

//Auth::routes();

// 下面是入库工具
Route::get('/home', 'HomeController@index')->name('home');
Route::get('/array', 'HomeController@array');
Route::get('/inbound', 'InboundController@inbound');
Route::get('/inbound/get_order_by_express_no', 'InboundController@getOrderByExpressNo');
Route::get('/inbound/get_book_by_isbn', 'InboundController@getBookByIsbn');
Route::get('/inbound/get_sku_by_hly_code', 'InboundController@getSkuByHlyCode');
Route::get('/inbound/get_sku_by_isbn', 'InboundController@getSkuByIsbn');
Route::get('/inbound/next_order', 'InboundController@nextOrder');
Route::any('/inbound/config', 'InboundController@config');
Route::get('/inbound/tags', 'InboundController@tags');
Route::post('/inbound/sku', 'InboundController@sku');
Route::post('/inbound/order_item', 'InboundController@orderItem');
Route::post('/inbound/deny', 'InboundController@deny');
Route::get('/inbound/next_item', 'InboundController@nextItem');
Route::post('/inbound/add_item', 'InboundController@addItem');
Route::get('/inbound/delete_item', 'InboundController@deleteItem');
Route::get('/inbound/all_items', 'InboundController@allItems');
Route::get('/inbound/complete_order', 'InboundController@completeOrder');
Route::get('/inbound/update_ship_price', 'InboundController@updateShipPrice');
Route::get('/inbound/user_situation', 'InboundController@userSituation');
Route::get('/inbound/add_version', 'InboundController@addVersion');
Route::get('/inbound/mark_order_as_evil', 'InboundController@markOrderAsEvil');
Route::get('/inbound/ban_book', 'InboundController@banBook');
Route::get('/inbound/review_ok', 'InboundController@reviewOk');

Route::get('/inbound/band_hly_code', 'InboundController@bandHlyCode');
Route::post('/inbound/band', 'InboundController@band');

Route::get('/store_shelf', 'StoreShelfController@index');
Route::get('/store_shelf2', 'StoreShelfController@index');
Route::get('/store_shelf3', 'StoreShelfController@index');
Route::post('/store_shelf', 'StoreShelfController@update');
Route::get('/book/{bookId}/versions', 'StoreShelfController@index');
Route::get('/book/{bookId}/version/{versionId}/edit', 'StoreShelfController@index');
Route::post('/store_shelf/config', 'StoreShelfController@config');
Route::get('/store_shelf/stores', 'StoreShelfController@getStores');
Route::get('/store_shelf/stores2', 'StoreShelfController@getStores2');
Route::get('/store_shelf/boxes', 'StoreShelfController@getBoxes');
Route::get('/store_shelf/add_store', 'StoreShelfController@createStores');
Route::get('/store_shelf/get_sku_by_code', 'StoreShelfController@getSkuByCode');
Route::get('/ceshi', 'TestsController@ceshi');

// 更新快递号
Route::get('/zto', 'HomeController@zto');

// test
Route::get('/test/price_convert/{book}', 'TestsController@testPriceConvert');
Route::get('/test/recommend', 'TestsController@recommend');
Route::get('/test/book_recommend', 'TestsController@bookRecommend');
Route::get('/test/lenglei', 'TestsController@lenglei');
Route::get('/test/push_order/{no}', 'TestsController@pushOrder');


// 下边是 pc 页面的路由
Route::group([
    'prefix' => 'pc',
    'namespace' => 'Pc',
], function() {
    Route::get('/index', 'PcController@index');
    Route::get('/shop', 'PcController@ui');//pc首页
    Route::get('/shop2', 'PcController@ui');
    Route::get('/shop3', 'PcController@ui');
    Route::get('/cart', 'PcController@ui');
    Route::get('/cart2', 'PcController@ui');
    Route::get('/sale_order/{no}', 'PcController@ui');
    Route::get('/recover_order/{no}', 'PcController@ui');
    Route::get('/book/{isbn}', 'PcController@ui');
    Route::get('/book2/{isbn}', 'PcController@ui');
    Route::get('/scan', 'PcController@ui');
    Route::get('/my', 'PcController@ui');
    Route::get('/level_desc', 'PcController@ui');
    Route::get('/review_standard', 'PcController@ui');
    Route::get('/qa', 'PcController@ui');
    Route::get('/search', 'PcController@ui');
    Route::get('/search2', 'PcController@ui');//搜索2
    Route::get('/my_orders', 'PcController@ui');
    Route::get('/my_orders2', 'PcController@ui');//我的订单
    Route::get('/user/{openId}', 'PcController@ui');
    Route::get('/user2/{openId}', 'PcController@ui');
    Route::get('/express_fee', 'PcController@ui');
    Route::get('/all_address', 'PcController@ui');
    Route::get('/jzm', 'PcController@ui');
    Route::get('/share_desc', 'PcController@ui');
    Route::get('/share', 'PcController@ui');
    Route::get('/address_edit', 'PcController@ui');
    Route::get('/address_list', 'PcController@ui');
    Route::get('/recbooks', 'PcController@ui');
    Route::get('/recSearchBook', 'PcController@ui');
    Route::get('/sdBookComment/{sdbookid}', 'PcController@ui');
    Route::get('/zanUsers', 'PcController@ui');
    Route::get('/shudanAll', 'PcController@ui');
    Route::get('/shudan/{shudan}', 'PcController@ui');
    Route::get('/classify/{tag}', 'PcController@ui');
    Route::get('/comment/{bookId}', 'PcController@ui');
    Route::get('/tags', 'PcController@ui');
    Route::get('/my2', 'PcController@ui'); 
    Route::get('/myCoupons', 'PcController@ui'); //优惠券页
    Route::get('/bigClassify/{name}', 'PcController@ui'); //8个分类内页
    Route::get('/hotBook', 'PcController@ui'); //畅销书籍
    Route::get('/sale_invoice', 'PcController@ui');//买书订单页
    Route::get('/recover_invoice', 'PcController@ui');//卖书订单页
    Route::get('/fourClassify/{tags}', 'PcController@ui');//四个分类
    Route::get('/newbook', 'PcController@ui');//新书专区
});