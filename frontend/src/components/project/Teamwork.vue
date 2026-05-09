<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import api from '../../api/axios'; 

// ==========================================
// 1. INTERFACES & GLOBAL STATE
// ==========================================
interface TeamworkForm {
  name: string;
  email: string;
  password?: string;
  role: string;
  position: string;
  company_id: number | null;
  status: string;
  phone: string;
  hourly_rate: number;
  org_legal_name: string;
  org_email: string;
  org_phone: string;
  org_address: string;
  org_description: string;
}

const isLoading = ref(false);
const totalProjectCount = ref(0);

// Modal States
const showAddModal = ref(false);

// Data States
const teamData = ref<any[]>([]);       
const allCompanies = ref<any[]>([]);   
const teamTags = ref<any[]>([]);       
const organizations = ref<any[]>([]);
const isEditing = ref(false);
const editingId = ref<number | null>(null);
const entityType = ref('individual');
const selectedOrgLogo = ref<File | null>(null); 
const logoPreview = ref<string | null>(null);
const selectedAvatar = ref<File | null>(null);
const avatarPreview = ref<string | null>(null);

const teamworkForm = ref<TeamworkForm>({
  name: '', email: '', password: '', role: 'member', position: '', company_id: null, status: 'Active',
  phone: '', hourly_rate: 0,
  org_legal_name: '', org_email: '', org_phone: '', org_address: '', org_description: ''
});

// Helper Formatting
const formatCurrency = (val: number) => {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val || 0);
};

// ==========================================
// 2. FILTERING, SEARCHING & PAGINATION STATE
// ==========================================
const activeFilter = ref({ type: 'all', value: null as any });
const openMenu = ref<string | null>('role'); 
const searchQuery = ref('');

// Pagination Members (Server-side & Client-side fallback)
const currentPage = ref(1);
const totalPages = ref(1);

// Pagination Organizations (Client-side)
const orgCurrentPage = ref(1);
const orgItemsPerPage = 6;

// Menentukan seksi mana yang tampil di All Data
const showMembersSection = computed(() => {
  return ['all', 'member', 'role', 'tag'].includes(activeFilter.value.type);
});
const showOrgsSection = computed(() => {
  return ['all', 'view_companies', 'tag'].includes(activeFilter.value.type);
});

// ==========================================
// 3. API ACTIONS
// ==========================================
const fetchData = async () => {
  isLoading.value = true;
  try {
    let params: any = { page: currentPage.value };

    if (activeFilter.value.type === 'tag') {
      const selectedTag = teamTags.value.find(t => t.id === activeFilter.value.value);
      if (selectedTag) params.tag_search = selectedTag.name;
    } 
    else if (activeFilter.value.type === 'role') {
      params.tag_search = activeFilter.value.value; 
    }

    if (searchQuery.value) {
      params.search = searchQuery.value;
    }

    const [resUsers, resComp, resTags, resSummary] = await Promise.all([
      api.get('/users', { params }), 
      api.get('/companies'),
      api.get('/master-data/tags_team').catch(() => ({ data: [] })),
      api.get('/teamwork/summary').catch(() => ({ data: {} }))
    ]);

    teamData.value = resUsers.data.data || resUsers.data || [];
    totalPages.value = resUsers.data.last_page || 1;

    allCompanies.value = resComp.data.data || resComp.data || [];
    teamTags.value = resTags.data.data || resTags.data || [];
    
    organizations.value = resSummary.data.organizations || [];
    totalProjectCount.value = resSummary.data.total_projects || 0;

  } catch (e) {
    console.error("Gagal memuat data", e);
  } finally {
    isLoading.value = false;
  }
};

const handleSearch = () => {
  currentPage.value = 1;
  orgCurrentPage.value = 1;
  fetchData();
};

const setFilter = (type: string, value: any) => {
  activeFilter.value = { type, value };
  currentPage.value = 1; 
  orgCurrentPage.value = 1;
  fetchData();           
};

const toggleMenu = (menuName: string) => {
  openMenu.value = openMenu.value === menuName ? null : menuName;
};

// --- LOGIKA PAGINATION MEMBERS ---
const nextPage = () => { if (currentPage.value < totalPages.value) { currentPage.value++; fetchData(); } };
const prevPage = () => { if (currentPage.value > 1) { currentPage.value--; fetchData(); } };
const goToPage = (page: number) => { currentPage.value = page; fetchData(); };

const filteredMembers = computed(() => {
  if (!searchQuery.value) return teamData.value;
  const q = searchQuery.value.toLowerCase();
  return teamData.value.filter(m => 
    (m.name && m.name.toLowerCase().includes(q)) ||
    (m.email && m.email.toLowerCase().includes(q)) ||
    (m.role && m.role.toLowerCase().includes(q)) ||
    (m.position && m.position.toLowerCase().includes(q))
  );
});

// --- LOGIKA PAGINATION ORGANIZATIONS ---
const filteredOrganizations = computed(() => {
  if (!searchQuery.value) return allCompanies.value;
  const q = searchQuery.value.toLowerCase();
  return allCompanies.value.filter(c => 
    (c.name && c.name.toLowerCase().includes(q)) ||
    (c.legal_name && c.legal_name.toLowerCase().includes(q)) ||
    (c.email && c.email.toLowerCase().includes(q))
  );
});

