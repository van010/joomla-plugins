<?php


namespace Joomla\Plugin\System\JAAIAssistant\Download;

use Joomla\CMS\Http\HttpFactory;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;
use Joomla\Registry\Registry;

defined('_JEXEC') or die('Restricted access');

class ImageDownload
{
    public static function download($url)
    {
        if (!$url) {
            throw new \Exception("Url empty");
        }

        $urlInfo = parse_url($url);

        if (empty($urlInfo['path'])) {
            throw new \Exception("Url not valid");
        }

        $info = pathinfo($urlInfo['path']);
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($info['extension'], $allowedExtensions)) {
            throw new \Exception("Extension is not allowed", 1);
        }

        $hash = md5($url);
        $fileName = $hash . '.' . $info['extension'];
        $imageFolder = JPATH_ROOT . '/images/jaaiassistant/images/';
        $imageFile = $imageFolder . $fileName;

        Folder::create(JPATH_ROOT . '/images/jaaiassistant/images/');

        if (is_file($imageFolder . $fileName)) {
            return self::getImageInfo($imageFile);
        }

        $options = new Registry();
        $options->set('userAgent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36');

        try {
            $response = HttpFactory::getHttp($options)->get($url);
        } catch (\RuntimeException $e) {
            throw new \RuntimeException('Download error.' . $e->getMessage(), $e->getCode(), $e);
        }

        if ($response->code != 200) {
            throw new \RuntimeException("Download error. Code: " . $response->code, 500);
        }

        $buffer = $response->body;
        $sizes = @getimagesizefromstring($buffer);

        if (!$sizes) {
            throw new \Exception("Image is not valid");
        }

        $allowedMime = ['image/jpeg', 'image/png'];

        if (empty($sizes['mime']) || !in_array($sizes['mime'], $allowedMime)) {
            throw new \Exception("Mine is not allowed", 1);
        }

        File::write($imageFile, $buffer);

        return self::getImageInfo($imageFile);
    }

    protected static function getImageInfo($file)
    {
        $info = pathinfo($file);
        $sizes = getimagesize($file);
        $result = new Registry();
        $result->set('fileType', $sizes['mime']);
        $result->set('width', $sizes[0]);
        $result->set('height', $sizes[1]);
        $result->set('extension', $info['extension']);

        $realPath = realpath($file);
        $imagesFolder = realpath(JPATH_ROOT . '/images/');
        $localPath = str_replace($imagesFolder, '', $realPath);
        $path = str_replace('\\', '/', 'local-images:' . $localPath);

        $result->set('path', $path);

        return $result->toObject();
    }
}
