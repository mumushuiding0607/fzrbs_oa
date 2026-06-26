import { PageContainer } from '@ant-design/pro-components';
import { Tabs } from 'antd';
import React from 'react';
import GroupTab from './GroupTab';

const Index: React.FC = () => {

    return (
        <PageContainer
            header={{
                breadcrumb: {},
            }}
        >
            <Tabs type="card">
                <Tabs.TabPane tab="商品分组管理" key="1" forceRender>
                    <GroupTab></GroupTab>
                </Tabs.TabPane>
            </Tabs>
        </PageContainer>
    );
};

export default Index;
