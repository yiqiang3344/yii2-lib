<?php
/**
 * User: ljj
 */
namespace yiqiang3344\yii2_lib\helper\tracer;

use Zipkin\Timestamp;
use Zipkin\Propagation\Map;
use Zipkin\Propagation\DefaultSamplingFlags;
use Zipkin\TracingBuilder;
use Zipkin\Samplers\BinarySampler;
use Zipkin\Endpoint;
use Zipkin\Span;
use yii\base\Model;
use Yii;
use yiqiang3344\yii2_lib\helper\config\Config;
use yii\base\Event;
use yii\httpclient\Client;
use yii\httpclient\Request;
use yii\httpclient\Response;
use yii\db\ActiveRecord;
use yiqiang3344\yii2_lib\helper\redis\Connection;

/**
 * 全链路追踪
 * User: ljj
 */
class Tracer extends Model
{
    private $tracing;
    private $tracer;

    /**
     * @var Span
     */
    private $span; // root span
    private $clientSpan; // childSpan
    private $mysqlClass; // 防止多次触发 init
    private $isOn;
    private $isFirstSpan;

    public function init()
    {
        if (isset($this->tracing)) {
            return;
        }

        // init tracing and tracer
        $config = Config::getArray('tracer');
        if(empty($config)){
            $this->isOn = false;
        }
        $this->isOn = $config['isOn'] ?? 0;
        $reportUrl = $config['url'] ?? '';
        if(!$this->isOn || empty($reportUrl)) {
            return;
        }

        if (!defined('PROJECT_NAME')) {
            throw new \Exception('项目名未定义');
        }
        $serviceName = PROJECT_NAME;
        $tracing = $this->createTracing($reportUrl, $serviceName, gethostbyname(gethostname()));
        $this->tracing = $tracing;
        $tracer = $tracing->getTracer();
        $this->tracer = $tracer;
        $this->span = $this->createSpan();

        $this->registerEvent();

        if(php_sapi_name() == 'fpm-fcgi'){
            register_shutdown_function([$this, 'flush']);
        }
        parent::init();
    }
    
    public function flush()
    {
        $status_code = Yii::$app->response->getStatusCode();
        $this->span->tag(\Zipkin\Tags\HTTP_STATUS_CODE, $status_code);
        $this->tagHttpError($this->span, $status_code);
        $this->span->finish();
        $this->tracer->flush();
    }

    private function createSpan() : Span
    {
        $header = Yii::$app->getRequest()->getHeaders()->toArray();
        $carrier = $this->parseHeaders($header);

        /* Extracts the context from the HTTP headers */
        $extractor = $this->tracing->getPropagation()->getExtractor(new Map());
        $extractedContext = $extractor($carrier);

        $this->isFirstSpan = $extractedContext->isSampled();
        if ($extractedContext->isSampled()){
            // 非初始节点
            $span = $this->tracer->nextSpan($extractedContext);
        } else {
            /* Always sample traces */
            $defaultSamplingFlags = DefaultSamplingFlags::createAsSampled();
    
            /* Creates the main span */
            $span = $this->tracer->newTrace($defaultSamplingFlags);
        }
        $span->start(Timestamp\now());
        $span->setName((Yii::$app->controller->id ?? '') . '/' . (Yii::$app->controller->action->id ?? ''));
        $span->setKind(\Zipkin\Kind\SERVER);
        return $span;
    }

    private function parseHeaders($headers)
    {
        return array_map(function ($header) {
            return $header[0];
        }, $headers);
    }

    /**
     * 获取 trace_id
     */
    public function getTraceId() : string
    {
        if(!isset($this->span)){
            return '';
        }
        return $this->span->getContext()->getTraceId();
    }

