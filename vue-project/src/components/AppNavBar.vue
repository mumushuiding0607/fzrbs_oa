<script setup lang="ts">
import { NavBar } from 'vant';
import { appEnv, handleLink } from "../utils/common";
const inApp = appEnv();
const onClickLeft = () => {
    if (props.backUrl) {
        handleLink(props.backUrl);
        return
    }
    window.history.back();
}
const props = withDefaults(
    defineProps<{
        backUrl?: any;
    }>(),
    {
        backUrl: undefined,
    }
);
</script>

<template>
    <div v-if="inApp">
        <NavBar title="" left-text="返回" left-arrow @click-left="onClickLeft" :fixed="true" />
        <div style="height: 46px;"></div>
    </div>
</template>