const orgTotalPages = computed(() => Math.ceil(filteredOrganizations.value.length / orgItemsPerPage) || 1);

const paginatedOrganizations = computed(() => {
  const start = (orgCurrentPage.value - 1) * orgItemsPerPage;
  return filteredOrganizations.value.slice(start, start + orgItemsPerPage);
});

const nextOrgPage = () => { if (orgCurrentPage.value < orgTotalPages.value) orgCurrentPage.value++; };
const prevOrgPage = () => { if (orgCurrentPage.value > 1) orgCurrentPage.value--; };
const goToOrgPage = (page: number) => { orgCurrentPage.value = page; };

// ==========================================
// 4. CRUD OPERATIONS
// ==========================================
const handleDeleteCompany = async (id: number) => {
  if (!confirm("Apakah Anda yakin ingin menghapus Organization ini? Data yang dihapus tidak dapat dikembalikan.")) return;
  try {
    await api.delete(`/companies/${id}`);
    alert("Organization berhasil dihapus!");
    await fetchData(); 
  } catch (error: any) {
    alert(error.response?.data?.error || "Gagal menghapus data Organization.");
  }
};

const handleAvatarChange = (e: Event) => {
  const target = e.target as HTMLInputElement;
  const file = target.files?.[0];
  if (file) {
    selectedAvatar.value = file;
    avatarPreview.value = URL.createObjectURL(file);
  }
};

const handleOrgLogoChange = (e: Event) => {
  const target = e.target as HTMLInputElement;
  const file = target.files?.[0];
  if (file) {
    selectedOrgLogo.value = file;
    logoPreview.value = URL.createObjectURL(file);
  }
};

const handleSave = async () => {
  try {
    let url = entityType.value === 'individual' ? '/users/register' : '/companies'; 

    if (entityType.value === 'individual') {
      if (!teamworkForm.value.name || !teamworkForm.value.email) {
          return alert("Nama dan Email wajib diisi.");
      }
      
      let formData = new FormData();
      formData.append('name', teamworkForm.value.name);
      formData.append('email', teamworkForm.value.email);
      formData.append('role', teamworkForm.value.role || 'member');
      formData.append('position', teamworkForm.value.position || '');
      formData.append('company_id', String(teamworkForm.value.company_id || ''));
      formData.append('status', teamworkForm.value.status || 'Active');
      formData.append('phone', teamworkForm.value.phone || '');
      formData.append('hourly_rate', String(teamworkForm.value.hourly_rate || 0));

      if (!isEditing.value) formData.append('password', 'password123');
      if (selectedAvatar.value) formData.append('avatar', selectedAvatar.value);

      if (isEditing.value) {
        url = `/users/${editingId.value}`;
        formData.append('_method', 'PUT'); 
        await api.post(url, formData, { headers: { 'Content-Type': 'multipart/form-data' }});
      } else {
        url = '/users/register';
        await api.post(url, formData, { headers: { 'Content-Type': 'multipart/form-data' }});
      }
      
    } else {
      const companyName = teamworkForm.value.org_legal_name || teamworkForm.value.name;
      if (!companyName) return alert("Nama Perusahaan (Legal Name) wajib diisi.");

      let formData = new FormData();
      formData.append('name', companyName);
      formData.append('legal_name', companyName);
      formData.append('email', teamworkForm.value.org_email || '');
      formData.append('phone', teamworkForm.value.org_phone || '');
      formData.append('address', teamworkForm.value.org_address || '');
      formData.append('description', teamworkForm.value.org_description || '');

      if (selectedOrgLogo.value) formData.append('logo', selectedOrgLogo.value);

      if (isEditing.value) {
        url = `/companies/${editingId.value}`;
        formData.append('_method', 'PUT'); 
        await api.post(url, formData, { headers: { 'Content-Type': 'multipart/form-data' }});
      } else {
        url = '/companies';
        await api.post(url, formData, { headers: { 'Content-Type': 'multipart/form-data' }});
      }
    }
    
    showAddModal.value = false;
    resetForm();
    alert(entityType.value === 'individual' ? "Member berhasil disimpan!" : "Organization berhasil didaftarkan!");
    await fetchData(); 

  } catch (e: any) {
    if (e.response?.status === 422) {
       const errors = e.response.data.errors;
       let errorMessage = "Cek kembali data Anda:\n";
       for (const key in errors) errorMessage += `- ${errors[key][0]}\n`;
       alert(errorMessage);
    } else {
       alert("Sistem Error: " + (e.response?.data?.error || "Koneksi terputus"));
    }
  }
};

const resetForm = () => {
  teamworkForm.value = { 
    name: '', email: '', password: '', role: 'member', position: '', company_id: null, status: 'Active',
    phone: '', hourly_rate: 0,
    org_legal_name: '', org_email: '', org_phone: '', org_address: '', org_description: ''
  };
  selectedOrgLogo.value = null;
  logoPreview.value = null; 
  isEditing.value = false;
  editingId.value = null;
  entityType.value = 'individual';
  selectedAvatar.value = null;
  avatarPreview.value = null;
};

const handleDeleteMember = async (id: number) => {
  if (!confirm("Apakah Anda yakin ingin menghapus personel ini?")) return;
  try {
    await api.delete(`/users/${id}`);
    alert("Personel berhasil dihapus!");
    await fetchData(); 
  } catch (error: any) {
    alert("Gagal menghapus data.");
  }
};

