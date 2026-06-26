import { render, createVNode } from "vue";
import popup from "./view.vue";

const mountNode = document.createElement("div");
export const addressBookPopup = (options?: { parentid?: any, user?: boolean, local?: boolean, nav?: boolean, selecttype?: 'user' | 'department' | 'all', mode?: 'single' | 'multi', max?: number, close?: any, updateSelectedData?: any }) => {

    const props: any = {
        mountNode,
        parentid: options?.parentid ? options?.parentid : 0,
        user: options?.user === false ? false : true,
        local: options?.local === false ? false : true,
        nav: options?.nav === false ? false : true,
        selecttype: options?.selecttype ? options?.selecttype : 'user',
        mode: options?.mode ? options?.mode : 'single',
        max: options?.max && options.max > 1 ? options?.max : 1,
    }

    function updateSelectedData(data: any) {
        closeAddressBookPopup()
        if (options?.updateSelectedData) options?.updateSelectedData(data)
    }

    props.updateSelectedData = updateSelectedData
    props.close = closeAddressBookPopup

    document.body.appendChild(mountNode);
    const app = createVNode(popup, props);
    render(app, mountNode);
}

export const closeAddressBookPopup = () => {
    document.body.removeChild(mountNode);
}