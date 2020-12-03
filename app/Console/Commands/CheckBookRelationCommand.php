<?php

namespace App\Console\Commands;

use App\Jobs\CrawlingByWebPageSubjectId3;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckBookRelationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:relation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update book relations when hly does not has one';

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
        $ids = DB::select("select * from books_relation where status=0 limit 10");
        for ($i=0;$i<count($ids);$i++){
            $relation = $ids[$i];
            $count = array_filter(explode(',', $relation->subjectids));
            if ($count==0) {
                CrawlingByWebPageSubjectId3::dispatchNow($relation->subjectid);
            }
            DB::update('update books_relation set status=1 where subjectid=?', [$relation->subjectid]);
        }
    }
}
