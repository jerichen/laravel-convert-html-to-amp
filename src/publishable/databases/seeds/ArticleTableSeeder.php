<?php

use Illuminate\Database\Seeder;
use Cw\Amp\app\models\Article;

class ArticleTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        for ($i = 1; $i <= 50; $i++) {
            $aricle = new Article();
            $aricle->content = "<p>第{$i}篇文章內容</p>" . "<iframe width='420' height='315' src='https://www.youtube.com/embed/tgbNymZ7vqY'></iframe>";
            $aricle->save();
        }
    }
}
