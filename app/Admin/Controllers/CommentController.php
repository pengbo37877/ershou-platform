<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/9/10
 * Time: 15:44
 */
namespace App\Admin\Controllers;

use App\Comment;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use function foo\func;
use Illuminate\Http\Request;

//use Milon\Barcode\DNS1D;

class CommentController
{

    private static $DNS1D;

    public function __construct()
    {
//        self::$DNS1D = $DNS1D;
    }

    public function index()
    {
        return Admin::content(function (Content $content) {
            $content->header('评论');
            $content->description('书单评论审核');
            $content->body($this->grid());
        });
    }

    public function update(Request $request,$id)
    {
        $comment = Comment::find($id);
        $comment->open = $request->open;
        $comment->save();
        return response()->json([
            'request'=>$request,
            'id'=>$id
        ]);
    }

    protected function grid()
    {
        return Admin::grid(Comment::class,function (Grid $grid){
            $grid->model()->orderBy('id','desc');
            $grid->column('id','ID');
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
            $grid->column('想要的书')->display(function(){
                $isbn = strlen($this->isbn)==13?$this->isbn:'9789624516739';
                if(strpos($this->isbn,'x') !== false){
                    $isbn = '9789624516739';
                }
                if(strpos($this->isbn,'X') !== false){
                    $isbn = '9789624516739';
                }
//                $imgBase64 = self::$DNS1D->getBarcodePNG($isbn, "EAN13");
                return '<p>'.$this->book->name.'</p>'.
                    '<p>'.$this->book->author.'</p>'.
                    '<p>'.$this->book->isbn.'</p>'.
//                    '<img src="data:image/png;base64,' . $imgBase64 . '" alt="barcode" /><br>'.
                    '<p style="color: green">豆瓣评分：'.$this->book->rating_num.' / '.$this->book->num_raters.'</p>'.
                    '<p style="color: #ff7701">价格：'.$this->book->price.'</p>'.
                    '<p>出版社：'.$this->book->press.'</p>'.
                    '<p>出版年：'.$this->book->publish_year.'</p><br>';
            });
            $grid->column('body','评论内容');
            $grid->column('open','通过?')->editable('select',[ 1 => '通过', 0 => '不通过']);
            $grid->column('created_at','评论时间');
            $grid->disableCreateButton();

            $grid->actions(function ($actions) {
                $actions->disableDelete();
                $actions->disableEdit();
            });
            $grid->tools(function ($tools) {
                $tools->batch(function ($batch) {
                    $batch->disableDelete();
                });
            });
            $grid->filter(function($filter){
                $filter->equal('book_id', '书ID');
                $filter->equal('user_id', '用户ID');
                $filter->where(function ($query) {
                    $query->whereHas('book', function ($query) {
                        $query->where('name', 'like', "{$this->input}%");
                    });
                }, '书名');
                $filter->where(function ($query) {
                    $query->whereHas('user', function ($query) {
                        $query->where('nickname', 'like', "{$this->input}%");
                    });
                }, '用户名');
            });
        });
    }
}