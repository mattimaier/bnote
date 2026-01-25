# BNote JavaScript Architecture
**Version:** 1.0  
**Date:** 2026-01-25  
**Purpose:** Vanilla JavaScript SPA architecture for BNote frontend

---

## Table of Contents

1. [Overview](#overview)
2. [Directory Structure](#directory-structure)
3. [Core Modules](#core-modules)
4. [Component System](#component-system)
5. [State Management](#state-management)
6. [Routing](#routing)
7. [API Client](#api-client)
8. [Form Handling](#form-handling)
9. [UI Components](#ui-components)
10. [Build & Deployment](#build--deployment)

---

## 1. Overview

### 1.1 Goals

- **Vanilla JavaScript:** No frameworks (React/Vue/Angular)
- **Modular:** ES6 modules, clear separation of concerns
- **Reusable:** Component library for common UI patterns
- **Maintainable:** Clean code, well-documented
- **Performant:** Lazy loading, efficient DOM updates
- **Mobile-Friendly:** Responsive design, touch support

### 1.2 Constraints

- **No Build Tools:** Must run directly in browser
- **No Node.js:** Pure client-side JavaScript
- **ES6+ Support:** Modern browsers only (IE11+ or modern browsers)
- **CDN Dependencies:** External libraries via CDN

### 1.3 Technology Stack

- **JavaScript:** ES6+ (modules, async/await, classes)
- **CSS:** Tailwind CSS 3.x (already integrated)
- **Icons:** Bootstrap Icons (already integrated)
- **HTTP:** Fetch API
- **Storage:** localStorage, sessionStorage
- **DOM:** Native DOM APIs

---

## 2. Directory Structure

```
/js/
├── app/
│   ├── main.js              # Application entry point
│   ├── config.js            # Configuration
│   ├── api/
│   │   ├── client.js        # API client wrapper
│   │   ├── endpoints.js     # Endpoint definitions
│   │   └── auth.js          # Authentication helpers
│   ├── store/
│   │   ├── store.js         # State management
│   │   └── modules/         # State modules
│   ├── router/
│   │   └── router.js        # Client-side routing (optional)
│   ├── components/
│   │   ├── Modal.js
│   │   ├── Dropdown.js
│   │   ├── Table.js
│   │   ├── Form.js
│   │   ├── Card.js
│   │   ├── Alert.js
│   │   ├── Spinner.js
│   │   ├── Pagination.js
│   │   └── DatePicker.js
│   ├── utils/
│   │   ├── dom.js           # DOM utilities
│   │   ├── validation.js    # Form validation
│   │   ├── format.js        # Data formatting
│   │   └── storage.js       # localStorage helpers
│   └── pages/
│       ├── Dashboard.js
│       ├── Rehearsals.js
│       ├── Concerts.js
│       ├── Contacts.js
│       └── ...
└── lib/                      # Third-party libraries (if needed)
```

---

## 3. Core Modules

### 3.1 Application Entry (`/js/app/main.js`)

```javascript
// Application initialization
class BNoteApp {
    constructor() {
        this.api = new ApiClient();
        this.store = new Store();
        this.router = new Router(); // Optional
        this.init();
    }
    
    async init() {
        // Check authentication
        const authenticated = await this.api.auth.checkSession();
        if (!authenticated) {
            this.redirectToLogin();
            return;
        }
        
        // Load user data
        const user = await this.api.auth.getUser();
        this.store.set('user', user);
        
        // Initialize router
        this.router.init();
        
        // Load current page
        this.loadPage();
    }
    
    loadPage() {
        const page = this.router.getCurrentPage();
        const PageClass = this.getPageClass(page);
        if (PageClass) {
            const pageInstance = new PageClass(this.api, this.store);
            pageInstance.render();
        }
    }
    
    redirectToLogin() {
        window.location.href = '/main.php?mod=login';
    }
}

// Initialize app when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.app = new BNoteApp();
});
```

### 3.2 Configuration (`/js/app/config.js`)

```javascript
const Config = {
    api: {
        baseUrl: '/api/v1',
        timeout: 30000
    },
    storage: {
        prefix: 'bnote_',
        sessionKey: 'bnote_session'
    },
    ui: {
        theme: 'default',
        language: 'de'
    },
    pagination: {
        defaultLimit: 50,
        maxLimit: 200
    }
};

export default Config;
```

---

## 4. Component System

### 4.1 Base Component Class

```javascript
class Component {
    constructor(container, props = {}) {
        this.container = container;
        this.props = props;
        this.state = {};
    }
    
    setState(newState) {
        this.state = { ...this.state, ...newState };
        this.render();
    }
    
    render() {
        // Override in subclasses
    }
    
    destroy() {
        if (this.container) {
            this.container.innerHTML = '';
        }
    }
}
```

### 4.2 Component Examples

**Modal Component:**
```javascript
class Modal extends Component {
    constructor(container, props) {
        super(container, props);
        this.isOpen = false;
    }
    
    open() {
        this.isOpen = true;
        this.render();
    }
    
    close() {
        this.isOpen = false;
        this.render();
    }
    
    render() {
        if (!this.isOpen) {
            this.container.innerHTML = '';
            return;
        }
        
        this.container.innerHTML = `
            <div class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
                <div class="bg-white rounded-xl shadow-medium p-6 max-w-2xl w-full mx-4">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-semibold">${this.props.title}</h2>
                        <button class="text-gray-400 hover:text-gray-600" onclick="this.close()">
                            <i class="bi-x-lg"></i>
                        </button>
                    </div>
                    <div class="modal-content">
                        ${this.props.content || ''}
                    </div>
                </div>
            </div>
        `;
        
        // Attach event listeners
        this.container.querySelector('.bi-x-lg').closest('button')
            .addEventListener('click', () => this.close());
    }
}
```

**Table Component:**
```javascript
class DataTable extends Component {
    constructor(container, props) {
        super(container, props);
        this.data = props.data || [];
        this.columns = props.columns || [];
        this.sortBy = null;
        this.sortOrder = 'asc';
    }
    
    sort(column) {
        if (this.sortBy === column) {
            this.sortOrder = this.sortOrder === 'asc' ? 'desc' : 'asc';
        } else {
            this.sortBy = column;
            this.sortOrder = 'asc';
        }
        this.render();
    }
    
    render() {
        const sortedData = this.getSortedData();
        
        this.container.innerHTML = `
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            ${this.columns.map(col => `
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase cursor-pointer"
                                    onclick="this.sort('${col.key}')">
                                    ${col.label}
                                    ${this.sortBy === col.key ? 
                                        (this.sortOrder === 'asc' ? '↑' : '↓') : ''}
                                </th>
                            `).join('')}
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        ${sortedData.map(row => `
                            <tr class="hover:bg-gray-50">
                                ${this.columns.map(col => `
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        ${this.formatCell(row[col.key], col)}
                                    </td>
                                `).join('')}
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        `;
    }
    
    getSortedData() {
        if (!this.sortBy) return this.data;
        
        return [...this.data].sort((a, b) => {
            const aVal = a[this.sortBy];
            const bVal = b[this.sortBy];
            
            if (this.sortOrder === 'asc') {
                return aVal > bVal ? 1 : -1;
            } else {
                return aVal < bVal ? 1 : -1;
            }
        });
    }
    
    formatCell(value, column) {
        if (column.format === 'date') {
            return new Date(value).toLocaleDateString('de-DE');
        }
        if (column.format === 'currency') {
            return new Intl.NumberFormat('de-DE', {
                style: 'currency',
                currency: 'EUR'
            }).format(value);
        }
        return value;
    }
}
```

---

## 5. State Management

### 5.1 Simple Store

```javascript
class Store {
    constructor() {
        this.state = {};
        this.listeners = [];
    }
    
    set(key, value) {
        this.state[key] = value;
        this.notify(key, value);
    }
    
    get(key) {
        return this.state[key];
    }
    
    subscribe(key, callback) {
        this.listeners.push({ key, callback });
    }
    
    notify(key, value) {
        this.listeners
            .filter(listener => listener.key === key)
            .forEach(listener => listener.callback(value));
    }
}
```

### 5.2 Usage Example

```javascript
// Set state
app.store.set('user', { id: 5, name: 'John' });

// Get state
const user = app.store.get('user');

// Subscribe to changes
app.store.subscribe('user', (user) => {
    console.log('User changed:', user);
});
```

---

## 6. Routing

### 6.1 Simple Router (Optional)

```javascript
class Router {
    constructor() {
        this.routes = {};
        this.currentRoute = null;
    }
    
    register(path, handler) {
        this.routes[path] = handler;
    }
    
    navigate(path) {
        window.history.pushState({}, '', path);
        this.handleRoute();
    }
    
    handleRoute() {
        const path = window.location.pathname;
        const handler = this.routes[path] || this.routes['/'];
        if (handler) {
            handler();
        }
    }
    
    init() {
        window.addEventListener('popstate', () => this.handleRoute());
        this.handleRoute();
    }
}
```

### 6.2 Alternative: URL-Based Routing

For simplicity, we can use URL parameters:
```
/modern/dashboard.html
/modern/rehearsals.html
/modern/rehearsals.html?id=42
/modern/contacts.html
```

---

## 7. API Client

### 7.1 API Client Class

```javascript
class ApiClient {
    constructor() {
        this.baseUrl = '/api/v1';
        this.timeout = 30000;
    }
    
    async request(method, endpoint, data = null, options = {}) {
        const url = `${this.baseUrl}${endpoint}`;
        const config = {
            method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            credentials: 'same-origin', // Include cookies
            ...options
        };
        
        if (data && (method === 'POST' || method === 'PUT')) {
            config.body = JSON.stringify(data);
        }
        
        try {
            const response = await fetch(url, config);
            const result = await response.json();
            
            if (!response.ok) {
                throw new ApiError(result.error.code, result.error.message, result.error.details);
            }
            
            return result.data;
        } catch (error) {
            if (error instanceof ApiError) {
                throw error;
            }
            throw new ApiError('NETWORK_ERROR', 'Network request failed', error);
        }
    }
    
    async get(endpoint, params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const url = queryString ? `${endpoint}?${queryString}` : endpoint;
        return this.request('GET', url);
    }
    
    async post(endpoint, data) {
        return this.request('POST', endpoint, data);
    }
    
    async put(endpoint, data) {
        return this.request('PUT', endpoint, data);
    }
    
    async delete(endpoint) {
        return this.request('DELETE', endpoint);
    }
}

class ApiError extends Error {
    constructor(code, message, details = []) {
        super(message);
        this.code = code;
        this.details = details;
    }
}
```

### 7.2 Auth Module

```javascript
class AuthApi {
    constructor(client) {
        this.client = client;
    }
    
    async login(username, password) {
        return this.client.post('/auth/login', { username, password });
    }
    
    async logout() {
        return this.client.post('/auth/logout');
    }
    
    async checkSession() {
        try {
            const result = await this.client.get('/auth/session');
            return result.authenticated;
        } catch (error) {
            return false;
        }
    }
    
    async getUser() {
        const result = await this.client.get('/auth/session');
        return result.user;
    }
}
```

### 7.3 Module-Specific APIs

```javascript
class RehearsalsApi {
    constructor(client) {
        this.client = client;
    }
    
    async list(params = {}) {
        return this.client.get('/rehearsals', params);
    }
    
    async get(id) {
        return this.client.get(`/rehearsals/${id}`);
    }
    
    async create(data) {
        return this.client.post('/rehearsals', data);
    }
    
    async update(id, data) {
        return this.client.put(`/rehearsals/${id}`, data);
    }
    
    async delete(id) {
        return this.client.delete(`/rehearsals/${id}`);
    }
    
    async getParticipants(id) {
        return this.client.get(`/rehearsals/${id}/participants`);
    }
    
    async participate(id, participate, reason = '') {
        return this.client.post(`/rehearsals/${id}/participate`, {
            participate,
            reason
        });
    }
}
```

### 7.4 API Client Usage

```javascript
// Initialize
const apiClient = new ApiClient();
const authApi = new AuthApi(apiClient);
const rehearsalsApi = new RehearsalsApi(apiClient);

// Usage
const rehearsals = await rehearsalsApi.list({ page: 1, limit: 50 });
const rehearsal = await rehearsalsApi.get(42);
await rehearsalsApi.participate(42, 1, 'Will attend');
```

---

## 8. Form Handling

### 8.1 Form Handler

```javascript
class FormHandler {
    constructor(formElement, onSubmit) {
        this.form = formElement;
        this.onSubmit = onSubmit;
        this.init();
    }
    
    init() {
        this.form.addEventListener('submit', async (e) => {
            e.preventDefault();
            await this.handleSubmit();
        });
    }
    
    async handleSubmit() {
        const data = this.serialize();
        const errors = this.validate(data);
        
        if (errors.length > 0) {
            this.showErrors(errors);
            return;
        }
        
        try {
            await this.onSubmit(data);
        } catch (error) {
            this.showError(error.message);
        }
    }
    
    serialize() {
        const formData = new FormData(this.form);
        const data = {};
        for (const [key, value] of formData.entries()) {
            data[key] = value;
        }
        return data;
    }
    
    validate(data) {
        const errors = [];
        const requiredFields = this.form.querySelectorAll('[required]');
        
        requiredFields.forEach(field => {
            if (!data[field.name]) {
                errors.push({
                    field: field.name,
                    message: `${field.label || field.name} is required`
                });
            }
        });
        
        return errors;
    }
    
    showErrors(errors) {
        errors.forEach(error => {
            const field = this.form.querySelector(`[name="${error.field}"]`);
            if (field) {
                field.classList.add('border-red-500');
                const errorMsg = document.createElement('div');
                errorMsg.className = 'text-red-500 text-sm mt-1';
                errorMsg.textContent = error.message;
                field.parentNode.appendChild(errorMsg);
            }
        });
    }
    
    showError(message) {
        // Show global error message
        const alert = new Alert(document.body, {
            type: 'error',
            message: message
        });
        alert.show();
    }
}
```

### 8.2 Usage Example

```javascript
const form = document.querySelector('#rehearsal-form');
const handler = new FormHandler(form, async (data) => {
    await rehearsalsApi.create(data);
    // Show success message
    // Redirect or refresh
});
```

---

## 9. UI Components

### 9.1 Alert/Notification Component

```javascript
class Alert extends Component {
    constructor(container, props) {
        super(container, props);
        this.type = props.type || 'info'; // info, success, error, warning
        this.message = props.message;
        this.duration = props.duration || 5000;
    }
    
    show() {
        this.render();
        setTimeout(() => this.hide(), this.duration);
    }
    
    hide() {
        this.container.innerHTML = '';
    }
    
    render() {
        const colors = {
            info: 'bg-blue-50 text-blue-800 border-blue-200',
            success: 'bg-green-50 text-green-800 border-green-200',
            error: 'bg-red-50 text-red-800 border-red-200',
            warning: 'bg-yellow-50 text-yellow-800 border-yellow-200'
        };
        
        this.container.innerHTML = `
            <div class="fixed top-4 right-4 z-50 max-w-md">
                <div class="border rounded-lg p-4 shadow-medium ${colors[this.type]}">
                    <div class="flex items-center justify-between">
                        <p>${this.message}</p>
                        <button onclick="this.hide()" class="ml-4">
                            <i class="bi-x"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
    }
}
```

### 9.2 Loading Spinner

```javascript
class Spinner extends Component {
    render() {
        this.container.innerHTML = `
            <div class="flex items-center justify-center p-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
            </div>
        `;
    }
}
```

### 9.3 Pagination Component

```javascript
class Pagination extends Component {
    constructor(container, props) {
        super(container, props);
        this.page = props.page || 1;
        this.totalPages = props.totalPages || 1;
        this.onPageChange = props.onPageChange || (() => {});
    }
    
    setPage(page) {
        this.page = page;
        this.onPageChange(page);
        this.render();
    }
    
    render() {
        const pages = [];
        for (let i = 1; i <= this.totalPages; i++) {
            pages.push(i);
        }
        
        this.container.innerHTML = `
            <div class="flex gap-2 items-center">
                <button 
                    class="px-3 py-2 rounded-lg ${this.page === 1 ? 'bg-gray-200' : 'bg-primary-600 text-white'}"
                    ${this.page === 1 ? 'disabled' : ''}
                    onclick="this.setPage(${this.page - 1})">
                    Previous
                </button>
                ${pages.map(p => `
                    <button 
                        class="px-3 py-2 rounded-lg ${this.page === p ? 'bg-primary-600 text-white' : 'bg-gray-200'}"
                        onclick="this.setPage(${p})">
                        ${p}
                    </button>
                `).join('')}
                <button 
                    class="px-3 py-2 rounded-lg ${this.page === this.totalPages ? 'bg-gray-200' : 'bg-primary-600 text-white'}"
                    ${this.page === this.totalPages ? 'disabled' : ''}
                    onclick="this.setPage(${this.page + 1})">
                    Next
                </button>
            </div>
        `;
    }
}
```

---

## 10. Page Implementation Example

### 10.1 Rehearsals Page

```javascript
class RehearsalsPage {
    constructor(api, store) {
        this.api = api;
        this.store = store;
        this.container = document.querySelector('#main-content');
        this.page = 1;
        this.loading = false;
    }
    
    async render() {
        this.container.innerHTML = `
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-2xl font-semibold">Rehearsals</h1>
                    <button id="add-rehearsal-btn" class="bg-primary-600 text-white px-4 py-2 rounded-lg">
                        Add Rehearsal
                    </button>
                </div>
                <div id="rehearsals-table"></div>
                <div id="pagination"></div>
            </div>
        `;
        
        await this.loadRehearsals();
        this.attachEventListeners();
    }
    
    async loadRehearsals() {
        this.showLoading();
        
        try {
            const result = await this.api.rehearsals.list({
                page: this.page,
                limit: 50
            });
            
            this.renderTable(result.data);
            this.renderPagination(result.meta.pagination);
        } catch (error) {
            this.showError(error.message);
        } finally {
            this.hideLoading();
        }
    }
    
    renderTable(rehearsals) {
        const tableContainer = document.querySelector('#rehearsals-table');
        const table = new DataTable(tableContainer, {
            data: rehearsals,
            columns: [
                { key: 'begin', label: 'Date', format: 'date' },
                { key: 'location', label: 'Location', format: (val) => val.name },
                { key: 'conductor', label: 'Conductor', format: (val) => `${val.name} ${val.surname}` },
                { key: 'status', label: 'Status' }
            ]
        });
        table.render();
    }
    
    renderPagination(pagination) {
        const paginationContainer = document.querySelector('#pagination');
        const paginationComponent = new Pagination(paginationContainer, {
            page: pagination.page,
            totalPages: pagination.pages,
            onPageChange: (page) => {
                this.page = page;
                this.loadRehearsals();
            }
        });
        paginationComponent.render();
    }
    
    attachEventListeners() {
        document.querySelector('#add-rehearsal-btn')
            .addEventListener('click', () => this.showAddForm());
    }
    
    showAddForm() {
        const modal = new Modal(document.body, {
            title: 'Add Rehearsal',
            content: this.getFormHTML()
        });
        modal.open();
        
        const form = document.querySelector('#rehearsal-form');
        const handler = new FormHandler(form, async (data) => {
            await this.api.rehearsals.create(data);
            modal.close();
            await this.loadRehearsals();
        });
    }
    
    getFormHTML() {
        return `
            <form id="rehearsal-form">
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1">Begin</label>
                    <input type="datetime-local" name="begin" required 
                           class="border border-gray-200 rounded-lg px-3 py-2 w-full">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1">End</label>
                    <input type="datetime-local" name="end" required 
                           class="border border-gray-200 rounded-lg px-3 py-2 w-full">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1">Location</label>
                    <select name="location" required 
                            class="border border-gray-200 rounded-lg px-3 py-2 w-full">
                        <!-- Options loaded dynamically -->
                    </select>
                </div>
                <button type="submit" class="bg-primary-600 text-white px-4 py-2 rounded-lg">
                    Save
                </button>
            </form>
        `;
    }
    
    showLoading() {
        const spinner = new Spinner(this.container);
        spinner.render();
    }
    
    hideLoading() {
        // Remove spinner
    }
    
    showError(message) {
        const alert = new Alert(document.body, {
            type: 'error',
            message: message
        });
        alert.show();
    }
}
```

---

## 11. Utilities

### 11.1 DOM Utilities

```javascript
class DOMUtils {
    static $(selector) {
        return document.querySelector(selector);
    }
    
    static $$(selector) {
        return document.querySelectorAll(selector);
    }
    
    static createElement(tag, classes = '', content = '') {
        const el = document.createElement(tag);
        el.className = classes;
        el.innerHTML = content;
        return el;
    }
    
    static show(element) {
        element.classList.remove('hidden');
    }
    
    static hide(element) {
        element.classList.add('hidden');
    }
}
```

### 11.2 Format Utilities

```javascript
class FormatUtils {
    static date(dateString) {
        return new Date(dateString).toLocaleDateString('de-DE');
    }
    
    static datetime(dateString) {
        return new Date(dateString).toLocaleString('de-DE');
    }
    
    static currency(amount) {
        return new Intl.NumberFormat('de-DE', {
            style: 'currency',
            currency: 'EUR'
        }).format(amount);
    }
    
    static time(dateString) {
        return new Date(dateString).toLocaleTimeString('de-DE', {
            hour: '2-digit',
            minute: '2-digit'
        });
    }
}
```

### 11.3 Storage Utilities

```javascript
class StorageUtils {
    static set(key, value) {
        localStorage.setItem(`bnote_${key}`, JSON.stringify(value));
    }
    
    static get(key) {
        const value = localStorage.getItem(`bnote_${key}`);
        return value ? JSON.parse(value) : null;
    }
    
    static remove(key) {
        localStorage.removeItem(`bnote_${key}`);
    }
}
```

---

## 12. Integration with Existing UI

### 12.1 Progressive Enhancement

**Option 1: Separate Pages**
- Create `/modern/` directory
- New pages: `modern/dashboard.html`, `modern/rehearsals.html`, etc.
- Old pages remain at `/src/presentation/modules/`

**Option 2: Feature Flag**
- Add toggle in settings: "Use new UI"
- JavaScript checks flag and loads appropriate UI
- URL parameter: `?ui=modern`

### 12.2 Shared Layout

**Reuse existing layout:**
- Header (`banner.php`)
- Sidebar (`navigation.php`)
- Footer (`footer.php`)

**JavaScript loads content into main area:**
```javascript
// In modern pages
<div id="app">
    <!-- Header and sidebar loaded via PHP includes -->
    <main id="main-content" class="md:ml-64 p-4 md:p-6">
        <!-- JavaScript renders content here -->
    </main>
</div>
```

---

## 13. Best Practices

### 13.1 Error Handling

**Always handle errors:**
```javascript
try {
    const data = await api.rehearsals.list();
    // Handle success
} catch (error) {
    if (error.code === 'AUTH_REQUIRED') {
        // Redirect to login
    } else if (error.code === 'NETWORK_ERROR') {
        // Show network error
    } else {
        // Show generic error
    }
}
```

### 13.2 Loading States

**Show loading indicators:**
```javascript
async loadData() {
    this.showLoading();
    try {
        const data = await this.api.getData();
        this.render(data);
    } finally {
        this.hideLoading();
    }
}
```

### 13.3 Debouncing

**Debounce search inputs:**
```javascript
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

const searchInput = document.querySelector('#search');
searchInput.addEventListener('input', debounce((e) => {
    this.search(e.target.value);
}, 300));
```

---

## 14. Testing

### 14.1 Manual Testing

**Browser Console Testing:**
```javascript
// Test API
const api = new ApiClient();
const rehearsals = await api.get('/rehearsals');
console.log(rehearsals);

// Test components
const modal = new Modal(document.body, { title: 'Test', content: 'Hello' });
modal.open();
```

### 14.2 Integration Testing

**Test full workflows:**
1. Login
2. Load dashboard
3. Navigate to rehearsals
4. Create rehearsal
5. Edit rehearsal
6. Delete rehearsal

---

## 15. Performance Optimization

### 15.1 Lazy Loading

**Load components on demand:**
```javascript
async loadPage(pageName) {
    const module = await import(`./pages/${pageName}.js`);
    const PageClass = module.default;
    const page = new PageClass(this.api, this.store);
    page.render();
}
```

### 15.2 Caching

**Cache API responses:**
```javascript
class CachedApiClient extends ApiClient {
    constructor() {
        super();
        this.cache = new Map();
    }
    
    async get(endpoint, params = {}) {
        const key = `${endpoint}:${JSON.stringify(params)}`;
        if (this.cache.has(key)) {
            return this.cache.get(key);
        }
        const data = await super.get(endpoint, params);
        this.cache.set(key, data);
        return data;
    }
}
```

---

**Document Status:** Complete  
**Last Updated:** 2026-01-25  
**Next:** See `MIGRATION_PLAN.md` for detailed migration roadmap