    /**
     * 对外请求开始的时候调这个，做标记
     */
    public function webRequestStart(Request &$request, string $name=null)
    {
        if(!$this->isOn){
            return;
        }

        /* Creates the span for getting the users list */
        $childSpan = $this->tracer->newChild($this->span->getContext());
        $childSpan->start();
        $childSpan->setKind(\Zipkin\Kind\CLIENT);
        $childSpan->setName($name ?: 'http_client');
        $childSpan->tag(\Zipkin\Tags\HTTP_URL, $request->getUrl());
        
        if(!$this->isFirstSpan) {
            $headers = $request->getHeaders()->toArray();
            $headers = $this->parseHeaders($headers);
            /* Injects the context into the wire */
            $injector = $this->tracing->getPropagation()->getInjector(new Map());
            $injector($childSpan->getContext(), $headers);
            $request->setHeaders($headers);
        }

        //$childSpan->annotate('request_started', Timestamp\now());
        $this->clientSpan = $childSpan;
    }

    /**
     * 对外请求结束的时候调这个，做标记
     */
    public function webRequestFinish(Response $response)
    {
        if(!$this->isOn || !isset($this->clientSpan)){
            return;
        }

        //$this->clientSpan->annotate('request_finished', Timestamp\now());
        $this->clientSpan->tag(\Zipkin\Tags\HTTP_STATUS_CODE, $response->getStatusCode());
        $this->tagHttpError($this->clientSpan, $response->getStatusCode());
        $this->clientSpan->finish();
    }

    private function tagHttpError(Span &$span, $status_code)
    {
        $code_prev = intval(intval($status_code) / 100);
        if ($code_prev == 4 || $code_prev == 5) {
            $span->tag(\Zipkin\Tags\ERROR, true);
        }
    }

    /**
     * 第三方客户端 http 请求开始
     * @param string url
     * @param string name span名称，不设置默认为url
     * @return array header ['trace_id'=>'xxx']
     */
    public function otherWebRequestStart(string $url, $name=null) : array
    {
        if(!$this->isOn){
            return [];
        }

        /* Creates the span for getting the users list */
        $childSpan = $this->tracer->newChild($this->span->getContext());
        $childSpan->start();
        $childSpan->setKind(\Zipkin\Kind\CLIENT);
        $childSpan->setName($name ?: 'http_client');
        $childSpan->tag(\Zipkin\Tags\HTTP_URL, $url);
        
        $headers = [];
        if(!$this->isFirstSpan) {
            /* Injects the context into the wire */
            $injector = $this->tracing->getPropagation()->getInjector(new Map());
            $injector($childSpan->getContext(), $headers);
        }

        //$childSpan->annotate('request_started', Timestamp\now());
        $this->clientSpan = $childSpan;
        return $headers;
    }

    /**
     * 第三方客户端 http 请求结束
     * @param string|int status_code
     */
    public function otherWebRequestFinish($status_code)
    {
        if(!$this->isOn || !isset($this->clientSpan)){
            return;
        }

        //$this->clientSpan->annotate('request_finished', Timestamp\now());
        $this->clientSpan->tag(\Zipkin\Tags\HTTP_STATUS_CODE, $status_code);
        $this->tagHttpError($this->clientSpan, $status_code);
        $this->clientSpan->finish();
    }

    /**
     * 追踪 mysql
     */
    public function mysqlStart($name)
    {
        $childSpan = $this->requestStart($name);
        if($childSpan){
            $childSpan->tag('db.type', 'sql');
        }
        return $childSpan;
    }

    /**
     * 追踪redis
     */
    public function redisStart($name)
    {
        $childSpan = $this->requestStart($name);
        if($childSpan){
            $childSpan->tag('db.type', 'redis');
        }
        return $childSpan;
    }

    /**
     * 通用追踪
     */
    public function requestStart($name)
    {
        if(!$this->isOn){
            return;
        }
        
        /* Creates the span for getting the users list */
        $childSpan = $this->tracer->newChild($this->span->getContext());
        $childSpan->start();
        $childSpan->setKind(\Zipkin\Kind\CLIENT);
        $childSpan->setName($name);

        $this->clientSpan = $childSpan;
        return $childSpan;
    }

