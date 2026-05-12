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
  facebook: string;
  twitter: string;
  instagram: string;
  linkedin: string;
  cover_url: string; 
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

// Upload States
const selectedOrgLogo = ref<File | null>(null); 
const logoPreview = ref<string | null>(null);
const selectedAvatar = ref<File | null>(null);
const avatarPreview = ref<string | null>(null);
const selectedCover = ref<File | null>(null);
const coverPreview = ref<string | null>(null);

// Preset Covers yang Disediakan
const presetCovers = [
  'https://images.unsplash.com/photo-1542224566-6e85f2e6772f?q=80&w=500', 
  'https://images.unsplash.com/photo-1579546929518-9e396f3cc809?q=80&w=500', 
  'https://images.unsplash.com/photo-1557683316-973673baf926?q=80&w=500', 
  'https://images.unsplash.com/photo-1506744626753-1fa28f67ea66?q=80&w=500'  
];

const teamworkForm = ref<TeamworkForm>({
  name: '', email: '', password: '', role: 'member', position: '', company_id: null, status: 'Active',
  phone: '', hourly_rate: 0,
  org_legal_name: '', org_email: '', org_phone: '', org_address: '', org_description: '',
  facebook: '', twitter: '', instagram: '', linkedin: '', cover_url: ''
});

// ==========================================
// 2. HELPER & FORMATTING
// ==========================================
const getImageUrl = (path: string | null): string | undefined => {
  if (!path) return undefined;
  if (path.startsWith('data:') || path.startsWith('http')) return path;
  const apiUrl = import.meta.env.VITE_API_URL || 'http://localhost:8000'; 
  const baseUrl = apiUrl.replace('/api', '');
  let cleanPath = path.startsWith('/') ? path.substring(1) : path;
  return cleanPath.toLowerCase().startsWith('uploads/') ? `${baseUrl}/${cleanPath}` : `${baseUrl}/uploads/${cleanPath}`;
};

// ==========================================
// 3. FILTERING, SEARCHING & PAGINATION STATE
// ==========================================
const activeFilter = ref({ type: 'all', value: null as any });
const openMenu = ref<string | null>('role'); 
const searchQuery = ref('');

const currentPage = ref(1);
const totalPages = ref(1);
const orgCurrentPage = ref(1);
const orgItemsPerPage = 6;

const showMembersSection = computed(() => ['all', 'member', 'role', 'tag'].includes(activeFilter.value.type));
const showOrgsSection = computed(() => ['all', 'view_companies', 'tag'].includes(activeFilter.value.type));

// ==========================================
// 4. API ACTIONS
// ==========================================
const fetchData = async () => {
  isLoading.value = true;
  try {
    let params: any = { page: currentPage.value };

    if (activeFilter.value.type === 'tag') {
      const selectedTag = teamTags.value.find(t => t.id === activeFilter.value.value);
      if (selectedTag) params.tag_search = selectedTag.name;
    } else if (activeFilter.value.type === 'role') {
      params.tag_search = activeFilter.value.value; 
    }

    if (searchQuery.value) params.search = searchQuery.value;

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
  } catch (e) { console.error("Gagal memuat data", e); } 
  finally { isLoading.value = false; }
};

const handleSearch = () => { currentPage.value = 1; orgCurrentPage.value = 1; fetchData(); };
const setFilter = (type: string, value: any) => { activeFilter.value = { type, value }; currentPage.value = 1; orgCurrentPage.value = 1; fetchData(); };
const toggleMenu = (menuName: string) => { openMenu.value = openMenu.value === menuName ? null : menuName; };

const nextPage = () => { if (currentPage.value < totalPages.value) { currentPage.value++; fetchData(); } };
const prevPage = () => { if (currentPage.value > 1) { currentPage.value--; fetchData(); } };
const goToPage = (page: number) => { currentPage.value = page; fetchData(); };

const filteredMembers = computed(() => {
  if (!searchQuery.value) return teamData.value;
  const q = searchQuery.value.toLowerCase();
  return teamData.value.filter(m => 
    (m.name && m.name.toLowerCase().includes(q)) || (m.email && m.email.toLowerCase().includes(q)) ||
    (m.role && m.role.toLowerCase().includes(q)) || (m.position && m.position.toLowerCase().includes(q))
  );
});

const filteredOrganizations = computed(() => {
  if (!searchQuery.value) return allCompanies.value;
  const q = searchQuery.value.toLowerCase();
  return allCompanies.value.filter(c => 
    (c.name && c.name.toLowerCase().includes(q)) || (c.legal_name && c.legal_name.toLowerCase().includes(q)) ||
    (c.email && c.email.toLowerCase().includes(q))
  );
});

