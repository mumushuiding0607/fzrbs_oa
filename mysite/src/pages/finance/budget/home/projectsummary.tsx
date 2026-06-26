import { AntDesignOutlined, RedoOutlined } from "@ant-design/icons";
import { Avatar, Row } from "antd";
import { CSSProperties } from "react";
import { useHistory, useModel } from "umi";
import DataCard from "../common/datacard";
import DataColumn from "../common/DataColumn";

const contaniner:CSSProperties = {
  background: 'white',
  width:'100%',
  height: '10vw',
  borderRadius:'1vw',
  display: 'flex',
  flexDirection: 'row',
  alignItems: 'center',
  padding: '1vw',
  boxSizing:'border-box',
  justifyContent: 'space-between'
}

const Projectsummary: React.FC<{data:any[],target?:{},mode?:any,onChange?:Function,onRefresh?:Function}>= ({onChange,onRefresh,data,target,mode='pie'})=>{
  const history = useHistory<any>() as any;
  return (
  <div>
        {
          
            mode=='pie'&&
            <Row>
              <div style={contaniner}>
              {
                    data.map((e:any,index:number)=>(
                      <div key={index} onClick={()=>{
                        history.push({pathname:'/finance/budget/project/list',query:{title:e.label,protype: e.id}})
                      }}><DataCard data={e} target={target}></DataCard></div>
                    ))
                  }
              </div>
              {
                  onRefresh && <div style={{color:'#dc676d',fontWeight:'bolder'}}>
                  <div onClick={()=>{onRefresh&&onRefresh()}}><RedoOutlined style={{ fontSize: '30px' }}/></div>刷新
                  
                  
                </div>
                }
            </Row>
        }
        {
          mode=="column" && 
          <div>
            <DataColumn data={data} onCheck={onChange} onRefresh={onRefresh}></DataColumn>
            
          </div>
        }
    
  </div>)

}

export default Projectsummary;