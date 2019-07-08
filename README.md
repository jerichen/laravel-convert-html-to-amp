## About convert-html-to-amp
#### 將文章內容由Html轉成符合amp內容。包含 : 
- image
- iframe
- youtube-iframe
- instagram-iframe
- facebook-ifram

#### Packages 使用方式
```bash
composer require jerichen/laravel-convert-html-to-amp
```
or
```bash
composer config repositories.laravel-convert-html-to-amp vcs git@github.com:jerichen/laravel-convert-html-to-amp
```

- publish migrations (原本有 Article 就只做這個)
```bash
php artisan vendor:publish --tag="amp-migrations" 
```

- 如果沒有 Article 做以下的動作(建立Article範例)
```bash
php artisan vendor:publish --tag="migrations" 
php artisan vendor:publish --tag="seeds" 
php artisan vendor:publish --tag="models" 
php artisan db:seed --class=ArticleTableSeeder
```

- ArticleController 範例檔
```php
<?php
namespace App\Http\Controllers;

use Cw\Amp\AmpHelper;
use App\Models\Entities\Article;
use Cw\Amp\app\models\AmpArticle;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    protected $article;
    protected $article_index_id_key = 'id';
    protected $article_content_key = 'content';

    public function __construct()
    {
        $this->article = new Article();
    }
    
    private function saveAmpArticle($data = [])
    {
        $amp_article = AmpArticle::updateOrCreate([
            'article_id' => $data['article_id'],
        ], [
            'content' => $data['amp_content'],
            'status' => AmpArticle::STATUS_OPEN,
        ]);
        return $amp_article;
    }

    public function amp()
    {
        $amp = new AmpHelper();
        $amp->setArticle($this->article);
        $amp->setArticleIdKey($this->article_index_id_key);
        $amp->setArticleContentKey($this->article_content_key);

        // push article_id
        $result = $amp->transferContent('{文章ID}');
        $this->saveAmpArticle($result);
    }
}
```

- 回傳值
```php
Collection 
[
    'article_id' => '{文章ID}',
    'amp_content' => '{文章內容}'
]
```


