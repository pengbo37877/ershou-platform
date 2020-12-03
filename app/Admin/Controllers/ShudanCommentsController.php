<?php

namespace App\Admin\Controllers;

use App\Book;
use App\ReminderItem;
use App\Shudan;

use App\ShudanComment;
use App\ShudanItem;
use App\User;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;

class ShudanCommentsController extends Controller
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

            $content->header('这里是书单中书');
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

            $content->header('这里是编辑书单里的书的');
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

            $content->header('这里可以新增一本书单里的书');
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
        return Admin::grid(ShudanComment::class, function (Grid $grid) {

            $grid->id('ID')->sortable();
            $grid->column('书')->display(function (){
                return "<img src='".$this->book->cover_replace."' style='width:80px;border:1px solid #222;'/><br>".
                    '<span style="font-size: 15px;font-weight: 700;">'.$this->book->name.'</span><br>'.
                    '<span style="color:#ccc;">'.$this->book->author.'</span><br>'.
                    '<span style="color:#ccc;">'.$this->book->press.'</span><br>'.
                    '<span style="color: green;">豆瓣评分：'.$this->book->rating_num.'</span><br>'.
                    '<span style="color: dodgerblue;">'.$this->book->isbn.'</span><br>'.
                    '<span style="color:#aaa;font-size: 12px;">分类：'.$this->book->category.'</span><br>'.
                    '<span style="color:#aaa;">'.$this->book->publish_year.'</span><br>'.
                    '<span style="color:red;">想要'.$this->book->reminder_count.'</span><br>'.
                    '<span style="color:#aaa;">subjectid：'.$this->book->subjectid.'</span>';
            });
            $grid->shudan_id('书单ID')->editable();
            $grid->comment_id('评论ID');
            $grid->book_id('书ID')->sortable();
            $grid->use_cover('使用本书的封面')->editable('select', [
                0 => '不用',
                1 => '使用',
            ]);
            $grid->created_at('创建时间')->sortable();
            $grid->updated_at('更新时间')->sortable();

            // 不在页面显示 `新建` 按钮，因为我们不需要在后台新建用户
//            $grid->disableCreateButton();

            $grid->actions(function ($actions) {
                // 不在每一行后面展示删除按钮
//                $actions->disableDelete();
                // 不在每一行后面展示编辑按钮
//                $actions->disableEdit();
            });
            $grid->tools(function ($tools) {
                // 禁用批量删除按钮
//                $tools->batch(function ($batch) {
//                    $batch->disableDelete();
//                });
            });

            $grid->filter(function($filter){

                // 去掉默认的id过滤器
                $filter->disableIdFilter();

                // 在这里添加字段过滤器
                $filter->equal('shudan_id', '书单ID');
                $filter->equal('book_id', '书ID');
                $filter->where(function ($query) {
                    $query->whereHas('book', function ($query) {
                        $query->where('name', 'like', "{$this->input}%");
                    });
                }, '书名');
                $filter->where(function ($query) {
                    $query->whereHas('book', function ($query) {
                        $query->where('author', 'like', "{$this->input}%");
                    });
                }, '作者');
                $filter->where(function ($query) {
                    $query->whereHas('book', function ($query) {
                        $query->where('press', 'like', "{$this->input}%");
                    });
                }, '出版社');
                $filter->in('use_cover', '使用该书的封面')->select([
                    0 => '不用',
                    1 => '使用',
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
        return Admin::form(ShudanComment::class, function (Form $form) {
            $form->tab('基本信息', function ($form){
                $form->display('id', 'ID');
                $form->text('shudan_id', '书单ID');
                $form->text('comment_id', '评论ID');
                $form->text('book_id', '书ID');
                $form->select('use_cover', '使用该书的封面')->options([
                    0 => '不用',
                    1 => '使用',
                ]);
                $form->editor('body', '文案');
            });
        });
    }
}
