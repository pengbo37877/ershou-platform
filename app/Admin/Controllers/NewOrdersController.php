<?php

namespace App\Admin\Controllers;

use App\Admin\Extensions\Tools\ShipBill;
use App\Admin\Extensions\Tools\StockOut;
use App\Admin\Extensions\Tools\ZtoBill;
use App\Admin\Extensions\ZtoExport;
use App\Book;
use App\BookShelf;
use App\BookSku;
use App\Events\OrderCanceled;
use App\Events\OrderCompleted;
use App\Events\OrderShipped;
use App\Events\OrderStockOut;
use App\Events\RecoverOrderChecked;
use App\Jobs\EnableCouponJob;
use App\Jobs\ZtoExposeServicePushOrderServiceJob;
use App\Jobs\ZtoOpenOrderCreateJob;
use App\Order;

use App\OrderItem;
use App\ReminderItem;
use App\User;
use App\UserAddress;
use App\Utils\JSON;
use App\Wallet;
use Barryvdh\Snappy\Facades\SnappyImage;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;
use Illuminate\Filesystem\Cache;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\MessageBag;
use Knp\Snappy\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use function Sodium\add;

class NewOrdersController extends Controller
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

            $content->header('订单');
            $content->description('收书和卖书的订单都在这里');

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

            $content->header('更新订单');
            $content->description('建议你只更新运营信息');

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

            $content->header('创建订单');
            $content->description('真的有必要吗？');

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
        $grid = new Grid(new Order);
        $grid->model()->where('new_flag',1)->orderBy('created_at', 'desc');
        $grid->id('ID')->sortable();
        $grid->type('订单')->display(function ($type) {
            $no = "<p>" . $this->no . "</p>";
            if ($this->express_prev_no) {
                $no .= "<p>中通订单号：" . $this->express_prev_no . "</p>";
            }
            $recover_all_count = Order::where('user_id', $this->user_id)
                ->where('type', Order::ORDER_TYPE_RECOVER)->count();
            $recover_complete_count = Order::where('user_id', $this->user_id)
                ->where('type', Order::ORDER_TYPE_RECOVER)
                ->where('recover_status', Order::RECOVER_STATUS_COMPLETE)->count();
            $sale_all_count = Order::where('user_id', $this->user_id)
                ->where('type', Order::ORDER_TYPE_SALE)->count();
            $sale_complete_count = Order::where('user_id', $this->user_id)
                ->where('type', Order::ORDER_TYPE_SALE)
                ->where('sale_status', Order::SALE_STATUS_COMPLETE)->count();
            $evil_count = Order::where('user_id', $this->user_id)
                ->where('is_evil', 1)->count();
            if ($type == Order::ORDER_TYPE_RECOVER) {
                return $no . "<span class='label label-primary'>收书</span><br><span class='label label-danger'>未完成" . ($recover_all_count - $recover_complete_count) . "次</span><br>" .
                    "<span class='label label-primary'>收书</span><br><span class='label label-danger'>已完成" . $recover_complete_count . "次</span><br>" .
                    "<span class='label label-primary'>恶意</span><br><span class='label label-danger'>" . $evil_count . "次</span>" .
                    "<p>上门时间：" . $this->recover_time . "</p>";
            } else {
                return $no . "<span class='label label-primary'>卖书</span><br><span class='label label-danger'>未完成" . ($sale_all_count - $sale_complete_count) . "次</span><br>" .
                    "<span class='label label-primary'>卖书</span><br><span class='label label-danger'>已完成" . $sale_complete_count . "次</span><br>" .
                    "<span class='label label-primary'>恶意</span><br><span class='label label-danger'>" . $evil_count . "次</span>";
            }
        })->sortable();
        $grid->recover_status("收书状态")->editable('select', [
            Order::RECOVER_STATUS_CANCEL => "用户取消",
            Order::RECOVER_STATUS_PENDING => "下单成功",
            Order::RECOVER_STATUS_VERIFIED => "审核通过",
            Order::RECOVER_STATUS_ARRANGE_EXPRESS => "叫快递",
            Order::RECOVER_STATUS_CHECKED => "已审核，通知用户",
            Order::RECOVER_STATUS_DELIVERED => "已发货",
            Order::RECOVER_STATUS_RECEIVED => "已收货",
            Order::RECOVER_STATUS_PAYING => "打款中",
            Order::RECOVER_STATUS_COMPLETE => "完成(已打款)",
        ]);
        $grid->sale_status('卖书状态')->editable('select', [
            Order::SALE_STATUS_CANCEL => "已取消",
            Order::SALE_STATUS_PENDING => "待支付",
            Order::SALE_STATUS_PAID => "已支付",
            Order::SALE_STATUS_STOCK_OUT => "已出库",
            Order::SALE_STATUS_ORDERED_EXPRESS => "已安排快递",
            Order::SALE_STATUS_DELIVERED => "已发货",
            Order::SALE_STATUS_COMPLETE => "已签收",
        ]);
        $grid->column('用户')->display(function () {
            $user = '<p>' . $this->user->nickname . '</p><p> [ ' . $this->user_id . ' ]</p>';
            $address = UserAddress::where('id', $this->address_id)->withTrashed()->first();
            if ($this->type == Order::ORDER_TYPE_SALE && !is_null($address)) {
                return $user . '<p style="color: #1c7430">' . $address->contact_name . '</p>' .
                    '<p style="color: #1c7430">' . $address->contact_phone . '</p>' .
                    '<p style="color: #1c7430; font-size: 11px;">' . $address->province . '</p>' .
                    '<p style="color: #1c7430; font-size: 11px;">' . $address->city . '</p>' .
                    '<p style="color: #1c7430; font-size: 11px;">' . $address->district . '</p>' .
                    '<p style="color: #1c7430; font-size: 11px;">' . $address->address . '</p>';
            } else if ($this->type == Order::ORDER_TYPE_RECOVER && !is_null($address)) {
                return $user . '<p style="color: #2ea8e5">' . $address->contact_name . '</p>' .
                    '<p style="color: #2ea8e5">' . $address->contact_phone . '</p>' .
                    '<p style="color: #2ea8e5;font-size: 11px;">' . $address->province . '</p>' .
                    '<p style="color: #2ea8e5;font-size: 11px;">' . $address->city . '</p>' .
                    '<p style="color: #2ea8e5;font-size: 11px;">' . $address->district . '</p>' .
                    '<p style="color: #2ea8e5;font-size: 11px;">' . $address->address . '</p>';
            } else {
                return $user;
            }
        });
        $grid->express('快递公司')->editable('select', [
            'ZTO' => '中通',
            'DBL' => '德邦',
            'SF' => '顺丰',
            'EMS' => '邮政'
        ]);
        $grid->express_no('运单号')->editable();
        $grid->ship_price('快递费')->editable();
        $grid->ship_status('快递状态')->editable('select', [
            Order::SHIP_STATUS_PENDING => Order::$shipStatusMap[Order::SHIP_STATUS_PENDING],
            Order::SHIP_STATUS_DELIVERED => Order::$shipStatusMap[Order::SHIP_STATUS_DELIVERED],
            Order::SHIP_STATUS_RECEIVED => Order::$shipStatusMap[Order::SHIP_STATUS_RECEIVED],
        ]);
        $grid->items('包含的书')->display(function ($items) {
            $priceDesc = '';
            if ($this->type == Order::ORDER_TYPE_SALE) {
                $its = $this->items()->with('bookSku')->get();
                $price = $its->pluck('bookSku')->sum->recover_price;
                $profit = $this->total_amount - $price;
                $coupon = $this->coupon;
                if ($coupon) {
                    $priceDesc = '<span>共' . $its->sum->amount . '本</span>' .
                        '<span class="label label-danger">￥' . $this->total_amount . '</span>' .
                        "<span class='label label-primary'>毛利" . (number_format($profit / $this->total_amount, 2) * 100) . "%</span><br>" .
                        "<span class='label label-warning'>" . $coupon->name . " 编码:" . $coupon->code . "</span><hr>";
                } else {
                    $priceDesc = '<span>共' . $its->sum->amount . '本</span>' .
                        '<span class="label label-danger">￥' . $this->total_amount . '</span>' .
                        "<span class='label label-primary'>毛利" . (number_format($profit / $this->total_amount, 2) * 100) . "%</span><hr>";
                }
            } else {
                $coupon = $this->coupon;
                $its = $this->items()->where('review_result', 1)->get();
                $price = $its->sum->reviewed_price;
                $sale_price = $this->from_skus->sum->price;
                $profit = $sale_price - $price - $this->ship_price;
                if ($coupon) {
                    $priceDesc = '<p>共' . count($its) . '本</p>' .
                        '<span class="label label-primary">预：' . $this->total_amount . '</span><br>' .
                        '<span class="label label-primary">实：' . $price . '</span><br>' .
                        '<span class="label label-warning">' . $coupon->name . ' ID:' . $coupon->id . '</span><br>' .
                        '<span class="label label-primary">卖：' . $sale_price . '</span><br>' .
                        '<span class="label label-success">利润：' . $profit . '</span>';
                } else {
                    $priceDesc = '<p>共' . count($its) . '本</p>' .
                        '<span class="label label-primary">预：' . $this->total_amount . '</span><br>' .
                        '<span class="label label-primary">实：' . $price . '</span><br>' .
                        '<span class="label label-primary">卖：' . $sale_price . '</span><br>' .
                        '<span class="label label-success">利润：' . $profit . '</span>';
                }
            }
            $items = array_map(function ($item) use ($priceDesc) {
                $bookPrice = floatval($item['book_sku']['original_price']);
                $discount = 0;
                if ($bookPrice > 0) {
                    $discount = number_format($item['price'] * 10 / $bookPrice, 1);
                }
                if (is_null($item['book_sku']) && $item['review_result'] == 1) {
                    return '<p style="color: dodgerblue;">' . $item['book']['name'] . ' (￥' . $item['price'] . '/' . $discount . '折)</p>' .
                        '<p style="color: dodgerblue">' . $item['book']['isbn'] . '</p><hr>' .
                        '<p style="color: red">' . $item['review'] . '</p><hr>';
                } else if (is_null($item['book_sku']) && $item['review_result'] == 0) {
                    return '<p style="color: red;">' . $item['book']['name'] . ' (￥' . $item['price'] . '/' . $discount . '折)</p>' .
                        '<p style="color: red">' . $item['book']['isbn'] . '</p>' .
                        '<p style="color: red">' . $item['review'] . '</p><hr>';
                } else {
                    $discount = number_format($item['book_sku']['price'] * 10 / $item['book_sku']['original_price'], 1);
                    return '<p style="color: #666;">' . $item['book']['name'] . ' </p>' .
                        '<p>成本价：' . $item['book_sku']['recover_price'] . '</p>' .
                        '<p>' . $item['book_sku']['title'] . $item['book_sku']['price'] . ' ~ ' . $discount . '折</p>' .
                        '<p style="color: darkslateblue">' . $item['book_sku']['hly_code'] . '</p><hr>';
                }
            }, $items);

            return $priceDesc . join('', $items);
        });
        $grid->column('支付状态')->display(function ($pay) {
            $method = $this->payment_method;
            $paid_at = $this->paid_at;
            if (is_null($paid_at)) {
                $paid_at_desc = "<p style='color: #888 '>未支 付</p>";
            } else {
                $paid_at_desc = "<p style='color:green;font-weight: 700'>已支付</p><p  s tyle = 'color: #0c5460;'>" . $pay . "</p>";
            }

            $refund_desc = "";
            if ($this->refund_status == Order::REFUND_STATUS_SUCCESS) {
                $refund_desc = Order::$refundStatusMap[$this->refund_status];
            }
            if ($this->refunds->count() > 0) {
                $refund_desc = '<p sty le="color: #ff48f8">部分退款' . $this->refunds->sum->amount  . '元</p>';
            } else {
                $refund_desc = Order::$refundStatusMap[$this->refund_status];
            }

            return $method . $paid_at_desc . $refund_desc;
        })->style('width: 80px;');
        $grid->closed('订单状态')->display(function ($c) {
            if ($c == 1) {
                return "<p style='color: #888;text-decoration: line-through '>已关闭</p>";
            } else {
                return "<p style='color: green;'>进行中</p>";
            }
        });
        $states = [
            'on' => ['value' => '1', 'text' => '恶意订单', 'color' => 'danger'],
            'off' => ['value' => '0', 'text' => '正常', 'color' => 'success'],
        ];
        $grid->is_evil('偏好')->switch($states);
        $grid->created_at('创建时间')->sortable();
        $grid->updated_at('更新时间')->sortable();

        // 不在页面显示 `新建` 按钮，因为我们不需要在后台新建用户
        $grid->disableCreateButton();

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
                //                    $batch->add('批量出库', new StockOut(1));
                $batch->add('生成发货单', new ShipBill(1)); // 自带出库功能
                $batch->add('下快递单', new ZtoBill(1)); //
            });
        });

        $grid->exporter(new ZtoExport());

        $grid->filter(function ($filter) {

            // 去掉默认的id过滤器
            //                $filter->disableIdFilter();

            // 在这里添加字段过滤器
            $filter->in('type', '订单类型')->multipleSelect([
                Order::ORDER_TYPE_RECOVER => '收书订单',
                Order::ORDER_TYPE_SALE => '卖书订单'
            ]);
            $filter->like('no', '订单编号');
            $filter->in('closed', '订单状态')->select([
                0 => '进行中',
                1 => '已关闭'
            ]);
            $filter->in('recover_status', '收书状态')->multipleSelect([
                Order::RECOVER_STATUS_CANCEL => "用户取消",
                Order::RECOVER_STATUS_PENDING => "下单成功(快去看看有没有不想收的书)",
                Order::RECOVER_STATUS_VERIFIED => "审核通过(该去叫个顺丰了)",
                Order::RECOVER_STATUS_ARRANGE_EXPRESS => "顺丰已叫(等顺丰取货)",
                Order::RECOVER_STATUS_CHECKED => "有不收的书，已审核，通知用户",
                Order::RECOVER_STATUS_DELIVERED => "顺丰已取货(收到货了要记得入库)",
                Order::RECOVER_STATUS_RECEIVED => "入库完成(嗯嗯，别记得给人家钱)",
                Order::RECOVER_STATUS_PAYING => "回流鱼打款中(要催一下财务，真的)",
                Order::RECOVER_STATUS_COMPLETE => "完成(看来一切顺利)",
            ]);
            $filter->in('sale_status', '卖书状态')->multipleSelect([
                Order::SALE_STATUS_CANCEL => "用户取消",
                Order::SALE_STATUS_PENDING => "下单成功(等待用户支付)",
                Order::SALE_STATUS_PAID => "用户已支付(可以安排货了)",
                Order::SALE_STATUS_STOCK_OUT => "回流鱼出库(用户已经不能取消了☺)",
                Order::SALE_STATUS_ORDERED_EXPRESS => "快递单已下(可以去打印了)",
                Order::SALE_STATUS_DELIVERED => "已发货(坐等用户收货)",
                Order::SALE_STATUS_COMPLETE => "完成(看来一切顺利)",
            ]);
            $filter->like('express', '快递公司');
            $filter->like('express_no', '快递编号');
            $filter->in('ship_status', '快递状态')->multipleSelect([
                Order::SHIP_STATUS_PENDING => Order::$shipStatusMap[Order::SHIP_STATUS_PENDING],
                Order::SHIP_STATUS_DELIVERED => Order::$shipStatusMap[Order::SHIP_STATUS_DELIVERED],
                Order::SHIP_STATUS_RECEIVED => Order::$shipStatusMap[Order::SHIP_STATUS_RECEIVED],
            ]);
            $filter->in('closed', '关闭状态')->multipleSelect([
                Order::PAYING_STATUS_OPEN => '正常',
                Order::PAYING_STATUS_CLOSE => '已关闭',
            ]);
            $filter->in('refund_status', '退款状态')->multipleSelect([
                Order::REFUND_STATUS_PENDING => Order::$refundStatusMap[Order::REFUND_STATUS_PENDING],
                Order::REFUND_STATUS_SUCCESS => Order::$refundStatusMap[Order::REFUND_STATUS_SUCCESS],
                Order::REFUND_STATUS_FAILED => Order::$refundStatusMap[Order::REFUND_STATUS_FAILED],
            ]);
            $filter->equal('user_id', '用户ID');
            $filter->where(function ($query) {
                $query->whereHas('address', function ($query) {
                    $query->where('contact_phone', 'like', "%{$this->input}%")->orWhere('contact_name', 'like', "%{$this->input}%");
                });
            }, '姓名/手机号');
        });

        return $grid;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Order);
        $form->tab('订单基本信息', function ($form) {
            $form->display('id', 'ID');
            $form->display('no', '订单号');
            $form->display('type', '订 单类型')->with(function ($v) {
                if ($v == Order::ORDER_TYPE_RECOVER) {
                    return  '收书订单';
                } else {
                    return '卖书订单';
                }
            });
            $form->select('recover_status', '收书订单状态')->options([
                Order::RECOVER_STATUS_CANCEL => "用户取消",
                Order::RECOVER_STATUS_PENDING => "下单成功(快去看看有没有不想收的书)",
                Order::RECOVER_STATUS_VERIFIED => "审核通过(该去叫个顺丰了)",
                Order::RECOVER_STATUS_ARRANGE_EXPRESS => "顺丰已叫(等顺丰取货)",
                Order::RECOVER_STATUS_CHECKED => "有不收的书，审核过后请转到这个状态",
                Order::RECOVER_STATUS_DELIVERED => "顺丰已取货(收到货了要记得入库)",
                Order::RECOVER_STATUS_RECEIVED => "入库完成(嗯嗯，别记得给人家钱)",
                Order::RECOVER_STATUS_PAYING => "回流鱼打款中(要催一下财务，真的)",
                Order::RECOVER_STATUS_COMPLETE => "完成(看来一切顺利)",
            ])->help('这里是收书订单的状态，请先确定订单类型再修改');
            $form->select('sale_status', '卖书订单状态')->options([
                Order::SALE_STATUS_CANCEL => "用户取消",
                Order::SALE_STATUS_PENDING => "下单成功(等待用户支付)",
                Order::SALE_STATUS_PAID => "用户已支付",
                Order::SALE_STATUS_STOCK_OUT => "回流鱼出库(用户不能取消了)",
                Order::SALE_STATUS_ORDERED_EXPRESS => "快递单已下(可以去打印了)",
                Order::SALE_STATUS_DELIVERED => "已发货",
                Order::SALE_STATUS_COMPLETE => "完成(看来一切顺利)",
            ])->help('这里是卖书订单的状态，请先确定订单类型再修改');;
            $form->display('user_id', ' 用户')->with(function ($id) {
                $user = User::find($id);
                return  $user->nickname . ' [ID: ' . $id . ']';
            });
            $form->display('address_id', ' 地址')->with(function ($id) {
                $ua = UserAddress::find($id);
                if ($ua) {
                    return $ua->province . ' ' . $ua->city . ' ' . $ua->district . ' ' . $ua->address . '  ' . $ua->contact_name . ' ' . $ua->contact_phone;
                }
            });
            $form->display('recover_time', '用户预约快递上门时间');
            $form->currency('total_amount', '价格')->symbol('￥');
            $form->datetime('paid_at', '支付时间')->format('YYYY-MM-DD HH:mm:ss')->help('设置支付时间意味着已支付，如果没有支付请留空');
            $form->display('payment_method', '支付方式');
            $states = [
                'on'  => ['value' => 1, 'text' => '已关闭', 'color' => 'success'],
                'off' => ['value' => 0, 'text' => '进行中', 'color' => 'danger'],
            ];
            $form->switch('closed', '订单状态')->states($states);
            $evils = [
                'on'  => ['value' => 1, 'text' => '恶意订单', 'color' => 'danger'],
                'off' => ['value' => 0, 'text' => '善意', 'color' => 'success'],
            ];
            $form->switch('is_evil', '订单偏好')->states($evils);
            $form->display('created_at', '创建时间');
            $form->display('updated_at', '更新时间');
        })->tab('快递信息', function ($form) {
            $form->select('express', '快递公司')->options([
                'ZTO' => '中通',
                'DBL' => '德邦',
                'SF' => '顺丰快递',
                'EMS' => '邮政'
            ]);
            $form->text('express_no', '快递单号');
            $form->text('ship_price', '快递费');
            $form->select('ship_status', '快递状态')->options([
                Order::SHIP_STATUS_PENDING => Order::$shipStatusMap[Order::SHIP_STATUS_PENDING],
                Order::SHIP_STATUS_DELIVERED => Order::$shipStatusMap[Order::SHIP_STATUS_DELIVERED],
                Order::SHIP_STATUS_RECEIVED => Order::$shipStatusMap[Order::SHIP_STATUS_RECEIVED],
            ]);
            $form->display('ship_data', '快递信息');
        })->tab('订单中的书', function ($form) {
            $form->hasMany('items', function (Form\NestedForm $form) {
                //                    $form->display('id', '折扣')->with(function ($id) {
                //                        Log::info('id='.$id);
                //                        $orderItem = OrderItem::with('book')->find($id);
                //                        $book = $orderItem->book;
                //                        $bookPrice = floatval($book->price);
                //                        if ($bookPrice>0) {
                //                            return number_format($orderItem*10/$bookPrice, 1).'折';
                //                        }
                //                    });
                $form->display('book_id',  '书')->with(function ($id) {
                    $book = Book::find($id);
                    if (!$book) {
                        return '书不存 在';
                    } else {
                        $rc = ReminderItem::where('book_id', $id)->count();
                        return "<img sr c ='" . $book->cover_replace . "' style='width: 50px'/>" .
                            "<p>" . $book->name . "</p>" .
                            "<p>" . $book->isbn . "</p>" .
                            "<p sty l e='color: green;' > 豆瓣评分：" . $book->rating_num . "</p>" .
                            "<p  s tyle='color: #8 8 8888;' >" . $book->category . "</p>" .
                            "<p> 价格 ￥" . $book->price . "</p>" .
                            "<p   style='color: red'>" . $rc . "人想要</p>";
                    }
                });
                $form->display('book_sku_id', 'SKU')->with(function ($id) {
                    $sku = BookSku::find($id);
                    if ($sku) {
                        return '品相=' . $sku->title . ' | 级别 =' . $sku->level . ' | 价格=￥ ' . $sku->price . ' | 回流鱼编码=' . $sku->hly_code;
                    } else {
                        return '暂无';
                    }
                });
                $form->currency('price', '收书价格')->symbol('￥');
                $reviews = [
                    'on'  => ['value' => 1, 'text' => '审核通过', 'color' => 'success'],
                    'off' => ['value' => 0, 'text' => '拒绝', 'color' => 'danger'],
                ];
                $form->switch('review_result', '审核建议')->states($reviews);
                $form->text('review', '拒绝原因 [审核通过请忽略这栏]');
            });
        });

        $form->saving(function ($form) {
            $sale_status = $form->model()->sale_status;
            $recover_status = $form->model()->recover_status;
            $closed = $form->model()->closed;
            if ($sale_status == Order::SALE_STATUS_CANCEL || $recover_status == Order::RECOVER_STATUS_CANCEL || $closed) {
                $error = new MessageBag([
                    'title'   => '错误',
                    'message' => '订单已无效，拒绝修改，强制修改只能找技术人员',
                ]);

                return back()->with(compact('error'));
            }
        });

        $form->saved(function ($form) {
            $type = $form->model()->type;
            $sale_status = $form->model()->sale_status;
            $ship_status = $form->model()->ship_status;
            $recover_status = $form->model()->recover_status;
            $closed = $form->model()->closed;
            if ($type == Order::ORDER_TYPE_RECOVER && $recover_status == Order::RECOVER_STATUS_COMPLETE && $closed == 0) {
                event(new OrderCompleted($form->model()));
            } else if ($type == Order::ORDER_TYPE_RECOVER && $recover_status == Order::RECOVER_STATUS_CHECKED && $closed == 0) {
                event(new RecoverOrderChecked($form->model()));
            } else if ($type == Order::ORDER_TYPE_SALE && $sale_status == Order::SALE_STATUS_STOCK_OUT && $closed == 0) {
                event(new OrderStockOut($form->model()));
            } else if ($type == Order::ORDER_TYPE_SALE && $sale_status == Order::SALE_STATUS_COMPLETE && $closed == 0) {
                event(new OrderCompleted($form->model()));
            } else if ($type == Order::ORDER_TYPE_SALE && $sale_status == Order::SALE_STATUS_CANCEL && $closed == 0) {
                event(new OrderCanceled($form->model()));
            } else if ($type == Order::ORDER_TYPE_RECOVER && $recover_status == Order::RECOVER_STATUS_ARRANGE_EXPRESS && $closed == 0) {
                event(new OrderShipped($form->model()));
            }
        });
        return $form;
    }

    public function shipBill(Request $request)
    {
        // 删除原有的invoice.pdf;
        Storage::disk('public')->delete('invoice.jpg');
        Storage::disk('public')->delete('invoice.pdf');
        $ids = $request->get('ids');
        $orders = Order::with('address')->with('user')->with('items.book')->with('items.bookSku')->with('items.bookSku.store_shelf')->find($ids);
        for ($i = 0; $i < count($orders); $i++) {
            $o = $orders->get($i);
            if ($o->sale_status != Order::SALE_STATUS_STOCK_OUT) {
                $o->sale_status = Order::SALE_STATUS_STOCK_OUT;
                $o->save();
            }
            EnableCouponJob::dispatch($o)->delay(now()->addSecond($i));
        }
        $view = view('pdf.shipBill', compact('orders'));
        $html = response($view)->getContent();

        // 生成pdf
        $pdf = SnappyPdf::loadHTML($html)->setOption('page-width', 250)
            ->setOption('page-height', 1200)->setWarnings(false)->save('storage/invoice.pdf');
    }

    public function ztoBill(Request $request)
    {
        $ids = $request->get('ids');
        $orders = Order::with('address')->where('type', Order::ORDER_TYPE_SALE)->where('closed', 0)->find($ids);

        for ($i = 0; $i < count($orders); $i++) {
            $order = $orders->get($i);
            ZtoExposeServicePushOrderServiceJob::dispatch($order)->onQueue('high');
        }
    }

    public function stockOut(Request $request)
    {
        $ids = $request->get('ids');
        $orders = Order::where('type', Order::ORDER_TYPE_SALE)->where('sale_status', Order::SALE_STATUS_PAID)
            ->where('closed', 0)->find($ids);
        $orders->each(function ($o) {
            $o->sale_status  = Order::SALE_STATUS_STOCK_OUT;
            $o->save();
        });
    }
}
