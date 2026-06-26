import { Button, Card, Drawer, Form, List, message, Modal, Tag, Tooltip } from 'antd';
import React, { useEffect, useRef, useState } from 'react';
import { orders, chargeBack, menuType } from './components/list/service';
import styles from './style.less';
import {
  BarChartOutlined,
  ExportOutlined,
  MenuOutlined,
  WalletOutlined,
  WechatOutlined,
} from '@ant-design/icons';
import { useModel, useRequest } from 'umi';
import StandardFormRow from '@/components/StandardFormRow';
import TagSelect from '@/components/TagSelect';
import {
  ProForm,
  ProFormDateRangePicker,
  ProFormInstance,
  ProFormSelect,
  ProFormText,
} from '@ant-design/pro-components';
import OrderDownloadModal from './components/OrderDownloadModal';
import OrderSumModal from './components/OrderSumModal';
import CaiGouModal from './components/CaiGouModal';
import PeopleSumModal from './components/PeopleSumModal';

const statusColor = {
  未使用: 'green',
  已使用: 'red',
  已取消: 'grey',
};

const CardInfo: React.FC<{
  orderInfo: any;
}> = ({ orderInfo }) => (
  <div className={styles.cardInfo}>
    <div>
      <p>姓名：{orderInfo.realname}</p>
      <p>手机号：{orderInfo.mobile}</p>
      <p>订单时间：{orderInfo.ordertime}</p>
      <p>
        用餐日期：{orderInfo.menudate} {orderInfo.type}
      </p>
      <p>
        订单金额：￥{orderInfo.ordermoney}
        {orderInfo.wxpay == 1 && (
          <Tooltip key="usedWechatPay" title="使用微信余额支付">
            <WechatOutlined className="weixin" />
          </Tooltip>
        )}
      </p>
      <p>
        订单状态：<Tag color={statusColor[orderInfo.status]}>{orderInfo.status}</Tag>
      </p>
    </div>
  </div>
);

