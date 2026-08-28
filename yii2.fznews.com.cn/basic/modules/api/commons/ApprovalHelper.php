<?php

namespace app\modules\api\commons;

use Yii;
use app\modules\api\models\WeixinOaApprovaldata;

/**
 * 审批流程公共处理类
 * 解决并发重复审批、审批人校验等通用问题
 */
class ApprovalHelper
{
    /**
     * 检查用户是否已在当前 step 审批过（幂等性检查）
     * 通过 approvaldata JSON 中的 ItemStatus 判断，ItemStatus != 1 即已审批
     *
     * @param string $thirdNo     业务单号
     * @param string $userid      审批人 wxuserid
     * @param int    $agentid     应用 agentId
     * @return array ['hasApproved' => bool, 'step' => int|null, 'errorMessage' => string]
     */
    public static function checkIdempotency($thirdNo, $userid, $agentid)
    {
        $approveres = WeixinOaApprovaldata::find()
            ->where(['agentid' => $agentid, 'thirdNo' => $thirdNo])
            ->one();

        if (!$approveres) {
            return [
                'hasApproved' => false,
                'step' => null,
                'errorMessage' => '找不到审批流程数据',
            ];
        }

        $step = intval($approveres->step);
        $approvearr = json_decode($approveres->data, true);

        if (!$approvearr || !isset($approvearr['data']['ApprovalNodes']['ApprovalNode'])) {
            return [
                'hasApproved' => false,
                'step' => $step,
                'errorMessage' => '',
            ];
        }

        // 在当前 step 的 items 里检查该用户是否已审批（ItemStatus != 1 即已处理）
        $items = $approvearr['data']['ApprovalNodes']['ApprovalNode'][$step]['Items']['Item'] ?? [];
        foreach ($items as $item) {
            if ($item['ItemUserId'] == $userid && intval($item['ItemStatus']) != 1) {
                return [
                    'hasApproved' => true,
                    'step' => $step,
                    'errorMessage' => '您已在该步骤完成审批，请勿重复提交',
                ];
            }
        }

        return [
            'hasApproved' => false,
            'step' => $step,
            'errorMessage' => '',
        ];
    }

    /**
     * 检查用户是否是当前审批人
     *
     * @param string $thirdNo          业务单号
     * @param string $userid           审批人 wxuserid
     * @param string $approvalUserid   当前审批人字段（可能是 userid1|userid2 格式）
     * @param string $approvalUsername 当前审批人姓名
     * @return array ['isApprover' => bool, 'errorMessage' => string]
     */
    public static function checkCurrentApprover($thirdNo, $userid, $approvalUserid, $approvalUsername)
    {
        if ($approvalUserid && !in_array($userid, explode('|', $approvalUserid))) {
            return [
                'isApprover' => false,
                'errorMessage' => '当前审批人是：' . $approvalUsername,
            ];
        }

        return [
            'isApprover' => true,
            'errorMessage' => '',
        ];
    }

    /**
     * 检查用户是否属于当前 step 的审批人列表中
     *
     * @param string $thirdNo     业务单号
     * @param string $userid      审批人 wxuserid
     * @param int    $agentid     应用 agentId
     * @return array ['inCurrentStep' => bool, 'step' => int|null, 'errorMessage' => string]
     */
    public static function checkUserInCurrentStep($thirdNo, $userid, $agentid)
    {
        $approveres = WeixinOaApprovaldata::find()
            ->where(['agentid' => $agentid, 'thirdNo' => $thirdNo])
            ->one();

        if (!$approveres) {
            return [
                'inCurrentStep' => false,
                'step' => null,
                'errorMessage' => '找不到审批流程数据',
            ];
        }

        $step = intval($approveres->step);
        $approvearr = json_decode($approveres->data, true);

        if (!$approvearr || !isset($approvearr['data']['ApprovalNodes']['ApprovalNode'])) {
            return [
                'inCurrentStep' => false,
                'step' => $step,
                'errorMessage' => '',
            ];
        }

        $items = $approvearr['data']['ApprovalNodes']['ApprovalNode'][$step]['Items']['Item'] ?? [];
        foreach ($items as $item) {
            if ($item['ItemUserId'] == $userid) {
                return [
                    'inCurrentStep' => true,
                    'step' => $step,
                    'errorMessage' => '',
                ];
            }
        }

        return [
            'inCurrentStep' => false,
            'step' => $step,
            'errorMessage' => '您不在当前审批节点中，无法审批',
        ];
    }

    /**
     * 包装审批操作：三重校验
     * 1. 检查用户是否属于当前 step（排除不在节点中的非法请求）
     * 2. 幂等性检查（该用户该 step 是否已审批过）
     * 3. 审批人身份检查（main 表 approvalUserid）
     *
     * @param string $thirdNo          业务单号
     * @param string $userid           审批人 wxuserid
     * @param int    $agentid          应用 agentId
     * @param string $approvalUserid   当前审批人字段
     * @param string $approvalUsername 当前审批人姓名
     * @return array ['pass' => bool, 'step' => int|null, 'errorMessage' => string]
     */
    public static function validateApproval($thirdNo, $userid, $agentid, $approvalUserid, $approvalUsername)
    {
        // 1. 检查用户是否属于当前 step
        $stepResult = self::checkUserInCurrentStep($thirdNo, $userid, $agentid);
        if (!$stepResult['inCurrentStep']) {
            return [
                'pass' => false,
                'step' => null,
                'errorMessage' => $stepResult['errorMessage'] ?: '您不在当前审批节点中',
            ];
        }

        // 2. 幂等性检查：当前 step 的 ItemStatus 是否已有值
        $idempotentResult = self::checkIdempotency($thirdNo, $userid, $agentid);
        if ($idempotentResult['hasApproved']) {
            return [
                'pass' => false,
                'step' => null,
                'errorMessage' => $idempotentResult['errorMessage'],
            ];
        }

        // 3. 审批人身份检查（main 表 approvalUserid）
        $approverResult = self::checkCurrentApprover($thirdNo, $userid, $approvalUserid, $approvalUsername);
        if (!$approverResult['isApprover']) {
            return [
                'pass' => false,
                'step' => null,
                'errorMessage' => $approverResult['errorMessage'],
            ];
        }

        return [
            'pass' => true,
            'step' => $idempotentResult['step'],
            'errorMessage' => '',
        ];
    }
}
