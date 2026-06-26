import { ProFormUploadButton } from '@ant-design/pro-components';
import { message } from 'antd';
import { RcFile, UploadFile } from 'antd/lib/upload';
import React, { useEffect, useImperativeHandle, useRef, useState } from 'react';
import PreviewImage from '@/components/PreviewImage';
import token from '@/utils/token';
import { uploadDelete } from '@/services/ant-design-pro/api';
import styles from './index.less';

export type MyUploadFileProps = {
  name: string;
  label?: string;
  max: number;
  multiple: boolean;
  maxSize: number;
  accept: string;
  listType: string;
  defaultImage: any;
  uploadType: number; //1：图片，2：文件附件(doc、xls,pdf等通用办公文件)，3：1+2
  uploadPath: string;
  title?: string;
  colProps?: any;
  className?: string;
  protect?: boolean;
};

const MyUploadFile = React.forwardRef((props: MyUploadFileProps, ref) => {
  const previewRef = useRef<any>();
  const [fielList, setFielList] = useState<any[]>(props.defaultImage);
  const Authorization = token.get();

  const beforeUpload = (file: RcFile, uploadFileList: any) => {
    const types = {
      image: ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
      file: [
        'text/xml',
        'application/msword',
        'application/vnd.ms-excel',
        'application/pdf',
        'application/vnd.ms-powerpoint',
        'text/plain',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/zip',
        'application/x-zip-compressed',
        'multipart/x-zip',
        'application/x-compressed',
      ],
      media: [
        'video/x-ms-wmv',
        'audio/mpeg',
        'video/mp4',
        'video/quicktime',
        'audio/x-mpeg',
        'audio/x-wav',
        'audio/x-ms-wmv',
      ],
    };
    if (fielList.length + uploadFileList.length > props.max) {
      message.warn('最大上传 ' + props.max + ' 个文件');
      return false;
    }
    if (props.uploadType == 1) {
      if (!types.image.includes(file.type)) {
        message.warn('只支持JPG/PNG/Gif/WEBP图片格式');
        return false;
      }
    } else if (props.uploadType == 2) {
      if (!types.file.includes(file.type)) {
        message.warn('只支持DOC/XLS/PDF/TXT/PPT/ZIP文件格式');
        return false;
      }
    } else if (props.uploadType == 3) {

      if (!types.image.includes(file.type) && !types.file.includes(file.type) && !types.media.includes(file.type)) {
        message.warn('只支持JPG/PNG/Gif/WEBP/DOC/XLS/PDF/TXT/PPT/ZIP/MP3/MP4/WMV/MPEG/MOV文件格式');
        return false;
      }
    } else if (props.uploadType == 4) {
      if (!types.media.includes(file.type)) {
        message.warn('只支持MP3/MP4/WMV/MPEG/MOV文件格式');
        return false;
      }
    }
    const isMaxSize = file.size / 1024 / 1024 < props.maxSize;
    if (!isMaxSize) {
      message.warn('图片不能大于 ' + props.maxSize.toString() + 'MB!');
      return false;
    }
    return true;
  };

  const handlePreview = async (file: UploadFile) => {
    previewRef?.current.setImage(file);
  };

  useEffect(() => {
    setFielList(props.defaultImage);
  }, [props.defaultImage]);

  useImperativeHandle(ref, () => ({
    getFileList: () => fielList,
  }));

  const fieldProps = {
    name: 'upfile',
    listType: props.listType,
    accept: props.accept,
    maxCount: props.max,
    headers: { Authorization: Authorization },
    beforeUpload: beforeUpload,
    onPreview: handlePreview,
    fileList: fielList,
    multiple: props.multiple,
    className: props.className ? styles[props.className] : '',
  };
  if (props.uploadType == 4 || props.uploadType == 3) {
    fieldProps.previewFile = (file) => {
      return Promise.resolve()
        .then((res) => {
          let thumbnail;
          fielList.forEach((item) => {
            if (item.response && item.response.data.originalName == file.name) {
              thumbnail = item.response.data.url;
              file.response = item.response;
            }
          })
          return thumbnail;
        })
    }
  }

  return (
    <>
      <ProFormUploadButton
        name={props.name}
        label={props.label}
        max={props.max}
        colProps={props.colProps}
        title={props.title}
        fieldProps={fieldProps}
        value={fielList}
        action={
          '/api/common/upload?uploadType=' +
          props.uploadType.toString() +
          '&uploadPath=' +
          props.uploadPath + '&protect=' + (props.protect ? 1 : 0)
        }
        onChange={(e) => {
          if (!e.file.status) {
            return;
          } else {
            if (e.file.status == 'done') {
              if (e.file.response.data) {
                if (e.file.response.data.state != 'SUCCESS') {
                  message.warn(e.file.response.data.state);
                  e.fileList.pop();
                }
              } else {
                message.warn('上传失败');
              }
            } else if (e.file.status == 'removed') {
              let fileurl;
              if (e.file.response) {
                fileurl = e.file.response.data.url;
              } else {
                fileurl = e.file.url?.toString();
              }
              uploadDelete({ fileurl, protect: props.protect ? 1 : 0 });
            }
          }
          setFielList([...e.fileList]);
        }}
      />
      <PreviewImage ref={previewRef} previewFileType={props.uploadType} />
    </>
  );
});
export default MyUploadFile;
