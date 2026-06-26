import React, { useEffect, useState } from 'react';
import { DatePicker } from 'antd';
import dayjs, { Dayjs } from 'dayjs';

const { RangePicker } = DatePicker;

// eslint-disable-next-line @typescript-eslint/no-explicit-any
type AnyFunction = (value: any) => void;

export type CustomRangePickerProps = {
  value?: string | [Dayjs, Dayjs] | null;
  onChange?: AnyFunction;
  format?: string;
  separator?: string;
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  [key: string]: any;
};

const CustomRangePicker = (props: CustomRangePickerProps) => {
  const { value, onChange, format = 'YYYY-MM-DD', separator = '至', ...restProps } = props;
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  const [innerValue, setInnerValue] = useState<any>(null);

  // 当外部传入字符串日期时，自动转换为dayjs对象
  useEffect(() => {
    if (value) {
      if (typeof value === 'string') {
        // 解析 "2026-02-28至2026-02-29" 格式
        const parts = value.split(separator);
        if (parts.length === 2) {
          const start = dayjs(parts[0].trim());
          const end = dayjs(parts[1].trim());
          if (start.isValid() && end.isValid()) {
            setInnerValue([start, end]);
          }
        }
      } else if (Array.isArray(value)) {
        setInnerValue(value);
      }
    } else {
      setInnerValue(null);
    }
  }, [value, separator]);

  // 值变化时自动转换成yyyy-mm-dd至yyyy-mm-dd格式
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  const handleChange = (dates: any) => {
    if (onChange) {
      if (dates && dates.length === 2) {
        // 输出 "yyyy-mm-dd至yyyy-mm-dd" 格式字符串
        const result = `${dates[0].format(format)}${separator}${dates[1].format(format)}`;
        onChange(result);
      } else {
        onChange(null);
      }
    }
  };

  return (
    <RangePicker
      {...restProps}
      format={format}
      value={innerValue}
      onChange={handleChange}
    />
  );
};

export default CustomRangePicker;
