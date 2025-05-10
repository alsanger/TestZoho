<!-- resources/js/components/ZohoForm.vue -->
<template>
    <div class="zoho-form-wrapper">
        <div class="zoho-form-card">
            <h2 class="zoho-form-title">Создать сделку и аккаунт в Zoho CRM</h2>

            <div v-if="success" class="zoho-alert zoho-alert-success">
                {{ success }}
            </div>

            <div v-if="error" class="zoho-alert zoho-alert-error">
                {{ error }}
            </div>

            <form @submit.prevent="submitForm" class="zoho-form">
                <div class="zoho-form-section">
                    <h3 class="zoho-section-title">Данные аккаунта</h3>
                    <div class="zoho-form-group">
                        <label class="zoho-form-label">Название аккаунта*</label>
                        <input v-model="form.account_name" type="text" class="zoho-form-input" required>
                        <p v-if="errors.account_name" class="zoho-form-error">{{ errors.account_name }}</p>
                    </div>

                    <div class="zoho-form-group">
                        <label class="zoho-form-label">Сайт*</label>
                        <input v-model="form.account_website" type="url" class="zoho-form-input" required>
                        <p v-if="errors.account_website" class="zoho-form-error">{{ errors.account_website }}</p>
                    </div>

                    <div class="zoho-form-group">
                        <label class="zoho-form-label">Телефон*</label>
                        <input v-model="form.account_phone" type="tel" class="zoho-form-input" required>
                        <p v-if="errors.account_phone" class="zoho-form-error">{{ errors.account_phone }}</p>
                    </div>
                </div>

                <div class="zoho-form-section">
                    <h3 class="zoho-section-title">Данные сделки</h3>
                    <div class="zoho-form-group">
                        <label class="zoho-form-label">Название сделки*</label>
                        <input v-model="form.deal_name" type="text" class="zoho-form-input" required>
                        <p v-if="errors.deal_name" class="zoho-form-error">{{ errors.deal_name }}</p>
                    </div>

                    <div class="zoho-form-group">
                        <label class="zoho-form-label">Стадия сделки*</label>
                        <select v-model="form.deal_stage" class="zoho-form-input" required>
                            <option v-for="stage in dealStages" :key="stage.value" :value="stage.value">
                                {{ stage.label }}
                            </option>
                        </select>
                        <p v-if="errors.deal_stage" class="zoho-form-error">{{ errors.deal_stage }}</p>
                    </div>
                </div>

                <div class="zoho-form-actions">
                    <button type="submit" class="zoho-button" :disabled="loading">
                        {{ loading ? 'Отправка...' : 'Создать запись' }}
                    </button>
                </div>
            </form>
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
            dealStages: [],
            errors: {},
            loading: false,
            success: null,
            error: null,
            loadingStages: false
        };
    },
    created() {
        this.fetchDealStages();
    },
    methods: {
        fetchDealStages() {
            this.loadingStages = true;
            axios.get('/api/zoho/deal-stages')
                .then(response => {
                    if (response.data && response.data.stages) {
                        this.dealStages = response.data.stages;
                        if (this.dealStages.length > 0) {
                            this.form.deal_stage = this.dealStages[0].value;
                        }
                    }
                })
                .catch(error => {
                    console.error('Ошибка при загрузке этапов сделок:', error);
                    if (error.response && error.response.status === 401) {
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
