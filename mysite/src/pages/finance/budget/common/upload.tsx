import React, { useState } from 'react';
import { PlusOutlined } from '@ant-design/icons';
import { Image, Modal, Upload } from 'antd';
import type { GetProp, UploadFile, UploadProps } from 'antd';
import { RcFile } from 'antd/lib/upload';
import { request, useModel } from 'umi';
import ImgCrop from 'antd-img-crop';
type FileType = Parameters<GetProp<UploadProps, 'beforeUpload'>>[0];

const getBase64 = (file: FileType): Promise<string> =>
  new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.readAsDataURL(file);
    
    reader.onload = () => resolve(reader.result as string);
    reader.onerror = (error) => reject(error);
  });
 const beforeUpload = (file: RcFile) => {

    const isLt1M = file.size / 1024 / 1024 < 50;
    if (!isLt1M) {
      Modal.error({title:'文件不能大于 50MB'});
  
      return false;
    }
    return isLt1M;
  };
const Uploadmodule: React.FC<{onchange?:Function,urls?:[]}> = ({onchange,urls}) => {
  const [previewOpen, setPreviewOpen] = useState(false);
  const [previewImage, setPreviewImage] = useState('');
  var [refreshKey, setRefreshKey]= useState(0)
  const [fileList, setFileList] = useState<UploadFile[]>(urls||[]);

  const handlePreview = async (file: UploadFile) => {
    console.log('preview')
    if (!file.url && !file.preview) {
      file.preview = await getBase64(file.originFileObj as FileType);
    }

    setPreviewImage(file.url || (file.preview as string));
    setPreviewOpen(true);
  };
  const handleRemove = (e:any)=>{
    var temp = fileList.filter(file=>file.url!=e.url)
    setFileList(temp)

    onchange && onchange(temp)
  }
  const handleChange: UploadProps['onChange'] = ({ fileList: newFileList }) =>{
   
  }
   

  const uploadButton = (
    <button style={{ border: 0, background: 'none' }} type="button">
      <PlusOutlined />
      <div style={{ marginTop: 8 }}>上传</div>
    </button>
  );
  return (
    <>
  
    <Upload
        key={refreshKey}
        name="file"
        listType="picture-card"
        fileList={[...fileList]}
        defaultFileList={[...fileList]}
        onPreview={handlePreview}
        onChange={handleChange}
        onRemove={handleRemove}
        beforeUpload={beforeUpload}
        customRequest={async (options) => {
          const formData = new FormData();
          formData.append('file', options.file);
         
          const result = await request('/api/budget/uploadfile', {
            method: 'POST',
            body: formData,
          });
     
          if (result.errorMessage) {
            Modal.error({title:result.errorMessage})
          } else {
            console.log('filelist add before:',fileList)
            fileList.push({
              uid:result.file.name,
              name: result.file.name,
              status: result.file.state,
              url: result.file.url,
            })
            console.log('filelist add:',fileList)
            setFileList(fileList)
            setRefreshKey(++refreshKey)
            onchange && onchange(fileList)
          }
        }}
      >
        {fileList.length >= 8 ? null : uploadButton}

      </Upload>

      {previewImage && (
        <Image
          wrapperStyle={{ display: 'none' }}
          preview={{
            visible: previewOpen,
            onVisibleChange: (visible) => setPreviewOpen(visible),
            afterOpenChange: (visible) => !visible && setPreviewImage(''),
          }}
          src={previewImage}
        />
      )}
 
      
    </>
  );
};

export default Uploadmodule;