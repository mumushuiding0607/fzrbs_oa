import type { ActionType, ProColumns } from '@ant-design/pro-components';
import { ProTable } from '@ant-design/pro-components';
import { useRef, useState } from 'react';
import { uTotall } from './service';
import styles from './styles.less';
import moment from 'moment';

const SharetaskUtotal: React.FC = () => {
    const actionRef = useRef<ActionType>();

    // 初始化 年份 月份 S
    const _year = moment().year();
    const _yearArr = [];
    for (let i = 2020; i < _year + 1; i++) {
        _yearArr.push({
            text: i,
        })
    }

    const _month = moment().month() + 1;
    const _monthArr = [];
    for (let j = 1; j < 13; j++) {
        _monthArr.push({
            text: j,
        })
    }
    // 初始化 年份 月份 E

    const columns: ProColumns<any>[] = [
        {
            title: '姓名',
            dataIndex: 'name',
            hideInSearch: true,
        },
        {
            title: '有效转发',
            dataIndex: 'task',
            hideInSearch: true,
        },
        {
            title: '阅读数',
            dataIndex: 'clicks',
            hideInSearch: true,
        },
        {
            title: '年份',
            dataIndex: 'year',
            initialValue: _year.toString(),
            formItemProps: { label: '年份' }, //修改查询表单的label值
            valueType: 'select',
            valueEnum: () => {
                const yearOption = {};
                _yearArr.forEach((_yearItem) => {
                    yearOption[_yearItem.text] = _yearItem.text;
                })
                return yearOption;
            },
        },
        {
            title: '月份',
            dataIndex: 'month',
            formItemProps: { label: '月份' }, //修改查询表单的label值
            initialValue: _month.toString(),
            valueType: 'select',
            valueEnum: () => {
                const monthOption = {};
                _monthArr.forEach((_monthItem) => {
                    monthOption[_monthItem.text] = _monthItem.text;
                })
                return monthOption;
            },
        },
    ];

    // 工具栏列设置不勾选某个列
    const [columnsStateMap, setColumnsStateMap] = useState({ month: { show: false }, year: { show: false } });

    return (
        <>
            <ProTable<any, any>
                className={styles.stab}
                columns={columns}
                columnsState={{
                    value: columnsStateMap,
                    onChange: setColumnsStateMap,
                }}
                actionRef={actionRef}
                cardBordered
                request={(params) => {
                    document.body.scrollTop = document.documentElement.scrollTop = 0;
                    return uTotall(params);
                }}
                rowKey="id"
                search={{
                    labelWidth: 'auto',
                }}
                dateFormatter="string"
                headerTitle="人员统计列表"
            />




        </>
    );
};
export default SharetaskUtotal;