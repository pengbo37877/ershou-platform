<?php

namespace App\Admin\Controllers;

use App\Book;
use App\ReminderItem;
use App\Shudan;

use App\ShudanItem;
use App\User;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;

class ShudansController extends Controller
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

            $content->header('这里是全部的书单');
            $content->description('过瘾');

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

            $content->header('这里是编辑书单的');
            $content->description('也很过瘾');

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

            $content->header('这里可以新增一个书单');
            $content->description('是不是很过瘾');

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
        return Admin::grid(Shudan::class, function (Grid $grid) {
            $grid->model()->orderByDesc('id');
            $grid->id('ID')->sortable();
            $grid->title('主题');
            $grid->cover('封面')->display(function ($cover){
                return "<img src='".$cover."' style='width:100px;'/>";
            });
            $grid->desc('描述')->style('width:300px;');
            $grid->open('是否开放')->editable('select', [
                Shudan::STATUS_CLOSE => '关闭',
                Shudan::STATUS_OPEN => '开放',
            ]);
            $grid->created_at('创建时间')->sortable();
            $grid->updated_at('更新时间')->sortable();

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
                $filter->in('open', '书单状态')->multipleSelect([
                    Shudan::STATUS_CLOSE => '已关闭',
                    Shudan::STATUS_OPEN => '开放中'
                ]);
                $filter->like('title', '书单主题');
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
        return Admin::form(Shudan::class, function (Form $form) {
            $form->tab('基本信息', function ($form){
                $form->display('id', 'ID');
                $form->text('title', '主题');
                $form->image('cover', '封面')->help('请使用350x500比例的图片');
                $form->color('color', '渐变主色')->default('#000000');
                $form->textarea('desc', '描述');
                $form->select('open', '状态')->options([
                    Shudan::STATUS_CLOSE => '关闭',
                    Shudan::STATUS_OPEN => '开放',
                ]);
            });
        });
    }
}
