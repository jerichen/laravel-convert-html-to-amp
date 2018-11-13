<?php
namespace Cw\Amp\app\models;

use Illuminate\Database\Eloquent\Model;

class AmpArticle extends Model
{
    protected $connection = 'mysql';
    protected $table = 'amp_articles';

    const STATUS_OPEN = 1;
    const STATUS_CLOSE = 2;

    protected $attributes = [
        'status' => self::STATUS_CLOSE
    ];

    protected $fillable = [
        'article_id',
        'content',
        'status',
        'online_time',
        'offline_time',
    ];

    public static function statusAry()
    {
        return [
            self::STATUS_OPEN => "上線",
            self::STATUS_CLOSE => "下線",
        ];
    }
}
