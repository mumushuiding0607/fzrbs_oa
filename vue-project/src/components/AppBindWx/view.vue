<script setup lang="ts">
import { Grid, GridItem, Loading } from 'vant';
const props = withDefaults(
    defineProps<{
        dialog?: any,
        showLoading?: any;
        title?: any;
    }>(),
    {
        dialog: false,
        showLoading: false,
        title: '',
    }
);
</script>
<template>
    <div v-if="props.dialog" class="myAppBindDialog">
        <div class="myAppBindDialogBox">
            <div v-if="props.title!=''">{{ props.title }}</div>
            <div style="text-align: center;margin-top: 30px;width:100%" v-if="props.showLoading">
                <Loading type="spinner" />
            </div>
            <Grid :column-num="3" :border="false">
                <GridItem icon="https://fzrb.fznews.com.cn/static/images/fzrbs_app_logo.png" text="掌上福州" />
                <GridItem icon="exchange" text="" />
                <GridItem icon="https://fzrb.fznews.com.cn/static/images/qywx_app_logo.png" text="企业微信" />
            </Grid>
        </div>

    </div>
    <Grid :column-num="3" :border="false" v-else>
        <GridItem icon="https://fzrb.fznews.com.cn/static/images/fzrbs_app_logo.png" text="掌上福州" />
        <GridItem icon="exchange" text="" />
        <GridItem icon="https://fzrb.fznews.com.cn/static/images/qywx_app_logo.png" text="企业微信" />
    </Grid>
</template>

<style scoped>
.myAppBindDialog {
    display: flex;
    justify-content: center;
    width: 100%;
    min-height: 100vh;
    margin: 0 auto;
    flex-direction: column;
    position: fixed;
    top: 0;
    left: 0;
    z-index: 100000000;
    background: rgba(0, 0, 0, 0.5);

}
.myAppBindDialogBox{
    background-color: #fff;
    margin:0 20px;
    text-align: center;
    padding: 20px 0;
    border-radius: 20px;
}
</style>