import { MinusOutlined, UndoOutlined } from '@ant-design/icons';
import { ActionType, ProColumns, ProTable } from '@ant-design/pro-components';
import { Button, Drawer, message, Modal } from 'antd';
import React, { useImperativeHandle, useRef } from 'react';
import { useState } from 'react';
import { rule, one, recycleBinDelete, recycleBinReduction } from '../list/service';
import styles from '../../index.less';

const RecyclebinDrawer = React.forwardRef((props, ref) => {
    const [visible, setVisible] = useState(false);
    const [currentRow, setCurrentRow] = useState<any>();
    const [selectedRowsState, setSelectedRows] = useState<any[]>([]);
    const actionRef = useRef<ActionType>();
    const [searchFlag, setSearchFlag] = useState<boolean>(false);
    const [preview, setPreview] = useState(false);

    const backTop = () => {
        const tags = document.getElementsByClassName('ant-drawer-body');
        if (tags.length > 0) {
            tags[0].scrollTo(0, 0);
        }
    };

    const handleCancel = () => {
        setVisible(false);
    };

    const handleRemove = async (selectedRows: any[], deleteRow: any) => {
        const hide = message.loading('正在删除');
        if (!selectedRows && !deleteRow) return true;

        try {
            let deleteIds = [];
            let result;
            if (selectedRows.length > 0) {
                deleteIds = selectedRows.map((row) => row.id);
                const params = {
                    id: deleteIds,
                };
                result = await recycleBinDelete(params);
            } else if (deleteRow) {
                deleteIds = [deleteRow].map((row) => row.id);
                result = await recycleBinDelete({
                    id: deleteIds,
                });
            }
            if (!result.errorMessage) {
                hide();
                message.success('删除成功');
            }
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
                actionRef.current?.reload?.();
            },
        });
    };

    const handleReduction = async (selectedRows: any[], deleteRow: any) => {
        const hide = message.loading('正在还原');
        if (!selectedRows && !deleteRow) return true;

        try {
            let deleteIds = [];
            let result;
            if (selectedRows.length > 0) {
                deleteIds = selectedRows.map((row) => row.id);
                const params = {
                    id: deleteIds,
                };
                result = await recycleBinReduction(params);
            } else if (deleteRow) {
                deleteIds = [deleteRow].map((row) => row.id);
                result = await recycleBinReduction({
                    id: deleteIds,
                });
            }
            if (!result.errorMessage) {
                hide();
                message.success('还原成功');
            }
            return true;
        } catch (error) {
            hide();
            message.error('还原失败，请重试');
            return false;
        }
    };

    const reductionItem = (item: React.SetStateAction<any | undefined>) => {
        Modal.confirm({
            title: '还原',
            content: '确定要还原吗？',
            okText: '确认',
            cancelText: '取消',
            onOk: async () => {
                await handleReduction([], item);
                actionRef.current?.reload?.();
            },
        });
    };

    const clearSelected = () => {
        setSelectedRows([]);
        actionRef?.current?.clearSelected();
    };

    const columns: ProFormColumnsType<any>[] = [
        {
            title: '标题',
            dataIndex: 'title',
            key: 'searchtitle',
            render: (dom, entity) => {
                return (
                    <a
                        onClick={async () => {
                            const info = await one({ id: entity.id });
                            setCurrentRow(info.data);
                            setPreview(true);
                        }}
                        dangerouslySetInnerHTML={{ __html: entity.title }}
                    />
                );
            },
        },
        {
            title: '编辑姓名',
            dataIndex: 'editor',
            key: 'searcheditor',
        },
        {
            title: '浏览量',
            dataIndex: 'click',
            hideInSearch: true,
        },
        {
            title: '发布时间',
            dataIndex: 'publictime',
            hideInSearch: true,
        },
        {
            title: '添加时间',
            dataIndex: 'inserttime',
            valueType: 'dateRange',
            key: 'inserttime',
            render: (_, entity) => {
                return entity.inserttime;
            },
        },
        {
            title: '操作',
            dataIndex: 'option',
            valueType: 'option',
            render: (_, entity) => [
                <a
                    key="reduction"
                    onClick={() => {
                        reductionItem(entity);
                    }}
                >
                    还原
                </a>,
                <a
                    key="delete"
                    onClick={() => {
                        deleteItem(entity);
                    }}
                >
                    删除
                </a>,
            ],
        },
    ];


    useImperativeHandle(ref, () => ({
        setVisible: (value: boolean) => {
            setVisible(value);
        },
    }));

    return (
        <Drawer
            width="100vw"
            title="回收站"
            visible={visible}
            onClose={handleCancel}
            className={styles['my-drawer']}
        >
            <ProTable<any, any>
                headerTitle="信息列表"
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
                    backTop();
                    params.state = -1;
                    if (searchFlag) {
                        params.search = 1;
                    }
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
                    <Button
                        type="primary"
                        key="reduction"
                        onClick={() => {
                            if (selectedRowsState.length == 0) {
                                message.warn('请选择要操作的项目！');
                                return;
                            }
                            Modal.confirm({
                                title: '系统提示',
                                content: '确定还原选中的项目吗？',
                                okText: '确认',
                                cancelText: '取消',
                                onOk: async () => {
                                    await handleReduction(selectedRowsState, undefined);
                                    setSelectedRows([]);
                                    actionRef.current?.reload?.();
                                },
                                onCancel: async () => clearSelected(),
                            });
                        }}
                    >
                        <UndoOutlined /> 还原
                    </Button>,
                    <Button
                        type="primary"
                        key="delete"
                        onClick={async () => {
                            if (selectedRowsState.length == 0) {
                                message.warn('请选择要操作的项目！');
                                return;
                            }
                            Modal.confirm({
                                title: '系统提示',
                                content: '确定删除选中的项目吗？删除后，信息将无法恢复！',
                                okText: '确认',
                                cancelText: '取消',
                                onOk: async () => {
                                    await handleRemove(selectedRowsState, undefined);
                                    setSelectedRows([]);
                                    actionRef.current?.reload?.();
                                },
                                onCancel: async () => clearSelected(),
                            });
                        }}
                    >
                        <MinusOutlined /> 删除
                    </Button>,
                ]}
            />
            {currentRow && (
                <Drawer
                    title="内容预览"
                    width="100vw"
                    visible={preview}
                    onClose={() => {
                        setPreview(false);
                    }}
                    closable={true}
                >
                    <h1 dangerouslySetInnerHTML={{ __html: currentRow.title }} />
                    <div dangerouslySetInnerHTML={{ __html: currentRow.content }} />
                </Drawer>
            )}
        </Drawer>
    );
});
export default RecyclebinDrawer;