    /**
     * 结束一个请求记录
     */
    public function requestFinish($name=null)
    {
        if(!$this->isOn || !isset($this->clientSpan)){
            return;
        }

        if($name){
            $this->clientSpan->setName($name);
        }
        $this->clientSpan->finish();
    }

    private function createTracing($httpReporterURL, $localServiceName, $localServiceIPv4, $localServicePort = null)
    {
        /*
        $httpReporterURL = getenv('HTTP_REPORTER_URL');
        if ($httpReporterURL === false) {
            $httpReporterURL = 'http://tracing-analysis-dc-hz.aliyuncs.com/adapt_af5buf5u2s@3b4eb977c2ddebd_af5buf5u2s@53df7ad2afe8301/api/v2/spans';
        }
        */

        $endpoint = Endpoint::create($localServiceName, $localServiceIPv4, null, $localServicePort);

        $reporter = new \Zipkin\Reporters\Http(['endpoint_url' => $httpReporterURL]);
        $sampler = BinarySampler::createAsAlwaysSample();
        $tracing = TracingBuilder::create()
            ->havingLocalEndpoint($endpoint)
            ->havingSampler($sampler)
            ->havingReporter($reporter)
            ->build();
        return $tracing;
    }

    private function registerEvent()
    {
        // 因为是串行执行，可以确认系统里只有一个活跃 clientSpan
        Event::on(Client::class, Client::EVENT_BEFORE_SEND, function ($event) {
            $this->webRequestStart($event->request);
        });

        Event::on(Client::class, Client::EVENT_AFTER_SEND, function ($event) {
            $this->webRequestFinish($event->response);
        });

        // mysql
        Event::on(ActiveRecord::class, ActiveRecord::EVENT_INIT,function($event){
            if($this->mysqlClass == $event->sender->className()){
                return;
            }
            $this->mysqlClass = $event->sender->className();
            $this->mysqlStart('MYSQL:init:'. $event->sender->tableName());
        });
        Event::on(ActiveRecord::class, ActiveRecord::EVENT_AFTER_FIND,function($event){
            $this->mysqlClass = null;
            $this->requestFinish('MYSQL:select:'. $event->sender->tableName());
        });
        Event::on(ActiveRecord::class, ActiveRecord::EVENT_BEFORE_UPDATE,function($event){
            $this->mysqlStart('MYSQL:update:'. $event->sender->tableName());
        });
        Event::on(ActiveRecord::class, ActiveRecord::EVENT_BEFORE_INSERT,function($event){
            $this->mysqlStart('MYSQL:insert:'. $event->sender->tableName());
        });
        Event::on(ActiveRecord::class, ActiveRecord::EVENT_BEFORE_DELETE,function($event){
            $this->mysqlStart('MYSQL:delete:'. $event->sender->tableName());
        });
        Event::on(ActiveRecord::class, ActiveRecord::EVENT_AFTER_UPDATE,function($event){
            $this->requestFinish();
        });
        Event::on(ActiveRecord::class, ActiveRecord::EVENT_AFTER_INSERT,function($event){
            $this->requestFinish();
        });
        Event::on(ActiveRecord::class, ActiveRecord::EVENT_AFTER_DELETE,function($event){
            $this->requestFinish();
        });

        // redis
        Event::on(Connection::class, Connection::EVENT_BEFORE_EXECUTE,function($event){
            $command = strtoupper($event->command);
            if($command == 'AUTH' || $command == 'SELECT'){
                return;
            }
            $this->redisStart('REDIS:' . $command);
        });
        Event::on(Connection::class, Connection::EVENT_AFTER_EXECUTE,function($event){
            $command = strtoupper($event->command);
            if($command == 'AUTH' || $command == 'SELECT'){
                return;
            }
            $this->requestFinish();
        });
    }
}