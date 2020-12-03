<?php

namespace App\Admin\Controllers;

use App\Jobs\SendLotteryNotificationJob;
use App\Lottery;
use App\LotteryUser;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;

class LotteriesController extends Controller
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

            $content->header('抽奖');
            $content->description('你的运气好不好');

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

            $content->header('修改抽奖');
            $content->description('已经上线的最好别修改了');

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

            $content->header('新开一个抽奖');
            $content->description('检验一下你的运气');

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
        return Admin::grid(Lottery::class, function (Grid $grid) {
            $grid->model()->orderBy('id', 'desc');
            $grid->id('ID')->sortable();
            $grid->title('标题');
            $grid->sub_title('副标题');
            $grid->image('奖品图片')->display(function($image) {
                return "<img src='".$image."' style='width:80px'/>";
            });
//            $grid->desc('推荐语');
            $grid->winner_count('获奖人数');
            $grid->column('获奖人')->display(function() {
                $winners = $this->winners;
                $new_winners = $winners->map(function($winner) {
                    return $winner->user_id;
                });
                return $new_winners->implode('<br>');
            });
            $grid->participants_count('参与人数');
            $grid->type('类型')->editable('select', [
                Lottery::TYPE_TIME => Lottery::$typeMap[Lottery::TYPE_TIME],
                Lottery::TYPE_COUNT => Lottery::$typeMap[Lottery::TYPE_COUNT],
            ]);
            $grid->start_at('开始时间');
            $grid->end_at('开奖时间');
            $grid->end_count('开奖人数');
            $grid->status('状态')->editable('select', [
                Lottery::STATUS_NOT_START => Lottery::$statusMap[Lottery::STATUS_NOT_START],
                Lottery::STATUS_RUNNING => Lottery::$statusMap[Lottery::STATUS_RUNNING],
                Lottery::STATUS_END_WITH_RESULT => Lottery::$statusMap[Lottery::STATUS_END_WITH_RESULT],
                Lottery::STATUS_END_WITH_NOTHING => Lottery::$statusMap[Lottery::STATUS_END_WITH_NOTHING],
            ]);
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
//                $filter->disableIdFilter();

                // 在这里添加字段过滤器
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
        return Admin::form(Lottery::class, function (Form $form) {

            $form->display('id', 'ID');
            $form->text('title', '标题');
            $form->text('sub_title', '副标题');
            $form->image('image', '奖品图片')->uniqueName();
            $form->textarea('desc', '推荐语')->rows(5);
            $form->number('winner_count', '获奖人数');
            $form->select('type', '类型')->options([
                Lottery::TYPE_TIME => Lottery::$typeMap[Lottery::TYPE_TIME],
                Lottery::TYPE_COUNT => Lottery::$typeMap[Lottery::TYPE_COUNT],
            ]);
            $form->datetime('start_at', '开始时间');
            $form->datetime('end_at', '开奖时间')->help('定时抽奖 填这个');
            $form->number('end_count', '开奖人数')->help('满人抽奖 填这个');
            $form->select('status', '状态')->options([
                Lottery::STATUS_NOT_START => Lottery::$statusMap[Lottery::STATUS_NOT_START],
                Lottery::STATUS_RUNNING => Lottery::$statusMap[Lottery::STATUS_RUNNING],
                Lottery::STATUS_END_WITH_RESULT => Lottery::$statusMap[Lottery::STATUS_END_WITH_RESULT],
                Lottery::STATUS_END_WITH_NOTHING => Lottery::$statusMap[Lottery::STATUS_END_WITH_NOTHING],
            ]);
            $form->editor('body', '图文详情');

            $form->saved(function(Form $form) {
                $lottery = $form->model();
                if ($lottery->type == Lottery::TYPE_TIME && $lottery->status == Lottery::STATUS_END_WITH_RESULT) {
                    $randomUsers = LotteryUser::where('lottery_id', $lottery->id)->get()
                        ->random($lottery->winner_count);
                    $randomUsers->each(function($u){
                        $u->win=1;
                        $u->save();
                    });
                    SendLotteryNotificationJob::dispatch($lottery)->delay(now()->addMinute(1));
                }
            });
        });
    }
}
