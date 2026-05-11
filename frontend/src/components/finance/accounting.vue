<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import api from '../../api/axios';

const formatCurrency = (val: number) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val || 0);

// Untuk tampilan angka K/M/B yang muat di lingkaran bobot/saldo
const formatCompact = (val: number) => {
    if (val === 0) return '0';
    return new Intl.NumberFormat('id-ID', { notation: "compact", maximumFractionDigits: 1 }).format(val);
};

const getFileUrl = (path: string | null | number | undefined): string => {
  if (!path || path === "0" || path === 0) return '';
  if (typeof path === 'string' && (path.startsWith('data:') || path.startsWith('http'))) return path;
  
  const apiUrl = import.meta.env.VITE_API_URL || 'http://localhost:8000'; 
  const baseUrl = apiUrl.replace('/api', '');
  
  let cleanPath = String(path).startsWith('/') ? String(path).substring(1) : String(path);
  return cleanPath.toLowerCase().startsWith('uploads/') ? `${baseUrl}/${cleanPath}` : `${baseUrl}/uploads/${cleanPath}`;
};

// ==========================================
// 1. STATE & DATA
// ==========================================
const dbProjects = ref<any[]>([]);
const dbJournals = ref<any[]>([]);
const dbBanks = ref<any[]>([]);
const dbCOAs = ref<any[]>([]);
const dbCompanies = ref<any[]>([]); // Tambahan untuk relasi entitas PT pada COA

const searchQuery = ref('');
const showLedgerModal = ref(false);
const selectedLedger = ref<any>(null);

// State Form COA Baru
const showCoaModal = ref(false);
const coaCategories = ['Asset', 'Liability', 'Equity', 'Revenue', 'Expense'];
const coaForm = ref({ 
    id: null as number | null, 
    pt_id: '' as any, 
    project_id: '' as any, 
    code: '', 
    name: '', 
    category: 'Asset' 
});

// Untuk sidebar style dari gambar referensi
const activeSidebar = ref('semua_proyek');

// ==========================================
// 2. KUMPULKAN DAN HITUNG DATA AKUNTANSI
// ==========================================
const projectsWithLedger = computed(() => {
  // Buat list project beserta 1 project bayangan untuk "KAS UMUM / NON-PROJECT"
  const baseProjects = [
    {
      id: null, 
      project_title: 'GENERAL / NON-PROJECT',
      client_name: 'Internal Organisasi',
      start_date: '-',
      finish_date: '-',
      status: 'General',
      progress: 0,
      team_count: 0,
      logo: null
    },
    ...dbProjects.value
  ];

  let mapped = baseProjects.map(proj => {
    const projJournals = dbJournals.value.filter(j => j.project_id === proj.id);
    
    let total_inflow = 0; // Debit
    let total_outflow = 0; // Kredit

    projJournals.forEach(j => {
      total_inflow += parseFloat(j.debit || 0);
      total_outflow += parseFloat(j.credit || 0);
    });

    const balance = total_inflow - total_outflow;

    return {
      ...proj,
      journals: projJournals,
      total_inflow,
      total_outflow,
      balance,
      borderColor: ['bg-cyan-400', 'bg-pink-500', 'bg-blue-700', 'bg-rose-500', 'bg-emerald-400'][Math.floor(Math.random() * 5)]
    };
  });

  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase();
    mapped = mapped.filter(p => p.project_title.toLowerCase().includes(q) || p.client_name?.toLowerCase().includes(q));
  }

  if (mapped.length > 0 && mapped[0].id === null && mapped[0].journals.length === 0) {
      mapped.shift(); 
  }

  return mapped;
});

// ==========================================
// 3. FUNGSI HELPER & API
// ==========================================
const fetchAll = async () => {
  try {
    const [resProj, resJournals, resBanks, resCOA, resComp] = await Promise.all([
      api.get('/projects'),
      api.get('/accounting/ledger'),
      api.get('/finance/banks'),
      api.get('/accounting/coas'),
      api.get('/companies')
    ]);
    dbProjects.value = resProj.data;
    dbJournals.value = resJournals.data;
    dbBanks.value = resBanks.data.data || resBanks.data;
    dbCOAs.value = resCOA.data.data || resCOA.data;
    dbCompanies.value = resComp.data.data || resComp.data;
  } catch (error) {
    console.error(error);
  }
};