const orgTotalPages = computed(() => Math.ceil(filteredOrganizations.value.length / orgItemsPerPage) || 1);
const paginatedOrganizations = computed(() => filteredOrganizations.value.slice((orgCurrentPage.value - 1) * orgItemsPerPage, orgCurrentPage.value * orgItemsPerPage));
const nextOrgPage = () => { if (orgCurrentPage.value < orgTotalPages.value) orgCurrentPage.value++; };
const prevOrgPage = () => { if (orgCurrentPage.value > 1) orgCurrentPage.value--; };
const goToOrgPage = (page: number) => { orgCurrentPage.value = page; };

// ==========================================
// 5. CRUD OPERATIONS
// ==========================================
const handleAvatarChange = (e: Event) => {
  const file = (e.target as HTMLInputElement).files?.[0];
  if (file) { selectedAvatar.value = file; avatarPreview.value = URL.createObjectURL(file); }
};

const handleOrgLogoChange = (e: Event) => {
  const file = (e.target as HTMLInputElement).files?.[0];
  if (file) { selectedOrgLogo.value = file; logoPreview.value = URL.createObjectURL(file); }
};

const handleCoverChange = (e: Event) => {
  const file = (e.target as HTMLInputElement).files?.[0];
  if (file) { 
    selectedCover.value = file; 
    coverPreview.value = URL.createObjectURL(file); 
    teamworkForm.value.cover_url = ''; 
  }
};

const selectPresetCover = (url: string) => {
  teamworkForm.value.cover_url = url;
  selectedCover.value = null;
  coverPreview.value = null;
};

const appendCommonFormData = (formData: FormData) => {
  formData.append('facebook', teamworkForm.value.facebook || '');
  formData.append('twitter', teamworkForm.value.twitter || '');
  formData.append('instagram', teamworkForm.value.instagram || '');
  formData.append('linkedin', teamworkForm.value.linkedin || '');
  formData.append('cover_url', teamworkForm.value.cover_url || '');
  if (selectedCover.value) formData.append('cover_image', selectedCover.value);
};

const handleSave = async () => {
  try {
    let url = entityType.value === 'individual' ? '/users/register' : '/companies'; 
    let formData = new FormData();

    if (entityType.value === 'individual') {
      if (!teamworkForm.value.name || !teamworkForm.value.email) return alert("Nama dan Email wajib diisi.");
      
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
      }
    } else {
      const companyName = teamworkForm.value.org_legal_name || teamworkForm.value.name;
      if (!companyName) return alert("Nama Perusahaan (Legal Name) wajib diisi.");

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
      }
    }
    
    appendCommonFormData(formData);

    await api.post(url, formData, { headers: { 'Content-Type': 'multipart/form-data' }});
    showAddModal.value = false;
    resetForm();
    alert(entityType.value === 'individual' ? "Member berhasil disimpan!" : "Organization berhasil disimpan!");
    await fetchData(); 
  } catch (e: any) { alert("Sistem Error: " + (e.response?.data?.error || "Koneksi terputus")); }
};

const resetForm = () => {
  teamworkForm.value = { 
    name: '', email: '', password: '', role: 'member', position: '', company_id: null, status: 'Active', phone: '', hourly_rate: 0,
    org_legal_name: '', org_email: '', org_phone: '', org_address: '', org_description: '',
    facebook: '', twitter: '', instagram: '', linkedin: '', cover_url: ''
  };
  selectedOrgLogo.value = null; logoPreview.value = null; 
  selectedAvatar.value = null; avatarPreview.value = null;
  selectedCover.value = null; coverPreview.value = null;
  isEditing.value = false; editingId.value = null;
  entityType.value = 'individual';
};

const handleDeleteMember = async (id: number) => {
  if (!confirm("Apakah Anda yakin ingin menghapus personel ini?")) return;
  try { await api.delete(`/users/${id}`); alert("Personel berhasil dihapus!"); await fetchData(); } catch (error: any) { alert("Gagal menghapus data."); }
};

const handleDeleteCompany = async (id: number) => {
  if (!confirm("Apakah Anda yakin ingin menghapus Organization ini?")) return;
  try { await api.delete(`/companies/${id}`); alert("Organization berhasil dihapus!"); await fetchData(); } catch (error: any) { alert("Gagal menghapus data."); }
};

const handleEditMaster = (member: any) => {
  isEditing.value = true; editingId.value = member.id; entityType.value = 'individual';
  teamworkForm.value = {
    ...teamworkForm.value,
    name: member.name || '', email: member.email || '', role: member.role || 'member',
    position: member.position || '', company_id: member.company_id || null, status: member.status || 'Active',
    phone: member.phone || '', hourly_rate: member.hourly_rate || 0,
    facebook: member.facebook || '', twitter: member.twitter || '', instagram: member.instagram || '', linkedin: member.linkedin || '',
    cover_url: member.cover_url || ''
  };
  
  selectedAvatar.value = null; selectedCover.value = null; coverPreview.value = null;
  avatarPreview.value = member.avatar_url ? getImageUrl(member.avatar_url) || null : null;
  if(member.cover_image) coverPreview.value = getImageUrl(member.cover_image) || null;
  
  showAddModal.value = true; 
};

