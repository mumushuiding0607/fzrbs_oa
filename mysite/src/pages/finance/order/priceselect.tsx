import React, { useEffect, useRef, useState } from 'react';
import { Button, Input, Modal, Space, Table, message } from 'antd';
import { EditOutlined } from '@ant-design/icons';
import { getPricelist, getPriceByConditions, savePricelist } from './service';
import AddPrice from './AddPrice';

interface PriceSelectProps {
  value?: any;
  onChange?: (value: any, record?: any) => void;
  // 关联参数
  E_PID?: number | string;
  E_MID?: number | string;
  E_AdField_ID?: number | string;
  E_Color_ID?: number | string;
  E_AdSize_ID?: number | string;
  style?: any;
  placeholder?: string;
}

const PriceSelect: React.FC<PriceSelectProps> = ({
  value,
  onChange,
  E_PID,
  E_MID,
  E_AdField_ID,
  E_Color_ID,
  E_AdSize_ID,
  style,
  placeholder = '请选择刊例价',
}) => {
  const [priceValue, setPriceValue] = useState<any>(value);
  const [priceRecord, setPriceRecord] = useState<any>(null);
  const [editModalVisible, setEditModalVisible] = useState(false);
  const [listModalVisible, setListModalVisible] = useState(false);
  const isFirstQuery = useRef(true);

  // 监听外部传入的value变化
  useEffect(() => {
    setPriceValue(value);
  }, [value]);

  // 监听关联参数变化，自动查询刊例价
  useEffect(() => {
    queryPrice();
  }, [E_PID, E_MID, E_AdField_ID, E_Color_ID, E_AdSize_ID]);

  // 根据条件查询刊例价
  const queryPrice = async () => {
    // 只有当所有必要参数都存在时才查询
    if (!E_PID || !E_AdField_ID || !E_Color_ID || !E_AdSize_ID) {
      return;
    }

    // 第一次查询且有初始值，不覆盖
    if (isFirstQuery.current && value) {
      isFirstQuery.current = false;
      return;
    }
    isFirstQuery.current = false;

    try {
      const params: any = {
        E_PID: E_PID,
        E_MID: E_MID,
        E_AdField_ID: E_AdField_ID,
        E_Color_ID: E_Color_ID,
        E_AdSize_ID: E_AdSize_ID,
      };

      const res: any = await getPriceByConditions(params);
      if (res.success && res.data) {
        // 取ID最大的那条（后端已按ID降序排列）
        const priceData = Array.isArray(res.data) ? res.data[0] : res.data;
        if (priceData && priceData.E_Price) {
          setPriceValue(priceData.E_Price);
          setPriceRecord(priceData);
          onChange && onChange(priceData.E_Price, priceData);
        } else {
          setPriceValue(undefined);
          setPriceRecord(null);
          onChange && onChange(undefined, undefined);
        }
      } else {
        setPriceValue(undefined);
        setPriceRecord(null);
        onChange && onChange(undefined, undefined);
      }
    } catch (error) {
      console.error('查询刊例价失败:', error);
      setPriceValue(undefined);
      setPriceRecord(null);
    }
  };

  // 处理值变化
  const handleChange = (e: any) => {
    const val = e.target.value;
    setPriceValue(val);
    onChange && onChange(val, priceRecord);
  };

  // 打开列表弹窗
  const handleOpenList = () => {
    setListModalVisible(true);
  };

  // 从列表中选择
  const handleSelectFromList = (record: any) => {
    setPriceValue(record.E_Price);
    setPriceRecord(record);
    onChange && onChange(record.E_Price, record);
    setListModalVisible(false);
  };

  // 列表弹窗中的编辑成功
  const handleListEditSuccess = () => {
    // 刷新列表
    queryPrice();
  };

  return (
    <div style={{ display: 'flex', alignItems: 'center', width: '100%' }}>
      <Input
        style={{ ...style, flex: 1 }}
        value={priceValue}
        onChange={handleChange}
        placeholder={placeholder}
        suffix={
          <Button
            type="text"
            size="small"
            icon={<EditOutlined />}
            onClick={handleOpenList}
            style={{ marginRight: -4 }}
          />
        }
      />

      {/* 刊例价列表弹窗 */}
      <Modal
        title="刊例价管理"
        visible={listModalVisible}
        onCancel={() => setListModalVisible(false)}
        footer={null}
        width={1000}
        destroyOnClose
      >
        <PriceListModal
          E_PID={E_PID}
          E_MID={E_MID}
          E_AdField_ID={E_AdField_ID}
          E_Color_ID={E_Color_ID}
          E_AdSize_ID={E_AdSize_ID}
          onSelect={handleSelectFromList}
          onEditSuccess={handleListEditSuccess}
        />
      </Modal>
    </div>
  );
};

