<?php

namespace App\Admin\Controllers;

use App\ReminderItem;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\MessageBag;
use Milon\Barcode\DNS1D;

class RemindersController extends Controller
{
    use ModelForm;

    private static $DNS1D;

    public function __construct(DNS1D $DNS1D)
    {
        self::$DNS1D = $DNS1D;
    }

    /**
     * Index interface.
     *
     * @return Content
     */
    public function index()
    {
        return Admin::content(function (Content $content) {

            $content->header('到货提醒');
            $content->description('用户想要这些书');

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
        return Admin::grid(ReminderItem::class, function (Grid $grid) {
            $grid->model()->orderBy('created_at', 'desc');
            $grid->id('ID')->sortable();
            $grid->column('用户')->display(function(){
                $sex='';
                if ($this->user->sex==1){
                    $sex = '男';
                }else if($this->user->sex==2){
                    $sex = '女';
                }else{
                    $sex = '性别不知';
                }
                return '<div style="text-align: center">'.
                    '<img style="width:50px;border-radius:25px;" src="'.$this->user->avatar.'">'.
                    '<p>'.$this->user->nickname.'</p>'.
                    '<p>[ '.$this->user->id.' ]</p>'.
                    '<p>'.$sex .'</p>'.
                    '<p>'.$this->user->province.'-'.$this->user->city.'</p></div>';
            });
            $grid->column('book.cover_replace', '封面')->display(function($img){
                return "<img src='$img' style='width: 64px'>";
            });
            $grid->column('想要的书')->display(function(){
                $isbn = strlen($this->isbn)==13?$this->isbn:'9789624516739';
                if(strpos($this->isbn,'x') !== false){
                    $isbn = '9789624516739';
                }
                if(strpos($this->isbn,'X') !== false){
                    $isbn = '9789624516739';
                }
                $imgBase64 = self::$DNS1D->getBarcodePNG($isbn, "EAN13");
                return '<p>'.$this->book->name.'</p>'.
                    '<p>'.$this->book->author.'</p>'.
                    '<p>'.$this->book->isbn.'</p>'.
                    '<img src="data:image/png;base64,' . $imgBase64 . '" alt="barcode" /><br>'.
                    '<p style="color: green">豆瓣评分：'.$this->book->rating_num.' / '.$this->book->num_raters.'</p>'.
                    '<p style="color: #ff7701">价格：'.$this->book->price.'</p>'.
                    '<p>出版社：'.$this->book->press.'</p>'.
                    '<p>出版年：'.$this->book->publish_year.'</p><br>';
            });
            $grid->column('book.reminder_count', '想要的人数')->sortable();;
            $grid->column('book.category', '标签');
            $grid->column('book.price', '价格')->editable();
            $grid->column('book.admin_user_id','决策')->editable('select', [
                0 => '算法',
                Admin::user()->id => '管理员'
            ]);
            $grid->column('book.can_recover', '收取情况')->editable('select', [ 1 => '收取', 0 => '不收']);
            $grid->created_at("创建时间")->sortable();

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
                $filter->equal('isbn', 'ISBN');
                $filter->equal('user_id', '用户ID');
                $filter->where(function ($query) {
                    $query->whereHas('book', function ($query) {
                        $query->where('name', 'like', "%{$this->input}%");
                    });
                }, '书名');
                $filter->where(function ($query) {
                    $query->whereHas('book', function ($query) {
                        $query->where('author', 'like', "%{$this->input}%");
                    });
                }, '作者');
                $filter->where(function ($query) {
                    $query->whereHas('book', function ($query) {
                        $query->where('translator', 'like', "%{$this->input}%");
                    });
                }, '译者');
                $filter->where(function ($query) {
                    $query->whereHas('book', function ($query) {
                        $query->where('press', 'like', "%{$this->input}%");
                    });
                }, '出版社');
                $filter->where(function ($query) {
                    $query->whereHas('book', function ($query) {
                        $query->where('rating_num', '>=', "{$this->input}");
                    });
                }, '评分>=');
                $filter->where(function ($query) {
                    $query->whereHas('book', function ($query) {
                        $query->where('rating_num', '<=', "{$this->input}");
                    });
                }, '评分<=');
                $filter->in('book.admin_user_id', '决策方')->multipleSelect([
                    0 => '机器决策',
                    Admin::user()->id => '管理员决策'
                ]);
                $filter->in('book.can_recover', '收取状态')->multipleSelect([
                    0 => '不收',
                    1 => '收',
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
        return Admin::form(ReminderItem::class, function (Form $form) {
            $form->display('book.name', '书名');
            $form->text('book.price', '价格');
            $form->text('book.can_recover', '收取情况');
            $form->text('book.admin_user_id', '决策');
        });
    }
}
