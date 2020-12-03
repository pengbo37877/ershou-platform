<?php

namespace App\Admin\Controllers;

use App\Coupon;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;

class CouponsController extends Controller
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

            $content->header('现金券/红包');
            $content->description('这里是用户的现金券和红包数据');

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

            $content->header('修改红包数据');
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

            $content->header('给用户发红包');
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
        return Admin::grid(Coupon::class, function (Grid $grid) {
            $grid->model()->orderBy('id', 'desc');
            $grid->id('ID')->sortable();
            $grid->name('现金券/红包');
            $grid->code('编码');
            $grid->user_id('用户ID');
            $grid->user()->nickname('用户');
            $grid->from_user('来源用户ID');
            $grid->from()->nickname('来源用户');
            $grid->type('类型')->display(function ($type){
                switch ($type) {
                    case Coupon::TYPE_FIXED:
                        return '固定金额';
                    case Coupon::TYPE_PERCENT:
                        return '固定比例';
                }
            });
            $grid->value('金额');
            $grid->order_type('可用于')->display(function ($type){
                switch ($type) {
                    case Coupon::ORDER_TYPE_SALE:
                        return '买书订单';
                    case Coupon::ORDER_TYPE_RECOVER:
                        return '收书订单';
                }
            });
            $grid->min_amount('订单最低金额')->display(function($amount){
                if ($amount==0) {
                    return "<span class='label label-success'>无限制</span>";
                }
                return '￥'.$amount;
            });
            $grid->used('使用情况')->editable('select', [
                1 => '已使用',
                0 => '未使用'
            ]);
            $grid->enabled('是否可用')->editable('select', [
                1 => '可用',
                0 => '不可用'
            ]);
            $grid->not_before('在这之后');
            $grid->not_after('在这之前');

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
        return Admin::form(Coupon::class, function (Form $form) {

            $form->display('id', 'ID');
            $form->display('code', '现金券编码');
            $form->text('name', '现金券名称')->rules('required|min:3|max:8');
            $form->text('user_id', '用户ID')->rules('required');
            $form->select('type', '类型')->options([
                Coupon::TYPE_FIXED => Coupon::$typeMap[Coupon::TYPE_FIXED],
                Coupon::TYPE_PERCENT => Coupon::$typeMap[Coupon::TYPE_PERCENT],
            ])->rules('required');
            $form->currency('value', '金额')->symbol('￥')->rules('required')->help('如果是比例类型请填1~99之间的数字');
            $form->select('order_type', '可用于')->options([
                Coupon::ORDER_TYPE_RECOVER => Coupon::$orderTypeMap[Coupon::ORDER_TYPE_RECOVER],
                Coupon::ORDER_TYPE_SALE => Coupon::$orderTypeMap[Coupon::ORDER_TYPE_SALE],
            ])->rules('required');
            $form->radio('used', '使用情况')->options(['1' => '已使用', '0'=> '未使用'])->default('0');
            $form->currency('min_amount', '订单最低金额')->symbol('￥')->help('留空表示没有限制');
            $form->radio('enabled', '是否生效')->options(['1' => '生效', '0'=> '不生效'])->default('1');
            $form->datetime('not_before', '生效时间')->help('留空表示立即生效');
            $form->datetime('not_after', '过期时间')->help('留空表示永不过期');
        });
    }
}
