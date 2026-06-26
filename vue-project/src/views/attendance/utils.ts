import * as XLSX from 'xlsx'
export function downloadAsXlSX(data:any,filename:any) {
  
  
  const ws = XLSX.utils.aoa_to_sheet(data);
  const wb = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, ws, 'Sheet1');

  // 导出为 Excel 文件
  filename = (filename||'下载')+'.xlsx'
  XLSX.writeFile(wb, filename);
 
}