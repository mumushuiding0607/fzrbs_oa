import { ActionType, ProColumns, ProForm, ProFormInstance, ProFormSelect, ProFormText, ProFormTextArea, ProTable } from '@ant-design/pro-components';
import { Avatar, Button, Card, Col, Divider, Drawer, List, Modal, Row, Space, Tag, message } from 'antd';
import React, { useRef, useState } from 'react';
import { setting } from './components/list/service';
import browser from '@/utils/browser';
import Meta from 'antd/lib/card/Meta';
import { AppstoreOutlined, MailOutlined, MenuOutlined, SettingOutlined, TeamOutlined, UserOutlined } from '@ant-design/icons';
import DepartmentTree from '@/components/DepartmentTree';
import style from './style.less'

const SettingTab: React.FC = () => {
    const [showDetail, setShowDetail] = useState<boolean>(false);
    const [currentRow, setCurrentRow] = useState<any>();
    const actionRef = useRef<ActionType>();
    const [preview, setPreview] = useState(false);
    const treeRef: any = useRef();
    const [currentTypeId, setCurrentTypeId] = useState(0);
    const [selectUserId, setSelectUserId] = useState<any>([]);
    const [selectUsername, setSelectUsername] = useState<any>([]);
    const [settingTitle, setSettingTitle] = useState('');
    const formRef = useRef<ProFormInstance>();
    const formRef1 = useRef<ProFormInstance>();
    const [currentEditId, setCurrentEditId] = useState(0);
    const [now, setNow] = useState(() => Date.now());

    const handleRemove = async (selectedRows: any[], deleteRow: any) => {
        const hide = message.loading('正在删除');
        if (!selectedRows && !deleteRow) return true;

        try {
            const result = await setting({ action: 'delete', id: deleteRow.id })
            if (!result.errorMessage) {
                hide();
                message.success('删除成功');
            }
            return true;
        } catch (error) {
            hide();
            message.warn('删除失败，请重试');
            return false;
        }
    };

    const deleteItem = (item: React.SetStateAction<any | undefined>) => {
        Modal.confirm({
            title: '系统提示',
            content: '确定要删除吗？',
            okText: '确认',
            cancelText: '取消',
            onOk: async () => {
                await handleRemove([], item);
                actionRef.current?.reload?.();
            },
        });
    };

    const columns: ProColumns<any>[] = [
        {
            title: '类型ID',
            dataIndex: 'varid',
        },
        {
            title: '类型名称',
            dataIndex: 'varvalue',
        },
        {
            title: '操作',
            dataIndex: 'option',
            valueType: 'option',
            render: (_, entity) => [
                <a
                    key="edit"
                    onClick={async () => {
                        setCurrentEditId(parseInt(entity.id))
                        formRef1?.current?.setFieldsValue({ varid: entity.varid, varvalue: entity.varvalue })
                    }}
                >
                    修改
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

    const listData: any = [
        { name: '账号结算类别设置', icon: <AppstoreOutlined />, typeid: 1 },
        { name: '食堂菜单类别设置', icon: <MenuOutlined />, typeid: 2 },
        { name: '菜单提示信息设置', icon: <MailOutlined />, typeid: 3 },
        { name: '11:30点餐人员设置', icon: <UserOutlined />, typeid: 4 },
        { name: '点餐领导人员设置', icon: <TeamOutlined />, typeid: 5 },
        { name: '点餐其他相关设置', icon: <SettingOutlined />, typeid: 6 },
    ];

    const selectHour = {};
    for (let i = 6; i <= 24; i++) {
        selectHour[i] = i.toString() + '时';
    }

    const handleClose = (removedTag: string, index: any) => {
        const userid = selectUserId
        userid.splice(index, 1)
        const newTags = selectUsername.filter((tag: any) => tag !== removedTag);
        setSelectUsername([...newTags]);
        setSelectUserId([...userid]);
    };

    const usernameTag = (tag: string, index: any) => {
        const tagElem = (
            <Tag closable onClose={() => handleClose(tag, index)}>
                {tag}
            </Tag>
        );
        return (
            <span key={tag} style={{ display: 'inline-block', marginBottom: 5 }}>
                {tagElem}
            </span>
        );
    };

    return (
        <>
            <List<any>
                grid={{
                    gutter: 16,
                    xs: 1,
                    sm: 2,
                    md: 3,
                    lg: 3,
                    xl: 4,
                    xxl: 4,
                }}
                dataSource={listData}
                pagination={false}
                renderItem={(item) => {
                    return (
                        <List.Item>
                            <a
                                onClick={async () => {
                                    setNow(Date.now());
                                    setSettingTitle(item.name)
                                    setCurrentTypeId(item.typeid)
                                    let res: any
                                    if ([4, 5, 3, 6].includes(item.typeid)) {
                                        res = await setting({ action: 'show', typeid: item.typeid })
                                        if (res?.userid) {
                                            setSelectUserId([...res.userid]);
                                            setSelectUsername([...res.username]);
                                        }
                                    }

                                    setPreview(true);
                                    if ([4].includes(item.typeid)) {
                                        setTimeout(() => {
                                            if (res?.content) {
                                                formRef?.current?.setFieldsValue({ content: res?.content })
                                            }
                                        }, 800);
                                    } else if ([3, 6].includes(item.typeid)) {
                                        if (res?.data && res?.data.length > 0) {
                                            const contentValue: any = {};
                                            res?.data.forEach((element: any) => {
                                                contentValue['content_' + element.varid] = element.varvalue;
                                            });
                                            setTimeout(() => {
                                                formRef?.current?.setFieldsValue(contentValue)
                                            }, 800);
                                        }
                                    }
                                    return false;
                                }}
                            >
                                <Card>
                                    <Meta avatar={<Avatar icon={item.icon} />} title={item.name} />
                                </Card>
                            </a>

                        </List.Item>
                    );
                }}
            />
            <Drawer
                title={settingTitle}
                width="100vw"
                visible={preview}
                onClose={() => {
                    setPreview(false);
                }}
                closable={true}
                extra={
                    <Space>
                        {[4, 5, 3, 6].includes(currentTypeId) && <Button type="primary" onClick={async () => {
                            const params: any = { action: 'save', typeid: currentTypeId };
                            if (currentTypeId == 5) {
                                if (selectUserId.length == 0) {
                                    message.warn('请选择用户')
                                    return;
                                }
                                params.userid = selectUserId;
                                params.username = selectUsername;
                            } else if (currentTypeId == 4) {
                                params.username = formRef?.current?.getFieldValue('content');
                                if (!params.username) {
                                    message.warn('请输入用户姓名！')
                                    return;
                                }
                            } else if ([3, 6].includes(currentTypeId)) {
                                params.values = formRef?.current?.getFieldsValue();
                            }
                            const res = await setting(params)
                            if (res.success) {
                                message.success('保存成功')
                            }
                        }}>保存</Button>}
                    </Space>
                }
            >

                {currentTypeId == 5 && <Row>
                    <Col span={16}>
                        <Divider plain>已选用户</Divider>
                        <div>
                            {selectUsername.map(usernameTag)}
                        </div>
                    </Col>
                    <Col span={8}>
                        <Card title="请选择用户" extra={<Button type="primary" onClick={async () => {
                            const keys = treeRef?.current.getCheckedKeys();
                            if (keys.length > 0) {
                                const res = await setting({ userid: keys, action: 'user' })
                                if (res?.data) {
                                    const userid = selectUserId;
                                    const username = selectUsername;
                                    res?.data.forEach((element: any) => {
                                        if (!userid.includes(element.userid)) {
                                            userid.push(element.userid);
                                            username.push(element.name);
                                        }
                                    });
                                    setSelectUserId([...userid]);
                                    setSelectUsername([...username]);
                                }
                            } else {
                                message.warn('请选择用户')
                            }

                        }}>添加</Button>} style={{ width: '100%' }}>
                            <DepartmentTree
                                showLeafIcon={true}
                                selectable={true}
                                // onSelect={onSelect}
                                checkable={true}
                                checkStrictly={false}
                                showUser={true}
                                ref={treeRef}
                                local={true}
                            />
                        </Card>
                    </Col>
                </Row>}

                {currentTypeId == 4 && <ProForm
                    layout="vertical"
                    formRef={formRef}
                    submitter={false}
                >
                    <p>每个用户姓名用半角逗号分隔</p>
                    <ProFormTextArea
                        colProps={{ md: 12, xl: 24 }}
                        label=""
                        name="content"
                        fieldProps={{
                            showCount: true,
                            allowClear: true,
                            rows: 20,
                        }}
                        placeholder="请输入用户姓名"
                        rules={[
                            {
                                required: true,
                                message: '请输入用户姓名！',
                            },
                        ]}
                    />
                </ProForm>}

                {currentTypeId == 3 && <ProForm
                    layout="vertical"
                    formRef={formRef}
                    submitter={false}
                >
                    <p>早餐提示信息</p>
                    <ProFormTextArea
                        colProps={{ md: 12, xl: 24 }}
                        label=""
                        name="content_3"
                        fieldProps={{
                            showCount: true,
                            allowClear: true,
                            rows: 3,
                        }}
                        placeholder="请输入提示内容"
                    />
                    <p>午餐提示信息</p>
                    <ProFormTextArea
                        colProps={{ md: 12, xl: 24 }}
                        label=""
                        name="content_1"
                        fieldProps={{
                            showCount: true,
                            allowClear: true,
                            rows: 3,
                        }}
                        placeholder="请输入提示内容"
                    />
                    <p>晚餐提示信息</p>
                    <ProFormTextArea
                        colProps={{ md: 12, xl: 24 }}
                        label=""
                        name="content_2"
                        fieldProps={{
                            showCount: true,
                            allowClear: true,
                            rows: 3,
                        }}
                        placeholder="请输入提示内容"
                    />
                </ProForm>}

                {[1, 2].includes(currentTypeId) && <>
                    <Card title="新增修改类型" style={{ width: '100%' }} className={style.myCard}>
                        <ProForm
                            layout="inline"
                            formRef={formRef1}
                            submitter={{
                                searchConfig: { submitText: '保存' },
                                resetButtonProps: {
                                    style: {
                                        // 隐藏重置按钮
                                        display: 'none',
                                    },
                                },
                            }}
                            onFinish={async (values: any) => {
                                if (values.varid && isNaN(values.varid)) {
                                    message.warn('请输入整数');
                                    return false;
                                }
                                values.action = 'save'
                                values.typeid = currentTypeId
                                if (currentEditId > 0) {
                                    values.id = currentEditId
                                }
                                const res = await setting(values)
                                if (res.errorMessage) {
                                    message.warn(res.errorMessage);
                                    return false;
                                }
                                if (res.success) {
                                    setCurrentEditId(0)
                                    formRef1?.current?.setFieldsValue({ varid: '', varvalue: '' })
                                    actionRef?.current?.reload()
                                }
                                return true;
                            }}
                        >
                            <ProFormText
                                name="varid"
                                width="sm"
                                label="类型ID"
                                placeholder="请输入类型ID"
                                rules={[
                                    {
                                        required: true,
                                        message: '请输入类型ID！',
                                    },
                                ]}
                                colProps={{ md: 12, xl: 24 }}
                            />
                            <ProFormText
                                name="varvalue"
                                width="sm"
                                label="类型名称"
                                placeholder="请输入类型名称"
                                rules={[
                                    {
                                        required: true,
                                        message: '请输入类型名称！',
                                    },
                                ]}
                                colProps={{ md: 12, xl: 24 }}
                            />
                        </ProForm>
                    </Card>
                    <ProTable<any, any>
                        headerTitle="账号结算类别列表"
                        actionRef={actionRef}
                        rowKey="id"
                        params={{ now }}
                        search={false}
                        request={(params, sorter, filter) => {
                            document.body.scrollTop = document.documentElement.scrollTop = 0;
                            params.action = 'show';
                            params.typeid = currentTypeId;
                            return setting(params);
                        }}
                        columns={columns}
                        rowSelection={false}
                        tableAlertRender={false}
                        toolBarRender={() => []}
                    />
                </>
                }

                {currentTypeId == 6 && <ProForm layout="vertical"
                    formRef={formRef}
                    submitter={false}>
                    <p>订餐截止时间：</p>
                    <ProFormSelect valueEnum={selectHour} name="content_1" placeholder="订餐截止时间" />
                    <p>代购开始时间：</p>
                    <ProFormSelect valueEnum={selectHour} name="content_2" placeholder="订餐截止时间" />
                    <p>代购截止时间：</p>
                    <ProFormSelect valueEnum={selectHour} name="content_3" placeholder="订餐截止时间" />
                </ProForm>}
            </Drawer>
        </>

    );
};

export default SettingTab;
