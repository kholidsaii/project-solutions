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
      api.get('/users'),
      api.get('/teamwork/top-outstanding'),
      api.get('/companies')
    ]);

    organizations.value = resSummary.data.organizations || [];
    totalProjectCount.value = resSummary.data.total_projects || 0;
    individuals.value = resInd.data || [];
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
      <div v-if="currentTab === 'analytics'" class="animate-in fade-in duration-500 py-8 space-y-8">
        <!-- STATS CARDS -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
          <div v-for="s in stats" :key="s.label" :class="s.color" class="p-6 rounded-[2rem] text-white shadow-lg relative overflow-hidden group">
            <i class="fas opacity-10 text-6xl absolute -right-4 -bottom-4 group-hover:scale-125 transition-transform" :class="s.icon"></i>
            <p class="text-3xl font-black mb-1">{{ s.value }}</p>
            <p class="text-[10px] font-bold opacity-80 uppercase tracking-widest">{{ s.label }}</p>
          </div>
        </div>

        <!-- RELASI PROJECT & FINANCE (MOCKUP VISUAL) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
          <div class="lg:col-span-8 bg-white border border-slate-200 rounded-[2.5rem] p-10 shadow-sm">
            <div class="flex justify-between items-center mb-10">
              <h3 class="text-lg font-black text-slate-800 uppercase italic">Organization Power Distribution</h3>
              <span class="text-[10px] font-bold text-slate-400 uppercase">Monitoring 7 PT Bos</span>
            </div>
            <!-- Di sini nanti akan ada Chart kontribusi PT terhadap Project -->
            <div class="h-64 bg-slate-50 rounded-3xl border-2 border-dashed border-slate-200 flex items-center justify-center">
               <p class="text-slate-400 font-bold text-xs">PT Power Chart (Integration with Finance)</p>
            </div>
          </div>
          
          <!-- Bagian Sidebar Kasbon di Teamwork.vue -->
          <div class="lg:col-span-4 bg-slate-900 rounded-[2.5rem] p-8 text-white shadow-xl">
              <h3 class="text-sm font-black uppercase mb-6 text-indigo-400 italic">
                  <i class="fas fa-wallet mr-2"></i> Total Outstanding
              </h3>
              
              <div class="space-y-6">
                  <!-- Loop data dari API -->
                  <div v-for="item in topOutstanding" :key="item.id" class="flex justify-between items-center group">
                      <div>
                          <p class="text-[11px] font-black uppercase tracking-tight group-hover:text-indigo-300 transition-colors">
                              {{ item.member_name }}
                          </p>
                          <p class="text-[9px] text-slate-500 uppercase font-bold">
                              Project: {{ item.project_title || 'General / Internal' }}
                          </p>
                      </div>
                      <div class="text-right">
                          <p class="text-rose-400 font-black text-sm">{{ formatCurrency(item.amount) }}</p>
                      </div>
                  </div>

                  <!-- Jika Data Kosong -->
                  <div v-if="topOutstanding.length === 0" class="text-center py-4 border border-dashed border-slate-800 rounded-2xl opacity-30">
                      <p class="text-[9px] font-bold uppercase tracking-widest">No outstanding kasbon</p>
                  </div>

                  <hr class="border-slate-800">
                  
                  <button class="w-full bg-indigo-600 hover:bg-indigo-500 py-4 rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] shadow-lg shadow-indigo-900/20 transition-all active:scale-95">
                      Process Settlement
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