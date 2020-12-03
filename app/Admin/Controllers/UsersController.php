<?php

namespace App\Admin\Controllers;

use App\User;

use App\Wallet;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;
use Encore\Admin\Widgets\Box;
use Illuminate\Support\Facades\DB;
use Jenssegers\Agent\Agent;

class UsersController extends Controller
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
            // 页面标题
            $content->header('粉丝们');
            $content->description('这里是回流鱼的所有粉丝');
            $content->body($this->grid());
        });
    }

    protected function grid()
    {
        // 根据回调函数，在页面上
        return Admin::grid(User::class, function (Grid $grid) {
//            $grid->header(function($query) {
//                $sex = $query->select(DB::raw('count(sex) as count, sex'))->groupBy('sex')->get()->pluck('count', 'sex')->toArray();
//
//                $doughnut = view('admin.chart.sex', compact('sex'));
//
//                return new Box('性别比例', $doughnut);
//            });

            $grid->model()->orderBy('id', 'desc');
            // 创建一个列名为 ID 的列，内容是用户的 id 字段，并且可以在前端页面点击排序
            $grid->id('ID')->sortable();
            // 创建一个列名为 用户名 的列，内容是用户的 name 字段。下面的 email() 和 created_at() 同理
            $grid->nickname('用户名');
            $grid->avatar('头像')->display(function($avatar){
                return "<img src='".$avatar."' style='width:40px;'/>";
            });
            $grid->sex('性别')->display(function($sex){
                if ($sex == 1){
                    return '男';
                }else if($sex == 2){
                    return '女';
                }else{
                    return '不确定';
                }
            });
            $grid->column('来源地区')->display(function (){
                return '<p>'.$this->province.'-'.$this->city.'</p>';
            });
            $grid->column('关注来源')->display(function (){
                return '<span class="label label-success">'.$this->subscribe_scene.'</span><br>'.
                    '<span class="label label-success">来源用户ID：'.$this->qr_scene.'</span>';
            });
            $grid->column('终端信息')->display(function(){
                if (is_null($this->user_agent)) {
                    return '';
                }
                $agent = new Agent();
                $agent->setUserAgent($this->user_agent);
                $platform = $agent->platform();
                $pv = $agent->version($platform);
                return '<p>'.$platform.'-'.$pv.'</p>';
            });
            $grid->subscribe('关注')->display(function ($subscribe){
                if($subscribe==1){
                    return "<span class='label label-success'>关注</span>";
                }else{
                    return "<span class='label label-info'>取关了</span>";
                }
            });
//            $grid->column('扫码中')->display(function () {
//                $books = $this->for_sale_books->toArray();
//                if (count($books) == 0) {
//                    return '0';
//                }
//                $total = 0;
//                foreach ($books as $book) {
//                    if ($book['can_recover']) {
//                        $total = $total + floatval($book['price']) * $book['discount'] / 100;
//                    }
//                }
//                $nbs = array_map(function($book){
//                    if ($book['can_recover']) {
//                        return '<p>' . $book['name'].$book['id'] . ' 原价:' . $book['price'] . ' 折扣:' . $book['discount'] . '%'.
//                            "<span class='label label-success'>".$book['rating_num']."</span><span class='label label-success'>收取</span></p>";
//                    }
//                    return '<p>' . $book['name'].$book['id'] . ' 原价:' . $book['price'] . ' 折扣:' . $book['discount'] . '%'.
//                        "<span class='label label-success'>".$book['rating_num']."</span><span class='label label-default' style='text-decoration: line-through'>不收</span></p>";
//                }, $books);
//                return join('', $nbs).'<p>'.count($books).'本 ￥'.$total.'</p>';
//            });
            $grid->column('购物袋有')->display(function () {
                return '购'.$this->cart_items->count();
            });
            $grid->column('到货提醒')->display(function () {
                return '提醒'.$this->reminders->count();
            });
            $grid->column('书房中有')->display(function () {
                return '书房'.$this->on_shelf_books->count();
            });
            $grid->column('卖了')->display(function () {
                $price = Wallet::where('user_id', $this->id)->where('type', Wallet::TYPE_SALE_BOOK)
                    ->where('status', Wallet::STATUS_SUCCESS)->get()->sum->amount;
                return '卖'.$this->sold_books->count() . ' ￥'. $price;
            });
            $grid->column('余额￥')->display(function () {
                return $this->wallet_items->sum->amount;
            });
            $grid->created_at('注册时间');
            $grid->updated_at('最近登录')->sortable();
            // 不在页面显示 `新建` 按钮，因为我们不需要在后台新建用户
            $grid->disableCreateButton();

            $grid->actions(function ($actions) {
                $actions->disableView();
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
//                $filter->disableIdFilter();

                // 在这里添加字段过滤器
                $filter->in('sex', '性别')->multipleSelect([
                    0 => '女',
                    1 => '男',
                    '' => '不确定'
                ]);
                $filter->like('nickname', '昵称');
                $filter->like('province', '来源省份');
                $filter->like('city', '来源城市');
                $filter->like('subscribe_scene', '关注方式');
            });
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

            $content->header('header');
            $content->description('description');

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

            $content->header('header');
            $content->description('description');

            $content->body($this->form());
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Admin::form(User::class, function (Form $form) {

            $form->display('id', 'ID');

            $form->display('created_at', 'Created At');
            $form->display('updated_at', 'Updated At');
        });
    }
}
