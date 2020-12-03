<?php

namespace App\Admin\Controllers;

use App\Book;

use App\BookVersion;
use App\Events\BookRecoverPriceRisen;
use App\Jobs\UpdateBookFromDouban;
use App\SaleItem;
use Carbon\Carbon;
use EasyWeChat\OfficialAccount\Application;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;
use Illuminate\Http\Request;
use App\Admin\Extensions\Tools\RecoverBook;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Milon\Barcode\DNS1D;

class BooksController extends Controller
{
    use ModelForm;

    protected $app;

    private static $DNS1D;

    public function __construct(DNS1D $DNS1D, Application $app)
    {
        self::$DNS1D = $DNS1D;
        $this->app = $app;
    }

    /**
     * Index interface.
     *
     * @return Content
     */
    public function index()
    {
        return Admin::content(function (Content $content) {

            $content->header('书库');
            $content->description('回流鱼可以回收的书都在这里');

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
            $book = Book::select('name')->find($id);
            $content->header('《' . $book->name . '》');
            $content->description('谨慎修改');

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

            $content->header('新增一本书');
            $content->description('除了填写运营信息，还有基本信息需要填写');

            $content->body($this->form());
        });
    }

    public function recover(Request $request)
    {
        DB::transaction(function () use ($request) {
            foreach (Book::find($request->get('ids')) as $book) {
                $sale_items = SaleItem::where('book_id', $book->id)->get();
                if (intval($request->get('action')) == 1 && floatval($book->price) > 0) {
                    $book->can_recover = true;
                    $book->discount = 10;
                    $book->admin_user_id = Admin::user()->id;
                    $book->save();
                    $sale_items->each(function ($item) {
                        $item->can_recover = 1;
                        $item->save();
                        $this->app->template_message->send([
                            'touser' => $item->user->mp_open_id,
                            'template_id' => 'rgch7KVIzLxC7yX0SwH9_HWK6e4VPufqKbbDBGsLww0',
                            'url' => env('APP_URL') . '/wechat/scan',
                            'data' => [
                                'first' => '《' . $item->book->name . '》回流鱼开放收取了，快去卖书吧',
                                'keyword1' => '《' . $item->book->name . '》作者：' . $item->book->author,
                                'keyword2' => '1',
                                'keyword3' =>  $item->book->price . '元',
                                'keyword4' => Carbon::now()->toDateTimeString(),
                                'remark' => '阅读不孤读！'
                            ]
                        ]);
                    });
                } else {
                    $book->can_recover = false;
                    $book->admin_user_id = Admin::user()->id;
                    $book->discount = 0;
                    $book->save();
                    $sale_items->each(function ($item) {
                        $item->can_recover = 0;
                        $item->save();
                    });
                }
            }
        });
    }

