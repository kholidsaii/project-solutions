<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import api from '../../api/axios'; // Pastikan path import axios Anda benar

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
}

const isLoading = ref(false);
const totalProjectCount = ref(0);

// Modal States
const showAddModal = ref(false);
const showEditModal = ref(false);
const showDrilldownModal = ref(false);

// Data States
const organizations = ref<any[]>([]); 
const individuals = ref<any[]>([]);   
const topOutstanding = ref<any[]>([]); 
const allCompanies = ref<any[]>([]);
const selectedPTDetail = ref<any>(null);

// Form States
const isEditing = ref(false);
const editingId = ref<number | null>(null);
const entityType = ref('individual');

const teamworkForm = ref<TeamworkForm>({
  name: '',
  email: '',
  password: '',
  role: 'member',
  position: '',
  company_id: null,
  status: 'Active'
});

const editForm = ref({
  id: null as number | null,
  name: '',
  email: '',
  role: '',
  position: '',
  company_id: '' as string | number,
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
// 2. COMPUTED STATS (Summary di atas halaman)
// ==========================================
const stats = computed(() => [
  { label: 'Total Entities', value: organizations.value.length + individuals.value.length, color: 'bg-indigo-600', icon: 'fa-users' },
  { label: 'Organizations', value: organizations.value.length, color: 'bg-blue-500', icon: 'fa-building' },
  { label: 'Individuals', value: individuals.value.length, color: 'bg-emerald-500', icon: 'fa-user-tie' },
  { label: 'Total Projects', value: totalProjectCount.value, color: 'bg-amber-500', icon: 'fa-project-diagram' },
]);

const ptDistributionData = computed(() => {
  return allCompanies.value.map(pt => {
    const staffCount = individuals.value.filter(i => {
      if (i.company_id === null || i.company_id === undefined) return false;
      return Number(i.company_id) === Number(pt.id);
    }).length;
    
    const totalGlobal = individuals.value.length || 1;
    return {
      name: pt.name,
      staff: staffCount,
      power: staffCount > 0 ? Math.max((staffCount / totalGlobal) * 100, 25) : 0
    };
  });
});

const totalOutstandingGroup = computed(() => {
  return individuals.value.reduce((sum, item) => sum + (parseFloat(item.outstanding) || 0), 0);
});

const healthStatus = computed(() => {
  const ratio = totalOutstandingGroup.value / ((totalProjectCount.value || 1) * 10000000); 
  if (ratio < 0.2) return { label: 'Excellent', class: 'text-emerald-400', icon: 'fa-shield-check' };
  if (ratio < 0.5) return { label: 'Warning', class: 'text-amber-400', icon: 'fa-exclamation-triangle' };
  return { label: 'Critical', class: 'text-rose-400', icon: 'fa-skull-crossbones' };
});

// ==========================================
// 3. API ACTIONS (CRUD)
// ==========================================
const fetchData = async () => {
  isLoading.value = true;
  try {
    const [resSummary, resTop, resComp] = await Promise.all([
      api.get('/teamwork/summary'),
      api.get('/teamwork/top-outstanding'),
      api.get('/companies')
    ]);

    organizations.value = resSummary.data.organizations || [];
    totalProjectCount.value = resSummary.data.total_projects || 0;
    individuals.value = resSummary.data.individuals || []; 
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
      if (!isEditing.value) payload.password = 'password123';
    } else {
      if (!teamworkForm.value.name) {
          alert("Nama PT wajib diisi.");
          return;
      }
      payload = {
          name: teamworkForm.value.name, 
          legal_name: teamworkForm.value.position || null,
      };
    }

    if (isEditing.value) {
      url = entityType.value === 'individual' ? `/users/${editingId.value}/role` : `/companies/${editingId.value}`;
      await api.put(url, payload);
    } else {
      await api.post(url, payload);
    }
    
    showAddModal.value = false;
    await fetchData(); 
    resetForm();
    alert(entityType.value === 'individual' ? "Member berhasil ditambah!" : "PT berhasil didaftarkan!");

  } catch (e: any) {
    console.error(e);
    if (e.response && e.response.status === 422) {
       const errors = e.response.data.errors;
       let errorMessage = "Cek kembali data Anda:\n";
       for (const key in errors) errorMessage += `- ${errors[key][0]}\n`;
       alert(errorMessage);
    } else if (e.response?.data?.error) {
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

const handleDeleteMember = async (id: number) => {
  if (!confirm("Apakah Anda yakin ingin menghapus personel ini? Data tidak dapat dikembalikan.")) return;

  try {
    await api.delete(`/users/${id}`);
    alert("Personel berhasil dihapus!");
    await fetchData(); 
  } catch (error: any) {
    if (error.response?.status === 500) {
      alert("Gagal menghapus: Personel ini mungkin masih terikat dengan data keuangan/kasbon atau project.");
    } else {
      alert("Terjadi kesalahan saat menghapus data.");
    }
  }
};

const handleEditMaster = (member: any) => {
  editForm.value = {
    id: member.id,
    name: member.name,
    email: member.email,
    role: member.role || 'member',
    position: member.position || '',
    company_id: member.company_id || '',
  };
  showEditModal.value = true;
};

const submitEditMember = async () => {
  try {
    await api.put(`/users/${editForm.value.id}`, editForm.value);
    alert("Data personel berhasil diperbarui!");
    showEditModal.value = false; 
    await fetchData(); 
  } catch (error) {
    alert("Terjadi kesalahan saat memperbarui data personel.");
  }
};

const openPTDrilldown = (ptName: string) => {
  const pt = allCompanies.value.find(c => c.name === ptName);
  if (!pt) return;
  const members = individuals.value.filter(i => Number(i.company_id) === Number(pt.id));
  const totalReimburse = members.reduce((sum, m) => sum + (parseFloat(m.outstanding) || 0), 0);

  selectedPTDetail.value = {
    ...pt,
    members: members,
    project_count: members.length > 0 ? 3 : 0, 
    total_reimburse: totalReimburse,
  };
  showDrilldownModal.value = true;
};

const openFinanceModal = (item: any) => {
  alert(`Buka monitoring finansial untuk: ${item.name}\nSaldo Outstanding: ${formatCurrency(item.outstanding)}`);
};

// ==========================================
// 4. FILTERING & PAGINATION (Data Grid)
// ==========================================
const activeFilter = ref({ type: 'all', value: null as any });
const openMenu = ref<string | null>('pt'); 
const currentPage = ref(1);
const itemsPerPage = ref(6);

const setFilter = (type: string, value: any) => {
  activeFilter.value = { type, value };
  currentPage.value = 1; 
};

const toggleMenu = (menuName: string) => {
  openMenu.value = openMenu.value === menuName ? null : menuName;
};

const filteredTeamData = computed(() => {
  if (activeFilter.value.type === 'all') return individuals.value;

  return individuals.value.filter((member: any) => {
    if (activeFilter.value.type === 'pt') {
      if (activeFilter.value.value === null) {
        return member.company_id === null || member.company_id === undefined; 
      }
      return Number(member.company_id) === Number(activeFilter.value.value);
    }
    if (activeFilter.value.type === 'role') {
      return member.role === activeFilter.value.value;
    }
    return true;
  });
});

const totalPages = computed(() => {
  return Math.ceil(filteredTeamData.value.length / itemsPerPage.value) || 1;
});

const paginatedTeamData = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value;
  return filteredTeamData.value.slice(start, start + itemsPerPage.value);
});

const nextPage = () => { if (currentPage.value < totalPages.value) currentPage.value++; };
const prevPage = () => { if (currentPage.value > 1) currentPage.value--; };
const goToPage = (page: number) => { currentPage.value = page; };

// ==========================================
// 5. LIFECYCLE HOOK
// ==========================================
onMounted(fetchData);

</script>

<template>
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 animate-in fade-in duration-500 pb-20 mt-4">
    
    <!-- SIDEBAR NAVIGATION (Kiri) -->
    <div class="lg:col-span-3 space-y-6">
      <div class="bg-white border border-slate-200 rounded-[2.5rem] p-6 shadow-sm sticky top-8">
        <h4 class="text-[10px] font-black uppercase text-slate-400 mb-4 tracking-widest ml-2">
          Filter Personnel
        </h4>

        <div class="space-y-2">
          <!-- ALL PERSONNEL BUTTON -->
          <button @click="setFilter('all', null); openMenu = null"
            class="w-full flex items-center gap-4 px-5 py-4 rounded-2xl transition-all text-left group"
            :class="activeFilter.type === 'all' ? 'bg-indigo-50 border border-indigo-100' : 'hover:bg-slate-50 border border-transparent'">
            <div class="w-8 h-8 rounded-xl flex items-center justify-center transition-colors"
              :class="activeFilter.type === 'all' ? 'bg-indigo-600 text-white shadow-md' : 'bg-slate-100 text-slate-400 group-hover:bg-slate-200 group-hover:text-slate-600'">
              <i class="fas fa-layer-group"></i>
            </div>
            <span class="text-xs font-black uppercase tracking-tight" :class="activeFilter.type === 'all' ? 'text-indigo-600' : 'text-slate-500'">
              All Personnel
            </span>
          </button>

          <!-- AFFILIATED PT -->
          <div class="border border-transparent" :class="openMenu === 'pt' ? 'bg-slate-50 rounded-2xl border-slate-100 p-2' : ''">
            <button @click="toggleMenu('pt')"
              class="w-full flex items-center justify-between px-3 py-2 rounded-xl transition-all text-left group hover:bg-slate-50">
              <div class="flex items-center gap-4">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center transition-colors bg-slate-100 text-slate-400 group-hover:bg-slate-200 group-hover:text-indigo-500"
                  :class="activeFilter.type === 'pt' ? 'bg-indigo-100 text-indigo-600' : ''">
                  <i class="fas fa-building"></i>
                </div>
                <span class="text-xs font-black uppercase tracking-tight text-slate-500 group-hover:text-indigo-600">Affiliated PT</span>
              </div>
              <i class="fas fa-chevron-down text-[10px] text-slate-400 transition-transform duration-300" :class="openMenu === 'pt' ? 'rotate-180' : ''"></i>
            </button>
            
            <div v-show="openMenu === 'pt'" class="mt-2 ml-10 space-y-1 animate-in slide-in-from-top-2 duration-300 max-h-[250px] overflow-y-auto pr-2 custom-scrollbar">
              <button v-for="cp in allCompanies" :key="cp.id"
                @click="setFilter('pt', cp.id)"
                class="w-full text-left py-2 px-3 text-[10px] font-bold transition-all rounded-xl truncate"
                :class="activeFilter.type === 'pt' && activeFilter.value === cp.id ? 'text-indigo-600 bg-indigo-50' : 'text-slate-400 hover:text-indigo-500 hover:bg-slate-100'">
                — {{ cp.name }}
              </button>
              
              <button @click="setFilter('pt', null)"
                class="w-full text-left py-2 px-3 text-[10px] font-bold transition-all rounded-xl truncate mt-1 border-t border-slate-200/50"
                :class="activeFilter.type === 'pt' && activeFilter.value === null ? 'text-indigo-600 bg-indigo-50' : 'text-slate-400 hover:text-indigo-500 hover:bg-slate-100'">
                — Independent / No PT
              </button>
            </div>
          </div>

          <!-- ROLE / SPECIALTY -->
          <div class="border border-transparent" :class="openMenu === 'role' ? 'bg-slate-50 rounded-2xl border-slate-100 p-2' : ''">
            <button @click="toggleMenu('role')"
              class="w-full flex items-center justify-between px-3 py-2 rounded-xl transition-all text-left group hover:bg-slate-50">
              <div class="flex items-center gap-4">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center transition-colors bg-slate-100 text-slate-400 group-hover:bg-slate-200 group-hover:text-emerald-500"
                  :class="activeFilter.type === 'role' ? 'bg-emerald-100 text-emerald-600' : ''">
                  <i class="fas fa-user-tag"></i>
                </div>
                <span class="text-xs font-black uppercase tracking-tight text-slate-500 group-hover:text-emerald-600">Access Role</span>
              </div>
              <i class="fas fa-chevron-down text-[10px] text-slate-400 transition-transform duration-300" :class="openMenu === 'role' ? 'rotate-180' : ''"></i>
            </button>
            
            <div v-show="openMenu === 'role'" class="mt-2 ml-10 space-y-1 animate-in slide-in-from-top-2 duration-300">
              <button v-for="r in [{id: 'super_admin', label: 'Super Admin'}, {id: 'admin', label: 'Admin'}, {id: 'member', label: 'Member'}]" :key="r.id"
                @click="setFilter('role', r.id)"
                class="w-full text-left py-2 px-3 text-[10px] font-bold transition-all rounded-xl"
                :class="activeFilter.type === 'role' && activeFilter.value === r.id ? 'text-emerald-600 bg-emerald-50' : 'text-slate-400 hover:text-emerald-500 hover:bg-slate-100'">
                — {{ r.label }}
              </button>
            </div>
          </div>

        </div>
      </div>
    </div>

    <!-- MAIN CONTENT (Kanan) -->
    <div class="lg:col-span-9 flex flex-col gap-6 min-h-[600px] relative">
      
      <!-- Loading Overlay -->
      <div v-if="isLoading" class="absolute inset-0 bg-white/50 backdrop-blur-sm z-50 flex items-center justify-center rounded-[3rem]">
        <i class="fas fa-spinner fa-spin text-3xl text-indigo-600"></i>
      </div>

      <!-- Top Bar: Filter Status & Action Button -->
      <div class="bg-white rounded-[2.5rem] p-6 shadow-sm border border-slate-200 flex flex-col md:flex-row justify-between items-center gap-4 relative z-10">
        <div class="flex items-center gap-4 w-full md:w-auto">
          <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Current Filter:</span>
          <div class="bg-slate-50 border border-slate-100 text-indigo-700 text-[10px] font-bold uppercase py-3 px-5 rounded-xl flex items-center gap-3 min-w-[200px]">
            <span v-if="activeFilter.type === 'all'">All Personnel</span>
            <span v-else>{{ activeFilter.type }}: {{ activeFilter.value || 'Independent' }}</span>
            <button v-if="activeFilter.type !== 'all'" @click="setFilter('all', null)" class="ml-auto text-rose-400 hover:text-rose-600 transition-colors">
              <i class="fas fa-times-circle text-sm"></i>
            </button>
          </div>
        </div>

        <button @click="showAddModal = true" class="w-full md:w-auto bg-indigo-600 text-white px-8 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-xl shadow-indigo-100 hover:bg-indigo-700 transition-all flex items-center justify-center gap-3">
          <i class="fas fa-plus"></i> Add Team Member
        </button>
      </div>

      <!-- Content Area (Membungkus List Card Personnel) -->
      <div class="bg-white rounded-[3rem] border border-slate-200 shadow-sm flex-1 overflow-hidden flex flex-col p-6 lg:p-8">
        
        <!-- Header Daftar -->
        <!-- <div class="flex items-center gap-4 mb-6 px-2 border-b border-slate-50 pb-6">
          <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl shadow-inner">
            <i class="fas fa-users-cog"></i>
          </div>
          <div>
            <h2 class="text-sm font-black text-slate-800 uppercase tracking-widest">Team Directory</h2>
            <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-1">Manage Resource & Human Capital</p>
          </div>
        </div> -->

        <!-- List Personnel Cards (Grid) -->
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 items-start content-start flex-1 overflow-y-auto custom-scrollbar pr-2 pb-4">
          
          <!-- Looping Cards -->
          <div v-for="m in paginatedTeamData" :key="m.id" 
            class="bg-slate-50/40 border border-slate-100 p-6 rounded-[2.5rem] shadow-sm hover:shadow-lg hover:-translate-y-1 hover:bg-white transition-all duration-300 group relative overflow-hidden flex flex-col justify-between h-[210px]">
            
            <div class="flex items-center gap-4 relative z-10">
              <div class="w-14 h-14 rounded-[1.2rem] bg-indigo-50 flex items-center justify-center text-indigo-600 text-lg font-black shadow-inner uppercase border border-indigo-100 group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                <img v-if="m.avatar_url" :src="m.avatar_url" class="w-full h-full object-cover rounded-[1.2rem]">
                <span v-else>{{ m.name.substring(0,2) }}</span>
              </div>
              <div class="flex-1 min-w-0">
                <h5 class="text-[13px] font-black text-slate-800 uppercase tracking-tight truncate group-hover:text-indigo-600 transition-colors">{{ m.name }}</h5>
                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-0.5 truncate">{{ m.position || m.role || 'Staff' }}</p>
              </div>
            </div>

            <div class="mt-4 pt-4 border-t border-slate-100/60 space-y-2.5 relative z-10">
              <div class="flex justify-between items-center">
                <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest">Affiliation</span>
                <span class="text-[9px] font-black text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded border border-indigo-100 uppercase truncate max-w-[120px]">
                  {{ m.pt_owner_name || 'Independent' }}
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
                <span class="text-[10px] font-black italic" :class="m.outstanding > 0 ? 'text-rose-500' : 'text-emerald-500'">
                  {{ formatCurrency(m.outstanding) }}
                </span>
              </div>
            </div>

            <!-- ACTION BUTTONS HOVER -->
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

          <!-- EMPTY STATE -->
          <div v-if="filteredTeamData.length === 0" class="col-span-full py-24 text-center opacity-40 border-2 border-dashed border-slate-200 rounded-[3rem]">
              <i class="fas fa-users-slash text-5xl mb-4 text-slate-300"></i>
              <p class="text-[11px] font-black uppercase tracking-widest text-slate-500">No personnel found</p>
          </div>
        </div>

        <!-- PAGINATION CONTROLS -->
        <div v-if="totalPages > 1" class="mt-4 pt-6 border-t border-slate-100 bg-white flex flex-col md:flex-row justify-between items-center gap-4">
            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">
                Showing <span class="text-indigo-600">{{ (currentPage - 1) * itemsPerPage + 1 }}</span> to 
                <span class="text-indigo-600">{{ Math.min(currentPage * itemsPerPage, filteredTeamData.length) }}</span> 
                of <span class="text-slate-700">{{ filteredTeamData.length }}</span> entries
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

    <!-- ========================================== -->
    <!-- SEMUA MODAL DI BAWAH SINI -->
    <!-- ========================================== -->

    <!-- 1. MODAL ADD TEAM MEMBER -->
    <div v-if="showAddModal" class="fixed inset-0 z-[999] flex items-center justify-center p-6">
      <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showAddModal = false"></div>
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

            <template v-else>
                <div class="space-y-1">
                  <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Company / Entity Name</label>
                  <input v-model="teamworkForm.name" type="text" placeholder="E.G. PT. MAJU MUNDUR" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-3 text-xs font-bold outline-none focus:ring-2 ring-indigo-100 transition-all uppercase">
                </div>
                <div class="space-y-1">
                  <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Legal Registration Name</label>
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

    <!-- 2. MODAL EDIT PERSONNEL -->
    <div v-if="showEditModal" class="fixed inset-0 z-[999] flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="showEditModal = false"></div>
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

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #e2e8f0; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background-color: #cbd5e1; }
.animate-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
</style>