import { defineStore } from 'pinia'
import { ref } from "vue";

export const useShitangStore = defineStore("shitang", () => {
    const cartMenus = ref<any>([]);
    const orderInfo = ref<any>({});
    function setCartMenus(data: any) {
        cartMenus.value = data;
    }
    function setOrderInfo(data: any) {
        orderInfo.value = data;
    }
    return { cartMenus, orderInfo, setCartMenus, setOrderInfo };
}, { persist: { enabled: true } });

