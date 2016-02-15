<?php
namespace Smartbox\Integration\FrameworkBundle\Util;

use Smartbox\Integration\FrameworkBundle\Messages\Exchange;
use Smartbox\Integration\FrameworkBundle\Traits\UsesMapper;
use Smartbox\Integration\FrameworkBundle\Traits\UsesSerializer;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class ExpressionEvaluator
{
    use UsesSerializer;
    use UsesMapper;

    /** @var ExpressionLanguage */
    protected $language;

    public function __construct()
    {
        $cache = new ApcParserCache();
        $this->language = new ExpressionLanguage($cache);
        // Register any providers here
    }

    public function getExchangeExposedVars(){
        return array(
            'exchange',
            'msg',
            'headers',
            'body',
            'serializer',
            'mapper',
            'now'
        );
    }

    public function evaluateWithVars($expression, $vars)
    {
        $vars = array_merge($vars,[
            'serializer' => $this->getSerializer(),
            'mapper' => $this->getMapper()
        ]);

        return $this->language->evaluate($expression, $vars);
    }

    public function evaluateWithExchange($expression, Exchange $exchange)
    {
        $body = $exchange->getIn()->getBody();

        return $this->language->evaluate($expression, array(
            'exchange' => $exchange,
            'msg' => $exchange->getIn(),
            'headers' => $exchange->getIn()->getHeaders(),
            'body' => $body,
            'serializer' => $this->getSerializer(),
            'mapper' => $this->getMapper(),
            'now' => new \DateTime(),
        ));
    }

    /**
     * @param $expression
     * @param array $names
     * @return string
     */
    public function compile($expression, $names = array())
    {
        return $this->language->compile($expression, $names);
    }

}
