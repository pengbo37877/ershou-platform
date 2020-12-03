<?php

namespace App\Admin\Controllers;

use App\Order;
use App\OrderRefund;
use App\ReminderItem;
use EasyWeChat\Payment\Application;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\MessageBag;

class OrderRefundsController extends Controller
{
    use ModelForm;

    protected $payment;

    public function __construct(Application $payment)
    {
        $this->payment = $payment;
    }

    /**
     * Index interface.
     *
     * @return Content
     */
    public function index()
    {
        return Admin::content(function (Content $content) {

            $content->header('部分退款记录');
            $content->description('这里是用户的部分退款记录');

            $content->body($this->grid());
        });
    }

    /**
     * Edit interface.
     *
     * @param $id
     * @return Content
     */
    public function edit($id)
    {
        return Admin::content(function (Content $content) use ($id) {

            $content->header('');
            $content->description('');

            $content->body($this->form()->edit($id));
        });
    }

    /**
     * Create interface.
     *
     * @return Content
     */
    public function create()
    {
        return Admin::content(function (Content $content) {

            $content->header('');
            $content->description('');

            $content->body($this->form());
        });
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Admin::grid(OrderRefund::class, function (Grid $grid) {
            $grid->model()->orderBy('created_at', 'desc');
            $grid->id('ID')->sortable();
            $grid->column('order_id', '订单ID');
            $grid->column('desc', '退款原因');
            $grid->column('amount', '退款金额（元）');
            $grid->column('refund_no', '退款号');
            $grid->column('refund_status', '退款状态');
            $grid->column('result', '退款结果');
            $grid->created_at("创建时间")->sortable();
            $grid->updated_at("更新时间")->sortable();

            // 不在页面显示 `新建` 按钮，因为我们不需要在后台新建用户
//            $grid->disableCreateButton();

            $grid->actions(function ($actions) {
                // 不在每一行后面展示删除按钮
                $actions->disableDelete();
                // 不在每一行后面展示编辑按钮
                $actions->disableEdit();
            });
            $grid->tools(function ($tools) {
                // 禁用批量删除按钮
                $tools->batch(function ($batch) {
                    $batch->disableDelete();
                });
            });

            $grid->filter(function($filter){

                // 去掉默认的id过滤器
                $filter->disableIdFilter();

                // 在这里添加字段过滤器
            });
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Admin::form(OrderRefund::class, function (Form $form) {
            $form->hidden('refund_no');
            $form->hidden('refund_status', '退款状态');
            $form->text('order_id', '订单ID')->rules('required|numeric');
            $form->text('desc', '退款原因')->rules('required');
            $form->currency('amount', '退款金额')->symbol('￥')->rules('required|numeric');

            $form->saving(function (Form $form) {
                $order = Order::find($form->order_id);
                if ($order && $order->payment_method == Order::PAYMENT_WALLET) {
                    $error = new MessageBag([
                        'title'   => '错误',
                        'message' => '使用余额支付的订单不能在这里退款',
                    ]);

                    return back()->with(compact('error'));
                }
                $form->refund_no = OrderRefund::getAvailableRefundNo();
                $form->refund_status = OrderRefund::REFUND_STATUS_PENDING;
                $result = $this->payment->refund->byOutTradeNumber($order->no, $form->refund_no, $order->total_amount*100, $form->amount*100, [
                    // 可在此处传入其他参数，详细参数见微信支付文档
                    'refund_desc' => $form->desc,
                ]);
                Log::info('order refunds: order amount='.$order->total_amount);
                Log::info('order refunds: amount='.$form->amount);
                Log::info('order refunds:'.json_encode($result));
                if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS') {
                    $form->refund_status = OrderRefund::REFUND_STATUS_SUCCESS;
                }
                $form->result = json_encode($result);
            });
        });
    }
}
