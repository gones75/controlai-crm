<template>
    <div class="login-container">
        <!-- Círculos decorativos de fundo -->
        <div class="background-circle top-left"></div>
        <div class="background-circle bottom-right"></div>

        <div class="login-content">
            <!-- Card do Login -->
            <div class="login-box">
                <div class="logo">
                    <svg viewBox="0 0 400 120" class="controlai-logo">
                        <text x="40" y="80" font-family="Arial" font-weight="700" font-size="64" fill="#000000">
                            controla<tspan fill="#404040">i</tspan>
                        </text>
                        <rect x="315" y="20" width="15" height="15" fill="#000000"/>
                    </svg>
                </div>
                
                <h2 class="login-title">Faça seu login !</h2>
                
                <form @submit.prevent="login" class="login-form">
                    <div class="form-group">
                        <div class="input-wrapper">
                            <i class="email-icon">✉</i>
                            <input 
                                type="email" 
                                id="email" 
                                v-model="form.email"
                                placeholder="Digite seu e-mail"
                                :class="{ 'error': errors.email }"
                                required
                            >
                        </div>
                        <span class="error-message" v-if="errors.email">{{ errors.email }}</span>
                    </div>

                    <div class="form-group">
                        <div class="input-wrapper">
                            <i class="password-icon">🔑</i>
                            <input 
                                type="password" 
                                id="password" 
                                v-model="form.password"
                                placeholder="Digite sua senha"
                                :class="{ 'error': errors.password }"
                                required
                            >
                        </div>
                        <span class="error-message" v-if="errors.password">{{ errors.password }}</span>
                    </div>

                    <button type="submit" class="btn btn-primary">ENTRAR</button>
                </form>
            </div>

            <!-- Card de Welcome -->
            <div class="welcome-box">
                <h1>BEM-VINDO!</h1>
                <p>Sistema de Controle Integrado</p>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios'

export default {
    name: 'Login',
    data() {
        return {
            form: {
                email: '',
                password: ''
            },
            errors: {}
        }
    },
    methods: {
        async login() {
            try {
                const response = await axios.post('/api/login', {
                    email: this.form.email,
                    password: this.form.password
                });
                
                if (response.data.token) {
                    localStorage.setItem('token', response.data.token);
                    if (response.data.user) {
                        localStorage.setItem('user', JSON.stringify(response.data.user));
                    }
                    this.$router.push('/');
                }
            } catch (error) {
                console.error('Erro no login:', error);
                this.errors = error.response?.data?.errors || {
                    email: 'Erro ao fazer login. Tente novamente.'
                };
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
.login-container {
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    background-color: #ffffff;
    position: relative;
    overflow: hidden;
}

.background-circle {
    position: absolute;
    width: 300px;
    height: 300px;
    border-radius: 50%;
    background: linear-gradient(135deg, #4338ca, #3730a3);
    opacity: 0.8;
}

.top-left {
    top: -100px;
    left: -100px;
}

.bottom-right {
    bottom: -100px;
    right: -100px;
}

.login-content {
    display: flex;
    gap: 20px;
    max-width: 900px;
    width: 100%;
    margin: 0 20px;
    z-index: 1;
}

.login-box {
    background: white;
    padding: 40px;
    border-radius: 16px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    width: 100%;
    max-width: 450px;
}

.welcome-box {
    background: linear-gradient(135deg, #4338ca, #3730a3);
    padding: 40px;
    border-radius: 16px;
    color: white;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
    width: 100%;
    max-width: 450px;
}

.controlai-logo {
    height: 60px;
    width: auto;
    margin-bottom: 20px;
}

.login-title {
    font-size: 24px;
    margin-bottom: 30px;
    color: #1f2937;
}

.input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.input-wrapper i {
    position: absolute;
    left: 12px;
    color: #6b7280;
}

input {
    width: 100%;
    padding: 12px 12px 12px 40px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    font-size: 16px;
    transition: all 0.3s;
}

input:focus {
    outline: none;
    border-color: #4338ca;
    box-shadow: 0 0 0 3px rgba(67, 56, 202, 0.1);
}

.btn {
    width: 100%;
    padding: 12px;
    border-radius: 8px;
    font-weight: 600;
    text-transform: uppercase;
    transition: all 0.3s;
    margin-top: 20px;
}

.btn-primary {
    background: #4338ca;
    color: white;
    border: none;
}

.btn-primary:hover {
    background: #3730a3;
}

.welcome-box h1 {
    font-size: 32px;
    margin-bottom: 16px;
}

.welcome-box p {
    margin-bottom: 32px;
    opacity: 0.9;
}

@media (max-width: 768px) {
    .login-content {
        flex-direction: column;
    }

    .welcome-box {
        display: none;
    }
}
</style>