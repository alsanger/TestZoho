<template>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h2 class="text-2xl font-bold mb-4">Создать сделку и аккаунт в Zoho CRM</h2>

                    <div v-if="success" class="bg-green-100 p-4 mb-4 rounded">
                        {{ success }}
                    </div>

                    <div v-if="error" class="bg-red-100 p-4 mb-4 rounded">
                        {{ error }}
                    </div>

                    <form @submit.prevent="submitForm">
                        <div class="mb-4">
                            <h3 class="text-xl font-bold">Данные аккаунта</h3>
                            <div class="mb-4">
                                <label class="block text-gray-700 mb-2">Название аккаунта*</label>
                                <input v-model="form.account_name" type="text" class="w-full border px-3 py-2 rounded"
                                       required>
                                <p v-if="errors.account_name" class="text-red-500 text-sm mt-1">{{
                                        errors.account_name
                                    }}</p>
                            </div>

                            <div class="mb-4">
                                <label class="block text-gray-700 mb-2">Сайт*</label>
                                <input v-model="form.account_website" type="url" class="w-full border px-3 py-2 rounded"
                                       required>
                                <p v-if="errors.account_website" class="text-red-500 text-sm mt-1">
                                    {{ errors.account_website }}</p>
                            </div>

                            <div class="mb-4">
                                <label class="block text-gray-700 mb-2">Телефон*</label>
                                <input v-model="form.account_phone" type="tel" class="w-full border px-3 py-2 rounded"
                                       required>
                                <p v-if="errors.account_phone" class="text-red-500 text-sm mt-1">{{
                                        errors.account_phone
                                    }}</p>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h3 class="text-xl font-bold">Данные сделки</h3>
                            <div class="mb-4">
                                <label class="block text-gray-700 mb-2">Название сделки*</label>
                                <input v-model="form.deal_name" type="text" class="w-full border px-3 py-2 rounded"
                                       required>
                                <p v-if="errors.deal_name" class="text-red-500 text-sm mt-1">{{ errors.deal_name }}</p>
                            </div>

                            <div class="mb-4">
                                <label class="block text-gray-700 mb-2">Стадия сделки*</label>
                                <select v-model="form.deal_stage" class="w-full border px-3 py-2 rounded" required>
                                    <option v-for="stage in dealStages" :key="stage.value" :value="stage.value">
                                        {{ stage.label }}
                                    </option>
                                </select>
                                <p v-if="errors.deal_stage" class="text-red-500 text-sm mt-1">
                                    {{ errors.deal_stage }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-6">
                            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded" :disabled="loading">
                                {{ loading ? 'Отправка...' : 'Создать запись' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios';

export default {
    name: 'ZohoForm',
    data() {
        return {
            form: {
                account_name: '',
                account_website: '',
                account_phone: '',
                deal_name: '',
                deal_stage: ''
            },
            dealStages: [],  // массив для хранения этапов сделок
            errors: {},
            loading: false,
            success: null,
            error: null,
            loadingStages: false
        };
    },
    created() {
        // Загружаем этапы сделок при создании компонента
        this.fetchDealStages();
    },
    methods: {
        fetchDealStages() {
            this.loadingStages = true;

            axios.get('/api/zoho/deal-stages')
                .then(response => {
                    if (response.data && response.data.stages) {
                        this.dealStages = response.data.stages;

                        // Устанавливаем первое значение по умолчанию, если оно есть
                        if (this.dealStages.length > 0) {
                            this.form.deal_stage = this.dealStages[0].value;
                        }
                    }
                })
                .catch(error => {
                    console.error('Ошибка при загрузке этапов сделок:', error);
                    if (error.response && error.response.status === 401) {
                        // Неавторизованный доступ - перенаправляем на страницу авторизации
                        window.location.href = '/auth/zoho';
                    }
                })
                .finally(() => {
                    this.loadingStages = false;
                });
        },
        submitForm() {
            this.errors = {};
            this.success = null;
            this.error = null;
            this.loading = true;

            axios.post('/api/zoho/create', this.form)
                .then(response => {
                    this.success = 'Запись успешно создана в Zoho CRM!';
                    this.form = {
                        account_name: '',
                        account_website: '',
                        account_phone: '',
                        deal_name: '',
                        deal_stage: 'Qualification'
                    };
                })
                .catch(error => {
                    if (error.response) {
                        if (error.response.status === 401) {
                            // Неавторизованный доступ - перенаправляем на страницу авторизации
                            window.location.href = '/auth/zoho';
                            return;
                        }
                        this.error = error.response.data.error || 'Произошла ошибка при создании записи';
                    } else {
                        this.error = 'Произошла неизвестная ошибка';
                    }
                })
                .finally(() => {
                    this.loading = false;
                });
        }
    }
};
</script>
