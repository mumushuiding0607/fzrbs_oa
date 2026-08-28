import React, { useEffect, useState } from 'react';
import {
  Card,
  Table,
  Button,
  InputNumber,
  Select,
  Row,
  Col,
  Modal,
  Form,
  message,
  Space,
  Input,
  Tag,
} from 'antd';
import { getDepartments, savePenaltyInput, calculatePenalty } from './service';

const { Option } = Select;

/**
 * 扣罚录入页面
 */
interface PersonItem {
  key: string;
  user_id: string;
  user_name: string;
  deduct_type: 0 | 1;
  post_performance?: number;
  fixed_deduct?: number;
}

interface DeptItem {
  key: string;
  dept_id: number;
  dept_name: string;
  total_deduct: number;
  dept_person_count: number;
  persons: PersonItem[];
}

const PenaltyInput: React.FC = () => {
  const [loading, setLoading] = useState(false);
  const [year, setYear] = useState(new Date().getFullYear());
  const [quarter, setQuarter] = useState(Math.ceil((new Date().getMonth() + 1) / 3));
  const [dataSource, setDataSource] = useState<DeptItem[]>([]);
  const [personModalVisible, setPersonModalVisible] = useState(false);
  const [editingDept, setEditingDept] = useState<DeptItem | null>(null);
  const [personForm] = Form.useForm();

  // 加载部门列表
  const loadDepartments = async () => {
    try {
      const res: any = await getDepartments();
      if (!res.message && res.data) {
        const initialData: DeptItem[] = res.data.map((dept: any) => ({
          key: String(dept.id),
          dept_id: dept.id,
          dept_name: dept.name,
          total_deduct: 0,
          dept_person_count: 0,
          persons: [],
        }));
        setDataSource(initialData);
      }
    } catch (e) {
      message.error('加载部门失败');
    }
  };

  useEffect(() => {
    loadDepartments();
  }, []);

  // 更新部门数据
  const updateDept = (key: string, field: string, value: any) => {
    setDataSource((prev) =>
      prev.map((item) => (item.key === key ? { ...item, [field]: value } : item))
    );
  };

  // 打开人员弹窗
  const openPersonModal = (dept: DeptItem) => {
    setEditingDept(dept);
    personForm.setFieldsValue({
      persons: dept.persons.length > 0 ? [...dept.persons] : [],
    });
    setPersonModalVisible(true);
  };

  // 保存人员
  const handleSavePersons = () => {
    const persons: PersonItem[] = personForm.getFieldValue('persons') || [];
    // 验证
    for (const p of persons) {
      if (!p.user_name) {
        message.error('请输入员工姓名');
        return;
      }
      if (p.deduct_type === 0 && (!p.post_performance || p.post_performance <= 0)) {
        message.error('请输入有效的岗位绩效');
        return;
      }
      if (p.deduct_type === 1 && (!p.fixed_deduct || p.fixed_deduct <= 0)) {
        message.error('请输入有效的固定扣罚金额');
        return;
      }
    }
    if (editingDept) {
      updateDept(editingDept.key, 'persons', persons);
    }
    setPersonModalVisible(false);
  };

  // 保存录入
  const handleSave = async () => {
    // 验证
    for (const dept of dataSource) {
      if (dept.total_deduct <= 0) {
        message.error(`请为 ${dept.dept_name} 输入部门总扣罚金额`);
        return;
      }
      if (dept.dept_person_count <= 0) {
        message.error(`请为 ${dept.dept_name} 输入部门总人数`);
        return;
      }
      if (dept.dept_person_count <= dept.persons.length) {
        message.error(`${dept.dept_name} 部门人数必须大于被批评人数`);
        return;
      }
    }

    setLoading(true);
    try {
      const items = dataSource.map((dept) => ({
        dept_id: dept.dept_id,
        dept_name: dept.dept_name,
        total_deduct: dept.total_deduct,
        dept_person_count: dept.dept_person_count,
        persons: dept.persons.map((p) => ({
          user_id: p.user_id,
          user_name: p.user_name,
          deduct_type: p.deduct_type,
          post_performance: p.post_performance || 0,
          fixed_deduct: p.fixed_deduct || 0,
        })),
      }));

      const res: any = await savePenaltyInput({ year, quarter, items });
      if (!res.message) {
        message.success('保存成功');
      } else {
        message.error(res.message || '保存失败');
      }
    } catch (e) {
      message.error('保存失败');
    } finally {
      setLoading(false);
    }
  };

  // 计算分摊
  const handleCalculate = async () => {
    setLoading(true);
    try {
      const res: any = await calculatePenalty({ year, quarter });
      if (!res.message) {
        message.success('计算完成');
      } else {
        message.error(res.message || '计算失败');
      }
    } catch (e) {
      message.error('计算失败');
    } finally {
      setLoading(false);
    }
  };

  const columns = [
    {
      title: '部门',
      dataIndex: 'dept_name',
      key: 'dept_name',
      width: 150,
    },
    {
      title: '总扣罚金额(元)',
      dataIndex: 'total_deduct',
      key: 'total_deduct',
      width: 150,
      render: (_: any, record: DeptItem) => (
        <InputNumber
          min={0}
          precision={2}
          style={{ width: '100%' }}
          value={record.total_deduct}
          onChange={(val) => updateDept(record.key, 'total_deduct', val || 0)}
          placeholder="请输入"
        />
      ),
    },
    {
      title: '部门总人数',
      dataIndex: 'dept_person_count',
      key: 'dept_person_count',
      width: 120,
      render: (_: any, record: DeptItem) => (
        <InputNumber
          min={0}
          style={{ width: '100%' }}
          value={record.dept_person_count}
          onChange={(val) => updateDept(record.key, 'dept_person_count', val || 0)}
          placeholder="请输入"
        />
      ),
    },
    {
      title: '被批评人数',
      dataIndex: 'personCount',
      key: 'personCount',
      width: 100,
      render: (_: any, record: DeptItem) => record.persons.length,
    },
    {
      title: '被批评员工',
      key: 'persons',
      render: (_: any, record: DeptItem) => (
        <Space direction="vertical" size="small">
          {record.persons.map((p) => (
            <Tag key={p.key} color="red">
              {p.user_name}
              {p.deduct_type === 0
                ? ` (绩效×30%: ${p.post_performance})`
                : ` (固定: ${p.fixed_deduct})`}
            </Tag>
          ))}
          <Button type="link" size="small" onClick={() => openPersonModal(record)}>
            {record.persons.length > 0 ? '编辑' : '添加'}被批评员工
          </Button>
        </Space>
      ),
    },
  ];

  return (
    <div style={{ padding: 24 }}>
      <Card
        title="扣罚录入"
        extra={
          <Space>
            <Select value={year} onChange={setYear} style={{ width: 100 }}>
              {Array.from({ length: 5 }, (_, i) => new Date().getFullYear() - i).map((y) => (
                <Option key={y} value={y}>
                  {y}年
                </Option>
              ))}
            </Select>
            <Select value={quarter} onChange={setQuarter} style={{ width: 80 }}>
              {[1, 2, 3, 4].map((q) => (
                <Option key={q} value={q}>
                  Q{q}
                </Option>
              ))}
            </Select>
            <Button onClick={handleCalculate} loading={loading}>
              计算分摊
            </Button>
            <Button type="primary" onClick={handleSave} loading={loading}>
              保存录入
            </Button>
          </Space>
        }
      >
        <Table
          rowKey="key"
          columns={columns}
          dataSource={dataSource}
          pagination={false}
          scroll={{ x: 800 }}
        />
      </Card>

      {/* 被批评员工弹窗 */}
      <Modal
        title={`为 "${editingDept?.dept_name}" 添加被批评员工`}
        open={personModalVisible}
        onCancel={() => setPersonModalVisible(false)}
        onOk={handleSavePersons}
        width={700}
        okText="保存"
        cancelText="取消"
      >
        <Form form={personForm} layout="vertical">
          <Form.List name="persons">
            {(fields, { add, remove }) => (
              <>
                {fields.map((field) => (
                  <Card
                    key={field.key}
                    size="small"
                    style={{ marginBottom: 16 }}
                    title={`员工 ${field.name + 1}`}
                    extra={
                      <Button type="link" danger onClick={() => remove(field.name)}>
                        删除
                      </Button>
                    }
                  >
                    <Row gutter={12}>
                      <Col span={8}>
                        <Form.Item
                          name={[field.name, 'user_name']}
                          label="员工姓名"
                          rules={[{ required: true, message: '请输入员工姓名' }]}
                        >
                          <Input placeholder="请输入" />
                        </Form.Item>
                      </Col>
                      <Col span={8}>
                        <Form.Item
                          name={[field.name, 'deduct_type']}
                          label="扣罚方式"
                          initialValue={0}
                        >
                          <Select>
                            <Option value={0}>岗位绩效×30%</Option>
                            <Option value={1}>固定金额</Option>
                          </Select>
                        </Form.Item>
                      </Col>
                      <Col span={8}>
                        <Form.Item shouldUpdate noStyle>
                          {() => {
                            const persons = personForm.getFieldValue('persons') || [];
                            const deductType = persons[field.name]?.deduct_type;
                            if (deductType === 0) {
                              return (
                                <Form.Item name={[field.name, 'post_performance']} label="岗位绩效">
                                  <InputNumber
                                    min={0}
                                    precision={2}
                                    style={{ width: '100%' }}
                                  />
                                </Form.Item>
                              );
                            } else {
                              return (
                                <Form.Item name={[field.name, 'fixed_deduct']} label="固定扣罚">
                                  <InputNumber
                                    min={0}
                                    precision={2}
                                    style={{ width: '100%' }}
                                  />
                                </Form.Item>
                              );
                            }
                          }}
                        </Form.Item>
                      </Col>
                    </Row>
                  </Card>
                ))}
                <Button type="dashed" onClick={() => add()} block>
                  + 添加员工
                </Button>
              </>
            )}
          </Form.List>
        </Form>
      </Modal>
    </div>
  );
};

export default PenaltyInput;