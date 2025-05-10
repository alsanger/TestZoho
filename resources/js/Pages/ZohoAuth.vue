<!-- resources/js/components/ZohoAuth.vue -->
<template>
    <div class="zoho-auth-wrapper">
        <div class="zoho-card">
            <h2>Авторизация Zoho CRM</h2>
            <p>Для использования приложения необходимо авторизоваться в Zoho CRM.</p>
            <a :href="authUrl" class="zoho-button">Авторизоваться</a>
        </div>
    </div>
</template>

<script>
export default {
    name: 'ZohoAuth',
    props: {
        authUrl: {
            type: String,
            default: '/'
        }
    }
}
</script>