const getBankName = (bankId: any) => {
  if (!bankId) return '-';
  const b = dbBanks.value.find(x => x.id == bankId);
  return b ? `${b.bank_name}` : 'Unknown';
};

const openLedgerModal = (projectLedger: any) => {
  selectedLedger.value = projectLedger;
  showLedgerModal.value = true;
};

// --- FUNGSI TAMBAH COA ---
const openCoaModal = () => {
  coaForm.value = { id: null, pt_id: '', project_id: '', code: '', name: '', category: 'Asset' };
  showCoaModal.value = true;
};

const handleSaveCOA = async () => {
  if (!coaForm.value.code || !coaForm.value.name) return alert('Kode dan Nama Akun wajib diisi!');
  try {
    const payload: Record<string, any> = { ...coaForm.value };
    if (!payload.pt_id) payload.pt_id = null;
    if (!payload.project_id) payload.project_id = null;

    if (payload.id) { 
        await api.put(`/accounting/coas/${payload.id}`, payload); 
        alert("Akun COA diperbarui!"); 
    } else { 
        await api.post('/accounting/coas', payload); 
        alert("Akun COA baru berhasil direlasikan!"); 
    }
    showCoaModal.value = false; 
    await fetchAll(); 
  } catch (error: any) { 
    alert(error.response?.data?.error || "Terjadi kesalahan saat menyimpan."); 
  }
};

onMounted(fetchAll);
</script>

