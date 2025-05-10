<template>
    <Header />
    <div class="zoho-form-wrapper">
        <div class="zoho-form-card">
            <h2 class="zoho-form-title">Create a deal and account in Zoho CRM</h2>

            <div v-if="success" class="zoho-alert zoho-alert-success">
                {{ success }}
            </div>

            <div v-if="error" class="zoho-alert zoho-alert-error">
                {{ error }}
            </div>

            <form @submit.prevent="submitForm" class="zoho-form">
                <div class="zoho-form-section">
                    <h3 class="zoho-section-title">Account data</h3>
                    <div class="zoho-form-group">
                        <label class="zoho-form-label">Account name*</label>
                        <input v-model="form.account_name" type="text" class="zoho-form-input" required maxlength="255">
                        <p v-if="errors.account_name" class="zoho-form-error">{{ errors.account_name }}</p>
                    </div>

                    <div class="zoho-form-group">
                        <label class="zoho-form-label">Website*</label>
                        <input v-model="form.account_website" type="url" class="zoho-form-input" required maxlength="255">
                        <p v-if="errors.account_website" class="zoho-form-error">{{ errors.account_website }}</p>
                    </div>

                    <div class="zoho-form-group">
                        <label class="zoho-form-label">Phone*</label>
                        <input v-model="form.account_phone" type="tel" class="zoho-form-input" required maxlength="20">
                        <p v-if="errors.account_phone" class="zoho-form-error">{{ errors.account_phone }}</p>
                    </div>
                </div>

                <div class="zoho-form-section">
                    <h3 class="zoho-section-title">Deal data</h3>
                    <div class="zoho-form-group">
                        <label class="zoho-form-label">Deal name*</label>
                        <input v-model="form.deal_name" type="text" class="zoho-form-input" required maxlength="255">
                        <p v-if="errors.deal_name" class="zoho-form-error">{{ errors.deal_name }}</p>
                    </div>

                    <div class="zoho-form-group">
                        <label class="zoho-form-label">Deal stage*</label>
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
                        {{ loading ? 'Sending...' : 'Create record' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
import axios from 'axios';
import Header from "../components/Header.vue";

export default {
    name: 'ZohoForm',
    components: {Header},
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
                    console.error('Error loading deal stages:', error);
                    if (error.response && error.response.status === 401) {
                        window.location.href = '/auth/zoho';
                    }
                })
                .finally(() => {
                    this.loadingStages = false;
                });
        },

        validateForm() {
            let isValid = true;
            const errors = {};

            // Валидация имени аккаунта
            if (!this.form.account_name) {
                errors.account_name = 'Account name is required';
                isValid = false;
            } else if (this.form.account_name.length > 255) {
                errors.account_name = 'Account name must not exceed 255 characters';
                isValid = false;
            }

            // Валидация веб-сайта
            if (!this.form.account_website) {
                errors.account_website = 'Website URL is required';
                isValid = false;
            } else if (this.form.account_website.length > 255) {
                errors.account_website = 'Website URL must not exceed 255 characters';
                isValid = false;
            } else {
                try {
                    new URL(this.form.account_website);
                } catch (e) {
                    errors.account_website = 'Please enter a valid URL';
                    isValid = false;
                }
            }

            // Валидация телефона
            if (!this.form.account_phone) {
                errors.account_phone = 'Phone is required';
                isValid = false;
            } else if (this.form.account_phone.length > 20) {
                errors.account_phone = 'Phone must not exceed 20 characters';
                isValid = false;
            }

            // Валидация наименования сделки
            if (!this.form.deal_name) {
                errors.deal_name = 'Deal name is required';
                isValid = false;
            } else if (this.form.deal_name.length > 255) {
                errors.deal_name = 'Deal name must not exceed 255 characters';
                isValid = false;
            }

            // Валидация стадии сделки
            if (!this.form.deal_stage) {
                errors.deal_stage = 'Deal stage is required';
                isValid = false;
            } else if (this.form.deal_stage.length > 100) {
                errors.deal_stage = 'Deal stage must not exceed 100 characters';
                isValid = false;
            }

            this.errors = errors;
            return isValid;
        },

        submitForm() {
            this.errors = {};
            this.success = null;
            this.error = null;

            // Валидация формы перед отправкой
            if (!this.validateForm()) {
                return; // Отменяем отправку формы, если есть ошибки
            }

            this.loading = true;

            axios.post('/api/zoho/create', this.form)
                .then(response => {
                    this.success = 'Record successfully created in Zoho CRM!';
                    this.form = {
                        account_name: '',
                        account_website: '',
                        account_phone: '',
                        deal_name: '',
                        deal_stage: this.dealStages.length > 0 ? this.dealStages[0].value : ''
                    };
                })
                .catch(error => {
                    if (error.response) {
                        if (error.response.status === 401) {
                            window.location.href = '/auth/zoho';
                            return;
                        }

                        // Проверяем наличие ошибок валидации с стороны сервера
                        if (error.response.status === 422 && error.response.data.errors) {
                            this.errors = error.response.data.errors;
                            this.error = 'Please fix the errors in the form';
                        } else {
                            this.error = error.response.data.error || 'An error occurred while creating the record';
                        }
                    } else {
                        this.error = 'An unknown error occurred';
                    }
                })
                .finally(() => {
                    this.loading = false;
                });
        }
    }
};
</script>
