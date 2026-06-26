import { ClockCircleOutlined } from '@ant-design/icons';
import { Button, InputNumber, Timeline } from 'antd';
import React, { CSSProperties, useEffect, useRef, useState } from 'react';
import Add from '../company/add';
import { set } from 'lodash';

const box:CSSProperties = {
  width:'100%',
  display:'flex',
  flexDirection:'row',
  alignItems:'center'
}


const CompanysView: React.FC<{datas:Array<any>,amount?:any,onChange?:Function,update?:boolean}> = ({datas=[[],[]],amount=0,onChange,update=false}) => {
  const [data,setData] = useState<any>({})
  const [visible,setVisible] = useState(false)
  var [refreshkey,setRefreshkey] = useState(0)
  const isMounted = React.useRef(true)
  const [tempdatas,setTempdatas] = useState<any>(datas)
 
  useEffect(() =>{
    console.log('useEffect init')
    if (!isMounted.current){
      return
    }
    isMounted.current = false;
    if (update){
      init()
    }
    
  },[])
  const init = ()=>{
    console.log('real init')
    var temp = tempdatas
    if (Array.isArray(temp)){
      if (temp[0].length==1&&temp[1].length==1){
        temp[0][0].amount = amount
        temp[1][0].amount = amount
      }
    } else{
      temp = [[],[]]
    }
    setTempdatas(temp)
    send(temp)
   
  }
  const send = (e:any)=>{
    console.log("send:",e)
    if (e&&e.length>0){
      e=e.map((arr:any)=>{
        return (arr||[]).map((item:any)=>{
          item= item||{}
          return {value:item.value,id:item.id||item.value,amount:item.amount,company:item.company||item.label}
          
        })
      })
      onChange && onChange(e)
    }
   
   
  }
  const onAddChange = (newval:any)=>{
    console.log('onAddChange:',newval)
    setData(newval)
    newval.label = newval.company
    newval.value = newval.id
    var inx0 = tempdatas[0].findIndex((e:any)=>e.id==newval.id||e.value==newval.id)
    var inx1 = tempdatas[1].findIndex((e:any)=>e.id==newval.id||e.value==newval.id)
    if (inx0>-1) {
      newval.amount = tempdatas[0][inx0].amount
      tempdatas[0][inx0] = newval
    }
    if (inx1>-1) {
      newval.amount =tempdatas[1][inx1].amount
      tempdatas[1][inx1] = newval
    }
    setTempdatas(tempdatas)
    send(tempdatas)


    
  }
  
  const onAmountChange=(i:number,j:number,amount:any)=>{
    if (onChange) {
      tempdatas[i][j].amount = amount||0
      send(tempdatas)
    }
  }
 
  return (
    <div style={{'width':'100%'}}>

      <Timeline>
        {
          tempdatas[0].map((e:any,i:number)=>{
            e=e||{}
            return (<div key={'0.'+i}>
              <Timeline.Item color='green'>
                <div style={box}>
                  <div style={{flex:6,paddingRight:'20px'}}>{e.company||e.label}</div>
                  <div style={{flex:4}}>
                    {
                      update && <InputNumber
                      defaultValue={e.amount?parseFloat(e.amount):(tempdatas[0].length==1?amount:0)}
                      prefix="￥"
                      style={{'width':'80%','border':'none'}}
                      onChange={(e)=>{
                        onAmountChange(0,i,e)
                      }}
                    />
                    }
                    {
                      !update && <span>￥{e.amount||0}</span>
                    }
                    
                  </div>
                  <div style={{flex:2,color:'#1890ff'}}><span  onClick={()=>{
                    setVisible(true)
                    setData(e)
                    setRefreshkey(++refreshkey)
                  }}>公司信息</span></div>
                </div>
              </Timeline.Item>
            </div>)
          })
        }
        {
          tempdatas[1].map((e:any,i:number)=>{
            e=e||{}
            return (<div key={'1.'+i}>
              <Timeline.Item color='red'>
                <div style={box}>
                  <div style={{flex:6,paddingRight:'20px'}}>{e.company||e.label}</div>
                  <div style={{flex:4}}>
                    {
                      update && <InputNumber
                      defaultValue={e.amount?parseFloat(e.amount):(tempdatas[1].length==1?amount:0)}
                      prefix="￥"
                      style={{'width':'80%','border':'none'}}
                      onChange={(e)=>{
                        onAmountChange(1,i,e)
                      }}
                    />
                    }
                    {
                      !update && <span>￥{e.amount||0}</span>
                    }
                    
                  </div>
                  <div style={{flex:2,color:'#1890ff'}}><span  onClick={()=>{
                    setVisible(true)
                    setData(e)
                    setRefreshkey(++refreshkey)
                  }}>公司信息</span></div>
                </div>
              </Timeline.Item>
            </div>)
          })
        }

    </Timeline>

      
    {
      !isMounted.current && <Add key={refreshkey} sign={1} visible={visible} id={data.id||data.value} update={update} onChange={onAddChange} onVisibleChange={setVisible}></Add>
    }
    
    </div>
  );
}

export default CompanysView;