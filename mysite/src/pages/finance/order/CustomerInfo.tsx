import { Input } from 'antd';
import React, { useState, useEffect } from 'react';

export interface CustomerInfoValue {
  name?: string;
  phone?: string;
  idnumber?: string;
}

export interface CustomerInfoProps {
  value?: CustomerInfoValue | string;
  onChange?: (value: string) => void;
  placeholder?: string[];
}

const CustomerInfo: React.FC<CustomerInfoProps> = (props) => {
  const { value, onChange, placeholder = ['姓名', '手机号', '身份证号'] } = props;

  const [name, setName] = useState<string>('');
  const [phone, setPhone] = useState<string>('');
  const [idnumber, setIdnumber] = useState<string>('');

  // 解析传入值
  useEffect(() => {
    if (!value) {
      setName('');
      setPhone('');
      setIdnumber('');
      return;
    }

    // 判断是对象还是字符串
    if (typeof value === 'object') {
      // JSON对象
      setName(value.name || '');
      setPhone(value.phone || '');
      setIdnumber(value.idnumber || '');
    } else if (typeof value === 'string') {
      // 字符串格式：name-phone-idnumber
      const parts = value.split('-');
      if (parts.length >= 3) {
        setName(parts[0] || '');
        setPhone(parts[1] || '');
        setIdnumber(parts[2] || '');
      } else if (parts.length === 2) {
        setName(parts[0] || '');
        setPhone(parts[1] || '');
        setIdnumber('');
      } else if (parts.length === 1) {
        setName(parts[0] || '');
        setPhone('');
        setIdnumber('');
      }
    }
  }, [value]);

  // 触发onChange
  const triggerChange = (newName: string, newPhone: string, newIdnumber: string) => {
    const newValue = `${newName}-${newPhone}-${newIdnumber}`;
    onChange?.(newValue);
  };

  // 各字段变化时触发
  const handleNameChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const newName = e.target.value;
    setName(newName);
    triggerChange(newName, phone, idnumber);
  };

  const handlePhoneChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const newPhone = e.target.value;
    setPhone(newPhone);
    triggerChange(name, newPhone, idnumber);
  };

  const handleIdnumberChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const newIdnumber = e.target.value;
    setIdnumber(newIdnumber);
    triggerChange(name, phone, newIdnumber);
  };

  return (
    <Input.Group compact>
      <Input
        style={{ width: '30%', textAlign: 'center' }}
        placeholder={placeholder[0]}
        value={name}
        onChange={handleNameChange}
        maxLength={50}
      />
      <Input
        style={{ width: '5%', borderLeft: 0, borderRight: 0, pointerEvents: 'none', backgroundColor: '#fff' }}
        placeholder="-"
        disabled
      />
      <Input
        style={{ width: '30%', textAlign: 'center' }}
        placeholder={placeholder[1]}
        value={phone}
        onChange={handlePhoneChange}
        maxLength={11}
      />
      <Input
        style={{ width: '5%', borderLeft: 0, borderRight: 0, pointerEvents: 'none', backgroundColor: '#fff' }}
        placeholder="-"
        disabled
      />
      <Input
        style={{ width: '30%', textAlign: 'center' }}
        placeholder={placeholder[2]}
        value={idnumber}
        onChange={handleIdnumberChange}
        maxLength={18}
      />
    </Input.Group>
  );
};

export default CustomerInfo;
