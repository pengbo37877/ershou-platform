<?php

namespace App\Admin\Controllers;

use App\BookShop;
use App\Order;
use App\Wallet;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;

class BookShopController extends Controller
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

            $content->header('书店');
            $content->description('供应商数据');

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
        return Admin::grid(BookShop::class, function (Grid $grid) {
            $grid->id('ID')->sortable();
            $grid->column('shop_name','供应商');
            $grid->column('ship_price','单件运费')->display(function (){
                return $this->ship_price.'元';
            });
            $grid->column('express','快递公司');
            $grid->column('addr','发货地址');
            $grid->column('username','负责人');
            $grid->column('phone','联系方式');
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
        return Admin::form(BookShop::class, function (Form $form) {

            $form->display('id', 'ID');
            $form->text('shop_name', '供应商');
            $form->select('express', '快递公司')->options([
                'ZTO'=>'中通'
            ]);
            $form->number('ship_price', '单件运费');
            $form->text('username', '联系人');
            $form->text('phone', '电话');
            $form->text('addr', '发货地址');

            $form->submitted(function(Form $form) {

            });
        });
    }
}
