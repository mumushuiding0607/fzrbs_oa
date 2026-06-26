import { Tabs } from "antd";
import Budgetdetail from "../budget/budgetdetail";
import Finaldetail from "../budget/finaldetail";
import { useEffect, useState } from "react";
import { getthirdno } from "../budget/service";

// data
const { TabPane } = Tabs;
const Apply:React.FC<{data:any,onchange: Function}> = ({data}) =>{
  // data

  // dom
  return (
    <>

      <Tabs  key={'tab1'} defaultActiveKey="1" >

        {
        
        <TabPane tab="预算" key={2}>
          <Budgetdetail key={12} id={data.id}></Budgetdetail>
        </TabPane>
        }
        {
          <TabPane tab="决算" key={3}>
          <Finaldetail key={13} id={data.id}></Finaldetail>
        </TabPane>
        }
        
      </Tabs>

    </>
  )
}
export default Apply