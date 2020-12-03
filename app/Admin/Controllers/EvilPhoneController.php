<?php

namespace App\Admin\Controllers;

use App\Order;
use App\EvilPhone;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;

class EvilPhoneController extends Controller
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

            $content->header('恶意');
            $content->description('恶意订单涉及的手机号');

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

            $content->header('修改');
            $content->description('谨慎修改');

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

            $content->header('添加恶意电话');
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
        return Admin::grid(EvilPhone::class, function (Grid $grid) {
            $grid->model()->orderBy('updated_at', 'desc');
            $grid->id('ID')->sortable();
            $grid->order_id('订单ID');
            $grid->user_id('用户ID');
            $grid->username('寄件名');
            $grid->phone('电话');
            $grid->created_at('创建时间');
            $grid->updated_at('更新时间');

            $grid->actions(function ($actions) {
                // 不在每一行后面展示删除按钮
                $actions->disableDelete();
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
                $filter->phone('phone', '电话');
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
        return Admin::form(EvilPhone::class, function (Form $form) {

            $form->display('id', 'ID');
            $form->text('user_id', '用户ID')->help('可为空');
            $form->text('order_id', '订单ID')->help('可为空');
            $form->text('username', '寄件名')->help('可为空');
            $form->mobile('phone', '电话')->rules('required');
        });
    }
}
