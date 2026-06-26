import {  Row } from "antd";
import { CSSProperties } from "react";
import StaticsCard from "../common/staticscard";
import { Statistic } from "@ant-design/pro-components";

const contaniner:CSSProperties = {
  background: 'white',
  width:'100%',
  height: '200px',
  borderRadius:'1vw',
  display: 'flex',
  flexDirection: 'row',
  alignItems: 'center',
  padding: '1vw',
  boxSizing:'border-box',
  justifyContent: 'space-between'
}

const BuinessStatics: React.FC<{data:any[],target?:{head:string}}>= ({data,target})=>{
  return (<>
  
    <Row>
      <div style={contaniner}>

        {
            data.map((e:{},index:any)=>(
              <div><StaticsCard key={index} data={e}></StaticsCard></div>
            ))
          }
      </div>
    </Row>
  
  </>)
}

export default BuinessStatics;