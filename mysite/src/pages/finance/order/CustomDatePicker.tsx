import React, { useEffect, useState } from 'react';
import { DatePicker } from 'antd';
import dayjs, { Dayjs } from 'dayjs';

// eslint-disable-next-line @typescript-eslint/no-explicit-any
type AnyFunction = (value: any, dayjsValue?: Dayjs | null) => void;

export type CustomDatePickerProps = {
  value?: string | Dayjs | null;
  onChange?: AnyFunction;
  format?: string;
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  [key: string]: any;
};

const CustomDatePicker = (props: CustomDatePickerProps) => {
  const { value, onChange, format = 'YYYY-MM-DD', ...restProps } = props;
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  const [innerValue, setInnerValue] = useState<any>(null);

  // 当外部传入字符串日期时，自动转换为dayjs对象
  useEffect(() => {
    if (value) {
      console.log('value', value);
      if (typeof value === 'string') {
        // 尝试解析各种日期格式
        const parsed = dayjs(value);
        if (parsed.isValid()) {
          setInnerValue(parsed);
        }
      } else {
        setInnerValue(value);
      }
    } else {
      setInnerValue(null);
    }
  }, [value]);

  // 值变化时自动转换成yyyy-mm-dd格式
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  const handleChange = (date: any) => {
    if (onChange) {
      if (date) {
        // 输出yyyy-mm-dd格式字符串
        onChange(date.format(format), date);
      } else {
        onChange(null, null);
      }
    }
  };

  return (
    <DatePicker
      {...restProps}
      format={format}
      value={innerValue}
      onChange={handleChange}
    />
  );
};

export default CustomDatePicker;
