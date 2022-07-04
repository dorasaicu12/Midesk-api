<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CreateGroup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:group {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $deletedFormat = '';
        $fname = $this->argument('name');
        File::copyDirectory(app_path('Http/Controllers/v3'), app_path('Http/Controllers/'.$fname));
        File::copyDirectory(app_path('Http/Requests/v3'), app_path('Http/Requests/'.$fname));

        foreach(glob(app_path('Http/Controllers/'.$fname.'/*.php')) as $file) {
            $str = file_get_contents($file);
            $str = str_replace('v3', $fname,$str);
            file_put_contents($file, $str);
        }

        foreach(glob(app_path('Http/Requests/'.$fname.'/*.php')) as $file) {
            $str = file_get_contents($file);
            $str = str_replace('v3', $fname,$str);
            file_put_contents($file, $str);
        }
    }
}
