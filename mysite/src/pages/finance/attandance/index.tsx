import { PageContainer } from '@ant-design/pro-components';
import { useState } from 'react';
import CheckinDataList from './CheckinDataList';
import AttendanceList from './AttendanceList';

const AttendanceManage: React.FC = () => {
  const [activeTabKey, setActiveTabKey] = useState<string>('checkin');

  return (
    <PageContainer
      title="考勤管理"
      tabList={[
        {
          tab: '打卡异常数据',
          key: 'checkin',
        },
        {
          tab: '考勤异常审批',
          key: 'attendance',
        }
      ]}
      onTabChange={(key) => {
        setActiveTabKey(key);
      }}
    >
      {activeTabKey === 'checkin' && <CheckinDataList />}
      {activeTabKey === 'attendance' && <AttendanceList />}
    </PageContainer>
  );
};

export default AttendanceManage;
