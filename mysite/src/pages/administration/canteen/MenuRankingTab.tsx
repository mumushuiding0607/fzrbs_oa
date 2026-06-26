import { ActionType, ProColumns, ProFormDateRangePicker, ProTable } from "@ant-design/pro-components";
import { Button } from "antd";
import { useRef, useState } from "react";
import { menuRanking } from './components/list/service';

const MenuRankingTab: React.FC = () => {
    const actionRef = useRef<ActionType>();
    const [searchFlag, setSearchFlag] = useState<boolean>(false);

    const columns: ProColumns<any>[] = [
        {
            title: '时间',
            dataIndex: 'ordertime',
            key: 'ordertime',
            valueType: 'dateRange',
            hideInTable: true,
            render: (_, entity) => {
                return '';
            },
        },
        {
            title: '菜品名称',
            dataIndex: 'name',
            hideInSearch: true,
        },
        {
            title: '点菜次数',
            dataIndex: 'sum',
            hideInSearch: true,
        },
        {
            title: '出现次数',
            dataIndex: 'times',
            hideInSearch: true,
        },
        {
            title: '平均值',
            dataIndex: 'average',
            hideInSearch: true,
        },
    ];


    return (
        <>
            <ProTable<any, any>
                headerTitle="菜品排行情况列表"
                actionRef={actionRef}
                rowKey="id"
                search={{
                    optionRender: (searchConfig, { form }, dom) => [
                        <Button
                            key="resetText"
                            onClick={() => {
                                setSearchFlag(false);
                                form?.resetFields();
                                form?.submit();
                            }}
                        >
                            {searchConfig.resetText}
                        </Button>,
                        <Button
                            key="searchText"
                            type="primary"
                            onClick={() => {
                                setSearchFlag(true);
                                form?.submit();
                            }}
                        >
                            {searchConfig.searchText}
                        </Button>,

                    ],
                }}
                request={(params, sorter, filter) => {
                    if (searchFlag) {
                        params.search = 1;
                    }
                    return menuRanking(params);
                }}
                columns={columns}
                toolBarRender={() => []}
                pagination={{ pageSize: 10000, showSizeChanger: false }}
            />
        </>
    );
};

export default MenuRankingTab;