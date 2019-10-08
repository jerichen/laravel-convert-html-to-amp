<?php
namespace Jerichen\Amp\app\models;

use Illuminate\Database\Eloquent\Model;
use Jerichen\Amp\app\models\AmpArticle;

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
