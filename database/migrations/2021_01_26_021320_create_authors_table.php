<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAuthorsTable extends Migration
{
    /**
     * 作者信息
     *
     * @return void
     */
    public function up()
    {
        Schema::create('authors', function (Blueprint $table) {
            $table->id();
            $table->string('home')->comment('个人主页链接'); 
            $table->string('career')->nullable()->comment('职业经历');
            $table->string('profile')->nullable()->comment('个人简介');
            $table->string('keywords', 64)->comment('关键词');
            $table->string('last_time')->nullable()->comment('最后一次发文链接');
            $table->integer('read')->default(0)->comment('阅读数');
            $table->integer('fans')->default(0)->comment('粉丝数');
            $table->integer('thumb')->default(0)->comment('点赞数');
            $table->integer('article')->default(0)->comment('文章数');
            $table->integer('platform')->comment('平台类型 1:CSDN; 2:开源中国; 3:博客园');
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
        Schema::dropIfExists('authors');
    }
}
