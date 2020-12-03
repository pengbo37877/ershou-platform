<?php

namespace App\Console\Commands;

use App\Jobs\GetDzyBookInfoJob;
use Illuminate\Console\Command;

class GetDzyBookInfoCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:dzy {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get dzy book price info';

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
        $name = $this->argument('name');
        GetDzyBookInfoJob::dispatch($name);
    }
}
