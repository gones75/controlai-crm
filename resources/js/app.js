import './bootstrap';
import { createApp } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import App from './App.vue';
import Home from './views/Home.vue'
import MainLayout from './layouts/MainLayout.vue';
import Login from './views/Login.vue';
import Clientes from './views/Clientes.vue';
import Fornecedores from './views/Fornecedores.vue';
import Produtos from './views/Produtos.vue';
import CriarNotas from './views/CriarNotas.vue';
import BuscarNotas from './views/BuscarNotas.vue';
import CriarDespesas from './views/CriarDespesas.vue';
import BuscarDespesas from './views/BuscarDespesas.vue';

const routes = [
    {
        path: '/login',
        component: Login,
        meta: { guestOnly: true }
    },
    {
        path: '/',
        component: MainLayout,
        meta: { requiresAuth: true },
        children: [
            {
                path: '', 
                redirect: '/home'
            },
            {
                path: '/home',
                name: 'home',
                component: Home
            },
            {
                path: '/clientes',
                name: 'clientes',
                component: Clientes
            },
            {
                path: '/produtos',
                name: 'produtos',
                component: Produtos
            },
            {
                path: '/criar-notas',
                name: 'criar-notas',
                component: CriarNotas
            },
            {
                path: '/buscar-notas',
                name: 'buscar-notas',
                component: BuscarNotas
            },
            {
                path: '/criar-despesas',
                name: 'criar-despesas',
                component: CriarDespesas
            },
            {
                path: '/buscar-despesas',
                name: 'buscar-despesas',
                component: BuscarDespesas,  
            },
            {
                path: '/fornecedores',
                name: 'fornecedores',
                component: Fornecedores
            },
        ]
    }
];

const router = createRouter({
    history: createWebHistory(),
    routes
});

router.beforeEach((to, from, next) => {
    try {
        const hasToken = localStorage.getItem('token');

        if (to.meta.requiresAuth && !hasToken) {
            next('/login');
            return;
        }

        // Rota é login e já tem token
        if (to.path === '/login' && hasToken) {
            next('/');
            return;
        }

        next();
    } catch (error) {
        console.error('Erro na navegação:', error);
        next('/login');
    }
});

const app = createApp(App);
app.use(router);
app.mount('#app');

export default router;