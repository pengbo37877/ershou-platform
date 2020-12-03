<template>
    <a :class="itemClass"
       @click="onItemClicked" :href="url">
        <div :class="itemLabelClass">
            <slot></slot>
        </div>
        <!--<img src="/images/down.png" class="down" v-if="$parent.value === id">-->
    </a>
</template>

<script>
    /**
     * ly-tab-item
     * @desc 搭配 tab 使用
     * @param {Number} id - 选中的item的索引值
     * @param {slot} [icon] - icon 图标
     * @param {slot} - 文字
     *
     * @example
     * <ly-tab-item>
     *   <img slot="icon" src="http://placehold.it/100x100">
     *   前端
     * </ly-tab-item>
     */
    export default {
        name: 'LyTabItem',
        computed: {
            itemClass: function () {
                if (this.$parent.value === this.id) {
                    return 'tabItemActive';
                }
                return 'tabItem';
            },
            itemLabelClass: function() {
                if (this.$parent.value === this.id) {
                    return 'tabLabelActive';
                }
                return 'tabLabel';
            }
        },
        data() {
            return {
                id: (this.$parent.$children.length || 1) - 1
            }
        },
        props: ['url'],
        methods: {
            onItemClicked: function() {
                this.$parent.$emit('input', this.id)
            }
        }
    }
</script>

<style lang="scss">
    .tabItem {
        position: relative;
        display: flex;
        flex-direction: row;
        justify-content: center;
        align-items: center;
        text-decoration: none;
        flex-grow: 1;
        font-size: 14px;
        border-radius: 5px;
        border: 0.5px solid #ddd;
        padding: 4px 15px;
        /*background-color: #ffe8e8;*/
        &:not(:first-child) {
            margin-left: 10px;
        }
    }

    .tabItemActive {
        display: flex;
        flex-direction: row;
        justify-content: center;
        align-items: center;
        text-decoration: none;
        flex-grow: 1;
        font-size: 15px;
        border-radius: 5px;
        padding: 4px 15px;
        background-color: #3D404A;
        &:not(:first-child) {
            margin-left: 10px;
        }
    }

    .tabLabel {
        color: #3D404A;
        line-height: 32px;
        font-size: 15px;
        opacity: 0.8;
    }

    .tabLabelActive {
        color: white;
        line-height: 32px;
        font-size: 15px;
        font-weight: 600;
    }
</style>
