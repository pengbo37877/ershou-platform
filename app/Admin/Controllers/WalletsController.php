<?php

namespace App\Admin\Controllers;

use App\Order;
use App\Wallet;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;

class WalletsController extends Controller
{
    use ModelForm;

    /**
     * Index interface.
     *
     * @return Content
     */
    public function index()
    {
        return Admin::content(function (Content $content) {

            $content->header('钱包');
            $content->description('这里是大家的财务数据，赚的花的都在这里');

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

            $content->header('修改钱包数据');
            $content->description('谨慎修改，这里是钱');

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

            $content->header('给用户加钱或者扣钱');
            $content->description('创建之前请主动检查三遍，问问自己你确定要这样做吗？');

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
        return Admin::grid(Wallet::class, function (Grid $grid) {
            $grid->model()->orderBy('id', 'desc');
            $grid->id('ID')->sortable();
            $grid->order_id('订单ID');
            $grid->user_id('用户ID');
            $grid->user()->nickname('用户');
            $grid->type('类型')->display(function ($type){
                switch ($type) {
                    case Wallet::TYPE_BUY_BOOK:
                        return '买书支出';
                    case Wallet::TYPE_SALE_BOOK:
                        return '卖书收入';
                    case Wallet::TYPE_TRANSFER_IN:
                        return '充值';
                    case Wallet::TYPE_TRANSFER_OUT:
                        return '提现';
                    case Wallet::TYPE_BUY_BOOK_REFUND:
                        return '订单取消退款';
                    default:
                        return 'WO CAO';
                }
            });
            $grid->status('状态')->display(function ($status) {
                switch ($status) {
                    case Wallet::STATUS_PENDING:
                        return '进行中';
                    case Wallet::STATUS_SUCCESS:
                        return '成功';
                    case Wallet::STATUS_FAILED:
                        return '失败';
                    default:
                        return 'WO CAO';
                }
            });
            $grid->amount('金额');
            $grid->memo('说明');
//            $grid->memo('说明');
            $grid->result('付款结果')->display(function($result){
                if (empty($result)) {
                    return '';
                }
                $arr = json_decode($result, true);
                if (isset($arr['status']) && $arr['status'] == 'SUCCESS') {
                    return '成功';
                }else if(isset($arr['return_msg'])) {
                    return $arr['return_msg'];
                }else{
                    $reason = $arr['reason'];
                    return $reason;
                }
            });
            $grid->created_at('创建时间');
            $grid->updated_at('更新时间');

            // 不在页面显示 `新建` 按钮，因为我们不需要在后台新建用户
//            $grid->disableCreateButton();

            $grid->actions(function ($actions) {
                // 不在每一行后面展示删除按钮
                $actions->disableDelete();
                // 不在每一行后面展示编辑按钮
//                $actions->disableEdit();
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
                $filter->equal('user_id', '用户ID');
                $filter->equal('order_id', '订单ID');
                $filter->in('type', '款项类型')->multipleSelect([
                    Wallet::TYPE_SALE_BOOK => '卖书收入',
                    Wallet::TYPE_BUY_BOOK => '买书支出',
                    Wallet::TYPE_TRANSFER_OUT => "提现",
                    Wallet::TYPE_TRANSFER_IN => "充值",
                    Wallet::TYPE_BUY_BOOK_REFUND => "订单取消退款",
                ]);
                $filter->in('status', '款项状态')->multipleSelect([
                    Wallet::STATUS_PENDING => '进行中',
                    Wallet::STATUS_SUCCESS => '成功',
                    Wallet::STATUS_FAILED => '失败',
                ]);
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
        return Admin::form(Wallet::class, function (Form $form) {

            $form->display('id', 'ID');
            $form->text('user_id', '用户ID');
            $form->text('order_id', '订单ID')->help('不绑定订单这里请留空');
            $form->select('type', '款项类型')->options([
                Wallet::TYPE_SALE_BOOK => '卖书收入',
                Wallet::TYPE_BUY_BOOK => '买书支出',
                Wallet::TYPE_TRANSFER_OUT => "提现",
                Wallet::TYPE_TRANSFER_IN => "充值",
                Wallet::TYPE_BUY_BOOK_REFUND => "订单取消退款",
            ]);
            $form->select('status', '款项状态')->options([
                Wallet::STATUS_PENDING => '进行中',
                Wallet::STATUS_SUCCESS => '成功',
                Wallet::STATUS_FAILED => '失败',
            ]);
            $form->currency('amount', '金额')->symbol('￥')->help('支出，提现类型这里请填写负值');
            $form->text('memo', '说明')->help('如果是新建项目，这里务必填写说明');
            $form->submitted(function(Form $form) {
                if (!empty($form->order_id)) {
                    $order = Order::find($form->order_id);
                    if (!$order) {
                        $error = new MessageBag([
                            'title'   => '错误',
                            'message' => '没有找到ID为:'.$form->order_id.'的订单',
                        ]);

                        return back()->with(compact('error'));
                    }
                    $form->user_id = $order->user_id;
                }
            });
        });
    }
}
