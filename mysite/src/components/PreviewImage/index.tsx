import { Modal } from 'antd';
import { RcFile, UploadFile } from 'antd/lib/upload';
import React, { useImperativeHandle } from 'react';
import { useState } from 'react';
import browser from '@/utils/browser';

export type PreviewImageProps = {
  previewFileType?: number;
};

const getBase64 = (file: RcFile): Promise<string> =>
  new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.readAsDataURL(file);
    reader.onload = () => resolve(reader.result as string);
    reader.onerror = (error) => reject(error);
  });

const PreviewImage = React.forwardRef((props: PreviewImageProps, ref) => {
  const [previewVisible, setPreviewVisible] = useState(false);
  const [previewImage, setPreviewImage] = useState('');
  const [previewFileType, setPreviewFileType] = useState(1);
  const [fileType, setFileType] = useState('');

  const handleCancel = () => setPreviewVisible(false);

  useImperativeHandle(ref, () => ({
    setImage: async (file: UploadFile) => {
      setFileType(file.type as string);
      if (props.previewFileType) {
        setPreviewFileType(props.previewFileType)
      }
      if (!file.url && !file.preview) {
        file.preview = await getBase64(file.originFileObj as RcFile);
      }
      setPreviewImage(file.url || (file.preview as string));
      setPreviewVisible(true);
    },
  }));

  return (
    <Modal visible={previewVisible} title="预览" footer={null} onCancel={handleCancel} width={browser.mobile() ? '100%' : '700px'}>
      {(previewFileType == 1 || (previewFileType == 3 && fileType?.indexOf('image/') != -1)) && <img style={{ width: '100%' }} src={previewImage} />}
      {(previewFileType == 4 || (previewFileType == 3 && fileType?.indexOf('video/') != -1)) && <video src={previewImage} width={browser.mobile() ? '100%' : '640px'} height="auto" controls={true} preload="auto" muted={true}></video>}
    </Modal>
  );
});
export default PreviewImage;
