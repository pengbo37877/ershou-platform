<?php

namespace App\Console\Commands;

use App\Jobs\PendingBookFetch;
use Illuminate\Console\Command;

class PendingBookFetchCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pending:fetch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetching Pending book info from douban';

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
        for ($i=0;$i<3;$i++){
            PendingBookFetch::dispatch()->delay(now()->addSecond(20));
        }
    }
}
