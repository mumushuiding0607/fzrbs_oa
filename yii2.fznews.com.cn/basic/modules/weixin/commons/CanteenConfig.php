<?php

namespace app\modules\weixin\commons;

use Yii;

/**
 * 食堂相关配置数据类
 */
class CanteenConfig
{
    public static function data()
    {
        // 用户账号结算类别
        $userType = [];
        $res = (new \yii\db\Query())->select('*')->from('shitang_setting')->where(['=', 'typeid', 1])->all();
        foreach ($res as $row) {
            $userType[$row['varid']] = ['text' => $row['varvalue']];
        }
        if (!$userType) {
            $userType = [
                5 => ['text' => '社直'],
                6 => ['text' => '日报'],
                7 => ['text' => '晚报'],
                2 => ['text' => '社新媒体中心'],
                3 => ['text' => '晚报运营中心'],
                4 => ['text' => '日报运营中心'],
                9 => ['text' => '福小子体育文化传播有限公司'],
                10 => ['text' => '众创孵化中心'],
                11 => ['text' => '福州新闻图片社'],
                12 => ['text' => '晚报运营中心(一碗福州)'],
                13 => ['text' => '晚报发行中心'],
                14 => ['text' => '市宣教中心'],
                15 => ['text' => '社视觉中心'],
                8 => ['text' => '报社其他'],
                1 => ['text' => '无补贴'],
                -1 => ['text' => '辞职'],
                0 => ['text' => '未设置'],
            ];
        }
        // 食堂菜单类别
        $orderType = [];
        $res = (new \yii\db\Query())->select('*')->from('shitang_setting')->where(['=', 'typeid', 2])->all();
        foreach ($res as $row) {
            $orderType[$row['varid']] = $row['varvalue'];
        }
        if (!$orderType) {
            $orderType = [1 => '午餐', 2 => '晚餐', 3 => '早餐', 4 => '其他', 5 => '代购', 6 => '面对面', 7 => '咖啡', 100 => '现煮'];
        }
        // 用户订单状态类别
        $orderStatus = [0 => '未使用', 1 => '已使用', 2 => '已取消'];
        // 用户订单支付类别
        $orderPayType = [0 => '餐补余额支付', 1 => '微信余额支付'];
        // 供应时段
        $timeInterval = ['全天', '午餐', '晚餐', '早餐'];
        // 取餐时间
        $useTime = [
            1 => ['11:30', '11:50', '12:10', '12:30'],
            2 => ['18:00', '18:20', '18:40', '19:00'],
        ];
        // 公告
        $notice = [
            1 => '每天午餐预订时间最迟截至前一天晚上22:00',
            2 => '',
            3 => '取餐时间：8:00－9:30',
        ];
        $noticeKeys = array_keys($notice);
        $res = (new \yii\db\Query())->select('*')->from('shitang_setting')->where(['=', 'typeid', 3])->all();
        foreach ($res as $row) {
            if (in_array($row['varid'], $noticeKeys) && trim($row['varvalue'])) {
                $notice[$row['varid']] = $row['varvalue'];
            }
        }
        // 记者和中层人员名单，可点11:30的餐
        $reporter = [];
        $row = (new \yii\db\Query())->select('*')->from('shitang_setting')->where(['=', 'typeid', 4])->one();
        if ($row) {
            $reporter = explode(',', $row['varvalue']);
        }
        if (!$reporter) {
            $reporter = ['王华明', '潘起华', '黄财东', '陈春辉', '陈松光', '黄平安', '李欣', '赵时强', '李振建', '马心怡', '高峥', '翁礼清', '张秋生', '黄财升', '张浩清', '彭小林', '林玉和', '冯东升', '毛清玉', '林昱', '王宇', '吴旭东', '孟健', '徐旭', '雷岩平', '陈长森', '朱毓松', '王威', '张旭', '綦芬', '赵铮艳', '陈丹', '陈木易', '程明', '何佳媛', '李晖', '李锦清', '梁凯鸿', '全怡月', '林春长', '林铭', '刘珺', '许安梓', '马丽清', '毛小春', '江海', '王光慧', '徐文宇', '叶智勤', '郑瑞洋', '朱丹华', '叶诚', '张旭阳', '陈暖', '石美祥', '林双伟', '陈尚言', '王月玲', '林雅', '管慧', '兰超', '危砖黄', '邱泉盛', '王杨林', '李琼', '郑锦銮', '金清华', '赵莹', '林根', '刘颖', '鄢斌', '范庆芬', '顾伟', '翁宇民', '陈坚', '管澍', '刘玉纯', '叶繁', '刘磊', '杨星', '张杰', '张秀冰', '卓巧华', '江娟珺', '吴妍欣', '迟娟娟', '侯宗焜', '林立新', '林亦敏', '杨韬', '王臻', '关志杰', '丁胜华', '廖晓霞', '卢锃', '黄渭水', '吴载文', '王文兵', '李芳云', '陈跃平', '季珊珊', '林已琳', '李季春', '郭秀春', '武维琦', '刘亦洪', '陈翔茹', '王晓婷', '魏旸艳', '徐强', '吴德峰', '陈琳', '黄戎杰', '杨莹', '谢星星', '李白蕾', '陈敏灵', '张铁国', '朱榕', '王玉萍', '祁正华', '曾建兵', '任思言', '吴晖', '王元锴', '郑帅', '池远', '叶义斌', '孙漫', '黄凌', '覃作权', '林晗', '张笑雪', '莫思予', '蒋雅琛', '钱嘉宜', '欧阳进权', '赵昕玥', '余少林', '阮冠达', '颜澜萍', '叶欣童', '林瑞琪', '邹家骅', '张人峰', '王炳聪', '林文婧', '赖志昌', '叶娴', '冯雪珠', '林洪相', '万小英', '李淑娟', '谢薇', '鄢秀钦', '林奕婷', '陈玫萍', '陈章浜', '曹聪', '陈永章', '黎伦俊', '赵金华', '王新', '徐冶', '程仁山', '李军', '吕路阳', '黄成锟', '胡一晟', '姜福涛', '刘子锐', '刘君琳', '黄芳宾', '鲍海峰', '李海燕', '林若菡', '刘舒', '熊宏娇', '赖旋莉', '吴世耀', '林伟', '贺鹏', '周甬', '范雄', '黄而海', '林敏勇', '刘必泳', '吴文霖', '陈玲云', '李永华', '陈然', '陈颖', '李琳珊', '林少斌', '林玮佳', '刘梦霆', '马春林', '苏雪容', '徐匆', '伊宁倩', '詹婷婷', '钟培培', '卓德华', '朱幸宇', '刘宝英', '李佾阳', '潘温祥', '陈嘉尉', '陈美玉', '陈哲钊', '陈智丰', '黄丽薇', '林欢', '林学晨', '刘晓芳', '申哲', '苏帆', '孙静晨', '滕一郎', '童雯婷', '王婧', '肖远强', '杨扬', '陈莉', '黄熙', '林昊', '林梦西', '林娴洁', '林昕蔚', '马沄钦', '王成男', '翁莉琼', '谢文君', '陈颖洁', '陈昊翔', '陈志超', '黄芷桐', '林鸿杰', '林凯航', '林美雪', '林羽晗', '邱陵', '苏怡莲', '杨丹艳', '原浩', '俞建强', '俞松', '陈华敏', '陈捷阳', '金振玉', '林德辉', '刘新征', '潘倩', '吴世荣', '钟文兴', '田辉', '张凌', '曾艳', '包丽君', '邓军', '肖迪勇'];
        }
        // 领导名单
        $leader = [];
        $row = (new \yii\db\Query())->select('*')->from('shitang_setting')->where(['=', 'typeid', 5])->one();
        if ($row) {
            $users = explode(',', $row['varvalue']);
            foreach ($users as $v) {
                $userinfo = explode('|||', $v);
                $leader[] = $userinfo[0];
                $leader[] = $userinfo[1];
            }
        }
        if (!$leader) {
            $leader = ['卓良辉', '谢联灵', '庄永强', '陈滨峰', '吴金垵', '金麦子', '朱福星', '张维璟', '刘琳', '赵莹', 'chenbinfeng', 'xielianling', 'zhuangyongqiang', 'WuJinAn', 'ZhuoLiangHui', 'jinmaizi', 'ZhuFuXing', 'zhaoying'];
        }
        // 专门账号
        $speciallyUserId = ['ChenYiXiao', 'zhanghaoqing', 'linyuhe'];
        $speciallyUserIName = ['ChenYiXiao' => '陈宜孝', 'zhanghaoqing' => '张浩清', 'linyuhe' => '林玉和'];
        // 取餐时间最多下单人数
        $orderMaxNum = [
            'default' => 50, // 默认
            '1' => 70, // 11:30
        ];
        // 订餐代购时间设置
        $timeSetting = [
            // 订餐截止时间
            1 => "22",
            // 代购开始时间
            2 => "07",
            // 代购结束时间
            3 => "20",
        ];
        $timeSettingKeys = array_keys($timeSetting);
        $res = (new \yii\db\Query())->select('*')->from('shitang_setting')->where(['=', 'typeid', 6])->all();
        foreach ($res as $row) {
            if (in_array($row['varid'], $timeSettingKeys) && trim($row['varvalue'])) {
                $varvalue = strlen($row['varvalue']) < 2 ? '0' . $row['varvalue'] : $row['varvalue'];
                $timeSetting[$row['varid']] = $varvalue;
            }
        }

        return ['userType' => $userType, 'orderType' => $orderType, 'orderStatus' => $orderStatus, 'orderPayType' => $orderPayType, 'timeInterval' => $timeInterval, 'useTime' => $useTime, 'notice' => $notice, 'reporter' => $reporter, 'leader' => $leader, 'speciallyUserId' => $speciallyUserId, 'speciallyUserIName' => $speciallyUserIName, 'orderMaxNum' => $orderMaxNum, 'dingcantime' => $timeSetting[1], 'daigoutime1' => $timeSetting[2], 'daigoutime2' => $timeSetting[3]];
    }
}
