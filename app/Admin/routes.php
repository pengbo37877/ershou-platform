<?php

use Illuminate\Routing\Router;

Admin::registerAuthRoutes();

Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
], function (Router $router) {

    $router->get('/', 'HomeController@index');

    $router->resource('users', 'UsersController');
    $router->resource('books', 'BooksController');
    $router->post('books/recover', 'BooksController@recover');
    $router->post('books/update_dou_info', 'BooksController@updateDouInfo');
    $router->resource('book_skus', 'BookSkusController');
    $router->post('book_skus/on_sale', 'BookSkusController@onSale');
    $router->post('book_skus/copy_sku', 'BookSkusController@copySku');
    $router->post('book_skus/change_group', 'BookSkusController@changeGroup');
    $router->resource('orders', 'OrdersController');
    $router->resource('new_orders', 'NewOrdersController');
    $router->resource('order_items', 'OrderItemsController');
    $router->resource('wallets', 'WalletsController');
    $router->resource('shudans', 'ShudansController');
    $router->resource('shudan_comments', 'ShudanCommentsController');
    $router->resource('user_search_histories', 'UserSearchController');
    $router->resource('reminders', 'RemindersController');
    $router->resource('refunds', 'OrderRefundsController');
    $router->resource('sale_items', 'SaleItemsController');
    $router->resource('coupons', 'CouponsController');
    $router->resource('store_shelves', 'StoreShelvesController');
    $router->post('store_shelves/generate_code', 'StoreShelvesController@generateCode');
    $router->resource('juzis', 'JuzisController');
    $router->resource('pictures', 'PicturesController');
    $router->resource('recover_reports', 'RecoverReportsController');
    $router->resource('dou_lists', 'DoulistsController');
    $router->post('recover_reports/accept', 'RecoverReportsController@accept');
    $router->post('recover_reports/deny', 'RecoverReportsController@deny');
    $router->post('orders/ship_bill', 'OrdersController@shipBill');
    $router->post('orders/zto_bill', 'OrdersController@ztoBill');
    $router->post('orders/stock_out', 'OrdersController@stockOut');
    $router->get('book/versions', 'BooksController@versions');
    $router->resource('lotteries', 'LotteriesController');
    $router->resource('comments', 'CommentController');
    $router->resource('sts', 'StsController');
    $router->resource('buttons', 'ButtonsController');
    $router->resource('newbooks', 'NewBookController');
    $router->resource('shops', 'BookShopController');
    $router->resource('ship_rules', 'ShipRuleController');
    $router->resource('evil_phones', 'EvilPhoneController');
    $router->post('ship_rules/add', 'ShipRuleController@createShipRule');
    $router->post('sts/update_ship', "StsController@updateShip");
    $router->get('sts/getSF', "StsController@getSFList");
});
