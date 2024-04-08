<?php


namespace yiqiang3344\yii2_lib\helper\event;


use Closure;
use yii\base\Component;
use Yii;
use Exception;
use yii\base\InvalidConfigException;

/**
 * 事件门面类
 */
class Event extends Component
{
    /**
     * @var array
     */
    protected $listen = [];

    /**
     * 各事件已经绑定的监听者
     * @var array
     */
    protected $onListenerMap = [];

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
    }

    /**
     * @return array
     */
    public function getListen()
    {
        return $this->listen;
    }

    public function setListen(array $listen)
    {
        $this->listen = $listen;
    }

    /**
     * 调度事件.
     *
     * @param \yii\base\Event $event
     * @param null $data
     * @param array|closure|object|string|null $listeners
     *
     * @throws InvalidConfigException
     */
    public function dispatch(\yii\base\Event $event, $data = null, $listeners = null)
    {
        $listeners = $this->getListeners($event, $listeners);

        $this->onListeners($event, $data, $listeners);

        $this->trigger(get_class($event), $event);
    }

    /**
     * 获取事件全部监听.
     *
     * @param \yii\base\Event $event
     * @param array|closure|object|string|null $listeners
     *
     * @return array
     */
    public function getListeners(\yii\base\Event $event, $listeners = null)
    {
        $listeners = is_object($listeners) ? [$listeners] : (array)$listeners;

        return array_unique(
            array_merge(
                isset($this->listen[get_class($event)]) ? $this->listen[get_class($event)] : [],
                $listeners
            )
        );
    }

    /**
     * 批量添加事件监听.
     *
     * @param \yii\base\Event $event
     * @param null $data
     * @param array $listeners
     * @throws InvalidConfigException
     */
    public function onListeners(\yii\base\Event $event, $data = null, array $listeners = [])
    {
        foreach ($listeners as $listener) {
            $listener = Yii::createObject($listener);
            if (!$listener instanceof ListenerInterface) {
                throw new Exception(sprintf('The %s muse be implement %s.', get_class($listener), ListenerInterface::class));
            }

            if (isset($this->onListenerMap[get_class($event)][get_class($listener)])) {
                // 事件已经绑定监听者不重复绑定
                continue;
            }

            $this->on(get_class($event), [$listener, 'handle'], $data);
            $this->onListenerMap[get_class($event)][get_class($listener)] = 1; //事件已经绑定监听者做标记
        }
    }

    public static function event(\yii\base\Event $event, $data = null, $listeners = null)
    {
        /** @var Event $eventFacade */
        $eventFacade = \Yii::$app->event;
        $eventFacade->dispatch($event, $data, $listeners);
    }
}