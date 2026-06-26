<script setup lang="ts">
import { RouterView } from "vue-router";
</script>

<template>
  <!-- <RouterView /> -->
  <router-view v-slot="{ Component }">
    <keep-alive>
      <component :is="Component" v-if="$route.meta.keepAlive" :key="$route.fullPath" />
    </keep-alive>
    <component :is="Component" v-if="!$route.meta.keepAlive" :key="$route.name" />
  </router-view>
</template>