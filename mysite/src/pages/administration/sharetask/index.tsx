import { PageContainer } from '@ant-design/pro-components';
import { Tabs } from 'antd';
import SharetaskList from './sharetaskList';
import SharetaskAdmin from './sharetaskAdmin';
import SharetaskUtotal from './sharetaskUtotal';
import SharetaskTotal from './sharetaskTotal';
import { useEffect, useState } from 'react';
import { accessTab } from './service';

const Canteen: React.FC = () => {
    const [tabs, setTabs] = useState<string>('share-task-list');
    useEffect(() => {
        accessTab().then((res) => {
            setTabs(res.data);
        });
    }, []);

    return (
        <PageContainer
            header={{
                breadcrumb: {},
            }}
        >
            <Tabs type="card">
                {tabs.includes('share-task-list') && (
                    <Tabs.TabPane tab="传播任务列表" key="share-task-list">
                        <SharetaskList />
                    </Tabs.TabPane>
                )}
                {tabs.includes('share-task-admin') && (
                    <Tabs.TabPane tab="管理员设置" key="share-task-admin">
                        <SharetaskAdmin />
                    </Tabs.TabPane>
                )}
                {tabs.includes('share-task-utotal') && (
                    <Tabs.TabPane tab="人员统计" key="share-task-utotal">
                        <SharetaskUtotal />
                    </Tabs.TabPane>
                )}
                {tabs.includes('share-task-total') && (
                    <Tabs.TabPane tab="任务统计" key="share-task-total">
                        <SharetaskTotal />
                    </Tabs.TabPane>
                )}
            </Tabs>
        </PageContainer>
    );
};

export default Canteen;
