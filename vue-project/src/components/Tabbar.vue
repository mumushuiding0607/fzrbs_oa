<script setup lang="ts">
import { Tabbar, TabbarItem } from 'vant';
const props = withDefaults(
    defineProps<{
        tabs?: any,
    }>(),
    {
        tabs: [],
    }
);
</script>

<template>
    <div v-if="props.tabs.length > 0" class="myTabbar">
        <Tabbar route safe-area-inset-bottom style="z-index: 1000;">
            <TabbarItem :replace="value?.replace ? value?.replace : false" :to="value.to"
                :icon="value?.icon ? value?.icon : ''" v-for="(value, index) in props.tabs">{{ value.title }}
            </TabbarItem>
        </Tabbar>
    </div>
</template>
<style scoped>
.myTabbar {
    width: 100%;
}

.myTabbar .van-tabbar-item {
    position: relative;
}

.myTabbar .van-tabbar-item::after {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 1px;
    height: 100%;
    background-color: #ebedf0;
    margin-left: 10px;
    transform: scaleX(0.5);
    transform-origin: left;
}

.myTabbar .van-tabbar-item:last-child::after {
    display: none;
}

@media screen and (min-width: 500px) {

    .myTabbar {
        width: 500px;
    }
}
</style>
<style>
.page {
    padding-bottom: 50px;
}

@media screen and (min-width: 500px) {
    .page {
        padding-bottom: 50px;
    }
}
</style>