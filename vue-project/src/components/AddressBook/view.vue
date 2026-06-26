<script setup lang="ts">
import { Icon, CellGroup, Cell, Space, Loading, Search, CheckboxGroup, Checkbox, Sticky, RadioGroup, Radio, Row, Col, Button } from 'vant';
import { onMounted, ref, watch } from 'vue';
import { departmentRule, searchNameRule } from '../../api/config';
import { handleLink, deviceCheck } from "../../utils/common";

const props = withDefaults(
    defineProps<{
        parentid?: any,
        user?: boolean;
        local?: boolean;
        nav?: boolean;
        selecttype?: 'user' | 'department' | 'all' | 'none';
        mode?: 'single' | 'multi',
        max?: number,
        updateSelectedData?: Function;
        close?: Function;
        hideCloseIcon?: boolean;
    }>(),
    {
        parentid: 0,
        user: true,
        local: true,
        nav: true,
        selecttype: 'user',
        mode: 'single',
        max: 1,
        hideCloseIcon: false,
    }
);

const departments: any = ref<any>([]);
const loading: any = ref<any>(false);
const curdepartmentid: any = ref<any>([]);
const predepartmentid: any = ref<any>([]);
const prehistoryids: any = ref<any>([]);
const backhistoryids: any = ref<any>([]);
const navdepartments: any = ref<any>([]);
const searchname: any = ref<any>('');
const users: any = ref<any>([]);
const checkgroupResult = ref<any>([]);
const checkeddepartmentid: any = ref<any>(undefined);
const currentshowdata = ref<any>([]);
const searchshowdata = ref<any>([]);
const selectusers = ref<any>([]);

const select_type = props.selecttype

onMounted(async () => {
    predepartmentid.value = props.parentid == 0 ? 1 : props.parentid
    loadDepartmentOrUserData(predepartmentid.value)
})

const handleHistory = (parentid: any, isLeaf: any, item: any) => {
    if (isLeaf) {
        if (select_type == 'none') {
            handleLink('/addressbookuser?userid=' + item.key)
        }
        return
    }
    predepartmentid.value = curdepartmentid.value
    prehistoryids.value.push(predepartmentid.value)
    loadDepartmentOrUserData(parentid)
}

const loadDepartmentOrUserData = (parentid: any) => {
    selectusers.value = []
    checkeddepartmentid.value = undefined
    curdepartmentid.value = parentid
    departments.value = [];
    loading.value = true;
    checkgroupResult.value = [];
    departmentRule({ tree: 1, parentid: parentid, user: props.user ? 1 : 0, local: props.local ? 1 : 0, nav: props.nav ? 1 : 0 }).then((res: any) => {
        loading.value = false
        if (res?.data) {
            departments.value = res?.data
            currentshowdata.value = res?.data
        }
        if (res?.nav_data) {
            navdepartments.value = res?.nav_data
        }
    });

}

const gotopre = () => {
    console.log(prehistoryids.value)
    if (prehistoryids.value.length > 0) {
        backhistoryids.value.push(curdepartmentid.value)
        loadDepartmentOrUserData(prehistoryids.value.pop())
    }
}

const gotoback = () => {
    console.log(backhistoryids.value)
    if (backhistoryids.value.length > 0) {
        prehistoryids.value.push(curdepartmentid.value)
        loadDepartmentOrUserData(backhistoryids.value.pop())
    }
}

const onSearch = () => {
    if (searchname.value != '') {
        selectusers.value = []
        checkeddepartmentid.value = undefined
        searchNameRule({ username: searchname.value }).then((res: any) => {
            if (res?.data) {
                users.value = res?.data
                searchshowdata.value = res?.data
            }
        });
    } else {
        searchshowdata.value = []
        selectusers.value = []
    }
}

const handleConfirm = () => {
    emit('updateSelectedData', selectusers.value)
    if (props.updateSelectedData) {
        props.updateSelectedData(selectusers.value)
    }
}

const handleClose = () => {
    emit('close')
    if (props.close) {
        props.close()
    }
}

const emit = defineEmits(['close', 'updateSelectedData']);

watch(checkeddepartmentid, (newvalue, oldvalue) => {
    if (searchname.value != '' && searchshowdata.value.length > 0) {
        selectusers.value = searchshowdata.value.filter((item: any) => item.userid == newvalue)
    } else if (currentshowdata.value.length > 0) {
        selectusers.value = currentshowdata.value.filter((item: any) => item.key == newvalue)
    }
})

watch(checkgroupResult, (newvalue, oldvalue) => {
    if (searchname.value != '' && searchshowdata.value.length > 0) {
        selectusers.value = searchshowdata.value.filter((item: any) => newvalue.includes(item.userid))
    } else if (currentshowdata.value.length > 0) {
        selectusers.value = currentshowdata.value.filter((item: any) => newvalue.includes(item.key))
    }
})

