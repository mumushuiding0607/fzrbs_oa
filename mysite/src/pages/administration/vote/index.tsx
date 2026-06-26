import { PageContainer } from '@ant-design/pro-components';
import { Tabs } from 'antd';
import ItemsManager from './itemsManager';
import ItemsAdmin from './itemsAdmin';
import { useEffect, useState } from 'react';
import { accessTab } from './service';

const Canteen: React.FC = () => {
    const [tabs, setTabs] = useState<string>('vote-items');
    // const onChange = (key: string) => {
    //     // console.log(key);
    //     // setTabs(key);
    // };
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
                {tabs.includes('vote-items') && (
                    <Tabs.TabPane tab="评议项目管理" key="vote-items">
                        <ItemsManager />
                    </Tabs.TabPane>
                )}
                {tabs.includes('vote-admin') && (
                    <Tabs.TabPane tab="评议管理员设置" key="vote-admin">
                        <ItemsAdmin />
                    </Tabs.TabPane>
                )}
            </Tabs>
        </PageContainer>
    );
};

export default Canteen;
