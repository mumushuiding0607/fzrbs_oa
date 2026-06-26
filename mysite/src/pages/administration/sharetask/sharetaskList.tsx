import type { ActionType, ProColumns } from '@ant-design/pro-components';
import { DrawerForm, ProFormDateTimePicker, ProFormDigit, ProFormSelect, ProFormText } from '@ant-design/pro-components';
import tools from '@/utils/tools';
import { ProTable } from '@ant-design/pro-components';
import { Button, Modal, Space, message } from 'antd';
import { useEffect, useRef, useState } from 'react';
import { rule, getItems, updateRule, updateState, configRule } from './service';
import styles from './styles.less';
import MyUploadFile from '@/components/MyUploadFile';
import { ExportOutlined } from '@ant-design/icons';

const SharetaskList: React.FC = () => {
    const actionRef = useRef<ActionType>();
    const actionShareTaskRef = useRef<ActionType>();
    const [showShareLists, setShowShareLists] = useState<boolean>(false);
    const [viewID, setViewID] = useState<any>(0);
    const [isShareTaskTitle, setIsShareTaskTitle] = useState<any>('传播任务');
    const formRef = useRef<any>();
    const [showForm, setShowForm] = useState<boolean>(false);
    const [defaultImage, setDefaultImage] = useState<any[]>([]);
    const [source, setSource] = useState<any>({});
    const [level, setLevel] = useState<any>({});
    const uploadRef = useRef();


    const handleUpdateState = async (id: number, st: number) => {
        const action = (st == 1 ? '发布' : (st == -1 ? '删除' : '撤销'));
        Modal.confirm({
            title: action,
            content: '确定要' + action + '吗？',
            okText: '确认',
            cancelText: '取消',
            onOk: async () => {
                const updateData = { id: id, st: st };
                await updateState(updateData);
                actionRef.current?.reload?.();
            },
        });
    }

    const columns: ProColumns<any>[] = [
        {
            dataIndex: 'index',
            title: '序号',
            valueType: 'indexBorder',
            width: 48,
        },
        {
            title: '标题',
            dataIndex: 'title',
        },
        {
            title: '摘要',
            dataIndex: 'remark',
            hideInSearch: true,
        },
        {
            title: '分享图',
            dataIndex: 'image',
            hideInSearch: true,
            render: (_: any, record: any) => {
                if (record.image == '') {
                    return ('-');
                } else {
                    return (
                        <img style={styles.image} src={record.image} />
                    );
                }
            },
        },
        {
            title: '截止时间',
            dataIndex: 'endtime',
            valueType: 'date',
            hideInSearch: true,
        },
        {
            disable: true,
            title: '来源',
            dataIndex: 'source',
            filters: true,
            onFilter: true,
            valueType: 'select',
            hideInSearch: true,
            valueEnum: source,
        },
        {
            disable: true,
            title: '是否重点',
            dataIndex: 'level',
            filters: true,
            onFilter: true,
            valueType: 'select',
            hideInSearch: true,
            valueEnum: level,
        },
        {
            title: '发布人',
            dataIndex: 'editor',
            hideInSearch: true,
        },
        {
            title: '执行情况',
            dataIndex: 'sharenum',
            hideInSearch: true,
            render: (text, record) => {
                return (
                    <>
                        <p>转发量:{record.sharenum}</p>
                        <p>阅读量:{record.clicknum}</p>
                    </>
                );
            },
        },
        {
            disable: true,
            title: '状态',
            dataIndex: 'state',
            filters: true,
            onFilter: true,
            ellipsis: true,
            valueType: 'select',
            hideInSearch: true,
            valueEnum: {
                0: {
                    text: '未发布',
                    status: 'Processing',
                },
                1: {
                    text: '已发布',
                    status: 'Success',
                },
            },
        },
        {
            title: '创建时间',
            dataIndex: 'inserttime',
            hideInSearch: true,
        },
        {
            title: '',
            key: 'action',
            dataIndex: 'action',
            hideInTable: true,
            renderFormItem: (item, { type, defaultRender, ...rest }, form) => {
                return (
                    <>
                        <Space>
                            <Button
                                key="export"
                                onClick={() => {
                                    const values = form?.getFieldsValue();
                                    const params = {};
                                    if (values.title) {
                                        params.title = values.title;
                                    }
                                    const fileName = '分享任务数据表';
                                    tools.downloadFile(
                                        '/api/sharetask/task-download',
                                        params,
                                        fileName + '.xls',
                                    );
                                }}
                            >
                                <ExportOutlined />
                                导出数据
                            </Button>

                        </Space>
                    </>
                );
            },
        },
        {
            title: '操作',
            valueType: 'option',
            key: 'option',
            render: (text, record) => [
                <a
                    key="editable"
                    onClick={() => {
                        if (record.image != '') {
                            const image = {
                                uid: record.id.toString(),
                                name: record.title,
                                status: 'done',
                                url: record.image,
                                thumbUrl: record.image,
                            };
                            setDefaultImage([image]);
                        } else {
                            setDefaultImage([]);
                        }
                        setShowForm(true);
                        setTimeout(() => {
                            formRef?.current?.setFieldsValue(record);
                        }, 100);
                    }}
                >
                    编辑
                </a>,
                <a
                    key="view"
                    onClick={() => {
                        setTimeout(() => {
                            setShowShareLists(true);
                            setViewID(record.id);
                            setIsShareTaskTitle('分享任务：' + record.title);
                            actionShareTaskRef.current?.reload?.();
                        }, 100);
                    }}
                >
                    查看
                </a>,
                <a
                    key="push"
                    onClick={() => {
                        const state = parseInt(record.state) == 1 ? 0 : 1;
                        handleUpdateState(record.id, state);
                    }}
                >
                    {parseInt(record.state) == 1 ? '撤销' : '发布'}
                </a>,
                <a
                    key="del"
                    onClick={() => {
                        handleUpdateState(record.id, -1);
                    }}
                >
                    删除
                </a>,
            ],
        },
    ];

    // 工具栏列设置不勾选某个列
    const [columnsStateMap, setColumnsStateMap] = useState({ remark: { show: false }, image: { show: false }, endtime: { show: false } });


    useEffect(() => {
        configRule().then((res) => {
            if (res.data?.source) {
                setSource(res.data?.source);
            }
            if (res.data?.level) {
                setLevel(res.data?.level);
            }
        });
    }, []);


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
                request={(params, sorter, filter) => {
                    document.body.scrollTop = document.documentElement.scrollTop = 0;
                    return rule(params);
                }}
                rowKey="id"
                search={{
                    labelWidth: 'auto',
                }}
                dateFormatter="string"
                headerTitle="分享任务列表"
                toolBarRender={() => [
                    <Button
                        key="button"
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
                title="编辑分享任务"
                visible={showForm}
                onVisibleChange={setShowForm}
                formRef={formRef}
                autoFocusFirstInput
                drawerProps={{
                    maskClosable: true,
                    destroyOnClose: true,
                    onClose: () => { },
                }}
                submitter={{ searchConfig: { submitText: '保存' } }}
                submitTimeout={2000}
                onFinish={async (values) => {
                    if (values.upload && values.upload.length > 0) {
                        values.image = values.upload[0].response.data.url;
                    } else {
                        const uploads = uploadRef?.current.getFileList();
                        if (uploads.length > 0) {
                            values.image = uploads[0].url;
                        } else {
                            values.image = '';
                        }
                    }
                    delete values.upload;
                    try {
                        const getUpdateResult = await updateRule(values);
                        if (getUpdateResult.errorMessage) {
                            message.warn(getUpdateResult.errorMessage);
                            return false;
                        } else {
                            message.success((values?.id ? '修改' : '添加') + '成功');
                            actionRef.current?.reload?.();
                            return true;
                        }
                    } catch (error) {
                        return false;
                    }
                }}
                layout="vertical"
                grid={true}
            >
                <ProFormDigit name="id" hidden />
                <ProFormText
                    name="title"
                    label="标题"
                    placeholder="请输入标题"
                    rules={[{ required: true, message: '请输入标题' }]}
                />
                <ProFormText
                    name="remark"
                    label="摘要"
                    placeholder="请输入摘要"
                />
                <ProFormText
                    name="link"
                    label="链接"
                    placeholder="请输入链接"
                    rules={[{ required: true, message: '请输入链接' }]}
                />
                <MyUploadFile
                    name="upload"
                    label="分享图"
                    max={1}
                    multiple={false}
                    accept="image/*"
                    maxSize={3}
                    listType="picture-card"
                    defaultImage={defaultImage}
                    uploadPath="sharewxtask"
                    uploadType={1}
                    ref={uploadRef}
                />
                <ProFormDateTimePicker name="endtime" label="截止时间" />
                <ProFormSelect
                    width="xs"
                    valueEnum={source}
                    name="source"
                    label="来源"
                />
                <ProFormSelect
                    width="xs"
                    valueEnum={level}
                    name="level"
                    label="是否重点"
                />
            </DrawerForm>

            <Modal
                className={styles.clickTable}
                title={isShareTaskTitle}
                visible={showShareLists}
                onCancel={() => {
                    setShowShareLists(false);
                }}
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
                        {
                            title: '',
                            key: 'action',
                            dataIndex: 'action',
                            hideInTable: true,
                            renderFormItem: (item, { type, defaultRender, ...rest }, form) => {
                                return (
                                    <>
                                        <Space>
                                            <Button
                                                key="export"
                                                onClick={() => {
                                                    const values = form?.getFieldsValue();
                                                    const params = {};
                                                    if (values.name) {
                                                        params.name = values.name;
                                                    }
                                                    if (values.departmentname) {
                                                        params.departmentname = values.departmentname;
                                                    }
                                                    params.t_id = viewID;
                                                    const fileName = '用户分享数据列表';
                                                    tools.downloadFile(
                                                        '/api/sharetask/user-task-download',
                                                        params,
                                                        fileName + '.xls',
                                                    );
                                                }}
                                            >
                                                <ExportOutlined />
                                                导出数据
                                            </Button>

                                        </Space>
                                    </>
                                );
                            },
                        },
                        {
                            title: '分享时间',
                            dataIndex: 'inserttime',
                            hideInSearch: true,
                        },
                    ]}
                    actionRef={actionShareTaskRef}
                    cardBordered
                    request={(params, sorter, filter) => {
                        document.body.scrollTop = document.documentElement.scrollTop = 0;
                        params.id = viewID;
                        params = { ...params, sorter, filter };
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
export default SharetaskList;