import { Drawer, Form, Tabs } from 'antd';
import React, { useImperativeHandle, useRef } from 'react';
import { useState } from 'react';
import { account } from '../../service';
import { useModel } from 'umi';
import tools from '@/utils/tools';
import MyOrderList from './myOrderList';
import StandardFormRow from '@/components/StandardFormRow';
import TagSelect from '@/components/TagSelect';
import { ProForm, ProFormDateRangePicker, ProFormInstance } from '@ant-design/pro-components';
import RechargeLogList from './rechargeLogList';

const UserCenterModal = React.forwardRef((props, ref) => {
  const [visible, setVisible] = useState(false);
  const { initialState } = useModel('@@initialState');
  const { currentUser } = initialState;
  const [accountData, setAccountData] = useState<object>({});
  const [activeTabKey, setActiveTabKey] = useState<string>('myOrder');
  const formRef = useRef<ProFormInstance>();
  const myOrderListRef = useRef();

  const handleCancel = () => {
    setVisible(false);
  };

  const onTabChange = (key: string) => {
    setActiveTabKey(key);
  };

  const loadUserData = () => {
    account().then((data: any) => {
      if (data.data) {
        setAccountData(data.data);
      }
    });
  };

  const backTop = () => {
    const tags = document.getElementsByClassName('ant-drawer-body');
    if (tags.length > 0) {
      tags[0].scrollTo(0, 0);
    }
  };

  useImperativeHandle(ref, () => ({
    setVisible: (value: boolean) => {
      loadUserData();
      myOrderListRef?.current?.reload();
      setVisible(value);
    },
  }));

  const tagSelectChange = (values) => {
    myOrderListRef?.current.search(formRef?.current?.getFieldsFormatValue());
  };

  return (
    <Drawer
      width="100vw"
      visible={visible}
      onClose={handleCancel}
      title={
        <>
          <span style={{ display: 'inline-block', marginRight: 20 }}>用户中心</span>
          <img
            src={accountData.avatar ? accountData.avatar : '/images/default_avatar.png'}
            width={40}
            height={40}
            style={{ borderRadius: 20, marginRight: 5 }}
          />
          <span style={{ display: 'inline-block', marginLeft: 20 }}>
            姓名：{accountData.username}
          </span>
          <span style={{ display: 'inline-block', marginLeft: 20 }}>
            部门：{accountData.departmentname}
          </span>
          <span style={{ display: 'inline-block', marginLeft: 20 }}>
            餐补充值余额：{tools.formatCurrency(accountData.balance / 100)}元
          </span>
          <span style={{ display: 'inline-block', marginLeft: 20 }}>
            微信充值余额：{tools.formatCurrency(accountData.weixinbalance / 100)}元
          </span>
        </>
      }
    >
      <Tabs
        tabPosition="top"
        onChange={onTabChange}
        activeKey={activeTabKey}
        tabBarExtraContent={
          <>
            <div style={{ display: activeTabKey == 'myOrder' ? 'block' : 'none' }}>
              <ProForm
                layout="inline"
                autoFocusFirstInput={false}
                grid={true}
                rowProps={{
                  gutter: [16, 16],
                }}
                formRef={formRef}
                submitter={{
                  searchConfig: { submitText: '查询' },
                }}
                onFinish={async (values) => {
                  myOrderListRef?.current.search(formRef?.current?.getFieldsFormatValue());
                }}
                onReset={async (values) => {
                  myOrderListRef?.current.search(formRef?.current?.getFieldsFormatValue());
                }}
              >
                <StandardFormRow title="订单状态" last style={{ paddingBottom: 0 }}>
                  <Form.Item name="status">
                    <TagSelect expandable={false} onChange={tagSelectChange}>
                      <TagSelect.Option value="0">未使用</TagSelect.Option>
                      <TagSelect.Option value="1">已使用</TagSelect.Option>
                    </TagSelect>
                  </Form.Item>
                </StandardFormRow>
                <ProFormDateRangePicker
                  colProps={{ md: 12, xl: 12 }}
                  name="orderTime"
                  label="订单时间"
                />
              </ProForm>
            </div>
          </>
        }
      >
        <Tabs.TabPane tab="我的订单" key="myOrder" forceRender={true}>
          <MyOrderList ref={myOrderListRef} reloadUserData={loadUserData} backTop={backTop} />
        </Tabs.TabPane>
        <Tabs.TabPane tab="充值日志" key="rechargeLog">
          <RechargeLogList backTop={backTop} />
        </Tabs.TabPane>
      </Tabs>
    </Drawer>
  );
});
export default UserCenterModal;