const handleEditMaster = (member: any) => {
  isEditing.value = true;
  editingId.value = member.id;
  entityType.value = 'individual';
  
  teamworkForm.value = {
    name: member.name || '', email: member.email || '', password: '', role: member.role || 'member',
    position: member.position || '', company_id: member.company_id || null, status: member.status || 'Active',
    phone: member.phone || '', hourly_rate: member.hourly_rate || 0,
    org_legal_name: '', org_email: '', org_phone: '', org_address: '', org_description: ''
  };
  
  selectedAvatar.value = null;
  if (member.avatar_url) avatarPreview.value = `http://localhost:8000/uploads/${member.avatar_url}`;
  else avatarPreview.value = null;

  selectedOrgLogo.value = null;
  showAddModal.value = true; 
};

const handleEditCompany = (company: any) => {
  isEditing.value = true;
  editingId.value = company.id;
  entityType.value = 'organization';

  teamworkForm.value = {
    name: company.name || '', email: '', password: '', role: 'member', position: '', company_id: null, status: 'Active', phone: '', hourly_rate: 0,
    org_legal_name: company.legal_name || '', org_email: company.email || '', org_phone: company.phone || '',
    org_address: company.address || '', org_description: company.description || ''
  };
  
  selectedOrgLogo.value = null;
  if (company.logo_path) logoPreview.value = `http://localhost:8000/uploads/${company.logo_path}`;
  else logoPreview.value = null;

  showAddModal.value = true; 
};

onMounted(fetchData);
</script>

