import React from 'react';
import Dictlist from '../budget/dict/dictlist';

// 预警级别文本
export const WarningLevelText: Record<number, string> = {
  1: '预警',
  2: '调离',
};

const EvaluationConfig: React.FC = () => {
  return <Dictlist />;
};

export default EvaluationConfig;