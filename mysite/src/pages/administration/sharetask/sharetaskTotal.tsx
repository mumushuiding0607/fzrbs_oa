import type { ActionType, ProColumns } from '@ant-design/pro-components';
import { ProTable } from '@ant-design/pro-components';
import { useRef, useState } from 'react';
import { totall, getItems } from './service';
import styles from './styles.less';
import moment from 'moment';
import { Card, Descriptions, Modal } from 'antd';

const SharetaskTotal: React.FC = () => {
    const actionRef = useRef<ActionType>();
    const [taskNum, setTaskNum] = useState<any>(0);
    const [shareTotal, setShareTotal] = useState<any>(0);
    const [clickTotal, setClickTotal] = useState<any>(0);
    const actionShareTaskRef = useRef<ActionType>();
    const [showShareTotal, setShowShareTotal] = useState<boolean>(false);
    const [viewID, setViewID] = useState<any>(0);
    const [isShareTaskTitle, setIsShareTaskTitle] = useState<any>('任务');
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
            title: '标题',
            dataIndex: 'title',
        },
        {
            title: '有效转发',
            dataIndex: 'sharenum',
            hideInSearch: true,
        },
        {
            title: '阅读数',
            dataIndex: 'clicknum',
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
        {
            title: '创建时间',
            dataIndex: 'inserttime',
            hideInSearch: true,
        },
        {
            title: '操作',
            valueType: 'option',
            key: 'option',
            render: (text, record) => [
                <a
                    key="view"
                    onClick={() => {
                        setTimeout(() => {
                            setShowShareTotal(true);
                            setViewID(record.id);
                            setIsShareTaskTitle('分享任务：' + record.title);
                            actionShareTaskRef.current?.reload?.();
                        }, 100);
                    }}
                >
                    查看
                </a>,
            ],
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
                request={async (params) => {
                    document.body.scrollTop = document.documentElement.scrollTop = 0;
                    const data = totall(params);
                    setTaskNum((await data).total);
                    setShareTotal((await data).shareTotal);
                    setClickTotal((await data).clickTotal);
                    return data;
                }}
                tableExtraRender={() => (
                    <Card className={styles.taskTotalInfo}>
                        <Descriptions size="small" column={3}>
                            <Descriptions.Item label="任务数">
                                {taskNum}
                            </Descriptions.Item>
                            <Descriptions.Item label="有效转发">
                                {shareTotal}
                            </Descriptions.Item>
                            <Descriptions.Item label="阅读数">
                                {clickTotal}
                            </Descriptions.Item>
                        </Descriptions>
                    </Card>
                )}
                rowKey="id"
                search={{
                    labelWidth: 'auto',
                }}
                dateFormatter="string"
                headerTitle="任务统计列表"
            />
            <Modal
                className={styles.clickTable}
                title={isShareTaskTitle}
                visible={showShareTotal}
                onCancel={() => {
                    setShowShareTotal(false);
                }}
                destroyOnClose={true}
                footer={null}
            >

                <ProTable<any, any>
                    className={styles.stab}
                    columns={[
                        {
                            title: '分享人',
                            dataIndex: 'name',
                        },
                        {
                            title: '部门',
                            dataIndex: 'departmentname',
                        },
                        {
                            title: '阅读量',
                            dataIndex: 'clicknum',
                            sorter: (a, b) => a.clicknum - b.clicknum,
                            hideInSearch: true,
                        },
                    ]}
                    actionRef={actionShareTaskRef}
                    cardBordered
                    request={(params) => {
                        document.body.scrollTop = document.documentElement.scrollTop = 0;
                        params.id = viewID;
                        return getItems(params);
                    }}
                    rowKey="userid"
                    search={{
                        span: 6,
                        labelWidth: 'auto',
                    }}
                    dateFormatter="string"
                    headerTitle="用户分享数据列表"
                />
            </Modal>
        </>
    );
};
export default SharetaskTotal;