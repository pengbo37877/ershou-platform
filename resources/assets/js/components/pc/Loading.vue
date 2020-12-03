<template>
    <div class="loading" v-if="loading">
        <van-loading type="spinner" size="16px" color="#ff9a9a"/>
    </div>
</template>

<script>
    export default {
        data() {
            return {
            }
        },
        props: ['loading']
    }
</script>

<style scoped>
    body {
        background-color: white;
        margin: 0;
    }
    .loading {
        display: flex;
        flex-direction: row;
        justify-content: center;
        align-items: center;
        padding: 10px;
    }
</style>