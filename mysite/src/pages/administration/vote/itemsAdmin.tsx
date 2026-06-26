import { PlusOutlined } from '@ant-design/icons';
import type { ProColumns } from '@ant-design/pro-components';
import { ProFormSelect } from '@ant-design/pro-components';
import { DrawerForm, ProFormDigit } from '@ant-design/pro-components';
import { ProTable } from '@ant-design/pro-components';
import { Button, Form, message, Modal } from 'antd';
import React, { useRef, useState } from 'react';
import { ruleAdmin, removeAdminRule, updateAdminRule } from './service';
import DepartmentTreeSelect from '@/components/DepartmentTreeSelect';

const ItemsAdmin: React.FC = () => {
    const actionRef = useRef<any>();
    const formRef = useRef<any>();
    const [showForm, setShowForm] = useState<boolean>(false);
    const treeSelectRef = useRef<any>();
    const treeSelectRef1 = useRef<any>();

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
            title: '管理员姓名',
            dataIndex: 'username',
        },
        {
            disable: true,
            title: '是否邀请人员',
            dataIndex: 'invite',
            hideInSearch: true,
            valueEnum: {
                0: {
                    text: '否',
                },
                1: {
                    text: '是',
                }
            },
        },
        {
            disable: true,
            title: '状态',
            dataIndex: 'state',
            hideInSearch: true,
            valueEnum: {
                0: {
                    text: '禁用',
                    status: 'Default',
                },
                1: {
                    text: '正常',
                    status: 'Success',
                }
            },
        },
        {
            title: '创建时间',
            key: 'showTime',
            dataIndex: 'inserttime',
            valueType: 'dateTime',
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
                        setTimeout(() => {
                            treeSelectRef?.current.setCheckedKeys([{ label: record.username, value: record.userid }]);
                            treeSelectRef1?.current.setCheckedKeys(record.department.split(','));
                            formRef?.current?.setFieldsValue(record);
                        }, 500);
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
                    pageSize: 5,
                }}
                headerTitle="用户列表"
                toolBarRender={() => [
                    <Button
                        key="buttonU"
                        icon={<PlusOutlined />}
                        onClick={() => {
                            setShowForm(true)
                        }}
                        type="primary"
                    >
                        新建
                    </Button>,
                ]}
            />

            <DrawerForm
                title="编辑评议管理员"
                visible={showForm}
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
                    const users: any[] = [];
                    const keys = treeSelectRef?.current.getCheckedKeys();
                    if (keys.length != 1) {
                        message.warn('请选择一个管理员用户');
                        return false;
                    } else {
                        keys.forEach(element => {
                            if (isNaN(element.value)) {
                                users.push(element);
                            }
                        });
                    }
                    if (users.length != 1 && keys.length == 1) {
                        message.warn('请选择管理员用户姓名，不能选择部门');
                        return false;
                    }
                    values.userid = users[0]['value'];
                    values.username = users[0]['label'];
                    const keys1 = treeSelectRef1?.current.getCheckedKeys();
                    const keys2 = treeSelectRef1?.current.getAllKeys();
                    if (keys1.length == 0) {
                        message.warn('请选择管理部门');
                        return false;
                    } else {
                        values.department = keys1.join(',');
                        values.parentdepartment = _.difference(keys2, keys1).join(',');
                    }
                    try {
                        const getUpdateResult = await updateAdminRule(values);
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
                <ProFormDigit name="id" hidden />
                <Form.Item style={{ width: '100%' }} label="管理员" required={true}>
                    <DepartmentTreeSelect
                        checkable={true}
                        checkStrictly={true}
                        showLeafIcon={true}
                        local={true}
                        showUser={true}
                        placeholder='请选择管理员用户'
                        ref={treeSelectRef}
                        allowClear={true}
                    />
                </Form.Item>
                <Form.Item style={{ width: '100%' }} label="管理部门" required={true}>
                    <DepartmentTreeSelect
                        checkable={true}
                        checkStrictly={false}
                        showLeafIcon={true}
                        local={true}
                        showAll={true}
                        placeholder='请选择管理部门'
                        ref={treeSelectRef1}
                        showCheckedStrategy="SHOW_ALL"
                        allowClear={true}
                    />
                </Form.Item>
                <ProFormSelect
                    width="xs"
                    options={[
                        {
                            value: '0',
                            label: '否',
                        },
                        {
                            value: '1',
                            label: '是',
                        },
                    ]}
                    initialValue="0"
                    name="invite"
                    label="邀请参与人员"
                    placeholder="请选择是否邀请参与人员"
                    rules={[
                        {
                            required: true,
                            message: '请选择是否邀请参与人员',
                        },
                    ]}
                />
                <ProFormSelect
                    width="xs"
                    options={[
                        {
                            value: '1',
                            label: '启用',
                        },
                        {
                            value: '0',
                            label: '禁用',
                        },
                    ]}
                    initialValue="1"
                    name="state"
                    label="用户状态"
                    placeholder="请选择用户状态"
                    rules={[
                        {
                            required: true,
                            message: '请选择用户状态',
                        },
                    ]}
                />
            </DrawerForm>
        </>
    );
};

export default ItemsAdmin;