<template>
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 animate-in fade-in duration-500 pb-20 mt-4">
    
    <div class="lg:col-span-3 space-y-6">
      <div class="bg-white border border-slate-200 rounded-[2rem] p-6 shadow-sm sticky top-8">
        <h4 class="text-[10px] font-black uppercase text-slate-400 mb-5 tracking-widest ml-2">Navigation</h4>
        <div class="space-y-1.5">
          
          <button @click="setFilter('all', 'all'); openMenu = null" class="w-full flex items-center gap-4 px-4 py-3 rounded-2xl transition-all text-left group border" :class="activeFilter.type === 'all' ? 'bg-indigo-50 border-indigo-100 shadow-sm' : 'hover:bg-slate-50 border-transparent'">
            <div class="w-8 h-8 rounded-xl flex items-center justify-center transition-colors" :class="activeFilter.type === 'all' ? 'bg-indigo-600 text-white shadow-md' : 'bg-slate-100 text-slate-400 group-hover:bg-slate-200 group-hover:text-indigo-500'"><i class="fas fa-layer-group text-xs"></i></div>
            <span class="text-[11px] font-black uppercase tracking-widest transition-colors" :class="activeFilter.type === 'all' ? 'text-indigo-600' : 'text-slate-500 group-hover:text-indigo-600'">All Data</span>
          </button>

          <button @click="setFilter('member', 'all'); openMenu = null" class="w-full flex items-center gap-4 px-4 py-3 rounded-2xl transition-all text-left group border" :class="activeFilter.type === 'member' ? 'bg-blue-50 border-blue-100 shadow-sm' : 'hover:bg-slate-50 border-transparent'">
            <div class="w-8 h-8 rounded-xl flex items-center justify-center transition-colors" :class="activeFilter.type === 'member' ? 'bg-blue-600 text-white shadow-md' : 'bg-slate-100 text-slate-400 group-hover:bg-slate-200 group-hover:text-blue-500'"><i class="fas fa-user-tie text-xs"></i></div>
            <span class="text-[11px] font-black uppercase tracking-widest transition-colors" :class="activeFilter.type === 'member' ? 'text-blue-600' : 'text-slate-500 group-hover:text-blue-600'">Member</span>
          </button>

          <button @click="setFilter('view_companies', 'all'); openMenu = null" class="w-full flex items-center gap-4 px-4 py-3 rounded-2xl transition-all text-left group border" :class="activeFilter.type === 'view_companies' ? 'bg-emerald-50 border-emerald-100 shadow-sm' : 'hover:bg-slate-50 border-transparent'">
            <div class="w-8 h-8 rounded-xl flex items-center justify-center transition-colors" :class="activeFilter.type === 'view_companies' ? 'bg-emerald-600 text-white shadow-md' : 'bg-slate-100 text-slate-400 group-hover:bg-slate-200 group-hover:text-emerald-500'"><i class="fas fa-building text-xs"></i></div>
            <span class="text-[11px] font-black uppercase tracking-widest transition-colors" :class="activeFilter.type === 'view_companies' ? 'text-emerald-600' : 'text-slate-500 group-hover:text-emerald-600'">Organization</span>
          </button>

          <div class="border rounded-2xl transition-all" :class="openMenu === 'role' || activeFilter.type === 'role' ? 'bg-slate-50 border-slate-100 pb-2' : 'border-transparent'">
            <button @click="toggleMenu('role')" class="w-full flex items-center justify-between px-4 py-3 rounded-2xl transition-all text-left group hover:bg-slate-50/80">
              <div class="flex items-center gap-4">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center transition-colors bg-slate-100 text-slate-400 group-hover:bg-slate-200 group-hover:text-amber-500" :class="activeFilter.type === 'role' ? 'bg-amber-100 text-amber-600' : ''"><i class="fas fa-user-shield text-xs"></i></div>
                <span class="text-[11px] font-black uppercase tracking-widest transition-colors text-slate-500 group-hover:text-amber-600" :class="activeFilter.type === 'role' ? 'text-amber-600' : ''">Access Role</span>
              </div>
              <i class="fas fa-chevron-down text-[10px] text-slate-400 transition-transform duration-300" :class="openMenu === 'role' ? 'rotate-180' : ''"></i>
            </button>
            <div v-show="openMenu === 'role'" class="mt-1 px-2 space-y-1 animate-in slide-in-from-top-2 duration-300">
              <button v-for="r in [{id: 'super_admin', label: 'Super Admin'}, {id: 'admin', label: 'Admin'}, {id: 'member', label: 'Member'}]" :key="r.id"
                @click="setFilter('role', r.id)"
                class="w-full flex items-center text-left py-2.5 px-3 text-[10px] font-black uppercase tracking-widest transition-all rounded-xl"
                :class="activeFilter.type === 'role' && activeFilter.value === r.id ? 'text-amber-600 bg-amber-100/50' : 'text-slate-400 hover:text-amber-500 hover:bg-slate-100/50'">
                <span class="opacity-40 font-normal mr-2 text-[11px]">#</span> {{ r.label }}
              </button>
            </div>
          </div>

          <div class="border rounded-2xl transition-all" :class="openMenu === 'tag' || activeFilter.type === 'tag' ? 'bg-slate-50 border-slate-100 pb-2' : 'border-transparent'">
            <button @click="toggleMenu('tag')" class="w-full flex items-center justify-between px-4 py-3 rounded-2xl transition-all text-left group hover:bg-slate-50/80">
              <div class="flex items-center gap-4">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center transition-colors bg-slate-100 text-slate-400 group-hover:bg-slate-200 group-hover:text-rose-500" :class="activeFilter.type === 'tag' ? 'bg-rose-100 text-rose-600' : ''"><i class="fas fa-tags text-xs"></i></div>
                <span class="text-[11px] font-black uppercase tracking-widest transition-colors text-slate-500 group-hover:text-rose-600" :class="activeFilter.type === 'tag' ? 'text-rose-600' : ''">Team Tags</span>
              </div>
              <i class="fas fa-chevron-down text-[10px] text-slate-400 transition-transform duration-300" :class="openMenu === 'tag' ? 'rotate-180' : ''"></i>
            </button>
            <div v-show="openMenu === 'tag'" class="mt-1 px-2 space-y-1 animate-in slide-in-from-top-2 duration-300 max-h-[250px] overflow-y-auto custom-scrollbar">
              <div v-if="teamTags.length === 0" class="text-[9px] font-bold text-slate-400 uppercase tracking-widest py-2 px-3 text-center">Belum ada tag</div>
              <button v-for="tag in teamTags" :key="tag.id"
                @click="setFilter('tag', tag.id)"
                class="w-full flex items-center text-left py-2.5 px-3 text-[10px] font-black uppercase tracking-widest transition-all rounded-xl truncate"
                :class="activeFilter.type === 'tag' && activeFilter.value === tag.id ? 'text-rose-600 bg-rose-100/50' : 'text-slate-400 hover:text-rose-500 hover:bg-slate-100/50'">
                <span class="opacity-40 font-normal mr-2 text-[11px]">#</span> {{ tag.name }}
              </button>
            </div>
          </div>

        </div>
      </div>
    </div>

    <div class="lg:col-span-9 flex flex-col gap-6 min-h-[600px] relative">
      <div v-if="isLoading" class="absolute inset-0 bg-white/50 backdrop-blur-sm z-50 flex items-center justify-center rounded-[3rem]">
        <i class="fas fa-spinner fa-spin text-3xl text-indigo-600"></i>
      </div>

      <div class="bg-white rounded-[2.5rem] p-5 shadow-sm border border-slate-200 flex flex-col xl:flex-row justify-between items-center gap-4 relative z-10">
        
        <div class="flex items-center gap-4 w-full xl:w-auto">
          <div class="bg-slate-50 border border-slate-100 text-indigo-700 text-[10px] font-bold uppercase py-2.5 px-5 rounded-xl flex items-center gap-3 min-w-[160px]">
            <span v-if="activeFilter.type === 'all'"><i class="fas fa-filter mr-2 opacity-50"></i> All Data</span>
            <span v-else-if="activeFilter.type === 'member'">Member</span>
            <span v-else-if="activeFilter.type === 'view_companies'">Organization</span>
            <span v-else>
              {{ activeFilter.type === 'tag' ? 'TAG : ' + (teamTags.find(t => t.id === activeFilter.value)?.name || '') : activeFilter.type + ': ' + activeFilter.value }}
            </span>
            <button v-if="activeFilter.type !== 'all'" @click="setFilter('all', 'all')" class="ml-auto text-rose-400 hover:text-rose-600 transition-colors">
              <i class="fas fa-times-circle text-sm"></i>
            </button>
          </div>
        </div>

        <div class="flex flex-col sm:flex-row items-center gap-3 w-full xl:w-auto">
          <div class="relative w-full sm:w-64">
            <input v-model="searchQuery" @keyup.enter="handleSearch" type="text" placeholder="Cari Personil / PT..." class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-2.5 text-[10px] font-bold outline-none focus:ring-2 ring-indigo-100 transition-all uppercase pl-9 text-slate-700">
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
            <button v-if="searchQuery" @click="searchQuery = ''; handleSearch()" class="absolute right-3 top-1/2 -translate-y-1/2 text-rose-400 hover:text-rose-600">
              <i class="fas fa-times"></i>
            </button>
          </div>

          <button @click="resetForm(); showAddModal = true" class="w-full sm:w-auto bg-indigo-600 text-white px-6 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest shadow-md hover:bg-indigo-700 transition-all flex items-center justify-center gap-2 whitespace-nowrap">
            <i class="fas fa-plus"></i> Add Entity
          </button>
        </div>
      </div>

      <div class="bg-white rounded-[3rem] border border-slate-200 shadow-sm flex-1 overflow-hidden flex flex-col p-6 lg:p-8">
        <div class="flex-1 overflow-y-auto custom-scrollbar pr-2 pb-4 space-y-8">
          
          <div v-if="showOrgsSection">
            <div class="flex justify-between items-center mb-4 ml-2">
               <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest flex items-center gap-2">
                 <i class="fas fa-building text-emerald-500"></i> Organization
               </h3>
               <span class="text-[9px] font-bold text-slate-400 bg-slate-50 px-3 py-1 rounded border border-slate-100">Total: {{ filteredOrganizations.length }} Data</span>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 items-start content-start">
              <div v-for="cp in paginatedOrganizations" :key="cp.id" 
                class="relative bg-white rounded-3xl p-6 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)] border border-slate-100 hover:shadow-[0_8px_30px_rgb(0,0,0,0.06)] hover:-translate-y-1 hover:border-indigo-200 transition-all duration-300 group overflow-hidden flex flex-col justify-between h-[210px]">
                
                <div class="absolute -right-6 -top-6 w-24 h-24 bg-gradient-to-br from-indigo-50 to-white rounded-full opacity-50 group-hover:scale-150 transition-transform duration-700 ease-out z-0"></div>

                <div class="relative z-10 flex flex-col h-full">
                  <div class="flex items-center gap-4 mb-4">
                    <div class="w-14 h-14 shrink-0 rounded-2xl bg-indigo-50 flex items-center justify-center text-indigo-600 text-xl font-black shadow-inner border border-indigo-100 group-hover:bg-indigo-600 group-hover:text-white transition-colors overflow-hidden p-1">
                      <img v-if="cp.logo_path" :src="`http://localhost:8000/uploads/${cp.logo_path}`" class="w-full h-full object-contain rounded-xl">
                      <i v-else class="fas fa-building"></i>
                    </div>
                    <div class="flex-1 min-w-0 overflow-hidden">
                      <h3 class="text-[13px] font-black text-slate-800 uppercase tracking-tight truncate group-hover:text-indigo-600 transition-colors">{{ cp.name }}</h3>
                      <div class="mt-1">
                        <span class="text-[9px] font-bold px-2 py-0.5 bg-slate-100 text-slate-500 rounded-md uppercase tracking-widest truncate inline-block max-w-full">{{ cp.legal_name || 'Registered Entity' }}</span>
                      </div>
                    </div>
                  </div>

                  <div class="w-full h-px bg-slate-100 mb-3 group-hover:bg-indigo-50 transition-colors"></div>

                  <div class="space-y-2.5 flex-grow">
                    <div class="flex justify-between items-center">
                      <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest">Email</span>
                      <span class="text-[9px] font-bold text-slate-500 lowercase truncate max-w-[140px]" :title="cp.email">{{ cp.email || '-' }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                      <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest">Phone</span>
                      <span class="text-[9px] font-bold text-slate-500 truncate max-w-[140px]">{{ cp.phone || '-' }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                      <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest">Debt / Kasbon</span>
                      <span class="text-[10px] font-black italic text-emerald-500">{{ formatCurrency(0) }}</span>
                    </div>
                  </div>
                </div>

                <div class="absolute inset-x-0 bottom-0 p-6 bg-gradient-to-t from-white via-white to-transparent translate-y-[120%] group-hover:translate-y-0 transition-transform duration-300 ease-out flex justify-center gap-3 z-20 rounded-b-[2.5rem]">
                  <button @click="handleEditCompany(cp)" class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white transition-all shadow-sm flex items-center justify-center">
                      <i class="fas fa-edit text-[11px]"></i>
                  </button>
                  <button @click="handleDeleteCompany(cp.id)" class="w-10 h-10 rounded-xl bg-rose-50 text-rose-500 hover:bg-rose-600 hover:text-white transition-all shadow-sm flex items-center justify-center">
                      <i class="fas fa-trash text-[11px]"></i>
                  </button>
                </div>
              </div>
              
              <div v-if="paginatedOrganizations.length === 0" class="col-span-full py-10 text-center opacity-40 border-2 border-dashed border-slate-200 rounded-[2rem]">
                  <i class="fas fa-search text-3xl mb-3 text-slate-300"></i>
                  <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">No Organization found</p>
              </div>
            </div>

            <div v-if="orgTotalPages > 1" class="mt-4 pt-4 border-t border-slate-100 flex flex-col md:flex-row justify-between items-center gap-4">
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Page <span class="text-indigo-600">{{ orgCurrentPage }}</span> of <span class="text-indigo-600">{{ orgTotalPages }}</span></p>
                <div class="flex items-center gap-2">
                    <button @click="prevOrgPage" :disabled="orgCurrentPage === 1" class="w-7 h-7 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-500 hover:bg-indigo-50 disabled:opacity-30 disabled:cursor-not-allowed shadow-sm">
                        <i class="fas fa-chevron-left text-[10px]"></i>
                    </button>
                    <div class="flex items-center gap-1">
                        <button v-for="page in orgTotalPages" :key="page" @click="goToOrgPage(page)" class="w-7 h-7 flex items-center justify-center rounded-xl text-[10px] font-black transition-all shadow-sm" :class="orgCurrentPage === page ? 'bg-indigo-600 text-white' : 'bg-white border border-slate-200 text-slate-500 hover:bg-slate-50'">{{ page }}</button>
                    </div>
                    <button @click="nextOrgPage" :disabled="orgCurrentPage === orgTotalPages" class="w-7 h-7 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-500 hover:bg-indigo-50 disabled:opacity-30 disabled:cursor-not-allowed shadow-sm">
                        <i class="fas fa-chevron-right text-[10px]"></i>
                    </button>
                </div>
            </div>
          </div>

          <div v-if="showMembersSection">
            <div class="flex justify-between items-center mb-4 ml-2 mt-2">
               <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest flex items-center gap-2">
                 <i class="fas fa-user-tie text-blue-500"></i> Member
               </h3>
               <span class="text-[9px] font-bold text-slate-400 bg-slate-50 px-3 py-1 rounded border border-slate-100">Total: {{ filteredMembers.length }} Data</span>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 items-start content-start">
              <div v-for="m in filteredMembers" :key="m.id" 
                class="relative bg-white rounded-3xl p-6 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)] border border-slate-100 hover:shadow-[0_8px_30px_rgb(0,0,0,0.06)] hover:-translate-y-1 hover:border-indigo-200 transition-all duration-300 group overflow-hidden flex flex-col justify-between h-[210px]">
                
                <div class="absolute -right-6 -top-6 w-24 h-24 bg-gradient-to-br from-indigo-50 to-white rounded-full opacity-50 group-hover:scale-150 transition-transform duration-700 ease-out z-0"></div>
                
                <div class="relative z-10 flex flex-col h-full">
                  <div class="flex items-center gap-4 mb-4">
                    <div class="w-14 h-14 shrink-0 rounded-2xl bg-indigo-50 flex items-center justify-center text-indigo-600 text-lg font-black shadow-inner uppercase border border-indigo-100 group-hover:bg-indigo-600 group-hover:text-white transition-colors overflow-hidden">
                      <img v-if="m.avatar_url" :src="`http://localhost:8000/uploads/${m.avatar_url}`" class="w-full h-full object-cover">
                      <span v-else>{{ m.name ? m.name.substring(0,2) : 'NN' }}</span>
                    </div>
                    <div class="flex-1 min-w-0 overflow-hidden">
                      <h5 class="text-[13px] font-black text-slate-800 uppercase tracking-tight truncate group-hover:text-indigo-600 transition-colors">{{ m.name }}</h5>
                      <div class="mt-1">
                        <span class="text-[9px] font-bold px-2 py-0.5 bg-slate-100 text-slate-500 rounded-md uppercase tracking-widest truncate inline-block max-w-full">{{ m.role || m.position || 'Staff' }}</span>
                      </div>
                    </div>
                  </div>

                  <div class="w-full h-px bg-slate-100 mb-3 group-hover:bg-indigo-50 transition-colors"></div>

                  <div class="space-y-2.5 flex-grow">
                    <div class="flex justify-between items-center">
                      <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest">Affiliation</span>
                      <span class="text-[9px] font-black text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded border border-indigo-100 uppercase truncate max-w-[120px]">
                        {{ m.company_id ? (allCompanies.find(c => c.id === m.company_id)?.name || 'Linked PT') : 'Independent' }}
                      </span>
                    </div>
                    <div class="flex justify-between items-center">
                      <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest">Email</span>
                      <span class="text-[9px] font-bold text-slate-500 lowercase truncate max-w-[140px]" :title="m.email">
                        {{ m.email || 'no-email@system.com' }}
                      </span>
                    </div>
                    <div class="flex justify-between items-center">
                      <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest">Debt / Kasbon</span>
                      <span class="text-[10px] font-black italic text-emerald-500">{{ formatCurrency(0) }}</span>
                    </div>
                  </div>
                </div>

                <div class="absolute inset-x-0 bottom-0 p-6 bg-gradient-to-t from-white via-white to-transparent translate-y-[120%] group-hover:translate-y-0 transition-transform duration-300 ease-out flex justify-center gap-3 z-20 rounded-b-[2.5rem]">
                  <button @click="handleEditMaster(m)" class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white transition-all shadow-sm flex items-center justify-center">
                      <i class="fas fa-edit text-[11px]"></i>
                  </button>
                  <button @click="handleDeleteMember(m.id)" class="w-10 h-10 rounded-xl bg-rose-50 text-rose-500 hover:bg-rose-600 hover:text-white transition-all shadow-sm flex items-center justify-center">
                      <i class="fas fa-trash text-[11px]"></i>
                  </button>
                </div>
              </div>

              <div v-if="filteredMembers.length === 0" class="col-span-full py-16 text-center opacity-40 border-2 border-dashed border-slate-200 rounded-[2rem]">
                  <i class="fas fa-search text-3xl mb-3 text-slate-300"></i>
                  <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">No personnel found</p>
              </div>
            </div>

            <div v-if="totalPages > 1" class="mt-4 pt-4 border-t border-slate-100 flex flex-col md:flex-row justify-between items-center gap-4">
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Page <span class="text-indigo-600">{{ currentPage }}</span> of <span class="text-indigo-600">{{ totalPages }}</span></p>
                <div class="flex items-center gap-2">
                    <button @click="prevPage" :disabled="currentPage === 1" class="w-7 h-7 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-500 hover:bg-indigo-50 disabled:opacity-30 disabled:cursor-not-allowed shadow-sm">
                        <i class="fas fa-chevron-left text-[10px]"></i>
                    </button>
                    <div class="flex items-center gap-1">
                        <button v-for="page in totalPages" :key="page" @click="goToPage(page)" class="w-7 h-7 flex items-center justify-center rounded-xl text-[10px] font-black transition-all shadow-sm" :class="currentPage === page ? 'bg-indigo-600 text-white' : 'bg-white border border-slate-200 text-slate-500 hover:bg-slate-50'">{{ page }}</button>
                    </div>
                    <button @click="nextPage" :disabled="currentPage === totalPages" class="w-7 h-7 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-500 hover:bg-indigo-50 disabled:opacity-30 disabled:cursor-not-allowed shadow-sm">
                        <i class="fas fa-chevron-right text-[10px]"></i>
                    </button>
                </div>
            </div>

          </div>

        </div>
      </div>
    </div>

    <div v-if="showAddModal" class="fixed inset-0 z-[999] flex items-center justify-center p-6">
      <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showAddModal = false"></div>
      <div class="bg-white w-full max-w-lg rounded-[2.5rem] shadow-2xl relative z-10 overflow-hidden animate-in zoom-in duration-300">
        <div class="p-8 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
          <div>
            <h3 class="text-xl font-black text-slate-800 uppercase italic">
              {{ isEditing ? 'Edit Entity' : 'Add New Entity' }}
            </h3>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Register Team or Organization</p>
          </div>
          <button @click="showAddModal = false" class="text-slate-300 hover:text-rose-500 transition-colors">
            <i class="fas fa-times-circle text-2xl"></i>
          </button>
        </div>

        <div class="p-8 space-y-5 max-h-[70vh] overflow-y-auto custom-scrollbar">
          <div class="flex bg-slate-100 p-1 rounded-2xl">
            <button v-for="t in ['individual', 'organization']" :key="t"
              @click="entityType = t"
              class="flex-1 py-2 text-[10px] font-black uppercase tracking-widest rounded-xl transition-all"
              :class="entityType === t ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-400'">
              {{ t }}
            </button>
          </div>

          <div class="space-y-4">
            <template v-if="entityType === 'individual'">
                <div class="space-y-1">
                  <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Full Name</label>
                  <input v-model="teamworkForm.name" type="text" placeholder="E.G. JOHN DOE" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-3 text-xs font-bold outline-none focus:ring-2 ring-indigo-100 transition-all uppercase">
                </div>
                <div class="space-y-1">
                  <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Email Address</label>
                  <input v-model="teamworkForm.email" type="email" placeholder="JOHN@EXAMPLE.COM" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-3 text-xs font-bold outline-none uppercase">
                </div>
                <div class="grid grid-cols-2 gap-4 mt-4">
                  <div class="space-y-1">
                    <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Phone Number</label>
                    <input v-model="teamworkForm.phone" type="text" placeholder="E.G. +6281234..." class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-3 text-xs font-bold outline-none focus:ring-2 ring-indigo-100 uppercase">
                  </div>
                  <div class="space-y-1">
                    <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Hourly Rate (IDR)</label>
                    <input v-model="teamworkForm.hourly_rate" type="number" placeholder="E.G. 150000" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-3 text-xs font-bold outline-none focus:ring-2 ring-indigo-100">
                  </div>
                </div>
                <div class="grid grid-cols-2 gap-4 mt-4">
                  <div class="space-y-1">
                    <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Job Role / Position</label>
                    <input v-model="teamworkForm.position" type="text" placeholder="E.G. DEVELOPER" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-3 text-xs font-bold outline-none uppercase">
                  </div>
                  <div class="space-y-1">
                    <label class="text-[9px] font-black text-slate-400 uppercase ml-1">System Access Level</label>
                    <select v-model="teamworkForm.role" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-3 text-xs font-bold outline-none appearance-none cursor-pointer">
                      <option value="member">Member</option>
                      <option value="admin">Admin</option>
                      <option value="super_admin">Super Admin</option>
                    </select>
                  </div>
                </div>
                <div class="space-y-1 mt-4">
                    <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Affiliated PT</label>
                    <select v-model="teamworkForm.company_id" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-3 text-xs font-bold outline-none appearance-none cursor-pointer">
                      <option :value="null">-- INDEPENDENT / NO PT --</option>
                      <option v-for="cp in allCompanies" :key="cp.id" :value="cp.id">{{ cp.name }}</option>
                    </select>
                </div>
                <div class="space-y-1 mt-4">
                  <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Profile Avatar</label>
                  <div class="relative w-full h-[70px] border-2 border-dashed border-slate-200 rounded-2xl flex flex-col items-center justify-center text-slate-400 hover:bg-slate-50 hover:border-indigo-300 transition-all cursor-pointer overflow-hidden group">
                    <input type="file" @change="handleAvatarChange" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-20" accept="image/png, image/jpeg, image/svg+xml">
                    <img v-if="avatarPreview" :src="avatarPreview" class="absolute inset-0 w-full h-full object-contain p-1 opacity-40 group-hover:opacity-20 transition-opacity z-0" />
                    <i v-if="!avatarPreview" class="fas fa-user-circle text-xl mb-1" :class="selectedAvatar ? 'text-indigo-500' : ''"></i>
                    <span class="text-[9px] font-black uppercase tracking-widest text-center px-4 truncate relative z-10 transition-all" 
                          :class="(selectedAvatar || avatarPreview) ? 'text-indigo-600 bg-white/90 px-3 py-1 rounded-lg shadow-sm' : ''">
                      {{ selectedAvatar ? selectedAvatar.name : (avatarPreview ? 'Change Avatar' : 'Click to Upload Avatar') }}
                    </span>
                  </div>
                </div>
            </template>

            <template v-else>
                <div class="space-y-1">
                  <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Company / Entity Name</label>
                  <input v-model="teamworkForm.name" type="text" placeholder="E.G. PT. MAJU MUNDUR" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-3 text-xs font-bold outline-none focus:ring-2 ring-indigo-100 transition-all uppercase">
                </div>
                
                <div class="space-y-1">
                  <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Legal Registration Name</label>
                  <input v-model="teamworkForm.org_legal_name" type="text" placeholder="E.G. PT. MAJU MUNDUR SEJAHTERA" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-3 text-xs font-bold outline-none uppercase">
                </div>

                <div class="grid grid-cols-2 gap-4 mt-4">
                  <div class="space-y-1">
                    <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Company Email</label>
                    <input v-model="teamworkForm.org_email" type="email" placeholder="CONTACT@COMPANY.COM" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-3 text-xs font-bold outline-none uppercase">
                  </div>
                  <div class="space-y-1">
                    <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Office Phone Number</label>
                    <input v-model="teamworkForm.org_phone" type="text" placeholder="E.G. 021-1234567" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-3 text-xs font-bold outline-none uppercase">
                  </div>
                </div>

                <div class="space-y-1 mt-4">
                  <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Company Address</label>
                  <textarea v-model="teamworkForm.org_address" rows="2" placeholder="FULL COMPANY ADDRESS..." class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-3 text-xs font-bold outline-none uppercase resize-none"></textarea>
                </div>

                <div class="space-y-1 mt-4">
                  <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Business Activity / Description</label>
                  <textarea v-model="teamworkForm.org_description" rows="2" placeholder="BRIEF DESCRIPTION OF BUSINESS OR SERVICES..." class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-3 text-xs font-bold outline-none uppercase resize-none"></textarea>
                </div>

                <div class="space-y-1 mt-4">
                  <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Company Logo</label>
                  <div class="relative w-full h-[70px] border-2 border-dashed border-slate-200 rounded-2xl flex flex-col items-center justify-center text-slate-400 hover:bg-slate-50 hover:border-indigo-300 transition-all cursor-pointer overflow-hidden group">
                    <input type="file" @change="handleOrgLogoChange" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-20" accept="image/png, image/jpeg, image/svg+xml">
                    <img v-if="logoPreview" :src="logoPreview" class="absolute inset-0 w-full h-full object-contain p-1 opacity-40 group-hover:opacity-20 transition-opacity z-0" />
                    <i v-if="!logoPreview" class="fas fa-image text-xl mb-1" :class="selectedOrgLogo ? 'text-indigo-500' : ''"></i>
                    <span class="text-[9px] font-black uppercase tracking-widest text-center px-4 truncate relative z-10 transition-all" 
                          :class="(selectedOrgLogo || logoPreview) ? 'text-indigo-600 bg-white/90 px-3 py-1 rounded-lg shadow-sm' : ''">
                      {{ selectedOrgLogo ? selectedOrgLogo.name : (logoPreview ? 'Change Logo' : 'Click to Upload Logo') }}
                    </span>
                  </div>
                </div>

                <div class="p-4 bg-amber-50 rounded-2xl border border-amber-100 mt-4">
                    <p class="text-[10px] text-amber-700 font-medium">
                        <i class="fas fa-info-circle mr-1"></i> Registering a new organization will make it available in the "Affiliated PT" dropdown for personnel assignment.
                    </p>
                </div>
            </template>
          </div>

          <div class="pt-4">
            <button @click="handleSave" class="w-full bg-indigo-600 text-white py-4 rounded-2xl text-[11px] font-black uppercase tracking-[0.2em] shadow-lg shadow-indigo-100 hover:bg-indigo-700 active:scale-95 transition-all">
              Confirm & Register Entity
            </button>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #e2e8f0; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background-color: #cbd5e1; }
.animate-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
</style>