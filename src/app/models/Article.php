<?php
namespace Cw\Amp\app\models;

use Illuminate\Database\Eloquent\Model;
use Cw\Amp\app\models\AmpArticle;

class Article extends Model
{
    protected $connection = 'mysql';
    protected $table = 'articles';

    protected $fillable = [
        'content',
    ];

    public function amp_article()
    {
        return $this->hasOne(AmpArticle::class, 'article_id', 'id');
    }
}
