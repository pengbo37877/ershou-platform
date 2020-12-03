<?php

namespace App\Admin\Controllers;

use App\Admin\Extensions\Tools\GenerateCode;
use App\BookSku;
use App\StoreShelf;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Milon\Barcode\DNS1D;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Barryvdh\Snappy\Facades\SnappyPdf;

class StoreShelvesController extends Controller
{
    use HasResourceActions;
    private static $DNS1D;

    public function __construct(DNS1D $DNS1D)
    {
        self::$DNS1D = $DNS1D;
    }
    /**
     * Index interface.
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        return $content
            ->header('仓库')
            ->description('回流鱼仓库')
            ->body($this->grid());
    }

    /**
     * Show interface.
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function show($id, Content $content)
    {
        return $content
            ->header('Detail')
            ->description('description')
            ->body($this->detail($id));
    }

    /**
     * Edit interface.
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function edit($id, Content $content)
    {
        return $content
            ->header('Edit')
            ->description('description')
            ->body($this->form()->edit($id));
    }

    /**
     * Create interface.
     *
     * @param Content $content
     * @return Content
     */
    public function create(Content $content)
    {
        return $content
            ->header('创建仓库')
            ->description('嗯')
            ->body($this->form());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new StoreShelf);
        $grid->model()->withCount('skus');
        $grid->id('ID')->sortable();
        $grid->code('编码');
        $grid->column('条形码')->display(function (){
            return self::$DNS1D->getBarcodeSVG($this->code, "C93",1,40);
        });
        $grid->desc('描述');
        $grid->column('容量')->display(function() {
            return $this->capacity . StoreShelf::$unitMap[$this->unit];
        });
        $grid->column('已占用')->display(function() {
            return $this->skus_count;
        });

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
                $batch->add('生成贴片', new GenerateCode(1));
            });
        });
        $grid->filter(function($filter){

            // 去掉默认的id过滤器
            $filter->disableIdFilter();

            // 在这里添加字段过滤器
            $filter->where(function ($query) {
                $query->where('code', 'like', "{$this->input}%");
            }, '编码');
        });
        return $grid;
    }

    public function generateCode(Request $request){
        Storage::disk('public')->delete('storeShelvesCode.pdf');
        $ids = $request->get('ids');
        $codes = StoreShelf::find($ids);
        $arr = array();
        foreach ($codes as $code){
            $json = array();
            $base64 = self::$DNS1D->getBarcodePNG($code->code, "C93",2,70);
            $json["base64"] = $base64;
            $json["code"] = $code->code;
            array_push($arr,$json);
        }
        $view = view('pdf.gencode', compact('arr'));
        $html = response($view)->getContent();
//        return $html;
        // 生成pdf
        $pdf = SnappyPdf::loadHTML($html)->setOption('page-width', 300)
            ->setOption('page-height', 1000)->setWarnings(false)->save('storage/storeShelvesCode.pdf');
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(StoreShelf::findOrFail($id));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new StoreShelf);
        $form->text('code', '编码');
        $form->text('desc', '描述');
        $form->number('capacity', '容量');
        $form->select('unit', '容量单位')->options([
            StoreShelf::UNIT_BEN => StoreShelf::$unitMap[StoreShelf::UNIT_BEN],
            StoreShelf::UNIT_CUBIC_METERS => StoreShelf::$unitMap[StoreShelf::UNIT_CUBIC_METERS],
        ]);
        return $form;
    }
}
