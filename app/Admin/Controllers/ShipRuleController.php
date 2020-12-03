<?php

namespace App\Admin\Controllers;

use App\Book;
use App\ShipRule;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\MessageBag;
use function Sodium\add;

class ShipRuleController extends Controller
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

            $content->header('运费规则');
            $content->description('运费规则');

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
            $ship_rule = ShipRule::find($id);
            $articleView = view('admin.updateShipRule2',compact('ship_rule'))->render();
            $content->header('编辑运费规则');
            $content->description('编辑运费规则');
            $content->row($articleView);
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
            $ship_rule = null;
            $articleView = view('admin.addShipRule')->render();
            $content->header('添加运费规则');
            $content->description('添加运费规则');
            $content->row($articleView);
        });
    }

    public function createShipRule(){
        $id = \request('id');
        $name = \request('name');
        $content = \request('content');
        $reject = \request('reject');
        $base_price = \request('base_price');
        try {
            if ($id) {
                $row = ShipRule::find($id)->update([
                    'name' => $name,
                    'content' => $content,
                    'reject' => $reject,
                    'base_price'=>$base_price
                ]);
            } else {
                $row = ShipRule::create([
                    'name' => $name,
                    'content' => $content,
                    'reject' => $reject,
                    'base_price'=>$base_price
                ]);
            }
            return response()->json(['code'=>200,'msg'=>'成功']);
        }catch (\Exception $e){
            return response()->json(['code'=>500,'msg'=>$e->getMessage()]);
        }
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Admin::grid(ShipRule::class, function (Grid $grid) {

            $grid->id('ID')->sortable();
            $grid->name('名称')->editable();
            $grid->reject('拒发货地区')->display(function ($areas){
                $areas = explode(",",$areas);
                $html = '';
                foreach ($areas as $area){
                    $html .= '<p class="label label-danger">'.$area.'</p><br>';
                }
                return $html;
            });
            $grid->base_price('基础运费')->editable();
            $grid->content('规则内容')->display(function ($content){
                $html = '';
                if($content && $content != '[]'){
                    foreach (json_decode($content,true) as $item){
                        $html .= '<p style="margin-top: 5px;" class="label label-info">'.$item["areas"].'========'.$item["addition"].'元</p><br>';
                    }
                    return $html;
                }else{
                    return '空';
                }
            });
            $grid->created_at('创建时间');
            $grid->updated_at('更新时间');

            $grid->actions(function ($actions) {
                $actions->disableView();
            });
            $grid->tools(function ($tools) {
                // 禁用批量删除按钮
                $tools->batch(function ($batch) {
                });
            });

            $grid->filter(function ($filter) {

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
        return Admin::form(ShipRule::class, function (Form $form) {
            // 行内编辑必须在form()里定义需要编辑的字段
            $form->text('name','名称');
            $form->currency('base_price','基础运费');
            $form->saving(function (Form $form){
                Log::info('saving');
            });
        });
    }
}
