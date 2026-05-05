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
  // Tambahan untuk Individual
  phone: string;
  hourly_rate: number;
  // Tambahan untuk Organization
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
// const topOutstanding = ref<any[]>([]);
const isEditing = ref(false);
const editingId = ref<number | null>(null);
const entityType = ref('individual');
const selectedOrgLogo = ref<File | null>(null); // State untuk file logo
const logoPreview = ref<string | null>(null);
const selectedAvatar = ref<File | null>(null);
const avatarPreview = ref<string | null>(null);

const teamworkForm = ref<TeamworkForm>({
  name: '',
  email: '',
  password: '',
  role: 'member',
  position: '',
  company_id: null,
  status: 'Active',
  phone: '',
  hourly_rate: 0,
  org_legal_name: '',
  org_email: '',
  org_phone: '',
  org_address: '',
  org_description: ''
});

// Helper Formatting
const formatCurrency = (val: number) => {
  return new Intl.NumberFormat('id-ID', { 
    style: 'currency', 
    currency: 'IDR', 
    maximumFractionDigits: 0 
  }).format(val || 0);
};

// ==========================================
// 2. FILTERING & PAGINATION STATE
// ==========================================
const activeFilter = ref({ type: 'all', value: null as any });
const openMenu = ref<string | null>('role'); 
const currentPage = ref(1);
const totalPages = ref(1);

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

const setFilter = (type: string, value: any) => {
  activeFilter.value = { type, value };
  currentPage.value = 1; 
  fetchData();           
};

const toggleMenu = (menuName: string) => {
  openMenu.value = openMenu.value === menuName ? null : menuName;
};

const nextPage = () => { if (currentPage.value < totalPages.value) { currentPage.value++; fetchData(); } };
const prevPage = () => { if (currentPage.value > 1) { currentPage.value--; fetchData(); } };
const goToPage = (page: number) => { currentPage.value = page; fetchData(); };

const paginatedTeamData = computed(() => teamData.value);

// Fungsi khusus untuk menghapus Organization
const handleDeleteCompany = async (id: number) => {
  // Munculkan peringatan konfirmasi terlebih dahulu
  if (!confirm("Apakah Anda yakin ingin menghapus Organization ini? Data yang dihapus tidak dapat dikembalikan.")) return;
  
  try {
    // Panggil API Delete yang baru kita buat
    await api.delete(`/companies/${id}`);
    alert("Organization berhasil dihapus!");
    
    // Refresh data di layar agar kartu perusahaan langsung menghilang
    await fetchData(); 
  } catch (error: any) {
    // Tampilkan pesan error dari backend (misal jika PT masih dipakai di proyek)
    alert(error.response?.data?.error || "Gagal menghapus data Organization.");
  }
};

// Fungsi menangkap file avatar
const handleAvatarChange = (e: Event) => {
  const target = e.target as HTMLInputElement;
  const file = target.files?.[0];
  if (file) {
    selectedAvatar.value = file;
    avatarPreview.value = URL.createObjectURL(file);
  }
};

// Menangkap file gambar logo
const handleOrgLogoChange = (e: Event) => {
  const target = e.target as HTMLInputElement;
  
  // 1. Ambil file pertama secara aman
  const file = target.files?.[0];

  // 2. Jika file-nya benar-benar ada (bukan undefined), baru kita proses
  if (file) {
    selectedOrgLogo.value = file;
    
    // 3. Buat URL pratinjau lokal dari file yang sudah dipastikan aman
    logoPreview.value = URL.createObjectURL(file);
  }
};

