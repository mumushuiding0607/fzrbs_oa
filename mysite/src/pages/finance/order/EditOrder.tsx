import { Divider, Spin } from 'antd';
import React, { useEffect, useState } from 'react';

import AddOrder from './AddOrder';
import AdvitemList from './AdvitemList';

interface EditOrderProps {
  data?: any;
  onChange?: () => void;
}

const EditOrder: React.FC<EditOrderProps> = ({ data, onChange }) => {
  const [orderData, setOrderData] = useState<any>(data||{});
  const [loading, setLoading] = useState(false);

  // 加载订单和广告数据
  useEffect(() => {

  }, [data]);

  // 处理订单保存成功
  const handleOrderSuccess = () => {
    onChange?.();
  };

  if (loading) {
    return (
      <div style={{ textAlign: 'center', padding: '50px 0' }}>
        <Spin size="large" />
      </div>
    );
  }

  return (
    <div style={{ maxHeight: '80vh', overflow: 'auto' }}>
      {/* 上半部分：订单信息 */}
      <div style={{ marginBottom: '16px' }}>
        <AddOrder
          data={orderData}
        />
      </div>

      <Divider style={{ margin: '16px 0' }} />

      {/* 下半部分：广告列表 */}
      <div>
        
        <AdvitemList
          order={orderData}
        />
      </div>
    </div>
  );
};

export default EditOrder;