const handleEditCompany = (company: any) => {
  isEditing.value = true; editingId.value = company.id; entityType.value = 'organization';
  teamworkForm.value = {
    ...teamworkForm.value,
    name: company.name || '', org_legal_name: company.legal_name || '', org_email: company.email || '', org_phone: company.phone || '',
    org_address: company.address || '', org_description: company.description || '',
    facebook: company.facebook || '', twitter: company.twitter || '', instagram: company.instagram || '', linkedin: company.linkedin || '',
    cover_url: company.cover_url || ''
  };
  
  selectedOrgLogo.value = null; selectedCover.value = null; coverPreview.value = null;
  logoPreview.value = company.logo_path ? getImageUrl(company.logo_path) || null : null;
  if(company.cover_image) coverPreview.value = getImageUrl(company.cover_image) || null;
  
  showAddModal.value = true; 
};

onMounted(fetchData);
</script>

<template>
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 animate-in fade-in duration-500 pb-20 mt-4">
    
    <div class="lg:col-span-3 space-y-6">
      <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-sm sticky top-8">
        <h4 class="text-[13px] font-bold text-center mb-3 pb-3 border-b border-gray-100">Navigation</h4>
        <div class="space-y-1">
          <button @click="setFilter('all', 'all'); openMenu = null" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all text-left text-[12px] font-medium" :class="activeFilter.type === 'all' ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50'"><i class="fas fa-layer-group text-gray-400"></i> All Data</button>
          <button @click="setFilter('member', 'all'); openMenu = null" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all text-left text-[12px] font-medium" :class="activeFilter.type === 'member' ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50'"><i class="fas fa-user-tie text-gray-400"></i> Member</button>
          <button @click="setFilter('view_companies', 'all'); openMenu = null" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all text-left text-[12px] font-medium" :class="activeFilter.type === 'view_companies' ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50'"><i class="fas fa-building text-gray-400"></i> Organization</button>

          <div class="rounded-lg transition-all" :class="openMenu === 'role' || activeFilter.type === 'role' ? 'bg-gray-50' : ''">
            <button @click="toggleMenu('role')" class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg transition-all text-left text-[12px] font-medium text-gray-600 hover:bg-gray-50"><div class="flex items-center gap-3"><i class="fas fa-folder text-gray-400"></i> Access Role</div><i class="fas fa-chevron-down text-[10px] text-gray-400 transition-transform duration-300" :class="openMenu === 'role' ? 'rotate-180' : ''"></i></button>
            <div v-show="openMenu === 'role'" class="mt-1 px-2 pb-2 space-y-1"><button v-for="r in [{id: 'super_admin', label: 'Super Admin'}, {id: 'admin', label: 'Admin'}, {id: 'member', label: 'Member'}]" :key="r.id" @click="setFilter('role', r.id)" class="w-full text-left py-2 px-8 text-[11px] font-medium rounded-md transition-all" :class="activeFilter.type === 'role' && activeFilter.value === r.id ? 'text-blue-600 bg-blue-100/50' : 'text-gray-500 hover:text-blue-500 hover:bg-gray-100/50'">{{ r.label }}</button></div>
          </div>

          <div class="border-t border-gray-100 my-2"></div>

          <div class="rounded-lg transition-all" :class="openMenu === 'tag' || activeFilter.type === 'tag' ? 'bg-gray-50' : ''">
            <button @click="toggleMenu('tag')" class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg transition-all text-left text-[12px] font-medium text-gray-600 hover:bg-gray-50"><div class="flex items-center gap-3"><i class="fas fa-folder text-gray-400"></i> Team Tags</div><i class="fas fa-chevron-down text-[10px] text-gray-400 transition-transform duration-300" :class="openMenu === 'tag' ? 'rotate-180' : ''"></i></button>
            <div v-show="openMenu === 'tag'" class="mt-1 px-2 pb-2 space-y-1 max-h-[250px] overflow-y-auto custom-scrollbar"><div v-if="teamTags.length === 0" class="text-[10px] italic text-gray-400 py-1 px-4">Belum ada tag</div><button v-for="tag in teamTags" :key="tag.id" @click="setFilter('tag', tag.id)" class="w-full text-left py-2 px-8 text-[11px] font-medium rounded-md transition-all truncate" :class="activeFilter.type === 'tag' && activeFilter.value === tag.id ? 'text-blue-600 bg-blue-100/50' : 'text-gray-500 hover:text-blue-500 hover:bg-gray-100/50'">{{ tag.name }}</button></div>
          </div>
        </div>
      </div>
    </div>

    <div class="lg:col-span-9 flex flex-col gap-4 min-h-[600px] relative">
      <div v-if="isLoading" class="absolute inset-0 bg-white/50 backdrop-blur-sm z-50 flex items-center justify-center rounded-xl"><i class="fas fa-spinner fa-spin text-3xl text-blue-600"></i></div>

      <div class="flex flex-col xl:flex-row gap-3 bg-white p-3 border border-gray-200 rounded-lg shadow-sm relative z-10">
        <button class="bg-[#4361EE] text-white px-5 py-2.5 rounded text-sm font-bold flex items-center gap-2 shadow-sm shrink-0 whitespace-nowrap"><i class="fas fa-home"></i> Teamwork</button>
        <div class="flex-1 relative"><i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i><input v-model="searchQuery" @keyup.enter="handleSearch" type="text" placeholder="Search..." class="w-full bg-white border border-gray-300 rounded pl-10 pr-4 py-2.5 text-sm outline-none focus:border-[#4361EE] transition-colors"><button v-if="searchQuery" @click="searchQuery = ''; handleSearch()" class="absolute right-3 top-1/2 -translate-y-1/2 text-red-400 hover:text-red-600"><i class="fas fa-times"></i></button></div>
        <button class="bg-white border border-gray-300 text-[#4361EE] px-5 py-2.5 rounded text-sm font-semibold flex items-center gap-2 hover:bg-gray-50 shrink-0"><i class="fas fa-filter"></i> Filter</button>
        <div class="flex gap-2 shrink-0"><button class="bg-[#4361EE] text-white w-10 h-10 rounded flex items-center justify-center shadow-sm"><i class="fas fa-bars"></i></button><button class="bg-[#4361EE] text-white w-10 h-10 rounded flex items-center justify-center shadow-sm opacity-60 hover:opacity-100"><i class="fas fa-th-large"></i></button><button @click="resetForm(); showAddModal = true" class="bg-[#4361EE] text-white w-10 h-10 rounded flex items-center justify-center shadow-sm font-bold text-lg"><i class="fas fa-plus"></i></button></div>
      </div>

      <div class="flex-1 flex flex-col pt-2">
        <div class="flex-1 space-y-8">
          
          <div v-if="showOrgsSection && paginatedOrganizations.length > 0">
            <div :class="paginatedOrganizations.length < 3 ? 'flex flex-wrap justify-center gap-6' : 'grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 items-start content-start'">
              
              <div v-for="cp in paginatedOrganizations" :key="cp.id" 
                   :class="paginatedOrganizations.length < 3 ? 'w-full sm:w-[320px]' : ''" 
                   class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden flex flex-col relative group">
                
                <div class="absolute top-2 right-2 flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity z-20">
                    <button @click="handleEditCompany(cp)" class="w-6 h-6 bg-white/90 rounded shadow flex items-center justify-center text-[#4361EE] hover:bg-white"><i class="fas fa-edit text-[10px]"></i></button>
                    <button @click="handleDeleteCompany(cp.id)" class="w-6 h-6 bg-white/90 rounded shadow flex items-center justify-center text-red-500 hover:bg-white"><i class="fas fa-trash text-[10px]"></i></button>
                </div>

                <div class="h-20 w-full bg-slate-100 relative">
                  <div class="absolute inset-0 bg-cover bg-center" :style="{ backgroundImage: `url(${cp.cover_image ? getImageUrl(cp.cover_image) : (cp.cover_url || presetCovers[1])})` }"></div>
                </div>

                <div class="flex justify-center -mt-10 relative z-10">
                  <div class="w-20 h-20 bg-white rounded-full p-1 shadow-sm border border-gray-100 flex items-center justify-center overflow-hidden">
                    <img v-if="cp.logo_path" :src="getImageUrl(cp.logo_path)" class="w-full h-full object-contain rounded-full">
                    <i v-else class="fas fa-building text-3xl text-gray-300"></i>
                  </div>
                </div>

                <div class="p-4 flex flex-col items-center flex-1">
                   <h4 class="font-black text-[13px] text-[#2B3674] uppercase text-center w-full leading-tight">{{ cp.name }}</h4>
                   <span class="text-[10px] font-bold px-2 py-0.5 mt-1 text-emerald-500 capitalize">Organization</span>
                   <div class="w-full border-t border-gray-100 my-2"></div>
                   <p class="text-[9px] text-center text-gray-500 leading-relaxed font-medium">
                     {{ cp.email || 'No Email' }}<br>{{ cp.phone || 'No Phone' }}<br><span class="truncate block w-full mt-1">{{ cp.legal_name || 'Registered Entity' }}</span>
                   </p>
                </div>

                <div class="pb-4 pt-1 flex justify-center gap-3 text-gray-400 text-xs">
                   <a v-if="cp.facebook" :href="cp.facebook" target="_blank" class="hover:text-blue-600 transition-colors"><i class="fab fa-facebook-f"></i></a>
                   <a v-if="cp.twitter" :href="cp.twitter" target="_blank" class="hover:text-blue-400 transition-colors"><i class="fab fa-twitter"></i></a>
                   <a v-if="cp.instagram" :href="cp.instagram" target="_blank" class="hover:text-pink-600 transition-colors"><i class="fab fa-instagram"></i></a>
                   <a v-if="cp.linkedin" :href="cp.linkedin" target="_blank" class="hover:text-blue-700 transition-colors"><i class="fab fa-linkedin-in"></i></a>
                   <template v-if="!cp.facebook && !cp.twitter && !cp.instagram && !cp.linkedin">
                     <i class="fab fa-facebook-f opacity-30"></i><i class="fab fa-twitter opacity-30"></i><i class="fab fa-instagram opacity-30"></i><i class="fab fa-linkedin-in opacity-30"></i>
                   </template>
                </div>
              </div>
            </div>

            <div v-if="orgTotalPages > 1" class="mt-4 flex flex-col md:flex-row justify-between items-center gap-4">
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Page {{ orgCurrentPage }} of {{ orgTotalPages }}</p>
                <div class="flex items-center gap-1.5"><button @click="prevOrgPage" :disabled="orgCurrentPage === 1" class="w-7 h-7 flex items-center justify-center rounded bg-white border border-gray-200 text-gray-500 hover:bg-gray-50 disabled:opacity-30"><i class="fas fa-chevron-left text-[10px]"></i></button><div class="flex items-center gap-1"><button v-for="page in orgTotalPages" :key="page" @click="goToOrgPage(page)" class="w-7 h-7 flex items-center justify-center rounded text-[10px] font-bold transition-all" :class="orgCurrentPage === page ? 'bg-[#4361EE] text-white' : 'bg-white border border-gray-200 text-gray-500 hover:bg-gray-50'">{{ page }}</button></div><button @click="nextOrgPage" :disabled="orgCurrentPage === orgTotalPages" class="w-7 h-7 flex items-center justify-center rounded bg-white border border-gray-200 text-gray-500 hover:bg-gray-50 disabled:opacity-30"><i class="fas fa-chevron-right text-[10px]"></i></button></div>
            </div>
          </div>

          <div v-if="showMembersSection && filteredMembers.length > 0">
            <div :class="filteredMembers.length < 3 ? 'flex flex-wrap justify-center gap-6' : 'grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 items-start content-start'">
              
              <div v-for="m in filteredMembers" :key="m.id" 
                   :class="filteredMembers.length < 3 ? 'w-full sm:w-[320px]' : ''" 
                   class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden flex flex-col relative group">
                
                <div class="absolute top-2 right-2 flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity z-20">
                    <button @click="handleEditMaster(m)" class="w-6 h-6 bg-white/90 rounded shadow flex items-center justify-center text-[#4361EE] hover:bg-white"><i class="fas fa-edit text-[10px]"></i></button>
                    <button @click="handleDeleteMember(m.id)" class="w-6 h-6 bg-white/90 rounded shadow flex items-center justify-center text-red-500 hover:bg-white"><i class="fas fa-trash text-[10px]"></i></button>
                </div>

                <div class="h-20 w-full bg-slate-100 relative">
                  <div class="absolute inset-0 bg-cover bg-center" :style="{ backgroundImage: `url(${m.cover_image ? getImageUrl(m.cover_image) : (m.cover_url || presetCovers[0])})` }"></div>
                </div>

                <div class="flex justify-center -mt-10 relative z-10">
                  <div class="w-20 h-20 bg-white rounded-full p-1 shadow-sm border border-gray-100 flex items-center justify-center overflow-hidden">
                    <img v-if="m.avatar_url" :src="getImageUrl(m.avatar_url)" class="w-full h-full object-cover rounded-full">
                    <div v-else class="w-full h-full bg-gray-200 rounded-full flex items-center justify-center text-gray-500 text-xl font-bold uppercase">{{ m.name.substring(0,2) }}</div>
                  </div>
                </div>

                <div class="p-4 flex flex-col items-center flex-1">
                   <h4 class="font-black text-[13px] text-[#2B3674] uppercase text-center w-full leading-tight truncate">{{ m.name }}</h4>
                   <span class="text-[10px] font-bold px-2 py-0.5 mt-1 capitalize" :class="m.role === 'admin' || m.role === 'super_admin' ? 'text-red-500' : 'text-emerald-500'">{{ m.role || 'Member' }}</span>
                   <div class="w-full border-t border-gray-100 my-2"></div>
                   <p class="text-[9px] text-center text-gray-500 leading-relaxed font-medium">
                     {{ m.position || 'Staff' }}<br><span class="truncate block w-full">{{ m.email || 'no-email@system.com' }}</span>{{ m.phone || '-' }}<br>
                   </p>
                </div>

                <div class="pb-4 pt-1 flex justify-center gap-3 text-gray-400 text-xs">
                   <a v-if="m.facebook" :href="m.facebook" target="_blank" class="hover:text-blue-600 transition-colors"><i class="fab fa-facebook-f"></i></a>
                   <a v-if="m.twitter" :href="m.twitter" target="_blank" class="hover:text-blue-400 transition-colors"><i class="fab fa-twitter"></i></a>
                   <a v-if="m.instagram" :href="m.instagram" target="_blank" class="hover:text-pink-600 transition-colors"><i class="fab fa-instagram"></i></a>
                   <a v-if="m.linkedin" :href="m.linkedin" target="_blank" class="hover:text-blue-700 transition-colors"><i class="fab fa-linkedin-in"></i></a>
                   <template v-if="!m.facebook && !m.twitter && !m.instagram && !m.linkedin">
                     <i class="fab fa-facebook-f opacity-30"></i><i class="fab fa-twitter opacity-30"></i><i class="fab fa-instagram opacity-30"></i><i class="fab fa-linkedin-in opacity-30"></i>
                   </template>
                </div>
              </div>
            </div>

            <div v-if="totalPages > 1" class="mt-4 flex flex-col md:flex-row justify-between items-center gap-4">
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Page {{ currentPage }} of {{ totalPages }}</p>
                <div class="flex items-center gap-1.5"><button @click="prevPage" :disabled="currentPage === 1" class="w-7 h-7 flex items-center justify-center rounded bg-white border border-gray-200 text-gray-500 hover:bg-gray-50 disabled:opacity-30"><i class="fas fa-chevron-left text-[10px]"></i></button><div class="flex items-center gap-1"><button v-for="page in totalPages" :key="page" @click="goToPage(page)" class="w-7 h-7 flex items-center justify-center rounded text-[10px] font-bold transition-all" :class="currentPage === page ? 'bg-[#4361EE] text-white' : 'bg-white border border-gray-200 text-gray-500 hover:bg-gray-50'">{{ page }}</button></div><button @click="nextPage" :disabled="currentPage === totalPages" class="w-7 h-7 flex items-center justify-center rounded bg-white border border-gray-200 text-gray-500 hover:bg-gray-50 disabled:opacity-30"><i class="fas fa-chevron-right text-[10px]"></i></button></div>
            </div>
          </div>

          <div v-if="filteredMembers.length === 0 && paginatedOrganizations.length === 0 && !isLoading" class="py-16 text-center opacity-40 border-2 border-dashed border-gray-300 rounded-xl">
              <i class="fas fa-search text-3xl mb-3 text-gray-400"></i><p class="text-xs font-bold uppercase text-gray-500">Pencarian tidak ditemukan</p>
          </div>

        </div>
      </div>
    </div>

    <div v-if="showAddModal" class="fixed inset-0 z-[999] flex items-center justify-center p-6">
      <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showAddModal = false"></div>
      <div class="bg-white w-full max-w-2xl rounded-[2.5rem] shadow-2xl relative z-10 overflow-hidden animate-in zoom-in duration-300">
        <div class="p-8 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
          <div>
            <h3 class="text-xl font-black text-slate-800 uppercase italic">{{ isEditing ? 'Edit Entity' : 'Add New Entity' }}</h3>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Register Team or Organization</p>
          </div>
          <button @click="showAddModal = false" class="text-slate-300 hover:text-rose-500 transition-colors"><i class="fas fa-times-circle text-2xl"></i></button>
        </div>

        <div class="p-8 space-y-6 max-h-[70vh] overflow-y-auto custom-scrollbar">
          <div class="flex bg-slate-100 p-1 rounded-2xl w-max mx-auto mb-4">
            <button v-for="t in ['individual', 'organization']" :key="t" @click="entityType = t" class="px-8 py-2.5 text-[10px] font-black uppercase tracking-widest rounded-xl transition-all" :class="entityType === t ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-400'">{{ t }}</button>
          </div>

          <div class="space-y-4">
            <template v-if="entityType === 'individual'">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div class="space-y-1"><label class="text-[9px] font-black text-slate-400 uppercase ml-1">Full Name</label><input v-model="teamworkForm.name" type="text" placeholder="E.G. JOHN DOE" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-3 text-xs font-bold outline-none focus:ring-2 ring-indigo-100 transition-all uppercase"></div>
                  <div class="space-y-1"><label class="text-[9px] font-black text-slate-400 uppercase ml-1">Email Address</label><input v-model="teamworkForm.email" type="email" placeholder="JOHN@EXAMPLE.COM" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-3 text-xs font-bold outline-none uppercase"></div>
                </div>
                
                <div class="grid grid-cols-2 gap-4 mt-4">
                  <div class="space-y-1"><label class="text-[9px] font-black text-slate-400 uppercase ml-1">Phone Number</label><input v-model="teamworkForm.phone" type="text" placeholder="E.G. +6281234..." class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-3 text-xs font-bold outline-none uppercase"></div>
                  <div class="space-y-1"><label class="text-[9px] font-black text-slate-400 uppercase ml-1">Hourly Rate (IDR)</label><input v-model="teamworkForm.hourly_rate" type="number" placeholder="E.G. 150000" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-3 text-xs font-bold outline-none"></div>
                </div>
                <div class="grid grid-cols-2 gap-4 mt-4">
                  <div class="space-y-1"><label class="text-[9px] font-black text-slate-400 uppercase ml-1">Job Role / Position</label><input v-model="teamworkForm.position" type="text" placeholder="E.G. DEVELOPER" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-3 text-xs font-bold outline-none uppercase"></div>
                  <div class="space-y-1"><label class="text-[9px] font-black text-slate-400 uppercase ml-1">System Access Level</label><select v-model="teamworkForm.role" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-3 text-xs font-bold outline-none appearance-none cursor-pointer"><option value="member">Member</option><option value="admin">Admin</option><option value="super_admin">Super Admin</option></select></div>
                </div>
                <div class="space-y-1 mt-4"><label class="text-[9px] font-black text-slate-400 uppercase ml-1">Affiliated PT</label><select v-model="teamworkForm.company_id" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-3 text-xs font-bold outline-none appearance-none cursor-pointer"><option :value="null">-- INDEPENDENT / NO PT --</option><option v-for="cp in allCompanies" :key="cp.id" :value="cp.id">{{ cp.name }}</option></select></div>
                <div class="space-y-1 mt-4">
                  <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Profile Avatar</label>
                  <div class="relative w-full h-[70px] border-2 border-dashed border-slate-200 rounded-2xl flex flex-col items-center justify-center text-slate-400 hover:bg-slate-50 cursor-pointer overflow-hidden group">
                    <input type="file" @change="handleAvatarChange" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-20" accept="image/png, image/jpeg, image/svg+xml">
                    <img v-if="avatarPreview" :src="avatarPreview" class="absolute inset-0 w-full h-full object-contain p-1 opacity-40 z-0" />
                    <i v-if="!avatarPreview" class="fas fa-user-circle text-xl mb-1"></i>
                    <span class="text-[9px] font-black uppercase text-center px-4 relative z-10 bg-white/80 py-1 rounded">{{ selectedAvatar ? selectedAvatar.name : (avatarPreview ? 'Change Avatar' : 'Upload Avatar') }}</span>
                  </div>
                </div>
            </template>

            <template v-else>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div class="space-y-1"><label class="text-[9px] font-black text-slate-400 uppercase ml-1">Company Name</label><input v-model="teamworkForm.name" type="text" placeholder="PT. MAJU MUNDUR" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-3 text-xs font-bold outline-none uppercase"></div>
                  <div class="space-y-1"><label class="text-[9px] font-black text-slate-400 uppercase ml-1">Legal Name</label><input v-model="teamworkForm.org_legal_name" type="text" placeholder="PT. MAJU MUNDUR SEJAHTERA" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-3 text-xs font-bold outline-none uppercase"></div>
                </div>
                <div class="grid grid-cols-2 gap-4 mt-4">
                  <div class="space-y-1"><label class="text-[9px] font-black text-slate-400 uppercase ml-1">Company Email</label><input v-model="teamworkForm.org_email" type="email" placeholder="CONTACT@COMPANY.COM" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-3 text-xs font-bold outline-none uppercase"></div>
                  <div class="space-y-1"><label class="text-[9px] font-black text-slate-400 uppercase ml-1">Office Phone Number</label><input v-model="teamworkForm.org_phone" type="text" placeholder="021-1234567" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-3 text-xs font-bold outline-none uppercase"></div>
                </div>
                <div class="space-y-1 mt-4"><label class="text-[9px] font-black text-slate-400 uppercase ml-1">Address</label><textarea v-model="teamworkForm.org_address" rows="2" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-3 text-xs font-bold outline-none uppercase resize-none"></textarea></div>
                <div class="space-y-1 mt-4">
                  <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Company Logo</label>
                  <div class="relative w-full h-[70px] border-2 border-dashed border-slate-200 rounded-2xl flex flex-col items-center justify-center text-slate-400 hover:bg-slate-50 cursor-pointer overflow-hidden group">
                    <input type="file" @change="handleOrgLogoChange" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-20" accept="image/png, image/jpeg, image/svg+xml">
                    <img v-if="logoPreview" :src="logoPreview" class="absolute inset-0 w-full h-full object-contain p-1 opacity-40 z-0" />
                    <i v-if="!logoPreview" class="fas fa-image text-xl mb-1"></i>
                    <span class="text-[9px] font-black uppercase text-center px-4 relative z-10 bg-white/80 py-1 rounded">{{ selectedOrgLogo ? selectedOrgLogo.name : (logoPreview ? 'Change Logo' : 'Upload Logo') }}</span>
                  </div>
                </div>
            </template>
            
            <div class="w-full border-t border-slate-100 my-6"></div>

            <div class="space-y-3">
              <label class="text-[10px] font-black text-indigo-500 uppercase flex items-center gap-2"><i class="fas fa-hashtag"></i> Social Media Links <span class="text-[8px] text-slate-400 font-normal ml-auto">(Opsional)</span></label>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="relative"><i class="fab fa-facebook-f absolute left-4 top-1/2 -translate-y-1/2 text-blue-600"></i><input v-model="teamworkForm.facebook" type="text" placeholder="https://facebook.com/..." class="w-full bg-slate-50 border border-slate-100 rounded-2xl pl-10 pr-4 py-3 text-[10px] font-bold outline-none text-slate-600"></div>
                <div class="relative"><i class="fab fa-twitter absolute left-4 top-1/2 -translate-y-1/2 text-blue-400"></i><input v-model="teamworkForm.twitter" type="text" placeholder="https://twitter.com/..." class="w-full bg-slate-50 border border-slate-100 rounded-2xl pl-10 pr-4 py-3 text-[10px] font-bold outline-none text-slate-600"></div>
                <div class="relative"><i class="fab fa-instagram absolute left-4 top-1/2 -translate-y-1/2 text-pink-600"></i><input v-model="teamworkForm.instagram" type="text" placeholder="https://instagram.com/..." class="w-full bg-slate-50 border border-slate-100 rounded-2xl pl-10 pr-4 py-3 text-[10px] font-bold outline-none text-slate-600"></div>
                <div class="relative"><i class="fab fa-linkedin-in absolute left-4 top-1/2 -translate-y-1/2 text-blue-700"></i><input v-model="teamworkForm.linkedin" type="text" placeholder="https://linkedin.com/in/..." class="w-full bg-slate-50 border border-slate-100 rounded-2xl pl-10 pr-4 py-3 text-[10px] font-bold outline-none text-slate-600"></div>
              </div>
            </div>

            <div class="space-y-3 mt-6">
              <label class="text-[10px] font-black text-indigo-500 uppercase flex items-center gap-2"><i class="fas fa-image"></i> Card Background Cover <span class="text-[8px] text-slate-400 font-normal ml-auto">Pilih atau Upload Gambar</span></label>
              <div class="flex gap-3 overflow-x-auto pb-2 custom-scrollbar">
                
                <div v-for="(bgUrl, idx) in presetCovers" :key="idx" @click="selectPresetCover(bgUrl)" 
                     class="w-24 h-16 rounded-xl flex-shrink-0 cursor-pointer overflow-hidden border-2 transition-all relative"
                     :class="teamworkForm.cover_url === bgUrl ? 'border-indigo-600 shadow-md scale-105' : 'border-transparent hover:border-slate-300'">
                  <div class="absolute inset-0 bg-cover bg-center" :style="{ backgroundImage: `url(${bgUrl})` }"></div>
                  <div v-if="teamworkForm.cover_url === bgUrl" class="absolute inset-0 bg-indigo-600/30 flex items-center justify-center"><i class="fas fa-check-circle text-white text-lg drop-shadow"></i></div>
                </div>

                <div class="relative w-24 h-16 rounded-xl flex-shrink-0 cursor-pointer overflow-hidden border-2 border-dashed flex flex-col items-center justify-center transition-all bg-slate-50"
                     :class="selectedCover || (coverPreview && !presetCovers.includes(coverPreview)) ? 'border-indigo-600' : 'border-slate-300 hover:bg-slate-100'">
                  <input type="file" @change="handleCoverChange" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-20" accept="image/png, image/jpeg, image/jpg">
                  <img v-if="coverPreview && !presetCovers.includes(coverPreview)" :src="coverPreview" class="absolute inset-0 w-full h-full object-cover z-0" />
                  <i v-if="!coverPreview || presetCovers.includes(coverPreview)" class="fas fa-upload text-slate-400 mb-1"></i>
                  <span v-if="!coverPreview || presetCovers.includes(coverPreview)" class="text-[8px] font-bold text-slate-400">Upload</span>
                  <div v-if="coverPreview && !presetCovers.includes(coverPreview)" class="absolute inset-0 bg-indigo-600/30 flex items-center justify-center z-10"><i class="fas fa-check-circle text-white text-lg drop-shadow"></i></div>
                </div>
              </div>
            </div>

          </div>

          <div class="pt-6">
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
.custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #e2e8f0; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background-color: #cbd5e1; }
.animate-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
</style>