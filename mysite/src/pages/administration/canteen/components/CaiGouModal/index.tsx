import { Modal } from 'antd';
import React, { useImperativeHandle, useRef } from 'react';
import { useState } from 'react';
import {
    ProForm,
    ProFormDatePicker,
    ProFormInstance,
} from '@ant-design/pro-components';
import tools from '@/utils/tools';
import moment from 'moment';

export type RechargeModalProps = {
    onOk?: (values: object) => void;
};

const CaiGouModal = React.forwardRef((props: RechargeModalProps, ref) => {
    const [visible, setVisible] = useState(false);
    const formRef = useRef<ProFormInstance>();

    const tomorrow = moment().add(1, 'days').format('YYYY-MM-DD');

    const handleCancel = () => setVisible(false);
    const handleOk = async () => {
        const values = formRef?.current?.getFieldsFormatValue();
        if (!values.orderTime) {
            values.orderTime = tomorrow;
        }
        let fileName = values.orderTime + '_采购登记订单';
        tools.downloadFile('/api/canteen/caigou-download', values, fileName + '.xls');
        setVisible(false);
    };

    useImperativeHandle(ref, () => ({
        setVisible: (value: boolean) => {
            setVisible(value);
        },
    }));

    return (
        <Modal visible={visible} title="采购登记订单导出" onCancel={handleCancel} onOk={handleOk}>
            <ProForm layout="vertical" formRef={formRef} submitter={false} initialValues={{ orderTime: tomorrow }}>
                <ProFormDatePicker name="orderTime" label="订单时间" />
            </ProForm>
        </Modal>
    );
});
export default CaiGouModal;
