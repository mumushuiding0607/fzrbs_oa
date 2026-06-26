
<script setup lang="ts">
const props = withDefaults(
    defineProps<{
        data: any;
        style?: string;
        option?: any
    }>(),
    {
        data: [],
        style: '',
        option: {}
    }
);
</script>
<template>
    <div class="vant-table">
        <table cellspacing="0" :style="props.style" class="table">
            <tr v-if="props.option && props.option?.column">
                <th class="th" v-for="(item, index) in props.option?.column" :key="index">{{ item.label }}</th>
            </tr>
            <tr v-for="(item, index) in props.data" :key="index" class="list-tr">
                <td v-for="(context, i) in props.option?.column" :key="i">{{ item[context.field] }}</td>
            </tr>
        </table>
    </div>
</template>

<style scoped>
.table {
    width: 100%;
}

.th {
    height: 40px;
    line-height: 40px;
    background-color: #eff2f5;
    text-align: center;
    font-weight: bold;
    font-size: 16px;
}

.list-tr {
    height: 40px;
    line-height: 40px;
    background-color: #eff2f5;
}

.list-tr:nth-child(2n) {
    background-color: #fff;
}

td {
    text-align: center;
    font-size: 16px;
}

@media screen and (min-width: 500px) {

    .table {
        width: 100%;
    }

    .th {
        height: 50px;
        line-height: 50px;
        background-color: #eff2f5;
        text-align: center;
        font-weight: bold;
        font-size: 18px;
    }

    .list-tr {
        height: 50px;
        line-height: 50px;
        background-color: #eff2f5;
    }

    .list-tr:nth-child(2n) {
        background-color: #fff;
    }

    td {
        text-align: center;
        font-size: 16px;
    }
}
</style>