<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:data {table}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'fix dirty data by table';

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
     * @return int
     */
    public function handle()
    {
        if ($table = $this->argument('table')) {
            return $this->$table();
        }
        return $this->error("必须提供你要修复数据的table");
    }

    /**
     * 删除重复数据
     */
    public function deleteData() {
        // $result = DB::select('SELECT min(id) FROM followers GROUP BY home HAVING count(home) > 1');
        $ids = DB::select('SELECT id FROM followers WHERE home in( SELECT home FROM followers GROUP BY home HAVING count(home) > 1) AND id NOT in( SELECT min(id) FROM followers GROUP BY home HAVING count(home) > 1)');

        foreach ($ids as $id) {
           
            DB::table('followers')->where('id', $id->id)->delete();
            $this->info('已删除 id: '. $id->id .' 😺');
        }

    }
}
