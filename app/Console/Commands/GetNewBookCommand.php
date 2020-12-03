<?php

namespace App\Console\Commands;

use App\Jobs\GetNewBookJob;
use Illuminate\Console\Command;

class GetNewBookCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:new';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get new Book';

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
        for ($i=0;$i<180;$i++) {
            GetNewBookJob::dispatch(now()->addSecond($i%3));
        }
    }
}