const OrderTab: React.FC = () => {
  const pageSize = 20;
  const [currentRow, setCurrentRow] = useState<any>();
  const [initialPage, setInitialPage] = useState<boolean>(true);
  const [userParams, setUserParams] = useState<object>({});
  const formRef = useRef<ProFormInstance>();
  const modalRef = useRef<any>();
  const modalRef1 = useRef<any>();
  const modalRef2 = useRef<any>();
  const modalRef3 = useRef<any>();
  const { initialState } = useModel('@@initialState');
  const { currentUser } = initialState ?? {};
  const [orderMenuType, setOrderMenuType] = useState<any>({});

  const { data, loading, run } = useRequest(
    (params: any) => {
      if (initialPage) {
        params.pageSize = pageSize;
        setInitialPage(false);
      }
      setUserParams(params);
      return orders(params);
    },
    {
      paginated: true,
    },
  );
  const list = data?.data || [];
  const total = data?.total || 0;
  const current = data?.current || 1;
  const paginationProps = {
    showTotal: (total, range) => `第  ${range[0]}-${range[1]} 条/总共 ${total} 条`,
    showSizeChanger: true,
    showQuickJumper: true,
    defaultPageSize: pageSize,
    total: parseInt(total),
    current: parseInt(current),
    onChange: (page: number, size: number) => {
      document.body.scrollTop = document.documentElement.scrollTop = 0;
      let params = userParams;
      params.current = page;
      params.pageSize = size;
      run(params);
    },
  };

  const searchData = (values: object, flag: number, refresh: number) => {
    let params = userParams;
    if (refresh == 0) {
      params.current = 1;
    }
    let autoSearch = false;
    if (values.status && values.status.length > 0) {
      params.status = values.status.join(',');
      autoSearch = true;
    } else if (params.status) {
      autoSearch = true;
      delete params.status;
    }
    if (values.payType && values.payType.length > 0) {
      params.payType = values.payType.join(',');
      autoSearch = true;
    } else if (params.payType) {
      autoSearch = true;
      delete params.payType;
    }
    if (autoSearch || flag == 1) {
      if (values.name && values.name.toString().trim() != '') {
        params.name = values.name;
      } else if (params.name) {
        delete params.name;
      }
      if (values.mobile && values.mobile.toString().trim() != '') {
        params.mobile = values.mobile;
      } else if (params.mobile) {
        delete params.mobile;
      }
      if (values.keyword && values.keyword.toString().trim() != '') {
        params.keyword = values.keyword;
      } else if (params.keyword) {
        delete params.keyword;
      }
      if (values.menuType && values.menuType.toString().trim() != '') {
        params.menuType = values.menuType;
      } else if (params.menuType) {
        delete params.menuType;
      }
      if (values.orderTime && values.orderTime.length > 0) {
        params.orderTime = values.orderTime.join(',');
      } else if (params.orderTime) {
        delete params.orderTime;
      }
      setUserParams(params);
      run(params);
    }
  };

  const tagSelectChange = (values) => {
    searchData(formRef?.current?.getFieldsFormatValue(), 0, 0);
  };

  const createActions = (item) => {
    const actions = [
      <Tooltip title="菜单" key="menu">
        <MenuOutlined onClick={() => setCurrentRow(item)} />
      </Tooltip>,
    ];

    if (currentUser?.access == 'admin') {
      actions.push(
        <Tooltip key="chargeBack" title="退单">
          <WalletOutlined
            onClick={async () => {
              if (item.status != '未使用') {
                message.warn('已使用或已取消订单，无法使用此操作！');
                return;
              }
              if (item.expire == 1) {
                message.warn('已过期订单，无法使用此操作！');
                return;
              }
              Modal.confirm({
                title: '系统提示',
                content: '确定要取消订单吗？',
                okText: '确认',
                cancelText: '取消',
                onOk: async () => {
                  const result = await chargeBack({ id: item.id });
                  if (result?.success) {
                    if (result?.errorMessage && result.errorMessage != '') {
                      message.warn(result.errorMessage);
                    } else {
                      message.success('订单取消成功');
                      searchData(formRef?.current?.getFieldsFormatValue(), 1, 1);
                    }
                  }
                },
              });
            }}
          />
        </Tooltip>,
      );
    }
    return actions;
  };

  useEffect(() => {
    menuType().then((res) => {
      setOrderMenuType(res.data);
    });
  }, []);

  return (
    <div className={styles.filterCardList}>
      <Card bordered={false}>
        <ProForm
          layout="inline"
          autoFocusFirstInput={false}
          grid={true}
          rowProps={{
            gutter: [16, 16],
          }}
          formRef={formRef}
          onFinish={async (values) => {
            searchData(values, 1, 0);
          }}
          onReset={async (values) => {
            searchData(values, 1, 0);
          }}
          submitter={{
            searchConfig: { submitText: '查询' },
            render: (props, doms) => {
              return [
                ...doms,
                <Button
                  htmlType="button"
                  key="export"
                  onClick={() => {
                    modalRef?.current.setVisible(true);
                  }}
                >
                  <ExportOutlined />
                  订单导出
                </Button>,
                <Button
                  htmlType="button"
                  key="ordersum"
                  onClick={() => {
                    modalRef1?.current.setVisible(true);
                  }}
                >
                  <BarChartOutlined />
                  每日订单统计
                </Button>,
                <Button
                  htmlType="button"
                  key="export_cg"
                  onClick={() => {
                    modalRef2?.current.setVisible(true);
                  }}
                >
                  <ExportOutlined />
                  采购登记订单导出
                </Button>,
                <Button
                  htmlType="button"
                  key="peoplesum"
                  onClick={() => {
                    modalRef3?.current.setVisible(true);
                  }}
                >
                  <BarChartOutlined />
                  每日用餐人数统计
                </Button>,
              ];
            },
          }}
        >
          <ProFormSelect
            name="menuType"
            label=""
            valueEnum={orderMenuType}
            fieldProps={{
              mode: 'multiple',
            }}
            placeholder="请选择订单分类"
            colProps={{ md: 12, xl: 4 }}
          />
          <ProFormText colProps={{ md: 12, xl: 4 }} name="name" label="姓名" />
          <ProFormText colProps={{ md: 12, xl: 6 }} name="keyword" label="菜单关键字" />
          <ProFormDateRangePicker colProps={{ md: 12, xl: 8 }} name="orderTime" label="订单时间" />

          <StandardFormRow title="订单状态" last style={{ paddingBottom: 0 }}>
            <Form.Item name="status">
              <TagSelect expandable={false} onChange={tagSelectChange}>
                <TagSelect.Option value="0">未使用</TagSelect.Option>
                <TagSelect.Option value="1">已使用</TagSelect.Option>
                <TagSelect.Option value="2">已取消</TagSelect.Option>
              </TagSelect>
            </Form.Item>
          </StandardFormRow>
          <StandardFormRow title="支付方式" last style={{ paddingBottom: 0 }}>
            <Form.Item name="payType">
              <TagSelect expandable={false} onChange={tagSelectChange}>
                <TagSelect.Option value="0">餐补余额</TagSelect.Option>
                <TagSelect.Option value="1">微信支付余额</TagSelect.Option>
              </TagSelect>
            </Form.Item>
          </StandardFormRow>
        </ProForm>
      </Card>
      <br />
      <List<any>
        rowKey="id"
        grid={{
          gutter: 16,
          xs: 1,
          sm: 2,
          md: 3,
          lg: 3,
          xl: 4,
          xxl: 4,
        }}
        loading={loading}
        dataSource={list}
        pagination={paginationProps}
        renderItem={(item) => (
          <List.Item key={item.id}>
            <Card hoverable bodyStyle={{ paddingBottom: 20 }} actions={createActions(item)}>
              <Card.Meta title={'订单号：' + item.orderid} />
              <div className={styles.cardItemContent}>
                <CardInfo orderInfo={item} />
              </div>
              <Drawer
                width="100%"
                title="菜单信息"
                placement="right"
                closable={true}
                getContainer={false}
                visible={currentRow == item}
                onClose={() => {
                  setCurrentRow(undefined);
                }}
                style={{ position: 'absolute' }}
              >
                <div
                  dangerouslySetInnerHTML={{
                    __html: item.orderinfo.reduce((pre, item) => pre + item + '<br>', ''),
                  }}
                />
              </Drawer>
            </Card>
          </List.Item>
        )}
      />
      <OrderDownloadModal ref={modalRef} />
      <OrderSumModal ref={modalRef1} />
      <CaiGouModal ref={modalRef2} />
      <PeopleSumModal ref={modalRef3} />
    </div>
  );
};

export default OrderTab;
