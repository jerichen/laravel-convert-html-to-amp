<?php
namespace Jerichen\Amp;

use DOMDocument;
use Imagick;
use Log;
use Exception;

class Article
{
    public function transferContent($content)
    {
        $result = collect();
        $result->put('content', $content);

        $amp_content = $this->getAmpContent($content);
        $result->put('amp_content', $amp_content);

        return $result;
    }

    private function getAmpContent($content)
    {
        try{
            $content = $this->filterTags($content);
            $dom = new DOMDocument();
            @$dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));

            $this->ampImage($dom);
            $this->ampIFrame($dom);

            $amp_content =  $dom->saveHTML($dom->documentElement);
            $amp_content = str_replace(['<html><body>', '</body></html>'], ['', ''], $amp_content);

            return $amp_content;

        }catch (Exception $e){
            Log::error("content somethings warning!!:" . $e);
        }
    }

    private function filterTags($content)
    {
        $content = preg_replace('/<(\/?font.*?)>/si', '', $content);
        $content = preg_replace('/style=\"[\s\S]*?\"/i', '', $content);
        $content = preg_replace('/\&nbsp\;/i', '', $content);
        return $content;
    }

    private function ampImage(DOMDocument &$dom)
    {
        $img_dom = $dom->getElementsByTagName('img');
        $length = $img_dom->length;
        for ($i = 0; $i < $length; $i++){
            $img = $img_dom->item(0);
            $img_path = trim($img->getAttribute('src'));
            $http_check = preg_match('/^https?:\/\//', $img_path);
            $img_path = (!$http_check ? 'http:' : '').$img_path;

            $tempFile = tmpfile();
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
        }
    }

    private function ampIFrame(DOMDocument &$dom)
    {
        $iframe_dom = $dom->getElementsByTagName('iframe');
        $length = $iframe_dom->length;
        for ($i = 0; $i < $length; $i++){
            $iframe = $iframe_dom->item(0);
            $path = $iframe->getAttribute('src');

            // default iframe or youtube iframe
            $pattern = '/^(https?:\/\/|http?:\/\/|\/\/)(?:www.)?(?:youtube.com|youtu.be)?(\/embed\/)([a-zA-Z0-9_-]+)/';
            preg_match($pattern, $path, $match);

            if($match){
                $this->youtubeFrame($dom, $iframe, $path, $match[3]);
            }else{
                $this->defaultIFrame($dom, $iframe, $path);
            }
        }

        return ($iframe_dom->length) ? true : false;
    }

    private function youtubeFrame(DOMDocument &$dom, $iframe, $path, $youtube_id)
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

    private function defaultIFrame(DOMDocument &$dom, $iframe, $path)
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
