import React, { useEffect, useImperativeHandle } from 'react';
let editor;

export type UEditorProps = {
  setContent?: (value: string) => void;
  initData?: any;
  config?: any;
};

const UEditor = React.forwardRef((props: UEditorProps, ref) => {
  // 初始化编辑器
  const setConfig = (initData: any, config: any, setContent: any) => {
    editor =
      window.UE &&
      window.UE.getEditor('editor', {
        enableAutoSave: false,
        autoHeightEnabled: false,
        autoFloatEnabled: false,
        initialFrameHeight: (config && config.initialFrameHeight) || 450,
        initialFrameWidth: (config && config.initialFrameWidth) || '100%',
        zIndex: 1030,
      });
    editor.ready(() => {
      if (initData) {
        editor.setContent(initData); //设置默认值/表单回显
      }
    });
    editor.addListener('blur', function () {
      setContent(editor.getContent());
    });
  };

  useEffect(() => {
    setConfig(props.initData, props.config, props.setContent);
    return () => {
      editor.destroy();
      // editor.removeListener(); //不要打开，否则返回有问题报错
    };
  }, []);

  useImperativeHandle(ref, () => ({
    getUEContent: () => {
      return editor.getContent(); //获取编辑器内容
    },
  }));
  return <script id="editor" type="text/plain" />;
});
export default UEditor;