const cancelRadio = (data: any) => {
    if (searchname.value != '') {
        if (selectusers.value.length > 0 && data?.userid == selectusers.value[0]?.userid) {
            selectusers.value = []
            if (checkeddepartmentid.value == data?.userid) {
                checkeddepartmentid.value = undefined
            }
        }
    } else {
        if (selectusers.value.length > 0 && data?.key == selectusers.value[0]?.key) {
            selectusers.value = []
            if (checkeddepartmentid.value == data?.key) {
                checkeddepartmentid.value = undefined
            }
        }
    }
}

const gotoUser = (data: any) => {
    if (select_type == 'none') {
        handleLink('/addressbookuser?userid=' + data.userid)
    }
}
</script>

<template>
    <div class="content-box">
        <Sticky :offset-top="0">
            <div style="padding-bottom: 10px;background-color: #f7f7f7;">
                <Search v-model="searchname" show-action placeholder="请输入搜索姓名" @search="onSearch"
                    @update:model-value="onSearch" :clearable="true" :disabled="select_type == 'department'">
                    <template #action>
                        <div @click="onSearch">搜索</div>
                    </template>
                    <template #left v-if="!props.hideCloseIcon">
                        <span style="display: inline-block;padding-right: 10px;">
                            <Icon name="cross" size="20" @click="() => { handleClose() }" />
                        </span>
                    </template>
                </Search>
            </div>
        </Sticky>

        <div v-if="searchname == ''">
            <Sticky :offset-top="64">
                <Cell :title="navdepartments.map((item: any) => item.name).join('>')">
                    <template #title>
                        <span v-for="(value, index) in navdepartments">
                            <label @click="handleHistory(value.id, false, value)">{{ value.name }}</label>
                            {{ index == navdepartments.length - 1 ? '' : ' > ' }}
                        </span>
                    </template>
                </Cell>
            </Sticky>
            <Loading size="24px" vertical v-if="loading" style="margin-top: 30px;">加载中...</Loading>
            <div v-else class="content-tree-box">
                <CheckboxGroup v-model="checkgroupResult" shape="dot" :max="props.max ? props.max : 0"
                    v-if="props.mode == 'multi'">
                    <CellGroup v-if="departments.length > 0">
                        <Cell v-for="(value, index) in departments"
                            @click="handleHistory(value.key, value.isLeaf, value)">
                            <template #title>
                                <div v-if="value.isLeaf && value?.avatar">
                                    <Space>
                                        <Checkbox :name="value.key" v-if="['user', 'all'].includes(select_type)">
                                            <img :src="value?.avatar" width="30" height="30">
                                        </Checkbox>
                                        <img :src="value?.avatar" width="30" height="30" v-else>
                                        <div>{{ value.title }}</div>
                                    </Space>
                                </div>
                                <div v-else>
                                    <Space>
                                        <Checkbox :name="value.key" v-if="['department', 'all'].includes(select_type)"
                                            @click.stop="">
                                            <img src="https://fzrb.fznews.com.cn/static/images/wenjianjia.png"
                                                width="30" height="30">
                                        </Checkbox>
                                        <img src="https://fzrb.fznews.com.cn/static/images/wenjianjia.png" width="30"
                                            height="30" v-else>
                                        <div>{{ value.title }}</div>
                                    </Space>
                                </div>
                            </template>
                        </Cell>
                    </CellGroup>
                    <div v-else style="margin-top: 30px;text-align: center;">
                        暂无数据
                    </div>
                </CheckboxGroup>
                <RadioGroup v-model="checkeddepartmentid" shape="dot" v-else>
                    <CellGroup v-if="departments.length > 0">
                        <Cell v-for="(value, index) in departments"
                            @click="handleHistory(value.key, value.isLeaf, value)">
                            <template #title>
                                <div v-if="value.isLeaf && value?.avatar">
                                    <Space>
                                        <Radio :name="value.key" v-if="['user', 'all'].includes(select_type)"
                                            @click.stop="cancelRadio(value)">
                                            <img :src="value?.avatar" width="30" height="30">
                                        </Radio>
                                        <img :src="value?.avatar" width="30" height="30" v-else>
                                        <div>{{ value.title }}</div>
                                    </Space>
                                </div>
                                <div v-else>
                                    <Space>
                                        <Radio :name="value.key" v-if="['department', 'all'].includes(select_type)"
                                            @click.stop="cancelRadio(value)">
                                            <img src="https://fzrb.fznews.com.cn/static/images/wenjianjia.png"
                                                width="30" height="30">
                                        </Radio>
                                        <img src="https://fzrb.fznews.com.cn/static/images/wenjianjia.png" width="30"
                                            height="30" v-else>
                                        <div>{{ value.title }}</div>
                                    </Space>
                                </div>
                            </template>
                        </Cell>
                    </CellGroup>
                    <div v-else style="margin-top: 30px;text-align: center;">
                        暂无数据
                    </div>
                </RadioGroup>
            </div>
            <div class="department-nav" v-if="deviceCheck() == 'Android'">
                <div class="department-nav-box">
                    <Space :size="30">
                        <Icon name="arrow-left" :color="prehistoryids.length > 0 ? '#000' : '#ccc'"
                            @click="gotopre()" />
                        <Icon name="arrow" :color="backhistoryids.length > 0 ? '#000' : '#ccc'" @click="gotoback()" />
                    </Space>
                </div>
                <div class="department-nav-blank"></div>
            </div>
        </div>
        <div v-else>
            <CheckboxGroup v-model="checkgroupResult" shape="dot" :max="props.max ? props.max : 0"
                v-if="props.mode == 'multi'">
                <CellGroup v-if="users.length > 0">
                    <Cell v-for="(value, index) in users" @click.stop="gotoUser(value)">
                        <template #title>
                            <Space>
                                <Checkbox :name="value.userid" v-if="['user', 'all'].includes(select_type)">
                                    <img :src="value?.avatar" width="30" height="30">
                                </Checkbox>
                                <img :src="value?.avatar" width="30" height="30" v-else>
                                <div>{{ value.name }}</div>
                            </Space>
                        </template>
                    </Cell>
                </CellGroup>
            </CheckboxGroup>
            <RadioGroup v-model="checkeddepartmentid" shape="dot" v-else>
                <CellGroup v-if="users.length > 0">
                    <Cell v-for="(value, index) in users" @click.stop="gotoUser(value)">
                        <template #title>
                            <Space>
                                <Radio :name="value.userid" v-if="['user', 'all'].includes(select_type)"
                                    @click.stop="cancelRadio(value)">
                                    <img :src="value?.avatar" width="30" height="30">
                                </Radio>
                                <img :src="value?.avatar" width="30" height="30" v-else>
                                <div>{{ value.name }}</div>
                            </Space>
                        </template>
                    </Cell>
                </CellGroup>
            </RadioGroup>
        </div>
    </div>
    <div class="select-users" v-if="selectusers.length > 0">
        <div class="select-users-box">
            <Row style="width: 100%;">
                <Col span="21" style="overflow-x: auto;height: 100%;">
                <Space>
                    <img :src="value?.avatar" width="30" height="30" v-for="(value, index) in selectusers"
                        style="margin-left: 5px;" v-if="['user', 'all'].includes(select_type)">

                    <img src="https://fzrb.fznews.com.cn/static/images/wenjianjia.png" width="30" height="30"
                        style="margin-left: 5px;" v-for="(value, index) in selectusers"
                        v-if="['department', 'all'].includes(select_type)">
                </Space>
                </Col>
                <Col span="3"><Button type="primary" size="small" @click="handleConfirm">确定</Button></Col>
            </Row>
        </div>
        <div class="department-nav-blank"></div>
    </div>
