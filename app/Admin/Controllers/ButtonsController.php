<?php

namespace App\Admin\Controllers;

use App\Button;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;
use Illuminate\Support\MessageBag;

class ButtonsController extends Controller
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

            $content->header('公众号菜单');
            $content->description('自定义公众号菜单');

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
        return Admin::grid(Button::class, function (Grid $grid) {
            $grid->model()->orderBy('cell');
            $grid->id('ID');
            $grid->column('btn_id','父级ID');
            $grid->column('type','类型');
            $grid->column('cell','菜单级别');
            $grid->column('name','按钮文字');
            $grid->column('url','链接');
            $grid->column('key','点击');

            $grid->created_at('创建时间');
            $grid->updated_at('更新时间');

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
        $arr = [];
        $btns = Button::where('cell',1)->where('type','list')->get();
        foreach ($btns as $btn){
            $arr[$btn->id] = $btn->name;
        }
        return Admin::form(Button::class, function (Form $form) use ($arr) {

            $form->display('id', 'ID');
            $form->text('name', '按钮文字')->required();
            $form->select('type', '类型')->default('view')->options([
                'view' => '网页链接',
                'click' => '点击按钮',
                'list' => '折叠菜单',
            ]);
            $form->select('btn_id','父级菜单')->options($arr)->help('添加子菜单时选择');
            $form->radio('cell', '菜单级别')->options([1 => '顶级',2 => '折叠'])->default(1);
            $form->text('url', '网页链接')->placeholder('类型为"网页链接"时填写，"/"开头，不包括域名');
            $form->text('key', '点击按钮符号')->placeholder('类型为"点击按钮"时填写，请询问后台');
            $form->submitted(function(Form $form) {
                $cell1 = Button::where('cell',1)->count();
                if ($form->type == "view" && empty($form->url)) {
                    $error = new MessageBag([
                        'title'   => '错误',
                        'message' => '网页链接必须填写',
                    ]);
                    return back()->with(compact('error'));
                }elseif ($form->type == "click" && empty($form->key)){
                    $error = new MessageBag([
                        'title'   => '错误',
                        'message' => '点击按钮符号必须填写',
                    ]);
                    return back()->with(compact('error'));
                }elseif ($form->cell == 1 && $cell1 >= 3){
                    $error = new MessageBag([
                        'title'   => '错误',
                        'message' => '顶级菜单已满',
                    ]);
                    return back()->with(compact('error'));
                }elseif ($form->cell == 2 && empty($form->btn_id)){
                    $error = new MessageBag([
                        'title'   => '错误',
                        'message' => '折叠菜单必须选择父级菜单',
                    ]);
                    return back()->with(compact('error'));
                }
            });
        });
    }
}
