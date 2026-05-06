<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import api from '../../api/axios';

const dbCOAs = ref<any[]>([]);
const dbCompanies = ref<any[]>([]);
const dbProjects = ref<any[]>([]); 

const activeAccEntity = ref('all'); 
const activeAccCategory = ref('all'); 
const searchCOA = ref('');

const sysConfig = JSON.parse(localStorage.getItem('kerjapro_finance_setup') || '{}');
const defaultCats = [
  { id: 'Asset', label: 'Harta (Assets)', icon: 'fa-wallet', color: 'text-emerald-500' },
  { id: 'Liability', label: 'Kewajiban (Liabilities)', icon: 'fa-hand-holding-usd', color: 'text-rose-500' },
  { id: 'Equity', label: 'Modal (Equity)', icon: 'fa-scale-balanced', color: 'text-indigo-500' },
  { id: 'Revenue', label: 'Pendapatan (Revenue)', icon: 'fa-arrow-trend-up', color: 'text-emerald-400' },
  { id: 'Expense', label: 'Beban (Expenses)', icon: 'fa-arrow-trend-down', color: 'text-rose-400' }
];

const coaCategories = [
  { id: 'all', label: 'Semua Kategori', icon: 'fa-layer-group', color: 'text-slate-500' },
  ...(sysConfig.coa_categories || defaultCats)
];

const entityFilteredData = computed(() => {
  if (activeAccEntity.value === 'all') return dbCOAs.value;
  if (activeAccEntity.value === 'personal') return dbCOAs.value.filter(c => !c.pt_id);
  return dbCOAs.value.filter(c => c.pt_id === activeAccEntity.value);
});

const filteredCOAs = computed(() => {
  let data = [...entityFilteredData.value];
  if (activeAccCategory.value !== 'all') data = data.filter(c => c.category === activeAccCategory.value);
  if (searchCOA.value) {
    const q = searchCOA.value.toLowerCase();
    data = data.filter(c => c.name.toLowerCase().includes(q) || c.code.toLowerCase().includes(q));
  }
  return data;
});

const getCategoryCount = (catId: string) => {
  if (catId === 'all') return entityFilteredData.value.length;
  return entityFilteredData.value.filter(c => c.category === catId).length;
};

const showCoaModal = ref(false);
const coaForm = ref({ id: null as number | null, pt_id: '' as string | number | null, project_id: '' as string | number | null, code: '', name: '', category: 'Asset' });

// === LOGIKA DINAMIS: Filter PT berdasarkan Project ===
const availableCompaniesForForm = computed(() => {
  if (coaForm.value.project_id) {
    // Cari data project yang dipilih
    const selectedProject = dbProjects.value.find(p => p.id === coaForm.value.project_id);
    if (selectedProject && selectedProject.companies) {
      return selectedProject.companies; // Kembalikan hanya PT yang terikat project tsb
    }
    return [];
  }
  return dbCompanies.value; // Kembalikan semua PT jika tidak ada project
});

// Otomatis reset PT jika user ganti project dan PT sebelumnya tidak terikat
watch(() => coaForm.value.project_id, (newProjectId) => {
  if (newProjectId) {
    const validPtIds = availableCompaniesForForm.value.map((c: any) => c.id);
    if (coaForm.value.pt_id && !validPtIds.includes(coaForm.value.pt_id)) {
      coaForm.value.pt_id = ''; // Reset PT karena tidak valid
    }
  }
});
// =======================================================

const fetchMasterData = async () => {
  try {
    const [resCOA, resComp, resProj] = await Promise.all([
      api.get('/accounting/coas'), 
      api.get('/companies'),
      api.get('/projects')
    ]);
    dbCOAs.value = resCOA.data.data || resCOA.data;
    dbCompanies.value = resComp.data.data || resComp.data;
    dbProjects.value = resProj.data.data || resProj.data;
  } catch (error) { 
    console.error(error); 
  }
};

