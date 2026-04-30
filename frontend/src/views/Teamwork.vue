<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
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
    const [resSummary, resInd, resTop, resComp] = await Promise.all([
      api.get('/teamwork/summary'),
      api.get('/users'), // <-- Pastikan ini endpoint yang benar
      api.get('/teamwork/top-outstanding'),
      api.get('/companies')
    ]);

    // Update state
    organizations.value = resSummary.data.organizations || [];
    totalProjectCount.value = resSummary.data.total_projects || 0;
    
    // PENTING: Pastikan individuals diisi dari resInd.data
    individuals.value = resInd.data || []; 
    
    allCompanies.value = resComp.data || [];
    
    console.log("Data Users Ter-load:", individuals.value);
  } catch (e) {
    console.error("Gagal sinkronisasi teamwork", e);
  } finally {
    isLoading.value = false;
  }
};

const handleSave = async () => {
  try {
    let url = entityType.value === 'individual' ? '/users/register' : '/companies';
    
    const payload = { ...teamworkForm.value };
    if (!isEditing.value && entityType.value === 'individual') {
        payload.password = 'password123';
    }

    // Perbaikan Error 7052: Gunakan pemanggilan eksplisit untuk amannya TypeScript
    if (isEditing.value) {
      url = entityType.value === 'individual' ? `/users/${editingId.value}/role` : `/companies/${editingId.value}`;
      await api.put(url, payload);
    } else {
      await api.post(url, payload);
    }
    
    showAddModal.value = false;
    await fetchData();
    resetForm();
    alert("Data berhasil disimpan!");
  } catch (e) {
    console.error(e);
    alert("Gagal memproses data.");
  }
};

const resetForm = () => {
  teamworkForm.value = { name: '', email: '', password: '', role: 'member', position: '', company_id: null, status: 'Active' };
  isEditing.value = false;
  editingId.value = null;
  entityType.value = 'individual';
};

const handleDeleteMember = async (id: number) => {
  if (!confirm('Hapus entitas/member ini secara permanen?')) return;
  try {
    const endpoint = currentTab.value === 'entities' ? `/companies/${id}` : `/users/${id}`;
    await api.delete(endpoint);
    await fetchData(); 
    alert("Data berhasil dihapus!");
  } catch (e) {
    console.error("Gagal hapus", e);
  }
};

