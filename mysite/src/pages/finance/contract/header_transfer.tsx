import { Button, Transfer } from 'antd';
import React, { useEffect, useState } from 'react';
import { getheaders } from './service';
import { request } from 'umi';


const HeaderTransfer: React.FC<{onChange?:Function,headers?:any,url?:any}> = ({onChange,headers=[],url}) => {

  const [from,setFrom]=useState<any>(headers)
  const [targetKeys, setTargetKeys] = useState<any>([]);

  useEffect(()=>{
    if (headers.length==0){
      if (url){
        request<{
          data: {};
          errorMessage:String;
        }>(url, {
          method: 'GET',
        }).then((res:any)=>{
      
          setFrom(res.data||[])
          setTargetKeys(res.data.map((e:any)=>e.key))
     
          onChange && onChange(res.data)
        })
      }else{
        getheaders().then((res:any)=>{
          setFrom(res)
          setTargetKeys(res.map((e:any)=>e.key))
          onChange && onChange(res)
        })
      }
      
    }else{
      setTargetKeys(headers.filter((e:any)=>e.title).map((e:any)=>e.key))
      onChange && onChange(headers)
    }
    
  },[])

  const handleChange = (newTargetKeys: string[]) => {
    setTargetKeys(newTargetKeys)
    onChange && onChange(from.filter((e:any)=> (newTargetKeys||targetKeys).includes(e.key)))
  };


  return (
    <Transfer

      style={{marginTop:'15px'}}
      dataSource={from||[]}
      showSearch
      listStyle={{
        width: 250,
        height:600
      }}
      titles={['可选', '已选']}
      operations={['添加', '删除']}
      targetKeys={targetKeys}
      onChange={handleChange}
      render={item => `${item.title}`}

    />
  );
};

export default HeaderTransfer;