    public function updateDouInfo(Request $request)
    {
        $i = 0;
        foreach (Book::find($request->get('ids')) as $book) {
            UpdateBookFromDouban::dispatch($book)->delay(now()->addSecond($i));
            $i += 3;
        }
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Admin::grid(Book::class, function (Grid $grid) {
            $grid->model()->orderBy('id', 'desc');
            $grid->column('ID/封面/价格/出版社')->display(function () {
                $a = "<img src='" . $this->cover_replace . "' style='width:70px;'/><br>" .
                    "<span>" . $this->id . "</span><br>" .
                    "<span>subjectid: " . $this->subjectid . "</span><br>" .
                    "<span style='color: #888888'>价格：" . $this->price . " / " . $this->page_num . "页</span><br>";
                if ($this->admin_user_id > 0) {
                    $a = $a . "<span class='label label-success'>管理员决策</span><br>";
                } else {
                    $a = $a . "<span class='label label-default'>机器决策</span><br>";
                }
                if ($this->can_recover == 1) {
                    $a = $a . "<span class='label label-success'>收取折扣</span> <span class='label label-success'>" . $this->discount . "%</span>";
                } else {
                    $a = $a . "<span class='label label-primary'>不收</span>";
                }
                return $a;
            });
            // 创建一个列名为 用户名 的列，内容是用户的 name 字段。下面的 email() 和 created_at() 同理
            $grid->column('书名/作者/ISBN/出版社/豆瓣评分')->display(function () {
                $isbn = strlen($this->isbn) == 13 ? $this->isbn : '9789624516739';
                if (strpos($this->isbn, 'x') !== false) {
                    $isbn = '9789624516739';
                }
                if (strpos($this->isbn, 'X') !== false) {
                    $isbn = '9789624516739';
                }
                //                $imgBase64 = self::$DNS1D->getBarcodePNG($isbn, "EAN13");
                $x = "<span style='font-size: 15px;font-weight: 700;word-wrap:break-word;'>" . $this->name . "</span><br>" .
                    //                    '<img src="data:image/png;base64,' . $imgBase64 . '" alt="barcode" /><br>'.
                    "<span style='color: #888888;word-wrap:break-word;'>" . $this->isbn . " / " . $this->id . "</span><br>" .
                    "<span style='color: #CCCCCC;word-wrap:break-word;'>作者：" . mb_substr($this->author, 0, 10) . "</span><br>" .
                    "<span style='color: #CCCCCC;word-wrap:break-word;'>译者：" . $this->translator . "</span><br>" .
                    "<span style='color: #CCCCCC;word-wrap:break-word;'>出版社：" . $this->press . "</span><br>" .
                    "<span style='color: #CCCCCC;word-wrap:break-word;'>出版时间: " . $this->publish_year . "</span><br>" .
                    "<span style='color: green'>评分/人数：" . $this->rating_num . " / " . $this->num_raters . "</span><br>" .
                    "<span>系列：" . $this->series . "</span><br>" .
                    "<span>装帧：" . $this->binding . "</span><br>";
                $a = explode(',', $this->category);
                $b = array_map(function ($g) {
                    return '<span class="badge badge-secondary">' . $g . '</span>&nbsp;';
                }, $a);
                return $x . join(' ', $b);
            })->style('width:15%;word-wrap:break-word;');
            $grid->column('其他平台价格')->display(function () {
                $prices = $this->prices;
                if (count($prices) > 0) {
                    $other_prices = $prices->first();
                    $dd_new_price = $other_prices->dd_new_price ? "<span class='label label-warning'>当当网：" . $other_prices->dd_new_price . "</span><br>" : '';
                    $jd_new_price = $other_prices->jd_new_price ? "<span class='label label-warning'>京东商城：" . $other_prices->jd_new_price . "</span><br>" : '';
                    $amz_new_price = $other_prices->amz_new_price ? "<span class='label label-warning'>亚马逊：" . $other_prices->amz_new_price . "</span><br>" : '';
                    $bc_new_price = $other_prices->bc_new_price ? "<span class='label label-warning'>中国图书网：" . $other_prices->bc_new_price . "</span><br>" : '';
                    $dzy_price = $other_prices->bc_new_price ? "<span class='label label-warning'>dzy：" . $other_prices->dzy_price . "</span><br>" : '';
                    $douban_es_count = $other_prices->douban_es_count ? "<span class='label label-warning'>豆瓣在售二手：" . $other_prices->douban_es_count . "本</span><br>" : '';
                    $douban_es_low = $other_prices->douban_es_low ? "<span class='label label-warning'>豆瓣二手低价：" . $other_prices->douban_es_low . "</span><br>" : '';
                    $douban_es_high = $other_prices->douban_es_high ? "<span class='label label-warning'>豆瓣二手高价：" . $other_prices->douban_es_high . "</span><br>" : '';
                    $douban_es_want_count = $other_prices->douban_es_want_count ? "<span class='label label-warning'>豆瓣二手需求：" . $other_prices->douban_es_want_count . "人</span><br>" : '';
                    return $dd_new_price . $jd_new_price . $amz_new_price . $bc_new_price . $dzy_price . $douban_es_count . $douban_es_low . $douban_es_high . $douban_es_want_count;
                } else {
                    return '';
                }
            });
            $grid->price('RMB价格')->editable();
            $grid->original_price('原价格');
            $grid->can_recover('收取情况')->editable('select', [
                0 => '不收',
                1 => '收'
            ]);
            $grid->admin_user_id('决策')->editable();
            $grid->volume_count('册数')->editable();
            $grid->type('类型')->editable('select', [
                Book::TYPE_DEFAULT => Book::$typeMap[Book::TYPE_DEFAULT],
                Book::TYPE_OUT_OF_PRINT => Book::$typeMap[Book::TYPE_OUT_OF_PRINT],
                Book::TYPE_BAN => Book::$typeMap[Book::TYPE_BAN],
            ]);
            $grid->versions('版本')->display(function ($versions) {
                if (count($versions) == 0) {
                    return '<span class="label label-primary">默认版本</span><br>';
                }
                $b = array_map(function ($version) {
                    return '<p>'.$version['id'] .'/'. $version['title'] . '/￥' . $version['price'] . '</span></p>';
                }, $versions);
                return '<span class="label label-primary">默认</span><br>' . join('', $b);
            });
            $grid->reminder_count('想要')->sortable();
            $grid->sale_sku_count('在售')->sortable();
            $grid->sale_item_count('粉丝在卖')->sortable();
            $grid->all_sku_count('总 SKU 数')->sortable();
            $grid->user_add('来源')->sortable();
            $grid->updated_at('最后更新')->sortable();
            // 不在页面显示 `新建` 按钮，因为我们不需要在后台新建用户
//            $grid->disableCreateButton();

            $grid->actions(function ($actions) {
                // 不在每一行后面展示按钮
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
                    $batch->add('改为收取', new RecoverBook(1));
                    $batch->add('改为不收', new RecoverBook(0));
                    //                    $batch->add('更新豆瓣信息', new UpdateBookDoubanInfo(1));
                });
            });

            $grid->filter(function ($filter) {

                // 去掉默认的id过滤器
                //                $filter->disableIdFilter();

                // 在这里添加字段过滤器
                $filter->where(function ($query) {
                    $query->where('isbn', 'like', "{$this->input}%");
                }, 'ISBN');
                $filter->gt('rating_num', '豆瓣评分');
                $filter->gt('num_raters', '评分人数');
                $filter->gt('price', '定价');
                $filter->where(function ($query) {
                    $query->where('name', 'like', "{$this->input}%");
                }, '书名');
                $filter->where(function ($query) {
                    $query->where('author', 'like', "{$this->input}%");
                }, '作者');
                $filter->where(function ($query) {
                    $query->where('translator', 'like', "{$this->input}%");
                }, '译者');
                $filter->where(function ($query) {
                    $query->where('category', 'like', "{$this->input}%");
                }, '标签');
                $filter->where(function ($query) {
                    $query->where('series', 'like', "{$this->input}%");
                }, '系列');
                //                $filter->where(function ($query) {
                //                    $query->where('publish_year', 'like', "%{$this->input}%");
                //                }, '出版年');
                $filter->gt('publish_year', '出版年');
                $filter->where(function ($query) {
                    $query->where('press', 'like', "{$this->input}%");
                }, '出版社');
                $filter->in('can_recover', '收取状态')->multipleSelect([
                    0 => '不收',
                    1 => '收取'
                ]);
                $filter->in('admin_user_id', '决策方')->multipleSelect([
                    0 => '机器决策',
                    1 => '管理员决策'
                ]);
                $filter->equal('user_add', '来源');
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
        return Admin::form(Book::class, function (Form $form) {

            $form->tab('运营信息', function ($form) {
                $form->display('id', 'ID');
                $form->text('isbn', 'ISBN');
                $form->text('name', '书名');
                $form->text('author', '作者');
                $form->text('press', '出版社');
                $form->display('category', '标签');
                $form->image('cover_replace', '封面')->uniqueName();
                $form->display('binding', '装帧');
                $form->display('page_num', '页数');
                $form->display('rating_num', '豆瓣评分');
                $form->display('num_raters', '评分人数');
                $form->text('price', '价格');
                $form->display('publish_year', '出版日期');
                $form->text('volume_count', '册数');
                $form->text('discount', '收书初始折扣');
                $form->display('sale_discount', '卖书初始折扣');
                $form->select('can_recover', '是否收取')->options([
                    Book::STATUS_ACCEPT => Book::$statusMap[Book::STATUS_ACCEPT],
                    Book::STATUS_REJECT => Book::$statusMap[Book::STATUS_REJECT],
                ]);
                $form->select('admin_user_id', '决策')->options([
                    0 => '算法',
                    Admin::user()->id => '管理员'
                ]);
                $form->select('type', '类型')->options([
                    Book::TYPE_DEFAULT => Book::$typeMap[Book::TYPE_DEFAULT],
                    Book::TYPE_OUT_OF_PRINT => Book::$typeMap[Book::TYPE_OUT_OF_PRINT],
                    Book::TYPE_BAN => Book::$typeMap[Book::TYPE_BAN],
                ]);
            })->tab('内容简介', function ($form) {
                $form->textarea('summary', '内容简介');
            })->tab('作者简介', function ($form) {
                $form->textarea('author_intro', '作者简介');
            })->tab('目录', function ($form) {
                $form->textarea('catalog', '目录');
            })->tab('版本', function ($form) {
                $form->hasMany('versions', function (Form\NestedForm $form) {
                    $form->display('id');
                    $form->text('title', '新版本说明')->rules('required');
                    $form->text('press', '新版本出版社')->rules('required');
                    $form->currency('price', '新版本价格')->symbol('￥')->rules('required|numeric');
                    $form->image('cover', '新版本封面')->uniqueName();
                });
            });
            $form->saved(function (Form $form) { });
        });
    }

    public function versions(Request $request)
    {
        $isbn = $request->get('q');
        $book = Book::where('isbn', $isbn)->first();
        if ($book) {
            return BookVersion::with('book')->where('book_id', $book->id)
                ->paginate(null, ['id', 'price', 'title', DB::raw("concat('价格：￥', price,'，版本说明：',title) as text")]);
        }
        return [];
    }
}
