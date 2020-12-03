<?php

namespace App\Admin\Controllers;

use App\Admin\Extensions\Tools\CopySku;
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

class BookSkusController extends Controller
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

            $content->header('SKU');
            $content->description('这里面放着所有[已经收的][要卖的][已经卖的]二手书');

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

            $content->header('编辑SKU');
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

            $content->header('新增SKU');
            $content->description('一般是为了上架才来这，不然请离开');

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
            $grid->model()->with('store_shelf')->orderBy('id', 'desc');

            $grid->id('ID')->sortable();
            $grid->column('store_shelf_id', '仓库')->display(function () {
                if ($this->store_shelf) {
                    return $this->store_shelf->code;
                } else {
                    return '无';
                }
            });
            $grid->column('书')->display(function () {
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
            });
            $grid->column('书版本')->display(function () {
                $version = $this->book_version;
                if ($version) {
                    return '<p>' . $version->title . '</p>' .
                        '<p style="color: orange">￥' . $version->price . '</p>';
                } else {
                    return '<span class="label label-primary">默认</span>';
                }
            });
            $grid->column('用户')->display(function () {
                if ($this->user) {
                    return '<img src="' . $this->user->avatar . '" style="width:32px;"><br>' .
                        '<span class="badge badge-success">' . $this->user->nickname . '</span>' .
                        '<p>[' . $this->user_id . ']</p>';
                } else {
                    return '';
                }
            });
            $grid->hly_code('回流鱼码')->editable();
            $grid->level('品相')->editable('select', [
                BookSku::LEVEL_100 => '新书',
                BookSku::LEVEL_80 => '上好',
                BookSku::LEVEL_60 => '中等',
                BookSku::LEVEL_NOT_FOR_SURE => '未确定'
            ]);
            $grid->title('品相说明')->editable();
            $grid->column('想要')->display(function () {
                return count($this->book->reminders);
            });
            $grid->column('已放入购物袋')->display(function () {
                $count = CartItem::where('book_id', $this->book_id)->count();
                $count2 = CartItem::where('book_sku_id', $this->id)->count();
                return "<span class='label label-primary'>书：" . $count . "</span><br>" .
                    "<span class='label label-primary'>SKU：" . $count2 . "</span><br>";
            });
            $grid->column('book.price', '原价');
            $grid->original_price('换算成人民币')->sortable()->editable();
            $grid->recover_price('回收价￥')->display(function ($recover_price) {
                if (is_null($this->original_price)) {
                    return 0;
                }
                return $recover_price .
                    '<br><span class="label label-success">' . number_format(floatval($recover_price) * 10 / floatval($this->original_price), 1) . '折</span>';
            });
            $grid->price('售价')->editable();
            $grid->column('过往售卖均值')->display(function () {
                $new_skus = BookSku::where('book_id', $this->book_id)->where('book_version_id', $this->book_version_id)->where('level', BookSku::LEVEL_100)->where('status', BookSku::STATUS_SOLD)
                    ->orderByDesc('sold_at')->take(10)->get();
                $top_skus = BookSku::where('book_id', $this->book_id)->where('book_version_id', $this->book_version_id)->where('level', BookSku::LEVEL_80)->where('status', BookSku::STATUS_SOLD)
                    ->orderByDesc('sold_at')->take(10)->get();
                $middle_skus = BookSku::where('book_id', $this->book_id)->where('book_version_id', $this->book_version_id)->where('level', BookSku::LEVEL_60)->where('status', BookSku::STATUS_SOLD)
                    ->orderByDesc('sold_at')->take(10)->get();
                $qx = '';
                $sh = '';
                $zd = '';
                if ($new_skus) {
                    $qx = "新：<span class='label label-primary'>" . number_format($new_skus->avg->price, 2) . "</span><br>";
                }
                if ($top_skus) {
                    $sh = "上：<span class='label label-primary'>" . number_format($top_skus->avg->price, 2) . "</span><br>";
                }
                if ($middle_skus) {
                    $zd = "中：<span class='label label-primary'>" . number_format($middle_skus->avg->price, 2) . "</span><br>";
                }
                return $qx . $sh . $zd;
            });
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
            $grid->status('sku状态')->editable('select', [
                BookSku::STATUS_NOT_FOR_SALE => '预存',
                BookSku::STATUS_RETREADING => '翻新中',
                BookSku::STATUS_READY_TO_GO => '自动',
                BookSku::STATUS_FOR_SALE => '在售',
                BookSku::STATUS_SOLD => '已卖',
                BookSku::STATUS_ISSUE => '有问题，不能上架',
            ]);
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
            $grid->mark("备注")->editable();
            $grid->sale_at("上架时间")->sortable();
            $grid->sold_at("售出时间")->sortable();

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
                    $batch->add('改为暂不上架', new OnSaleSku(BookSku::STATUS_NOT_FOR_SALE));
                    $batch->add('改为自动上架', new OnSaleSku(BookSku::STATUS_READY_TO_GO));
                    $batch->add('改为翻新中', new OnSaleSku(BookSku::STATUS_RETREADING));
                    $batch->add('改为上架', new OnSaleSku(BookSku::STATUS_FOR_SALE));
                    $batch->add('改为已售', new OnSaleSku(BookSku::STATUS_SOLD));
                    $batch->add('拷贝Sku', new CopySku());
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
        return Admin::form(BookSku::class, function (Form $form) {

            $form->tab('SKU的运营信息', function ($form) {
                $form->hidden('id');
                $form->hidden('book_id');
                $form->text('isbn', 'ISBN')->rules('required|min:10|max:13');
                $form->display('book.name', '书名');
                $form->display('book.cover_replace', '封面')->with(function ($cover) {
                    return '<img src="' . $cover . '" style="width:80px;"/>';
                });
                $form->display('book.category', '标签');
                $form->display('book.rating_num', '豆瓣评分');
                $form->select('level', '品相级别')->options([
                    BookSku::LEVEL_60 => '中等',
                    BookSku::LEVEL_80 => '上好',
                    BookSku::LEVEL_100 => '新书',
                ]);
                $form->text('title', '品相说明');
                $form->display('book.price', '原价');
                $form->currency('original_price', '原书定价换算成人民币价格')->symbol('￥')->rules('required|numeric');
                $form->currency('recover_price', '回收价')->symbol('￥')->rules('required|numeric');
                $form->display('id', '回收价/最近一次售出情况')->with(function ($id) {
                    if ($id) {
                        $orderItem = OrderItem::whereHas('order', function ($q) {
                            $q->where('type', Order::ORDER_TYPE_SALE);
                        })->where('book_id', $this->book_id)->with('order')->orderBy('created_at', 'desc')->first();
                        $sku = DB::select('select recover_price,original_price from book_skus where id=?', [$id])[0];
                        if (floatval($sku->original_price) > 0) {
                            if ($orderItem) {
                                return "<span class='label label-success'>" .
                                    $orderItem->remind_count . '人想要&nbsp;&nbsp;&nbsp;' . $orderItem->cart_item_count . '人放入了购物车' .
                                    '&nbsp;&nbsp;&nbsp;库存为：' . $orderItem->sale_sku_count . '&nbsp;&nbsp;&nbsp;卖价为：' . $orderItem->price . '</span><br>' .
                                    '回收价￥' . $sku->recover_price . " -> " . number_format($sku->recover_price * 10 / $sku->original_price, 1) . '折';
                            } else {
                                return '回收价￥' . $sku->recover_price . " => " . number_format($sku->recover_price * 10 / $sku->original_price, 1) . '折';
                            }
                        } else {
                            return '无';
                        }
                    } else {
                        return '无';
                    }
                });
                $form->display('book_id', '其他平台价格')->with(function ($id) {
                    $book = Book::find($id);
                    if ($book) {
                        $prices = $book->prices;
                        if (count($prices) > 0) {
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
                        } else {
                            return '无';
                        }
                    } else {
                        return '';
                    }
                });
                $form->display('book_id', '定价参考')->with(function ($id) {
                    if ($id) {
                        $skus = BookSku::select('title', 'price', 'status', 'original_price', 'sale_at', 'sold_at')
                            ->where('book_id', $id)->orderBy('updated_at', 'desc')->take(20)->get();
                        $rc = ReminderItem::where('book_id', $id)->count();
                        $cc = CartItem::where('book_id', $id)->count();
                        $book = Book::find($id);
                        $price = floatval($book->price);
                        $new_skus = $skus->map(function ($sku) use ($price) {
                            if ($sku->status == BookSku::STATUS_SOLD) {
                                $interval = (strtotime($sku->sold_at) - strtotime($sku->sale_at)) / 3600;
                                return "<span class='label label-info'>已售:￥" . $sku->price .
                                    ' (' . number_format($sku->price * 10 / $sku->original_price, 1) . '折) [' . $sku->title . '] &nbsp;&nbsp;&nbsp;上架时间：' .
                                    $sku->sale_at . ' &nbsp;&nbsp;&nbsp;卖出时间：' . $sku->sold_at . '&nbsp;&nbsp;&nbsp;耗时：' . ceil($interval) . '小时</span>';
                            } else if ($sku->status == BookSku::STATUS_NOT_FOR_SALE) {
                                return "<span class='label label-default'>未上架:￥" . $sku->price . '</span>';
                            } else if ($sku->status == BookSku::STATUS_READY_TO_GO) {
                                return "<span class='label label-success'>自动上架:￥" . $sku->price .
                                    ' (' . number_format($sku->price * 10 / $sku->original_price, 1) . '折) [' . $sku->title . '</span>';
                            } else if ($sku->status == BookSku::STATUS_FOR_SALE) {
                                $interval = (strtotime(now()) - strtotime($sku->sale_at)) / 3600;
                                return "<span class='label label-success'>正在售卖:￥" . $sku->price .
                                    ' (' . number_format($sku->price * 10 / $sku->original_price, 1) . '折) [' . $sku->title . '] &nbsp;&nbsp;&nbsp;上架时间：' .
                                    $sku->sale_at . '&nbsp;&nbsp;&nbsp;已上架：' . ceil($interval) . '小时</span>';
                            }
                        });
                        return $new_skus->implode('<br>') . "<br><span class='label label-danger'>" . ($rc + $cc) . "人想要</span>";
                    } else {
                        return '';
                    }
                });
                $form->display('book_id', '建议价')->with(function ($id) {
                    $price50 = number_format($this->recover_price / 0.5, 2);
                    $price60 = number_format($this->recover_price / 0.4, 2);
                    $price65 = number_format($this->recover_price / 0.35, 2);
                    $price70 = number_format($this->recover_price / 0.3, 2);
                    $price75 = number_format($this->recover_price / 0.25, 2);
                    $price80 = number_format($this->recover_price / 0.2, 2);

                    $discount50 = 0;
                    $discount60 = 0;
                    $discount65 = 0;
                    $discount70 = 0;
                    $discount75 = 0;
                    $discount80 = 0;

                    if ($this->original_price > 0) {
                        $discount50 = number_format($price50 * 10 / $this->original_price, 1);
                        $discount60 = number_format($price60 * 10 / $this->original_price, 1);
                        $discount65 = number_format($price65 * 10 / $this->original_price, 1);
                        $discount70 = number_format($price70 * 10 / $this->original_price, 1);
                        $discount75 = number_format($price75 * 10 / $this->original_price, 1);
                        $discount80 = number_format($price80 * 10 / $this->original_price, 1);
                    }

                    return '<span class="label label-danger">毛利50%：' . $price50 . ' - ' . $discount50 . '折</span><br>' .
                        '<span class="label label-primary">毛利60%：' . $price60 . ' - ' . $discount60 . '折</span><br>' .
                        '<span class="label label-danger">毛利65%：' . $price65 . ' - ' . $discount65 . '折</span><br>' .
                        '<span class="label label-danger">毛利70%：' . $price70 . ' - ' . $discount70 . '折</span><br>' .
                        '<span class="label label-danger">毛利75%：' . $price75 . ' - ' . $discount75 . '折</span><br>' .
                        '<span class="label label-danger">毛利80%：' . $price80 . ' - ' . $discount80 . '折</span>';
                });
                $form->currency('price', 'SKU售价')->symbol('￥');
                $form->select('status', '状态')->options([
                    BookSku::STATUS_NOT_FOR_SALE => '预存（不上架）',
                    BookSku::STATUS_RETREADING => '翻新中（不上架）',
                    BookSku::STATUS_READY_TO_GO => '机器自动上架',
                    BookSku::STATUS_FOR_SALE => '上架',
                    BookSku::STATUS_SOLD => '已卖',
                    BookSku::STATUS_ISSUE => '有问题，不能上架'
                ])->default(BookSku::STATUS_READY_TO_GO);
                $form->text('hly_code', '回流鱼编码');
                $form->multipleSelect('category', '重新设置分组')->options(function () {
                    $script = <<<JS
                $('.category').select2().val({$this->category->pluck('id')}).trigger("change");
JS;
                    Admin::script($script);
                    return Tag::all()->pluck('name', 'id');
                })->help('最多选择三个分类，多余的会被丢弃');
                $form->select('book_version_id', '图书版本')->options(function ($version_id) {
                    $version = BookVersion::find($version_id);
                    if ($version) {
                        return [$version_id => $version->price . ' - ' . $version->title];
                    } else {
                        return [0 => '默认版本'];
                    }
                })->ajax('/admin/book/versions')->help('这里只能使用isbn搜索');
                $form->text('mark', '备注');
                $form->display('store_shelf_id', '仓库ID');
            });

            $form->saving(function (Form $form) {
                if (strlen($form->hly_code) != 13 && strlen($form->model()->hly_code) != 13) {
                    $error = new MessageBag([
                        'title'   => '保存出错',
                        'message' => '回流鱼码长度不对' . strlen($form->hly_code),
                    ]);

                    return back()->with(compact('error'));
                }
                if (is_null($form->book_id) && is_null($form->model()->book_id)) {
                    $book = Book::where('isbn', $form->isbn)->first();
                    if (!$book) {
                        $error = new MessageBag([
                            'title'   => '保存出错',
                            'message' => '没有ISBN为' . $form->isbn . '的书',
                        ]);

                        return back()->with(compact('error'));
                    } else {
                        $form->book_id = $book->id;
                    }
                }

                if (
                    $form->model() && $form->model()->status == BookSku::STATUS_READY_TO_GO
                    && $form->status == BookSku::STATUS_FOR_SALE
                ) {
                    $error = new MessageBag([
                        'title'   => '自动上架中',
                        'message' => '该SKU不能手动上架',
                    ]);

                    return back()->with(compact('error'));
                }

                if ($form->model() && ($form->model()->status == BookSku::STATUS_SOLD || $form->model()->status == BookSku::STATUS_ISSUE)) {
                    $error = new MessageBag([
                        'title'   => 'SKU 已售或有问题',
                        'message' => '若要上架请重新创建SKU',
                    ]);
                    return back()->with(compact('error'));
                }

                if (is_null($form->model())) {
                    $sku = BookSku::where('hly_code', $form->hly_code)->first();
                    if ($sku) {
                        $error = new MessageBag([
                            'title' => '上架错误',
                            'message' => '回流鱼码重复 ' . $form->hly_code . ' 已使用过',
                        ]);
                        return back()->with(compact('error'));
                    }
                    if ($form->status == BookSku::STATUS_READY_TO_GO && (is_null($form->title) || is_null($form->level) || is_null($form->price) || is_null($form->hly_code))) {
                        $error = new MessageBag([
                            'title' => '上架错误',
                            'message' => '信息不完整不能上架',
                        ]);
                        return back()->with(compact('error'));
                    }
                } else {
                    if ($form->model()->status == BookSku::STATUS_READY_TO_GO && (is_null($form->model()->title) ||
                        is_null($form->model()->level) || is_null($form->model()->price) || is_null($form->model()->hly_code))) {
                        $error = new MessageBag([
                            'title' => '上架错误',
                            'message' => '信息不完整不能上架',
                        ]);
                        return back()->with(compact('error'));
                    }
                }
            });
        });
    }

    public function onSale(Request $request)
    {
        foreach (BookSku::find($request->get('ids')) as $sku) {
            $action = $request->get('action');
            // 上架的商品要审核
            if (
                $action == BookSku::STATUS_READY_TO_GO && $sku->status == BookSku::STATUS_NOT_FOR_SALE &&
                $sku->title != null && is_numeric($sku->price) && is_numeric($sku->original_price) &&
                $sku->hly_code != null
            ) {
                $on_sale_sku = BookSku::where('book_id', $sku->book_id)->where('status', BookSku::STATUS_FOR_SALE)
                    ->where('level', $sku->level)->first();
                if ($on_sale_sku) {
                    $sku->status = BookSku::STATUS_READY_TO_GO;
                    $sku->save();
                } else {
                    $sku->status = BookSku::STATUS_FOR_SALE;
                    $sku->sale_at = now();
                    $sku->save();
                }
                // 给想要的用户发通知
                // 书上架，通知500人
                $rs = DB::select("select * from reminder_items where book_id=? order by open_times, updated_at desc limit 500", [$sku->book_id]);
                for ($i = 0; $i < count($rs); $i++) {
                    $r = $rs[$i];
                    NotifyUserBookOnSaleJob::dispatch($r->user_id, $r->book_id)->delay(now()->addSecond(30 * $i));
                }
            } else {
                $sku->status = $request->get('action');
                $sku->save();
            }
        }
    }

    public function copySku(Request $request)
    {
        foreach (BookSku::find($request->get('ids')) as $sku) {
            $new_sku = $sku->replicate();
            $new_sku->hly_code = '请重新编辑';
            $new_sku->status = BookSku::STATUS_READY_TO_GO;
            $new_sku->push();
        }
    }

    public function changeGroup(Request $request)
    {
        foreach (BookSku::find($request->get('ids')) as $sku) {
            $groups = explode(',', $sku->groups);
            $action = $request->get('action');
            // 先删除新上架
            foreach ($groups as $key => $value) {
                if (in_array($value, array('新上架'))) {
                    unset($groups[$key]);
                }
            }
            if ($action == 1) {
                Log::info('添加新上架');
                array_push($groups, '新上架');
            }
            $sku->groups = join(',', $groups);
            $sku->save();
        }
    }
}
