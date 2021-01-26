<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFollowersTable extends Migration
{
    /**
     * 关注者信息
     *
     * @return void
     */
    public function up()
    {
        Schema::create('followers', function (Blueprint $table) {
            $table->id();
            $table->string('home')->comment('个人主页链接'); 
            $table->string('career')->nullable()->comment('职业经历');
            $table->string('profile')->nullable()->comment('个人简介');
            $table->string('keywords', 64)->comment('关键词');
            $table->string('industry', 64)->nullable()->comment('所在行业');
            $table->string('last_time')->nullable()->comment('最后一次发文链接');
            $table->integer('article')->default(0)->comment('文章数');
            $table->integer('platform')->comment('平台类型 1:知乎; 2:简书');
            $table->integer('followers')->default(0)->comment('关注者数');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('followers');
    }
}
