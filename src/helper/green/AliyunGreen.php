<?php


namespace yiqiang3344\yii2_lib\helper\green;

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Green\Green;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;
use yiqiang3344\yii2_lib\helper\green\log\GreenLog;

/**
 * 阿里云安全内容类
 */
class AliyunGreen
{
    private $accessKeyId;
    private $accessKeySecret;
    private $region;

    private static $sceneMap = [
        Constant::SCENE_PORN => 'porn',
        Constant::SCENE_TERRORISM => 'terrorism',
        Constant::SCENE_ANTISPAM => 'antispam',
    ];

    private static $resultMap = [
        'pass' => Constant::SUGGESTION_PASS,
        'review' => Constant::SUGGESTION_REVIEW,
        'block' => Constant::SUGGESTION_BLOCK,
    ];

    private static $labelMap = [
        'normal' => Constant::LABEL_NORMAL,
        'spam' => Constant::LABEL_SPAM,
        'ad' => Constant::LABEL_AD,
        'politics' => Constant::LABEL_POLITICS,
        'terrorism' => Constant::LABEL_TERRORISM,
        'abuse' => Constant::LABEL_ABUSE,
        'porn' => Constant::LABEL_PORN,
        'flood' => Constant::LABEL_FLOOD,
        'contraband' => Constant::LABEL_CONTRABAND,
        'meaningless' => Constant::LABEL_MEANINGLESS,
        'harmful' => Constant::LABEL_HARMFUL,
        'customized' => Constant::LABEL_CUSTOMIZED,
    ];

    private function __construct($accessKeyId, $accessKeySecret, $region)
    {
        $this->accessKeyId = $accessKeyId;
        $this->accessKeySecret = $accessKeySecret;
        $this->region = $region;
    }

    public static function instance($accessKeyId, $accessKeySecret, $region = 'cn-shanghai')
    {
        return new self($accessKeyId, $accessKeySecret, $region);
    }

    /**
     * @param string $flowNumber 请求流水号
     * @param string $imageUrl 图片链接
     * @param string[] $scenes 场景列表
     * @param string $bizType 业务类型
     * @return array
     * @throws ClientException
     * @throws ServerException
     */
    public function image($flowNumber, $imageUrl, $scenes = [Constant::SCENE_PORN, Constant::SCENE_TERRORISM], $bizType = '')
    {
        $dataId = PROJECT_NAME . $flowNumber;
        $log = new GreenLog();
        $log->start('aliyunGreenImage', 'aliyun', 'image', [
            'dataId' => $dataId,
            'imageUrl' => $imageUrl,
            'scenes' => $scenes,
            'bizType' => $bizType,
        ]);

        $result = [
            'status' => Constant::STATUS_ERROR, //请求状态
            'msg' => '', //结果描述
            'requestId' => '', //阿里云请求ID
            'suggestion' => Constant::SUGGESTION_ERROR, //最终结论
            'results' => [], //结果明细
        ];

        try {
            AlibabaCloud::accessKeyClient($this->accessKeyId, $this->accessKeySecret)
                ->regionId($this->region)
                ->asDefaultClient();

            $task1 = [
                'dataId' => $dataId,
                'url' => $imageUrl,
            ];
            //场景转换
            $_scenes = [];
            foreach ($scenes as $scene) {
                $_scenes[] = self::$sceneMap[$scene];
            }
            $response = Green::v20180509()->imageSyncScan()
                ->body(json_encode([
                    'tasks' => [$task1],
                    'scenes' => $_scenes,
                    'bizType' => $bizType
                ]))->request();

            $code = $response->code;
            $result['requestId'] = $response->requestId;
            $result['msg'] = $response->msg;

            if (200 == $response->code) {
                $result['status'] = Constant::STATUS_SUCCESS;
                $taskResult = $response->data[0];
                if (200 == $taskResult->code) {
                    $result['suggestion'] = Constant::SUGGESTION_PASS;
                    $sceneResults = $taskResult->results;
                    $sceneMapFlip = array_flip(self::$sceneMap);
                    foreach ($sceneResults as $sceneResult) {
                        $_d = [
                            'scene' => $sceneMapFlip[$sceneResult->scene],
                            'label' => self::$labelMap[$sceneResult->label] ?? Constant::LABEL_OTHER,
                            'result' => self::$resultMap[$sceneResult->suggestion],
                            'rate' => $sceneResult->rate,
                        ];
                        $result['results'][] = $_d;
                        //最终结论取最严重的
                        if ($result['suggestion'] == Constant::SUGGESTION_PASS && in_array($_d['result'], [Constant::SUGGESTION_REVIEW, Constant::SUGGESTION_BLOCK])) {
                            $result['suggestion'] = $_d['result'];
                        }
                        if ($result['suggestion'] == Constant::SUGGESTION_REVIEW && in_array($_d['result'], [Constant::SUGGESTION_BLOCK])) {
                            $result['suggestion'] = $_d['result'];
                        }
                    }
                } else {
                    $code = $taskResult->code;
                    $result['status'] = Constant::STATUS_ERROR;
                    $result['msg'] = $taskResult->msg;
                }
            }
        } catch (ClientException $exception) {
            $code = $exception->getErrorCode();
            $result['status'] = Constant::STATUS_ERROR;
            $result['msg'] = $exception->getErrorMessage();
        } catch (ServerException $exception) {
            $code = $exception->getErrorCode();
            $result['status'] = Constant::STATUS_ERROR;
            $result['msg'] = $exception->getErrorMessage();
            $result['requestId'] = $exception->getRequestId();
        }

        $log->setResult($result, $code);
        $log->writeLog();
        return $result;
    }

