<?php

namespace App\Admin\Controllers;

use App\Book;
use App\BookShop;
use App\BookSku;
use App\CartItem;
use App\Order;
use App\ShipRule;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;
use Encore\Admin\Show;
use function foo\func;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\MessageBag;

class NewBookController extends Controller
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

            $content->header('新书');
            $content->description('新书');

            $content->body($this->grid());
        });
    }

    public function show($id, Content $content)
    {
        return $content
            ->header('Detail')
            ->description('description')
            ->body($this->detail($id));
    }

    protected function detail($id)
    {
        $show = new Show(BookSku::findOrFail($id));

        return $show;
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
        return Admin::grid(BookSku::class, function (Grid $grid) {
            $grid->model()->where('ifnew', 1)->with('book_version')->with('ship_rule')->orderBy('updated_at', 'desc');
            $grid->id('ID')->sortable();
            $grid->column('书')->display(function () {
                if(!$this->book_version_id){
                    return "<img src='" . $this->book->cover_replace . "' style='width:80px;border:1px solid #222;'/><br>" .
                        '<span style="font-size: 15px;font-weight: 700;">' . $this->book->name . '</span><br>' .
                        '<span style="color:#ccc;">' . $this->book->author . '</span><br>' .
                        '<span style="color:#ccc;">' . $this->book->press . '</span><br>' .
                        '<span style="color: green;">豆瓣评分：' . $this->book->rating_num . '</span><br>' .
                        '<span style="color: dodgerblue;">' . $this->book->isbn . '</span><br>' .
                        '<span style="color:#aaa;font-size: 12px;">分类：' . $this->book->category . '</span><br>' .
                        '<span style="color:#aaa;">' . $this->book->publish_year . '</span><br>' .
                        '<span style="color:#aaa;">subjectid：' . $this->book->subjectid . '</span><br>' .
                        '<span style="color:red;">id： ' . $this->book->id . '</span>';
                }else{
                    $cover_replace = $this->book_version->cover ? $this->book_version->cover:$this->book->cover_replace;
                    $press = $this->book_version->press;
                    $mark = $this->book_version->title;
                    return "<img src='" . $cover_replace . "' style='width:80px;border:1px solid #222;'/><br>" .
                        '<span style="font-size: 15px;font-weight: 700;">' . $this->book->name . '</span><br>' .
                        '<span style="color:#ccc;">' . $this->book->author . '</span><br>' .
                        '<span style="color:#ccc;">' . $press . '</span><br>' .
                        '<span style="color: green;">豆瓣评分：' . $this->book->rating_num . '</span><br>' .
                        '<span style="color: dodgerblue;">' . $this->book->isbn . '</span><br>' .
                        '<span style="color:#aaa;font-size: 12px;">分类：' . $this->book->category . '</span><br>' .
                        '<span style="color:#aaa;">' . $this->book->publish_year . '</span><br>' .
                        '<span style="color:#aaa;">subjectid：' . $this->book->subjectid . '</span><br>' .
                        '<span style="color:red;">id： ' . $this->book->id . '</span><br>'.
                        '<label class="label label-info">版本说明： ' . $mark . '</label>';
                }
            });
            $grid->column('想要')->display(function () {
                return count($this->book->reminders);
            });
            $grid->column('已放入购物袋')->display(function () {
                $count = CartItem::where('book_id', $this->book_id)->count();
                $count2 = CartItem::where('book_sku_id', $this->id)->count();
                return "<span class='label label-primary'>书：" . $count . "</span><br>" .
                    "<span class='label label-primary'>SKU：" . $count2 . "</span><br>";
            });
            $grid->column('原价')->display(function(){
                if($this->book_version_id){
                    return '<p style="color: red;">版本'.$this->book_version->id.'</p><p>'.$this->book_version->price.'</p>';
                }
                return '<p style="color: red;">默认版本</p><p>'.$this->book->price.'</p>';
            });
            $grid->original_price('换算成人民币')->sortable();
            $grid->price('售价')->editable();
            $grid->column('其他平台售价')->display(function () {
                $prices = $this->book->prices;
                if (count($prices) > 0) {
                    $other_prices = $prices->first();
                    $dd_new_price = $other_prices->dd_new_price ? "<span class='label label-warning'>当当网：" . $other_prices->dd_new_price . "</span><br>" : '';
                    $jd_new_price = $other_prices->jd_new_price ? "<span class='label label-warning'>京东商城：" . $other_prices->jd_new_price . "</span><br>" : '';
                    $amz_new_price = $other_prices->amz_new_price ? "<span class='label label-warning'>亚马逊：" . $other_prices->amz_new_price . "</span><br>" : '';
                    $bc_new_price = $other_prices->bc_new_price ? "<span class='label label-warning'>中国图书网：" . $other_prices->bc_new_price . "</span><br>" : '';
                    $dzy_price = $other_prices->dzy_price ? "<span class='label label-warning'>dzy：" . $other_prices->dzy_price . "</span><br>" : '';
                    $douban_es_count = $other_prices->douban_es_count ? "<span class='label label-warning'>豆瓣在售二手：" . $other_prices->douban_es_count . "本</span><br>" : '';
                    $douban_es_low = $other_prices->douban_es_low ? "<span class='label label-warning'>豆瓣二手低价：" . $other_prices->douban_es_low . "</span><br>" : '';
                    $douban_es_high = $other_prices->douban_es_high ? "<span class='label label-warning'>豆瓣二手高价：" . $other_prices->douban_es_high . "</span><br>" : '';
                    $douban_es_want_count = $other_prices->douban_es_want_count ? "<span class='label label-warning'>豆瓣二手需求：" . $other_prices->douban_es_want_count . "人</span><br>" : '';
                    return $dd_new_price . $jd_new_price . $amz_new_price . $bc_new_price . $dzy_price . $douban_es_count . $douban_es_low . $douban_es_high . $douban_es_want_count;
                } else {
                    return '';
                }
            });
            $grid->status('状态')->editable('select', [
                0 => '不上架',
                1 => '在售',
                2 => '自动',
                3 => '已锁定',
                4 => '已卖',
                5 => '翻新中',
                8 => '有问题的'
            ]);
            $grid->stock('库存')->editable();
            $grid->column('运费')->display(function (){
                if(!$this->ship_rule){
                    return '空';
                }
                if($this->ship_rule_id == 1){
                    return '回流鱼默认';
                }
                $html = '';
                $html .= '<label class="label label-default">'.$this->ship_rule->name.'</label><br>';
                if($this->ship_rule->reject) $html .= '<p class="label label-danger">'.$this->ship_rule->reject.'</p><br>';
                if($this->ship_rule->content){
                    $content = json_decode($this->ship_rule->content,true);
                    foreach ($content as $item){
                        $html .= '<p class="label label-info">'.$item["areas"].'==='.$item["addition"].'元</p><br>';
                    }
                }
                return $html;
            });
            $grid->column('供应商')->display(function () {
                $shop = $this->book_shop;
                return '<span class="label label-default">供应商：' . $shop->shop_name . '</span><br>'
                    . '<span class="label label-default">快递：' . $shop->express . '</span><br>'
                    . '<span class="label label-default">单件运费：' . $shop->ship_price . '元</span><br>'
                    . '<span class="label label-default">发货地址：' . $shop->addr . '</span><br>'
                    . '<span class="label label-default">联系人：' . $shop->username . '</span><br>'
                    . '<span class="label label-default">电话：' . $shop->phone . '</span><br>';
            });
            $grid->column("分组")->display(function () {
                $book = $this->book;
                $r = '';
                if ($book->group1) {
                    $r = $r . '<span class="label label-default">' . $book->group1 . '</span><br>';
                }
                if ($book->group2) {
                    $r = $r . '<span class="label label-default">' . $book->group2 . '</span><br>';
                }
                if ($book->group3) {
                    $r = $r . '<span class="label label-default">' . $book->group3 . '</span><br>';
                }
                return $r;
            });
            $grid->column('出现在卖单ID')->display(function () {
                $orders = $this->orders()->where([
                    ['type', '=', Order::ORDER_TYPE_SALE],
                    ['sale_status', '<>', Order::SALE_STATUS_CANCEL],
                    ['closed', '=', false],
                    ['paid_at', '<>', null]
                ])->get()->pluck('id')->toArray();
                return join(',', $orders);
            });
            $grid->sale_at("上架时间")->sortable();

            // 不在页面显示 `新建` 按钮，因为我们不需要在后台新建用户
            //            $grid->disableCreateButton();

            $grid->actions(function ($actions) {
                $actions->disableView();
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

            $grid->filter(function ($filter) {

                // 去掉默认的id过滤器
                $filter->disableIdFilter();

                // 在这里添加字段过滤器
                $filter->equal('user_id', '用户ID');
                $filter->equal('isbn', 'ISBN');
                $filter->equal('book_id', 'Book ID');
                $filter->where(function ($query) {
                    $query->whereHas('book', function ($query) {
                        $query->where('name', 'like', "%{$this->input}%")->orWhere('author', 'like', "%{$this->input}%");
                    });
                }, '书名/作者');
                $filter->equal('hly_code', '回流鱼编码');
                $filter->in('status', 'SKU状态')->multipleSelect([
                    BookSku::STATUS_NOT_FOR_SALE => '未上架',
                    BookSku::STATUS_RETREADING => '翻新中',
                    BookSku::STATUS_FOR_SALE => '售卖中',
                    BookSku::STATUS_READY_TO_GO => '机器自动上架',
                    BookSku::STATUS_SOLD => '已卖',
                    BookSku::STATUS_ISSUE => '有问题，不能上架'
                ]);
                $filter->in('level', '品相级别')->multipleSelect([
                    BookSku::LEVEL_1 => '未确定分级',
                    BookSku::LEVEL_60 => '二手中等',
                    BookSku::LEVEL_80 => '二手上好',
                    BookSku::LEVEL_100 => '全新',
                ]);
                $filter->like('groups', '分组');
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
        $arr = array();
        $ship_rules = array();
        $shops = BookShop::select('id', 'shop_name')->get();
        foreach ($shops as $shop) {
            $arr[$shop->id] = $shop->shop_name;
        }
        $shipRule = ShipRule::where('id','<>',1)->select('id','name')->get();
        foreach ($shipRule as $item){
            $ship_rules[$item->id] = $item->name;
        }
        return Admin::form(BookSku::class, function (Form $form) use ($arr,$ship_rules) {

            $form->display('id', 'ID');
            $form->text('book_id', '书籍ID');
            $form->text('book_version_id', '版本号');
            $form->hidden('level', '品相')->default(100);
            $form->hidden('ifnew', '新书')->default(1);
            $form->select('status', '状态')->options([
                0 => '不上架',
                1 => '在售',
                4 => '下架'
            ]);
            $form->number('stock', '库存')->default(1);
            $form->select('shop_id', '供应商')->options($arr);
            $form->currency('ship_price', '运费')->symbol('￥')->help('基础运费以此为准');
            $form->select('ship_rule_id', '运费规则')->options($ship_rules);
            $form->currency('price', '售价')->symbol('￥');
            //保存前回调
            $form->saving(function (Form $form) {
                $book_id = $form->book_id ? $form->book_id : $form->model()->book_id;
                $book = Book::find($book_id);
                Log::info('book_id: '.$book_id);
                if (!$book) {
                    $error = new MessageBag([
                        'title'   => '错误',
                        'message' => '没有找到书籍ID为'.$book_id.'的书籍'
                    ]);
                    return back()->with(compact('error'));
                }
                $action = $form->model()->id ? 'update' : 'create';
                if($action == "create"){
                    $book_sku = BookSku::where('book_id',$book_id)
                        ->where('ifnew',1)->where('shop_id',$form->shop_id)
                        ->where('book_version_id',$form->book_version_id)->first();
                    if($book_sku){
                        $error = new MessageBag([
                            'title'   => '错误',
                            'message' => '该供应商已有此书在售'
                        ]);
                        return back()->with(compact('error'));
                    }
                    $form->model()->isbn = $book->isbn;
                    $form->model()->original_price = $book->original_price;
                    $form->model()->hly_code = 'new'.time();
                }
            });
            $form->saved(function (Form $form){
                $book_id = $form->book_id ? $form->book_id : $form->model()->book_id;
                Log::info('book_id:'.$book_id);
                $book = Book::find($book_id);
                $new_sku = BookSku::where('book_id',$book_id)->where('ifnew',1)->where('status',1)->get();
                $es_count = BookSku::where('book_id',$book_id)->where('ifnew','<>',1)->whereIn('status',[1,2])->count();
                $count = $new_sku->sum->stock + $es_count;
                Log::info('count:'.$count);
                Log::info('book:'.json_encode($book->toArray()));
                $book->sale_item_count = $count;
                $book->save();
                Log::info('save done');
            });
        });
    }
}
