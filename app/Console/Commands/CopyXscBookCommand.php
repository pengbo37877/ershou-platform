<?php

namespace App\Console\Commands;

use App\Jobs\CopyXscBooks;
use Illuminate\Console\Command;

class CopyXscBookCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'copy:xsc';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'copy book data from xsc db';

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
        for ($i=0;$i<10;$i++){
            CopyXscBooks::dispatch()->delay(now()->addSecond($i*6));
        }
    }
}
