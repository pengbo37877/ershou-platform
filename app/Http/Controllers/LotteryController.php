<?php

namespace App\Http\Controllers;

use App\Lottery;
use App\LotteryUser;
use App\User;
use App\UserAddress;
use Carbon\Carbon;
use EasyWeChat\MiniProgram\Application;
use http\Env\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use phpspider\core\requests;
use Ramsey\Uuid\Uuid;
use zgldh\QiniuStorage\QiniuStorage;

class LotteryController extends Controller
{
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function index()
    {
        $unionid = request('unionid');
        $type = request('type'); // all,create,win
        Log::info('lotteries unionid='.$unionid);
        $user = User::where('union_id', $unionid)->first();
        if ($user) {
            Log::info('lotteries find user');
            if (is_null($type) || empty($type)) {
                $lotteries = Lottery::with(['participants' => function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                }])->with('winners.user')->withCount('participants')->onHome()->orderByDesc('created_at')->paginate();
            }else if($type=='all') {
                $lottery_ids = LotteryUser::select('lottery_id')->where('user_id', $user->id)->groupBy('lottery_id')->get()->toArray();
                $lotteries = Lottery::whereIn('id', $lottery_ids)->orWhere('user_id', $user->id)->orderByDesc('created_at')->paginate();
            }else if($type=='create') {
                $lotteries = Lottery::where('user_id', $user->id)->orderByDesc('created_at')->paginate();
            }else if($type=='win') {
                $lottery_ids = LotteryUser::select('lottery_id')->where('user_id', $user->id)->where('win', 1)->groupBy('lottery_id')->get()->toArray();
                $lotteries = Lottery::whereIn('id', $lottery_ids)->orderByDesc('created_at')->paginate();
            }
        }else{
            Log::info('lotteries find nothing');
            $lotteries = Lottery::with('winners.user')->withCount('participants')->onHome()->orderByDesc('created_at')->paginate();
        }
        return $lotteries;
    }

    public function show()
    {
        $user_id = request('user');
        $id = request('lottery');
        $unionid = request('unionid');
        if ($user_id) {
            $lottery = Lottery::with(['participants' => function($q) use($user_id){
                $q->where('user_id', $user_id);
            }])->withCount('participants')->with('winners.user')->find($id);
        }else if($unionid){
            $user = User::where('union_id', $unionid)->first();
            $lottery = Lottery::with(['participants' => function($q) use($user){
                $q->where('user_id', $user->id);
            }])->withCount('participants')->with('winners.user')->find($id);
        }else{
            $lottery = Lottery::withCount('participants')->with('winners.user')->find($id);
        }

        if (!$lottery){
            return response()->json([
                'code' => 500,
                'msg' => '你要找的抽奖不存在或已删除'
            ]);
        }
        return $lottery;
    }

    public function store()
    {
        $id = request('id');
        $image = request('image');
        $title = request('title');
        $body = request('body');
        $end_at = request('end_at');
        $desc = request('desc');
        $winner_count = request('winner_count');
        $unionid = request('unionid');
        if ($id) {
            // 更新
            $lottery = Lottery::find($id);
            if (is_null($lottery->uuid)) {
                $lottery->uuid = Lottery::findAvailableUuid();
            }
            $lottery->end_at = Carbon::createFromTimeString($end_at);
            $lottery->title = $title;
            $lottery->image = $image;
            $lottery->desc = $desc;
            $lottery->body = $body?$body:'';
            $lottery->winner_count = $winner_count;
            $lottery->save();
        }else{
            // 新建
            $user = User::where('union_id', $unionid)->first();
            $lottery = Lottery::create([
                'uuid' => Lottery::findAvailableUuid(),
                'user_id' => $user->id,
                'start_at' => Carbon::now(),
                'end_at' => Carbon::createFromTimeString($end_at),
                'title' => $title,
                'image' => $image,
                'desc' => $desc,
                'body' => $body?$body:'',
                'body_format' => 'html',
                'winner_count' => $winner_count,
                'type' => Lottery::TYPE_TIME,
                'status' => Lottery::STATUS_RUNNING,
                'show_on_home' => 0,
            ]);
        }
        return $lottery;
    }

    public function fakeShow()
    {
        $status = request('status');
        $user = request('user');
        $id = request('lottery');
        if ($user) {
            $l = Lottery::with(['participants' => function($q) use($user){
                $q->where('user_id', $user);
            }])->with('winners.user')->find($id);
            $l->status=intval($status);
            return $l;
        }else{
            $l = Lottery::with('winners.user')->find($id);
            $l->status=intval($status);
            return $l;
        }
    }

    public function getLotteryByUuid()
    {
        $user_id = request('user');
        $uuid = request('uuid');
        $unionid = request('unionid');
        if ($user_id) {
            $lottery = Lottery::with(['participants' => function($q) use($user_id){
                $q->where('user_id', $user_id);
            }])->withCount('participants')->with('winners.user')->where('uuid', $uuid)->first();
        }else if($unionid){
            $user = User::where('union_id', $unionid)->first();
            $lottery = Lottery::with(['participants' => function($q) use($user){
                $q->where('user_id', $user->id);
            }])->withCount('participants')->with('winners.user')->where('uuid', $uuid)->first();
        }else{
            $lottery = Lottery::withCount('participants')->with('winners.user')->where('uuid', $uuid)->first();
        }

        if (!$lottery){
            return response()->json([
                'code' => 500,
                'msg' => '你要找的抽奖不存在或已删除'
            ]);
        }
        return $lottery;
    }

    public function login()
    {
        $code = request('code');
        return $this->app->auth->session($code);
    }

    public function user()
    {
        $session = request('session');
        $openid = request('openid');
        $unionid = request('unionid');
        $user = User::where('union_id', $unionid)->first();
        if ($session && $openid && $user) {
            $user->lottery_session=$session;
            $user->lottery_open_id=$openid;
            $user->save();
        }
        return $user;
    }

    public function userLotteryInfo()
    {
        $unionid = request('unionid');
        $user = User::where('union_id', $unionid)->first();
        $lottery_ids = LotteryUser::select('lottery_id')->where('user_id', $user->id)->groupBy('lottery_id')->get()->toArray();
        $all_count = Lottery::whereIn('id', $lottery_ids)->orWhere('user_id', $user->id)->count();
        $create_count = Lottery::where('user_id', $user->id)->count();
        $participate_count = LotteryUser::where('user_id', $user->id)->count();
        $win_count = LotteryUser::where('user_id', $user->id)->where('win', 1)->count();
        return response()->json([
            'all_count' => $all_count,
            'create_count' => $create_count,
            'participate_count' => $participate_count,
            'win_count' => $win_count
        ]);
    }

    public function getUserByUnionId()
    {
        $unionid = request('unionid');
        return User::where('union_id', $unionid)->first();
    }

    public function userAddresses()
    {
        $unionid = request('unionid');
        $user = User::where('union_id', $unionid)->first();
        if ($user) {
            return UserAddress::where('user_id', $user->id)->orderByDesc('created_at')->get();
        }
        return [];
    }

    public function createUserAddress()
    {
        $unionid = request('unionid');
        $name = request('name');
        $phone = request('phone');
        $province = request('province');
        $city = request('city');
        $district = request('district');
        $address = request('address');
        $user = User::where('union_id', $unionid)->first();
        if ($user) {
            $userAddress = UserAddress::create([
                'user_id' => $user->id,
                'province' => $province,
                'city' => $city,
                'district' => $district,
                'address' => $address,
                'contact_name' => $name,
                'contact_phone' => $phone
            ]);
            return $userAddress;
        }
        return response()->json([
            'msg' => '新增地址错误',
            'code' => 500
        ]);
    }

    public function deleteUserAddress()
    {
        $unionid = request('unionid');
        $address_id = request('address');
        $user = User::where('union_id', $unionid)->first();
        $address = UserAddress::find($address_id);
        if ($user && $address && $user->id==$address->user_id) {
            $address->delete();
            return response()->json([
                'msg' => 'success',
                'code' => 200
            ]);
        }else{
            return response()->json([
                'msg' => '删除地址出错',
                'code' => 500
            ]);
        }
    }

    public function updateLotteryAddress()
    {
        $unionid = request('unionid');
        $address_id = request('address');
        $lottery_id = request('lottery');
        $user = User::where('union_id', $unionid)->first();
        $address = UserAddress::find($address_id);
        if ($user && $address && $user->id==$address->user_id) {
            $lottery_user = LotteryUser::where('user_id', $user->id)->where('lottery_id', $lottery_id)->first();
            $lottery_user->address_id = $address_id;
            $lottery_user->save();
            return response()->json([
                'msg' => 'success',
                'code' => 200
            ]);
        }else{
            return response()->json([
                'msg' => '选择地址错误',
                'code' => 500
            ]);
        }
    }

    public function lotteryAddresses()
    {
        $lottery_id = request('lottery');
        $add_ids = LotteryUser::select('address_id')->where('lottery_id', $lottery_id)->where('address_id', '>', 0)->get()->toArray();
        if ($lottery_id && count($add_ids)>0) {
            return UserAddress::whereIn('id', $add_ids)->orderByDesc('created_at')->get();
        }
        return [];
    }

    public function decryptedData()
    {
        $session = request('session');
        $iv = request('iv');
        $encryptedData = request('encryptedData');
        $dataArray = $this->app->encryptor->decryptData($session, $iv, $encryptedData);
        // 新建或者更新用户信息
        $unionid = $dataArray['unionId'];
        if (is_null($unionid) || empty($unionid)) {
            return null;
        }
        $openid = $dataArray['openId'];
        $nickName = $dataArray['nickName'];
        $gender = $dataArray['gender'];
        $city = $dataArray['city'];
        $province = $dataArray['province'];
        $avatarUrl = $dataArray['avatarUrl'];
        Log::info('decryptedData unionid='.$unionid);
        $user = User::where('union_id', $unionid)->first();
        if ($user) {
            // 更新
            $user->avatar = $avatarUrl;
            $user->lottery_open_id = $openid;
            $user->lottery_session = $session;
            $user->save();
        }else{
            User::create([
                'lottery_open_id' => $openid,
                'lottery_session' => $session,
                'union_id' => $unionid,
                'nickname' => $nickName,
                'sex' => $gender,
                'province' => $province,
                'city' => $city,
                'avatar' => $avatarUrl
            ]);
        }
        return $dataArray;
    }

    public function join()
    {
        $lottery_id = request('lottery');
        $user_id = request('user');
        $form_id = request('formId');
        $union_id = request('unionid');
        if ($user_id == 'undefined') {
            $user = User::where('union_id', $union_id)->first();
            $user_id = $user->id;
        }
        LotteryUser::create([
            'lottery_id' => $lottery_id,
            'user_id' => $user_id,
            'form_id' => $form_id
        ]);
        $lottery = Lottery::with(['participants' => function($q) use($user_id) {
            $q->where('user_id', $user_id);
        }])->withCount('participants')->with('winners.user')->find($lottery_id);
//        $lottery->participants_count = LotteryUser::where('lottery_id', $lottery_id)->count();
//        $lottery->save();
        return $lottery;
    }

    public function send()
    {
        $lottery = Lottery::find(1);
        $lotteryUser = LotteryUser::where('user_id', 1)->first();
        $this->app->template_message->send([
            'touser' => 'op8uO4kVUG-kTJMgAA4oQCeAaFDA',
            'template_id' => 'RieZ5veYMn0BYH5fFx3HhpRxQt4gNt4ECtHppN-2Y3U',
            'page' => 'pages/result/result?id=1',
            'form_id' => $lotteryUser->form_id,
            'data' => [
                'keyword1' => $lottery->title,
                'keyword2' => $lottery->title,
                'keyword3' => '回流鱼抽奖机 参与的抽奖正在开奖，点击查看中奖名单'
            ],
        ]);
        return response()->json(['msg' => 'ok']);
    }

    public function upload(Request $request)
    {
        $disk = QiniuStorage::disk('qiniu');
        $file = $request->file('image');
        if(!$file->isValid()){
            Log::error('上传图片有问题：'.ini_get('upload_tmp_dir'));
            Log::error('上传图片有问题：'.$file->getErrorMessage());
            return response()->json([
                'msg' => '上传图片失败',
                'code' => 500
            ]);
        }
        $filename = $disk->put('lottery', $file);
        Log::info("转存到七牛的图片名称：".$filename);
//        return 'http://pic.ovoooo.com/'.$filename;
        return response()->json([
            'url' => 'http://pic.ovoooo.com/'.$filename
        ]);
    }
}
