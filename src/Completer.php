<?php

namespace Ridzhi\Readline\Completer\Yii2;


use Ridzhi\Readline\CompleteInterface;
use Ridzhi\Readline\Info\InfoInterface;
use Ridzhi\Readline\Info\Parser;
use yii\console\Application;
use yii\console\Controller;
use yii\console\controllers\HelpController;

/**
 * Class Completer
 * @package Ridzhi\Readline\Completer\Yii2
 */
class Completer implements CompleteInterface
{

    /**
     * @var \ReflectionClass
     */
    protected $class;

    /**
     * @var HelpController
     */
    protected $obj;

    /**
     * @var array[]
     */
    protected $api;

    /**
     * Completer constructor.
     * @throws \ReflectionException
     */
    public function __construct()
    {
        if (!\Yii::$app instanceof Application) {
            throw new \RuntimeException('This completer uses yii\'s HelpController and for correct work you must to init yii console application');
        }

        $this->class = new \ReflectionClass(HelpController::class);
        $this->obj = $this->class->newInstanceWithoutConstructor();
        $this->api = $this->buildAPI();
    }

    /**
     * @param string $input User input to cursor position
     * @return array
     */
    public function complete(string $input): array
    {
        $info = Parser::parse($input);
        $current = $info->getCurrent();

        switch ($info->getType()) {
            case InfoInterface::TYPE_ARG:
                $args = $info->getArgs();

                if (count($args) <= 1) {
                    return $this->filter($current, $this->commands());
                }

            case InfoInterface::TYPE_OPTION_SHORT:
            case InfoInterface::TYPE_OPTION_LONG:
                $command = $info->getArgs()[0];

                return $this->filter($current, $this->options($command));
            default:
                return [];

        }
    }

    /**
     * @return array
     */
    public function commands(): array
    {
        return array_keys($this->api);
    }

    /**
     * @param string $command
     *
     * @return array
     */
    public function options(string $command): array
    {
        if (!isset($this->api[$command])) {
            return [];
        }

        $options = $this->api[$command];

        //normalize
        return array_map(function ($elem) {
            return '--' . $elem;
        }, $options);
    }

    /**
     * @return array
     */
    protected function buildAPI(): array
    {
        $api = [];
        $commands = $this->reflectCommands();

        foreach ($commands as $c) {
            /** @var Controller $controller */
            $controller = \Yii::$app->createController($c)[0];
            $actions = $this->reflectActions($controller);

            foreach ($actions as $a) {
                $cmd = sprintf("%s/%s", $c, $a);
                $o = $controller->options($a);
                asort($o);
                $api[$cmd] = $o;
            }
        }

        return $api;
    }

    /**
     * @return array
     */
    protected function reflectCommands(): array
    {
        $method = $this->class->getMethod('getCommands');
        return $method->invoke($this->obj);
    }

    /**
     * @param Controller $controller
     * @return array
     */
    protected function reflectActions(Controller $controller): array
    {
        $method = $this->class->getMethod('getActions');
        return $method->invoke($this->obj, $controller);
    }

    /**
     * @param string $search
     * @param array $handle
     * @return array
     */
    protected function filter(string $search, array $handle): array
    {
        if ($search === '') {
            return $handle;
        }

        return array_filter($handle, function ($item) use ($search) {
            return strpos($item, $search) === 0 && $item !== $search;
        });
    }

}