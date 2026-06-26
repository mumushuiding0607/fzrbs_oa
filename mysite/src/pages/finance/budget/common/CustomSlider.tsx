import React from 'react';
import './CustomSlider.css'
const CustomSlider: React.FC<{min:any,max:any,value:any,onChange:Function,style:any}> = ({ style,min = 0, max = 100, value = 0, onChange }) => {
  return (
    <input
      type="range"
      min={min}
      max={max}
      value={value}
      style={style}
      onChange={(e) => onChange && onChange(Number(e.target.value))}
      className="custom-slider"
    />
  );
};



export default CustomSlider;