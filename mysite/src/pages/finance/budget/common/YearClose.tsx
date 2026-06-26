import { Modal, Select, message } from 'antd';
import { useEffect, useState } from 'react';
import { getdictlist, savedict } from '../dict/service';

interface YearCloseSelectorProps {
  open: boolean;
  onClose: () => void;
}

const YearClose: React.FC<YearCloseSelectorProps> = ({ open, onClose }) => {
  const [selectedYear, setSelectedYear] = useState<string>('');
  
  useEffect(() => {
    if (open) {
      getdictlist({type:"结账年份"}).then((res: any) => {
        const year = res?.data?.[0]?.value || '';
        setSelectedYear(year);
      });
    }
  }, [open]);

  const yearOptions = Array.from({ length: 6 }, (_, i) => {
    const year = new Date().getFullYear() - i;
    return { label: year + '年', value: year };
  });

  const handleSave = async () => {
    if (!selectedYear) {
      message.warning('请选择结账年份');
      return;
    }
    try {
      await savedict({
        type: '结账年份',
        label: selectedYear,
        value:selectedYear,
      });
      message.success('结账年份设置成功');
      onClose();
    } catch (error) {
      message.error('保存失败');
    }
  };

  return (
    <Modal
      title="设置结账年份"
      visible={open}
      onCancel={onClose}
      onOk={handleSave}
    >
      <div style={{ padding: '20px 0' }}>
        <Select
          style={{ width: '100%' }}
          placeholder="请选择结账年份"
          value={selectedYear}
          onChange={setSelectedYear}
          options={yearOptions}
        />
        <div style={{ marginTop: 8, fontSize: 12, color: '#888' }}>
          设置后将影响非报业务计量表的结账状态
        </div>
      </div>
    </Modal>
  );
};

export default YearClose;
