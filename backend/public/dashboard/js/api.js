/**
 * PG A1 Management System — API Client Module
 * Handles auth, token management, and all API calls.
 */
const API_BASE = 'http://127.0.0.1:8000/api/v1';

const api = {
  // ─── Token Management ─────────────────────────────────────────
  getToken() {
    return localStorage.getItem('pga1_token');
  },

  setToken(token) {
    localStorage.setItem('pga1_token', token);
  },

  getUser() {
    const u = localStorage.getItem('pga1_user');
    return u ? JSON.parse(u) : null;
  },

  setUser(user) {
    localStorage.setItem('pga1_user', JSON.stringify(user));
  },

  clearAuth() {
    localStorage.removeItem('pga1_token');
    localStorage.removeItem('pga1_user');
  },

  isLoggedIn() {
    return !!this.getToken();
  },

  // ─── HTTP Helpers ─────────────────────────────────────────────
  async request(method, path, body = null, isFormData = false) {
    const headers = { 'Accept': 'application/json' };
    const token = this.getToken();
    if (token) headers['Authorization'] = `Bearer ${token}`;
    if (!isFormData) headers['Content-Type'] = 'application/json';

    const options = { method, headers };
    if (body) {
      options.body = isFormData ? body : JSON.stringify(body);
    }

    const response = await fetch(`${API_BASE}${path}`, options);

    if (response.status === 401) {
      this.clearAuth();
      window.location.href = 'login.html';
      return null;
    }

    let data;
    try {
      data = await response.json();
    } catch (parseErr) {
      // Server returned non-JSON (e.g. HTML error page)
      throw { status: response.status, data: { message: 'Server error. Please try again.' } };
    }

    if (!response.ok) {
      // Sanitize raw SQL/technical errors for display
      if (data.message && (data.message.includes('SQLSTATE') || data.message.includes('Query') || data.message.length > 200)) {
        if (data.message.includes('Duplicate entry') || data.message.includes('1062')) {
          data.message = 'This record already exists. Please use a different value.';
        } else {
          data.message = 'Something went wrong. Please try again.';
        }
      }
      throw { status: response.status, data };
    }

    return data;
  },

  get(path) { return this.request('GET', path); },
  post(path, body) { return this.request('POST', path, body); },
  put(path, body) { return this.request('PUT', path, body); },
  upload(path, formData) { return this.request('POST', path, formData, true); },

  // ─── Auth ─────────────────────────────────────────────────────
  async login(mobile, password) {
    const data = await this.post('/login', { mobile, password });
    if (data && data.token) {
      this.setToken(data.token);
      this.setUser(data.user);
    }
    return data;
  },

  async logout() {
    try { await this.post('/logout'); } catch (e) {}
    this.clearAuth();
    window.location.href = 'login.html';
  },

  async me() {
    return this.get('/me');
  },

  // ─── Super Admin ──────────────────────────────────────────────
  async superAdminDashboard() {
    return this.get('/super-admin/dashboard');
  },

  async listAdmins() {
    return this.get('/super-admin/admins');
  },

  async createAdmin(data) {
    return this.post('/super-admin/admins', data);
  },

  async assignPg(adminId, pgLocationIds) {
    return this.post(`/super-admin/admins/${adminId}/assign-pg`, { pg_location_ids: pgLocationIds });
  },

  async createPgLocation(data) {
    return this.post('/super-admin/pg-locations', data);
  },

  // ─── Admin ────────────────────────────────────────────────────
  async adminPgLocations() {
    return this.get('/admin/pg-locations');
  },

  async adminTenants(params = '') {
    return this.get(`/admin/tenants${params ? '?' + params : ''}`);
  },

  async adminTenantTrash() {
    return this.get('/admin/tenants/trash');
  },

  async deleteTenant(tenantId) {
    return this.request('DELETE', `/admin/tenants/${tenantId}`);
  },

  async restoreTenant(tenantId) {
    return this.post(`/admin/tenants/${tenantId}/restore`);
  },

  async forceDeleteTenant(tenantId) {
    return this.request('DELETE', `/admin/tenants/${tenantId}/force-delete`);
  },

  async adminTenantDetail(id) {
    return this.get(`/admin/tenants/${id}`);
  },

  async adminPayments(status = '') {
    return this.get(`/admin/payments${status ? '?status=' + status : ''}`);
  },

  async verifyPayment(paymentId, verifiedAmount) {
    return this.post(`/admin/payments/${paymentId}/verify`, { verified_amount: verifiedAmount });
  },

  async rejectPayment(paymentId, reason) {
    return this.post(`/admin/payments/${paymentId}/reject`, { rejection_reason: reason });
  },

  async adminRooms(pgLocationId = '') {
    return this.get(`/admin/rooms${pgLocationId ? '?pg_location_id=' + pgLocationId : ''}`);
  },

  async createRoom(data) {
    return this.post('/admin/rooms', data);
  },

  async deleteRoom(roomId) {
    return this.request('DELETE', `/admin/rooms/${roomId}`);
  },

  async adminBeds(roomId = '') {
    return this.get(`/admin/beds${roomId ? '?room_id=' + roomId : ''}`);
  },

  async createBed(data) {
    return this.post('/admin/beds', data);
  },

  async updateBed(bedId, data) {
    return this.put(`/admin/beds/${bedId}`, data);
  },

  async deleteBed(bedId) {
    return this.request('DELETE', `/admin/beds/${bedId}`);
  },

  async generateOnboardingLink(pgLocationId = null) {
    return this.post('/admin/onboarding/invite', { pg_location_id: pgLocationId });
  },

  async listApplications(params = '') {
    if (typeof params === 'string' && params && !params.includes('=')) {
      params = 'status=' + params;
    }
    return this.get(`/admin/onboarding/applications${params ? '?' + params : ''}`);
  },

  async getApplicationDocuments(invitationId) {
    return this.get(`/admin/onboarding/${invitationId}/documents`);
  },

  async viewDocumentUrl(documentId) {
    // Returns the URL with auth — we'll open it in a new tab
    return `${API_BASE}/admin/documents/${documentId}/view`;
  },

  async approveApplication(invitationId, data) {
    return this.post(`/admin/onboarding/${invitationId}/approve`, data);
  },

  async rejectApplication(invitationId, reason) {
    return this.post(`/admin/onboarding/${invitationId}/reject`, { reason });
  },

  async generateRent(billingMonth) {
    return this.post('/admin/rents/generate', { billing_month: billingMonth });
  },

  async generateIndividualRent(tenantId, billingMonth) {
    return this.post('/admin/rents/generate-individual', { tenant_id: tenantId, billing_month: billingMonth });
  },

  // ─── Notes ────────────────────────────────────────────────────
  async getNotes() {
    return this.get('/admin/notes');
  },

  async createNote(data) {
    return this.post('/admin/notes', data);
  },

  async updateNote(noteId, data) {
    return this.put(`/admin/notes/${noteId}`, data);
  },

  async deleteNote(noteId) {
    return this.request('DELETE', `/admin/notes/${noteId}`);
  },

  // ─── Expenses ─────────────────────────────────────────────────
  async getExpenses(month = '', pgLocationId = '') {
    let params = [];
    if (month) params.push('month=' + month);
    if (pgLocationId) params.push('pg_location_id=' + pgLocationId);
    return this.get(`/admin/expenses${params.length ? '?' + params.join('&') : ''}`);
  },

  async createExpense(formData) {
    return this.upload('/admin/expenses', formData);
  },

  async updateExpense(expenseId, formData) {
    return this.upload(`/admin/expenses/${expenseId}`, formData);
  },

  async deleteExpense(expenseId) {
    return this.request('DELETE', `/admin/expenses/${expenseId}`);
  },

  async changeTenantRent(tenantId, data) {
    return this.post(`/admin/tenants/${tenantId}/change-rent`, data);
  },

  async resetTenantPassword(tenantId, newPassword) {
    return this.post(`/admin/tenants/${tenantId}/reset-password`, { new_password: newPassword });
  },

  async impersonateTenant(tenantId) {
    return this.post(`/admin/tenants/${tenantId}/impersonate`);
  },

  async adjustTenantElectricity(tenantId, data) {
    return this.post(`/admin/tenants/${tenantId}/electricity-adjustment`, data);
  },

  async markElectricityPaid(allocationId) {
    return this.post(`/admin/electricity-allocations/${allocationId}/mark-paid`);
  },

  // ─── Tenant ───────────────────────────────────────────────────
  async tenantDashboard() {
    return this.get('/tenant/dashboard');
  },

  async tenantProfile() {
    return this.get('/tenant/profile');
  },

  async tenantRents() {
    return this.get('/tenant/rents');
  },

  async submitPayment(formData) {
    return this.upload('/tenant/payments', formData);
  },

  // ─── Public ───────────────────────────────────────────────────
  async publicPgLocations() {
    return this.request('GET', '/public/pg-locations');
  },
};

// ─── Auth Guard ─────────────────────────────────────────────────
function requireAuth(allowedRoles = []) {
  if (!api.isLoggedIn()) {
    window.location.href = 'login.html';
    return false;
  }
  const user = api.getUser();
  if (allowedRoles.length > 0 && !allowedRoles.includes(user.role)) {
    alert('Access denied. Insufficient permissions.');
    window.location.href = 'login.html';
    return false;
  }
  return true;
}

// Redirect to correct dashboard based on role
function redirectToDashboard() {
  const user = api.getUser();
  if (!user) return;
  switch (user.role) {
    case 'super_admin': window.location.href = 'super-admin.html'; break;
    case 'admin': window.location.href = 'admin.html'; break;
    case 'tenant': window.location.href = 'tenant.html'; break;
  }
}
