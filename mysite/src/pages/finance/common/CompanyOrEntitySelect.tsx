
import Companyselect from "../company/companyselect"

const CompanyOrEntitySelect:React.FC<{type:Boolean,value?:any,onChange?:Function,multiple?:boolean}> = ({type,value,onChange,multiple=false}) =>{

  const chfunc = (e:any,v:any)=>{
    
    if(Array.isArray(e)){
      onChange && onChange({value:e.map((x:any)=>x.value).join(','),label:e.map((x:any)=>x.label).join(',')})
    } else {
      onChange && onChange(v)
    }
  }
  return <Companyselect sign={1} value={value}  multiple={multiple}  onChange={chfunc}/>
  // return (<>
  //       {
  //         type && <Companyselect sign={1} value={value} multiple={false}  onChange={chfunc}/>
  //       } 
  //       {
  //         !type && <Companyselect  sign={2}  value={value} multiple={false} onChange={chfunc}/>
  //       }
  // </>)
}
export default CompanyOrEntitySelect