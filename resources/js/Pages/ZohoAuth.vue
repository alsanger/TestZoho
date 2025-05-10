<template>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200 text-center">
                    <h2 class="text-2xl font-bold mb-4">Авторизация Zoho CRM</h2>
                    <p class="mb-6">Для использования приложения необходимо авторизоваться в Zoho CRM.</p>
                    <a :href="authUrl" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-3 px-6 rounded-lg transition">
                        Авторизоваться в Zoho CRM
                    </a>
                </div>
            </div>
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
