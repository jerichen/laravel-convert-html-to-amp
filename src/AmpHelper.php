<?php
namespace Cw\Amp;

use Cw\Amp\app\models\Article;
use DOMDocument;
use Imagick;
use Log;
use Exception;

class AmpHelper
{
    protected $article;
    protected $article_index_id_key;
    protected $article_content_key;

    public function __construct()
    {
        $this->setArticle();
        $this->setArticleIdKey();
        $this->setArticleContentKey();
    }

    public function setArticle($article = Article::class)
    {
        $this->article = $article;
    }

    public function setArticleIdKey($article_id_key = 'id')
    {
        $this->article_index_id_key = $article_id_key;
    }

    public function setArticleContentKey($article_content_key = 'content')
    {
        $this->article_content_key = $article_content_key;
    }

    public function transferContent($article_id)
    {
        $result = collect();
        $result->put('article_id', $article_id);

        $article = $this->getArticleContent($article_id);
        $result->put('amp_content', $article);

        return $result;
    }

    private function getArticleContent($article_id)
    {
        try{
            $content = $this->article->where($this->article_index_id_key, $article_id)
                ->pluck($this->article_content_key)
                ->first();

            if($content){
                $content = self::filterTags($content);
                $dom = new DOMDocument();
                @$dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));

                self::ampExampleUrl($dom, $content);
                self::ampImage($dom);
                self::ampIFrame($dom);

                $amp_content =  $dom->saveHTML($dom->documentElement);
                $amp_content = str_replace(['<html><body>', '</body></html>'], ['', ''], $amp_content);

                return $amp_content;

            }else{
                return $content;
            }

        }catch (Exception $e){
            Log::error("article_id:{$article_id} . somethings warning!!:" . $e);
        }
    }

    private static function filterTags($content)
    {
        $content = preg_replace('/<(\/?font.*?)>/si', '', $content);
        $content = preg_replace('/style=\"[\s\S]*?\"/i', '', $content);
        $content = preg_replace('/\&nbsp\;/i', '', $content);
        return $content;
    }

    private static function ampExampleUrl(DOMDocument &$dom, $content)
    {
        $url_dom = $dom->getElementsByTagName('a');

        foreach ($url_dom as $node) {
            $url = trim($node->getAttribute('href'));

            $pattern = '/^(https?:\/\/|http?:\/\/|\/\/)(?:www.example.com.tw)/';
            $http_check = preg_match($pattern, $url);

            if($http_check){
                $query = parse_url($url, PHP_URL_QUERY);
                if($query){
                    $new_url = $url . '&from=example-article';
                }else{
                    $new_url = $url . '?from=example-article';
                }
                $node->setAttribute('href', $new_url);
            }
        }
    }

    private static function ampImage(DOMDocument &$dom)
    {
        $img_dom = $dom->getElementsByTagName('img');
        $length = $img_dom->length;
        for ($i = 0; $i < $length; $i++){
            $img = $img_dom->item(0);
            $img_path = trim($img->getAttribute('src'));
            $http_check = preg_match('/^https?:\/\//', $img_path);
            $img_path = (!$http_check ? 'http:' : '').$img_path;

            $tempFile = tmpfile();
            $header_array = get_headers($img_path,1);
            if(preg_match('/200/', $header_array[0])){
                $content = file_get_contents($img_path);
                fwrite($tempFile, $content);
                fseek($tempFile, 0);
                $getMeta = stream_get_meta_data($tempFile);
                $uri = $getMeta['uri'];
                $image = new Imagick($uri);
                $image_width = $image->getImageWidth();
                $image_height = $image->getImageHeight();
                fclose($tempFile);

                $amp_img = $dom->createElement('amp-img');
                $amp_img->setAttribute('src', $img_path);
                $amp_img->setAttribute('width',$image_width);
                $amp_img->setAttribute('height', $image_height);
                $amp_img->setAttribute('layout', 'responsive');
                $img->parentNode->replaceChild($amp_img, $img);
            }else{
                $img->parentNode->removeChild($img);
            }
        }
    }

    private static function ampIFrame(DOMDocument &$dom)
    {
        $iframe_dom = $dom->getElementsByTagName('iframe');
        $length = $iframe_dom->length;
        for ($i = 0; $i < $length; $i++) {
            $iframe = $iframe_dom->item(0);
            $path = trim($iframe->getAttribute('src'));

            $youtube_pattern = '/^(https?:\/\/|http?:\/\/|\/\/)(?:www.)?(?:youtube.com|youtu.be)?(\/embed\/)([a-zA-Z0-9_\/]+)/';;
            preg_match($youtube_pattern, $path, $youtube_match);

            $instagram_pattern = '/^(https?:\/\/|http?:\/\/|\/\/)(?:www.)?(?:instagram.com\/p\/)?([a-zA-Z0-9_-]+)?(\/embed)/';
            preg_match($instagram_pattern, $path, $instagram_match);

            $twitter_pattern = '/^(https?:\/\/|http?:\/\/|\/)(twitframe.com\/show\?url\=)(https?:\/\/|http?:\/\/|\/)(twitter.com\/)([a-zA-Z0-9_-]+\/status\/)([a-zA-Z0-9_-]+)/';
            preg_match($twitter_pattern, $path, $twitter_match);

            $facebook_pattern = '/^(https?:\/\/|http?:\/\/|\/)(www\.facebook\.com\/plugins\/video\.php\?href\=)([^ ]+)/';;
            preg_match($facebook_pattern, $path, $facebook_match);

            if ($youtube_match) {
                self::youtubeIFrame($dom, $iframe, $path, $youtube_match[3]);
            } else if ($instagram_match) {
                self::instagramIFrame($dom, $iframe, $path, $instagram_match[2]);
            } else if ($twitter_match) {
                self::twitterIFrame($dom, $iframe, $path, $twitter_match[6]);
            } else if ($facebook_match) {
                self::facebookIFrame($dom, $iframe, $path, urldecode($facebook_match[3]));
            } else {
                self::defaultIFrame($dom, $iframe, $path);
            }
        }
        return ($iframe_dom->length) ? true : false;
    }

    private static function youtubeIFrame(DOMDocument &$dom, $iframe, $path, $youtube_id)
    {
        $path = preg_replace('/^\/\//', 'https://', $path);
        $amp_iframe = $dom->createElement('amp-youtube');
        $amp_iframe->setAttribute('width', '16');
        $amp_iframe->setAttribute('height', '9');
        $amp_iframe->setAttribute('data-videoid', $youtube_id);
        $amp_iframe->setAttribute('layout', 'responsive');

        $clone = $amp_iframe->cloneNode();
        $iframe->parentNode->replaceChild($clone, $iframe);
    }

    private static function instagramIFrame(DOMDocument &$dom, $iframe, $path, $instagram_id)
    {
        $path = preg_replace('/^\/\//', 'https://', $path);
        $amp_iframe = $dom->createElement('amp-instagram');
        $amp_iframe->setAttribute('width', '1');
        $amp_iframe->setAttribute('height', '1');
        $amp_iframe->setAttribute('data-shortcode', $instagram_id);
        $amp_iframe->setAttribute('layout', 'responsive');

        $clone = $amp_iframe->cloneNode();
        $iframe->parentNode->replaceChild($clone, $iframe);
    }

    private static function twitterIFrame(DOMDocument &$dom, $iframe, $path, $twitter_id)
    {
        $path = preg_replace('/^\/\//', 'https://', $path);
        $amp_iframe = $dom->createElement('amp-twitter');
        $amp_iframe->setAttribute('width', '375');
        $amp_iframe->setAttribute('height', '472');
        $amp_iframe->setAttribute('data-tweetid', $twitter_id);
        $amp_iframe->setAttribute('layout', 'responsive');

        $clone = $amp_iframe->cloneNode();
        $iframe->parentNode->replaceChild($clone, $iframe);
    }

    private static function facebookIFrame(DOMDocument &$dom, $iframe, $path, $facebook_url)
    {
        $amp_iframe = $dom->createElement('amp-facebook');
        $amp_iframe->setAttribute('width', '1');
        $amp_iframe->setAttribute('height', '1');
        $amp_iframe->setAttribute('data-href', $facebook_url);
        $amp_iframe->setAttribute('layout', 'responsive');

        $clone = $amp_iframe->cloneNode();
        $iframe->parentNode->replaceChild($clone, $iframe);
    }

    private static function defaultIFrame(DOMDocument &$dom, $iframe, $path)
    {
        $path = preg_replace('/^\/\//', 'https://', $path);
        $amp_iframe = $dom->createElement('amp-iframe');
        $amp_iframe->setAttribute('src', $path);
        $amp_iframe->setAttribute('frameborder', $iframe->getAttribute('frameborder'));
        $amp_iframe->setAttribute('sandbox', 'allow-scripts allow-same-origin allow-popups allow-popups-to-escape-sandbox');
        $amp_iframe->setAttribute('width', $iframe->getAttribute('width'));
        $amp_iframe->setAttribute('height', $iframe->getAttribute('height'));
        $amp_iframe->setAttribute('layout', 'responsive');

        $clone = $amp_iframe->cloneNode();
        $iframe->parentNode->replaceChild($clone, $iframe);
    }
}
