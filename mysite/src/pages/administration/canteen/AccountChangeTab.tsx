import type { ProColumns, ActionType } from '@ant-design/pro-table';
import ProTable from '@ant-design/pro-table';
import React, { useEffect, useRef, useState } from 'react';
import { accountChange, type, accountChangeCreate } from './components/list/service';
import { Button, message, Space, Modal } from 'antd';
import { ProFormSelect } from '@ant-design/pro-components';
import { ExportOutlined, PlusSquareOutlined } from '@ant-design/icons';
import tools from '@/utils/tools';
import { useModel } from 'umi';
import OrderDownloadModal from './components/OrderDownloadModal';

var myDate = new Date();
const selectMonth = {
  '1': '1月',
  '2': '2月',
  '3': '3月',
  '4': '4月',
  '5': '5月',
  '6': '6月',
  '7': '7月',
  '8': '8月',
  '9': '9月',
  '10': '10月',
  '11': '11月',
  '12': '12月',
};
const currentDate = new Date();
const thisYear = currentDate.getFullYear();
const thisMonth = myDate.getMonth().toString();
const selectYear = {};
for (let i = 2021; i <= thisYear + 1; i++) {
  selectYear[i] = i.toString() + '年';
}

const AccountChangeTab = React.forwardRef((props, ref) => {
  const actionRef = useRef<ActionType>();
  const [searchFlag, setSearchFlag] = useState<boolean>(false);
  const [userType, setUserType] = useState<any>({});
  const { initialState } = useModel('@@initialState');
  const { currentUser } = initialState ?? {};
  const modalRef = useRef<any>();

  const columns: ProColumns<any>[] = [
    {
      title: '用户分类',
      key: 'userType',
      dataIndex: 'userType',
      hideInTable: true,
      renderFormItem: (item, { type, defaultRender, ...rest }, form) => {
        return <ProFormSelect valueEnum={userType} name="userType" placeholder="请选择用户分类" />;
      },
      fieldProps: {
        labelInValue: '',
      },
      colSize:2,
    },
    {
      title: '年份',
      key: 'year',
      dataIndex: 'year',
      hideInTable: true,
      renderFormItem: (item, { type, defaultRender, ...rest }, form) => {
        return <ProFormSelect valueEnum={selectYear} name="year" placeholder="请选择年份" />;
      },
      initialValue: thisYear,
    },
    {
      title: '月份',
      key: 'month',
      dataIndex: 'month',
      hideInTable: true,
      renderFormItem: (item, { type, defaultRender, ...rest }, form) => {
        return <ProFormSelect valueEnum={selectMonth} name="month" placeholder="请选择月份" />;
      },
      initialValue: thisMonth,
    },
    {
      title: '姓名',
      dataIndex: 'username',
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
                  let params = {};
                  if (values.userType) {
                    params.userType = values.userType;
                  }
                  if (values.year) {
                    params.year = values.year;
                  }
                  if (values.month) {
                    params.month = values.month;
                    if (params.month.length == 1) {
                      params.month = '0' + params.month;
                    }
                  }
                  if (values.username) {
                    params.username = values.username;
                  }
                  let fileName = '食堂账户余额变动情况表';
                  if (values.userType) {
                    fileName = fileName + '(' + userType[values.userType[0]].text + ')';
                  }
                  tools.downloadFile(
                    '/api/canteen/accountChangeDownload',
                    params,
                    fileName + '.xls',
                  );
                }}
              >
                <ExportOutlined />
                导出数据
              </Button>
              {currentUser?.access == 'admin' && (
                <Button
                  key="create"
                  onClick={() => {
                    const values = form?.getFieldsValue();
                    let params = {};
                    if (values.userType) {
                      params.userType = values.userType;
                    }
                    if (values.year) {
                      params.year = values.year;
                    }
                    if (values.month) {
                      params.month = values.month;
                      if (params.month.length == 1) {
                        params.month = '0' + params.month;
                      }
                    }
                    if (params.year && params.month) {
                      Modal.confirm({
                        title: '系统提示',
                        content:
                          '确定要生成' +
                          (params.userType ? userType[params.userType].text : '') +
                          params.year +
                          '-' +
                          params.month +
                          '的数据吗？',
                        okText: '确认',
                        cancelText: '取消',
                        onOk: async () => {
                          const hide = message.loading('正在生成');
                          const result = await accountChangeCreate(params);
                          hide();
                          if (result.errorCode) {
                            message.warn(result.errorMessage);
                          } else {
                            message.success('生成成功');
                            actionRef?.current?.reload();
                          }
                        },
                      });
                    } else {
                      message.warn('请选择要生成的月份');
                    }
                  }}
                >
                  <PlusSquareOutlined />
                  生成数据
                </Button>
              )}
            </Space>
          </>
        );
      },
    },
    {
      title: '所在部门',
      dataIndex: 'departmentname',
      hideInSearch: true,
    },
    {
      title: '月份',
      dataIndex: 'howmonth',
      hideInSearch: true,
    },
    {
      title: '月初余额(餐补+微信)',
      dataIndex: 'all_startbalance',
      hideInSearch: true,
    },
    {
      title: '月内充值金额(餐补+微信)',
      dataIndex: 'all_acountpay',
      hideInSearch: true,
    },
    {
      title: '月内消费金额(餐补+微信)',
      dataIndex: 'all_use',
      hideInSearch: true,
    },
    {
      title: '月末余额(餐补+微信)',
      dataIndex: 'all_endbalance',
      hideInSearch: true,
    },
  ];

  useEffect(() => {
    type().then((res) => {
      setUserType(res.data);
    });
  }, []);

  return (
    <>
      <ProTable<any, any>
        headerTitle="每月账号余额变动情况列表"
        actionRef={actionRef}
        rowKey="id"
        search={{
          labelWidth: 'auto',
          span: {
            xs: 24,
            sm: 24,
            md: 12,
            lg: 12,
            xl: 3,
            xxl: 3,
          },
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
            // <Button
            //   key="export"
            //   onClick={() => {
            //     const values = searchConfig?.form?.getFieldsValue();
            //     let params = {};
            //     if (values.userType) {
            //       params.userType = values.userType;
            //     }
            //     if (values.year) {
            //       params.year = values.year;
            //     }
            //     if (values.month) {
            //       params.month = values.month;
            //       if (params.month.length == 1) {
            //         params.month = '0' + params.month;
            //       }
            //     }
            //     if (values.username) {
            //       params.username = values.username;
            //     }
            //     let fileName = '食堂账户余额变动情况表';
            //     if (values.userType) {
            //       fileName = fileName + '(' + userType[values.userType[0]].text + ')';
            //     }
            //     tools.downloadFile('/api/canteen/accountChangeDownload', params, fileName + '.xls');
            //   }}
            // >
            //   <ExportOutlined />
            //   导出数据
            // </Button>,
            // <Button
            //   key="create"
            //   onClick={() => {
            //     const values = searchConfig?.form?.getFieldsValue();
            //     let params = {};
            //     if (values.userType) {
            //       params.userType = values.userType;
            //     }
            //     if (values.year) {
            //       params.year = values.year;
            //     }
            //     if (values.month) {
            //       params.month = values.month;
            //       if (params.month.length == 1) {
            //         params.month = '0' + params.month;
            //       }
            //     }
            //     if (params.userType && params.year && params.month) {
            //       Modal.confirm({
            //         title: '系统提示',
            //         content:
            //           '确定要生成' +
            //           userType[params.userType].text +
            //           params.year +
            //           '-' +
            //           params.month +
            //           '的数据吗？',
            //         okText: '确认',
            //         cancelText: '取消',
            //         onOk: async () => {
            //           message.loading('正在生成');
            //           const result = await accountChangeCreate(params);
            //           if (result.errorCode) {
            //             message.warn(result.errorMessage);
            //           } else {
            //             message.success('生成成功');
            //           }
            //         },
            //       });
            //     } else {
            //       message.warn('请选择要生成的分类和月份');
            //     }
            //   }}
            // >
            //   <PlusSquareOutlined />
            //   生成数据
            // </Button>,
          ],
        }}
        request={(params, sorter, filter) => {
          document.body.scrollTop = document.documentElement.scrollTop = 0;
          if (searchFlag) {
            params.search = 1;
          }
          if (params.month && params.month.length == 1) {
            params.month = '0' + params.month;
          }
          return accountChange(params);
        }}
        columns={columns}
        toolBarRender={() => [
          <Button
            htmlType="button"
            key="export"
            type="primary"
            onClick={() => {
              modalRef?.current.setVisible(true);
            }}
          >
            <ExportOutlined />
            订单数据导出
          </Button>
        ]}
      />
      <OrderDownloadModal ref={modalRef} />
    </>
  );
});

export default AccountChangeTab;
