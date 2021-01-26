<?php

namespace App\Console\Commands;

use GuzzleHttp\Client;
use App\Models\Follower;
use Illuminate\Console\Command;
use Symfony\Component\DomCrawler\Crawler;


class ParseAuthor extends Command
{
       /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parse:author';

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
     * @return int
     */
    public function handle()
    {
        self::getIndustryForZhiHu('https://www.zhihu.com/people/laopan233'); 
        $homePrefixForZhihu = 'https://www.zhihu.com/people/';
        // 获取知乎 Java 话题下的关注者用户信息
        $zhihu = self::getFollowersForZhiHuAndJava(20);

        // 持久化
        foreach($zhihu as $item) {
            if('「已注销」' == $item->name) {
                $this->error("该账号已被注销");
                continue;
            }
            if(empty($item->url_token)){
                $this->error($item->name . " url_token无法获取，因此无法获取个人主页链接");
                continue;
            }
            $follower = new Follower();
            $follower->home = $homePrefixForZhihu . $item->url_token; // 个人主页链接
            $follower->profile = $item->headline; // 个人简介
            $follower->article = $item->articles_count; // 文章数
            $follower->platform = Follower::PLATFORM_ZHIHU; // 平台类型
            $follower->keywords = 'Java'; // 关键词
            $follower->followers = $item->follower_count; // 被关注数
            $follower->industry = self::getIndustryForZhiHu($homePrefixForZhihu . $item->url_token); // 所在行业
            if($item->articles_count > 0) {
                // #ProfileHeader > div > div.ProfileHeader-wrapper > div > div.ProfileHeader-content > div.ProfileHeader-contentBody > div > div > div:nth-child(3) > div
                $follower->last_time =self::getLastTimeForZhiHu($homePrefixForZhihu . $item->url_token . '/posts'); // 最后一次发文时间
            }
            $follower->save();
            $this->info($homePrefixForZhihu . $item->url_token . ' 爬取成功');
        }
        
        return 0;
    }

    /**
     * 获取知乎用户最后一次发文时间
     * 
     * note: 这里的 $home 拼接了一个 /post
     */
    private static function getLastTimeForZhiHu($home)
    {
        // 获取用户信息中, 最后一篇文章的链接
        $postHtml = self::sendRequest($home);
        $postCrawler = new Crawler($postHtml);
        $lastPostUrl = $postCrawler->filter('#Profile-posts > div:nth-child(2) > div:nth-child(1) > div > h2 > a')->attr('href');

        $articleHtml = self::sendRequest('https:' . $lastPostUrl);
        $articleCrawler = new Crawler($articleHtml);
        return $articleCrawler->filter('#root > div > main > div > article > div.ContentItem-time')->text();
    }

    /**
     * 获取知乎个人主页中的 ‘所在行业’ 信息
     */
    private static function getIndustryForZhiHu($home)
    {
        $profileHtml = self::sendRequest($home);
        $profileCrawler = new Crawler($profileHtml);
        if($profileCrawler->filter('#ProfileHeader > div > div.ProfileHeader-wrapper > div > div.ProfileHeader-content > div.ProfileHeader-contentBody > div > div > div:nth-child(1)')->count() == 1) {
            return $profileCrawler->filter('#ProfileHeader > div > div.ProfileHeader-wrapper > div > div.ProfileHeader-content > div.ProfileHeader-contentBody > div > div > div:nth-child(1)')->text();
        }
        return;
    }

    /**
     * 发送请求
     */
    private static function sendRequest($targetUrl)
    {
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
    }

    /**
     * 获取知乎 Java 话题下的关注者用户信息
     */
    private static function getFollowersForZhiHuAndJava($offset)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_PROXYAUTH => CURLAUTH_BASIC,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_URL => 'https://www.zhihu.com/api/v4/topics/19561132/followers?offset=&limit=20&include=data%5B*%5D.gender%2Canswer_count%2Carticles_count%2Cfollower_count%2Cis_following%2Cis_followed',
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
              "User-Agent: Mozilla/5.0 (Linux; Android 8.0.0; Pixel 2 XL Build/OPD1.170816.004) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.25 Mobile Safari/537.36",
              'Cookie: _xsrf=aewHwuIwc7RNigoSKiTA3vkyf0z530sU; KLBRSID=b33d76655747159914ef8c32323d16fd|1611378782|1611378265'
            ),
        ));
        $response = curl_exec($curl);
        

        curl_close($curl);
        $json = json_decode($response);
        
        return $json->data;
    }
}
