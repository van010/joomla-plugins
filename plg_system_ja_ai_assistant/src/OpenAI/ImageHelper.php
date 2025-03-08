<?php


namespace Joomla\Plugin\System\JAAIAssistant\OpenAI;

use Joomla\CMS\Factory;
use Joomla\CMS\Http\HttpFactory;
use Joomla\Plugin\System\JAAIAssistant\OpenAI\Trait\ApiTrait;
use Joomla\Registry\Registry;

defined('_JEXEC') or die('Restricted access');

class ImageHelper
{
    use ApiTrait;

    protected static $endpoint = 'https://api.openai.com/v1/images/generations';

    public static function doTask()
    {
        $input = Factory::getApplication()->input;
        $promt = trim($input->getString('content', ''));

        $options = new Registry();
        $options->set('userAgent', 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:41.0) Gecko/20100101 Firefox/41.0');

        try {
            $data = new Registry();
            $data->set('model', 'dall-e-2');
            $data->set('prompt', $promt);
            $data->set('n', 1);
            $data->set('size', '256x256');

            $apiKey = self::getApiKey();
            $header = [
                'Authorization' => "Bearer $apiKey",
                'Content-Type' => 'application/json',
            ];

            $response = HttpFactory::getHttp($options)->post(self::$endpoint, $data->toString(), $header);
        } catch (\RuntimeException $e) {
            throw new \RuntimeException('Unable to access endpoint.', $e->getCode(), $e);
        }

        if ($response->code != 200) {
            $errorRes = new Registry($response->body);
            $errorData = $errorRes->get('error');

            if ($errorData) {
                throw new \RuntimeException($errorData->message, 500);
            } else {
                throw new \RuntimeException("Access API error", 500);
            }
        }

        $result = new Registry($response->body);
        $imageData= $result->get('data', []);

        return [
            'type' => 'image',
            'data' => $imageData ? $imageData[0]->url : '',
        ];
    }
}
