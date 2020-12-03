<?php

namespace App\Admin\Controllers;

use App\Coupon;
use App\Juzi;
use App\Picture;
use App\Wallet;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;

class JuzisController extends Controller
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

            $content->header('句子迷');
            $content->description('这里是经典美句');

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

            $content->header('修改句子数据');
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

            $content->header('新增句子');
            $content->description('遇到经典句子，怎能放过');

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
        return Admin::grid(Juzi::class, function (Grid $grid) {
            $grid->model()->orderBy('id', 'desc');
            $grid->id('ID')->sortable();
            $grid->picture_id('配图')->display(function ($id){
                $picture = Picture::find($id);
                if ($picture) {
                    return "<img src='" . $picture->image . "' width='40px'>";
                }
                return "<img src='/images/jz-default-image.jpg' width='40px'>";
            });
            $grid->body('句子');
            $grid->author('作者');
            $grid->book_name('书');

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
                $filter->like('author', '作者');
                $filter->like('book_name', '书');
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
        return Admin::form(Juzi::class, function (Form $form) {

            $form->display('id', 'ID');
            $form->number('picture_id', '图片ID')->rules('required');
            $form->editor('body', '句子');
            $form->text('author', '作者');
            $form->text('book_name', '书');
        });
    }
}
