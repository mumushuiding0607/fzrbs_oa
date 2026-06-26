import React from "react"

const box:React.CSSProperties={
  margin:'0',
  fontWeight:'normal',
  fontSize:'14px',
  textAlign:'center',
  minWidth:'25%',
  maxWidth:'25%',
  flex:'0 0 25%',
  boxSizing:'border-box'
}
const Usersigns: React.FC<{datas:any,justifyContent?:any}> = ({datas="",justifyContent='flex-start'}) => {

  // 支持字符串和数组两种格式
  const items = Array.isArray(datas)
    ? datas
    : (typeof datas === 'string' && datas.length > 0 ? datas.split(';') : []);

  return (
    <div style={{display:'flex',flexWrap:'wrap',width:'100%',justifyContent:justifyContent}}>
        {
          items.length>0 && items.map((item:any,index:any)=>{
            return (
              <div key={index} style={box}>{item}</div>
            )
           }
          )
        }
    </div>
  )
}

export default Usersigns
