import {
  ActionType,
  DrawerForm,
  ProColumns,
  ProDescriptions,
  ProForm,
  ProFormDatePicker,
  ProFormDigit,
  ProFormInstance,
  ProFormMoney,
  ProFormSelect,
  ProFormText,
  ProFormTextArea,
  ProTable,
} from '@ant-design/pro-components';
import { Alert, Button, Drawer, message, Modal } from 'antd';
import React, { useEffect, useRef, useState } from 'react';
import {
  menus,
  menuType,
  addMenu,
  updateMenu,
  removeMenu,
  updateMenuStatus,
} from './components/list/service';
import browser from '@/utils/browser';
import tools from '@/utils/tools';
import moment from 'moment';
import MyUploadFile from '@/components/MyUploadFile';
import { ArrowDownOutlined, ArrowUpOutlined, MinusOutlined, PlusOutlined } from '@ant-design/icons';

const weekday = { 1: '一', 2: '二', 3: '三', 4: '四', 5: '五', 6: '六', 7: '日' };

const MenuTab: React.FC = () => {
  const [showDetail, setShowDetail] = useState<boolean>(false);
  const [showForm, setShowForm] = useState<boolean>(false);
  const [currentRow, setCurrentRow] = useState<any>();
  const actionRef = useRef<ActionType>();
  const [myMenuType, setMyMenuType] = useState<any>({});
  const [defaultImage, setDefaultImage] = useState<any[]>([]);
  const formRef = useRef<ProFormInstance>();
  const [responseState, setResponseState] = useState<API.ErrorResponse>();
  const [selectedRowsState, setSelectedRows] = useState<any[]>([]);

  const handleRemove = async (selectedRows: any[], deleteRow: any) => {
    const hide = message.loading('正在删除');
    if (!selectedRows && !deleteRow) return true;

    try {
      let deleteIds = [];
      let result;
      if (selectedRows.length > 0) {
        deleteIds = selectedRows.map((row) => row.id);
        result = await removeMenu({
          id: deleteIds,
        });
      } else if (deleteRow) {
        deleteIds = [deleteRow].map((row) => row.id);
        result = await removeMenu({
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
      message.warn('删除失败，请重试');
      return false;
    }
  };

  const handleAddAndUpdate = async (updateRow: any, values: any) => {
    const hide = message.loading('正在保存');
    try {
      let result;
      if (updateRow == undefined) {
        result = await addMenu({
          values,
        });
      } else {
        result = await updateMenu({
          id: updateRow.id,
          values,
        });
      }
      hide();
      setResponseState(result);
      return result;
    } catch (error) {
      message.warn('保存失败！');
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

  const clearSelected = () => {
    setSelectedRows([]);
    actionRef?.current?.clearSelected();
  };

  const changeStatus = async (status) => {
    if (selectedRowsState.length == 0) {
      message.warn('请选择要操作的项目！');
      return;
    }
    const title = status == 0 ? '下线' : '上线';
    Modal.confirm({
      title: '系统提示',
      content: '确定' + title + '选中的项目吗？',
      okText: '确认',
      cancelText: '取消',
      onOk: async () => {
        let updateIds = [];
        updateIds = selectedRowsState.map((row) => row.id);
        await updateMenuStatus({ id: updateIds, values: { status } });
        clearSelected();
        actionRef.current?.reload?.();
      },
      onCancel: async () => clearSelected(),
    });
  };

  const columns: ProColumns<any>[] = [
    {
      title: '菜品名称',
      dataIndex: 'name',
      render: (dom, entity) => {
        return (
          <a
            onClick={() => {
              setCurrentRow(entity);
              clearSelected();
              setShowDetail(true);
            }}
          >
            {dom}
          </a>
        );
      },
    },
    {
      title: '供应日期',
      dataIndex: 'menudate1',
      hideInSearch: true,
      render: (dom, entity) => {
        return entity.menudate1
          ? entity.menudate1.substr(0, 4) +
          '-' +
          entity.menudate1.substr(4, 2) +
          '-' +
          entity.menudate1.substr(6, 2) +
          (entity.menudate == '' ? '' : '星期' + weekday[entity.menudate])
          : '';
      },
    },
    {
      title: '供应时段',
      dataIndex: 'menudate2',
      valueEnum: {
        0: {
          text: '全天',
        },
        1: {
          text: '午餐',
        },
        2: {
          text: '晚餐',
        },
        3: {
          text: '早餐',
        },
      },
      hideInSearch: true,
    },
    {
      title: '菜品价格',
      dataIndex: 'price',
      valueType: 'money',
      hideInSearch: true,
      sorter: true,
      render: (dom, entity) => {
        return tools.formatCurrency(entity.price / 100);
      },
    },
    {
      title: '菜品分类',
      dataIndex: 'typeid',
      valueEnum: myMenuType,
    },
    {
      title: '状态',
      dataIndex: 'status',
      valueEnum: {
        0: {
          text: '下线',
          status: 'Default',
        },
        1: {
          text: '上线',
          status: 'Success',
        },
      },
    },
    {
      title: '总预订数',
      dataIndex: 'buynum',
      hideInSearch: true,
      hideInForm: true,
      sorter: true,
    },
    {
      title: '预订限制数量',
      dataIndex: 'buylimit',
      hideInSearch: true,
      hideInForm: true,
      hideInTable: true,
      tip: '0或空为无限量',
    },
    {
      title: '库存数量',
      dataIndex: 'totallimit',
      hideInSearch: true,
      hideInForm: true,
      hideInTable: true,
      tip: '0或空为无限量',
    },
    {
      title: '点赞',
      dataIndex: 'support',
      hideInSearch: true,
      hideInForm: true,
      sorter: true,
    },
    {
      title: '评星',
      dataIndex: 'star',
      hideInSearch: true,
      hideInForm: true,
      sorter: true,
      render: (dom, entity) => {
        return entity.star ? entity.star.substr(0, 3) : '';
      },
    },
    {
      title: '添加时间',
      dataIndex: 'inserttime',
      hideInSearch: true,
      hideInForm: true,
      render: (dom, entity) => {
        return moment(entity.inserttime * 1000).format('YYYY-MM-DD HH:mm:ss');
      },
    },
    {
      title: '操作',
      dataIndex: 'option',
      valueType: 'option',
      render: (_, entity) => [
        <a
          key="edit"
          onClick={() => {
            setCurrentRow(entity);
            if (entity.image != '') {
              const url = entity.image.substr(0, 6) == 'assets' ? '/' + entity.image : entity.image;
              const image = {
                uid: entity.id.toString(),
                name: entity.name,
                status: 'done',
                url: url,
                thumbUrl: url,
              };
              setDefaultImage([image]);
            } else {
              setDefaultImage([]);
            }
            clearSelected();
            setShowForm(true);
          }}
        >
          修改
        </a>,
        <a
          key="delete"
          onClick={() => {
            clearSelected();
            deleteItem(entity);
          }}
        >
          删除
        </a>,
      ],
    },
  ];

  useEffect(() => {
    menuType().then((res) => {
      setMyMenuType(res.data);
    });
  }, []);

  return (
    <>
      <ProTable<any, any>
        headerTitle="菜品列表"
        actionRef={actionRef}
        rowKey="id"
        request={(params, sorter, filter) => {
          document.body.scrollTop = document.documentElement.scrollTop = 0;
          params = { ...params, sorter, filter };
          return menus(params);
        }}
        columns={columns}
        rowSelection={{
          onChange: (_, selectedRows) => {
            setSelectedRows(selectedRows);
          },
        }}
        tableAlertRender={false}
        toolBarRender={() => [
          <Button
            type="primary"
            key="new"
            onClick={() => {
              setCurrentRow(undefined);
              setDefaultImage([]);
              clearSelected();
              setShowForm(true);
            }}
          >
            <PlusOutlined /> 新建
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
                content: '确定删除选中的项目吗？',
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
            <MinusOutlined /> 批量删除
          </Button>,
          <Button
            type="primary"
            key="online"
            onClick={async () => {
              changeStatus(1);
            }}
          >
            <ArrowUpOutlined /> 上线
          </Button>,
          <Button
            type="primary"
            key="offline"
            onClick={async () => {
              changeStatus(0);
            }}
          >
            <ArrowDownOutlined /> 下线
          </Button>,
        ]}
      />
      <Drawer
        width={browser.mobile() ? '100vw' : 600}
        visible={showDetail}
        onClose={() => {
          setCurrentRow(undefined);
          setShowDetail(false);
        }}
        closable={true}
      >
        <ProDescriptions<any>
          column={1}
          title="详情"
          request={async () => ({
            data: currentRow || {},
          })}
          params={{
            id: currentRow?.id,
          }}
          columns={columns}
        />
      </Drawer>
      <DrawerForm
        title="编辑菜品信息"
        width={browser.mobile() ? '100vw' : 600}
        visible={showForm}
        onVisibleChange={setShowForm}
        formRef={formRef}
        autoFocusFirstInput
        drawerProps={{
          destroyOnClose: true,
          onClose: () => {
            setResponseState(undefined);
          },
        }}
        submitter={{ searchConfig: { submitText: '提交' } }}
        submitTimeout={2000}
        onFinish={async (values) => {
          setResponseState(undefined);
          if (values.upload && values.upload.length > 0) {
            values.image = values.upload[0].response.data.url;
          } else {
            if (!currentRow) {
              values.image = '';
            }
          }
          delete values.upload;
          const result = await handleAddAndUpdate(currentRow, values);
          if (result) {
            if (result.errorCode) {
              return false;
            }
            message.success('保存成功！');
            actionRef.current?.reload?.();
          }
          return true;
        }}
        initialValues={{
          ...currentRow,
          menudate: currentRow ? currentRow.menudate + '' : '1',
          menudate2: currentRow ? currentRow.menudate2 + '' : '1',
          typeid: currentRow ? currentRow.typeid + '' : '1',
          price: currentRow ? currentRow.price / 100 : '',
          status: currentRow ? currentRow.status + '' : '1',
          menudate1: currentRow && currentRow.menudate1 ? currentRow.menudate1 : null,
        }}
        layout="vertical"
        grid={true}
      >
        {responseState?.errorCode && (
          <Alert message={responseState?.errorMessage} type="warning" closable={true} showIcon />
        )}
        <ProForm.Group>
          <ProFormText
            name="name"
            width="sm"
            label="菜品名称"
            placeholder="请输入菜品名称"
            rules={[
              {
                required: true,
                message: '请输入菜品名称！',
              },
            ]}
            colProps={{ md: 12, xl: 24 }}
          />
        </ProForm.Group>
        <ProForm.Group>
          <ProFormMoney
            label="菜品价格"
            name="price"
            rules={[
              {
                required: true,
                message: '请输入菜品价格！',
              },
            ]}
            colProps={{ md: 12, xl: 16 }}
            fieldProps={{
              controls: false,
              addonAfter: '元',
            }}
          />
          <ProFormSelect
            name="status"
            label="状态"
            valueEnum={{
              0: '下线',
              1: '上线',
            }}
            colProps={{ md: 12, xl: 8 }}
            placeholder="请选择状态"
            rules={[
              {
                required: true,
                message: '请选择状态！',
              },
            ]}
          />
        </ProForm.Group>
        <ProForm.Group>
          <ProFormDatePicker
            colProps={{ md: 12, xl: 8 }}
            name="menudate1"
            label="供应日期"
            placeholder="请选择日期"
            rules={[
              {
                required: true,
                message: '请选择供应日期！',
              },
            ]}
          />
          <ProFormSelect
            name="menudate"
            label="星期"
            valueEnum={{
              1: '星期一',
              2: '星期二',
              3: '星期三',
              4: '星期四',
              5: '星期五',
              6: '星期六',
              7: '星期日',
            }}
            colProps={{ md: 12, xl: 8 }}
            placeholder="请选择星期"
            rules={[
              {
                required: true,
                message: '请选择供应星期！',
              },
            ]}
          />
          <ProFormSelect
            name="menudate2"
            label="时段"
            valueEnum={{
              0: '全天',
              1: '午餐',
              2: '晚餐',
              3: '早餐',
            }}
            colProps={{ md: 12, xl: 8 }}
            placeholder="请选择时段"
            rules={[
              {
                required: true,
                message: '请选择供应时段！',
              },
            ]}
          />
        </ProForm.Group>
        <ProForm.Group>
          <ProFormSelect
            name="typeid"
            label="分类"
            valueEnum={myMenuType}
            colProps={{ md: 12, xl: 8 }}
            placeholder="请选择分类"
            rules={[
              {
                required: true,
                message: '请选择分类！',
              },
            ]}
          />
          <ProFormDigit
            label="限购数量"
            name="buylimit"
            fieldProps={{ precision: 0 }}
            colProps={{ md: 12, xl: 8 }}
            tooltip="0或空为无限量"
          />
          <ProFormDigit
            label="库存数量"
            name="totallimit"
            fieldProps={{ precision: 0 }}
            colProps={{ md: 12, xl: 8 }}
            tooltip="0或空为无限量"
          />
        </ProForm.Group>
        <ProForm.Group>
          <ProFormTextArea colProps={{ md: 12, xl: 24 }} label="菜品详情" name="introduce" />
        </ProForm.Group>
        <MyUploadFile
          name="upload"
          label="菜品图片"
          max={1}
          multiple={false}
          accept="image/*"
          maxSize={3}
          listType="picture-card"
          defaultImage={defaultImage}
          uploadPath="canteen"
          uploadType={1}
        />
      </DrawerForm>
    </>
  );
};

export default MenuTab;
