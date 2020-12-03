<?php

namespace App\Admin\Controllers;

use App\Book;
use App\DouList;
use App\Shudan;

use App\ShudanItem;
use App\User;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;

class DoulistsController extends Controller
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

            $content->header('这里是全部的豆列');
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

            $content->header('这里是编辑豆列');
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

            $content->header('这里可以新增一个豆列');
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
        return Admin::grid(DouList::class, function (Grid $grid) {

            $grid->id('ID')->sortable();
            $grid->doulist_id('豆列ID')->sortable();
            $grid->name('豆列');
//            $grid->desc('描述');
            $grid->start('下次抓取起点')->sortable();
            $grid->book_count('书籍数量')->sortable();
            $grid->following_count('关注人数')->sortable();
            $grid->recommend_count('推荐人数')->sortable();
            $grid->subjectids('书')->display(function ($subjectids){
                $id_array = explode(',', $subjectids);
                $result = array_map(function ($subjectid) {
                    $book = Book::where('subjectid', $subjectid)->first();
                    if ($book) {
                        $s = '';
                        if ($book->can_recover) {
                            $s = '<span class="label label-success">收</span>';
                        }else{
                            $s = '<span class="label label-primary">不收</span>';
                        }
                        return '<span class="label label-primary">'.$book->rating_num.'/'.$book->num_raters.
                            '</span><span class="label label-success">'.
                            $book->name.'</span>['.$book->author.']<span class="label label-info">'.$book->publish_year.
                            '</span>('.$book->press.')<span class="label label-warning">'.$book->price.'</span>'.$s.
                            $book->isbn.' / '.$book->id.'<br>';
                    }
                }, $id_array);
                return join('', $result);
            });
            // 不在页面显示 `新建` 按钮，因为我们不需要在后台新建用户
            $grid->disableCreateButton();

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
                $filter->like('name', '书单主题');
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
                $form->text('doulist_id', '豆列ID');
                $form->text('name', '主题');
                $form->text('desc', '描述');
                $form->text('start', '下次抓取起点');
                $form->text('book_count', '书籍数量');
                $form->text('following_count', '关注人数');
                $form->text('recommend_count', '推荐人数');
                $form->text('subjectids', 'subjectids');
            });
        });
    }
}
