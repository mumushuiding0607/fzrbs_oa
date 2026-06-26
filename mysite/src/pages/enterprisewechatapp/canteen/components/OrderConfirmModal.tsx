import { Alert, Avatar, Button, List, message, Modal } from 'antd';
import React, { useImperativeHandle, useRef } from 'react';
import { useState } from 'react';
import {
  ProForm,
  ProFormDatePicker,
  ProFormInstance,
  ProFormSelect,
} from '@ant-design/pro-components';
import tools from '@/utils/tools';
import styles from '../../style.less';
import moment from 'moment';
import { saveOrder } from '../service';
import { useModel } from 'umi';

export type OrderConfirmModalProps = {
  configData: object;
  menus: object;
  typeId: number;
  onOk?: () => void;
};

const OrderConfirmModal = React.forwardRef((props: OrderConfirmModalProps, ref) => {
  const { configData, menus, typeId } = props;
  const [visible, setVisible] = useState(false);
  const [loading, setLoading] = useState(false);
  const formRef = useRef<ProFormInstance>();
  const [responseState, setResponseState] = useState<API.ErrorResponse>();
  const { initialState } = useModel('@@initialState');
  const { currentUser } = initialState;

  const useTime: object[] = [];
  const timeInterval: object[] = [];
  if (configData?.useTime && [1, 2].includes(typeId)) {
    configData.useTime[typeId].forEach((element) => {
      useTime.push({ label: element, value: element });
    });
  }
  if (configData?.timeInterval) {
    configData.timeInterval.forEach((element) => {
      timeInterval.push({ label: element, value: element });
    });
  }

  const handleCancel = () => {
    setResponseState(undefined);
    setLoading(false);
    setVisible(false);
  };

  const handleOk = async () => {
    setResponseState(undefined);
    let result;
    const values = formRef?.current?.getFieldsFormatValue();
    if (!values.paytype) {
      values.paytype = 0;
    }
    if (!values.menudate) {
      values.menudate = [5].includes(typeId)
        ? moment(configData.today).add(1, 'days').format('YYYY-MM-DD')
        : Object.values(menus)[0].menudate1;
    }
    if (!values.typeid) {
      values.typeid = typeId;
    }
    if ([1, 2].includes(typeId) && !values.public) {
      values.public = '11:50';
    }
    if ([5].includes(typeId)) {
      if (values.menudate <= configData.today) {
        result = { errorCode: '0000', errorMessage: '取餐日期选择错误', success: true };
        setResponseState({ ...result });
        return;
      }
    }
    setLoading(true);
    const menuData = [];
    Object.values(menus).forEach((element, index) => {
      menuData.push({ id: element.id, count: element.count });
    });
    values.menus = menuData;
    result = await saveOrder(values);
    setLoading(false);
    if (result.errorMessage) {
      // message.warn(result.errorMessage);
      setResponseState(result);
      return;
    }
    if (props.onOk) {
      props.onOk();
    }
    message.success('订单提交成功！');
    setVisible(false);
  };

  const handleDateChange = (date: any, dateString: string) => {
    if ([5].includes(typeId)) {
      if (dateString <= configData.today) {
        const result = { errorCode: '0000', errorMessage: '取餐日期选择错误', success: true };
        setResponseState({ ...result });
      } else {
        setResponseState(undefined);
      }
    }
  };

  useImperativeHandle(ref, () => ({
    setVisible: (value: boolean) => {
      setVisible(value);
      setTimeout(() => {
        const showMenuDate = [1, 2, 3].includes(props.typeId)
          ? Object.values(props.menus)[0]?.menudate1
          : moment(props.configData.today).add(1, 'days').format('YYYY-MM-DD');
        formRef?.current?.setFieldsValue({ menudate: showMenuDate });
      }, 200);
    },
  }));

  return (
    <Modal
      visible={visible}
      title="订单确认"
      onCancel={handleCancel}
      className={styles.myPage}
      footer={[
        <span key="totlaMoney" style={{ fontSize: '18px', marginRight: 10 }}>
          总计：
          {tools.formatCurrency(
            Object.values(menus).reduce(
              (pre, item) => pre + (parseFloat(item.price) * item.count)!,
              0,
            ),
          )}
        </span>,
        <Button key="cancal" onClick={handleCancel}>
          取消
        </Button>,
        <Button key="submit" type="primary" loading={loading} onClick={handleOk}>
          提交
        </Button>,
      ]}
    >
      <ProForm layout="vertical" formRef={formRef} submitter={false}>
        {responseState?.errorCode && (
          <Alert message={responseState?.errorMessage} type="warning" closable={true} showIcon />
        )}
        <ProForm.Group>
          <ProFormDatePicker
            name="menudate"
            label="取餐日期"
            colProps={{ md: 12, xl: 8 }}
            allowClear={false}
            fieldProps={{
              disabled: ![5].includes(typeId),
              onChange: handleDateChange,
            }}
            rules={[
              {
                required: true,
                message: '请选择取餐日期！',
              },
            ]}
          />
          {![5].includes(typeId) && (
            <ProFormSelect
              name="typeid"
              label="用餐时段"
              request={async () => timeInterval}
              fieldProps={{
                defaultValue: timeInterval[typeId],
                disabled: true,
              }}
              placeholder="请选择用餐时段"
              rules={[
                {
                  required: true,
                  message: '请选择用餐时段！',
                },
              ]}
              colProps={{ md: 12, xl: 8 }}
              allowClear={false}
            />
          )}
          {useTime.length > 0 && (
            <ProFormSelect
              name="public"
              label="预约时间"
              request={async () => useTime}
              fieldProps={{
                defaultValue: configData.reporter.includes(currentUser.realname)
                  ? '11:30'
                  : '11:50',
              }}
              placeholder="请选择预约时间"
              rules={[
                {
                  required: true,
                  message: '请选择预约时间！',
                },
              ]}
              colProps={{ md: 12, xl: 8 }}
              allowClear={false}
            />
          )}
        </ProForm.Group>
        <ProFormSelect
          name="paytype"
          label="支付方式"
          request={async () => [
            { label: '餐补充值余额', value: '0' },
            { label: '微信充值余额', value: '1' },
          ]}
          fieldProps={{
            defaultValue: '0',
          }}
          placeholder="请选择支付方式"
          rules={[
            {
              required: true,
              message: '请选择支付方式！',
            },
          ]}
          colProps={{ md: 12, xl: 24 }}
          allowClear={false}
        />
      </ProForm>
      <List
        itemLayout="horizontal"
        dataSource={Object.values(menus)}
        header="订单菜单信息"
        renderItem={(item) => (
          <List.Item
            actions={[
              <span key={'menuCount' + item.id.toString()}>
                {'x' + item.count.toString()}
                <br />
                {tools.formatCurrency(parseFloat(item.price) * item.count)}
              </span>,
            ]}
          >
            <List.Item.Meta
              avatar={<Avatar src={item.image} />}
              title={item.name}
              description={tools.formatCurrency(parseFloat(item.price))}
            />
          </List.Item>
        )}
      />
    </Modal>
  );
});
export default OrderConfirmModal;
