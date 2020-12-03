<?php

namespace App\Console\Commands;

use App\DouList;
use App\Jobs\GetDouListJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class GetDouListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:list {id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get Dou List from douban.com';

    /**
     * Create a new command instance.
     *
     * @return void
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
        $id = $this->argument('id');
        if ($id == 'random') {
            $gap = 6;
            $doulist_id = Cache::get('doulist_id');
            if ($doulist_id) {
                for ($i = 0; $i < $gap; $i++) {
                    GetDouListJob::dispatch($doulist_id + $i)->delay(now()->addSecond(rand(0, 60)));
                }
                Cache::increment('doulist_id', $gap);
            } else {
                Cache::forever('doulist_id', 100000);
            }
        }else{
            GetDouListJob::dispatch($id);
        }
    }
}
