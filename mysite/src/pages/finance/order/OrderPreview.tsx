import React, { useEffect } from 'react';
import { Modal } from 'antd';
import AdvitemList from './AdvitemList';
import Filescard from '../contract/filescard';

interface OrderPreviewProps {
  visible: boolean;
  order: any;
  onClose: () => void;
  onRefresh?: () => void;
}

const OrderPreview: React.FC<OrderPreviewProps> = ({ visible, order, onClose, onRefresh }) => {
  // 监听 visible 变化，弹窗打开时执行
  useEffect(() => {
    console.log('OrderPreview visible:', visible);
    if (visible) {
      console.log('OrderPreview opened:', order);
    }
  }, [visible, order]);

  return (
    <Modal
      
      title="订单详情"
      style={{ top: 20 }}
      width={1400}
      visible={visible}
      onCancel={onClose}
      footer={null}
      destroyOnClose
    >
      {/* 上半部分：订单信息 */}
      <div style={{ padding: '20px', borderBottom: '1px solid #eee', marginBottom: '20px' }}>
        <h3 style={{ marginTop: 0, marginBottom: '16px' }}>订单信息</h3>
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4, 1fr)', gap: '16px' }}>
          <div><strong>订单编号：</strong>{order.SYS_DOCUMENTID}</div>
          <div><strong>合同编号：</strong>{order.contractserial}</div>
          <div><strong>主体：</strong>{order.partbname}</div>
          <div><strong>客户：</strong>{order.AO_Customer}</div>
          <div><strong>刊物：</strong>{order.publication}</div>
          <div><strong>业务员：</strong>{order.AO_Salesman}</div>
          <div><strong>部门：</strong>{order.AO_Org}</div>
          <div><strong>创建时间：</strong>{order.SYS_CREATED?.substring?.(0, 19)}</div>
          <div><strong>总应收款：</strong>¥{order.AO_AllMoney}</div>
          <div><strong>已收款：</strong>¥{order.AO_ReceivedMoney}</div>
          <div>
            <strong>欠款：</strong>
            <span style={{ color: order.AO_DebtMoney > 0 ? 'red' : 'inherit' }}>
              ¥{order.AO_DebtMoney}
            </span>
          </div>

        </div>
        <div style={{ marginBottom: '20px', borderBottom: '1px solid #eee', paddingBottom: '10px' }}>
            <h3 style={{ margin: 0 }}>附件</h3>
          </div>
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '16px', marginBottom: '20px' }}>
            <div><Filescard  urls={order.fileurls} mode="list"/></div>
          </div>
      </div>


    </Modal>
  );
};

export default OrderPreview;
