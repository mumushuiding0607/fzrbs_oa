import { ActionType, PageContainer, ProColumns, ProTable } from '@ant-design/pro-components';
import React, { useEffect, useRef, useState } from 'react';
import { rule, type } from './service';

const Suggest: React.FC = () => {
    const [selectedRowsState, setSelectedRows] = useState<any[]>([]);
    const actionRef = useRef<ActionType>();
    const [selectType, setSelectType] = useState<any>({});

    const columns: ProColumns<any>[] = [
        {
            title: '姓名',
            dataIndex: 'username',
        },
        {
            title: '意见建议',
            dataIndex: 'message',
            ellipsis: true,
        },
        {
            title: '类别',
            dataIndex: 'type',
            valueEnum: selectType,
        },
        {
            title: '添加时间',
            dataIndex: 'inserttime',
            valueType: 'dateRange',
            render: (_, entity) => {
                return entity.inserttime;
            },
        }
    ];

    useEffect(() => {
        type().then((res) => {
            setSelectType(res.data);
        });
    }, []);

    return (
        <PageContainer
            header={{
                breadcrumb: {},
            }}
        >
            <ProTable<any, any>
                headerTitle="意见建议列表"
                actionRef={actionRef}
                rowKey="id"
                request={(params, sorter, filter) => {
                    document.body.scrollTop = document.documentElement.scrollTop = 0;
                    return rule(params);
                }}
                columns={columns as ProColumns<any>[]}
                rowSelection={{
                    onChange: (_, selectedRows) => {
                        setSelectedRows(selectedRows);
                    },
                }}
                tableAlertRender={false}
                toolBarRender={() => [

                ]}
            />
        </PageContainer>
    );
};

export default Suggest;