// ==========================================
// 4. CRUD OPERATIONS
// ==========================================
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

      if (!isEditing.value) {
          formData.append('password', 'password123');
      }

      if (selectedAvatar.value) {
        formData.append('avatar', selectedAvatar.value);
      }

      // 4. Proses pengiriman ke Backend
      if (isEditing.value) {
        url = `/users/${editingId.value}`;
        // Spoofer Laravel: Beritahu backend bahwa ini sebenarnya proses PUT
        formData.append('_method', 'PUT'); 
        
        // Kirim menggunakan POST dengan headers khusus multipart
        await api.post(url, formData, { headers: { 'Content-Type': 'multipart/form-data' }});
      } else {
        url = '/users/register';
        await api.post(url, formData, { headers: { 'Content-Type': 'multipart/form-data' }});
      }
      
    } else {
      // ==========================================
      // PENANGANAN UNTUK ORGANIZATION (PERUSAHAAN)
      // ==========================================
      
      // 1. Validasi: Pastikan nama perusahaan tidak kosong
      // (Bisa dari input name biasa atau org_legal_name, tergantung desain form kamu)
      const companyName = teamworkForm.value.org_legal_name || teamworkForm.value.name;
      if (!companyName) {
          return alert("Nama Perusahaan (Legal Name) wajib diisi.");
      }

      // 2. Buat wadah FormData baru
      let formData = new FormData();

      // 3. Masukkan data teks ke dalam wadah
      // Catatan: Jika nilai kosong (null/undefined), kita beri string kosong ('') agar tidak error
      formData.append('name', companyName);
      formData.append('legal_name', companyName);
      formData.append('email', teamworkForm.value.org_email || '');
      formData.append('phone', teamworkForm.value.org_phone || '');
      formData.append('address', teamworkForm.value.org_address || '');
      formData.append('description', teamworkForm.value.org_description || '');

      // 4. Masukkan file logo jika pengguna memilih gambar
      if (selectedOrgLogo.value) {
        formData.append('logo', selectedOrgLogo.value);
      }

      // 5. Proses Pengiriman API
      if (isEditing.value) {
        // Jika sedang mode Edit
        url = `/companies/${editingId.value}`;
        
        // Gunakan trik spoofer _method=PUT karena kita mengirim file dengan mode POST
        formData.append('_method', 'PUT'); 
        
        await api.post(url, formData, { 
            headers: { 'Content-Type': 'multipart/form-data' }
        });
      } else {
        // Jika sedang mode Add (Tambah Baru)
        url = '/companies';
        await api.post(url, formData, { 
            headers: { 'Content-Type': 'multipart/form-data' }
        });
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
  logoPreview.value = null; // Tambahkan baris ini
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

// 1. Fungsi Klik Tombol Edit untuk Individual
const handleEditMaster = (member: any) => {
  isEditing.value = true;
  editingId.value = member.id;
  entityType.value = 'individual';
  
  // Isi form dengan data lama dari database
  teamworkForm.value = {
    name: member.name,
    email: member.email,
    password: '', // Kosongkan password demi keamanan
    role: member.role || 'member',
    position: member.position || '',
    company_id: member.company_id || null,
    status: member.status || 'Active',
    phone: member.phone || '',
    hourly_rate: member.hourly_rate || 0,
    org_legal_name: '', org_email: '', org_phone: '', org_address: '', org_description: ''
  };
  
  selectedAvatar.value = null;
  if (member.avatar_url) {
    avatarPreview.value = `http://localhost:8000/uploads/${member.avatar_url}`;
  } else {
    avatarPreview.value = null;
  }

  selectedOrgLogo.value = null;
  showAddModal.value = true; // Munculkan Modal Utama
};

// 3. Perbarui bagian bawah dari fungsi handleEditCompany
const handleEditCompany = (company: any) => {
  isEditing.value = true;
  editingId.value = company.id;
  entityType.value = 'organization';

  teamworkForm.value = {
    name: company.name, 
    email: '', password: '', role: 'member', position: '', company_id: null, status: 'Active', phone: '', hourly_rate: 0,
    
    org_legal_name: company.legal_name || '',
    org_email: company.email || '',
    org_phone: company.phone || '',
    org_address: company.address || '',
    org_description: company.description || ''
  };
  
  selectedOrgLogo.value = null;

  // --- TAMBAHKAN LOGIKA PREVIEW INI ---
  // Cek apakah ada logo di database, jika ada, buatkan URL lengkapnya
  if (company.logo_path) {
    logoPreview.value = `http://localhost:8000/uploads/${company.logo_path}`;
  } else {
    logoPreview.value = null;
  }
  // -----------------------------------

  showAddModal.value = true; 
};

onMounted(fetchData);
</script>

<template>
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 animate-in fade-in duration-500 pb-20 mt-4">
    
    <!-- SIDEBAR NAVIGATION (Kiri) -->
    <div class="lg:col-span-3 space-y-6">
      <div class="bg-white border border-slate-200 rounded-[2.5rem] p-6 shadow-sm sticky top-8">
        <h4 class="text-[10px] font-black uppercase text-slate-400 mb-5 tracking-widest ml-2">
          Filter Personnel
        </h4>

        <div class="space-y-1.5">
          <!-- 1. MEMBER BUTTON -->
          <button @click="setFilter('all', null); openMenu = null"
            class="w-full flex items-center gap-4 px-4 py-3 rounded-2xl transition-all text-left group border"
            :class="activeFilter.type === 'all' ? 'bg-indigo-50 border-indigo-100 shadow-sm' : 'hover:bg-slate-50 border-transparent'">
            <div class="w-8 h-8 rounded-xl flex items-center justify-center transition-colors"
              :class="activeFilter.type === 'all' ? 'bg-indigo-600 text-white shadow-md' : 'bg-slate-100 text-slate-400 group-hover:bg-slate-200 group-hover:text-indigo-500'">
              <i class="fas fa-users text-xs"></i>
            </div>
            <span class="text-[11px] font-black uppercase tracking-widest transition-colors" 
              :class="activeFilter.type === 'all' ? 'text-indigo-600' : 'text-slate-500 group-hover:text-indigo-600'">
              Member
            </span>
          </button>

          <!-- 2. ORGANIZATION BUTTON -->
          <button @click="setFilter('view_companies', null); openMenu = null"
            class="w-full flex items-center gap-4 px-4 py-3 rounded-2xl transition-all text-left group border"
            :class="activeFilter.type === 'view_companies' ? 'bg-indigo-50 border-indigo-100 shadow-sm' : 'hover:bg-slate-50 border-transparent'">
            <div class="w-8 h-8 rounded-xl flex items-center justify-center transition-colors"
              :class="activeFilter.type === 'view_companies' ? 'bg-indigo-600 text-white shadow-md' : 'bg-slate-100 text-slate-400 group-hover:bg-slate-200 group-hover:text-indigo-500'">
              <i class="fas fa-building text-xs"></i>
            </div>
            <span class="text-[11px] font-black uppercase tracking-widest transition-colors" 
              :class="activeFilter.type === 'view_companies' ? 'text-indigo-600' : 'text-slate-500 group-hover:text-indigo-600'">
              Organization
            </span>
          </button>

          <!-- 3. ACCESS ROLE -->
          <div class="border rounded-2xl transition-all" 
               :class="openMenu === 'role' || activeFilter.type === 'role' ? 'bg-slate-50 border-slate-100 pb-2' : 'border-transparent'">
            <button @click="toggleMenu('role')"
              class="w-full flex items-center justify-between px-4 py-3 rounded-2xl transition-all text-left group hover:bg-slate-50/80">
              <div class="flex items-center gap-4">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center transition-colors bg-slate-100 text-slate-400 group-hover:bg-slate-200 group-hover:text-emerald-500"
                  :class="activeFilter.type === 'role' ? 'bg-emerald-100 text-emerald-600' : ''">
                  <i class="fas fa-user-tag text-xs"></i>
                </div>
                <span class="text-[11px] font-black uppercase tracking-widest transition-colors text-slate-500 group-hover:text-emerald-600"
                      :class="activeFilter.type === 'role' ? 'text-emerald-600' : ''">
                  Access Role
                </span>
              </div>
              <i class="fas fa-chevron-down text-[10px] text-slate-400 transition-transform duration-300" :class="openMenu === 'role' ? 'rotate-180' : ''"></i>
            </button>
            
            <div v-show="openMenu === 'role'" class="mt-1 px-2 space-y-1 animate-in slide-in-from-top-2 duration-300">
              <button v-for="r in [{id: 'super_admin', label: 'Super Admin'}, {id: 'admin', label: 'Admin'}, {id: 'member', label: 'Member'}]" :key="r.id"
                @click="setFilter('role', r.id)"
                class="w-full flex items-center text-left py-2.5 px-3 text-[10px] font-black uppercase tracking-widest transition-all rounded-xl"
                :class="activeFilter.type === 'role' && activeFilter.value === r.id ? 'text-emerald-600 bg-emerald-100/50' : 'text-slate-400 hover:text-emerald-500 hover:bg-slate-100/50'">
                <span class="opacity-40 font-normal mr-2 text-[11px]">#</span> {{ r.label }}
              </button>
            </div>
          </div>

          <!-- 4. TEAM TAGS -->
          <div class="border rounded-2xl transition-all" 
               :class="openMenu === 'tag' || activeFilter.type === 'tag' ? 'bg-slate-50 border-slate-100 pb-2' : 'border-transparent'">
            <button @click="toggleMenu('tag')"
              class="w-full flex items-center justify-between px-4 py-3 rounded-2xl transition-all text-left group hover:bg-slate-50/80">
              <div class="flex items-center gap-4">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center transition-colors bg-slate-100 text-slate-400 group-hover:bg-slate-200 group-hover:text-amber-500"
                  :class="activeFilter.type === 'tag' ? 'bg-amber-100 text-amber-600' : ''">
                  <i class="fas fa-tags text-xs"></i>
                </div>
                <span class="text-[11px] font-black uppercase tracking-widest transition-colors text-slate-500 group-hover:text-amber-600"
                      :class="activeFilter.type === 'tag' ? 'text-amber-600' : ''">
                  Team Tags
                </span>
              </div>
              <i class="fas fa-chevron-down text-[10px] text-slate-400 transition-transform duration-300" :class="openMenu === 'tag' ? 'rotate-180' : ''"></i>
            </button>
            
            <div v-show="openMenu === 'tag'" class="mt-1 px-2 space-y-1 animate-in slide-in-from-top-2 duration-300 max-h-[250px] overflow-y-auto custom-scrollbar">
              <div v-if="teamTags.length === 0" class="text-[9px] font-bold text-slate-400 uppercase tracking-widest py-2 px-3 text-center">
                Belum ada tag
              </div>
              <button v-for="tag in teamTags" :key="tag.id"
                @click="setFilter('tag', tag.id)"
                class="w-full flex items-center text-left py-2.5 px-3 text-[10px] font-black uppercase tracking-widest transition-all rounded-xl truncate"
                :class="activeFilter.type === 'tag' && activeFilter.value === tag.id ? 'text-amber-600 bg-amber-100/50' : 'text-slate-400 hover:text-amber-500 hover:bg-slate-100/50'">
                <span class="opacity-40 font-normal mr-2 text-[11px]">#</span> {{ tag.name }}
              </button>
            </div>
          </div>

        </div>
      </div>
    </div>

    <!-- MAIN CONTENT (Kanan) -->
    <div class="lg:col-span-9 flex flex-col gap-6 min-h-[600px] relative">
      
      <div v-if="isLoading" class="absolute inset-0 bg-white/50 backdrop-blur-sm z-50 flex items-center justify-center rounded-[3rem]">
        <i class="fas fa-spinner fa-spin text-3xl text-indigo-600"></i>
      </div>

      <!-- Top Bar: Filter Status & Action Button -->
      <div class="bg-white rounded-[2.5rem] p-6 shadow-sm border border-slate-200 flex flex-col md:flex-row justify-between items-center gap-4 relative z-10">
        <div class="flex items-center gap-4 w-full md:w-auto">
          <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Current Filter:</span>
          <div class="bg-slate-50 border border-slate-100 text-indigo-700 text-[10px] font-bold uppercase py-3 px-5 rounded-xl flex items-center gap-3 min-w-[200px]">
            <span v-if="activeFilter.type === 'all'">Member</span>
            <span v-else-if="activeFilter.type === 'view_companies'">Organization</span>
            <span v-else>
              {{ activeFilter.type === 'tag' ? 'TAG : ' + (teamTags.find(t => t.id === activeFilter.value)?.name || '') : activeFilter.type + ': ' + activeFilter.value }}
            </span>
            <button v-if="activeFilter.type !== 'all'" @click="setFilter('all', null)" class="ml-auto text-rose-400 hover:text-rose-600 transition-colors">
              <i class="fas fa-times-circle text-sm"></i>
            </button>
          </div>
        </div>

        <button @click="showAddModal = true" class="w-full md:w-auto bg-indigo-600 text-white px-8 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-xl shadow-indigo-100 hover:bg-indigo-700 transition-all flex items-center justify-center gap-3">
          <i class="fas fa-plus"></i> Add Team / Entity
        </button>
      </div>

      <!-- Content Area -->
      <div class="bg-white rounded-[3rem] border border-slate-200 shadow-sm flex-1 overflow-hidden flex flex-col p-6 lg:p-8">
        
        <!-- GRID UNTUK MENAMPILKAN DAFTAR PERUSAHAAN (ORGANIZATION) -->
        <div v-if="activeFilter.type === 'view_companies'" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 items-start content-start flex-1 overflow-y-auto custom-scrollbar pr-2 pb-4">
          <div v-for="cp in allCompanies" :key="cp.id" 
            class="relative bg-white rounded-3xl p-6 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)] border border-slate-100 hover:shadow-[0_8px_30px_rgb(0,0,0,0.06)] hover:-translate-y-1 hover:border-indigo-200 transition-all duration-300 group overflow-hidden flex flex-col justify-between h-[210px]">
            
            <!-- Dekorasi Latar (Aksen Lingkaran di Pojok Kanan Atas) -->
            <div class="absolute -right-6 -top-6 w-24 h-24 bg-gradient-to-br from-indigo-50 to-white rounded-full opacity-50 group-hover:scale-150 transition-transform duration-700 ease-out z-0"></div>

            <div class="relative z-10 flex flex-col h-full">
              <!-- Header: Logo & Nama -->
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

              <!-- Garis Pemisah (Divider) -->
              <div class="w-full h-px bg-slate-100 mb-3 group-hover:bg-indigo-50 transition-colors"></div>

              <!-- Detail Informasi -->
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
                  <!-- Format kasbon sementara di-set ke 0 sampai dihubungkan ke backend -->
                  <span class="text-[10px] font-black italic text-emerald-500">
                    {{ formatCurrency(0) }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Tombol Aksi Edit & Delete (Muncul saat di-hover) -->
            <div class="absolute inset-x-0 bottom-0 p-6 bg-gradient-to-t from-white via-white to-transparent translate-y-[120%] group-hover:translate-y-0 transition-transform duration-300 ease-out flex justify-center gap-3 z-20 rounded-b-[2.5rem]">
              <button @click="handleEditCompany(cp)" class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white transition-all shadow-sm flex items-center justify-center">
                  <i class="fas fa-edit text-[11px]"></i>
              </button>
              <button @click="handleDeleteCompany(cp.id)" class="w-10 h-10 rounded-xl bg-rose-50 text-rose-500 hover:bg-rose-600 hover:text-white transition-all shadow-sm flex items-center justify-center">
                  <i class="fas fa-trash text-[11px]"></i>
              </button>
            </div>
          </div>
          
          <!-- EMPTY STATE -->
          <div v-if="allCompanies.length === 0" class="col-span-full py-24 text-center opacity-40 border-2 border-dashed border-slate-200 rounded-[3rem]">
              <i class="fas fa-building-circle-exclamation text-5xl mb-4 text-slate-300"></i>
              <p class="text-[11px] font-black uppercase tracking-widest text-slate-500">No Organization found</p>
          </div>
        </div>

        <!-- GRID UNTUK MENAMPILKAN DAFTAR PERSONNEL (MEMBER) -->
        <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 items-start content-start flex-1 overflow-y-auto custom-scrollbar pr-2 pb-4">
          <div v-for="m in paginatedTeamData" :key="m.id" 
            class="relative bg-white rounded-3xl p-6 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)] border border-slate-100 hover:shadow-[0_8px_30px_rgb(0,0,0,0.06)] hover:-translate-y-1 hover:border-indigo-200 transition-all duration-300 group overflow-hidden flex flex-col justify-between h-[210px]">
            
            <!-- Dekorasi Latar (Aksen Lingkaran di Pojok Kanan Atas) -->
            <div class="absolute -right-6 -top-6 w-24 h-24 bg-gradient-to-br from-indigo-50 to-white rounded-full opacity-50 group-hover:scale-150 transition-transform duration-700 ease-out z-0"></div>
            
            <div class="relative z-10 flex flex-col h-full">
              <!-- Header: Avatar & Nama -->
              <div class="flex items-center gap-4 mb-4">
                <div class="w-14 h-14 shrink-0 rounded-2xl bg-indigo-50 flex items-center justify-center text-indigo-600 text-lg font-black shadow-inner uppercase border border-indigo-100 group-hover:bg-indigo-600 group-hover:text-white transition-colors overflow-hidden">
                  <!-- Format disamakan persis dengan template companies -->
                  <img v-if="m.avatar_url" :src="`http://localhost:8000/uploads/${m.avatar_url}`" class="w-full h-full object-cover">
                  <!-- Fallback: Tampilkan inisial 2 huruf jika belum ada foto -->
                  <span v-else>{{ m.name.substring(0,2) }}</span>
                </div>
                <div class="flex-1 min-w-0 overflow-hidden">
                  <h5 class="text-[13px] font-black text-slate-800 uppercase tracking-tight truncate group-hover:text-indigo-600 transition-colors">{{ m.name }}</h5>
                  <div class="mt-1">
                    <span class="text-[9px] font-bold px-2 py-0.5 bg-slate-100 text-slate-500 rounded-md uppercase tracking-widest truncate inline-block max-w-full">{{ m.position || m.role || 'Staff' }}</span>
                  </div>
                </div>
              </div>

              <!-- Garis Pemisah (Divider) -->
              <div class="w-full h-px bg-slate-100 mb-3 group-hover:bg-indigo-50 transition-colors"></div>

              <!-- Detail Informasi -->
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
                  <span class="text-[10px] font-black italic text-emerald-500">
                    {{ formatCurrency(0) }}
                  </span>
                </div>
              </div>
            </div>

            <!-- ACTION BUTTONS HOVER -->
            <div class="absolute inset-x-0 bottom-0 p-6 bg-gradient-to-t from-white via-white to-transparent translate-y-[120%] group-hover:translate-y-0 transition-transform duration-300 ease-out flex justify-center gap-3 z-20 rounded-b-[2.5rem]">
              <button @click="handleEditMaster(m)" class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white transition-all shadow-sm flex items-center justify-center">
                  <i class="fas fa-edit text-[11px]"></i>
              </button>
              <button @click="handleDeleteMember(m.id)" class="w-10 h-10 rounded-xl bg-rose-50 text-rose-500 hover:bg-rose-600 hover:text-white transition-all shadow-sm flex items-center justify-center">
                  <i class="fas fa-trash text-[11px]"></i>
              </button>
            </div>
          </div>

          <!-- EMPTY STATE -->
          <div v-if="paginatedTeamData.length === 0 && !isLoading" class="col-span-full py-24 text-center opacity-40 border-2 border-dashed border-slate-200 rounded-[3rem]">
              <i class="fas fa-users-slash text-5xl mb-4 text-slate-300"></i>
              <p class="text-[11px] font-black uppercase tracking-widest text-slate-500">No personnel found</p>
          </div>
        </div>

        <!-- PAGINATION SERVER-SIDE -->
        <div v-if="totalPages > 1 && activeFilter.type !== 'view_companies'" class="mt-4 pt-6 border-t border-slate-100 bg-white flex flex-col md:flex-row justify-between items-center gap-4">
            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">
                Viewing Page <span class="text-indigo-600">{{ currentPage }}</span> of <span class="text-indigo-600">{{ totalPages }}</span>
            </p>
            
            <div class="flex items-center gap-2">
                <button @click="prevPage" :disabled="currentPage === 1" 
                        class="w-8 h-8 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-500 hover:bg-indigo-50 hover:text-indigo-600 hover:border-indigo-200 disabled:opacity-30 disabled:cursor-not-allowed transition-all shadow-sm">
                    <i class="fas fa-chevron-left text-[10px]"></i>
                </button>
                
                <div class="flex items-center gap-1">
                    <button v-for="page in totalPages" :key="page" @click="goToPage(page)"
                            class="w-8 h-8 flex items-center justify-center rounded-xl text-[10px] font-black transition-all shadow-sm"
                            :class="currentPage === page ? 'bg-indigo-600 text-white shadow-indigo-200 border-transparent' : 'bg-white border border-slate-200 text-slate-500 hover:bg-slate-50'">
                        {{ page }}
                    </button>
                </div>

                <button @click="nextPage" :disabled="currentPage === totalPages"
                        class="w-8 h-8 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-500 hover:bg-indigo-50 hover:text-indigo-600 hover:border-indigo-200 disabled:opacity-30 disabled:cursor-not-allowed transition-all shadow-sm">
                    <i class="fas fa-chevron-right text-[10px]"></i>
                </button>
            </div>
        </div>
      </div>
    </div>

    <!-- 1. MODAL ADD TEAM MEMBER / ENTITY -->
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
            <!-- TAMPILAN UNTUK INDIVIDUAL -->
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

            <!-- TAMPILAN UNTUK ORGANIZATION -->
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
                  
                  <!-- Tambahkan class 'group' di div ini agar efek hover bekerja maksimal -->
                  <div class="relative w-full h-[70px] border-2 border-dashed border-slate-200 rounded-2xl flex flex-col items-center justify-center text-slate-400 hover:bg-slate-50 hover:border-indigo-300 transition-all cursor-pointer overflow-hidden group">
                    
                    <!-- Input File (Transparan, diletakkan paling atas/z-20 agar tetap bisa diklik) -->
                    <input type="file" @change="handleOrgLogoChange" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-20" accept="image/png, image/jpeg, image/svg+xml">
                    
                    <!-- Menampilkan Gambar Pratinjau (Jika ada) -->
                    <img v-if="logoPreview" :src="logoPreview" class="absolute inset-0 w-full h-full object-contain p-1 opacity-40 group-hover:opacity-20 transition-opacity z-0" />

                    <!-- Ikon Default (Sembunyi jika ada gambar) -->
                    <i v-if="!logoPreview" class="fas fa-image text-xl mb-1" :class="selectedOrgLogo ? 'text-indigo-500' : ''"></i>
                    
                    <!-- Teks Keterangan di Atas Gambar -->
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