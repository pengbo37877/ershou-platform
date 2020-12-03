<?php

namespace App\Admin\Controllers;

use App\Admin\Extensions\AcceptReport;
use App\Admin\Extensions\DenyReport;
use App\Book;
use App\Coupon;

use App\Events\RecoverReportAccept;
use App\RecoverReport;
use App\SaleItem;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;

class RecoverReportsController extends Controller
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

            $content->header('扫码卖书的反馈');
            $content->description('用户认为这些书可以收');

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
            $content->description('可以收的书在这里改');

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
        return Admin::grid(RecoverReport::class, function (Grid $grid) {
            $grid->model()->orderBy('id', 'desc');
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
            $grid->book_id('Book ID');
            $grid->column('封面')->display(function(){
                return "<img src='".$this->book->cover_replace."' style='width:60px'/>";
            });
            $grid->column('书')->display(function (){
                $a = "<a href='/admin/books?isbn=".$this->book->isbn."' target='_blank'>".$this->book->name."</a><br>";
                return $a. '<span>'.$this->book->isbn.'</span><br>'.
                    '<span style="color:#ff7701">价格：'.$this->book->price.'</span><br>'.
                    '<span style="color:#ccc;">'.$this->book->author.'</span><br>'.
                    '<span style="color:#ccc;">'.$this->book->press.'</span><br>'.
                    '<span style="color: green;">豆瓣评分：'.$this->book->rating_num.' / '.$this->book->num_raters.'</span><br>'.
                    '<span style="color:#aaa;font-size: 12px;">分类：'.$this->book->category.'</span><br>'.
                    '<span style="color:#aaa;">'.$this->book->publish_year.'</span><br>'.
                    '<span style="color:#aaa;">subjectid：'.$this->book->subjectid.'</span>';
            });
            $grid->column('收取暂况')->display(function(){
                $r = '';
                if($this->book->can_recover==1){
                    $r = "<span class='label label-success'>收取</span>";
                }else{
                    $r = "<span class='label label-primary'>不收</span>";
                }
                return $r;
            });
            $grid->column('审核结果')->display(function(){
                if ($this->book->admin_user_id>0) {
                    return "<span class='label label-success'>管理员(".$this->book->admin_user_id.")已审核</span>";
                }
                return "<span class='label label-default'>未审核</span>";
            });
            $grid->type('理由类型')->display(function ($type){
                switch ($type) {
                    case RecoverReport::TYPE_GOOD:
                        return '内容好';
                    case RecoverReport::TYPE_OUT_OF_PRINT:
                        return '绝版书';
                    case RecoverReport::TYPE_SUIT:
                        return '系列书';
                }
            });
            $grid->reason('用户认为收的理由');

            // 不在页面显示 `新建` 按钮，因为我们不需要在后台新建用户
            $grid->disableCreateButton();

            $grid->actions(function ($actions) {
                // 不在每一行后面展示删除按钮
                $actions->disableDelete();
                // 不在每一行后面展示编辑按钮
                $actions->disableEdit();
                $actions->disableView();
                $book = Book::find($actions->row['book_id']);
                if ($book->admin_user_id>0) {
                    if ($book->can_recover==true) {
                        $actions->append(new DenyReport($actions->row['book_id']));
                    }else{
                        $actions->append(new AcceptReport($actions->row['id']));
                    }
                }else {
                    $actions->append(new AcceptReport($actions->row['id']));
                    $actions->append(new DenyReport($actions->row['book_id']));
                }
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
        return Admin::form(RecoverReport::class, function (Form $form) {

            $form->display('id', 'ID');
        });
    }

    public function accept()
    {
        $id = request('id');
        $recoverReport = RecoverReport::with(['book', 'user'])->find($id);
        $book = $recoverReport->book;
        if ($book) {
            $book->admin_user_id = Admin::user()->id;
            $book->can_recover = true;
            $book->discount = 10;
            $book->save();
        }
        event(new RecoverReportAccept($recoverReport));
        return response()->json(['msg' => 'success']);
    }

    public function deny()
    {
        $book_id = request('book_id');
        $book = Book::find($book_id);
        if ($book) {
            $book->admin_user_id = Admin::user()->id;
            $book->can_recover = false;
            $book->save();
        }
        return response()->json(['msg' => 'success']);
    }
}