<template>
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 animate-in fade-in duration-500 text-slate-800 font-sans">
    
    <div class="lg:col-span-3 space-y-6">
      <div class="bg-white border border-slate-200 rounded-lg shadow-sm overflow-hidden">
        <div class="p-4 bg-slate-50 border-b border-slate-200 text-center">
          <h3 class="text-sm font-black text-slate-800 tracking-tight">Navigation</h3>
        </div>
        
        <div class="flex flex-col p-2 space-y-1">
          <button @click="activeSidebar = 'semua_proyek'" class="px-5 py-3 flex items-center gap-3 text-xs font-bold text-slate-700 hover:bg-slate-50 rounded-md transition-colors" :class="activeSidebar === 'semua_proyek' ? 'bg-slate-100 text-indigo-600' : ''">
            <i class="fas fa-folder text-slate-400"></i> Semua Proyek
          </button>
          <button @click="activeSidebar = 'uang_masuk'" class="px-5 py-3 flex items-center gap-3 text-xs font-bold text-slate-700 hover:bg-slate-50 rounded-md transition-colors">
            <i class="fas fa-folder text-slate-400"></i> Uang Masuk / Income
          </button>
          <button @click="activeSidebar = 'uang_keluar'" class="px-5 py-3 flex items-center gap-3 text-xs font-bold text-slate-700 hover:bg-slate-50 rounded-md transition-colors">
            <i class="fas fa-folder text-slate-400"></i> Uang Keluar / Expanse
          </button>
          <button @click="activeSidebar = 'general'" class="px-5 py-3 flex items-center gap-3 text-xs font-bold text-slate-700 hover:bg-slate-50 rounded-md transition-colors">
            <i class="fas fa-folder text-slate-400"></i> Buku Besar Kas Umum
          </button>
        </div>
      </div>
    </div>
    
    <div class="lg:col-span-9 flex flex-col gap-4 min-h-[600px]">
      
      <div class="bg-white border border-slate-200 rounded-lg p-2 flex flex-col md:flex-row justify-between items-center gap-4 shadow-sm">
        <div class="flex items-center gap-2 w-full px-2">
          <div class="bg-blue-500 text-white px-4 py-2 rounded-md flex items-center gap-2 text-xs font-bold shadow-sm w-44 justify-center whitespace-nowrap cursor-pointer hover:bg-blue-600">
            <i class="fas fa-home"></i> All Accounting
          </div>
          <div class="relative flex-1">
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 text-xs"></i>
            <input v-model="searchQuery" type="text" placeholder="Search project ledger..." class="w-full pl-8 pr-3 py-2 bg-white border border-slate-200 rounded-md text-xs font-medium outline-none focus:ring-1 ring-blue-300">
          </div>
          <button class="bg-white border border-slate-200 text-slate-600 px-4 py-2 rounded-md flex items-center gap-2 text-xs font-bold hover:bg-slate-50 whitespace-nowrap">
            <i class="fas fa-filter text-blue-500"></i> Filter
          </button>
          <div class="flex items-center gap-1">
            <button class="bg-blue-500 text-white w-8 h-8 rounded-md flex items-center justify-center hover:bg-blue-600"><i class="fas fa-bars"></i></button>
            <button class="bg-blue-500 text-white w-8 h-8 rounded-md flex items-center justify-center hover:bg-blue-600"><i class="fas fa-th-large"></i></button>
          </div>
          <button @click="openCoaModal" class="bg-blue-600 text-white px-4 py-2 rounded-md flex items-center gap-2 text-xs font-bold hover:bg-blue-700 shadow-md whitespace-nowrap">
            <i class="fas fa-plus"></i> Tambah COA
          </button>
        </div>
      </div>

      <div class="flex-1 space-y-3 mt-2">
        
        <div v-if="projectsWithLedger.length === 0" class="p-10 text-center text-slate-400 border-2 border-dashed rounded-xl">
           <i class="fas fa-folder-open text-4xl mb-3"></i>
           <p class="text-xs font-bold uppercase tracking-widest">Tidak ada project / buku besar ditemukan</p>
        </div>

        <div v-for="proj in projectsWithLedger" :key="proj.id" @click="openLedgerModal(proj)" class="bg-white border border-slate-200 rounded-lg flex relative overflow-hidden group shadow-sm hover:shadow-md transition-all cursor-pointer">
          
          <div class="w-3 shrink-0" :class="proj.borderColor"></div>
          
          <div class="p-4 flex flex-col md:flex-row items-center justify-between gap-4 w-full">
             
             <div class="flex items-center gap-5 w-full md:w-auto overflow-hidden">
                <div class="w-[70px] h-[70px] rounded-full border border-slate-200 p-0.5 flex shrink-0 items-center justify-center overflow-hidden bg-slate-50">
                   <img v-if="proj.logo" :src="getFileUrl(proj.logo)" alt="Logo" class="w-full h-full rounded-full object-cover">
                   <i v-else class="fas fa-university text-2xl text-slate-300"></i>
                </div>

                <div class="flex-1 min-w-0">
                   <div class="flex flex-wrap gap-2 mb-2">
                     <span class="px-3 py-1 rounded-full text-[10px] font-black text-white bg-rose-500">Project</span>
                     <span class="px-3 py-1 rounded-full text-[10px] font-black text-white bg-green-500">{{ proj.category || 'Ledger' }}</span>
                     <span class="px-3 py-1 rounded-full text-[10px] font-black text-slate-700 bg-yellow-300">{{ proj.status }}</span>
                     <span class="px-3 py-1 rounded-full text-[10px] font-black text-white bg-blue-700">Team : {{ proj.team_count }}</span>
                   </div>

                   <h4 class="text-base font-black text-slate-800 uppercase tracking-tight truncate group-hover:text-blue-600 transition-colors">{{ proj.project_title }}</h4>
                   
                   <div class="text-[10px] font-bold text-slate-500 mt-1 leading-tight">
                     <p>Customer<span class="ml-2 mr-2">:</span>{{ proj.client_name }}</p>
                     <p>Start Date<span class="ml-2 mr-2">:</span>{{ proj.start_date }}</p>
                     <p>Finish Date<span class="ml-1 mr-2">:</span>{{ proj.finish_date }}</p>
                   </div>
                </div>
             </div>

             <div class="shrink-0 flex items-center justify-center">
                <div class="w-20 h-20 rounded-full flex flex-col items-center justify-center border-[5px] border-white ring-[3px]" :class="proj.balance >= 0 ? 'bg-green-100 ring-green-400' : 'bg-red-100 ring-red-400'">
                   <span class="font-black text-sm text-center px-1" :class="proj.balance >= 0 ? 'text-green-600' : 'text-red-600'">Rp {{ formatCompact(proj.balance) }}</span>
                   <span class="text-[8px] font-bold uppercase mt-0.5" :class="proj.balance >= 0 ? 'text-green-600' : 'text-red-600'">Saldo Net</span>
                </div>
             </div>

          </div>
        </div>

      </div>
    </div>

    <div v-if="showLedgerModal && selectedLedger" class="fixed inset-0 z-[999] flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showLedgerModal = false"></div>
      
      <div class="bg-white rounded-2xl w-full max-w-6xl max-h-[95vh] flex flex-col relative z-10 shadow-2xl">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50 rounded-t-2xl shrink-0">
          <div class="flex items-center gap-4">
             <div class="w-12 h-12 rounded-xl border border-slate-200 p-0.5 bg-white overflow-hidden flex items-center justify-center">
                 <img v-if="selectedLedger.logo" :src="getFileUrl(selectedLedger.logo)" class="w-full h-full object-cover">
                 <i v-else class="fas fa-university text-2xl text-slate-300"></i>
             </div>
             <div>
                <h3 class="text-xl font-black text-slate-800 uppercase tracking-tighter">{{ selectedLedger.project_title }}</h3>
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mt-0.5">Buku Jurnal / General Ledger</p>
             </div>
          </div>
          <button @click="showLedgerModal = false" class="w-10 h-10 rounded-lg bg-white border border-slate-200 text-slate-400 hover:bg-rose-50 hover:text-rose-500 transition-all flex items-center justify-center"><i class="fas fa-times"></i></button>
        </div>

        <div class="flex-1 overflow-auto p-6 bg-slate-50/30">
           <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-8">
              <div class="bg-white border border-emerald-100 p-5 rounded-xl shadow-sm border-l-4 border-l-emerald-500">
                 <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Uang Masuk (Debit)</p>
                 <h4 class="text-xl font-black text-emerald-600">{{ formatCurrency(selectedLedger.total_inflow) }}</h4>
              </div>
              <div class="bg-white border border-rose-100 p-5 rounded-xl shadow-sm border-l-4 border-l-rose-500">
                 <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Uang Keluar (Kredit)</p>
                 <h4 class="text-xl font-black text-rose-600">{{ formatCurrency(selectedLedger.total_outflow) }}</h4>
              </div>
              <div class="bg-white border border-blue-100 p-5 rounded-xl shadow-sm border-l-4 border-l-blue-500">
                 <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Saldo Bersih (Balance)</p>
                 <h4 class="text-xl font-black text-blue-700">{{ formatCurrency(selectedLedger.balance) }}</h4>
              </div>
           </div>

           <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
              <div class="p-4 bg-slate-50 border-b border-slate-200">
                 <h4 class="text-xs font-black text-slate-700 uppercase"><i class="fas fa-table text-slate-400 mr-2"></i> Rincian Histori Transaksi</h4>
              </div>
              <div class="overflow-x-auto">
                 <table class="w-full text-left">
                    <thead class="bg-slate-100">
                       <tr class="text-[10px] font-black text-slate-500 uppercase tracking-widest">
                          <th class="p-4">Tanggal</th>
                          <th class="p-4">No. Akun (COA)</th>
                          <th class="p-4">Keterangan / Transaksi</th>
                          <th class="p-4">Alur Banking (Dari <i class="fas fa-arrow-right"></i> Ke)</th>
                          <th class="p-4 text-right bg-emerald-50/50">Debit (Masuk)</th>
                          <th class="p-4 text-right bg-rose-50/50">Kredit (Keluar)</th>
                       </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                       <tr v-if="selectedLedger.journals.length === 0">
                          <td colspan="6" class="p-12 text-center text-slate-400 text-xs font-bold uppercase tracking-widest">Belum ada transaksi jurnal untuk project ini.</td>
                       </tr>
                       <tr v-for="j in selectedLedger.journals" :key="j.id" class="hover:bg-slate-50 transition-colors">
                          <td class="p-4 text-[10px] font-black text-slate-500">{{ j.transaction_date }}</td>
                          <td class="p-4">
                             <span class="bg-slate-800 text-white px-2 py-0.5 rounded text-[9px] font-black tracking-widest mr-2">{{ j.coa_code }}</span>
                             <span class="text-[10px] font-black text-slate-700 uppercase">{{ j.coa_name }}</span>
                          </td>
                          <td class="p-4">
                             <p class="text-xs font-bold text-slate-800 uppercase">{{ j.description }}</p>
                             <p class="text-[8px] font-bold text-slate-400 uppercase mt-0.5">Ref: {{ j.transaction_number || '-' }}</p>
                          </td>
                          <td class="p-4">
                             <div class="flex items-center gap-2">
                                <span class="px-2 py-1 bg-slate-100 border border-slate-200 rounded text-[9px] font-black text-rose-500 uppercase"><i class="fas fa-sign-out-alt mr-1"></i> {{ getBankName(j.bank_from) }}</span>
                                <i class="fas fa-arrow-right text-slate-300 text-[10px]"></i>
                                <span class="px-2 py-1 bg-slate-100 border border-slate-200 rounded text-[9px] font-black text-emerald-500 uppercase"><i class="fas fa-sign-in-alt mr-1"></i> {{ getBankName(j.bank_to) }}</span>
                             </div>
                          </td>
                          <td class="p-4 text-right text-xs font-black text-emerald-600 bg-emerald-50/30">{{ parseFloat(j.debit) > 0 ? formatCurrency(parseFloat(j.debit)) : '-' }}</td>
                          <td class="p-4 text-right text-xs font-black text-rose-600 bg-rose-50/30">{{ parseFloat(j.credit) > 0 ? formatCurrency(parseFloat(j.credit)) : '-' }}</td>
                       </tr>
                    </tbody>
                 </table>
              </div>
           </div>
        </div>
      </div>
    </div>

    <div v-if="showCoaModal" class="fixed inset-0 z-[999] flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showCoaModal = false"></div>
      
      <div class="bg-white rounded-[2rem] w-full max-w-2xl relative z-10 shadow-2xl animate-in zoom-in-95 duration-300">
        
        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50 rounded-t-[2rem]">
          <div>
            <h3 class="text-lg font-black text-slate-800 uppercase tracking-tighter">{{ coaForm.id ? 'Edit Akun COA' : 'Buat Akun COA Baru' }}</h3>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">Pengaturan Master Ledger & Proyek</p>
          </div>
          <button @click="showCoaModal = false" class="w-8 h-8 rounded-xl bg-white border border-slate-200 text-slate-400 hover:bg-rose-50 hover:text-rose-500 transition-all flex items-center justify-center shadow-sm">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <div class="p-6 space-y-5">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-2">
              <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Relasi Project</label>
              <select v-model="coaForm.project_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-xs font-bold uppercase outline-none focus:ring-2 ring-blue-100 text-slate-700">
                <option value="">-- GENERAL (Tanpa Project) --</option>
                <option v-for="pj in dbProjects" :key="pj.id" :value="pj.id">{{ pj.project_title }}</option>
              </select>
            </div>
            <div class="space-y-2">
              <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Entitas PT</label>
              <select v-model="coaForm.pt_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-xs font-bold uppercase outline-none focus:ring-2 ring-blue-100 text-slate-700">
                <option value="">-- GENERAL / PERSONAL --</option>
                <option v-for="pt in dbCompanies" :key="pt.id" :value="pt.id">{{ pt.name }}</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
              <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Kode Akun</label>
              <input v-model="coaForm.code" type="text" placeholder="Contoh: 1102" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-xs font-black outline-none focus:ring-2 ring-blue-100 uppercase">
            </div>
            <div class="space-y-2">
              <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Kategori Akun</label>
              <select v-model="coaForm.category" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-xs font-bold uppercase outline-none focus:ring-2 ring-blue-100 text-slate-700">
                <option v-for="cat in coaCategories" :key="cat" :value="cat">{{ cat }}</option>
              </select>
            </div>
          </div>

          <div class="space-y-2">
            <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Nama Akun (Account Name)</label>
            <input v-model="coaForm.name" type="text" placeholder="Contoh: Kas Kecil Operasional Proyek" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-xs font-bold outline-none focus:ring-2 ring-blue-100 uppercase">
          </div>
        </div>

        <div class="p-6 border-t border-slate-100 flex justify-end gap-3 bg-slate-50 rounded-b-[2rem]">
          <button @click="showCoaModal = false" class="px-6 py-3 rounded-xl text-[10px] font-black uppercase text-slate-500 hover:bg-slate-100">Batal</button>
          <button @click="handleSaveCOA" class="bg-blue-600 text-white px-8 py-3 rounded-xl text-[10px] font-black uppercase shadow-md hover:bg-blue-700">
            <i class="fas fa-save mr-1"></i> Simpan COA
          </button>
        </div>

      </div>
    </div>

  </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background-color: #94a3b8; }
.animate-in { animation: fadeIn 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
@keyframes fadeIn { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }

select {
  -webkit-appearance: none;
  -moz-appearance: none;
  background-image: url("data:image/svg+xml;utf8,<svg fill='%2394a3b8' height='24' viewBox='0 0 24 24' width='24' xmlns='http://www.w3.org/2000/svg'><path d='M7 10l5 5 5-5z'/><path d='M0 0h24v24H0z' fill='none'/></svg>");
  background-repeat: no-repeat;
  background-position-x: 95%;
  background-position-y: 50%;
}
</style>