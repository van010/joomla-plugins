<?php


namespace Joomla\Plugin\System\JAAIAssistant\OpenAI;

use Joomla\CMS\Factory;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Plugin\System\JAAIAssistant\OpenAI\Trait\ApiTrait;
use Joomla\Registry\Registry;

defined('_JEXEC') or die('Restricted access');

class ChatHelper
{
    use ApiTrait;

    protected static $endpoint = 'https://api.openai.com/v1/chat/completions';

    public static function doTask()
    {
        $input = Factory::getApplication()->input;
        $task = $input->get('aitask', '');

        $promt = '';

        switch ($task) {
            case 'continue_writing':
                $promt .= "continue writing \n";
                break;
        }

        $content = $input->getString('content', '');
        $promt .= $content;

        $options = new Registry();
        $options->set('userAgent', 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:41.0) Gecko/20100101 Firefox/41.0');

        try {
            $data = new Registry();
			$aiModel = self::getModelsType();
            $data->set('model', $aiModel);
            $data->set('messages', [
                [
                    'role' => 'user',
                    'content' => $promt,
                ]
            ]);

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
        $choices = $result->get('choices', []);

        return [
            'type' => 'text',
            'data' => $choices[0]->message->content,
        ];
    }

	public static function dev()
	{
		return [
			'type' => 'text',
			'code' => 200,
			'data' => self::generateRandomSentence(),
		];
	}

	public static function generateRandomSentence() {
		// config end words here
		$endWords = 30;
		$startWords = round($endWords / 1.5);

	    $words = array(
	        'The', 'quick', 'brown', 'fox', 'jumps', 'over', 'the', 'lazy', 'dog',
	        'Jack', 'and', 'Jill', 'went', 'up', 'the', 'hill', 'to', 'fetch', 'a', 'pail', 'of', 'water.',
	        'Mary', 'had', 'a', 'little', 'lamb,', 'its', 'fleece', 'was', 'white', 'as', 'snow.',
	        'Humpty', 'Dumpty', 'sat', 'on', 'a', 'wall,', 'Humpty', 'Dumpty', 'had', 'a', 'great', 'fall.'
	    );

	    $sentence = '';
	    $wordCount = count($words);
	    $targetWordCount = rand($startWords, $endWords); // Generate a random number of words for the sentence

	    for ($i = 0; $i < $targetWordCount; $i++) {
	        $randomIndex = rand(0, $wordCount - 1);
	        $sentence .= $words[$randomIndex] . ' ';
	    }

	    // Capitalize the first letter of the sentence and add a period at the end
		$sentence = ucfirst(trim($sentence)) . '.\\n\\n' . ucfirst(trim($sentence)) . '. \\n Hihi 123';
	    // $sentence = ucfirst(trim($sentence)) . '. ';

	    return $sentence;
	}

	/**
	 * get model type
	 *
	 * @return mixed|\stdClass
	 *
	 * @since version
	 */
	public static function getModelsType()
	{
		$params = self::getParams();
		return $params->get('models', 'gpt-3.5-turbo');
	}

	/**
	 * get plugin params
	 *
	 * @return Registry
	 *
	 * @since version
	 */
	public static function getParams()
	{
		$plugin = PluginHelper::getPlugin('system', 'jaaiassistant');
		$plgParams = new Registry();
		return $plgParams->loadString($plugin->params);
	}

	/**
	 * function call through api for dev test
	 * administrator/index.php?option=com_ajax&plugin=jaaiassistant&format=json&group=system&task=custom_dev
	 *
	 * @since version
	 */
	public static function customDev()
	{
		self::getModelsType();
	}
}
