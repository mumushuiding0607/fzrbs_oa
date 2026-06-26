import React, { useEffect, useState } from 'react';
import { Select } from 'antd';
import { getapps } from './service';

interface AgentselectProps {
  value?: any;
  onChange?: (value: any) => void;
  multiple?: boolean;
  style?: React.CSSProperties;
  placeholder?: string;
}

const Agentselect: React.FC<AgentselectProps> = ({
  value,
  onChange,
  multiple = false,
  style,
  placeholder = '请选择应用',
}) => {
  const [options, setOptions] = useState<any[]>([]);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    const fetchApps = async () => {
      setLoading(true);
      try {
        const res: any = await getapps();
        if (res && res.data) {
          const formattedOptions = Object.entries(res.data).map(([key, val]: [any, any]) => ({
            value: key,
            label: val.text || val.label || key,
          }));
          setOptions(formattedOptions);
        }
      } catch (error) {
        console.error('Failed to fetch apps:', error);
      } finally {
        setLoading(false);
      }
    };
    fetchApps();
  }, []);

return (
    <Select
      value={multiple ? (value && value.split ? value.split(',') : (value || undefined)) : value}
      onChange={onChange}
      mode={multiple ? 'multiple' : undefined}
      options={options}
      loading={loading}
      style={{ width: '100%', ...style }}
      placeholder={placeholder}
      allowClear
      showSearch
      filterOption={(input, option) =>
        (option?.label as string)?.toLowerCase().includes(input.toLowerCase())
      }
    />
  );
};

export default Agentselect;