<!-- resources/js/components/ZohoAuth.vue -->
<template>
    <Header />
    <div class="zoho-auth-wrapper">
        <div class="zoho-card">
            <h2>Zoho CRM authorization</h2>
            <p>To use the application, you need to authorize in Zoho CRM.</p>
            <a :href="authUrl" class="zoho-button">Authorize</a>
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
