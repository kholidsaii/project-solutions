<script setup lang="ts">
import { ref, onMounted } from 'vue';
import api from '../../api/axios';

// ==========================================
// 1. MASTER LABELS (DATABASE)
// ==========================================
const dbLabels = ref<any[]>([]);
const activeSetupNav = ref('labels'); 
const showLabelModal = ref(false);

const labelForm = ref({ id: null as number | null, name: '', color: 'bg-slate-100 text-slate-600' });
const colorOptions = [
  { class: 'bg-rose-100 text-rose-600', name: 'Merah (Urgent/Expense)' },
  { class: 'bg-indigo-100 text-indigo-600', name: 'Ungu (Vendor/B2B)' },
  { class: 'bg-emerald-100 text-emerald-600', name: 'Hijau (Income/Approve)' },
  { class: 'bg-amber-100 text-amber-600', name: 'Kuning (Ops/Warning)' },
  { class: 'bg-slate-100 text-slate-600', name: 'Abu-abu (General)' },
];

const fetchMasterData = async () => {
  try {
    const res = await api.get('/master-data/labels');
    dbLabels.value = res.data.data || res.data;
  } catch (error) { console.error(error); }
};

const handleSaveLabel = async () => {
  try {
    const payload = { name: labelForm.value.name, color: labelForm.value.color };
    if (labelForm.value.id) { await api.put(`/master-data/labels/${labelForm.value.id}`, payload); } 
    else { await api.post('/master-data/labels', payload); }
    showLabelModal.value = false; await fetchMasterData(); 
  } catch (error) { alert("Terjadi kesalahan."); }
};

const handleDeleteLabel = async (id: number) => {
  if (!confirm("Yakin hapus label ini?")) return;
  try { await api.delete(`/master-data/labels/${id}`); await fetchMasterData(); } 
  catch (error) { console.error(error); }
};

// ==========================================
// 2. SYSTEM NAVIGATION CONFIG (LOCAL STORAGE)
// ==========================================
const defaultSysConfig = {
  trx_menus: [
    { id: 'pending', label: 'Pending / Diajukan', icon: 'fa-clock', color: 'text-amber-500', active: true },
    { id: 'approved', label: 'Approved / Sukses', icon: 'fa-check-double', color: 'text-emerald-500', active: true },
    { id: 'income', label: 'Uang Masuk (Income)', icon: 'fa-arrow-down', color: 'text-emerald-500', active: true },
    { id: 'expense', label: 'Uang Keluar (Expense)', icon: 'fa-arrow-up', color: 'text-rose-500', active: true },
  ],
  coa_categories: [
    { id: 'Asset', label: 'Harta (Assets)', icon: 'fa-wallet', color: 'text-emerald-500' },
    { id: 'Liability', label: 'Kewajiban (Liabilities)', icon: 'fa-hand-holding-usd', color: 'text-rose-500' },
    { id: 'Equity', label: 'Modal (Equity)', icon: 'fa-scale-balanced', color: 'text-indigo-500' },
    { id: 'Revenue', label: 'Pendapatan (Revenue)', icon: 'fa-arrow-trend-up', color: 'text-emerald-400' },
    { id: 'Expense', label: 'Beban (Expenses)', icon: 'fa-arrow-trend-down', color: 'text-rose-400' },
  ]
};

const sysConfig = ref<typeof defaultSysConfig>(JSON.parse(localStorage.getItem('kerjapro_finance_setup') || JSON.stringify(defaultSysConfig)));

const saveSysConfig = () => {
  localStorage.setItem('kerjapro_finance_setup', JSON.stringify(sysConfig.value));
  alert('Konfigurasi Navigasi Sistem berhasil diupdate!');
};

const newCoaCat = ref({ id: '', label: '', icon: 'fa-folder', color: 'text-indigo-500' });
const addCoaCategory = () => {
  if (!newCoaCat.value.id || !newCoaCat.value.label) return alert("ID dan Label wajib diisi!");
  sysConfig.value.coa_categories.push({ ...newCoaCat.value });
  newCoaCat.value = { id: '', label: '', icon: 'fa-folder', color: 'text-indigo-500' };
  saveSysConfig();
};

