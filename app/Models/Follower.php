<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Follower extends Model
{
    use HasFactory;

    // 平台类型
    public const PLATFORM_ZHIHU = 1; // 知乎
    public const PLATFORM_JIANSHU = 2; // 简书
}
