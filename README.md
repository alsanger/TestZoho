# Zoho CRM Deal & Account Form (Vue.js + Laravel)

## Overview

This is a test assignment for employment. The goal of the project is to build a web form using **Vue.js** as the frontend and **Laravel** as the backend, which allows users to create both **Deal** and **Account** records in **Zoho CRM**, and link them together using the **Zoho CRM API**.

### Key Features
- Create a **Deal** and an **Account** in Zoho CRM.
- Automatically link the created Deal to the Account.
- OAuth2 authentication with **automatic token refresh**.
- Responsive Vue.js-based frontend.
- Laravel backend with validation and API integration.
- Real-time feedback on success or failure.

## 🔗 Demo & Repository

- GitHub Repository: [https://github.com/alsanger/TestZoho.git](https://github.com/alsanger/TestZoho.git)
- Demo video: _Provided in the submission (mandatory)_

---

## 📦 Technologies Used

- **Frontend**: Vue.js (Composition API)
- **Backend**: Laravel 12
- **API**: Zoho CRM REST API
- **Auth**: OAuth2 with automatic refresh logic
- **HTTP Client**: Axios

---

## 📋 Required Fields

### Account data
| Field | Maps to (Zoho CRM) |
|--------------|--------------------|
| Account name | `Account Name`     |
| Account website | `Website`       |
| Account phone | `Phone`           |

### Deal data
| Field | Maps to (Zoho CRM) |
|-------|---------------------|
| Deal name | `Deal Name` |
| Deal stage | `Stage` |

> Additional required fields (based on Zoho CRM settings) are also handled automatically.

---

## ⚙️ Setup Instructions
```bash
1. git clone https://github.com/alsanger/TestZoho.git
2. cd TestZoho
2. composer install
3. npm install
4. npm run build
5. php artisan migrate
6. cp .env.example .env
7. php artisan key:generate 
8. php artisan serve
```

> Set the following variables in your `.env` file:

```
ZOHO_CLIENT_ID=
ZOHO_CLIENT_SECRET=
```

---

## 🧪 Usage Guide

1. Open the form in your browser (`http://localhost:8000` or your configured URL).
2. Please authenticate on first launch
2. Fill in all fields for the **Account data** and **Deal data**.
3. Click **Create record**.
4. Records will be created in your **Zoho CRM** and automatically linked.
5. A success message will be shown, or an error message if something goes wrong.

---

## 🔄 Token Refresh

- This project uses the **refresh token** mechanism to automatically update access tokens when expired.
- No manual re-authentication is required once set up properly.

---

## 📎 Useful Links

- [Zoho CRM API Documentation](https://www.zoho.com/crm/developer/docs/api/)
- [Zoho CRM OAuth Guide](https://www.zoho.com/crm/developer/docs/api/v2/oauth-overview.html)
- [Laravel](https://laravel.com/)
- [Vue.js](https://vuejs.org/)

---

## 👤 Author

**Oleksandr Salanhin**  
[LinkedIn](https://www.linkedin.com/in/oleksandr-s-59b495363/)  
[DOU Profile](https://dou.ua/users/a-san/)  
[GitHub](https://github.com/alsanger)

---

## ✅ Completion Status

- Test assignment completed within the 3-day deadline.
- Ready for demonstration and review upon request.
