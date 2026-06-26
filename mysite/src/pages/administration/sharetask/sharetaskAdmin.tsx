import { PlusOutlined } from '@ant-design/icons';
import type { ProColumns } from '@ant-design/pro-components';
import { DrawerForm, ProFormText } from '@ant-design/pro-components';
import { ProTable } from '@ant-design/pro-components';
import { Button, Form, message, Modal } from 'antd';
import React, { useRef, useState } from 'react';
import { ruleAdmin, removeAdminRule, updateAdminRule } from './service';
import styles from './styles.less';
import DepartmentTree from '@/components/DepartmentTree';
import DepartmentTreeSelect from '@/components/DepartmentTreeSelect';

const ItemsManager: React.FC = () => {
    const actionRef = useRef<any>();
    const formRef = useRef<any>();
    const [showForm, setShowForm] = useState<boolean>(false);
    const formRefTree = useRef<any>();

    const formRefTreeDep = useRef<any>();
    const [initFormRefTree, setInitFormRefTree] = useState<any>([]);
    // const [depIdMap, setDepIdMap] = useState<any>(['44', '38', '64']);
    const [isSetuserID, setIsSetUserID] = useState<boolean>(false);

    // 删除操作
    const handleRemove = async (selectedRows: any[], deleteRow: any) => {
        const hide = message.loading('正在删除');
        if (!selectedRows && !deleteRow) return true;

        try {
            if (deleteRow) {
                await removeAdminRule({
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

    const columns: ProColumns<any>[] = [
        {
            title: '序号',
            dataIndex: 'index',
            valueType: 'index',
        },
        {
            title: '用户名',
            dataIndex: 'userid',
            hideInSearch: true,
        },
        {
            title: '姓名',
            dataIndex: 'username',
        },
        {
            title: '创建时间',
            key: 'inserttime',
            dataIndex: 'inserttime',
            hideInSearch: true,
        },
        {
            title: '操作',
            valueType: 'option',
            key: 'option',
            render: (text, record, _) => [
                <a
                    key="editU"
                    onClick={() => {
                        setShowForm(true);
                        setIsSetUserID(false);
                        setInitFormRefTree([]);
                        setTimeout(() => {
                            setIsSetUserID(true)
                            setInitFormRefTree(record.parentdepartment);
                            formRefTreeDep?.current?.setCheckedKey(record.department);
                            // formRefTree?.current.setCheckedKeys([{ label: record.username, value: record.userid }]);
                            formRef?.current?.setFieldsValue(record);
                        }, 100);
                    }}
                >
                    修改
                </a>,
                <a
                    key="deleteU"
                    onClick={() => {
                        deleteItem(record);
                    }}
                >
                    删除
                </a>,

            ],
        },
    ];

    return (
        <>
            <ProTable<any, any>
                columns={columns}
                actionRef={actionRef}
                request={(params, sorter, filter) => {
                    document.body.scrollTop = document.documentElement.scrollTop = 0;
                    return ruleAdmin(params);
                }}
                rowKey="id"
                search={{
                    labelWidth: 'auto',
                }}
                pagination={{
                    pageSize: 10,
                }}
                headerTitle="管理员列表"
                toolBarRender={() => [
                    <Button
                        key="buttonU"
                        icon={<PlusOutlined />}
                        onClick={async () => {
                            setIsSetUserID(false);
                            setTimeout(() => {
                                setShowForm(true);
                            }, 100)
                        }}
                        type="primary"
                    >
                        新建
                    </Button>,
                ]}
            />

            <DrawerForm
                title="编辑管理员"
                visible={showForm}
                className={styles.dfrom}
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
                    const getFormRefTree = formRefTree?.current?.getCheckedKeys();// 选择用户
                    const getFormRefTreeDep = formRefTreeDep?.current?.getCheckedKeys();// 选中部门
                    const getFormRefTreeParentDep = formRefTreeDep?.current?.getAllCheckedKeys();// 选中部门及父类

                    // 部门数据处理
                    values.getFormRefTreeDep = getFormRefTreeDep.length == 0 ? initFormRefTree : getFormRefTreeDep;
                    if (!values.getFormRefTreeDep) {
                        message.warn('请选择管理部门');
                        return false;
                    }
                    values.getFormRefTreeParentDep = getFormRefTreeParentDep;

                    // 用户数据处理 新增/修改
                    if (!isSetuserID) {
                        if (!getFormRefTree) {
                            message.warn('请选择用户');
                            return false;
                        }
                        const getFormRefTreeVlue = [];
                        for (let i = 0; i < getFormRefTree.length; i++) {
                            getFormRefTreeVlue.push(getFormRefTree[i].value);
                        }
                        values.getFormRefTree = getFormRefTreeVlue;
                    } else {
                        values.getFormRefTree = [];
                    }

                    try {
                        const getUpdateResult = await updateAdminRule(values);
                        if (getUpdateResult.errorMessage) {
                            message.warn(getUpdateResult.errorMessage);
                        } else {
                            message.success('设置成功');
                        }
                        actionRef.current?.reload?.();
                        return true;
                    } catch (error) {
                        return false;
                    }
                }}
                layout="vertical"
                grid={true}
            >
                <ProFormText name="id" hidden />

                <Form.Item style={{ width: '100%' }} label="管理员" required={true}>
                    {isSetuserID ? (
                        <ProFormText
                            name="username"
                            width="md"
                            disabled
                        />) : (
                        <DepartmentTreeSelect
                            checkable={false}
                            checkStrictly={true}
                            showLeafIcon={true}
                            local={true}
                            showUser={true}
                            placeholder='请选择管理员用户'
                            ref={formRefTree}
                            allowClear={true}
                        />)
                    }
                </Form.Item>
                <Form.Item style={{ width: '100%' }} label="管理部门" required={true}>
                    <DepartmentTree
                        showLeafIcon={false}
                        checkable={true}
                        checkStrictly={false}
                        selectable={false}
                        ref={formRefTreeDep}
                        local={true}
                        showAll={true}
                    />
                </Form.Item>
            </DrawerForm>
        </>
    );
};

export default ItemsManager;