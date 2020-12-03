<?php

namespace App\Console\Commands;

use App\Book;
use App\CartItem;
use App\Jobs\NotifyHowManyBooksOnSaleJob;
use App\Jobs\NotifyUserBookOnSaleJob;
use App\ReminderItem;
use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class NotifyUserBookOnSale extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notify:user {user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify User Book On Sale';

    /**
     * Create a new command instance.
     *
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $user_id = $this->argument('user');
        if (is_numeric($user_id)) {
            NotifyHowManyBooksOnSaleJob::dispatch(User::find($user_id));
        }else if ($user_id == 'random'){
            // 给所有用户发消息
            if (now()->hour==9 || now()->hour==18) {
                $skip = Cache::get('notify_how_many_skip', 0);
                if ($skip%10000 != 0) return;
                $users = User::select('id', 'mp_open_id')->skip($skip)->take(10000)->get();
                for ($i=0;$i<count($users);$i++) {
                    if ($i%2==0) {
                        NotifyHowManyBooksOnSaleJob::dispatch($users[$i]);
                    }else{
                        NotifyHowManyBooksOnSaleJob::dispatch($users[$i])->onQueue('high');
                    }
                }
                $skip = $skip + count($users);
                if (count($users) < 10000) {
                    Cache::put('notify_how_many_skip', $skip, 12 * 60);
                } else {
                    Cache::put('notify_how_many_skip', $skip, 5);
                }
            }
        }
    }
}
