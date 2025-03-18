<template>
    <div class="user-menu">
        <button class="user-button" @click="toggleDropdown">
            {{ userName }}
            <span>▼</span>
        </button>
        <div v-if="showDropdown" class="dropdown-content">
            <a href="#" class="logout-button" @click.prevent="logout">
                Sair
            </a>
        </div>
    </div>
</template>

<script>


export default {
    name: 'UserMenu',
    data() {
        return {
            showDropdown: false,
            userName: ''
        }
    },

    methods: {
        toggleDropdown() {
            this.showDropdown = !this.showDropdown;
        },
        async logout() {
            try {
                await axios.post('/api/logout');
                
                localStorage.clear();
                this.$router.push('/login');
            } catch (error) {
                console.error('Erro ao fazer logout:', error);
            }
        }
    },
    mounted() {
    
        if (localStorage.getItem('user')) {
            try {
                const user = JSON.parse(localStorage.getItem('user'));
                if (user && user.name) {
                    this.userName = user.name;
                }
            } catch {
                localStorage.removeItem('user');
            }
        }
    }
}
</script>

<style scoped>
.user-menu {
    position: relative;
    display: inline-block;
}

.user-button {
    padding: 8px 16px;
    background: white;
    border: 1px solid #ccc;
    border-radius: 4px;
    cursor: pointer;
}

.dropdown-content {
    position: absolute;
    right: 0;
    top: 100%;
    background: white;
    border: 1px solid #ccc;
    border-radius: 4px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-top: 4px;
    z-index: 1000;
}

.logout-button {
    display: block;
    padding: 8px 16px;
    text-decoration: none;
    color: #dc3545;
    cursor: pointer;
    min-width: 100px;
}

.logout-button:hover {
    background: #f8f9fa;
}
</style>