    /**
     * 文本检查
     * @param string $flowNumber 请求流水号
     * @param string $str 文本
     * @param string $bizType
     * @return array
     */
    public function string($flowNumber, $str, $bizType = '')
    {
        $dataId = PROJECT_NAME . $flowNumber;
        $scenes = [Constant::SCENE_ANTISPAM];
        $log = new GreenLog();
        $log->start('aliyunGreenString', 'aliyun', 'string', [
            'dataId' => $dataId,
            'string' => $str,
            'scenes' => $scenes,
            'bizType' => $bizType,
        ]);

        $result = [
            'status' => Constant::STATUS_ERROR, //请求状态
            'msg' => '', //结果描述
            'requestId' => '', //阿里云请求ID
            'suggestion' => Constant::SUGGESTION_ERROR, //最终结论
            'results' => [], //结果明细
        ];
        try {
            AlibabaCloud::accessKeyClient($this->accessKeyId, $this->accessKeySecret)
                ->regionId($this->region)
                ->asDefaultClient();

            $task1 = [
                'dataId' => $dataId,
                'content' => $str,
            ];
            //场景转换
            $_scenes = [];
            foreach ($scenes as $scene) {
                $_scenes[] = self::$sceneMap[$scene];
            }
            $response = Green::v20180509()->textScan()
                ->body(json_encode([
                    'tasks' => [$task1],
                    'scenes' => $scenes,
                    'bizType' => $bizType,
                ]))->request();

            $code = $response->code;
            $result['requestId'] = $response->requestId;
            $result['msg'] = $response->msg;

            if (200 == $response->code) {
                $result['status'] = Constant::STATUS_SUCCESS;
                $taskResult = $response->data[0];
                if (200 == $taskResult->code) {
                    $result['suggestion'] = Constant::SUGGESTION_PASS;
                    $sceneResults = $taskResult->results;
                    $sceneMapFlip = array_flip(self::$sceneMap);
                    foreach ($sceneResults as $sceneResult) {
                        $_d = [
                            'scene' => $sceneMapFlip[$sceneResult->scene],
                            'label' => self::$labelMap[$sceneResult->label] ?? Constant::LABEL_OTHER,
                            'result' => self::$resultMap[$sceneResult->suggestion],
                            'rate' => $sceneResult->rate,
                        ];
                        $result['results'][] = $_d;
                        //最终结论取最严重的
                        if ($result['suggestion'] == Constant::SUGGESTION_PASS && in_array($_d['result'], [Constant::SUGGESTION_REVIEW, Constant::SUGGESTION_BLOCK])) {
                            $result['suggestion'] = $_d['result'];
                        }
                        if ($result['suggestion'] == Constant::SUGGESTION_REVIEW && in_array($_d['result'], [Constant::SUGGESTION_BLOCK])) {
                            $result['suggestion'] = $_d['result'];
                        }
                    }
                } else {
                    $code = $taskResult->code;
                    $result['status'] = Constant::STATUS_ERROR;
                    $result['msg'] = $taskResult->msg;
                }
            }
        } catch (ClientException $exception) {
            $code = $exception->getErrorCode();
            $result['status'] = Constant::STATUS_ERROR;
            $result['msg'] = $exception->getErrorMessage();
        } catch (ServerException $exception) {
            $code = $exception->getErrorCode();
            $result['status'] = Constant::STATUS_ERROR;
            $result['msg'] = $exception->getErrorMessage();
            $result['requestId'] = $exception->getRequestId();
        }
        $log->setResult($result, $code);
        $log->writeLog();
        return $result;
    }
}