const openCoaModal = (coa: any = null) => {
  if (coa) {
    coaForm.value = { ...coa, pt_id: coa.pt_id || '', project_id: coa.project_id || '' };
  } else {
    const defaultPt = (activeAccEntity.value !== 'all' && activeAccEntity.value !== 'personal') ? activeAccEntity.value : '';
    const defaultCat = activeAccCategory.value !== 'all' ? activeAccCategory.value : (sysConfig.coa_categories?.[0]?.id || 'Asset');
    coaForm.value = { id: null, pt_id: defaultPt as string, project_id: '', code: '', name: '', category: defaultCat };
  }
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
      alert("Akun COA berhasil diperbarui!"); 
    } else { 
      await api.post('/accounting/coas', payload); 
      alert("Akun COA baru berhasil dibuat!"); 
    }
    showCoaModal.value = false; 
    await fetchMasterData(); 
  } catch (error: any) { 
    alert(error.response?.data?.error || "Terjadi kesalahan."); 
  }
};

const handleDeleteCOA = async (id: number) => {
  if (!confirm("Yakin ingin menghapus akun COA ini?")) return;
  try { 
    await api.delete(`/accounting/coas/${id}`); 
    alert("Akun COA berhasil dihapus!"); 
    await fetchMasterData(); 
  } catch (error: any) { 
    alert(error.response?.data?.error || "Gagal menghapus COA. Akun terkait dengan transaksi."); 
  }
};

onMounted(fetchMasterData);
</script>

