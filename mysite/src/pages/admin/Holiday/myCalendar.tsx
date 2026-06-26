import { Button, Calendar, Col, Divider, Row, Select, Tag } from 'antd';
import React, { useEffect, useImperativeHandle, useState } from 'react';

export type MyCalendarProps = {
  onSelect?: (value: string) => void;
  onChange?: (value: string) => void;
  year?: number;
  disabledYearSelect?: boolean;
  title?: string;
  defaultSelectDays?: any
};

const MyCalendar = React.forwardRef((props: MyCalendarProps, ref) => {
  const [selectDay, setSelectDay] = useState<string[]>([]);
  const [tempDay, setTempDay] = useState<string>('');
  let myValue: any = undefined;
  let myChange: any = undefined;

  if (props.defaultSelectDays && props.defaultSelectDays.length > 0) {
    setSelectDay(props.defaultSelectDays);
  }

  const onMySelect = (value: any) => {
    setTempDay(value.format('YYYY-MM-DD'));
    if (props.onSelect) {
      props.onSelect(value.format('YYYY-MM-DD'));
    }
  };

  const onMyChange = (value: any) => {
    if (props.onChange) {
      props.onChange(value.format('YYYY-MM-DD'));
    }
  };

  const handleClose = (removedTag: string) => {
    const newTags = selectDay.filter((tag) => tag !== removedTag);
    setSelectDay(newTags);
  };

  const dayTag = (tag: string) => {
    const tagElem = (
      <Tag closable onClose={() => handleClose(tag)}>
        {tag}
      </Tag>
    );
    return (
      <span key={tag} style={{ display: 'inline-block', marginBottom: 5 }}>
        {tagElem}
      </span>
    );
  };

  useEffect(() => {
    if (props.year && myValue) {
      const now = myValue.clone().year(props.year);
      if (myChange) {
        myChange(now);
        setSelectDay([]);
      }
    }
  }, [props.year]);

  useImperativeHandle(ref, () => ({
    getSelectDays: () => selectDay,
    setDefaultSelectDays: (days: string[]) => setSelectDay(days),
  }));

  return (
    <>
      <Calendar
        fullscreen={false}
        headerRender={({ value, type, onChange, onTypeChange }) => {
          const start = 0;
          const end = 12;
          const monthOptions = [];

          const current = value.clone();
          const localeData = value.localeData();
          const months = [];
          for (let i = 0; i < 12; i++) {
            current.month(i);
            months.push(localeData.monthsShort(current));
          }

          for (let i = start; i < end; i++) {
            monthOptions.push(
              <Select.Option key={i} value={i}>
                {months[i]}
              </Select.Option>,
            );
          }

          const year = props.year ? props.year : value.year();
          const month = value.month();
          const options = [];
          for (let i = year - 10; i < year + 10; i += 1) {
            options.push(
              <Select.Option key={i} value={i}>
                {i}
              </Select.Option>,
            );
          }
          myValue = value;
          myChange = onChange;
          return (
            <div style={{ padding: 8 }}>
              <span>
                {props.title ? props.title : '请选择放假日期(周末默认为放假日期，无需设置)'}
              </span>
              <Row gutter={8}>
                <Col>
                  <Select
                    size="small"
                    dropdownMatchSelectWidth={false}
                    value={year}
                    onChange={(newYear) => {
                      const now = value.clone().year(newYear);
                      onChange(now);
                    }}
                    disabled={props.disabledYearSelect ? props.disabledYearSelect : true}
                  >
                    {options}
                  </Select>
                </Col>
                <Col>
                  <Select
                    size="small"
                    dropdownMatchSelectWidth={false}
                    value={month}
                    onChange={(newMonth) => {
                      const now = value.clone().month(newMonth);
                      onChange(now);
                    }}
                  >
                    {monthOptions}
                  </Select>
                </Col>
                <Col>
                  <Button
                    type="primary"
                    key="primary"
                    size="small"
                    onClick={() => {
                      if (tempDay != '' && !selectDay.includes(tempDay)) {
                        selectDay.push(tempDay);
                        const sortDay = selectDay.sort();
                        setSelectDay([...sortDay]);
                      }
                    }}
                  >
                    添加
                  </Button>
                </Col>
              </Row>
            </div>
          );
        }}
        onChange={onMyChange}
        onSelect={onMySelect}
      />
      <Divider />
      <div>{selectDay.map(dayTag)}</div>
    </>
  );
});

export default MyCalendar;
