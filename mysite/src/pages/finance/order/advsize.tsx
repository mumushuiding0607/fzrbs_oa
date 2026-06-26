import React, { useEffect, useRef, useState } from 'react';
import { Button, Divider, Modal, Select, Table, Space, message } from 'antd';
import { EditOutlined, DeleteOutlined, PlusOutlined } from '@ant-design/icons';
import { request } from 'umi';

import AddAdvsize from './AddAdvsize';

interface AdvsizeProps {
  value?: any;
  onChange?: (value: any) => void;
  adTypeId?: number | string | null;
  placeholder?: string;
  style?: React.CSSProperties;
  selectFirst?: boolean;
}

const Advsize: React.FC<AdvsizeProps> = ({
  value,
  onChange,
  adTypeId,
  placeholder = '请选择规格',
  style,
  selectFirst = true,
}) => {
  const [options, setOptions] = useState<any[]>([]);
  const [loading, setLoading] = useState(false);
  const [adTypeIdRef, setAdTypeIdRef] = useState<any>(null);
  const [addModalVisible, setAddModalVisible] = useState(false);
  const [editModalVisible, setEditModalVisible] = useState(false);
  const [selectedSize, setSelectedSize] = useState<any>(null);
  const [dataSource, setDataSource] = useState<any[]>([]);
  const [pagination, setPagination] = useState({ current: 1, pageSize: 10, total: 0 });
  const adTypeChangeCount = useRef(0);

  // 打开新增弹窗
  const handleAddSize = () => {
    setSelectedSize(null);
    setAddModalVisible(true);
  };

  // 打开编辑弹窗
  const handleEditSize = () => {
    const currentValue = value?.value ?? value;
    if (currentValue) {
      // 查找当前选中的规格信息
      const currentOption = options.find((opt) => opt.value === currentValue);
      if (currentOption) {
        setSelectedSize({
          SYS_DOCUMENTID: currentOption.value,
          E_Name: currentOption.label,
          E_Width: currentOption.width,
          E_Height: currentOption.height,
          E_LayoutAmount: currentOption.layoutAmount,
        });
        setAddModalVisible(true);
      }
    }
  };

  // 新增/编辑成功后重新加载
  const handleSizeSuccess = (newData: any) => {
    setAddModalVisible(false);
    fetchSizes(adTypeId);
    // 选中新添加/更新的项
    if (newData && newData.id) {
      const newOption = options.find((opt) => opt.value === newData.id);
      if (newOption) {
        onChange &&
          onChange({
            value: newOption.value,
            label: newOption.label,
            width: newOption.width,
            height: newOption.height,
            layoutAmount: newOption.layoutAmount,
          });
      }
    }
  };

  // 打开完整规格管理弹窗
  const handleManageSize = () => {
    setEditModalVisible(true);
    fetchSizeList();
  };

  // 当规格管理弹窗打开时获取数据
  useEffect(() => {
    if (editModalVisible) {
      fetchSizeList();
    }
  }, [editModalVisible]);

  // 渲染下拉菜单
  const dropdownRender = (menu: any) => {
    return (
      <>
        {menu}
        <Divider style={{ margin: '8px 0' }} />
        <Button
          type="primary"
          onClick={handleAddSize}
          style={{ width: '100%', marginBottom: '8px' }}
        >
          <PlusOutlined /> 新增规格
        </Button>
        <Button 
          onClick={handleEditSize} 
          style={{ width: '100%', marginBottom: '8px' }}
        >
          <EditOutlined /> 编辑当前规格
        </Button>
        <Button 
          onClick={handleManageSize} 
          style={{ width: '100%' }}
        >
          <EditOutlined /> 修改
        </Button>
      </>
    );
  };

  // 根据广告类型获取规格选项
  const fetchSizes = async (adType?: number | string | null) => {
    setLoading(true);
    try {
      const params: any = {};
      if (adType) {
        params.adTypeId = adType;
      }

      const response = await request<{
        data: any[];
      }>('/api/advertisemanange/getsizesbyadtype', {
        method: 'GET',
        params: params,
      });

      if (response && Array.isArray(response.data)) {
        const formattedOptions = response.data.map((item) => ({
          value: item.SYS_DOCUMENTID,
          label: item.E_Name,
          width: item.E_Width,
          height: item.E_Height,
          layoutAmount: item.E_LayoutAmount,
        }));
        setOptions(formattedOptions);
        return formattedOptions;
      } else {
        setOptions([]);
        return [];
      }
    } catch (error) {
      console.error('Failed to fetch sizes:', error);
      setOptions([]);
    } finally {
      setLoading(false);
    }
  };

  // 自动选中第一项的逻辑
  const autoSelectFirst = (opts: any[]) => {
    if (selectFirst && opts.length > 0) {
      const firstOption = opts[0];
      onChange &&
        onChange({
          value: firstOption.value,
          label: firstOption.label,
          width: firstOption.width,
          height: firstOption.height,
          layoutAmount: firstOption.layoutAmount,
        });
    }
  };

  // 当广告类型变化时重新加载规格选项
  useEffect(() => {
    const prevAdTypeId = adTypeIdRef;
    const isFirstChange = prevAdTypeId === null || prevAdTypeId === undefined;

    fetchSizes(adTypeId).then((formattedOptions: any) => {
      if (prevAdTypeId !== adTypeId) {
        if (isFirstChange) {
          if (value) {
          } else if (autoSelectFirst && formattedOptions && formattedOptions.length > 0) {
            autoSelectFirst(formattedOptions);
          }
        } else if (autoSelectFirst) {
          onChange && onChange(null);
          if (formattedOptions && formattedOptions.length > 0) {
            autoSelectFirst(formattedOptions);
          }
        }
      }
    });

    setAdTypeIdRef(adTypeId);
  }, [adTypeId]);

  // 处理选择变化
  const handleChange = (selectedValue: any) => {
    if (selectedValue) {
      const selectedOption = options.find((opt) => opt.value === selectedValue.value);
      onChange &&
        onChange({
          value: selectedValue.value,
          label: selectedValue.label,
          width: selectedOption?.width,
          height: selectedOption?.height,
          layoutAmount: selectedOption?.layoutAmount,
        });
    } else {
      onChange && onChange(null);
    }
  };

  // 规格列表管理弹窗
  const fetchSizeList = async (page: number = 1, pageSize: number = 10) => {
    setLoading(true);
    try {
      const params: any = {
        current: page,
        pageSize: pageSize,
      };
      if (adTypeId) {
        params.adTypeId = adTypeId;
      }

      const response = await request<{
        data: any[];
        total?: number;
      }>('/api/advertisemanange/getsizeslist', {
        method: 'GET',
        params: params,
      });

      if (response && Array.isArray(response.data)) {
        setDataSource(response.data);
        setPagination({
          current: page,
          pageSize: pageSize,
          total: response.total || response.data.length,
        });
      }
    } catch (error) {
      console.error('Failed to fetch size list:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleDelete = (record: any) => {
    Modal.confirm({
      title: '确定要删除该规格吗？',
      okText: '确认',
      cancelText: '取消',
      onOk: async () => {
        try {
          const res = await request('/api/advertisemanange/deletesize', {
            method: 'GET',
            params: { id: record.SYS_DOCUMENTID },
          });
          if (res.errorMessage) {
            Modal.error({ title: res.errorMessage });
            return;
          }
          message.success('删除成功');
          fetchSizeList(pagination.current, pagination.pageSize);
          fetchSizes(adTypeId); // 刷新下拉选项
        } catch (error) {
          message.error('删除失败');
        }
      },
    });
  };

  const handleEditFromList = (record: any) => {
    setSelectedSize({
      SYS_DOCUMENTID: record.SYS_DOCUMENTID,
      E_Name: record.E_Name,
      E_Width: record.E_Width,
      E_Height: record.E_Height,
      E_LayoutAmount: record.E_LayoutAmount,
    });
    setEditModalVisible(false);
    setAddModalVisible(true);
  };

  const sizeColumns = [
    {
      title: 'ID',
      dataIndex: 'SYS_DOCUMENTID',
      width: 60,
    },
    {
      title: '规格名称',
      dataIndex: 'E_Name',
    },
    {
      title: '宽度',
      dataIndex: 'E_Width',
      width: 80,
    },
    {
      title: '高度',
      dataIndex: 'E_Height',
      width: 80,
    },
    {
      title: '版数',
      dataIndex: 'E_LayoutAmount',
      width: 80,
    },
    {
      title: '操作',
      key: 'action',
      width: 120,
      render: (_: any, record: any) => (
        <Space>
          <Button 
            type="link" 
            size="small" 
            icon={<EditOutlined />}
            onClick={() => handleEditFromList(record)}
          >
            编辑
          </Button>
          <Button 
            type="link" 
            size="small" 
            danger 
            icon={<DeleteOutlined />}
            onClick={() => handleDelete(record)}
          >
            删除
          </Button>
        </Space>
      ),
    },
  ];

  return (
    <>
      <Select
        labelInValue
        style={{ width: '100%', ...style }}
        placeholder={placeholder}
        value={value}
        onChange={handleChange}
        options={options}
        loading={loading}
        allowClear
        showSearch
        optionFilterProp="label"
        notFoundContent={loading ? '加载中...' : '暂无数据'}
        dropdownRender={dropdownRender}
      />

      {/* 新增/编辑规格弹窗 */}
      <Modal
        title={selectedSize ? '编辑规格' : '新增规格'}
        visible={addModalVisible}
        onCancel={() => {
          setAddModalVisible(false);
          setSelectedSize(null);
        }}
        footer={null}
        width={600}
      >
        <AddAdvsize 
          data={selectedSize} 
          onChange={(newData:any) => {
            handleSizeSuccess(newData);
            fetchSizeList();
          }} 
        />
      </Modal>

      {/* 规格管理列表弹窗 */}
      <Modal
        title="规格管理"
        visible={editModalVisible}
        onCancel={() => setEditModalVisible(false)}
        footer={null}
        width={800}
        destroyOnClose
      >
        <Table
          columns={sizeColumns}
          dataSource={dataSource}
          rowKey="SYS_DOCUMENTID"
          loading={loading}
          pagination={{
            ...pagination,
            showSizeChanger: true,
            showQuickJumper: true,
            showTotal: (total) => `共 ${total} 条`,
          }}
          onChange={(paginationConfig) => {
            fetchSizeList(paginationConfig.current, paginationConfig.pageSize);
          }}
          size="small"
        />
      </Modal>
    </>
  );
};

export default Advsize;