<template>
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 animate-in fade-in duration-500">
    
    <!-- SIDEBAR NAVIGATION -->
    <div class="lg:col-span-3 space-y-6">
      <div class="bg-white rounded-[2.5rem] p-6 shadow-sm border border-slate-200">
        <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4 ml-2">Business Entities</h3>
        <div class="space-y-2">
          <button @click="activeAccEntity = 'all'" class="w-full flex items-center gap-4 px-5 py-3.5 rounded-2xl transition-all text-left" :class="activeAccEntity === 'all' ? 'bg-indigo-50 border border-indigo-100 text-indigo-600 shadow-sm' : 'hover:bg-slate-50 border border-transparent text-slate-500'"><i class="fas fa-globe text-sm w-4 text-center"></i> <span class="text-[11px] font-black uppercase">Consolidated (All)</span></button>
          <button @click="activeAccEntity = 'personal'" class="w-full flex items-center gap-4 px-5 py-3.5 rounded-2xl transition-all text-left" :class="activeAccEntity === 'personal' ? 'bg-indigo-50 border border-indigo-100 text-indigo-600 shadow-sm' : 'hover:bg-slate-50 border border-transparent text-slate-500'"><i class="fas fa-user-tie text-sm w-4 text-center"></i> <span class="text-[11px] font-black uppercase">Personal / General</span></button>
          <div class="pt-2 border-t border-slate-100 max-h-[220px] overflow-y-auto custom-scrollbar pr-1 space-y-2">
             <button v-for="pt in dbCompanies" :key="pt.id" @click="activeAccEntity = pt.id" class="w-full flex items-center gap-4 px-5 py-3 rounded-2xl transition-all text-left group" :class="activeAccEntity === pt.id ? 'bg-indigo-50 border border-indigo-100 text-indigo-600 shadow-sm' : 'hover:bg-slate-50 border border-transparent text-slate-500'">
               <div class="w-6 h-6 rounded-lg bg-white border flex items-center justify-center transition-colors" :class="activeAccEntity === pt.id ? 'border-indigo-200' : 'border-slate-200 group-hover:border-indigo-200'"><i class="fas fa-building text-[10px]" :class="activeAccEntity === pt.id ? 'text-indigo-500' : 'text-slate-300 group-hover:text-indigo-400'"></i></div>
               <span class="text-[10px] font-black uppercase truncate">{{ pt.name }}</span>
             </button>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-[2.5rem] p-6 shadow-sm border border-slate-200 sticky top-4">
        <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4 ml-2">Account Categories</h3>
        <div class="space-y-2">
          <button v-for="cat in coaCategories" :key="cat.id" @click="activeAccCategory = cat.id" class="w-full flex items-center justify-between px-5 py-3.5 rounded-2xl transition-all text-left group" :class="activeAccCategory === cat.id ? 'bg-slate-900 border border-slate-800 shadow-md' : 'hover:bg-slate-50 border border-transparent'">
            <div class="flex items-center gap-4">
              <i class="fas text-sm w-4 text-center" :class="[cat.icon, activeAccCategory === cat.id ? 'text-white' : cat.color]"></i>
              <span class="text-[10px] font-black uppercase tracking-tight" :class="activeAccCategory === cat.id ? 'text-white' : 'text-slate-500 group-hover:text-slate-700'">{{ cat.label }}</span>
            </div>
            <span class="text-[8px] font-black px-2 py-0.5 rounded-md" :class="activeAccCategory === cat.id ? 'bg-slate-700 text-slate-300' : 'bg-slate-100 text-slate-400'">{{ getCategoryCount(cat.id) }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- MAIN TABLE -->
    <div class="lg:col-span-9 flex flex-col gap-6 min-h-[600px]">
      <div class="bg-white rounded-[2.5rem] p-6 shadow-sm border border-slate-200 flex flex-col md:flex-row justify-between items-center gap-4">
        <div class="relative w-full md:w-96">
          <i class="fas fa-search absolute left-5 top-1/2 -translate-y-1/2 text-slate-300"></i>
          <input v-model="searchCOA" type="text" placeholder="Cari Kode atau Nama Akun..." class="w-full pl-12 pr-6 py-4 bg-slate-50 border border-slate-100 rounded-2xl text-[11px] font-black outline-none focus:ring-2 ring-indigo-100 uppercase tracking-widest text-slate-700">
        </div>
        <button @click="openCoaModal()" class="w-full md:w-auto bg-indigo-600 text-white px-8 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-xl shadow-indigo-100 hover:bg-indigo-700 hover:scale-[0.98] transition-all flex items-center justify-center gap-3">
          <i class="fas fa-plus-circle"></i> Tambah COA
        </button>
      </div>

      <div class="bg-white rounded-[3rem] border border-slate-200 shadow-sm flex-1 overflow-hidden flex flex-col">
        <div class="overflow-x-auto flex-1 p-2">
          <table class="w-full text-left">
            <thead class="bg-slate-50 rounded-t-[2.5rem]">
              <tr class="text-[9px] font-black text-slate-400 uppercase tracking-widest">
                <th class="p-6 rounded-tl-[2rem]">Account Code & Name</th>
                <th class="p-6">Category Type</th>
                <th class="p-6">Affiliated Entity & Project</th>
                <th class="p-6 text-right rounded-tr-[2rem]">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
              
              <tr v-if="filteredCOAs.length === 0">
                <td colspan="4" class="p-20 text-center text-slate-300">
                  <i class="fas fa-folder-open text-5xl mb-4 opacity-50"></i>
                  <p class="text-[10px] font-bold uppercase tracking-widest">Tidak ada akun COA ditemukan pada filter ini</p>
                </td>
              </tr>

              <tr v-for="coa in filteredCOAs" :key="coa.id" class="hover:bg-slate-50/50 transition-colors group">
                <td class="p-6">
                  <div class="flex items-center gap-4">
                    <div class="px-3 py-1.5 bg-indigo-50 border border-indigo-100 rounded-lg text-[10px] font-black text-indigo-600 shadow-sm">{{ coa.code }}</div>
                    <p class="text-xs font-black text-slate-700 uppercase group-hover:text-indigo-600 transition-colors">{{ coa.name }}</p>
                  </div>
                </td>
                <td class="p-6">
                  <span class="text-[10px] font-black uppercase tracking-widest text-slate-500">
                    <i class="fas fa-circle text-[8px] mr-1" :class="{'text-emerald-500': coa.category === 'Asset', 'text-rose-500': coa.category === 'Liability' || coa.category === 'Expense', 'text-indigo-500': coa.category === 'Equity', 'text-emerald-400': coa.category === 'Revenue'}"></i>
                    {{ coa.category }}
                  </span>
                </td>
                
                <!-- VISUALISASI KOLOM ENTITAS & PROJECT TERIKAT -->
                <td class="p-6">
                  <div class="flex flex-col gap-2 items-start">
                    <!-- Badge Project -->
                    <div v-if="coa.project_id" class="inline-flex items-center gap-2 px-3 py-1.5 bg-blue-50 rounded-xl border border-blue-100 w-full max-w-[200px]">
                      <div class="w-5 h-5 rounded-md bg-white border border-blue-100 flex items-center justify-center flex-shrink-0"><i class="fas fa-shield-alt text-[8px] text-blue-500"></i></div>
                      <div class="flex flex-col min-w-0">
                         <span class="text-[7px] font-bold text-blue-400 uppercase tracking-widest leading-none">Project:</span>
                         <span class="text-[10px] font-black text-blue-700 uppercase tracking-tight truncate">{{ coa.project_title || 'Linked Project' }}</span>
                      </div>
                    </div>
                    
                    <!-- Badge Perusahaan -->
                    <div v-if="coa.pt_id" class="inline-flex items-center gap-2 px-3 py-1.5 bg-slate-100 rounded-xl border border-slate-200 w-full max-w-[200px]">
                      <div class="w-5 h-5 rounded-md bg-white border border-slate-200 flex items-center justify-center flex-shrink-0"><i class="fas fa-building text-[8px] text-slate-500"></i></div>
                      <div class="flex flex-col min-w-0">
                         <span class="text-[7px] font-bold text-slate-400 uppercase tracking-widest leading-none">Entity / PT:</span>
                         <span class="text-[10px] font-black text-slate-700 uppercase tracking-tight truncate" :title="dbCompanies.find(c => c.id === coa.pt_id)?.name">
                           {{ dbCompanies.find(c => c.id === coa.pt_id)?.name || 'Unknown PT' }}
                         </span>
                      </div>
                    </div>

                    <!-- Jika General (Tidak ada PT dan Project) -->
                    <div v-if="!coa.pt_id && !coa.project_id" class="inline-flex items-center gap-2 px-3 py-1.5 bg-indigo-50 rounded-xl border border-indigo-100">
                      <div class="w-5 h-5 rounded-md bg-white border border-indigo-100 flex items-center justify-center flex-shrink-0"><i class="fas fa-user-tie text-[8px] text-indigo-400"></i></div>
                      <span class="text-[9px] font-black text-indigo-600 uppercase tracking-widest">Personal / General</span>
                    </div>
                  </div>
                </td>

                <td class="p-6 text-right">
                  <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button @click="openCoaModal(coa)" class="w-8 h-8 rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-indigo-600 hover:border-indigo-200 flex items-center justify-center shadow-sm transition-all"><i class="fas fa-edit text-[10px]"></i></button>
                    <button @click="handleDeleteCOA(coa.id)" class="w-8 h-8 rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-rose-600 hover:border-rose-200 flex items-center justify-center shadow-sm transition-all"><i class="fas fa-trash text-[10px]"></i></button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- MODAL FORM COA (CREATE & EDIT) -->
    <div v-if="showCoaModal" class="fixed inset-0 z-[999] flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showCoaModal = false"></div>
      
      <div class="bg-white rounded-[3rem] w-full max-w-2xl relative z-10 shadow-2xl animate-in zoom-in-95 duration-300">
        
        <div class="p-8 border-b border-slate-100 flex justify-between items-center bg-slate-50/50 rounded-t-[3rem]">
          <div>
            <h3 class="text-xl font-black text-slate-800 uppercase italic tracking-tighter">{{ coaForm.id ? 'Edit Akun COA' : 'Buat Akun COA Baru' }}</h3>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">Pengaturan Master Ledger & Pembukuan</p>
          </div>
          <button @click="showCoaModal = false" class="w-10 h-10 rounded-2xl bg-white border border-slate-200 text-slate-400 hover:bg-rose-50 hover:text-rose-500 hover:border-rose-100 transition-all flex items-center justify-center shadow-sm">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <div class="p-8 space-y-6">
          
          <div class="space-y-4 mb-2 p-5 bg-blue-50/50 border border-blue-100 rounded-2xl shadow-inner">
             <h4 class="text-[10px] font-black text-blue-600 uppercase tracking-widest"><i class="fas fa-link mr-1"></i> Relasi Keterikatan COA</h4>
             
             <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
               <!-- 1. Pilih Project -->
               <div class="space-y-2">
                 <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Relasi Project (Opsional)</label>
                 <div class="relative">
                    <i class="fas fa-shield-alt absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <select v-model="coaForm.project_id" class="w-full bg-white border border-slate-200 rounded-xl pl-10 pr-4 py-3 text-xs font-bold uppercase outline-none focus:ring-2 ring-indigo-100 appearance-none cursor-pointer text-slate-700 shadow-sm">
                      <option value="">-- GENERAL (Tanpa Project) --</option>
                      <option v-for="pj in dbProjects" :key="pj.id" :value="pj.id">{{ pj.project_title }}</option>
                    </select>
                 </div>
               </div>

               <!-- 2. Pilih PT (Filtered Automatically) -->
               <div class="space-y-2">
                 <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Kepemilikan PT</label>
                 <div class="relative">
                    <i class="fas fa-sitemap absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <select v-model="coaForm.pt_id" class="w-full bg-white border border-slate-200 rounded-xl pl-10 pr-4 py-3 text-xs font-bold uppercase outline-none focus:ring-2 ring-indigo-100 appearance-none cursor-pointer text-slate-700 shadow-sm">
                      <option value="">-- PERSONAL / GENERAL ACCOUNT --</option>
                      <option v-for="pt in availableCompaniesForForm" :key="pt.id" :value="pt.id">Corporate: {{ pt.name }}</option>
                    </select>
                 </div>
                 <p v-if="coaForm.project_id" class="text-[8px] font-bold text-emerald-500 italic ml-1 mt-1">* Memfilter PT khusus untuk project ini.</p>
               </div>
             </div>
          </div>

          <div class="grid grid-cols-2 gap-5">
            <div class="space-y-2">
              <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Kode Akun</label>
              <input v-model="coaForm.code" type="text" placeholder="E.G: 1-1001" class="w-full bg-white border border-slate-200 rounded-2xl px-5 py-4 text-sm font-black tracking-wider outline-none focus:ring-2 ring-indigo-100 uppercase placeholder:text-slate-300 shadow-sm">
            </div>
            <div class="space-y-2">
              <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Kategori Akun</label>
              <select v-model="coaForm.category" class="w-full bg-white border border-slate-200 rounded-2xl px-5 py-4 text-xs font-bold uppercase outline-none focus:ring-2 ring-indigo-100 appearance-none cursor-pointer text-slate-700 shadow-sm">
                <option v-for="cat in coaCategories.filter(c => c.id !== 'all')" :key="cat.id" :value="cat.id">{{ cat.id }}</option>
              </select>
            </div>
          </div>

          <div class="space-y-2">
            <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Nama Akun (Account Name)</label>
            <input v-model="coaForm.name" type="text" placeholder="Contoh: Kas Kecil Operasional..." class="w-full bg-white border border-slate-200 rounded-2xl px-5 py-4 text-xs font-bold outline-none focus:ring-2 ring-indigo-100 uppercase placeholder:text-slate-300 shadow-sm">
          </div>
        </div>

        <div class="p-8 border-t border-slate-100 flex justify-end gap-4 bg-slate-50/50 rounded-b-[3rem]">
          <button @click="showCoaModal = false" class="px-8 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest text-slate-500 hover:bg-slate-100 transition-all">Batal</button>
          <button @click="handleSaveCOA" class="bg-indigo-600 text-white px-10 py-4 rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] shadow-xl shadow-indigo-100 hover:bg-indigo-700 active:scale-95 transition-all flex items-center gap-2">
            <i class="fas fa-save"></i> {{ coaForm.id ? 'Simpan Perubahan' : 'Buat Akun COA' }}
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
.animate-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

select {
  -webkit-appearance: none;
  -moz-appearance: none;
  background-image: url("data:image/svg+xml;utf8,<svg fill='%2394a3b8' height='24' viewBox='0 0 24 24' width='24' xmlns='http://www.w3.org/2000/svg'><path d='M7 10l5 5 5-5z'/><path d='M0 0h24v24H0z' fill='none'/></svg>");
  background-repeat: no-repeat;
  background-position-x: 95%;
  background-position-y: 50%;
}
</style>    