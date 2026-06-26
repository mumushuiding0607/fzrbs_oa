import { ProForm, ProFormDatePicker, ProFormSelect } from '@ant-design/pro-components';
import { Card, Form, List, Modal } from 'antd';
import React, { useEffect, useImperativeHandle } from 'react';
import { useState } from 'react';
import { useRequest } from 'umi';
import { menuType, orderSum } from '../list/service';

export type RechargeModalProps = {
  onOk?: (values: object) => void;
};

const OrderSumModal = React.forwardRef((props: RechargeModalProps, ref) => {
  const [visible, setVisible] = useState(false);
  const [orderMenuType, setOrderMenuType] = useState<any>({});

  const handleCancel = () => setVisible(false);
  const handleOk = async () => {
    setVisible(false);
  };

  const { data, loading, run } = useRequest((params: any) => {
    return orderSum(params);
  });

  const list = data?.data || [];
  const orderDay = data?.orderDay || '';
  const orderTotalPrice = data?.orderTotalPrice || '0';
  const orderTotalNum = data?.orderTotalNum || '0';

  useImperativeHandle(ref, () => ({
    setVisible: (value: boolean) => {
      run({});
      setVisible(value);
    },
  }));

  useEffect(() => {
    menuType().then((res) => {
      setOrderMenuType(res.data);
    });
  }, []);

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
          <Form.Item label="每日订单统计" />
          <Form.Item
            label={
              orderDay +
              '的统计数据：' +
              '订单数(' +
              orderTotalNum.toString() +
              ')份，订单金额(' +
              orderTotalPrice.toString() +
              ')元。搜索'
            }
          />
          <ProFormDatePicker name="orderTime" label="订单时间" />
          <ProFormSelect
            name="menuType"
            label=""
            valueEnum={orderMenuType}
            fieldProps={{
              mode: 'multiple',
            }}
            placeholder="请选择菜单分类"
          />
        </ProForm>
      }
      onCancel={handleCancel}
      onOk={handleOk}
      width="100vw"
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
              {item.total}{item.type1 != '' && ',午餐(' + item.type1.toString() + ')'}
              {item.type2 != '' && ',晚餐(' + item.type2.toString() + ')'}
              {item.type3 != '' && ',早餐(' + item.type3.toString() + ')'}<br />
              订单数({item.order})
            </Card>
          </List.Item>
        )}
      />
    </Modal>
  );
});
export default OrderSumModal;
