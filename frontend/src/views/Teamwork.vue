<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue';
import api from '../api/axios';

// ==========================================
// 1. INTERFACES & GLOBAL STATE
// ==========================================
// Definisikan interface yang lengkap agar tidak ada error "property does not exist"
interface TeamworkForm {
  name: string;
  email: string;
  password?: string; // Gunakan tanda tanya agar opsional
  role: string;
  position: string;
  company_id: number | null;
  status: string;
}

const currentTab = ref('analytics');
const isLoading = ref(false);
const showAddModal = ref(false);
const totalProjectCount = ref(0);

// Data Teams
const organizations = ref<any[]>([]); 
const individuals = ref<any[]>([]);   
const topOutstanding = ref<any[]>([]); 
const allCompanies = ref<any[]>([]);

// Form State
const isEditing = ref(false);
const editingId = ref<number | null>(null);
const entityType = ref('individual');

// Gunakan satu variabel form yang konsisten
const teamworkForm = ref<TeamworkForm>({
  name: '',
  email: '',
  password: '',
  role: 'member',
  position: '',
  company_id: null,
  status: 'Active'
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
// 2. COMPUTED STATS
// ==========================================
const stats = computed(() => [
  { label: 'Total Entities', value: organizations.value.length + individuals.value.length, color: 'bg-indigo-600', icon: 'fa-users' },
  { label: 'Organizations', value: organizations.value.length, color: 'bg-blue-500', icon: 'fa-building' },
  { label: 'Individuals', value: individuals.value.length, color: 'bg-emerald-500', icon: 'fa-user-tie' },
  { label: 'Total Projects Run', value: totalProjectCount.value, color: 'bg-amber-500', icon: 'fa-project-diagram' },
]);

// ==========================================
// 3. API ACTIONS
// ==========================================
const fetchData = async () => {
  isLoading.value = true;
  try {
    // Hapus api.get('/users') karena datanya sudah ter-cover di '/teamwork/summary'
    const [resSummary, resTop, resComp] = await Promise.all([
      api.get('/teamwork/summary'),
      api.get('/teamwork/top-outstanding'),
      api.get('/companies')
    ]);

    organizations.value = resSummary.data.organizations || [];
    totalProjectCount.value = resSummary.data.total_projects || 0;
    
    // PERBAIKAN DI SINI: Ambil array individuals dari resSummary
    individuals.value = resSummary.data.individuals || []; 
    
    // topOutstanding sudah benar
    topOutstanding.value = resTop.data || []; 
    
    allCompanies.value = resComp.data || [];
  } catch (e) {
    console.error("Gagal sinkronisasi teamwork", e);
  } finally {
    isLoading.value = false;
  }
};

const handleSave = async () => {
  try {
    let url = entityType.value === 'individual' ? '/users/register' : '/companies';
    let payload: any = {};

    // 1. SIAPKAN DATA BERDASARKAN TIPE (INDIVIDUAL / ORGANIZATION)
    if (entityType.value === 'individual') {
      if (!teamworkForm.value.name || !teamworkForm.value.email) {
          alert("Nama dan Email wajib diisi untuk anggota baru.");
          return;
      }
      payload = {
          name: teamworkForm.value.name,
          email: teamworkForm.value.email,
          role: teamworkForm.value.role || 'member',
          position: teamworkForm.value.position,
          company_id: teamworkForm.value.company_id,
          status: teamworkForm.value.status
      };
      if (!isEditing.value) {
          payload.password = 'password123';
      }
    } else {
      // DATA UNTUK ORGANIZATION (PT)
      if (!teamworkForm.value.name) {
          alert("Nama PT wajib diisi.");
          return;
      }
      payload = {
          name: teamworkForm.value.name, 
          legal_name: teamworkForm.value.position || null, // Di HTML kita pakai input 'position' untuk field Legal Name
      };
    }

    // 2. EKSEKUSI API
    if (isEditing.value) {
      url = entityType.value === 'individual' ? `/users/${editingId.value}/role` : `/companies/${editingId.value}`;
      await api.put(url, payload);
    } else {
      await api.post(url, payload);
    }
    
    // 3. JIKA SUKSES
    showAddModal.value = false;
    await fetchData(); // Refresh data grid
    resetForm();
    alert(entityType.value === 'individual' ? "Member berhasil ditambah!" : "PT berhasil didaftarkan!");

  } catch (e: any) {
    console.error(e);
    // 4. PENANGANAN ERROR YANG LEBIH JELAS
    if (e.response && e.response.status === 422) {
       // Error Validasi Laravel
       const errors = e.response.data.errors;
       let errorMessage = "Cek kembali data Anda:\n";
       for (const key in errors) {
           errorMessage += `- ${errors[key][0]}\n`;
       }
       alert(errorMessage);
    } else if (e.response && e.response.data && e.response.data.error) {
       // Error 500 yang sudah kita tangkap di backend
       alert("Sistem Error:\n" + e.response.data.error);
    } else {
       alert("Terjadi kesalahan sistem atau koneksi terputus.");
    }
  }
};

const resetForm = () => {
  teamworkForm.value = { name: '', email: '', password: '', role: 'member', position: '', company_id: null, status: 'Active' };
  isEditing.value = false;
  editingId.value = null;
  entityType.value = 'individual';
};

// Fungsi untuk menghapus personel/member
const handleDeleteMember = async (id: number) => {
  // 1. Tampilkan dialog konfirmasi untuk mencegah salah klik
  if (!confirm("Apakah Anda yakin ingin menghapus personel ini? Data tidak dapat dikembalikan.")) {
    return;
  }

  try {
    // 2. Tembak API backend menggunakan method DELETE
    await api.delete(`/users/${id}`);
    
    // 3. Tampilkan pesan sukses
    alert("Personel berhasil dihapus!");
    
    // 4. Refresh data agar card langsung hilang dari layar
    // Catatan: Pastikan nama fungsi untuk mengambil data utamanya benar (misal: fetchData atau fetchTeamData)
    await fetchData(); 
    
  } catch (error: any) {
    console.error("Gagal menghapus data personel:", error);
    
    // Handle error spesifik (misalnya jika user masih punya kasbon)
    if (error.response?.status === 500) {
      alert("Gagal menghapus: Personel ini mungkin masih terikat dengan data keuangan/kasbon atau project.");
    } else {
      alert("Terjadi kesalahan saat menghapus data.");
    }
  }
};

// 1. Variabel State untuk Modal Edit
const showEditModal = ref(false);
const editForm = ref({
  id: null as number | null,
  name: '',
  email: '',
  role: '',
  position: '',
  company_id: '' as string | number,
});

// 2. Fungsi saat tombol Edit (pensil) diklik
const handleEditMaster = (member: any) => {
  // Masukkan data member yang diklik ke dalam form
  editForm.value = {
    id: member.id,
    name: member.name,
    email: member.email,
    role: member.role || 'member',
    position: member.position || '',
    company_id: member.company_id || '',
  };
  // Buka modalnya
  showEditModal.value = true;
};

// 3. Fungsi untuk menyimpan ke Backend
const submitEditMember = async () => {
  try {
    // Tembak API PUT untuk update data
    await api.put(`/users/${editForm.value.id}`, editForm.value);
    
    alert("Data personel berhasil diperbarui!");
    showEditModal.value = false; // Tutup modal
    
    await fetchData(); // Refresh data layar agar langsung berubah
  } catch (error) {
    console.error("Gagal update data:", error);
    alert("Terjadi kesalahan saat memperbarui data personel.");
  }
};

const openFinanceModal = (item: any) => {
  alert(`Buka monitoring finansial untuk: ${item.name}\nSaldo Outstanding: ${formatCurrency(item.outstanding)}`);
};
// Tambahkan di bagian Script Setup
// ==========================================
// NEW: LOGIC ANALYTICS DETAIL
// ==========================================

// 1. Menghitung distribusi project per PT (untuk Chart)
const ptDistributionData = computed(() => {
  return allCompanies.value.map(pt => {
    // Filter staff dengan konversi eksplisit ke Number
    const staffCount = individuals.value.filter(i => {
      // debugger
      // Logic: Pastikan company_id ada, lalu bandingkan nilainya secara paksa (Number)
      if (i.company_id === null || i.company_id === undefined) return false;
      
      const isMatch = Number(i.company_id) === Number(pt.id);
      
      // Log untuk debug di console browser
      if (isMatch) console.log(`Berhasil match: ${i.name} masuk ke ${pt.name}`);
      
      return isMatch;
    }).length;
    
    const totalGlobal = individuals.value.length || 1;

    return {
      name: pt.name,
      staff: staffCount,
      // Visual bar: Minimal 25% kalau ada orang biar kelihatan "hidup"
      power: staffCount > 0 ? Math.max((staffCount / totalGlobal) * 100, 25) : 0
    };
  });
});

// 2. Menghitung total beban kasbon grup
const totalOutstandingGroup = computed(() => {
  return individuals.value.reduce((sum, item) => sum + (parseFloat(item.outstanding) || 0), 0);
});

// 3. Status Kesehatan Finansial Tim
const healthStatus = computed(() => {
  const ratio = totalOutstandingGroup.value / (totalProjectCount.value * 10000000); // Rasio sederhana
  if (ratio < 0.2) return { label: 'Excellent', class: 'text-emerald-400', icon: 'fa-shield-check' };
  if (ratio < 0.5) return { label: 'Warning', class: 'text-amber-400', icon: 'fa-exclamation-triangle' };
  return { label: 'Critical', class: 'text-rose-400', icon: 'fa-skull-crossbones' };
});
// Tambahkan di Script Setup
const showDrilldownModal = ref(false);
const selectedPTDetail = ref<any>(null);

const openPTDrilldown = (ptName: string) => {
  const pt = allCompanies.value.find(c => c.name === ptName);
  if (!pt) return;

  // Filter anggota yang berafiliasi dengan PT ini
  const members = individuals.value.filter(i => Number(i.company_id) === Number(pt.id));

  // Mockup Project Aktif (Bisa diintegrasikan dengan API projects)
  const activeProjects = members.length > 0 ? 3 : 0; 

  // Kalkulasi estimasi reimburse/spending dari PT ini (Mockup integrasi)
  const totalReimburse = members.reduce((sum, m) => sum + (parseFloat(m.outstanding) || 0), 0);

  selectedPTDetail.value = {
    ...pt,
    members: members,
    project_count: activeProjects,
    total_reimburse: totalReimburse,
  };
  
  showDrilldownModal.value = true;
};
onMounted(fetchData);

// ==========================================
// TAB: DATA (Filtering & Pagination)
// ==========================================
// Gunakan object untuk melacak tipe filter (pt atau role) dan valuenya
const activeFilter = ref({ type: 'all', value: null as any });
const openMenu = ref<string | null>('pt'); // Default buka menu PT
const currentPage = ref(1);
const itemsPerPage = ref(6);

// Fungsi set filter dan otomatis reset ke halaman 1
const setFilter = (type: string, value: any) => {
  activeFilter.value = { type, value };
  currentPage.value = 1; 
};

// Fungsi buka-tutup akordeon menu di sidebar
const toggleMenu = (menuName: string) => {
  openMenu.value = openMenu.value === menuName ? null : menuName;
};

// Computed untuk memfilter data berdasarkan Sidebar Navigasi
const filteredTeamData = computed(() => {
  if (activeFilter.value.type === 'all') return individuals.value;

  return individuals.value.filter((member: any) => {
    // 1. Jika filter berdasarkan PT
    if (activeFilter.value.type === 'pt') {
      if (activeFilter.value.value === null) {
        return member.company_id === null || member.company_id === undefined; // Independent
      }
      return Number(member.company_id) === Number(activeFilter.value.value);
    }
    
    // 2. Jika filter berdasarkan ROLE (Super Admin, Admin, Member)
    if (activeFilter.value.type === 'role') {
      return member.role === activeFilter.value.value;
    }

    return true;
  });
});

// --- LOGIC PAGINATION (Tetap Sama) ---
const totalPages = computed(() => {
  return Math.ceil(filteredTeamData.value.length / itemsPerPage.value) || 1;
});

const paginatedTeamData = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value;
  const end = start + itemsPerPage.value;
  return filteredTeamData.value.slice(start, end);
});

