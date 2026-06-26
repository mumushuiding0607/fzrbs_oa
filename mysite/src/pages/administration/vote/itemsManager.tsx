import { PlusOutlined } from '@ant-design/icons';
import { DragSortTable, ProCard, ProColumns, ProForm, ProFormDependency, TableDropdown } from '@ant-design/pro-components';
import { ProFormList, ProFormTreeSelect } from '@ant-design/pro-components';
import { ModalForm } from '@ant-design/pro-components';
import { DrawerForm, ProFormDateTimeRangePicker, ProFormDigit, ProFormText, ProFormTextArea } from '@ant-design/pro-components';
import { ProTable } from '@ant-design/pro-components';
import { Button, Divider, Form, List, message, Modal, Radio, Tabs } from 'antd';
import { useEffect, useRef, useState } from 'react';
import { rule, updateRule, removeRule, removeSubRule, pushRule, countRule, managerRule, departmentRule, one, sortRule, voteUserRule } from './service';
import React from 'react';
import tools from '@/utils/tools';
import styles from './styles.less';
import DepartmentTreeSelect from '@/components/DepartmentTreeSelect';
import { useModel } from 'umi';
import { NamePath } from 'antd/lib/form/interface';

const ItemsManager: React.FC = () => {
    const actionRef = useRef<any>();
    const formRef = useRef<any>();
    const [currentRow, setCurrentRow] = useState<any>();
    const [showForm, setShowForm] = useState<boolean>(false);
    const [showCount, setShowCount] = useState<boolean>(false);
    const [manager, setManager] = useState<any>(undefined);
    const [childrenId, setChildrenId] = useState<number[]>([]);
    const treeSelectRef = useRef<any>();
    const treeSelectRef1 = useRef<any>();
    const [treeData, setTreeData] = useState<any[]>([]);
    const { initialState } = useModel('@@initialState');
    const { currentUser } = initialState;
    const [isUserInfoData, setIsUserInfoData] = useState<any[]>([]);
    const [modalVisible, setModalVisible] = useState<boolean>(false);
    const formViewRef = useRef<any>();
    const [dragSortTableData, SetDragSortTableData] = useState<any[]>([]);
    const [isCountData, setIsCounData] = useState<any>();

    const [showVoteUser, setShowVoteUser] = useState<boolean>(false);
    const [isVoteUsertData, setIsVoteUserData] = useState<any>();
    const voteUser = async (item: { id: any; }, pid: any) => {
        const count = await voteUserRule({ id: item, sid: pid });
        setIsVoteUserData(count.data);
        return true;
    };


    const handleRemove = async (selectedRows: any[], deleteRow: any) => {
        const hide = message.loading('正在删除');
        if (!selectedRows && !deleteRow) return true;

        try {
            if (selectedRows.length > 0) {
                await removeRule({
                    id: selectedRows.map((row) => row.id),
                });
            } else if (deleteRow) {
                await removeRule({
                    id: [deleteRow].map((row) => row.id),
                });
            }
            hide();
            message.success('删除成功');
            return true;
        } catch (error) {
            hide();
            message.error('删除失败，请重试');
            return false;
        }
    };

    const deleteItem = (item: React.SetStateAction<any | undefined>) => {
        Modal.confirm({
            title: '删除',
            content: '确定要删除吗？',
            okText: '确认',
            cancelText: '取消',
            onOk: async () => {
                await handleRemove([], item);
                actionRef.current?.reloadAndRest?.();
            },
        });
    };

    const pushItem = (item: React.SetStateAction<any | undefined>) => {
        const state = parseInt(item.state);
        const title = state == 1 ? '撤销' : '发布';
        Modal.confirm({
            title: title,
            content: '确定要' + title + '吗？',
            okText: '确认',
            cancelText: '取消',
            onOk: async () => {
                await pushRule({
                    id: item.id,
                    state: state == 1 ? 0 : 1,
                });
                actionRef.current?.reloadAndRest?.();
            },
        });
    };

    const countItem = async (item: { id: any; }, pid: any) => {
        const count = await countRule({ id: item, sid: pid });
        setIsCounData(count.data);
        return true;
    };

    // 数据导出
    const downloadFunExcel = async (selValues: any, name: any) => {
        tools.downloadFile('/api/vote/download-excel', { selValues }, name + '.xls');
    }

    const handleView = async (_selid: number) => {
        const hide = message.loading('获取中...');
        setIsUserInfoData([]);
        try {
            const view_info = await one({ id: _selid, preview: 1 });
            if (view_info.errorCode) {
                hide();
                message.warning(view_info.errorMessage);
                return false;
            }
            if (view_info.data) {
                hide();
                setModalVisible(true);
                formViewRef?.current?.setFieldsValue({ title: view_info.title });
                const users = [];
                view_info.data.forEach((element) => {
                    users.push(element.info);
                })
                SetDragSortTableData(users);
                setIsUserInfoData(view_info.data);
            }
            return true;
        } catch (error) {
            hide();
            return false;
        }
    };
    const titleOnCell = (record: any) => {
        handleView(record.id);
    };

    const columns: ProColumns<any>[] = [
        {
            title: '序号',
            dataIndex: 'index',
            valueType: 'index',
        },
        {
            title: '项目名称',
            dataIndex: 'title',
            render: (dom, record) => [
                <a
                    key="title"
                    onClick={() => {
                        titleOnCell(record);
                    }}
                >
                    {dom}
                </a>,
            ],
        },
        {
            title: '开始时间',
            dataIndex: 'starttime',
            hideInSearch: true,
        },
        {
            title: '截止时间',
            dataIndex: 'endtime',
            width: 180,
            hideInSearch: true,
        },
        {
            disable: true,
            title: '状态',
            dataIndex: 'state',
            hideInSearch: true,
            valueEnum: {
                0: {
                    text: '未发布',
                    status: 'Default',
                },
                1: {
                    text: '已发布',
                    status: 'Success',
                }
            },
        },
        {
            title: '创建时间',
            key: 'showTime',
            dataIndex: 'inserttime',
            valueType: 'date',
            hideInSearch: true,
        },
        {
            title: '创建者',
            key: 'editor',
            dataIndex: 'editor',

            hideInSearch: true,
        },
        {
            title: '操作',
            valueType: 'option',
            key: 'option',
            render: (text, record, _) => [
                <a
                    key="edit"
                    onClick={async () => {
                        setCurrentRow(record);
                        const info = await one({ id: record.id });
                        setShowForm(true);
                        setTimeout(() => {
                            formRef?.current?.setFieldsValue(record);
                            formRef?.current?.setFieldsValue({ createTimeRanger: [record.starttime, record.endtime], remark: info?.data?.remark });
                            if (info?.data) {
                                treeSelectRef?.current.setCheckedKeys(info?.data.participant.split(','));
                                setParticipantCount(info?.data.participant.split(',').length);
                                if (manager && parseInt(manager?.invite) == 1) {
                                    treeSelectRef1?.current.setCheckedKeys(info?.data.inviter.split(','));
                                    setInviterCount(info?.data.inviter.split(',').length);
                                }
                            }
                            if (info?.sublists) {
                                formRef?.current?.setFieldsValue({ sublists: info?.sublists });
                            }
                        }, 500);
                    }}
                >
                    修改
                </a>,
                <a
                    key="delete"
                    onClick={() => {
                        deleteItem(record);
                    }}
                >
                    删除
                </a>,
                <a
                    key="push"
                    onClick={() => {
                        pushItem(record);
                    }}
                >
                    {parseInt(record.state) == 1 ? '撤销' : '发布'}
                </a>,
                <TableDropdown
                    key="actionGroup"
                    onSelect={(key) => {

                        if (key == 'download') {
                            downloadFunExcel(record.id, record.title);
                        } else if (key == 'recordVote') {
                            voteUser(record.id, 0);
                            setShowVoteUser(true);
                        } else if (key == 'count_1') {
                            countItem(record.id, 1);
                            setShowCount(true);
                        } else if (key == 'count_2') {
                            countItem(record.id, 2);
                            setShowCount(true);
                        }

                    }}
                    menus={[
                        { key: 'count_1', name: '统计参与' },
                        { key: 'count_2', name: '统计邀请' },
                        { key: 'recordVote', name: '投票记录' },
                        { key: 'download', name: '数据导出' },
                    ]}
                />
            ],
        },
    ];

    const columns1: ProColumns<any>[] = [
        {
            title: '排序',
            dataIndex: 'sort',
            width: 60,
            className: 'drag-visible',
        },
        {
            title: '姓名',
            dataIndex: 'name',
        },
        {
            title: '优秀',
            dataIndex: 'l3',
            render: (dom, entity) => {
                return (
                    <Radio defaultChecked={false} disabled={true}>
                        优秀
                    </Radio>
                );
            },
        },
        {
            title: '合格',
            dataIndex: 'l2',
            render: (dom, entity) => {
                return (
                    <Radio defaultChecked={true} disabled={true}>
                        合格
                    </Radio>
                );
            },
        },
        {
            title: '基本合格',
            dataIndex: 'l1',
            render: (dom, entity) => {
                return (
                    <Radio defaultChecked={false} disabled={true}>
                        基本合格
                    </Radio>
                );
            },
        },
        {
            title: '不合格',
            dataIndex: 'l0',
            render: (dom, entity) => {
                return (
                    <Radio defaultChecked={false} disabled={true}>
                        不合格
                    </Radio>
                );
            },
        },
    ];

    const handleDragSortEnd = (newDataSource: any) => {
        const tempUser = newDataSource[0]['userid'];
        let changeIndex = -1;
        for (let i = 0; i < dragSortTableData.length; i++) {
            const subItem = dragSortTableData[i];
            for (let j = 0; j < subItem.length; j++) {
                if (subItem[j]['userid'] == tempUser) {
                    changeIndex = i;
                    break;
                }
            }
            if (changeIndex > -1) {
                break;
            }
        }
        if (changeIndex > -1) {
            const tempDragSortTableData = dragSortTableData;
            tempDragSortTableData[changeIndex] = newDataSource;
            SetDragSortTableData([...tempDragSortTableData]);
        }
    };


    // 邀请参与评议人员 获取用户人数
    const [participantCount, setParticipantCount] = useState<number>();
    const getParticipantNum = async (values: any[]) => {
        setParticipantCount(values.length);
    };

    // 邀请参与评议人员 获取用户人数
    const [inviterCount, setInviterCount] = useState<number>();
    const getInviterNum = async (values: any[]) => {
        setInviterCount(values.length);
    };
    // 评议项目设置 获取用户人数
    const depName: NamePath[] = ['users', 'usersNum'];


    useEffect(() => {
        const params = { tree: 1, parentid: 0, showAll: 1, user: 1, local: 1, showNoBodyDepartment: 0 };
        if (currentUser?.usertype == 0) {
            managerRule().then((res) => {
                if (res.data) {
                    setManager(res.data);
                    const departmentIds = (res.data.department + ',' + res.data.parentdepartment).split(',').map((id) => parseInt(id));
                    setChildrenId(departmentIds);
                    params.childrenId = departmentIds.join(',');
                    departmentRule(params).then((res) => {
                        if (res.data) {
                            setTreeData(res.data);
                        }
                    });
                } else {
                    setChildrenId([-1]);
                }
            });
        } else {
            setManager({ invite: 1 });
            departmentRule(params).then((res) => {
                if (res.data) {
                    setTreeData(res.data);
                }
            });
        }
    }, []);



    return (
        <>
            <ProTable<any, any>
                columns={columns}
                actionRef={actionRef}
                request={(params, sorter, filter) => {
                    document.body.scrollTop = document.documentElement.scrollTop = 0;
                    return rule(params);
                }}
                rowKey="id"
                search={{
                    labelWidth: 'auto',
                }}
                headerTitle="评议项目列表"
                toolBarRender={() => [
                    <Button
                        key="button"
                        icon={<PlusOutlined />}
                        onClick={() => {
                            setCurrentRow(undefined)
                            setShowForm(true);
                        }}
                        type="primary"
                    >
                        新建
                    </Button>,
                ]}
            />

            <ModalForm
                title="评议浏览"
                formRef={formViewRef}
                autoFocusFirstInput
                visible={modalVisible}
                className={styles.votelist}
                modalProps={{
                    destroyOnClose: true,
                    onCancel: () => {
                        setModalVisible(false);
                    },
                }}
                submitter={{
                    render: (props, doms) => {
                        return [
                            <Button
                                type="primary"
                                key="submitsort"
                                onClick={async () => {
                                    const subItemId = [];
                                    const subItemUser = [];
                                    isUserInfoData.forEach((element, index) => {
                                        subItemId.push(element.sid);
                                        const tempUserId = [];
                                        dragSortTableData[index].forEach((element1) => {
                                            tempUserId.push(element1.userid);
                                        })
                                        subItemUser.push(tempUserId);
                                    });
                                    await sortRule({ userid: subItemUser, sid: subItemId });
                                    message.success('排序保存成功');
                                }}
                            >
                                保存排序
                            </Button>,
                        ];
                    },
                }}
                submitTimeout={2000}
            >
                <ProFormText
                    name="title"
                    disabled={true}
                />
                {isUserInfoData &&
                    <List
                        dataSource={isUserInfoData}
                        style={{ height: 500, overflowY: 'scroll' }}
                        renderItem={(item, index) => (
                            <>
                                <Divider orientation="center">{item.stitle}(可评优秀 {item.snum} 人)</Divider>
                                <DragSortTable
                                    headerTitle={null}
                                    columns={columns1}
                                    rowKey="userid"
                                    pagination={false}
                                    search={false}
                                    options={false}
                                    showHeader={false}
                                    dataSource={dragSortTableData[index]}
                                    dragSortKey="sort"
                                    onDragSortEnd={handleDragSortEnd}
                                />
                            </>
                        )}
                    />
                }
            </ModalForm>

            <DrawerForm
                title="编辑评议项目信息"
                visible={showForm}
                width="100vw"
                onVisibleChange={setShowForm}
                formRef={formRef}
                autoFocusFirstInput
                drawerProps={{
                    maskClosable: false,
                    destroyOnClose: true,
                    onClose: () => {
                    },
                }}
                submitter={{ searchConfig: { submitText: '保存' } }}
                submitTimeout={2000}
                onFinish={async (values) => {
                    const users = treeSelectRef?.current.getCheckedKeys();
                    if (users.length == 0) {
                        message.warn('请选择参与评议人员');
                        return false;
                    } else {
                        values.participant = users.join(',');
                    }

                    if (manager && parseInt(manager?.invite) == 1) {
                        const users1 = treeSelectRef1?.current.getCheckedKeys();
                        if (users1.length == 0) {
                            message.warn('请选择邀请参与评议人员');
                            return false;
                        } else {
                            values.inviter = users1.join(',');
                        }
                    }
                    if (!values.sublists || values.sublists.length == 0) {
                        message.warn('请设置评议项目');
                        return false;
                    }
                    try {
                        const getUpdateResult = await updateRule(values);
                        if (getUpdateResult.errorMessage) {
                            message.success(getUpdateResult.errorMessage);
                            return false;
                        }
                        message.success((values.id ? '修改' : '新增') + '成功');
                        actionRef.current?.reload?.();
                        return true;
                    } catch (error) {
                        return false;
                    }
                }}
                layout="vertical"
                grid={true}
            >
                <Tabs style={{ width: '100%' }}>
                    <Tabs.TabPane tab="基本信息" key="baseInfo" forceRender>
                        <ProFormDigit name="id" hidden />
                        <ProFormText
                            name="title"
                            label="项目名称"
                            placeholder="请输入项目名称"
                            rules={[{ required: true, message: '请输入项目名称' }]}
                        />
                        <ProFormDateTimeRangePicker
                            transform={(values) => {
                                return {
                                    starttime: values ? values[0] : undefined,
                                    endtime: values ? values[1] : undefined,
                                };
                            }}
                            name="createTimeRanger"
                            label="评议时间"
                            rules={[{ required: true, message: '请选择评议时间' }]}
                        />
                        <ProFormTextArea
                            name="remark"
                            label="评议规则"
                            placeholder="请输入评议规则说明"
                        />
                    </Tabs.TabPane>
                    <Tabs.TabPane tab="参与评议人员" key="voteusers" forceRender>
                        <Form.Item style={{ width: '100%' }} label="参与评议人员" required={true}>
                            <DepartmentTreeSelect
                                checkable={true}
                                checkStrictly={false}
                                showLeafIcon={true}
                                local={true}
                                showAll={true}
                                showUser={true}
                                showNoBodyDepartment={true}
                                placeholder='请选择参与评议人员'
                                ref={treeSelectRef}
                                onChange={getParticipantNum}
                                childrenId={childrenId}
                                showCheckedStrategy="SHOW_CHILD"
                                allowClear={true}
                            />
                            <div className={styles.inviteCountPre}>已选择：{participantCount}</div>
                        </Form.Item>
                    </Tabs.TabPane>

                    {((manager && parseInt(manager?.invite) == 1) || currentUser?.usertype == 1) && <Tabs.TabPane tab="邀请参与评议人员" key="inviteusers" forceRender>
                        <Form.Item style={{ width: '100%' }} label="邀请参与评议人员" required={true}>
                            <DepartmentTreeSelect
                                checkable={true}
                                checkStrictly={false}
                                showLeafIcon={true}
                                local={true}
                                showAll={true}
                                showUser={true}
                                showNoBodyDepartment={true}
                                placeholder='请选择邀请参与评议人员'
                                ref={treeSelectRef1}
                                onChange={getInviterNum}
                                showCheckedStrategy="SHOW_CHILD"
                                allowClear={true}
                            />
                            <div className={styles.inviteCountPre}>已选择：{inviterCount}</div>
                        </Form.Item>
                    </Tabs.TabPane>}

                    <Tabs.TabPane tab="评议项目设置" key="votes" forceRender>
                        <ProFormList
                            name="sublists"
                            label="评议项目设置"
                            min={1}
                            copyIconProps={false}
                            creatorButtonProps={{
                                creatorButtonText: '新建评议项目',
                            }}
                            initialValue={currentRow ? undefined : [
                                {
                                    stitle: '中层',
                                }, {
                                    stitle: '普通员工',
                                },
                            ]}
                            actionGuard={{
                                beforeRemoveRow: async (index) => {
                                    return new Promise(async (resolve) => {
                                        if (index < 1) {
                                            message.warn('默认项目不能删');
                                            resolve(false);
                                            return;
                                        } else {
                                            const row = formRef?.current.getFieldsValue();
                                            const sid = row.sublists[index].sid;
                                            if (sid) {
                                                await removeSubRule({
                                                    id: sid
                                                });
                                            }
                                        }
                                        resolve(true)
                                    });
                                },
                            }}
                            itemRender={({ listDom, action }, { record }) => {
                                return (
                                    <ProCard
                                        bordered
                                        extra={action}
                                        style={{
                                            marginBlockEnd: 8,
                                        }}
                                    >
                                        {listDom}
                                    </ProCard>
                                );
                            }}
                        >
                            <ProForm.Group>
                                <ProFormText
                                    width="sm"
                                    name="stitle"
                                    label="评选标题"
                                    placeholder="请输入如：中层或者普通员工"
                                    rules={[{ required: true, message: '请输入评选标题' }]}
                                    colProps={{ md: 12, xl: 12 }}
                                />
                                <ProFormDigit
                                    name="snum"
                                    label="可评优秀人数"
                                    placeholder="请输入可评优秀人数"
                                    min={1}
                                    max={50}
                                    rules={[{ required: true, message: '请输入可评优秀人数' }]}
                                    colProps={{ md: 12, xl: 12 }}
                                />
                            </ProForm.Group>
                            <ProFormTreeSelect
                                name="users"
                                placeholder="请选择被评议人员"
                                allowClear
                                request={async () => {
                                    return treeData;
                                }}
                                rules={[{ required: true, message: '请选择被评议人员' }]}
                                fieldProps={{
                                    treeLine: true,
                                    filterTreeNode: true,
                                    showSearch: false,
                                    labelInValue: false,
                                    treeCheckable: true,
                                    autoClearSearchValue: false,
                                    multiple: true,
                                    treeNodeFilterProp: 'title',
                                    fieldNames: {
                                        label: 'title',
                                    },
                                }}
                            />
                            <ProFormDependency name={depName}>
                                {(depValues) => {
                                    let depValuesLength = 0;
                                    if (depValues?.users) {// 判断 对象中是否存在这个属性 ?. 来判断 非空属性
                                        depValuesLength = depValues.users.length;
                                        const elementArr = [];
                                        if (depValuesLength > 0) {
                                            for (let index = 0; index < depValuesLength; index++) {
                                                const element = depValues.users[index];
                                                if (!parseInt(element)) {
                                                    elementArr.push(element);
                                                }
                                            }
                                        }
                                        depValuesLength = elementArr.length;
                                    }
                                    return <div className={styles.userCountPre}>已选择：{depValuesLength}</div>;
                                }}
                            </ProFormDependency>
                        </ProFormList>

                    </Tabs.TabPane>
                </Tabs>
            </DrawerForm>

            <ModalForm
                title="数据统计"
                visible={showCount}
                modalProps={{
                    destroyOnClose: true,
                    maskClosable: false,
                    onCancel: () => {
                        setShowCount(false);
                    },
                }}
                submitter={false}
            >
                <List
                    dataSource={isCountData}
                    style={{ height: 500, overflowY: 'scroll' }}
                    renderItem={(item) => (
                        <>
                            <Divider orientation="center">{item.title}</Divider>
                            <ProTable<any, any>
                                columns={[
                                    {
                                        title: '姓名',
                                        dataIndex: 'name',
                                    },
                                    {
                                        title: '优秀',
                                        dataIndex: 'type1',
                                    },
                                    {
                                        title: '合格',
                                        dataIndex: 'type2',
                                    },
                                    {
                                        title: '基本合格',
                                        dataIndex: 'type3',
                                    },
                                    {
                                        title: '不合格',
                                        dataIndex: 'type4',
                                    },
                                ]}
                                dataSource={item.users}
                                pagination={false}
                                options={false}
                                search={false}
                                rowKey="userid"
                            />
                        </>
                    )}
                />
            </ModalForm>

            {/* 查看 */}
            <ModalForm
                title="投票情况"
                visible={showVoteUser}
                modalProps={{
                    destroyOnClose: true,
                    maskClosable: false,
                    onCancel: () => {
                        setShowVoteUser(false);
                    },
                }}
                submitter={false}
            >
                <List
                    dataSource={isVoteUsertData}
                    style={{ height: 500, overflowY: 'scroll' }}
                    renderItem={(item) => (
                        <>
                            <Divider orientation="center">{item.title}</Divider>
                            <ProTable<any, any>
                                columns={[
                                    {
                                        title: '姓名',
                                        dataIndex: 'name',
                                    }
                                ]}
                                dataSource={item.users}
                                pagination={false}
                                options={false}
                                search={false}
                                rowKey="userid"
                            />
                        </>
                    )}
                />
            </ModalForm>
        </>
    );
};

export default ItemsManager;