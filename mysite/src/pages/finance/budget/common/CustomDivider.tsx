const CustomDivider: React.FC<{height?:string}> = ({height})=>{

  return (<div style={{width:'100%',padding:height?height:'0.5vw',fontSize:'1px',color:"lightgray"}}></div>)
}

export default CustomDivider;