const nextPage = () => { if (currentPage.value < totalPages.value) currentPage.value++; };
const prevPage = () => { if (currentPage.value > 1) currentPage.value--; };
const goToPage = (page: number) => { currentPage.value = page; };

// ==========================================
// TAB: SETUP (Teamwork & Project Linkage)
// ==========================================
const activeSetupMenu = ref('project_assignment');

// Data dummy sementara untuk Projects (Nanti kamu sambungkan ke endpoint /projects)
const availableProjects = ref<any[]>([]);

// Form untuk assign PT ke Project
const assignForm = ref({
  company_id: '',
  project_id: ''
});

// Fungsi Fetch Projects Khusus untuk Setup
const fetchSetupData = async () => {
  try {
    const res = await api.get('/projects');
    
    // Bantuan Debugging: Cek di Inspect -> Console untuk melihat bentuk datanya
    console.log("Cek Data Projects dari Backend:", res.data); 

    // LOGIC AMAN: Mengakomodasi jika Laravel mengirim `res.data` (polosan) ATAU `res.data.data` (pagination/resource)
    availableProjects.value = res.data.data || res.data || [];
    
  } catch (e) {
    console.error("Gagal load projects untuk setup", e);
  }
};

// Panggil fungsi ini jika user masuk ke tab setup
watch(currentTab, (newTab) => {
  if (newTab === 'setup' && availableProjects.value.length === 0) {
    fetchSetupData();
  }
}, { immediate: true }); // <--- TAMBAHAN PENTING: immediate true memastikan fungsi berjalan langsung jika halaman di-refresh di tab ini