// 刊例价列表弹窗组件
const PriceListModal: React.FC<{
  E_PID?: number | string;
  E_MID?: number | string;
  E_AdField_ID?: number | string;
  E_Color_ID?: number | string;
  E_AdSize_ID?: number | string;
  onSelect?: (record: any) => void;
  onEditSuccess?: () => void;
}> = ({ E_PID, E_MID, E_AdField_ID, E_Color_ID, E_AdSize_ID, onSelect, onEditSuccess }) => {
  const [loading, setLoading] = useState(false);
  const [dataSource, setDataSource] = useState<any[]>([]);
  const [pagination, setPagination] = useState({ current: 1, pageSize: 10, total: 0 });
  const [editRecord, setEditRecord] = useState<any>(null);
  const [modalVisible, setModalVisible] = useState(false);

  const fetchData = async (page: number = 1, pageSize: number = 10) => {
    setLoading(true);
    try {
      const params: any = {
        current: page,
        pageSize: pageSize,
      };
      // 添加关联筛选参数
      if (E_PID) {
        params.E_PID = E_PID;
      }
      if (E_MID) {
        params.E_MID = E_MID;
      }
      if (E_AdField_ID) {
        params.E_AdField_ID = E_AdField_ID;
      }
      if (E_Color_ID) {
        params.E_Color_ID = E_Color_ID;
      }
      if (E_AdSize_ID) {
        params.E_AdSize_ID = E_AdSize_ID;
      }
      console.log('params', params);
      const res: any = await getPricelist(params);
      if (res) {
        setDataSource(res.data || []);
        setPagination({
          current: page,
          pageSize: pageSize,
          total: res.total || 0,
        });
      }
    } catch (error) {
      console.error('Failed to fetch price list:', error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchData();
  }, [E_PID]);

  const handleTableChange = (paginationConfig: any) => {
    fetchData(paginationConfig.current, paginationConfig.pageSize);
  };

  const handleEdit = (record: any) => {
    setEditRecord(record);
    setModalVisible(true);
  };

  const handleModalSuccess = () => {
    setModalVisible(false);
    setEditRecord(null);
    fetchData(pagination.current, pagination.pageSize);
    onEditSuccess?.();
  };

  const columns = [
    {
      title: 'ID',
      dataIndex: 'SYS_DOCUMENTID',
      key: 'SYS_DOCUMENTID',
      width: 60,
    },
    {
      title: '刊物',
      dataIndex: 'E_PID_label',
      key: 'E_PID',
      width: 100,
      render: (text: string, record: any) => text || record.E_PID,
    },
    {
      title: '投放日',
      dataIndex: 'E_MID_label',
      key: 'E_MID',
      width: 100,
      render: (text: string, record: any) => text || record.E_MID,
    },
    {
      title: '版位',
      dataIndex: 'E_AdField_ID_label',
      key: 'E_AdField_ID',
      width: 100,
      render: (text: string, record: any) => text || record.E_AdField_ID,
    },
    {
      title: '颜色',
      dataIndex: 'E_Color_ID_label',
      key: 'E_Color_ID',
      width: 80,
      render: (text: string, record: any) => text || record.E_Color_ID,
    },
    {
      title: '规格',
      dataIndex: 'E_AdSize_ID_label',
      key: 'E_AdSize_ID',
      width: 100,
      render: (text: string, record: any) => text || record.E_AdSize_ID,
    },
    {
      title: '单价',
      dataIndex: 'E_Price',
      key: 'E_Price',
      width: 100,
      render: (text: number) => (text ? `¥${text.toFixed(2)}` : ''),
    },
    {
      title: '操作',
      key: 'action',
      width: 120,
      render: (_: any, record: any) => (
        <Space>
          <Button type="link" size="small" onClick={() => onSelect?.(record)}>
            选用
          </Button>
          <Button
            type="link"
            size="small"
            icon={<EditOutlined />}
            onClick={() => handleEdit(record)}
          >
            编辑
          </Button>
        </Space>
      ),
    },
  ];

  return (
    <div>
      <div style={{ marginBottom: 16, textAlign: 'right' }}>
        <Button
          type="primary"
          onClick={() => {
            // 处理可能的对象类型值（如E_AdSize_ID可能是对象）
            const getValue = (val: any) => {
              if (val === null || val === undefined) return undefined;
              return typeof val === 'object' ? val.value ?? val.id ?? undefined : val;
            };
            // 传递当前筛选条件作为初始值，方便新增
            const initialData =
              E_PID || E_MID || E_AdField_ID || E_Color_ID || E_AdSize_ID
                ? {
                    E_PID: getValue(E_PID),
                    E_MID: getValue(E_MID),
                    E_AdField_ID: getValue(E_AdField_ID),
                    E_Color_ID: getValue(E_Color_ID),
                    E_AdSize_ID: getValue(E_AdSize_ID),
                  }
                : null;
            setEditRecord(initialData);
            setModalVisible(true);
          }}
        >
          新增刊例价
        </Button>
      </div>
      <Table
        columns={columns}
        dataSource={dataSource}
        rowKey="SYS_DOCUMENTID"
        loading={loading}
        pagination={{
          ...pagination,
          showSizeChanger: true,
          showQuickJumper: true,
          showTotal: (total) => `共 ${total} 条`,
        }}
        onChange={handleTableChange}
        size="small"
      />

      <Modal
        title={editRecord ? '编辑刊例价' : '新增刊例价'}
        visible={modalVisible}
        onCancel={() => {
          setModalVisible(false);
          setEditRecord(null);
        }}
        footer={null}
        width={600}
      >
        <AddPrice data={editRecord} onChange={handleModalSuccess} />
      </Modal>
    </div>
  );
};

export default PriceSelect;
