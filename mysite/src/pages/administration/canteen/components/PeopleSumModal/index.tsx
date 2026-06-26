import { ProForm, ProFormDatePicker } from '@ant-design/pro-components';
import { Card, Form, List, Modal } from 'antd';
import React, { useImperativeHandle } from 'react';
import { useState } from 'react';
import { useRequest } from 'umi';
import { peopleSum } from '../list/service';

export type RechargeModalProps = {
    onOk?: (values: object) => void;
};

const PeopleSumModal = React.forwardRef((props: RechargeModalProps, ref) => {
    const [visible, setVisible] = useState(false);

    const handleCancel = () => setVisible(false);
    const handleOk = async () => {
        setVisible(false);
    };

    const { data, loading, run } = useRequest((params: any) => {
        return peopleSum(params);
    });

    const list = data?.data || [];
    const orderDay = data?.orderDay || '';

    useImperativeHandle(ref, () => ({
        setVisible: (value: boolean) => {
            run({});
            setVisible(value);
        },
    }));

    return (
        <Modal
            visible={visible}
            title={
                <ProForm
                    layout="inline"
                    submitter={{ searchConfig: { submitText: '查询' } }}
                    onFinish={async (values) => {
                        if (values.menuType) {
                            values.menuType = values.menuType.join(',');
                        }
                        run(values);
                    }}
                >
                    <Form.Item label={"每日用餐人数统计(此数据为订单数统计出的用餐人数，实际用餐人数可能高于此统计数据)。当前统计时间：" + orderDay + '。搜索'} />
                    <ProFormDatePicker name="orderTime" label="用餐日期" />
                </ProForm>
            }
            onCancel={handleCancel}
            onOk={handleOk}
            width="80vw"
            footer={false}
        >
            <List<any>
                rowKey="id"
                grid={{
                    gutter: 16,
                    xs: 1,
                    sm: 2,
                    md: 3,
                    lg: 3,
                    xl: 8,
                    xxl: 8,
                }}
                loading={loading}
                dataSource={list}
                renderItem={(item) => (
                    <List.Item>
                        <Card hoverable bodyStyle={{ paddingBottom: 20 }} title={item.name}>
                            总人数：{item.num + item.num1}<br />
                            预定人数：{item.num}<br />
                            面对面人数：{item.num1}<br />
                        </Card>
                    </List.Item>
                )}
            />
        </Modal>
    );
});
export default PeopleSumModal;