</template>
<style scoped>
.content-box {
    position: fixed;
    left: 0;
    top: 0;
    width: 100%;
    height: 100vh;
    overflow: auto;
    background-color: #fff;
    z-index: 10000000000000000;
}

.content-tree-box {
    padding-bottom: 80px;
}

.department-nav {
    position: fixed;
    left: 0;
    bottom: 0;
    background: #f7f7f7;
    width: 100%;
}

.department-nav-box {
    display: flex;
    height: 50px;
    text-align: center;
    justify-content: center;
    align-items: center;
}

.department-nav-blank {
    padding-bottom: constant(safe-area-inset-bottom);
    padding-bottom: env(safe-area-inset-bottom);
}

.select-users {
    position: fixed;
    left: 0;
    bottom: 0;
    background: #f7f7f7;
    width: 100%;
    z-index: 100000000000000001;
}

.select-users-box {
    display: flex;
    height: 50px;
    align-items: center;
}

@media screen and (min-width: 500px) {
    .content-box {
        position: fixed;
        left: 50%;
        top: 0;
        width: 500px;
        height: 100vh;
        overflow: auto;
        margin: 0 auto;
        margin-left: -250px;
        background-color: #fff;
    }

    .content-tree-box {
        padding-bottom: 80px;
    }

    .department-nav {
        position: fixed;
        left: 50%;
        bottom: 0;
        display: flex;
        height: 50px;
        background: #f7f7f7;
        width: 500px;
        text-align: center;
        justify-content: center;
        align-items: center;
        margin-left: -250px;
    }

    .department-nav-box,
    .select-users-box {
        display: flex;
        height: 50px;
        text-align: center;
        justify-content: center;
        align-items: center;
    }

    .select-users {
        position: fixed;
        left: 50%;
        bottom: 0;
        display: flex;
        height: 50px;
        background: #f7f7f7;
        width: 500px;
        align-items: center;
        margin-left: -250px;
    }
}
</style>