// Fungsi Save Assignment
const handleAssignProject = async () => {
  if (!assignForm.value.company_id || !assignForm.value.project_id) {
    alert("Pilih PT dan Project terlebih dahulu!");
    return;
  }
  
  try {
    // LOGIKA BACKEND: Kamu perlu membuat endpoint ini di ProjectController
    // Endpoint ini akan meng-update 'company_id' pada baris project yang dipilih.
    await api.put(`/projects/${assignForm.value.project_id}/assign-company`, {
      company_id: assignForm.value.company_id
    });
    
    alert("Project berhasil di-assign ke PT!");
    assignForm.value = { company_id: '', project_id: '' };
    await fetchSetupData(); // Refresh data
  } catch (e) {
    console.error(e);
    alert("Gagal assign project. Pastikan endpoint backend sudah siap.");
  }
};

// Fungsi untuk Memutuskan Hubungan Project dari PT
const handleUnassignProject = async (projectId: number) => {
  if (!confirm("Apakah Anda yakin ingin memutuskan (unlink) PT dari Project ini?")) {
    return;
  }
  
  try {
    await api.put(`/projects/${projectId}/unassign-company`);
    alert("Project berhasil dilepas dari PT!");
    await fetchSetupData(); // Refresh tabel otomatis
  } catch (e) {
    console.error("Gagal unassign project", e);
    alert("Gagal memproses data. Pastikan endpoint backend sudah siap.");
  }
};

