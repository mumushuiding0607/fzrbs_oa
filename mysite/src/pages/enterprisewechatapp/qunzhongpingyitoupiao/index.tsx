import { CheckCard, ModalForm, PageContainer, ProFormRadio, ProFormText } from '@ant-design/pro-components';
import { Divider, List, Modal, Tag, message } from 'antd';
import React, { useEffect, useRef, useState } from 'react';
import { history, useModel } from 'umi';
import { rule, viewinfo, saveVote } from './service';
import styles from './style.less';
import styles1 from '../style.less';

const VoteList: React.FC = () => {
    const { initialState } = useModel('@@initialState');
    const { currentUser } = initialState;
    const query = history.location.query;

    const formRef = useRef<any>();
    const [modalVisible, setModalVisible] = useState<boolean>(false);
    const [isInfoId, setIsInfoId] = useState<any>();
    const [isUserInfoData, setIsUserInfoData] = useState<any[]>([]);
    const [submitVisible, setSubmitVisible] = useState<any>('block');
    const [isPage, setIsPage] = useState<any>(1);
    const [isPageSize, setIsPageSize] = useState<any>(20);
    const [listData, setListData] = useState<any[]>([]);
    const [listTotal, setListTotal] = useState<any>();

    const getdata = async (page: number, size: number) => {
        const newdata = await rule({ 'current': page, 'pageSize': size });
        if (newdata?.total) {
            setListTotal(parseInt(newdata.total));
        }
        if (newdata?.data) {
            setListData(newdata.data);
        }
    }

    const paginationProps = {
        showTotal: (total: any, range: any[]) => `第  ${range[0]}-${range[1]} 条/总共 ${total} 条`,
        total: listTotal,
        showSizeChanger: true,
        showQuickJumper: true,
        defaultPageSize: isPageSize,
        current: isPage,
        onChange: async (page: number, size: number) => {
            document.body.scrollTop = document.documentElement.scrollTop = 0;
            const newdata = await rule({ 'current': page, 'pageSize': size });
            if (newdata.data) {
                setIsPage(page);
                setIsPageSize(size);
                setListData(newdata.data);
            }
        },
    };

    useEffect(() => {
        if (!currentUser.wxuserid || currentUser.wxuserid == '') {
            Modal.confirm({
                content: '您还未绑定企业微信号，请先绑定企业微信号',
                okText: '去绑定',
                onOk: () => {
                    history.push('/account/settings/?key=binding');
                }
            });
        }
        if (query.iframe) {
            const header = document.getElementsByTagName('header');
            if (header.length > 0) {
                header.forEach((element: any) => {
                    element.style.display = 'none';
                });
            }
        }
        getdata(isPage, isPageSize);
    }, []);

    const handleView = async (_selid: number) => {
        const hide = message.loading('获取中...');
        try {
            const view_info = await viewinfo({ id: _selid });
            if (view_info.errorCode) {
                hide();
                message.warning(view_info.errorMessage);
                return false;
            }
            if (view_info.data) {
                hide();
                setModalVisible(true);
                let values = view_info.data;
                let userselect = window.localStorage.getItem('fzbrs_oa_review_' + _selid);
                if (userselect != null) {
                    userselect = JSON.parse(userselect);
                    values = { ...values, ...userselect };
                }
                formRef?.current?.setFieldsValue(values);
                setIsInfoId(_selid);
                setIsUserInfoData(view_info.data.data);
            }
            return true;
        } catch (error) {
            hide();
            return false;
        }
    };

    const onChangeCard = (value: any) => {
        setSubmitVisible('block');
        if (value[1] == '已评议') {
            setSubmitVisible('none');
        }
        if (value) {
            handleView(value[0]);
        }
    };

    return (
        <>
            <PageContainer
                header={{
                    title: (
                        <>
                            {query.icon && (
                                <img
                                    src={query.icon}
                                    width={40}
                                    height={40}
                                    style={{ borderRadius: 20, marginRight: 5 }}
                                />
                            )}
                            {query.title ? query.title : '企业应用'}
                        </>
                    ),
                }}
                className={query.iframe ? styles1.iframePage : ''}
            >
                <CheckCard.Group
                    onChange={onChangeCard}
                    style={{ width: '100%' }}
                >
                    <List
                        pagination={paginationProps}
                        itemLayout="horizontal"
                        dataSource={listData}
                        renderItem={(item, index) => (
                            <CheckCard
                                disabled={parseInt(item.over) == 1}
                                key={'card-' + index}
                                title={
                                    <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'flex-start' }}>
                                        <Tag color={parseInt(item.over) == 1 ? '#ccc' : (item.voteflag == '已评议' ? 'blue' : 'green')}>{parseInt(item.over) == 1 ? '已结束' : item.voteflag}</Tag>
                                        <span>{item.title}</span>
                                    </div>
                                }
                                description={
                                    <div style={{ display: 'flex', flexDirection: 'column' }}>
                                        <span>开始时间：{item.starttime}</span>
                                        <span>截止时间：{item.endtime}</span>
                                    </div>
                                }
                                value={[item.id, item.voteflag]}
                            />
                        )}
                    />
                </CheckCard.Group>
            </PageContainer>

            <ModalForm
                title="我要评议"
                formRef={formRef}
                visible={modalVisible}
                className={styles.votelist}
                onVisibleChange={setModalVisible}
                autoFocusFirstInput
                modalProps={{
                    maskClosable: false,
                    destroyOnClose: true,
                }}
                submitTimeout={2000}
                onFinish={async (values: any) => {
                    const postData = { data: values, id: isInfoId };
                    const res = await saveVote(postData);
                    if (res.errorCode) {
                        message.warning(res.errorMessage);
                        return false;
                    } else {
                        message.success('评议成功');
                        setModalVisible(false);
                        getdata(isPage, isPageSize);
                        delete values.title;
                        window.localStorage.setItem('fzbrs_oa_review_' + isInfoId, JSON.stringify(values));
                        return true;
                    }
                }}
                submitter={{
                    resetButtonProps: { style: { display: submitVisible } },
                    submitButtonProps: { style: { display: submitVisible } }
                }}
            >
                <ProFormText
                    name="title"
                    disabled={true}
                />
                {isUserInfoData &&
                    <List
                        className={styles.votelist}
                        dataSource={isUserInfoData}
                        style={{ height: 500, overflowY: 'scroll' }}
                        renderItem={(item) => (
                            <>
                                <Divider orientation="center">{item.stitle}(可评优秀 {item.snum} 人)</Divider>
                                <List
                                    bordered
                                    dataSource={item.info}
                                    renderItem={(item_1) => (
                                        <List.Item>
                                            <ProFormRadio.Group
                                                name={item_1.userid}
                                                label={item_1.name}
                                                fieldProps={{
                                                    defaultValue: '2'
                                                }}
                                                options={[
                                                    { label: '优秀', value: '1', },
                                                    { label: '合格', value: '2', },
                                                    { label: '基本合格', value: '3', },
                                                    { label: '不合格', value: '4', },
                                                ]} />
                                        </List.Item>
                                    )}
                                />
                            </>
                        )}
                    />
                }
            </ModalForm>
        </>
    );
};
export default VoteList;
