<?php

namespace App\Console\Commands;

use App\Book;
use App\BookSku;
use App\Jobs\DownloadCoverImageBySubjectId;
use App\Jobs\DownloadCoverImageBySubjectId2;
use Illuminate\Console\Command;

class DownloadCoverImageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'download:cover';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download Cover Image where Cover Replace is null';

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
//        $gif = 'https://img3.doubanio.com/f/shire/5522dd1f5b742d1e1394a17f44d590646b63871d/pics/book-default-lpic.gif';
        $books = Book::whereNull('cover_replace')->orderByDesc('updated_at')
//            ->where('cover_image','<>',$gif)
            ->where('user_add', '<>', 3)->take(200)->get();
        for ($i = 0; $i < count($books); $i++) {
            $book = $books->get($i);
            $book->user_add = 3;
            $book->save();
            DownloadCoverImageBySubjectId::dispatch($book->subjectid)->delay(now()->addSecond($i))->onQueue('low');
        }

        $epubiBooks = Book::where('cover_replace', 'like', 'http://pic.epubi.cn%')
            ->where('user_add', '<>', 6)->orderByDesc('updated_at')->take(60)->get();
        for ($j = 0; $j < count($epubiBooks); $j++) {
            $book = $epubiBooks->get($j);
            $book->user_add = 6;
            $book->save();
            DownloadCoverImageBySubjectId2::dispatch($book->subjectid)->delay(now()->addSecond($j));;
        }
    }
}
