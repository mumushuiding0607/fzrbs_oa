import React, { useState } from 'react';
import ListTable from './components/truckOrder';
import StaticsTable from './components/statistics';
import DriverStatus from './components/driverStatus';

import {
  PageContainer
} from '@ant-design/pro-components';




const Index: React.FC = () => {
  const tabContent = {
    truckOrder: <ListTable />,
    statics: <StaticsTable />,
    driverStatus: <DriverStatus />,
  };
  const [tabKeyState, setTabKeyState] = useState<string>('truckOrder');

  return (
    <PageContainer
      fixedHeader
      header={{ title: '车辆管理', breadcrumb: {} }}

      tabList={[
        {
          tab: '派车订单',
          key: 'truckOrder'
        },
        {

          tab: '月统计',
          key: 'statics',
        },
        {
          tab: '司机去向',
          key: 'driverStatus',
        },


      ]}

      onTabChange={(key) => {
        console.log('key', key);
        setTabKeyState(key);
      }}
    >
      {tabContent[tabKeyState]}

    </PageContainer>
  );
};

export default Index;
