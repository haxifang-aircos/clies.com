<?php

namespace App\Console\Commands;

use App\Models\Follower;
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
     * 数据合并
     */
    public function dataMerge()
    {
        echo "开始合并话题关注者信息 🚧";
        DB::connection('clues2')->table('followers')->chunkById(1000, function ($followers) {
            foreach ($followers as $follower) {
                DB::connection('mysql')->table('followers')->insert([
                    // 个人主页链接
                    'home' => $follower->home,
                    // 职业经历
                    'career' => $follower->career,
                    // 个人简介
                    'profile' => $follower->profile,
                    // 关键词
                    'keywords' => $follower->keywords,
                    // 所在行业
                    'industry' => $follower->industry,
                    // 最后一次发文时间
                    'last_time' => $follower->last_time,
                    // 文章数
                    'article' => $follower->article,
                    // 平台类型
                    'platform' => Follower::PLATFORM_ZHIHU,
                    // 关注者数
                    'fans' => $follower->followers,
                    // 创建时间
                    'created_at' => now(),
                    // 更新时间
                    'updated_at' => now(),
                ]);
                $this->info('home: ' . $follower->home . ' 已同步');
            }
        });
    }

    /**
     * 删除重复数据
     */
    public function deleteData()
    {
        // $result = DB::select('SELECT min(id) FROM followers GROUP BY home HAVING count(home) > 1');
        $ids = DB::select('SELECT id FROM followers WHERE home in( SELECT home FROM followers GROUP BY home HAVING count(home) > 1) AND id NOT in( SELECT min(id) FROM followers GROUP BY home HAVING count(home) > 1)');

        foreach ($ids as $id) {

            DB::table('followers')->where('id', $id->id)->delete();
            $this->info('已删除 id: ' . $id->id . ' 😺');
        }

    }
}
