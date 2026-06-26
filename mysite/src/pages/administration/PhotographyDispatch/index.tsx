
import React, { useRef, useState } from 'react';
import ListTable from './components/list';
import StatisticsTable from './components/statistics';
import ReportTable from './components/reportList';

import {
  PageContainer
} from '@ant-design/pro-components';

const Index: React.FC = () => {
  const tabContent = {
    list: <ListTable />,
    statistics: <StatisticsTable />,
    report: <ReportTable />,
    // statics: <StaticsTable />,
    // driverStatus: <DriverStatus />,
  };
  const [tabKeyState, setTabKeyState] = useState<string>('list');

  return (
    <PageContainer
      fixedHeader
      header={{ title: '摄影派工', breadcrumb: {} }}

      tabList={[
        {
          tab: '派工清单',
          key: 'list'
        },
        {

          tab: '月度统计',
          key: 'statistics',
        },
        {
          tab: '记者去向',
          key: 'report',
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
