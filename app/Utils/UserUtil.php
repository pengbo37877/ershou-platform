<?php

namespace App\Utils;


use App\Book;
use App\CartItem;
use App\Order;
use App\OrderItem;
use App\ReminderItem;
use App\User;
use App\UserBanBook;
use App\UserRecommend;
use App\UserSearchHistory;
use App\ViewBook;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserUtil {
    public static function updateRecommends($user_id)
    {
        // 搜索记录 SS，时效性
        $search_ids = UserSearchHistory::select('book_ids', 'subjectids')->where('user_id', $user_id)->orderByDesc('updated_at')
            ->where('updated_at', '>', now()->subDays(7))
            ->take(2)->get();
        $search_book_ids = [];
        foreach ($search_ids as $us) {
            $search_book_ids = array_merge($search_book_ids, explode(',', $us->book_ids));
        }
        Log::info('$search_book_ids count='.count($search_book_ids));

        // 已购书籍 G
        $buy_book_ids = OrderItem::select('book_id')->whereHas('order', function($q) use ($user_id){
            $q->where('user_id', $user_id)->where('type', Order::ORDER_TYPE_SALE);
        })->orderByDesc('id')->take(100)->get()->pluck('book_id')->toArray();
        Log::info('$buy_book_ids count='.count($buy_book_ids));

        // 购物车书籍 W
        $cart_book_ids = CartItem::select('book_id')->where('user_id', $user_id)->get()->pluck('book_id')->toArray();
        Log::info('$cart_book_ids count='.count($cart_book_ids));

        // 到货提醒 D
        $reminder_book_ids = ReminderItem::select('book_id')->where('user_id', $user_id)
            ->orderByDesc('id')->take(200)->get()->pluck('book_id')->toArray();
        Log::info('$reminder_book_ids count='.count($reminder_book_ids));

        // 浏览数据 L (浏览超过10秒钟)
        $view_book_ids = ViewBook::select('book_id')->where('user_id', $user_id)->where('second', '>=', '5')
            ->orderByDesc('id')->take(200)->get()->pluck('book_id')->toArray();
        Log::info('$view_book_ids count='.count($view_book_ids));

        // 标签Tag
        $user = User::find($user_id);
        $tags_book_ids = [];
        if ($user) {
            $user_tags = $user->tags()->get()->reverse()->take(3)->pluck('name')->toArray();
            if (count($user_tags)>0) {
                $tags_book_ids = Book::select('id')->whereIn('group1', $user_tags)
                    ->where('sale_sku_count', '>', 0)->orderByDesc('reminder_count')->take(100)->get()->pluck('id')->toArray();
            }
        }
        Log::info('$tags_book_ids count='.count($tags_book_ids));
        // 用户兴趣集合 B=SS ∪ G ∪ W ∪ D ∪ L ∪ Tag
        $B = array_merge($search_book_ids, $buy_book_ids, $cart_book_ids, $reminder_book_ids, $view_book_ids, $tags_book_ids);
        Log::info('B count='.count($B));

        // 豆瓣的推荐集合 T
        $book_subjectids = Book::select('subjectid')->whereIn('id', $B)
            ->orderByRaw(DB::raw('FIND_IN_SET(subjectid, "' . implode(",", $B) . '"' . ")"))
            ->get()->pluck('subjectid')->toArray();
        $douban_subjectids = DB::table('books_relation')->whereIn('subjectid', $book_subjectids)
            ->orderByRaw(DB::raw('FIND_IN_SET(subjectid, "' . implode(",", $book_subjectids) . '"' . ")"))
            ->select('subjectids')->get()->pluck('subjectids')->toArray();
        $T=[];
        foreach ($douban_subjectids as $subjectids) {
            $ids = explode(',', $subjectids);
            foreach ($ids as $id) {
                if (!empty($id)) {
                    array_push($T, $id);
                }
            }
        }
        if (count($T)<100) {
            $other_douban_subjectids = DB::table('books_relation')->whereIn('subjectid', $T)
                ->orderByRaw(DB::raw('FIND_IN_SET(subjectid, "' . implode(",", $T) . '"' . ")"))
                ->select('subjectids')->get()->pluck('subjectids')->toArray();
            foreach ($other_douban_subjectids as $subjectids) {
                $ids = explode(',', $subjectids);
                foreach ($ids as $id) {
                    if (!empty($id)) {
                        array_push($T, $id);
                    }
                }
            }
        }
        Log::info('T count='.count($T));

        // 回流鱼在售集合 H，缓存一分钟
        $H = Cache::remember('hly_sale_books', 10, function () {
            return Book::select('subjectid')->where('sale_sku_count', '>', 0)->get()->pluck('subjectid')->toArray();
        });

        // 用户反馈集合 F
        $F = UserBanBook::select('subjectid')->where('user_id', $user_id)->get()->pluck('subjectid')->toArray();

        $s = [];
        foreach ($search_ids as $si) {
            $s = array_merge($s , explode(',', $si->subjectids));
        }
        $a = array_merge($s, $T);
        // 这块的计算很耗时，其实就是一个排序过程
        $H = array_diff($H, $F);
        $r = array_intersect($a, $H);
//        $d = array_diff($H, $r);
//        $recommend_set = array_merge($r, $d);

        $user_r = UserRecommend::where('user_id', $user_id)->first();
        if ($user_r) {
            $user_r->update([
                'subjectids' => join(',', $r)
            ]);
        }else{
            UserRecommend::create([
                'user_id' => $user_id,
                'subjectids' => join(',', $r)
            ]);
        }
    }
}