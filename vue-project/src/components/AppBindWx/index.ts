import { render, createVNode } from "vue";
import popup from "./view.vue";

const mountNode = document.createElement("div");
export const showAppBindWxDialog = (options?: { dialog?: boolean, showLoading?: boolean, title?: string }) => {
    document.body.appendChild(mountNode);
    const app = createVNode(popup, {
        mountNode,
        dialog: options?.dialog ? true : false,
        showLoading: options?.showLoading ? true : false,
        title: options?.title ? options?.title : '',
    });
    render(app, mountNode);
}

export const closeAppBindWxDialog = () => {
    document.body.removeChild(mountNode);
}