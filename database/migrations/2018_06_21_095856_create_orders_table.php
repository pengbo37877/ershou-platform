<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->increments('id');
            $table->string('no')->unique(); // 订单号
            $table->string('prepay_id')->nullable();
            $table->unsignedInteger('coupon_id')->nullable(); // 现金券
            $table->unsignedInteger('type'); // 订单类型：1收书，2卖书
            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->text('address'); // 地址
            $table->timestamp('recover_time'); // 上门回收时间
            $table->decimal('total_amount'); // 总费用
            $table->text('remark')->nullable(); // 备注
            $table->dateTime('paid_at')->nullable(); // 支付时间
            $table->string('payment_method')->nullable(); // 支付渠道
            $table->string('payment_no')->nullable(); // 支付编号
            $table->string('refund_status')->default(\App\Order::REFUND_STATUS_PENDING);
            $table->string('refund_no')->nullable(); // 退款编号
            $table->boolean('closed')->default(false); // 订单关闭
            $table->boolean('reviewed')->default(false); // 订单审核
            $table->string('express')->nullable(); // 快递公司
            $table->string('express_no')->nullable(); // 快递单
            $table->string('ship_status')->default(\App\Order::SHIP_STATUS_PENDING); // 快递状态
            $table->text('ship_data')->nullable(); // 快递数据
            $table->decimal('ship_price')->default(0); // 快递费
            $table->text('extra')->nullable(); // 其他数据
            $table->integer('recover_status')->nullable(); // 其他数据
            $table->integer('sale_status')->nullable(); // 其他数据
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orders');
    }
}
