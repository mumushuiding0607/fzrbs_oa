import React, { useState, useEffect } from 'react';
import { Modal, Badge, Calendar } from 'antd';
import { getadvcalendar } from './service';
import moment from 'moment';
import AdvitemList from './advitemlist';

interface PreviewCalendarModalProps {
  visible: boolean;
  onClose: () => void;
}

const PreviewCalendarModal: React.FC<PreviewCalendarModalProps> = ({ visible, onClose }) => {
  const [calendarData, setCalendarData] = useState<{ date: string; advCount: number }[]>([]);
  const [currentMonth, setCurrentMonth] = useState(moment());
  const [selectedDate, setSelectedDate] = useState<string | null>(null);
  const [advListVisible, setAdvListVisible] = useState(false);
  const [selectedDateInfo, setSelectedDateInfo] = useState<{ start: string; end: string } | null>(null);

  useEffect(() => {
    if (visible) {
      fetchCalendarData();
    }
  }, [visible, currentMonth]);

  const fetchCalendarData = async () => {
    try {
      const res = await getadvcalendar({
        year: currentMonth.year(),
        month: currentMonth.month() + 1,
      });
      setCalendarData(res || []);
    } catch (error) {
      console.error('获取广告日历失败', error);
    }
  };

  const fetchAdvListByDate = async (date: string) => {
    setSelectedDate(date);
    setSelectedDateInfo({ start: date, end: date });
    setAdvListVisible(true);
  };

  const dateCellRender = (date: moment.Moment) => {
    const dateStr = date.format('YYYY-MM-DD');
    const item = calendarData.find(d => d.date === dateStr);
    const count = item?.advCount || 0;

    if (count === 0) {
      return null;
    }

    return (
      <div
        style={{
          position: 'absolute',
          top: -12,
          right: -10,
          zIndex: 1,
          cursor: 'pointer',
        }}
        onClick={(e) => {
          e.stopPropagation();
          fetchAdvListByDate(dateStr);
        }}
      >
        <Badge
          count={count}
          size="small"
          style={{
            backgroundColor: '#f5222d',
            padding: '0 2px',
            minWidth: 16,
            height: 16,
          }}
        />
      </div>
    );
  };

  const monthChange = (date: moment.Moment) => {
    setCurrentMonth(date);
  };

  return (
    <>
      <Modal
        title="广告预览刊期"
        visible={visible}
        onCancel={onClose}
        footer={null}
        width={600}
        destroyOnClose
      >
        <Calendar
            fullscreen={false}
            value={currentMonth}
            onPanelChange={monthChange}
            dateCellRender={dateCellRender}
            selectable={false}
          />
      </Modal>

      <Modal
        title="广告列表"
        style={{ top: 20 }}
        visible={advListVisible}
        onOk={() => setAdvListVisible(false)}
        onCancel={() => setAdvListVisible(false)}
        footer={null}
        width={1400}
        destroyOnClose
      >
        {selectedDateInfo && (
          <AdvitemList
            key={selectedDate}
            params={{
              AI_PublishTime: selectedDateInfo.start,
              AI_PublishEndTime: selectedDateInfo.end,
            }}
          />
        )}
      </Modal>
    </>
  );
};

export default PreviewCalendarModal;
