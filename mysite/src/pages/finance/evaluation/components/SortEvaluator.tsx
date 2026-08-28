import React, { useEffect, useState } from 'react';
import { Modal, Button, message } from 'antd';
import { getEvaluatorSeq, saveEvaluatorSeq } from '../service';

interface Evaluator {
  userid: string;
  name: string;
  seq: number;
}

const SortEvaluator: React.FC<{
  visible: boolean;
  onClose: () => void;
}> = ({ visible, onClose }) => {
  const [data, setData] = useState<Evaluator[]>([]);
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [draggedIndex, setDraggedIndex] = useState<number | null>(null);

  // 加载评分人列表
  const loadData = async () => {
    setLoading(true);
    try {
      const res: any = await getEvaluatorSeq();
      if (res.message) {
        message.error(res.message);
      } else {
        setData(res.data || []);
      }
    } catch (e) {
      message.error('加载失败');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (visible) {
      loadData();
    }
  }, [visible]);

  // 拖拽开始
  const handleDragStart = (index: number) => {
    setDraggedIndex(index);
  };

  // 拖拽结束
  const handleDragOver = (e: React.DragEvent, index: number) => {
    e.preventDefault();
    if (draggedIndex === null || draggedIndex === index) return;

    const newData = [...data];
    const [removed] = newData.splice(draggedIndex, 1);
    newData.splice(index, 0, removed);

    // 更新 seq
    newData.forEach((item, i) => {
      item.seq = i + 1;
    });

    setDraggedIndex(index);
    setData(newData);
  };

  // 拖拽结束
  const handleDrop = (e: React.DragEvent) => {
    e.preventDefault();
    setDraggedIndex(null);
  };

  // 保存排序
  const handleSave = async () => {
    setSaving(true);
    try {
      const orders = data.map((item) => ({
        userid: item.userid,
        seq: item.seq,
      }));
      const res: any = await saveEvaluatorSeq({ orders });
      if (res.message) {
        message.error(res.message);
      } else {
        message.success('保存成功');
        onClose();
      }
    } catch (e) {
      message.error('保存失败');
    } finally {
      setSaving(false);
    }
  };

  // 将数据分成每行10个
  const rows: Evaluator[][] = [];
  for (let i = 0; i < data.length; i += 10) {
    rows.push(data.slice(i, i + 10));
  }

  return (
    <Modal
      title="考核对象排序"
      visible={visible}
      onCancel={onClose}
      width={800}
      footer={[
        <Button key="cancel" onClick={onClose}>
          取消
        </Button>,
        <Button key="save" type="primary" loading={saving} onClick={handleSave}>
          保存
        </Button>,
      ]}
    >
      <div style={{ maxHeight: 500, overflow: 'auto', padding: '8px 0' }}>
        <p style={{ color: '#666', marginBottom: 16 }}>
          拖动卡片可调整顺序，每行显示10个。排序结果将影响打印时的顺序。
        </p>
        {loading ? (
          <div style={{ textAlign: 'center', padding: 40 }}>加载中...</div>
        ) : (
          <div>
            {rows.map((row, rowIndex) => (
              <div
                key={rowIndex}
                style={{
                  display: 'flex',
                  marginBottom: 8,
                  gap: 8,
                }}
              >
                {row.map((item, colIndex) => {
                  const globalIndex = rowIndex * 10 + colIndex;
                  const isDragging = draggedIndex === globalIndex;
                  return (
                    <div
                      key={item.userid}
                      draggable
                      onDragStart={() => handleDragStart(globalIndex)}
                      onDragOver={(e) => handleDragOver(e, globalIndex)}
                      onDrop={handleDrop}
                      onDragEnd={() => setDraggedIndex(null)}
                      style={{
                        flex: '0 0 calc(10% - 7.2px)',
                        minWidth: 60,
                        maxWidth: 80,
                        height: 36,
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        background: isDragging ? '#e6f7ff' : '#fafafa',
                        border: '1px solid #d9d9d9',
                        borderRadius: 4,
                        cursor: 'grab',
                        fontSize: 13,
                        userSelect: 'none',
                        boxShadow: isDragging ? '0 2px 8px rgba(0,0,0,0.15)' : 'none',
                        transition: 'box-shadow 0.2s, background 0.2s',
                      }}
                    >
                      {item.name}
                    </div>
                  );
                })}
              </div>
            ))}
          </div>
        )}
      </div>
    </Modal>
  );
};

export default SortEvaluator;