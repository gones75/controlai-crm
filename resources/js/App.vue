<template>
    <router-view :key="$route.fullPath"></router-view>
</template>

<script>
export default {
    name: 'App'
}
</script>

<style>
html, body {
    margin: 0;
    padding: 0;
    height: 100%;
}
</style>