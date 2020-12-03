<?php

namespace App\Admin\Controllers;

use App\Admin\Extensions\Tools\OnSaleSku;
use App\Admin\Extensions\Tools\SkuChangeGroup;
use App\Book;
use App\BookSku;

use App\BookVersion;
use App\CartItem;
use App\Events\SkuForSale;
use App\Jobs\NotifyUserBookOnSaleJob;
use App\Order;
use App\OrderItem;
use App\ReminderItem;
use App\Tag;
use Carbon\Carbon;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\MessageBag;

class OrderItemsController extends Controller
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

            $content->header('订单审核专用');
            $content->description("1、使用订单编号搜索 2、审核订单里的每一本书 3、去订单页打款");

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

            $content->header('编辑Order Item');
            $content->description('一切都有可能');

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

            $content->header('新增Order Item');
            $content->description('订单条目少了才用在这里增加');

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
        return Admin::grid(OrderItem::class, function (Grid $grid) {
            $grid->model()->whereHas('order', function($q){
                $q->where('recover_status', Order::RECOVER_STATUS_PAYING)->where('closed', 0);
            })->orderBy('id', 'desc');

            $grid->id('ID')->sortable();
            $grid->column('书')->display(function (){
                return "<img src='".$this->book->cover_replace."' style='width:80px;border:1px solid #222;'/><br>".
                    '<span style="font-size: 15px;font-weight: 700;">'.$this->book->name.'</span><br>'.
                    '<span style="font-size: 12px;">'.$this->book->subtitle.'</span><br>'.
                    '<span style="color:#ccc;">'.join('<br>',explode(' ',$this->book->author)).'</span><br>'.
                    '<span style="color:#ccc;">'.$this->book->press.'</span><br>'.
                    '<span style="color: green;">豆瓣评分：'.$this->book->rating_num.'/'.$this->book->num_raters.'</span><br>'.
                    '<span style="color: dodgerblue;">'.$this->book->isbn.'</span><br>'.
                    '<span style="color:#aaa;">'.$this->book->publish_year.'</span><br>'.
                    '<span style="color:#aaa;">subjectid：'.$this->book->subjectid.'</span><br>'.
                    '<span style="color:#888888;">分类：<br>'.join('<br>',explode(',',$this->book->category)).'</span><br>'.
                    '<span style="color: firebrick;">定价：'.$this->book->price.'</span>';
            })->style('width:150px;');
            $grid->column('用户')->display(function(){
                if ($this->order->user) {
                    return '<img src="'.$this->order->user->avatar.'" style="width:32px;"><br>'.
                        '<span class="badge badge-success">' . $this->order->user->nickname . '</span>' .
                        '<p>[' . $this->order->user_id . ']</p>';
                }else {
                    return '';
                }
            });
            $grid->column('book_version_id', '图书版本')->radio(function($row){
                $this->value = $row['book_version_id']?$row['book_version_id']:'0';
                return BookVersion::where('book_id', $row->book_id)->get()->pluck('price', 'id')->toArray();
            });
            $grid->level('品相')->editable('select', [
                BookSku::LEVEL_100 => '新书',
                BookSku::LEVEL_80 => '上好',
                BookSku::LEVEL_60 => '中等',
                BookSku::LEVEL_NOT_FOR_SURE => '未确定'
            ]);
            $all = [
                '轻微污渍'=>'轻微污渍',
                '轻微泛黄'=>'轻微泛黄',
                '轻微霉点'=>'轻微霉点',
                '轻微笔记'=>'轻微笔记',
                '轻微划线'=>'轻微划线',
                '轻微磨损'=>'轻微磨损',
                '轻微破损'=>'轻微破损',
                '轻微折痕'=>'轻微折痕',
                '轻微变形'=>'轻微变形',
                '轻微褪色'=>'轻微褪色',
                '轻微水渍'=>'轻微水渍',
                '轻微脱胶'=>'轻微脱胶',
                '贴有标签'=>'贴有标签',
                '封套丢失'=>'封套丢失',
                '盖有印章'=>'盖有印章',
                '污渍较重'=>'污渍较重',
                '泛黄较重'=>'泛黄较重',
                '霉点较重'=>'霉点较重',
                '笔记较重'=>'笔记较重',
                '划线较重'=>'划线较重',
                '磨损较重'=>'磨损较重',
                '破损较重'=>'破损较重',
                '折痕较重'=>'折痕较重',
                '变形较重'=>'变形较重',
                '褪色较重'=>'褪色较重',
                '水渍较重'=>'水渍较重',
                '一折收'=>'一折收',
                '+0.5折'=>'+0.5折',
            ];
            $grid->column('title_array', '品相说明')->checkbox(function($row) use ($all){
                $this->value = $row['title_array'];
                return $all;
            });
            $grid->review_result('审核')->editable('select', [
                OrderItem::REVIEW_OK => OrderItem::$reviewMap[OrderItem::REVIEW_OK],
                OrderItem::REVIEW_REJECT => OrderItem::$reviewMap[OrderItem::REVIEW_REJECT],
            ]);
            $grid->review('拒收说明')->editable();
            $grid->price('收购价￥')->display(function ($price){
                if (is_numeric($this->book->price)) {
                    $discount = $price * 10 / $this->book->price;
                }else{
                    $discount = 1;
                }
                return $price."<br><span class='label label-danger'>".number_format($discount, 1)." 折</span>";
            });
            $grid->hly_code('回流鱼码')->editable();
            $tags = Tag::select('name')->pluck('name')->toArray();
            $arr = [];
            for ($v=0;$v<count($tags);$v++){
                $arr[$tags[$v]] = $tags[$v];
            }
            $grid->column('group_array', '分类(最多选3个)')->checkbox(function($row) use ($arr){
                $this->value = $row['group_array'];
                return $arr;
            });
            $grid->column('其他平台售价')->display(function() {
                $prices = $this->book->prices;
                if (count($prices)>0) {
                    $other_prices = $prices->first();
                    $dd_new_price = $other_prices->dd_new_price ? "<span class='label label-warning'>当当网：" . $other_prices->dd_new_price . "</span><br>" : '';
                    $jd_new_price = $other_prices->jd_new_price ? "<span class='label label-warning'>京东商城：" . $other_prices->jd_new_price . "</span><br>" : '';
                    $amz_new_price = $other_prices->amz_new_price ? "<span class='label label-warning'>亚马逊：" . $other_prices->amz_new_price . "</span><br>" : '';
                    $bc_new_price = $other_prices->bc_new_price ? "<span class='label label-warning'>中国图书网：" . $other_prices->bc_new_price . "</span><br>" : '';
                    $douban_es_count = $other_prices->douban_es_count ? "<span class='label label-warning'>豆瓣在售二手：" . $other_prices->douban_es_count . "本</span><br>" : '';
                    $douban_es_low = $other_prices->douban_es_low ? "<span class='label label-warning'>豆瓣二手低价：" . $other_prices->douban_es_low . "</span><br>" : '';
                    $douban_es_high = $other_prices->douban_es_high ? "<span class='label label-warning'>豆瓣二手高价：" . $other_prices->douban_es_high . "</span><br>" : '';
                    $douban_es_want_count = $other_prices->douban_es_want_count ? "<span class='label label-warning'>豆瓣二手需求：" . $other_prices->douban_es_want_count . "人</span><br>" : '';
                    return $dd_new_price . $jd_new_price . $amz_new_price . $bc_new_price . $douban_es_count . $douban_es_low . $douban_es_high . $douban_es_want_count;
                }else{
                    return '';
                }
            });
            $grid->column('过往售卖均值')->display(function() {
                $new_skus = BookSku::where('book_id', $this->book_id)->where('level', BookSku::LEVEL_100)->where('status', BookSku::STATUS_SOLD)
                    ->orderByDesc('sold_at')->take(10)->get();
                $top_skus = BookSku::where('book_id', $this->book_id)->where('level', BookSku::LEVEL_80)->where('status', BookSku::STATUS_SOLD)
                    ->orderByDesc('sold_at')->take(10)->get();
                $middle_skus = BookSku::where('book_id', $this->book_id)->where('level', BookSku::LEVEL_60)->where('status', BookSku::STATUS_SOLD)
                    ->orderByDesc('sold_at')->take(10)->get();
                $qx='';
                $sh='';
                $zd='';
                if ($new_skus) {
                    $qx = "新：<span class='label label-primary'>".number_format($new_skus->avg->price, 2)."</span><br>";
                }
                if ($top_skus) {
                    $sh = "上：<span class='label label-primary'>".number_format($top_skus->avg->price, 2)."</span><br>";
                }
                if ($middle_skus) {
                    $zd = "中：<span class='label label-primary'>".number_format($middle_skus->avg->price, 2)."</span><br>";
                }
                return $qx.$sh.$zd;
            });
            $grid->order_id('订单ID')->display(function($order_id){
                return "<a href='/admin/orders?&id=".$order_id."' target='_blank'>去订单页打款</a>";
            });

            // 不在页面显示 `新建` 按钮，因为我们不需要在后台新建用户
//            $grid->disableCreateButton();

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
                $filter->where(function ($query) {
                    $query->whereHas('order', function ($query) {
                        $query->where('express_no', 'like', "%{$this->input}%");
                    });
                }, '顺丰单号');
                $filter->where(function ($query) {
                    $query->whereHas('order', function ($query) {
                        $query->where('user_id', "{$this->input}");
                    });
                }, '用户ID');
                $filter->equal('order_id', '订单ID');
                $filter->equal('book.isbn', 'ISBN');
                $filter->equal('book_id', 'Book ID');
                $filter->equal('hly_code', '回流鱼编码');
                $filter->where(function ($query) {
                    $query->whereHas('book', function ($query) {
                        $query->where('name', 'like', "%{$this->input}%")->orWhere('author', 'like', "%{$this->input}%");
                    });
                }, '书名/作者');
                $filter->in('level', '品相级别')->multipleSelect([
                    BookSku::LEVEL_1 => '未确定分级',
                    BookSku::LEVEL_60 => '二手中等[有划痕，有污渍，有发霉]',
                    BookSku::LEVEL_80 => '二手上好[轻污渍，轻划痕，轻发霉]',
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
        return Admin::form(OrderItem::class, function (Form $form) {

            $form->tab('SKU的运营信息', function ($form){
                $form->hidden('id');
                $form->hidden('book_id');
                $form->text('isbn', 'ISBN')->rules('required|min:10|max:13');
                $form->display('book.name', '书名');
                $form->display('book.cover_replace', '封面')->with(function ($cover) {
                    return '<img src="'.$cover.'" style="width:80px;"/>';
                });
                $form->display('book.category', '标签');
                $form->display('book.rating_num', '豆瓣评分');
                $form->select('level', '品相级别')->options([
                    BookSku::LEVEL_60 => '中等',
                    BookSku::LEVEL_80 => '上好',
                    BookSku::LEVEL_100 => '新书',
                ]);
                $form->text('title_array', '品相说明');
                $form->text('group_array', '分组');
                $form->text('review_result', '审核');
                $form->text('review', '拒收说明');
                $form->text('hly_code', '回流鱼码');
                $form->display('book.price', '原价');
                $form->currency('original_price', '原书定价换算成人民币价格')->symbol('￥')->rules('required|numeric');
                $form->currency('recover_price', '回收价')->symbol('￥')->rules('required|numeric');
                $form->currency('price', '价格')->symbol('￥');
                $form->multipleSelect('category', '重新设置分组')->options(function (){
                    $script = <<<JS
                $('.category').select2().val({$this->category->pluck('id')}).trigger("change");
JS;
                    Admin::script($script);
                    return Tag::all()->pluck('name', 'id');
                })->help('最多选择三个分类，多余的会被丢弃');
                $form->select('book_version_id', '图书版本')->options(function($version_id){
                    $version = BookVersion::find($version_id);
                    if ($version) {
                        return [$version_id => $version->price . ' - ' . $version->title];
                    }else{
                        return [0 => '默认版本'];
                    }
                })->ajax('/admin/book/versions')->help('这里只能使用isbn搜索');
            });

            $form->saving(function (Form $form) {
                if (is_null($form->model()->book_id)) {
                    $book = Book::where('isbn', $form->isbn)->first();
                    if (!$book) {
                        $error = new MessageBag([
                            'title'   => '保存出错',
                            'message' => '没有ISBN为'.$form->isbn.'的书',
                        ]);

                        return back()->with(compact('error'));
                    }else{
                        $form->book_id = $book->id;
                    }
                }

                if (is_null($form->category) && !is_null($form->groups)) {
                    $tags = Tag::select('id')->whereIn('name', explode(',', $form->groups))->get();
                    $form->category = $tags->toArray();
                }
            });
        });
    }
}
