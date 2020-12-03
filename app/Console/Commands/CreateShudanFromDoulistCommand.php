<?php

namespace App\Console\Commands;

use App\Book;
use App\DouList;
use App\Shudan;
use App\ShudanComment;
use Illuminate\Console\Command;

class CreateShudanFromDoulistCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:shudan {list}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create shudan from dou list';

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
        $list_id = $this->argument('list');
        if ($list_id == 'random') {
            $dou = DouList::where('generated', 0)->where('recommend_count', '>', 100)->first();
            $dou->generated = 1;
            $dou->save();
            $shudan = Shudan::where('doulist_id', $dou->doulist_id)->first();
            if ($shudan) {
                return;
            }
            $shudan = Shudan::create([
                'title' => $dou->name,
                'doulist_id' => $dou->doulist_id,
                'desc' => $dou->desc,
                'open' => 0,
                'cover' => 'http://pic.ovoooo.com/white.png',
                'color' => '#222222'
            ]);
            $coll = collect(explode(',', $dou->subjectids));
            $coll->each(function ($subjectid) use ($shudan) {
                $book = Book::where('subjectid', $subjectid)->first();
                if ($book) {
                    ShudanComment::create([
                        'shudan_id' => $shudan->id,
                        'comment_id' => 0,
                        'book_id' => $book->id
                    ]);
                }
            });
        } else {
            $shudan = Shudan::where('doulist_id', $list_id)->first();
            if ($shudan) {
                return;
            }
            if (is_numeric($list_id)) {
                $list = DouList::find($list_id);
                if ($list) {
                    $shudan = Shudan::create([
                        'title' => $list->name,
                        'doulist_id' => $list->doulist_id,
                        'desc' => $list->desc,
                        'open' => 0,
                        'cover' => 'http://pic.ovoooo.com/white.png',
                        'color' => '#555555'
                    ]);
                    $coll = collect(explode(',', $list->subjectids));
                    $coll->each(function ($subjectid) use ($shudan) {
                        $book = Book::where('subjectid', $subjectid)->first();
                        if ($book && strpos($book->isbn, '9787') === 0) {
                            ShudanComment::create([
                                'shudan_id' => $shudan->id,
                                'comment_id' => 0,
                                'book_id' => $book->id
                            ]);
                        }
                    });
                }
            }
        }
    }
}
