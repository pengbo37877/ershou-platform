<?php

namespace App\Admin\Controllers;

use App\Book;
use App\Events\BookRecoverPriceRisen;
use App\Events\SaleItemSaved;
use App\SaleItem;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;
use Milon\Barcode\DNS1D;
use Milon\Barcode\DNS2D;

class SaleItemsController extends Controller
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
            // 页面标题
            $content->header('用户卖书袋中的书');
            $content->description('从这里可以发现用户想卖的书');
            $content->body($this->grid());
        });
    }

    protected function grid()
    {
        // 根据回调函数，在页面上
        return Admin::grid(SaleItem::class, function (Grid $grid) {
            $grid->model()->orderByDesc('created_at');
            // 创建一个列名为 ID 的列，内容是用户的 id 字段，并且可以在前端页面点击排序
            $grid->id('ID')->sortable();
            $grid->column('用户')->display(function () {
                $sex = '';
                if ($this->user->sex == 1) {
                    $sex = '男';
                } else if ($this->user->sex == 2) {
                    $sex = '女';
                } else {
                    $sex = '性别不知';
                }
                return '<div style="text-align: center">' .
                    '<img style="width:50px;border-radius:25px;" src="' . $this->user->avatar . '">' .
                    '<p>' . $this->user->nickname . '</p>' .
                    '<p>[ ' . $this->user->id . ' ]</p>' .
                    '<p>' . $sex . '</p>' .
                    '<p>' . $this->user->province . '-' . $this->user->city . '</p></div>';
            })->style('width:120px;');
            $grid->column('封面')->display(function () {
                $r = '';
                if ($this->book->admin_user_id > 0) {
                    $r = $r . "<span class='label label-default'>管理员决策(" . $this->book->admin_user_id . ")</span><br>";
                } else {
                    $r = $r . "<span class='label label-default'>算法决策</span><br>";
                }
                if ($this->can_recover == 1) {
                    $r = $r . "<span class='label label-success'>折扣：" . $this->book->discount / 10 . '折</span><br>' .
                        "<span class='label label-success'>库存：" . $this->book->sale_sku_count . " / " . $this->book->all_sku_count . "</span><br>" .
                        "<span class='label label-success'>show：" . $this->show . "</span>";
                } else {
                    $r = $r . "<span class='label label-primary'>折扣：" . $this->book->discount / 10 . '折</span><br>' .
                        "<span class='label label-primary'>库存：" . $this->book->sale_sku_count . " / " . $this->book->all_sku_count . "</span><br>" .
                        "<span class='label label-primary'>show：" . $this->show . "</span>";
                }
                return "<img src='" . $this->book->cover_replace . "' style='width:80px;'/>" .
                    '<p style="color: #ff7701;font-size: 16px;"><span style="color: #aaa;font-size: 12px;">价格:</span>' . $this->book->price . '</p>' .
                    "<p style='color:red'>" . $this->book->reminders->count() . '想要</p>' . $r;
            });
            $grid->column('想卖的书')->display(function () {
                $isbn = strlen($this->isbn) == 13 ? $this->isbn : '9789624516739';
                if (strpos($this->isbn, 'x') !== false) {
                    $isbn = '9789624516739';
                }
                if (strpos($this->isbn, 'X') !== false) {
                    $isbn = '9789624516739';
                }
                $a = "<a href='/admin/books?isbn=" . $this->book->isbn . "' target='_blank'>" . $this->book->name . "</a>";
                return $a .
                    '<p>' . $this->book->author . '</p>' .
                    '<p>' . $this->book->translator . '</p>' .
                    '<img src="data:image/png;base64,' . self::$DNS1D->getBarcodePNG($isbn, "EAN13") . '" alt="barcode"/><br><br>' .
                    '<p>' . $this->book->isbn . ' / ' . $this->book->id . '</p>' .
                    '<p>' . $this->book->press . '</p>' .
                    '<p>' . $this->book->publish_year . '</p>' .
                    '<p style="color: green">豆瓣评分：' . $this->book->rating_num . ' / ' . $this->book->num_raters . '</p>' .
                    '<p style="color: #888888">' . $this->book->category . '</p>';
            })->style('width:180px;');
            $grid->can_recover('收取(item)')->editable('select', [1 => '收取', 0 => '不收']);
            $grid->column('收取(book)')->display(function () {
                $book = $this->book;
                $recover = $book->can_recover;
                if ($recover == 1) {
                    return "<span class='label label-success'>收（" . $book->admin_user_id . "）</span>";
                } else {
                    return "<span class='label label-primary'>不收（" . $book->admin_user_id . "）</span>";
                }
            });
            $grid->column('book.price', '价格')->editable();
            $grid->column('book.volume_count', '册数')->editable();
            $grid->column('其他平台价格')->display(function () {
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
            $grid->column('扫码中')->display(function () {
                $sale_items = $this->user->sale_items;
                if (count($sale_items) == 0) {
                    return '0';
                }
                $total = 0;
                $recover_count = 0;
                foreach ($sale_items as $item) {
                    if ($item->can_recover) {
                        $total = $total + floatval($item->book->price) * $item->book->discount / 100;
                        $recover_count++;
                    }
                }
                return '<p>收' . $recover_count . '/共' . count($sale_items) . '本 总额￥' . number_format($total, 2) . '</p>';
            });

            $grid->column('绑定的SKU')->display(function () {
                $sku = $this->sku;
                if ($sku) {
                    return '<p>' . $this->sku->title . '</p><p>[' . $this->book_sku_id . ']</p>';
                }
                return '无';
            });
            $grid->type('书籍类型')->editable('select', [
                Book::TYPE_DEFAULT => Book::$typeMap[Book::TYPE_DEFAULT],
                Book::TYPE_OUT_OF_PRINT => Book::$typeMap[Book::TYPE_OUT_OF_PRINT],
                Book::TYPE_BAN => Book::$typeMap[Book::TYPE_BAN],
            ]);
            $grid->created_at('创建时间');
            // 不在页面显示 `新建` 按钮，因为我们不需要在后台新建用户
            $grid->disableCreateButton();
            // 禁用导出按钮
            $grid->disableExport();

            $grid->actions(function ($actions) {
                // 不在每一行后面展示按钮
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
            $grid->filter(function ($filter) {

                // 去掉默认的id过滤器
                $filter->disableIdFilter();
                $filter->equal('user_id', '用户ID');
                $filter->equal('isbn', 'ISBN');
                $filter->between('book.rating_num', '评分');
                $filter->where(function ($query) {
                    $query->whereHas('book', function ($query) {
                        $query->where('publish_year', 'like', "{$this->input}%");
                    });
                }, '出版年');
                $filter->where(function ($query) {
                    $query->whereHas('book', function ($query) {
                        $query->where('sale_sku_count', "{$this->input}");
                    });
                }, '在售库存');
                $filter->where(function ($query) {
                    $query->whereHas('book', function ($query) {
                        $query->where('can_recover', "{$this->input}");
                    });
                }, '书籍本身收取状态');
                $filter->equal('can_recover', '用户扫码收取状态');
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
        return Admin::form(SaleItem::class, function (Form $form) {

            $form->display('id', 'ID');
            $form->text('book.price', '价格');
            $form->text('book.can_recover', '收取(book)');
            $form->text('can_recover', '收取(item)');

            $form->display('created_at', 'Created At');
            $form->display('updated_at', 'Updated At');

            $form->saved(function (Form $form) {
                $saleItem = $form->model();
                $book = Book::find($saleItem->book_id);
                if ($saleItem->can_recover == 1 && $book) {
                    $book->can_recover = 1;
                    $book->admin_user_id = 89;
                    $book->save();
                }
                if ($saleItem->can_recover == 0 && $book) {
                    $book->can_recover = 0;
                    $book->admin_user_id = 88;
                    $book->save();
                }
            });
        });
    }
}
