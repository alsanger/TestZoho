<!-- resources/js/components/ZohoAuth.vue -->
<template>
    <Header />
    <div class="zoho-auth-wrapper">
        <div class="zoho-card">
            <h2>Авторизация Zoho CRM</h2>
            <p>Для использования приложения необходимо авторизоваться в Zoho CRM.</p>
            <a :href="authUrl" class="zoho-button">Авторизоваться</a>
        </div>
    </div>
</template>

<script>
import Header from "../components/Header.vue";

export default {
    name: 'ZohoAuth',
    components: {Header},
    props: {
        authUrl: {
            type: String,
            default: '/'
        }
    }
}
</script>
