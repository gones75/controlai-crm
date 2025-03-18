<template>
    <div class="navbar">
        <div class="navbar-container">
            <!-- Logo à esquerda -->
            <router-link to="/" class="logo">
                <svg viewBox="0 0 400 120" class="controlai-logo">
                    <text x="40" y="80" font-family="Arial" font-weight="700" font-size="64" fill="#000000">
                        controla<tspan fill="#404040">i</tspan>
                    </text>
                    <rect x="315" y="20" width="15" height="15" fill="#000000"/>
                </svg>
            </router-link>

            <!-- Menu Principal Centralizado -->
            <nav class="nav-menu">
                <div class="nav-group">
                    <div class="nav-item" @click="toggleCadastros">
                        Cadastros
                        <span class="arrow" :class="{ 'arrow-down': showCadastros }">▼</span>
                    </div>
                    <div v-show="showCadastros" class="nav-submenu">
                        <router-link to="/clientes" class="submenu-item">Clientes</router-link>
                        <router-link to="/fornecedores" class="submenu-item">Fornecedores</router-link>
                    </div>
                </div>

                <router-link to="/produtos" class="nav-item">Produtos</router-link>
                
                <div class="nav-group">
                    <div class="nav-item" @click="toggleNotas">
                        Notas
                        <span class="arrow" :class="{ 'arrow-down': showNotas }">▼</span>
                    </div>
                    <div v-show="showNotas" class="nav-submenu">
                        <router-link to="/criar-notas" class="submenu-item">Criar Notas</router-link>
                        <router-link to="/buscar-notas" class="submenu-item">Buscar Notas</router-link>
                    </div>
                </div>

                <div class="nav-group">
                    <div class="nav-item" @click="toggleDespesas">
                        Despesas
                        <span class="arrow" :class="{ 'arrow-down': showDespesas }">▼</span>
                    </div>
                    <div v-show="showDespesas" class="nav-submenu">
                        <router-link to="/criar-despesas" class="submenu-item">Criar Despesas</router-link>
                        <router-link to="/buscar-despesas" class="submenu-item">Buscar Despesas</router-link>
                    </div>
                </div>
            </nav>

            <!-- Perfil do Usuário à direita -->
            <div class="user-profile" @click="toggleUserMenu" ref="userMenu">
                <div class="user-info">
                    <span class="user-name">{{ userName }} ▼</span>
                </div>
                <div v-show="showUserMenu" class="user-menu">
                    <div class="user-menu-item" @click="logout">Sair</div>
                </div>
            </div>
        </div>
    </div>
</template>


<script>
import axios from 'axios'

export default {
    name: 'Navbar',
    data() {
        return {
            showCadastros: false,
            showNotas: false,
            showDespesas: false,
            showUserMenu: false,
            userName: ''
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
    },

    beforeDestroy() {
        document.removeEventListener('click', this.handleClickOutside);
    },


    methods: {
        toggleNotas() {
            this.showNotas = !this.showNotas;
        },
        toggleDespesas() { 
            this.showDespesas = !this.showDespesas;
        },
        toggleCadastros() { 
            this.showCadastros = !this.showCadastros;
        },
        
        toggleUserMenu() {
            this.showUserMenu = !this.showUserMenu;
        },

        handleClickOutside(event) {
            if (this.$refs.userMenu && !this.$refs.userMenu.contains(event.target)) {
                this.showUserMenu = false;
            }
        },

        async logout() {
            try {
                await axios.post('/api/logout');
                localStorage.removeItem('token');
                localStorage.removeItem('user');
                this.$router.push('/login');
            } catch (error) {
                console.error('Erro no logout:', error);
            }
        }
    }
}
</script>

<style scoped>
.navbar {
    background-color: #ffffff;
    border-bottom: 1px solid #e5e7eb;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1000;
}

.navbar-container {
    display: grid;
    grid-template-columns: auto 1fr auto;
    align-items: center;
    height: 64px;
    width: 100%;
}

.logo {
    padding: 8px 20px;
    text-decoration: none;
}

.controlai-logo {
    height: 36px;
    width: auto;
}

.nav-menu {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    height: 100%;
}

.nav-group {
    position: relative;
    height: 100%;
    display: flex;
    align-items: center;
}

.nav-item {
    padding: 8px 16px;
    color: #4b5563;
    text-decoration: none;
    font-weight: 600;
    font-size: 16px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 4px;
    height: 100%;
    transition: all 0.2s ease;
}

.nav-item:hover {
    color: #4338ca;
}

.arrow {
    font-size: 10px;
    transition: transform 0.2s ease;
    color: currentColor;
}

.arrow-down {
    transform: rotate(180deg);
}

.nav-submenu {
    position: absolute;
    top: 100%;
    left: 0;
    background-color: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    min-width: 200px;
    z-index: 1000;
}

.submenu-item {
    display: block;
    padding: 10px 16px;
    color: #4b5563;
    text-decoration: none;
    font-size: 15px; 
    font-weight: 500;
    transition: all 0.2s ease;
}

.submenu-item:hover {
    background-color: #f3f4f6;
    color: #4338ca;
}

.user-profile {
    height: 100%;
    padding: 0 20px;
    display: flex;
    align-items: center;
    border-left: 1px solid #e5e7eb;
}

.user-info {
    cursor: pointer;
    padding: 8px;
}

.user-name {
    color: #4b5563;
    font-size: 14px;
    font-weight: 500;
}

.user-menu {
    position: absolute;
    top: 100%;
    right: 0;
    background-color: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    min-width: 150px;
    z-index: 1000;
}

.user-menu-item {
    padding: 10px 16px;
    color: #4b5563;
    font-size: 14px;
    transition: all 0.2s ease;
    cursor: pointer;
}

.user-menu-item:hover {
    background-color: #f3f4f6;
    color: #4338ca;
}

@media (max-width: 768px) {
    .navbar-container {
        grid-template-columns: auto auto auto;
    }
    
    .nav-menu {
        gap: 4px;
    }

    .nav-item {
        padding: 8px 12px;
        font-size: 15px; 
    }

    .submenu-item {
        font-size: 14px; 
    }

    .user-profile {
        padding: 0 10px;
    }
}
</style>