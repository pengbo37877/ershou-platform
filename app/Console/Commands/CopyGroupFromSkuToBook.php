<?php

namespace App\Console\Commands;

use App\BookSku;
use Illuminate\Console\Command;

class CopyGroupFromSkuToBook extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'copy:sku_group';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Copy Sku Group to Book';

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
        $skus = BookSku::with('book')->get();
        foreach ($skus as $sku) {
            $book = $sku->book;
            if (is_null($book->group1)) {
                $groups = explode(',', $sku->groups);
                $i = 0;
                foreach ($groups as $group) {
                    if ($group != '新上架' && $i<3) {
                        $i++;
                        $book->update([
                            'group'.$i => $group
                        ]);
                    }
                }
            }
        }
    }
}
