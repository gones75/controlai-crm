<template>
    <div class="app-container">
        <Sidebar />
        <div class="main-content">
            <div style="text-align: right; padding: 10px;">
                <UserMenu />
            </div>
            <router-view></router-view>
        </div>
    </div>
</template>

<script>
import Sidebar from '../components/Sidebar.vue';
import UserMenu from '../components/UserMenu.vue';

export default {
    name: 'MainLayout',
    components: {
        Sidebar,
        UserMenu
    }
}
</script>

<style scoped>
.app-container {
    display: flex;
    min-height: 100vh;
}

.main-content {
    flex: 1;
    padding: 20px;
}
</style>