import { Button, DatePicker, InputNumber, Modal, Timeline } from "antd"

import { useState } from "react"
import { delpaycondition } from "./service"




const PayCondition:React.FC<{defaultValues:any,onChange?:Function,defaultDate?:any,editable?:boolean}> = ({defaultValues=[],onChange,defaultDate,editable=true})=>{

  const [values,setValues] = useState(defaultValues)
  const [rate,setRate]=useState(100)
  const [date,setDate]=useState(defaultDate?defaultDate.format('YYYY-MM-DD'):'')


  const add = ()=>{
    
    if (!rate) {
      Modal.error({title:'回款比例不能为空'})
      return
    }
    if (!date) {
      Modal.error({title:'回款日期不能为空'})
      return
    }
    const inx = values.findIndex((v:{rate:Number})=>v.rate==100)
    if (inx>-1) {
      Modal.error({title:'回款比例已达100%，不能再添加'})
      return
    }
    const dinx = values.findIndex((v:{date:String})=>v.date==date)
    if (dinx>-1) {
      Modal.error({title:'回款日期不能重复'})
      return
    }
    var temp:any = [...values,{rate,date,current:0}]
    setValues(temp)
    onChange && onChange(temp)
    
  }
  const del = (e:any,index:number)=>{
    Modal.confirm({
      title: '确定要删除吗？',
      okText: '确认',
      cancelText: '取消',
      onOk: async () => {
        if (e.id){
          delpaycondition({id:e.id}).then((res)=>{
            if (res.errorMessage){
              Modal.error({title:res.errorMessage})
            } else {
              var temp:any = values.filter((v:any)=>v.id!=e.id)
              setValues(temp)
              onChange && onChange(temp)
            }
          })
        } else{
          var temp:any = values.filter((v:any,inx:number)=>inx!=index)
          setValues(temp)
          onChange && onChange(temp)
        }
        
      },
    });
  }
  return (<>
  

  <div style={{width:'100%'}}>

      {
        editable&&
        <div style={{marginBottom:'20px',width:'100%',display:'flex',flexDirection:'row',justifyContent:'space-between'}}>
          <DatePicker style={{width:'41%'}} defaultValue={defaultDate}  format="YYYY-MM-DD" placeholder="日期" onChange={(e)=>{
            setDate(e?e.format('YYYY-MM-DD'):'')
          }}/>
          <InputNumber style={{width:'44%'}} defaultValue={100} prefix="%" placeholder="回款比例" onChange={(e)=>{
          
            setRate(e)
          }} />
          <Button style={{ width: '15%' }} type="primary" onClick={add}>
            添加
          </Button>
        </div>
      }

      <Timeline>
          {
            values.map((e:{date:string,rate:Number,current:10},index:any)=>{
              return (
              
                  <Timeline.Item key={'timtline'+index} >
                      <div onClick={()=>{editable && del(e,index)}}>
                        <span >{e.date.substring(0,10)}前累计回款</span>
                        <span style={{color:'red',fontWeight:'bolder'}}>{e.rate}%</span>
                        {/* <span>,实际回款</span>
                        <span style={{color:'green',fontWeight:'bolder'}}>{e.current}%</span> */}
                      </div>
                  </Timeline.Item>
                
              )
            })
          }
      </Timeline>
      
  </div>
  
  </>)
}

export default PayCondition