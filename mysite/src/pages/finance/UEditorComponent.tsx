import React, { useEffect, useImperativeHandle } from 'react';


export type UEditorProps = {
  setContent?: (value: string) => void;
  initData?: any;
  config?: any;
  editorid?:any
};

let editor:any = null;
const UEditorComponent = React.forwardRef((props: UEditorProps, ref) => {
  var containerId = props.editorid
  // 初始化编辑器
  const setConfig = (initData: any, config: any, setContent: any) => {
    if (props.editorid){
      editor = window.UE.getEditor(props.editorid);
    }else{
      containerId = 'editor-' + Math.random().toString(36); // 确保唯一 ID
    }
    if (!editor){
      editor =
        window.UE &&
        window.UE.getEditor(containerId, {
          enableAutoSave: false,
          autoHeightEnabled: false,
          autoFloatEnabled: false,
          initialFrameHeight: (config && config.initialFrameHeight) || 450,
          initialFrameWidth: (config && config.initialFrameWidth) || '100%',
          zIndex: 1030,
        });
    }
    
    editor.ready(() => {
      if (initData) {
        
        editor.setContent(initData); //设置默认值/表单回显
      }
    });
    editor.addListener('blur', function () {
      if (props.editorid){
        editor = window.UE.getEditor(props.editorid);
      }
      setContent(editor.getContent());
    });
  };

  useEffect(() => {
    console.log('Ueditor containerId:',containerId)
    setConfig(props.initData, props.config, props.setContent);
    return () => {
      if (props.editorid){
        editor = window.UE.getEditor(props.editorid);
      }
      editor && editor.destroy && editor.destroy();
      // editor.removeListener(); //不要打开，否则返回有问题报错
    };
  }, []);

  useImperativeHandle(ref, () => ({
    getUEContent: () => {
      if (props.editorid){
        editor = window.UE.getEditor(props.editorid);
      }
      return editor.getContent(); //获取编辑器内容
    },
  }));
  return <script id={containerId} type="text/plain" />;
});
export default UEditorComponent;
