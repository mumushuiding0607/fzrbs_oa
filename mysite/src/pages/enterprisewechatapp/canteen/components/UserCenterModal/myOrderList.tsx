import { Card, List, message, Modal, Tag, Tooltip } from 'antd';
import React, { useImperativeHandle, useRef } from 'react';
import { useState } from 'react';
import { myOrder, shareOrder, sellOrder, closeOrder } from '../../service';
import { useRequest } from 'umi';
import styles from './style.less';
import {
  PoweroffOutlined,
  ShareAltOutlined,
  SyncOutlined,
  WechatOutlined,
} from '@ant-design/icons';
import DepartmentModal from '@/components/DepartmentModal';

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
      <p
        dangerouslySetInnerHTML={{
          __html: orderInfo.orderinfo.reduce((pre, orderInfo) => pre + orderInfo + '<br>', ''),
        }}
        style={{ height: 100, maxHeight: 100, overflow: 'auto' }}
      />
      <p>订单时间：{orderInfo.ordertime}</p>
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

export type MyOrderListProps = {
  reloadUserData?: () => void;
  backTop?: () => void;
};

const MyOrderList = React.forwardRef((props: MyOrderListProps, ref) => {
  const pageSize = 20;
  const [currentRow, setCurrentRow] = useState<any>();
  const [action, setAction] = useState<string>('');
  const [initialPage, setInitialPage] = useState<boolean>(true);
  const [userParams, setUserParams] = useState<object>({});
  const modalRef = useRef<any>();

  const { data, loading, run } = useRequest(
    async (params: any) => {
      if (initialPage) {
        params.pageSize = pageSize;
        setInitialPage(false);
      }
      setUserParams(params);
      return myOrder(params);
    },
    {
      paginated: true,
      // manual: true,
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
      if (props.backTop) {
        props.backTop();
      }
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
      if (values.orderTime && values.orderTime.length > 0) {
        params.orderTime = values.orderTime.join(',');
      } else if (params.orderTime) {
        delete params.orderTime;
      }
      setUserParams(params);
      run(params);
    }
  };

  const orderAction = (item: any, action: string) => {
    if (item.status == '已使用') {
      message.warn('订单已使用无法操作');
      return;
    }
    if (action == 'close' && item.expire == 1) {
      message.warn('订单已过取消时间');
      return;
    }
    setAction(action);
    setCurrentRow(item);
    if (['sell', 'share'].includes(action)) {
      const title = action == 'share' ? '请选择代领用户' : '请选择转让用户';
      modalRef?.current.setTitle(title);
      modalRef?.current.setVisible(true);
    } else {
      Modal.confirm({
        title: '取消',
        content: '确定要取消吗？',
        okText: '确认',
        cancelText: '取消',
        onOk: async () => {
          const result = await closeOrder({ orderId: item.id });
          if (result.errorMessage) {
            message.warn(result.errorMessage);
            return;
          }
          if (props.reloadUserData) {
            props.reloadUserData();
          }
          message.success('订单取消成功');
          searchData({}, 1, 1);
        },
      });
    }
  };

  const onDepartmentModalOk = async (value) => {
    if (action == 'share') {
      const result = await shareOrder({ orderId: currentRow.id, shareUserId: value });
      if (result.errorMessage) {
        message.warn(result.errorMessage);
        return;
      }
      message.success('订单代领已经成功发送给' + result.shareUserName);
    } else if (action == 'sell') {
      const result = await sellOrder({ orderId: currentRow.id, shareUserId: value });
      if (result.errorMessage) {
        message.warn(result.errorMessage);
        return;
      }
      message.success('订单转让已经成功发送给' + result.shareUserName + ',请等待对方接受');
    }
  };

  const createActions = (item) => {
    const actions = [
      <Tooltip title="订单转让" key="menu1">
        <SyncOutlined onClick={() => orderAction(item, 'sell')} />
      </Tooltip>,
      <Tooltip title="订单代领" key="menu2">
        <ShareAltOutlined onClick={() => orderAction(item, 'share')} />
      </Tooltip>,
      <Tooltip title="订单取消" key="menu3">
        <PoweroffOutlined onClick={() => orderAction(item, 'close')} />
      </Tooltip>,
    ];
    return actions;
  };

  useImperativeHandle(ref, () => ({
    search: (values: any) => {
      searchData(values, 1, 0);
    },
    reload: () => {
      if (userParams.current == 1) {
        run(userParams);
      }
    },
  }));

  return (
    <>
      <div className={styles.filterCardList}>
        <List
          rowKey="id"
          grid={{
            gutter: 16,
            xs: 1,
            sm: 2,
            md: 2,
            lg: 2,
            xl: 4,
            xxl: 4,
          }}
          loading={loading}
          dataSource={list}
          pagination={paginationProps}
          renderItem={(item, index) => (
            <List.Item key={item.id}>
              <Card hoverable bodyStyle={{ paddingBottom: 20 }} actions={createActions(item)}>
                <Card.Meta title={'取餐日期：' + item.menudate + ' ' + item.type} />
                <div className={styles.cardItemContent}>
                  <CardInfo orderInfo={item} />
                </div>
              </Card>
            </List.Item>
          )}
        />
      </div>
      <DepartmentModal ref={modalRef} multiple={false} onOk={onDepartmentModalOk} />
    </>
  );
});
export default MyOrderList;