</script>
<template>
  <div class="min-h-screen bg-[#F8FAFC] pb-20 md:pl-20 font-sans overflow-x-hidden text-slate-900">
    <div class="max-w-7xl mx-auto px-6 mt-8">
      
      <!-- HEADER (Identik dengan Works.vue) -->
      <div class="bg-white border-x border-t border-slate-200 rounded-t-3xl pt-8 pb-4 relative shadow-sm">
        <div class="flex items-center justify-between px-8">
          <div class="flex items-center gap-6">
            <div class="w-16 h-16 bg-indigo-600 rounded-2xl flex items-center justify-center shadow-lg absolute -bottom-10 left-8 z-10">
              <i class="fas fa-users-cog text-3xl text-white"></i>
            </div>
            <div class="ml-20">
              <h1 class="text-[26px] font-black text-[#2E3A8C] tracking-tighter leading-none mb-1 uppercase">Teamwork</h1>
              <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em]">Resource & Human Capital</p>
            </div>
          </div>
          <button @click="showAddModal = true" class="bg-indigo-600 text-white px-5 py-2.5 rounded-xl text-[11px] font-black uppercase tracking-widest shadow-lg active:scale-95 transition-all">
            <i class="fas fa-plus mr-2"></i> Add Team Member
          </button>
        </div>
      </div>

      <!-- NAVIGATION TABS -->
      <div class="bg-[#F1F5F9] border-x border-y border-slate-200 py-3 px-8 md:pl-[120px]">
        <div class="flex gap-8">
          <button v-for="tab in ['analytics', 'entities', 'setup']" :key="tab"
            @click="currentTab = tab" 
            class="group text-[11px] font-bold flex items-center gap-2 transition-all capitalize"
            :class="currentTab === tab ? 'text-[#2E3A8C]' : 'text-slate-400'">
            <div class="w-4 h-4 rounded bg-slate-200 flex items-center justify-center text-[8px]" 
                 :class="currentTab === tab ? 'bg-[#2E3A8C] text-white' : ''">
              <i class="fas fa-check"></i>
            </div>
            {{ tab }}
          </button>
        </div>
      </div>

      <!-- CONTENT: ANALYTICS -->
      <div v-if="currentTab === 'analytics'" class="animate-in fade-in zoom-in duration-700 py-8 space-y-10 pb-24">
        
        <!-- ROW 1: CORE METRICS -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
          <div v-for="s in stats" :key="s.label" 
              class="p-8 rounded-[2.5rem] text-white shadow-2xl relative overflow-hidden group transition-all hover:-translate-y-2"
              :class="s.color">
            <div class="absolute inset-0 bg-white/10 opacity-0 group-hover:opacity-100 transition-opacity"></div>
            <i class="fas opacity-10 text-7xl absolute -right-4 -bottom-4 transform group-hover:scale-110 transition-transform" :class="s.icon"></i>
            
            <p class="text-[10px] font-black opacity-70 uppercase tracking-[0.2em] mb-4">{{ s.label }}</p>
            <div class="flex items-baseline gap-2">
              <h3 class="text-4xl font-black italic tracking-tighter">{{ s.value }}</h3>
              <span class="text-[10px] font-bold opacity-60">Units</span>
            </div>
          </div>
        </div>

        <!-- ROW 2: INTERACTIVE POWER MATRIX -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
          
          <!-- LEFT: PT POWER DISTRIBUTION -->
          <div class="lg:col-span-8 bg-white border border-slate-200 rounded-[3.5rem] p-12 shadow-sm relative overflow-hidden">
            <div class="absolute top-0 right-0 p-10 opacity-[0.03] pointer-events-none">
              <i class="fas fa-network-wired text-[15rem]"></i>
            </div>

            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-12 gap-4">
              <div>
                <h3 class="text-2xl font-black text-slate-800 uppercase italic tracking-tighter">Organization Power Distribution</h3>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">Resource allocation mapping for 7 Entities</p>
              </div>
              <div class="px-5 py-2 bg-indigo-50 rounded-full border border-indigo-100 text-[9px] font-black text-indigo-600 uppercase tracking-widest">
                Live Sync: Active
              </div>
            </div>

            <!-- POWER BARS (Dynamic Visual) -->
            <div class="space-y-8 relative z-10">
              <div v-for="pt in ptDistributionData" :key="pt.name" 
                @click="openPTDrilldown(pt.name)"
                class="space-y-3 group cursor-pointer hover:bg-slate-50 p-2 rounded-2xl transition-all">
                <div class="flex justify-between items-end">
                  <div class="flex items-center gap-3">
                    <div class="w-2 h-2 rounded-full bg-indigo-500 shadow-[0_0_10px_rgba(99,102,241,0.5)]"></div>
                    <span class="text-[11px] font-black text-slate-700 uppercase tracking-tight">{{ pt.name }}</span>
                  </div>
                  <span class="text-[10px] font-bold text-slate-400 uppercase">{{ pt.staff }} Personnel Assigned</span>
                </div>
                <div class="h-4 bg-slate-50 rounded-full overflow-hidden border border-slate-100 shadow-inner">
                  <div class="h-full bg-gradient-to-r from-indigo-500 to-blue-400 transition-all duration-1000 ease-out group-hover:brightness-110"
                      :style="{ width: pt.power + '%' }">
                    <div class="w-full h-full animate-pulse bg-white/10"></div>
                  </div>
                </div>
              </div>
            </div>

            <div class="mt-12 pt-8 border-t border-slate-50 flex gap-10">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm shadow-sm">
                  <i class="fas fa-bolt"></i>
                </div>
                <div>
                  <p class="text-[14px] font-black text-slate-800 italic leading-none">High Productivity</p>
                  <p class="text-[8px] font-bold text-slate-400 uppercase mt-1">Status Overview</p>
                </div>
              </div>
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm shadow-sm">
                  <i class="fas fa-users-cog"></i>
                </div>
                <div>
                  <p class="text-[14px] font-black text-slate-800 italic leading-none">{{ individuals.length }} Experts</p>
                  <p class="text-[8px] font-bold text-slate-400 uppercase mt-1">Total Manpower</p>
                </div>
              </div>
            </div>
          </div>
          
          <!-- RIGHT: FINANCIAL RISK SIDEBAR -->
          <div class="lg:col-span-4 space-y-8">
            
            <!-- CARD: GROUP SETTLEMENT HEALTH -->
            <div class="bg-slate-900 rounded-[3rem] p-10 text-white shadow-2xl relative overflow-hidden group">
              <div class="absolute inset-0 bg-gradient-to-br from-indigo-600/20 to-transparent"></div>
              
              <div class="relative z-10">
                <div class="flex justify-between items-start mb-8">
                  <h3 class="text-sm font-black uppercase text-indigo-400 italic tracking-widest">
                    <i class="fas fa-wallet mr-2"></i> Debt Control
                  </h3>
                  <i class="fas fa-fingerprint text-slate-700 text-2xl"></i>
                </div>

                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Total Group Outstanding</p>
                <h4 class="text-3xl font-black italic tracking-tighter mb-6 text-rose-400">
                  {{ formatCurrency(totalOutstandingGroup) }}
                </h4>

                <div class="bg-white/5 border border-white/10 rounded-3xl p-6 space-y-4">
                  <div class="flex justify-between items-center">
                    <span class="text-[9px] font-black uppercase text-slate-400">Team Health</span>
                    <span :class="healthStatus.class" class="text-[10px] font-black uppercase flex items-center gap-2">
                      <i class="fas" :class="healthStatus.icon"></i> {{ healthStatus.label }}
                    </span>
                  </div>
                  <div class="h-1.5 bg-white/5 rounded-full overflow-hidden">
                    <div class="h-full bg-indigo-500 transition-all duration-1000" :style="{ width: '65%' }"></div>
                  </div>
                </div>
              </div>
            </div>

            <!-- CARD: TOP DEBTORS LIST -->
            <div class="bg-white border border-slate-200 rounded-[3rem] p-10 shadow-sm">
              <h3 class="text-[11px] font-black text-slate-800 uppercase italic tracking-widest mb-8 border-b border-slate-50 pb-4">
                Top Settlement Required
              </h3>
              
              <div class="space-y-6">
                  <div v-for="item in topOutstanding" :key="item.id" class="flex justify-between items-center group cursor-pointer hover:bg-slate-50 p-2 rounded-2xl transition-all">
                      <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-xl bg-slate-900 text-white flex items-center justify-center font-black text-[10px]">
                          {{ item.member_name.substring(0,2).toUpperCase() }}
                        </div>
                        <div>
                          <p class="text-[11px] font-black uppercase text-slate-700 tracking-tight group-hover:text-indigo-600 transition-colors">
                              {{ item.member_name }}
                          </p>
                          <p class="text-[8px] text-slate-400 uppercase font-bold tracking-tighter">
                              {{ item.project_title || 'General / Internal' }}
                          </p>
                        </div>
                      </div>
                      <div class="text-right">
                          <p class="text-rose-500 font-black text-[12px] italic">{{ formatCurrency(item.amount) }}</p>
                      </div>
                  </div>

                  <div v-if="topOutstanding.length === 0" class="text-center py-12 opacity-20">
                      <i class="fas fa-box-open text-4xl mb-4"></i>
                      <p class="text-[10px] font-bold uppercase tracking-widest">No outstanding</p>
                  </div>

                  <button class="w-full bg-slate-50 hover:bg-slate-100 text-slate-400 py-4 rounded-2xl text-[9px] font-black uppercase tracking-[0.2em] transition-all border border-slate-100">
                      View All Records
                  </button>
              </div>
            </div>

          </div>
        </div>

        <!-- DRILL-DOWN MODAL -->
      <div v-if="showDrilldownModal" class="fixed inset-0 z-[150] flex items-center justify-center p-6 bg-slate-900/60 backdrop-blur-md">
        <div class="bg-white w-full max-w-4xl rounded-[3rem] shadow-2xl overflow-hidden animate-in zoom-in duration-300">
          
          <!-- Modal Header -->
          <div class="p-10 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
            <div class="flex items-center gap-6">
              <div class="w-16 h-16 bg-slate-900 rounded-2xl flex items-center justify-center text-white text-2xl shadow-xl">
                <i class="fas fa-building"></i>
              </div>
              <div>
                <h3 class="text-2xl font-black text-slate-800 uppercase italic">{{ selectedPTDetail?.name }}</h3>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ selectedPTDetail?.legal_name }}</p>
              </div>
            </div>
            <button @click="showDrilldownModal = false" class="w-12 h-12 rounded-full hover:bg-slate-200 transition-colors flex items-center justify-center">
              <i class="fas fa-times text-slate-400"></i>
            </button>
          </div>

          <!-- Modal Content -->
          <div class="p-10 grid grid-cols-1 md:grid-cols-3 gap-10">
            
            <!-- Stats Summary -->
            <div class="space-y-6">
              <div class="bg-indigo-600 p-6 rounded-[2rem] text-white">
                <p class="text-[9px] font-black uppercase opacity-60 tracking-widest mb-1">Active Projects</p>
                <h4 class="text-3xl font-black italic">{{ selectedPTDetail?.project_count }} Units</h4>
              </div>
              <div class="bg-emerald-500 p-6 rounded-[2rem] text-white">
                <p class="text-[9px] font-black uppercase opacity-60 tracking-widest mb-1">Reimburse Estimation</p>
                <h4 class="text-2xl font-black italic">{{ formatCurrency(selectedPTDetail?.total_reimburse) }}</h4>
              </div>
            </div>

            <!-- Members List -->
            <div class="md:col-span-2 space-y-6">
              <h4 class="text-xs font-black uppercase text-slate-400 tracking-widest border-b border-slate-50 pb-4">Assigned Experts</h4>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-h-[300px] overflow-y-auto pr-2">
                <div v-for="m in selectedPTDetail?.members" :key="m.id" class="flex items-center gap-4 p-4 bg-slate-50 rounded-2xl border border-slate-100">
                  <div class="w-10 h-10 rounded-xl bg-white flex items-center justify-center font-black text-indigo-600 shadow-sm uppercase">
                    {{ m.name.substring(0,2) }}
                  </div>
                  <div>
                    <p class="text-[11px] font-black text-slate-800 uppercase">{{ m.name }}</p>
                    <p class="text-[9px] font-bold text-slate-400 uppercase italic">{{ m.position || 'Specialist' }}</p>
                  </div>
                </div>
                <div v-if="selectedPTDetail?.members.length === 0" class="col-span-2 py-10 text-center opacity-30">
                  <p class="text-[10px] font-black uppercase tracking-widest">No members assigned to this entity</p>
                </div>
              </div>
            </div>

          </div>

          <!-- Modal Footer -->
          <div class="p-8 bg-slate-50 flex justify-end">
            <button class="bg-slate-900 text-white px-8 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-lg hover:scale-95 transition-all">
              View Detailed Audit Trail
            </button>
          </div>
        </div>
      </div>
      </div>

      <!-- TAB CONTENT: MEMBERS (SIDEBAR DROPDOWN & CARDS PAGINATION) -->
      <div v-if="currentTab === 'entities'" class="grid grid-cols-1 lg:grid-cols-12 gap-8 animate-in fade-in duration-500 py-4">
        
        <!-- LEFT: NAVIGATION SIDEBAR (Projects.vue Style Matched) -->
        <div class="lg:col-span-3">
          <div class="bg-white border border-slate-200 rounded-[2.5rem] p-6 shadow-sm sticky top-8">
            <h4 class="text-[10px] font-black uppercase text-indigo-900 mb-6 tracking-widest pl-2">
              Navigation
            </h4>

            <div class="space-y-4">
              
              <!-- BIG BLUE BUTTON (ALL PERSONNEL) -->
              <button @click="setFilter('all', null)"
                class="w-full flex items-center justify-center gap-3 px-5 py-3.5 rounded-2xl text-[11px] font-black uppercase tracking-widest transition-all shadow-md"
                :class="activeFilter.type === 'all' ? 'bg-indigo-600 text-white shadow-indigo-200' : 'bg-slate-50 text-slate-500 hover:bg-slate-100 hover:text-indigo-600'">
                <i class="fas fa-layer-group"></i> All Personnel
              </button>

              <!-- ACCORDION 1: AFFILIATED PT -->
              <div class="pt-4 border-t border-slate-50">
                <button @click="toggleMenu('pt')" class="w-full flex justify-between items-center px-2 mb-3 group">
                  <div class="flex items-center gap-3 text-[10px] font-black text-slate-600 uppercase tracking-widest group-hover:text-indigo-600 transition-colors">
                    <i class="fas fa-folder text-indigo-600"></i> Affiliated PT
                  </div>
                  <i class="fas fa-chevron-down text-[10px] text-slate-400 transition-transform" :class="openMenu === 'pt' ? 'rotate-180' : ''"></i>
                </button>
                
                <!-- Dropdown List PT (With custom thin scrollbar class if you have one, or just clean padding) -->
                <div v-if="openMenu === 'pt'" class="space-y-2 pl-4 mt-3 animate-in slide-in-from-top-2 duration-300 max-h-[250px] overflow-y-auto overflow-x-hidden pr-2 custom-scrollbar">
                  
                  <!-- List PT dari Database -->
                  <button v-for="cp in allCompanies" :key="cp.id"
                    @click="setFilter('pt', cp.id)"
                    class="w-full text-left py-1 text-[10px] font-bold transition-all flex items-center gap-3 group/item"
                    :class="activeFilter.type === 'pt' && activeFilter.value === cp.id ? 'text-indigo-600' : 'text-slate-500 hover:text-indigo-600'">
                    <span class="text-slate-300 group-hover/item:text-indigo-300 transition-colors">—</span> 
                    <span class="truncate uppercase tracking-tight">{{ cp.name }}</span>
                  </button>
                  
                  <!-- Independent -->
                  <button @click="setFilter('pt', null)"
                    class="w-full text-left py-1 text-[10px] font-bold transition-all flex items-center gap-3 group/item mt-2 pt-2 border-t border-slate-50"
                    :class="activeFilter.type === 'pt' && activeFilter.value === null ? 'text-indigo-600' : 'text-slate-500 hover:text-indigo-600'">
                    <span class="text-slate-300 group-hover/item:text-indigo-300 transition-colors">—</span> 
                    <span class="truncate uppercase tracking-tight">Independent / No PT</span>
                  </button>
                </div>
              </div>

              <!-- ACCORDION 2: ROLE / SPECIALTY -->
              <div class="pt-4 border-t border-slate-50">
                <button @click="toggleMenu('role')" class="w-full flex justify-between items-center px-2 mb-3 group">
                  <div class="flex items-center gap-3 text-[10px] font-black text-slate-600 uppercase tracking-widest group-hover:text-indigo-600 transition-colors">
                    <i class="fas fa-folder text-indigo-600"></i> Role / Position
                  </div>
                  <i class="fas fa-chevron-down text-[10px] text-slate-400 transition-transform" :class="openMenu === 'role' ? 'rotate-180' : ''"></i>
                </button>
                
                <!-- Dropdown List Role -->
                <div v-if="openMenu === 'role'" class="space-y-2 pl-4 mt-3 animate-in slide-in-from-top-2 duration-300">
                  <button v-for="r in [{id: 'super_admin', label: 'Super Admin'}, {id: 'admin', label: 'Admin'}, {id: 'member', label: 'Member'}]" :key="r.id"
                    @click="setFilter('role', r.id)"
                    class="w-full text-left py-1 text-[10px] font-bold transition-all flex items-center gap-3 group/item"
                    :class="activeFilter.type === 'role' && activeFilter.value === r.id ? 'text-indigo-600' : 'text-slate-500 hover:text-indigo-600'">
                    <span class="text-slate-300 group-hover/item:text-indigo-300 transition-colors">—</span> 
                    <span class="uppercase tracking-tight">{{ r.label }}</span>
                  </button>
                </div>
              </div>

            </div>
          </div>
        </div>

        <!-- RIGHT: PERSONNEL CARDS GRID WITH PAGINATION -->
        <div class="lg:col-span-9 flex flex-col min-h-[500px]">
          
          <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 items-start content-start flex-1">
            
            <!-- PERHATIKAN: Sekarang looping-nya pakai paginatedTeamData -->
            <div v-for="m in paginatedTeamData" :key="m.id" 
              class="bg-white border border-slate-200 p-6 rounded-[2.5rem] shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all group relative overflow-hidden flex flex-col justify-between h-[210px]">
              
              <!-- Identitas Utama -->
              <div class="flex items-center gap-4 relative z-10">
                <div class="w-14 h-14 rounded-[1.2rem] bg-indigo-50 flex items-center justify-center text-indigo-600 text-lg font-black shadow-inner uppercase border border-indigo-100 group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                  <img v-if="m.avatar_url" :src="m.avatar_url" class="w-full h-full object-cover rounded-[1.2rem]">
                  <span v-else>{{ m.name.substring(0,2) }}</span>
                </div>
                <div class="flex-1 min-w-0">
                  <h5 class="text-[13px] font-black text-slate-800 uppercase tracking-tight truncate">{{ m.name }}</h5>
                  <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-0.5 truncate">{{ m.position || m.role || 'Staff' }}</p>
                </div>
              </div>

              <!-- Info Afiliasi, Email & Kasbon -->
              <div class="mt-4 pt-4 border-t border-slate-50 space-y-2.5 relative z-10">
                <div class="flex justify-between items-center">
                  <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest">Affiliation</span>
                  <span class="text-[9px] font-black text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded border border-indigo-100 uppercase truncate max-w-[120px]">
                    {{ m.pt_owner_name || 'Independent' }}
                  </span>
                </div>
                <!-- BARIS EMAIL BARU -->
                <div class="flex justify-between items-center">
                  <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest">Email</span>
                  <!-- Menggunakan lowercase agar bentuk email terlihat natural (tidak huruf besar semua) -->
                  <span class="text-[9px] font-bold text-slate-500 lowercase truncate max-w-[140px]" :title="m.email">
                    {{ m.email || 'no-email@system.com' }}
                  </span>
                </div>
                <div class="flex justify-between items-center">
                  <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest">Debt / Kasbon</span>
                  <span class="text-[10px] font-black italic" :class="m.outstanding > 0 ? 'text-rose-500' : 'text-emerald-500'">
                    {{ formatCurrency(m.outstanding) }}
                  </span>
                </div>
              </div>

              <!-- OVERLAY TOMBOL ACTION -->
              <div class="absolute inset-x-0 bottom-0 p-6 bg-gradient-to-t from-white via-white to-transparent translate-y-[120%] group-hover:translate-y-0 transition-transform duration-300 ease-out flex justify-center gap-3 z-20 rounded-b-[2.5rem]">
                <button @click="openFinanceModal(m)" class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white transition-all shadow-sm flex items-center justify-center">
                    <i class="fas fa-wallet text-[11px]"></i>
                </button>
                <button @click="handleEditMaster(m)" class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white transition-all shadow-sm flex items-center justify-center">
                    <i class="fas fa-edit text-[11px]"></i>
                </button>
                <button @click="handleDeleteMember(m.id)" class="w-10 h-10 rounded-xl bg-rose-50 text-rose-500 hover:bg-rose-600 hover:text-white transition-all shadow-sm flex items-center justify-center">
                    <i class="fas fa-trash text-[11px]"></i>
                </button>
              </div>
            </div>

            <!-- EMPTY STATE JIKA FILTER KOSONG -->
            <div v-if="filteredTeamData.length === 0" class="col-span-full py-24 text-center opacity-30 grayscale border-2 border-dashed border-slate-200 rounded-[3rem]">
                <i class="fas fa-users-slash text-5xl mb-4 text-slate-300"></i>
                <p class="text-[11px] font-black uppercase tracking-widest text-slate-500">No personnel found</p>
            </div>

          </div>

          <!-- PAGINATION CONTROLS -->
          <div v-if="totalPages > 1" class="mt-8 pt-6 border-t border-slate-200 flex flex-col md:flex-row justify-between items-center gap-4">
              <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">
                  Showing <span class="text-indigo-600">{{ (currentPage - 1) * itemsPerPage + 1 }}</span> to 
                  <span class="text-indigo-600">{{ Math.min(currentPage * itemsPerPage, filteredTeamData.length) }}</span> 
                  of <span class="text-slate-700">{{ filteredTeamData.length }}</span> entries
              </p>
              
              <div class="flex items-center gap-2">
                  <!-- Prev Button -->
                  <button @click="prevPage" :disabled="currentPage === 1" 
                          class="w-8 h-8 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-500 hover:bg-indigo-50 hover:text-indigo-600 hover:border-indigo-200 disabled:opacity-30 disabled:cursor-not-allowed transition-all shadow-sm">
                      <i class="fas fa-chevron-left text-[10px]"></i>
                  </button>
                  
                  <!-- Page Numbers -->
                  <div class="flex items-center gap-1">
                      <button v-for="page in totalPages" :key="page" @click="goToPage(page)"
                              class="w-8 h-8 flex items-center justify-center rounded-xl text-[10px] font-black transition-all shadow-sm"
                              :class="currentPage === page ? 'bg-indigo-600 text-white shadow-indigo-200 border-transparent' : 'bg-white border border-slate-200 text-slate-500 hover:bg-slate-50'">
                          {{ page }}
                      </button>
                  </div>

                  <!-- Next Button -->
                  <button @click="nextPage" :disabled="currentPage === totalPages"
                          class="w-8 h-8 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-500 hover:bg-indigo-50 hover:text-indigo-600 hover:border-indigo-200 disabled:opacity-30 disabled:cursor-not-allowed transition-all shadow-sm">
                      <i class="fas fa-chevron-right text-[10px]"></i>
                  </button>
              </div>
          </div>

        </div>
      </div>

      <!-- TAB CONTENT: SETUP (Master Data & Linkage) -->
      <div v-if="currentTab === 'setup'" class="grid grid-cols-1 lg:grid-cols-12 gap-8 animate-in fade-in duration-500 py-4">
        
        <!-- LEFT: SETUP NAVIGATION -->
        <div class="lg:col-span-3">
          <div class="bg-white border border-slate-200 rounded-[2.5rem] p-6 shadow-sm sticky top-8">
            <h4 class="text-[10px] font-black uppercase text-indigo-900 mb-6 tracking-widest pl-2">
              Navigation
            </h4>

            <div class="space-y-1">
              <!-- Menu 1: Project Assignment -->
              <button @click="activeSetupMenu = 'project_assignment'"
                class="w-full text-left px-5 py-3.5 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all flex items-center gap-3"
                :class="activeSetupMenu === 'project_assignment' ? 'bg-indigo-50 text-indigo-600 shadow-inner' : 'text-slate-400 hover:bg-slate-50'">
                <i class="fas fa-link w-4"></i> Project Assign
              </button>

              <!-- Menu 2: Job Positions -->
              <button @click="activeSetupMenu = 'job_positions'"
                class="w-full text-left px-5 py-3.5 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all flex items-center gap-3"
                :class="activeSetupMenu === 'job_positions' ? 'bg-indigo-50 text-indigo-600 shadow-inner' : 'text-slate-400 hover:bg-slate-50'">
                <i class="fas fa-user-tag w-4"></i> Job Positions
              </button>

              <!-- Menu 3: Finance Types -->
              <button @click="activeSetupMenu = 'finance_types'"
                class="w-full text-left px-5 py-3.5 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all flex items-center gap-3"
                :class="activeSetupMenu === 'finance_types' ? 'bg-indigo-50 text-indigo-600 shadow-inner' : 'text-slate-400 hover:bg-slate-50'">
                <i class="fas fa-file-invoice-dollar w-4"></i> Finance Types
              </button>
            </div>
          </div>
        </div>

        <!-- RIGHT: SETUP WORKSPACE -->
        <div class="lg:col-span-9 space-y-6">
          
          <!-- HEADER -->
          <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-sm flex items-center gap-4 text-indigo-900">
            <div class="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-lg">
              <i class="fas fa-th-large"></i>
            </div>
            <h3 class="text-sm font-black uppercase tracking-widest">
              TEAMWORK SETUP / {{ activeSetupMenu.replace('_', ' ') }}
            </h3>
          </div>

          <!-- CONTENT: PROJECT ASSIGNMENT -->
          <template v-if="activeSetupMenu === 'project_assignment'">
            
            <!-- FORM INPUT -->
            <div class="bg-white border border-slate-200 rounded-3xl p-8 shadow-sm space-y-6">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                  <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Select Entity (PT)</label>
                  <select v-model="assignForm.company_id" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-4 text-xs font-bold outline-none uppercase appearance-none focus:ring-2 ring-indigo-100 transition-all cursor-pointer">
                    <option value="" disabled>-- CHOOSE AFFILIATED PT --</option>
                    <option v-for="cp in allCompanies" :key="cp.id" :value="cp.id">{{ cp.name }}</option>
                  </select>
                </div>
                <div class="space-y-2">
                  <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Select Active Project</label>
                  <select v-model="assignForm.project_id" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-4 text-xs font-bold outline-none uppercase appearance-none focus:ring-2 ring-indigo-100 transition-all cursor-pointer">
                    <option value="" disabled>-- CHOOSE PROJECT --</option>
                    <option v-for="pj in availableProjects" :key="pj.id" :value="pj.id">{{ pj.project_title }}</option>
                  </select>
                </div>
              </div>
              <div class="flex justify-end pt-2">
                <button @click="handleAssignProject" class="bg-indigo-900 text-white px-10 py-4 rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] shadow-xl shadow-indigo-100 hover:bg-indigo-800 transition-all">
                  Save Assignment
                </button>
              </div>
            </div>

            <!-- DATA TABLE -->
            <div class="bg-white border border-slate-200 rounded-3xl overflow-hidden shadow-sm">
              <table class="w-full text-left">
                <thead class="bg-slate-50 border-b border-slate-100">
                  <tr class="text-[9px] font-black text-slate-400 uppercase tracking-widest">
                    <th class="p-6">Entity (PT) Handler</th>
                    <th class="p-6">Project Title</th>
                    <th class="p-6">Status</th>
                    <th class="p-6 text-right">Action</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                  <tr v-for="pj in availableProjects.filter(p => p.company_id)" :key="pj.id" class="hover:bg-slate-50/50 transition-colors group">
                    <td class="p-6 text-[11px] font-black text-indigo-600 uppercase">{{ pj.company_id ? (allCompanies.find(c => c.id === pj.company_id)?.name || 'Linked') : '-' }}</td>
                    <td class="p-6 text-[11px] font-bold text-slate-700 uppercase">{{ pj.project_title }}</td>
                    <td class="p-6">
                      <span class="text-[8px] font-black uppercase bg-emerald-50 text-emerald-600 px-2 py-1 rounded">Assigned</span>
                    </td>
                    <td class="p-6 text-right">
                    <button @click="handleUnassignProject(pj.id)" class="w-8 h-8 rounded-lg bg-slate-100 text-slate-400 hover:bg-rose-500 hover:text-white transition-all shadow-sm">
                      <i class="fas fa-unlink text-[10px]"></i>
                    </button>
                  </td>
                  </tr>
                  <tr v-if="availableProjects.filter(p => p.company_id).length === 0">
                    <td colspan="4" class="p-12 text-center text-slate-300 text-[10px] font-bold uppercase tracking-widest">No Projects Assigned Yet</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </template>

          <!-- CONTENT: JOB POSITIONS (Contoh placeholder) -->
          <template v-if="activeSetupMenu === 'job_positions'">
            <div class="bg-white border border-slate-200 rounded-3xl p-16 text-center shadow-sm">
              <i class="fas fa-tools text-5xl text-slate-200 mb-4"></i>
              <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest">Master Job Positions Setup</h4>
              <p class="text-[10px] text-slate-300 mt-2 uppercase">Coming Soon - Add standard roles like Developer, UI/UX, etc.</p>
            </div>
          </template>

        </div>
      </div>

    </div>
    <!-- MODAL ADD TEAM MEMBER -->
    <div v-if="showAddModal" class="fixed inset-0 z-[999] flex items-center justify-center p-6">
      <!-- Backdrop -->
      <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showAddModal = false"></div>
      
      <!-- Modal Card -->
      <div class="bg-white w-full max-w-lg rounded-[2.5rem] shadow-2xl relative z-10 overflow-hidden animate-in zoom-in duration-300">
        <div class="p-8 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
          <div>
            <h3 class="text-xl font-black text-slate-800 uppercase italic">Add New Entity</h3>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Register Team or Organization</p>
          </div>
          <button @click="showAddModal = false" class="text-slate-300 hover:text-rose-500 transition-colors">
            <i class="fas fa-times-circle text-2xl"></i>
          </button>
        </div>

        <div class="p-8 space-y-5">
          <!-- Entity Type Switcher -->
          <div class="flex bg-slate-100 p-1 rounded-2xl">
            <button v-for="t in ['individual', 'organization']" :key="t"
              @click="entityType = t"
              class="flex-1 py-2 text-[10px] font-black uppercase tracking-widest rounded-xl transition-all"
              :class="entityType === t ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-400'">
              {{ t }}
            </button>
          </div>

          <!-- Form Fields -->
          <div class="space-y-4">
            
            <!-- DYNAMIC FIELDS FOR INDIVIDUAL -->
            <template v-if="entityType === 'individual'">
                <div class="space-y-1">
                  <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Full Name</label>
                  <input v-model="teamworkForm.name" type="text" placeholder="E.G. JOHN DOE" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-3 text-xs font-bold outline-none focus:ring-2 ring-indigo-100 transition-all uppercase">
                </div>

                <div class="space-y-1">
                  <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Email Address</label>
                  <input v-model="teamworkForm.email" type="email" placeholder="JOHN@EXAMPLE.COM" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-3 text-xs font-bold outline-none uppercase">
                </div>

                <div class="grid grid-cols-2 gap-4">
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
            </template>

            <!-- DYNAMIC FIELDS FOR ORGANIZATION -->
            <template v-else>
                <div class="space-y-1">
                  <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Company / Entity Name</label>
                  <input v-model="teamworkForm.name" type="text" placeholder="E.G. PT. MAJU MUNDUR" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-3 text-xs font-bold outline-none focus:ring-2 ring-indigo-100 transition-all uppercase">
                </div>

                <div class="space-y-1">
                  <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Legal Registration Name</label>
                  <!-- Reusing the 'position' field to store legal_name temporarily before saving -->
                  <input v-model="teamworkForm.position" type="text" placeholder="E.G. PT. MAJU MUNDUR SEJAHTERA" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-3 text-xs font-bold outline-none uppercase">
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

    <!-- MODAL EDIT PERSONNEL -->
    <div v-if="showEditModal" class="fixed inset-0 z-[999] flex items-center justify-center p-4">
      <!-- Overlay/Background Hitam -->
      <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="showEditModal = false"></div>
      
      <!-- Modal Content -->
      <div class="bg-white rounded-[2.5rem] w-full max-w-md p-8 relative z-10 shadow-2xl animate-in zoom-in-95 duration-300">
        <div class="flex justify-between items-center mb-6">
          <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Edit Personnel</h3>
          <button @click="showEditModal = false" class="w-8 h-8 rounded-xl bg-slate-50 text-slate-400 hover:bg-rose-50 hover:text-rose-500 transition-all flex items-center justify-center">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <div class="space-y-5">
          <div class="space-y-2">
            <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Full Name</label>
            <input v-model="editForm.name" type="text" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-4 text-xs font-bold outline-none focus:ring-2 ring-indigo-100 transition-all">
          </div>
          
          <div class="space-y-2">
            <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Email</label>
            <input v-model="editForm.email" type="email" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-4 text-xs font-bold outline-none focus:ring-2 ring-indigo-100 transition-all">
          </div>

          <!-- PERBAIKAN: Grid diubah untuk menampung 3 inputan dengan rapi -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-2">
              <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Position / Title</label>
              <input v-model="editForm.position" type="text" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-4 text-xs font-bold outline-none uppercase focus:ring-2 ring-indigo-100 transition-all">
            </div>
            <div class="space-y-2">
              <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Affiliated PT</label>
              <select v-model="editForm.company_id" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-4 text-xs font-bold outline-none uppercase appearance-none focus:ring-2 ring-indigo-100 transition-all cursor-pointer">
                <option value="" disabled>-- Pilih PT --</option>
                <option v-for="cp in allCompanies" :key="cp.id" :value="cp.id">{{ cp.name }}</option>
              </select>
            </div>
            
            <!-- TAMBAHAN: Dropdown System Access Role -->
            <div class="space-y-2 md:col-span-2">
              <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">System Access Role</label>
              <select v-model="editForm.role" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-4 text-xs font-bold outline-none uppercase appearance-none focus:ring-2 ring-indigo-100 transition-all cursor-pointer">
                <option value="super_admin">Super Admin (Full Access)</option>
                <option value="admin">Admin</option>
                <option value="member">Member</option>
                <option value="staff">Staff / General</option>
              </select>
            </div>
          </div>

          <button @click="submitEditMember" class="w-full mt-4 bg-indigo-600 text-white px-5 py-4 rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] shadow-xl shadow-indigo-100 hover:bg-indigo-700 transition-all">
            Update Personnel
          </button>
        </div>
      </div>
    </div>
  </div>
</template>