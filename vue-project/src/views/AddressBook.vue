<script setup lang="ts">
import { onMounted } from 'vue';
import { ActionBar, ActionBarButton } from 'vant';
import { selectEnterpriseContact, openUserProfile, appEnv } from '../utils/common';
import AddressBook from '../components/AddressBook/view.vue';

const openEnterpriseContact = () => {
    selectEnterpriseContact({
        user: true,
    }).then(async (res: any) => {
        const userid = res.userIds.join();
        if (userid) {
            openUserProfile({ userid: userid })
        }
    }).catch((err) => {
        // showNotify({ message: err });
    })
}

onMounted(async () => {
})

const inApp = appEnv();
</script>

<template>
    <div v-wechat-title="$route.meta.title"></div>
    <ActionBar v-if="!inApp">
        <ActionBarButton type="primary" text="查看通讯录" @click="openEnterpriseContact" />
    </ActionBar>
    <AddressBook v-else :hideCloseIcon="true" selecttype="none" />
</template>
