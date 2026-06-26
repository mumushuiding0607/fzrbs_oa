import React, { useEffect, useState, useRef } from 'react';
import CustomSlider from '../budget/common/CustomSlider';

interface TableScrollSyncProps {
  tableId: string; // 显式传入 ProTable 的 id
  onScroll?: (scrollLeft: number) => void;
}

const TableScrollSync: React.FC<TableScrollSyncProps> = ({ tableId, onScroll }) => {
  const [scrollLeft, setScrollLeft] = useState(0);
  const [maxScroll, setMaxScroll] = useState(0);
  const [isVisible, setIsVisible] = useState(false);
  const scrollRef = useRef<number>(0);
  const maxScrollRef = useRef<number>(0);

  // 计算表格的最大滚动距离
  const calculateMaxScroll = () => {
    const tableContent = document.querySelector(`#${tableId} .ant-table-content`);
    if (tableContent) {
      const scrollWidth = (tableContent as HTMLElement).scrollWidth;
      const clientWidth = (tableContent as HTMLElement).clientWidth;
      const maxScrollValue = Math.max(0, scrollWidth - clientWidth);
      maxScrollRef.current = maxScrollValue;
      setMaxScroll(maxScrollValue);
      setIsVisible(maxScrollValue > 0);
      return maxScrollValue;
    }
    return 0;
  };

  // 监听表格滚动事件
  const handleTableScroll = (event: Event) => {
    const target = event.target as HTMLElement;
    const currentScroll = target.scrollLeft;
    scrollRef.current = currentScroll;
    setScrollLeft(currentScroll);
  };

  // 滑块变化处理
  const handleSliderChange = (value: number) => {
    const tableContent = document.querySelector(`#${tableId} .ant-table-content`);
    if (tableContent) {
      (tableContent as HTMLElement).scrollLeft = value;
      scrollRef.current = value;
      setScrollLeft(value);
    }
    onScroll && onScroll(value);
  };

  useEffect(() => {
    // 延迟执行，确保表格已渲染
    const timer = setTimeout(() => {
      calculateMaxScroll();

      // 监听表格滚动事件
      const tableContent = document.querySelector(`#${tableId} .ant-table-content`);
      if (tableContent) {
        tableContent.addEventListener('scroll', handleTableScroll);

        // 使用 ResizeObserver 监听表格大小变化
        const resizeObserver = new ResizeObserver(() => {
          calculateMaxScroll();
        });
        resizeObserver.observe(tableContent);

        return () => {
          tableContent.removeEventListener('scroll', handleTableScroll);
          resizeObserver.disconnect();
        };
      }
    }, 1000);

    return () => clearTimeout(timer);
  }, [tableId]);

  // 如果不需要滚动，隐藏组件
  if (!isVisible || maxScroll <= 0) {
    return null;
  }

  return (
    <div
      style={{
        position: 'fixed',
        bottom: 0,
        left: 0,
        right: 0,
        padding: '18px 15px 5px 15px',
        zIndex: 9,
        background: 'rgba(255, 255, 255, 0.95)',
        borderTop: '1px solid #f0f0f0',
        boxShadow: '0 -2px 8px rgba(0, 0, 0, 0.1)',
        display: 'flex',
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'center',
        backdropFilter: 'blur(8px)',
      }}
    >
      <div style={{ fontSize: '12px', color: '#666', marginRight: '10px' }}>
        左右滚动:
      </div>
      <CustomSlider
        min={0}
        max={Math.ceil(maxScroll)}
        value={Math.round(scrollLeft)}
        onChange={handleSliderChange}
        style={{
          marginBottom: 0,
          width: 'calc(100vw - 200px)',
          maxWidth: '800px'
        }}
      />
      <div style={{
        fontSize: '12px',
        color: '#999',
        marginLeft: '10px',
        minWidth: '80px'
      }}>
        {Math.round(scrollLeft)} / {Math.ceil(maxScroll)}px
      </div>
    </div>
  );
};

export default TableScrollSync;