const handleEditMaster = (item: any) => {
  isEditing.value = true;
  editingId.value = item.id;
  entityType.value = currentTab.value === 'entities' ? 'organization' : 'individual';
  
  teamworkForm.value = {
    name: item.name,
    email: item.email || '',
    role: item.role || 'member',
    position: item.position || '',
    company_id: item.company_id || null,
    status: item.status || 'Active'
  };
  
  showAddModal.value = true;
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
          <button v-for="tab in ['analytics', 'entities', 'members']" :key="tab"
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

      <!-- CONTENT: LIST VIEW (Entities/PT) -->
      <div v-if="currentTab === 'entities' || currentTab === 'members'" class="py-8 animate-in slide-in-from-bottom-4">
        <div class="bg-white border border-slate-200 rounded-4xl overflow-hidden shadow-sm overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[800px]">
                <thead class="bg-slate-50">
                    <tr class="text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">
                        <th class="p-6">Entity Detail</th>
                        <th class="p-6">Role / Specialty</th>
                        <th class="p-6">Affiliated PT (Bos Entiti)</th>
                        <th class="p-6">Financing (Kasbon/Modal)</th>
                        <th class="p-6 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <!-- Data List -->
                    <tr v-for="item in (currentTab === 'entities' ? organizations : individuals)" 
                        :key="item.id" 
                        class="hover:bg-slate-50/50 transition-colors group">
                        
                        <!-- Entity Detail -->
                        <td class="p-6">
                            <div class="flex items-center gap-4">
                                <!-- Logo Dinamis (Avatar atau Initials) -->
                                <div class="w-12 h-12 rounded-2xl bg-indigo-50 flex items-center justify-center text-indigo-600 font-black shadow-inner overflow-hidden">
                                    <img v-if="item.avatar_url" :src="item.avatar_url" class="w-full h-full object-cover">
                                    <span v-else>{{ item.name.substring(0,2).toUpperCase() }}</span>
                                </div>
                                <div>
                                    <p class="text-[12px] font-black text-slate-800 uppercase tracking-tight">{{ item.name }}</p>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter">
                                        {{ item.legal_name || item.email || 'Contractor' }}
                                    </p>
                                </div>
                            </div>
                        </td>

                        <!-- Role / Position -->
                        <td class="p-6">
                            <span class="px-3 py-1.5 bg-blue-50 text-blue-700 text-[10px] font-black rounded-lg uppercase border border-blue-100 shadow-sm">
                                {{ item.position || item.role || 'Staff' }}
                            </span>
                        </td>

                        <!-- Affiliated PT (Terelasi ke 7 PT Bos) -->
                        <td class="p-6">
                            <div class="flex items-center gap-2 text-[11px] font-bold text-slate-600">
                                <div class="w-2 h-2 rounded-full bg-emerald-500" v-if="item.pt_owner_name"></div>
                                <i v-else class="fas fa-landmark text-slate-300"></i>
                                <span class="uppercase tracking-tight">{{ item.pt_owner_name || 'Independent / Outside' }}</span>
                            </div>
                        </td>

                        <!-- Financing (Kasbon Team) -->
                        <td class="p-6">
                            <div class="flex flex-col">
                                <span class="text-[11px] font-black italic" :class="item.outstanding > 0 ? 'text-rose-500' : 'text-emerald-600'">
                                    {{ formatCurrency(item.outstanding) }}
                                </span>
                                <span v-if="item.outstanding > 0" class="text-[8px] font-black text-rose-300 uppercase tracking-tighter">
                                    Pending Settlement
                                </span>
                            </div>
                        </td>

                        <!-- Action Buttons -->
                        <td class="p-6 text-right">
                            <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-all transform group-hover:translate-x-0 translate-x-4">
                                <!-- Tombol Kasbon/Finance -->
                                <button @click="openFinanceModal(item)" class="w-8 h-8 rounded-lg bg-slate-100 text-slate-400 hover:bg-emerald-600 hover:text-white transition-all shadow-sm">
                                    <i class="fas fa-wallet text-[10px]"></i>
                                </button>
                                <!-- Tombol Edit -->
                                <button @click="handleEditMaster(item)" class="w-8 h-8 rounded-lg bg-slate-100 text-slate-400 hover:bg-indigo-600 hover:text-white transition-all shadow-sm">
                                    <i class="fas fa-edit text-[10px]"></i>
                                </button>
                                <!-- Tombol Hapus -->
                                <button @click="handleDeleteMember(item.id)" class="w-8 h-8 rounded-lg bg-slate-100 text-slate-400 hover:bg-rose-600 hover:text-white transition-all shadow-sm">
                                    <i class="fas fa-trash text-[10px]"></i>
                                </button>
                            </div>
                        </td>
                    </tr>

                    <!-- Empty State -->
                    <tr v-if="(currentTab === 'entities' ? organizations : individuals).length === 0">
                        <td colspan="5" class="p-20 text-center opacity-30 grayscale">
                            <i class="fas fa-users text-4xl mb-4"></i>
                            <p class="text-[10px] font-black uppercase tracking-widest">No members or entities found</p>
                        </td>
                    </tr>
                </tbody>
            </table>
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
            <div class="space-y-1">
              <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Full Name / PT Name</label>
              <input v-model="teamworkForm.name" type="text" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-3 text-xs font-bold outline-none focus:ring-2 ring-indigo-100 transition-all uppercase">
            </div>

            <div v-if="entityType === 'individual'" class="space-y-1">
              <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Email Address</label>
              <input v-model="teamworkForm.email" type="email" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-3 text-xs font-bold outline-none uppercase">
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div class="space-y-1">
                <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Role / Position</label>
                <input v-model="teamworkForm.position" type="text" placeholder="E.G. DEVELOPER" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-3 text-xs font-bold outline-none uppercase">
              </div>
              <div class="space-y-1">
                <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Affiliated PT</label>
                <select v-model="teamworkForm.company_id" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-3 text-xs font-bold outline-none appearance-none">
                  <option :value="null">-- INDEPENDENT --</option>
                  <option v-for="cp in allCompanies" :key="cp.id" :value="cp.id">{{ cp.name }}</option>
                </select>
              </div>
            </div>
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