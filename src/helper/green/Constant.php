<?php


namespace yiqiang3344\yii2_lib\helper\green;

/**
 * 安全内容通用常量
 */
class Constant
{
    //请求状态
    const STATUS_SUCCESS = 'success';//正常
    const STATUS_ERROR = 'error';//异常

    //检查结论
    const SUGGESTION_PASS = 'pass'; //合法
    const SUGGESTION_BLOCK = 'block'; //违规
    const SUGGESTION_REVIEW = 'review'; //疑似违规
    const SUGGESTION_ERROR = 'error'; //请求异常

    //标签
    const LABEL_NORMAL = 'normal'; //正常文本
    const LABEL_SPAM = 'spam'; //含垃圾信息
    const LABEL_AD = 'ad'; //广告
    const LABEL_POLITICS = 'politics'; //涉政
    const LABEL_TERRORISM = 'terrorism'; //暴恐
    const LABEL_ABUSE = 'abuse'; //辱骂
    const LABEL_PORN = 'porn'; //色情
    const LABEL_FLOOD = 'flood'; //灌水
    const LABEL_CONTRABAND = 'contraband'; //违禁
    const LABEL_MEANINGLESS = 'meaningless'; //无意义
    const LABEL_HARMFUL = 'harmful'; //不良场景（保护未成年场景，支持拜金炫富、追星应援、负面情绪、负面诱导等检测场景）
    const LABEL_CUSTOMIZED = 'customized'; //自定义（例如命中自定义关键词）
    const LABEL_OTHER = 'other'; //其他

    //场景
    const SCENE_PORN = 'porn'; //色情
    const SCENE_TERRORISM = 'terrorism'; //暴恐涉政
    const SCENE_ANTISPAM = 'antispam'; //文本内容检测

}