const removeCoaCategory = (index: number) => {
  if (!confirm("Hapus navigasi kategori ini?")) return;
  sysConfig.value.coa_categories.splice(index, 1);
  saveSysConfig();
};

onMounted(fetchMasterData);
</script>

<template>
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 animate-in fade-in duration-500 text-slate-800 font-sans">
    
    <div class="lg:col-span-3 space-y-6">
      <div class="bg-white border border-slate-200 rounded-lg shadow-sm overflow-hidden sticky top-4">
        <div class="p-4 bg-slate-50 border-b border-slate-200 text-center">
          <h3 class="text-sm font-black text-slate-800 tracking-tight">System Preferences</h3>
        </div>
        <div class="flex flex-col p-2 space-y-1">
          <button @click="activeSetupNav = 'labels'" class="px-5 py-3 flex items-center gap-3 text-xs font-bold text-slate-700 hover:bg-slate-50 rounded-md transition-colors" :class="activeSetupNav === 'labels' ? 'bg-slate-100 text-indigo-600' : ''"><i class="fas fa-tags text-slate-400"></i> Transaction Labels</button>
          <button @click="activeSetupNav = 'nav_trx'" class="px-5 py-3 flex items-center gap-3 text-xs font-bold text-slate-700 hover:bg-slate-50 rounded-md transition-colors" :class="activeSetupNav === 'nav_trx' ? 'bg-slate-100 text-indigo-600' : ''"><i class="fas fa-list-ul text-slate-400"></i> Menu Transaksi</button>
          <button @click="activeSetupNav = 'nav_coa'" class="px-5 py-3 flex items-center gap-3 text-xs font-bold text-slate-700 hover:bg-slate-50 rounded-md transition-colors" :class="activeSetupNav === 'nav_coa' ? 'bg-slate-100 text-indigo-600' : ''"><i class="fas fa-sitemap text-slate-400"></i> Kategori COA</button>
        </div>
      </div>
    </div>

    <div class="lg:col-span-9 flex flex-col gap-6 min-h-[600px]">
      
      <div v-if="activeSetupNav === 'labels'" class="flex-1 flex flex-col gap-4">
        
        <div class="bg-white border border-slate-200 rounded-lg p-4 flex flex-col md:flex-row justify-between items-center gap-4 shadow-sm">
          <div class="flex items-center gap-4">
             <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center"><i class="fas fa-tags"></i></div>
             <div>
                <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Master Data: Labels</h3>
                <p class="text-[9px] font-bold text-slate-400 uppercase mt-0.5">Atur label warna untuk tag transaksi</p>
             </div>
          </div>
          <button @click="labelForm = { id: null, name: '', color: 'bg-slate-100 text-slate-600' }; showLabelModal = true" class="bg-indigo-600 text-white px-6 py-2.5 rounded-lg text-xs font-bold uppercase shadow-sm hover:bg-indigo-700 transition-colors flex items-center gap-2"><i class="fas fa-plus"></i> Buat Label Baru</button>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm flex-1 overflow-hidden">
          <table class="w-full text-left">
            <thead class="bg-slate-50 border-b border-slate-200">
              <tr class="text-[10px] font-black text-slate-500 uppercase tracking-widest">
                <th class="p-5">Nama Label</th>
                <th class="p-5 text-center">Visual Preview</th>
                <th class="p-5 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-if="dbLabels.length === 0"><td colspan="3" class="p-12 text-center text-slate-400 text-xs font-bold uppercase tracking-widest border-2 border-dashed">Belum ada data label.</td></tr>
              
              <tr v-for="lbl in dbLabels" :key="lbl.id" class="hover:bg-slate-50/50 transition-colors group">
                <td class="p-5 text-xs font-black text-slate-800 uppercase">{{ lbl.name }}</td>
                <td class="p-5 text-center">
                   <span class="px-4 py-1.5 rounded-full text-[9px] font-black uppercase tracking-widest border shadow-sm" :class="lbl.color"><i class="fas fa-hashtag mr-1 opacity-50"></i> {{ lbl.name }}</span>
                </td>
                <td class="p-5 text-right">
                   <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                      <button @click="labelForm = {...lbl}; showLabelModal = true" class="w-8 h-8 rounded-lg bg-white border border-slate-200 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 flex items-center justify-center transition-colors"><i class="fas fa-edit text-xs"></i></button>
                      <button @click="handleDeleteLabel(lbl.id)" class="w-8 h-8 rounded-lg bg-white border border-slate-200 text-slate-400 hover:text-rose-600 hover:bg-rose-50 flex items-center justify-center transition-colors"><i class="fas fa-trash text-xs"></i></button>
                   </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div v-else-if="activeSetupNav === 'nav_trx'" class="flex-1 flex flex-col gap-4">
        <div class="bg-white rounded-2xl p-8 shadow-sm border border-slate-200">
          <h3 class="text-lg font-black text-slate-800 uppercase tracking-tight mb-2">Visibilitas Menu Transaksi</h3>
          <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Atur (Centang) untuk menampilkan atau menyembunyikan menu di Sidebar Tab Transaksi.</p>
          
          <div class="mt-8 space-y-4">
             <div v-for="(menu, idx) in sysConfig.trx_menus" :key="idx" class="flex items-center justify-between p-5 bg-slate-50 rounded-xl border border-slate-200 hover:border-indigo-300 transition-colors">
                <div class="flex items-center gap-5">
                   <div class="w-12 h-12 rounded-full bg-white border border-slate-200 flex items-center justify-center shadow-sm text-xl" :class="menu.color"><i class="fas" :class="menu.icon"></i></div>
                   <div>
                     <p class="text-sm font-black text-slate-800 uppercase">{{ menu.label }}</p>
                     <p class="text-[9px] font-bold text-slate-400 uppercase mt-1 tracking-widest">Filter System ID: {{ menu.id }}</p>
                   </div>
                </div>
                
                <label class="relative inline-flex items-center cursor-pointer">
                  <input type="checkbox" v-model="menu.active" @change="saveSysConfig" class="sr-only peer">
                  <div class="w-14 h-7 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-1 after:left-1 after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                </label>
             </div>
             
             <div class="p-5 bg-amber-50 rounded-xl border border-amber-200 flex items-center gap-4 mt-6">
               <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center text-amber-500 shrink-0"><i class="fas fa-exclamation-triangle"></i></div>
               <p class="text-[10px] font-bold text-amber-700 uppercase leading-relaxed">Catatan Penting: Status Database seperti <span class="bg-amber-200 text-amber-800 px-1 rounded">Pending</span>, <span class="bg-amber-200 text-amber-800 px-1 rounded">Approved</span>, dan <span class="bg-amber-200 text-amber-800 px-1 rounded">Rejected</span> bersifat mutlak. Penguncian dilakukan oleh sistem untuk menjaga integritas pembukuan Akuntansi & Banking.</p>
             </div>
          </div>
        </div>
      </div>

      <div v-else-if="activeSetupNav === 'nav_coa'" class="flex-1 flex flex-col gap-4">
        <div class="bg-white rounded-2xl p-8 shadow-sm border border-slate-200">
          <h3 class="text-lg font-black text-slate-800 uppercase tracking-tight mb-2">Master Kategori COA</h3>
          <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Kategori ini digunakan untuk mengelompokkan pembuatan akun baru di halaman Accounting.</p>
          
          <div class="mt-8 space-y-4">
             <div v-for="(cat, idx) in sysConfig.coa_categories" :key="idx" class="flex items-center justify-between p-5 bg-slate-50 rounded-xl border border-slate-200 hover:border-indigo-200 transition-colors">
                <div class="flex items-center gap-5">
                   <div class="w-12 h-12 rounded-full bg-white border border-slate-200 flex items-center justify-center shadow-sm text-xl" :class="cat.color"><i class="fas" :class="cat.icon"></i></div>
                   <div>
                     <p class="text-sm font-black text-slate-800 uppercase">{{ cat.label }}</p>
                     <p class="text-[9px] font-bold text-slate-400 uppercase mt-1 tracking-widest">Value Reference ID: {{ cat.id }}</p>
                   </div>
                </div>
                <button @click="removeCoaCategory(idx)" class="w-10 h-10 rounded-lg bg-white border border-slate-200 text-slate-400 hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 transition-colors flex items-center justify-center shadow-sm"><i class="fas fa-trash text-sm"></i></button>
             </div>
          </div>

          <div class="mt-8 p-6 bg-indigo-50 border border-indigo-100 rounded-2xl shadow-inner">
             <h4 class="text-xs font-black text-indigo-700 uppercase tracking-widest mb-4 flex items-center gap-2"><i class="fas fa-folder-plus"></i> Buat Kategori Baru</h4>
             <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div class="space-y-1">
                   <label class="text-[9px] font-black uppercase text-indigo-400 ml-1">Database ID</label>
                   <input v-model="newCoaCat.id" placeholder="E.G. INVENTORY" class="w-full bg-white border border-indigo-100 rounded-lg px-4 py-3 text-xs font-bold outline-none uppercase focus:ring-2 ring-indigo-200 text-slate-700">
                </div>
                <div class="space-y-1">
                   <label class="text-[9px] font-black uppercase text-indigo-400 ml-1">Display Label</label>
                   <input v-model="newCoaCat.label" placeholder="Persediaan Barang" class="w-full bg-white border border-indigo-100 rounded-lg px-4 py-3 text-xs font-bold outline-none uppercase focus:ring-2 ring-indigo-200 text-slate-700">
                </div>
                <div class="space-y-1">
                   <label class="text-[9px] font-black uppercase text-indigo-400 ml-1">FontAwesome Icon</label>
                   <input v-model="newCoaCat.icon" placeholder="fa-boxes" class="w-full bg-white border border-indigo-100 rounded-lg px-4 py-3 text-xs font-bold outline-none focus:ring-2 ring-indigo-200 text-slate-700">
                </div>
                <button @click="addCoaCategory" class="bg-indigo-600 text-white w-full py-3 rounded-lg text-xs font-black uppercase shadow-md hover:bg-indigo-700 transition-colors">Tambah</button>
             </div>
          </div>

        </div>
      </div>

    </div>

    <div v-if="showLabelModal" class="fixed inset-0 z-[999] flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showLabelModal = false"></div>
      
      <div class="bg-white rounded-[2rem] w-full max-w-sm relative z-10 shadow-2xl animate-in zoom-in-95 duration-300">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50 rounded-t-[2rem]">
           <div>
             <h3 class="text-lg font-black text-slate-800 uppercase tracking-tighter">{{ labelForm.id ? 'Edit Label' : 'Label Baru' }}</h3>
           </div>
           <button @click="showLabelModal = false" class="w-8 h-8 rounded-lg bg-white border border-slate-200 text-slate-400 hover:bg-rose-50 hover:text-rose-500 transition-colors flex items-center justify-center"><i class="fas fa-times"></i></button>
        </div>
        
        <div class="p-6 space-y-5">
          <div class="space-y-2">
             <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Nama Label</label>
             <input v-model="labelForm.name" type="text" placeholder="Masukkan nama..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-xs font-bold outline-none uppercase focus:ring-2 ring-indigo-100 text-slate-700">
          </div>
          <div class="space-y-2">
             <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Warna Identitas</label>
             <select v-model="labelForm.color" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-xs font-bold outline-none text-slate-700">
               <option v-for="c in colorOptions" :key="c.class" :value="c.class">{{ c.name }}</option>
             </select>
          </div>
          
          <div class="p-6 border-2 border-dashed border-slate-200 rounded-xl flex items-center justify-center bg-slate-50/50 mt-4">
             <span class="px-5 py-2 rounded-full text-[10px] font-black uppercase tracking-widest border shadow-sm" :class="labelForm.color"><i class="fas fa-hashtag mr-1 opacity-50"></i> {{ labelForm.name || 'Preview Label' }}</span>
          </div>
        </div>
        
        <div class="p-6 border-t border-slate-100 bg-slate-50 rounded-b-[2rem]">
           <button @click="handleSaveLabel" class="w-full bg-indigo-600 text-white py-3.5 rounded-xl text-xs font-black uppercase tracking-widest shadow-md hover:bg-indigo-700 transition-all flex items-center justify-center gap-2">
              <i class="fas fa-save"></i> Simpan Label
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