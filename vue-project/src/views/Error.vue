<script setup lang="ts">
import { Empty } from 'vant';
</script>

<template>
    <div v-wechat-title="$route.meta.title"></div>
    <Empty image="error" description="授权登录信息失效，请重新打开" />
</template>
