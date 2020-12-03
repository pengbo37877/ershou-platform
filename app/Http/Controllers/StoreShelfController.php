<?php

namespace App\Http\Controllers;

use App\Book;
use App\BookSku;
use App\BookVersion;
use App\Jobs\CrawlingByWebPageSubjectId;
use App\Jobs\NotifyUserBookOnSaleJob;
use App\ReminderItem;
use App\StoreShelf;
use Carbon\Carbon;
use EasyWeChat\OfficialAccount\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use phpspider\core\requests;
use Ramsey\Uuid\Uuid;
use zgldh\QiniuStorage\QiniuStorage;
use Illuminate\Support\Facades\Cache;

class StoreShelfController extends Controller
{
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $url = env('APP_URL');
        return view('storeShelf.index', compact('url'));
    }

    public function update()
    {
        $store_id = request('store');
        $code = request('code');
        $ids = request('ids');
        if($code){
            $store = StoreShelf::withCount('skus')->where('code',$code)->first();
        }elseif ($store_id){
            $store = StoreShelf::withCount('skus')->find($store_id);
        }else{
            return response()->json(['msg' => '仓库编码不能为空', 'code' => 500]);
        }
        if (!$store) {
            return response()->json(['msg' => '没有找到ID为' . $store_id . '的仓库', 'code' => 500]);
        }
        $left_count = $store->capacity - $store->skus_count;
        if (count($ids) > $left_count) {
            return response()->json(['msg' => '仓库' . $store_id . '剩余空间不足', 'code' => 500]);
        }
        foreach (BookSku::find(request('ids')) as $sku) {
            $sku->store_shelf_id = $store->id;
            $sku->save();
            if($sku->status == BookSku::STATUS_RETREADING){
                $this->updateSku($sku);
            }
        }
        return response()->json(['msg' => '操作成功', 'code' => 200]);
    }

    function updateSku($sku)
    {
        // 判断是否上架
        if ($sku->level == BookSku::LEVEL_100) { //全新上架一本
            // 新书只上架一本，其他的转为自动上架
            $count = BookSku::where('book_id', $sku->book_id)->where('level', BookSku::LEVEL_100)->where('status', BookSku::STATUS_FOR_SALE)->count();
            if ($count == 0) {
                $sku->status = BookSku::STATUS_FOR_SALE;
                $sku->sale_at = Carbon::now();
                $sku->save();
            } else {
                $sku->status = BookSku::STATUS_READY_TO_GO;
                $sku->save();
            }
        } else if ($sku->level == BookSku::LEVEL_80) { // 上好上架一本
            // 新书只上架一本，其他的转为自动上架
            $count = BookSku::where('book_id', $sku->book_id)->where('level', BookSku::LEVEL_80)->where('status', BookSku::STATUS_FOR_SALE)->count();
            if ($count == 0) {
                $sku->status = BookSku::STATUS_FOR_SALE;
                $sku->sale_at = Carbon::now();
                $sku->save();
            } else {
                $sku->status = BookSku::STATUS_READY_TO_GO;
                $sku->save();
            }
        } else if ($sku->level == BookSku::LEVEL_60) { // 中等填补3个中的剩下
            // 新书只上架一本，其他的转为自动上架
            $count = BookSku::where('book_id', $sku->book_id)->where('status', BookSku::STATUS_FOR_SALE)->count();
            if ($count < 3) {
                $sku->status = BookSku::STATUS_FOR_SALE;
                $sku->sale_at = Carbon::now();
                $sku->save();
            } else {
                $sku->status = BookSku::STATUS_READY_TO_GO;
                $sku->save();
            }
        } else {
            $sku->status = BookSku::STATUS_FOR_SALE;
            $sku->sale_at = Carbon::now();
            $sku->save();
        }
        // 书上架，通知粉丝，通知过3次的人不再通知
        $rs = DB::select("select * from reminder_items where book_id=? and deleted_at is null order by notify_times asc, open_times desc, updated_at asc limit 50", [$sku->book_id]);
        for ($i = 0; $i < count($rs); $i++) {
            $r = $rs[$i];
            NotifyUserBookOnSaleJob::dispatch($r->user_id, $r->book_id)->delay(now()->addSecond(20 * $i));
        }
    }

    public function createStores(){
        // 新增书架
        $code = request('code');
        if(!$code){
            // 新增排
            $row = request('row');
            $desc = request('desc');
            if(!$row || !$desc){
                return response()->json([
                    "code"=>500,
                    "msg"=>"请填写新增排编号。"
                ]);
            }
            $count = StoreShelf::where('code','like',$row.'%')->count();
            if($count>0){
                return response()->json([
                    "code"=>500,
                    "msg"=>"排号已占用"
                ]);
            }
            $data = array();
            foreach (["A","B"] as $face){
                for ($i=1;$i<5;$i++){
                    for($j=5;$j>=1;$j--){
                        for($k=1;$k<=5;$k++){
                            $num = str_pad(($i-1)*25 + (5-$j)*5 + $k,2,'0',STR_PAD_LEFT);
                            $shelf_data = [
                                "code"=>"$row$face-$i-$j-$num",
                                "desc"=>"$desc",
                                "capacity"=>16,
                                "unit"=>"1",
                                "row_num"=>$row,
                                "shelf_num"=>$i,
                                "floor_num"=>$j,
                                "box_num"=>$num,
                                "created_at"=>Carbon::now(),
                                "updated_at"=>Carbon::now(),
                            ];
                            array_push($data,$shelf_data);
                        }
                    }
                }
            }
            StoreShelf::insert($data);
        }else{
            $data = array();
            $obj = StoreShelf::where('code','like',$code.'%')->first();
            if(!$obj){
                return response()->json([
                    "code"=>500,
                    "msg"=>"排号不存在组"
                ]);
            }
            $shelf_num = request('shelf_num');
            foreach (["A","B"] as $face){
                for($j=5;$j>=1;$j--){
                    for($k=1;$k<=5;$k++){
                        $num = str_pad(($shelf_num-1)*25 + (5-$j)*5 + $k,2,STR_PAD_LEFT);
                        $shelf_data = [
                            "code"=>"$code$face-$shelf_num-$j-$num",
                            "desc"=>"$obj->desc",
                            "capacity"=>16,
                            "unit"=>"1",
                            "row_num"=>$obj->row_num,
                            "shelf_num"=>$shelf_num,
                            "floor_num"=>$j,
                            "box_num"=>$num,
                            "created_at"=>Carbon::now(),
                            "updated_at"=>Carbon::now(),
                        ];
                        array_push($data,$shelf_data);
                    }
                }
            }
            StoreShelf::insert($data);
        }
        Cache::forget("store_rows");
        return response()->json([
            "code"=>200,
            "msg"=>"ok"
        ]);
    }

    public function getStores()
    {
        return StoreShelf::withCount('skus')->get();
    }

    public function getStores2()
    {
//        Cache::forget('store_rows');
        $row_data = Cache::remember('store_rows',1440,function () {
            $rows = StoreShelf::select('row_num','shelf_num')->distinct('row_num','shelf_num')->get();
            $row_array = array();
            foreach ($rows as $row){
                if(is_numeric($row->row_num)){
                    $row_name_A = str_pad($row->row_num,2,'0',STR_PAD_LEFT)."A";
                    $row_name_B = str_pad($row->row_num,2,'0',STR_PAD_LEFT)."B";
                    if(array_key_exists($row_name_A,$row_array)){
                        array_push($row_array[$row_name_A],$row->shelf_num);
                    }else{
                        $row_array[$row_name_A] = array();
                        array_push($row_array[$row_name_A],$row->shelf_num);
                    }
                    if(array_key_exists($row_name_B,$row_array)){
                        array_push($row_array[$row_name_B],$row->shelf_num);
                    }else{
                        $row_array[$row_name_B] = array();
                        array_push($row_array[$row_name_B],$row->shelf_num);
                    }
                }else{
                    if(array_key_exists("旧书架",$row_array)){
                        array_push($row_array["旧书架"],$row->shelf_num);
                    }else{
                        $row_array["旧书架"] = array();
                        array_push($row_array["旧书架"],$row->shelf_num);
                    }
                }
            }
            return $row_array;
        });
        return $row_data;
    }

    public function getBoxes(){
        $row = request('row');
        $shelf = request('shelf');
        if(is_numeric($row[0])){
            $boxes = StoreShelf::withCount('skus')->where('code','like',$row.'%')->where('shelf_num',$shelf)->get();
        }else{
            $boxes = StoreShelf::withCount('skus')->where('code','like',$shelf.'%')->get();
        }
        return $boxes;
    }

    public function getSkuByCode()
    {
        $code = request('code');
        $sku = BookSku::with('book.versions', 'store_shelf', 'book_version')->where('hly_code', $code)->first();
        if ($sku) {
            $book = $sku->book;
            if (empty($book->cover_replace)) {
                CrawlingByWebPageSubjectId::dispatch($book->subjectid);
            }
            if ($sku->status == BookSku::STATUS_SOLD) {
                return response()->json(['msg' => '该Sku已售，无法入库', 'code' => 500]);
            }
            return $sku;
        }
        return response()->json(['msg' => '没有找到编码为' . $code . '的Sku', 'code' => 500]);
    }

    public function config(Request $request)
    {
        $url = request('url');
        $referer = $request->server->get("HTTP_REFERER");
        if ($url) {
            $config = $this->app->jssdk->setUrl(env('APP_URL') . '/store_shelf/' . $url)->buildConfig([
                'checkJsApi', 'scanQRCode'
            ], env('WX_DEBUG', false));
        } else if($referer){
            $config = $this->app->jssdk->setUrl($referer)->buildConfig([
                'checkJsApi', 'scanQRCode'
            ], env('WX_DEBUG', false));
        }else{
            $config = $this->app->jssdk->setUrl(env('APP_URL') . '/store_shelf')->buildConfig([
                'checkJsApi', 'scanQRCode'
            ], env('WX_DEBUG', false));
        }

        return $config;
    }

    public function uploadCover(Request $request)
    {
        if (!$request->hasFile('file')) {
            return response()->json(['msg' => '没有图片', 'code' => 500]);
        }
        $file = $request->file('file');
        $book = Book::find(request('book'));
        if (!$book) {
            return response()->json(['msg' => '书没找到', 'code' => 500]);
        }
        $disk = QiniuStorage::disk('qiniu');
        if (!empty($file)) {
            $name = $disk->put('sku', $file);
            Log::info('uploadCover name=' . $name);
            $book->cover_replace = $book->cover_replace = "http://pic.ovoooo.com/" . $name;
            $book->save();
        }
        return "http://pic.ovoooo.com/" . $name;
    }

    public function uploadVersionCover(Request $request)
    {
        if (!$request->hasFile('file')) {
            return response()->json(['msg' => '没有图片', 'code' => 500]);
        }
        $file = $request->file('file');
        $disk = QiniuStorage::disk('qiniu');
        $name = $disk->put('sku', $file);
        if (request('version') != 0) {
            $bookVersion = BookVersion::find(request('version'));
            if ($bookVersion) {
                $bookVersion->cover = "http://pic.ovoooo.com/" . $name;
                $bookVersion->save();
            }
        }
        return "http://pic.ovoooo.com/" . $name;
    }

    public function updateSkuVersion()
    {
        $sku_id = request('sku');
        $version_id = request('version');
        $sku = BookSku::with('book')->find($sku_id);
        $version = BookVersion::find($version_id);
        if (empty($sku)) {
            return response()->json(['msg' => 'sku不存在', 'code' => 500]);
        }
        if ($version_id != 0 && empty($version)) {
            return response()->json(['msg' => '版本不存在', 'code' => 500]);
        }
        if ($version_id != 0 && $sku->book_id != $version->book_id) {
            return response()->json(['msg' => '书籍不匹配', 'code' => 500]);
        }
        $sku->book_version_id = $version_id;
        if ($version_id == 0) {
            $sku->original_price = $sku->book->price;
        } else {
            $sku->original_price = $version->price;
        }
        $sku->save();
        return BookSku::with('book.versions', 'store_shelf', 'book_version')->find($sku_id);
    }

    public function createBookVersion()
    {
        if (request('id')) {
            $bookVersion = BookVersion::find(request('id'));
            $versions = BookVersion::where('book_id',$bookVersion->book_id)
                ->where('title',request('title'))->first();
            if($versions){
                return response()->json(['code'=>500,'msg'=>'新版本说明要求唯一']);
            }
            $bookVersion->update([
                'title' => request('title'),
                'name' => request('name'),
                'price' => request('price'),
                'cover' => request('cover'),
                'press' => request('press'),
                'publish_year' => request('publish_year'),
            ]);
            return $bookVersion;
        }
        $versions = BookVersion::where('book_id',request('book_id'))
            ->where('title',request('title'))->first();
        if($versions){
            return response()->json(['code'=>500,'msg'=>'新版本说明要求唯一']);
        }
        return BookVersion::create(request()->all());
    }
}
