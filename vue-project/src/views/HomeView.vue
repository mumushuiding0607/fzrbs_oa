<script setup lang="ts">
import { onActivated, onMounted, ref } from "vue";
import { appsConfig } from "../api/index";
import { Grid, GridItem, Image, Divider } from "vant";
import { handleLink } from "../utils/common";

const AppsInfo: any = ref([]);

const loadData = async () => {
  let res: any = await appsConfig();
  if (res.success && res?.data) {
    AppsInfo.value = res?.data;
  }
};

onMounted(() => {
  loadData();
});
onActivated(() => {
  loadData();
});
</script>

<template>
  <div v-wechat-title="$route.meta.title"></div>
  <div class="page index-page">
    <div v-if="AppsInfo.length > 0" v-for="(value, index) in AppsInfo" :key="value">
      <!-- <h2 class="apps-block__title">{{ value.name }}</h2> -->
      <Divider>{{ value.name }}</Divider>
      <Grid :border="false" :gutter="30">
        <GridItem v-for="(value1, index1) in value.children" :key="value1" @click="handleLink(value1.path)">
          <Image :src="value1.image" round />
          <p class="index-grid-text">{{ value1.name }}</p>
        </GridItem>
      </Grid>
    </div>
  </div>
</template>

<style>
.index-page {
  background-color: #fff;
}

.index-page .apps-block__title {
  padding: 10px;
  font-size: 16px;
  z-index: 100;
  background-color: #eff2f5;
}

.apps-block__title:after {
  position: absolute;
  box-sizing: border-box;
  content: " ";
  pointer-events: none;
  right: 0;
  bottom: 0;
  left: 0;
  border-bottom: 1px solid var(--van-cell-border-color);
  transform: scaleY(0.5);
}

.index-page .van-grid-item__content--center {
  justify-content: normal;
}

/* .index-page .van-grid:after {
  position: absolute;
  box-sizing: border-box;
  content: " ";
  pointer-events: none;
  right: 0;
  bottom: 0;
  left: 0;
  border-bottom: 1px solid var(--van-cell-border-color);
  transform: scaleY(0.5);
} */

.index-grid-text {
  margin-top: 6px;
  font-size: 12px;
  text-align: center;
}

.index-page .van-grid-item__content {
  font-size: 14px;
}

@media screen and (min-width: 500px) {
  .index-page .apps-block__title {
    padding: 10px;
    font-size: 16px;
    z-index: 100;
    background-color: #eff2f5;
  }

  .index-page .van-grid-item__content {
    font-size: 14px;
  }

  .index-grid-text {
    margin-top: 6px;
    font-size: 12px;
    text-align: center;
  }
}
</style>
