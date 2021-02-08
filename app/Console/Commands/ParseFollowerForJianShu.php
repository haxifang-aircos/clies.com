<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use GuzzleHttp\Client;
use App\Models\Follower;
use Illuminate\Console\Command;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;

class ParseFollowerForJianShu extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parse:jianshuFollower {collection} {--cycles=} {--sortId=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '获取简书用户信息';

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
        $cycles = $this->option('cycles'); // 抓取次数
        $sortId = $this->option('sortId'); // 起始排序ID
        $collection = $this->argument('collection'); // 合集名称
        $homePrefixForJianShu = 'https://www.jianshu.com/u/'; // 个人主页固定前缀

        for ($i = 0; $i <= $cycles; $i++) {
            // 获取指定合集下的关注者
            $followers = $this->getFollowers($sortId, $collection);

            for ($j = 0; $j < count($followers); $j++) {

                $follower = new Follower();
                $follower = $this->getHomeInfo($follower, $homePrefixForJianShu . $followers[$j]->slug);
                if(empty($follower)) {
                    continue;
                }

                $follower->keywords = $collection; // 关键词
                $follower->home = $homePrefixForJianShu . $followers[$j]->slug; // 个人主页链接
                $follower->platform = Follower::PLATFORM_JIANSHU; // 平台类型

                // 下一个请求的 max_sort_id, 这里的 19 代表这次请求返回的关注者数据中, 最后一个用户的数据
                if ($j == 19) {
                    $sortId = $followers[count($followers) - 1]->like_id;
                }
                $follower->save();
                $this->info('max_sort_id: ' . $sortId . ', 已收录: ' . $homePrefixForJianShu . $followers[$j]->slug . ', ' . $collection);
                sleep(3);
            }
        }
        return 0;
    }

    public function getFollowers($sortId, $collection)
    {
        $collectionUrl = null;

        switch ($collection) {
            case 'Java':
                $collectionUrl = 'https://www.jianshu.com/collection/2099/subscribers?max_sort_id=' . $sortId;
                break;
            case '前端':
                $collectionUrl = 'https://www.jianshu.com/collection/1084/subscribers?max_sort_id=' . $sortId;
                break;
            case '后端技术':
                $collectionUrl = '';
                break;
            case 'ios':
                $collectionUrl = '';
                break;
            case 'vscode':
                $collectionUrl = '';
                break;
            case '人工智能':
                $collectionUrl = '';
                break;
            case 'android':
                $collectionUrl = '';
                break;
            case 'github':
                $collectionUrl = '';
                break;
            default:
                throw new \Exception('话题未收录');

        }

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_PROXYAUTH => CURLAUTH_BASIC,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_URL => $collectionUrl,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                "Mozilla/5.0 (iPhone; CPU iPhone OS 6_0 like Mac OS X) AppleWebKit/536.26 (KHTML, like Gecko) Version/6.0 Mobile/10A5376e Safari/8536.25",
                'Cookie: _xsrf=aewHwuIwc7RNigoSKiTA3vkyf0z530sU; KLBRSID=b33d76655747159914ef8c32323d16fd|1611378782|1611378265',
            ),
        ));
        $response = curl_exec($curl);

        curl_close($curl);

        return json_decode($response);
    }

    /**
     * 获取用户个人主页信息
     *
     * note: 获取粉丝、收获喜欢、发布文章数、最后一次发文时间
     */
    public function getHomeInfo(Follower $follower, $homeUrl)
    {
        $lastTime = null;
        $homeHTML = $this->sendRequest($homeUrl);
        // 校验请求是否成功
        if(empty($homeHTML)) {
            return null;
        }

        $homeCrawler = new Crawler($homeHTML);

        // 文章数
        $follower->article = $homeCrawler->filter('body > div.container.person > div > div.col-xs-16.main > div.main-top > div.info > ul > li:nth-child(3) > div > a > p')->text();
        // 被关注数
        $follower->fans = $homeCrawler->filter('body > div.container.person > div > div.col-xs-16.main > div.main-top > div.info > ul > li:nth-child(1) > div > a > p')->text();
        // 收获喜欢
        $follower->thumb = $homeCrawler->filter('body > div.container.person > div > div.col-xs-16.main > div.main-top > div.info > ul > li:nth-child(5) > div > p')->text(); 

        // 最后一次发文时间
        if($follower->article != 0) {
            // 文章类型为置顶文章, 那么需要取第二篇文章
            if($homeCrawler->filterXPath('//*[@id="list-container"]/ul/li/div')->attr('class') != 'content  ') {
                // 判断文章的数量, 如果仅存在 1 篇置顶文章, 那么仍然取第一篇文章
                if($follower->article == 1) {
                    $lastTime = $homeCrawler->filterXPath('//*[@id="list-container"]/ul/li/div/div/span[2]')->attr('data-shared-at');
                } else {
                    $lastTime = $homeCrawler->filterXPath('//*[@id="list-container"]/ul/li[2]/div/div/span[2]')->attr('data-shared-at');
                }
            } else {
                $lastTime = $homeCrawler->filterXPath('//*[@id="list-container"]/ul/li/div/div/span[2]')->attr('data-shared-at');
            }
            
            // 格式化时间
            $follower->last_time = Carbon::parse($lastTime)->format('Y-m-d H:i:s');

        }
        return $follower;
    }

    /**
     * 发送请求
     */
    private function sendRequest($targetUrl, $retry = 0)
    {
        // 仅重试三次
        if ($retry == 3) {
            $this->error($targetUrl . ' 该简书用户账号已被停用');
            return null;
        }
        try {
            $client = new Client([
                'defaults' => [
                    'config' => [
                        'curl' => [
                            CURLOPT_SSLVERSION => CURL_SSLVERSION_SSLv3,
                        ],
                    ],
                ],
            ]);

            $response = $client->request('GET', $targetUrl);
            return $response->getBody()->getContents();
        } catch (RequestException $e) {
            $retry++;
            $this->error($targetUrl . ' 请求失败, 第' . $retry . '次重试');
            $this->sendRequest($targetUrl, $retry++);
        } catch (ConnectException $ce) {
            $retry++;
            $this->error($targetUrl . ' 连接失败, 第' . $retry . '次重试');
            $this->sendRequest($targetUrl, $retry++);
        }

    }
}
