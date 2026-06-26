import { useEffect, useState, useMemo, useRef } from 'react';
import { advitemstatistics } from './service';
import { useModel } from 'umi';

interface AdvitemStatisticsProps {
  params?: any;
  total?:any
}

const AdvitemStatistics: React.FC<AdvitemStatisticsProps> = ({ params,total=0 }) => {
  const [data, setData] = useState<any>([]);
  const lastParamsRef = useRef<string>('');
  const { initialState } = useModel<any>('@@initialState');
const { currentUser } = initialState;

  // 使用 useMemo 将 params 序列化为字符串，避免对象引用变化导致的重复请求
  const paramsKey = useMemo(() => JSON.stringify(params || {}), [params]);

  useEffect(() => {
    // 只有当 params 真正发生变化时才重新请求
    if (paramsKey === lastParamsRef.current) {
      return;
    }
    lastParamsRef.current = paramsKey;

    const fetchData = async () => {
      try {
        const res: any = await advitemstatistics({...params,userid:currentUser.wxuserid});
        if (res) {
          setData(res[0]);
        }
      } catch (error) {
        console.error('获取统计数据失败:', error);
      }
    };
    fetchData();
  }, [paramsKey]);

  // 格式化数值，保留两位小数
  const formatNum = (value: any) => {
    if (value === null || value === undefined || value === '') return '0.00';
    const num = parseFloat(value);
    return isNaN(num) ? '0.00' : num.toFixed(2);
  };
  
  // 格式化整数
  const formatInt = (value: any) => {
    if (value === null || value === undefined || value === '') return '0';
    const num = parseInt(value);
    return isNaN(num) ? '0' : num.toString();
  };

  return (
    <div style={{ marginBottom: 16, fontSize: 14 }}>
      总可收款: <span style={{ color: 'red' }}>{formatNum(data?.AI_AmountReceivable)}</span>
      &nbsp;&nbsp;&nbsp;&nbsp;
      总已收款: <span style={{ color: 'red' }}>{formatNum(data?.AI_AmountReceived)}</span>
      &nbsp;&nbsp;&nbsp;&nbsp;
      总欠款: <span style={{ color: 'red' }}>{formatNum(data?.AI_Debt)}</span>
      &nbsp;&nbsp;&nbsp;&nbsp;
      总版面数: <span style={{ color: 'red' }}>{data?.AI_AdvPages}</span>
      &nbsp;&nbsp;&nbsp;&nbsp;
      总广告数: <span style={{ color: 'red' }}>{formatInt(total)}</span>
      &nbsp;&nbsp;&nbsp;&nbsp;
      新媒体计量: <span style={{ color: 'red' }}>{formatInt(data?.number)}</span>
    </div>
  );
};

export default AdvitemStatistics;
